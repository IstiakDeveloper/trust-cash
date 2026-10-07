/**
 * TrustCash Offline Sync & Storage Engine
 * Handles IndexedDB caching for POS products, customers, accounts,
 * and queues offline transactions to auto-sync when online.
 */

const DB_NAME = 'TrustCashOfflineDB';
const DB_VERSION = 1;

let dbPromise = null;

function openDB() {
    if (dbPromise) return dbPromise;

    dbPromise = new Promise((resolve, reject) => {
        if (!('indexedDB' in window)) {
            console.warn('IndexedDB not supported in this browser.');
            resolve(null);
            return;
        }

        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            // POS Products store
            if (!db.objectStoreNames.contains('pos_products')) {
                db.createObjectStore('pos_products', { keyPath: 'id' });
            }
            // POS Customers store
            if (!db.objectStoreNames.contains('pos_customers')) {
                db.createObjectStore('pos_customers', { keyPath: 'id' });
            }
            // Bank Accounts store
            if (!db.objectStoreNames.contains('pos_bank_accounts')) {
                db.createObjectStore('pos_bank_accounts', { keyPath: 'id' });
            }
            // Offline Transactions Outbox
            if (!db.objectStoreNames.contains('pos_outbox')) {
                const outbox = db.createObjectStore('pos_outbox', { keyPath: 'local_id', autoIncrement: true });
                outbox.createIndex('sync_status', 'sync_status', { unique: false });
                outbox.createIndex('created_at', 'created_at', { unique: false });
            }
        };

        request.onsuccess = (event) => {
            resolve(event.target.result);
        };

        request.onerror = (event) => {
            console.error('IndexedDB open error:', event.target.error);
            reject(event.target.error);
        };
    });

    return dbPromise;
}

// Generic transaction helper
async function perform(storeName, mode, callback) {
    const db = await openDB();
    if (!db) return null;

    return new Promise((resolve, reject) => {
        try {
            const tx = db.transaction(storeName, mode);
            const store = tx.objectStore(storeName);
            const result = callback(store);

            tx.oncomplete = () => resolve(result);
            tx.onerror = () => reject(tx.error);
        } catch (err) {
            reject(err);
        }
    });
}

export const offlineSync = {
    /**
     * Cache items in an object store (e.g. 'pos_products')
     */
    async cacheItems(storeName, items) {
        if (!Array.isArray(items) || items.length === 0) return;
        const db = await openDB();
        if (!db) return;

        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);
            // Clear existing cache for fresh snapshot
            store.clear();
            items.forEach((item) => {
                if (item && item.id !== undefined) {
                    store.put(item);
                }
            });
            tx.oncomplete = () => resolve(true);
            tx.onerror = () => reject(tx.error);
        });
    },

    /**
     * Get all cached items from a store
     */
    async getCachedItems(storeName) {
        const db = await openDB();
        if (!db) return [];

        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    },

    /**
     * Save an offline sale to outbox queue
     */
    async queueOfflineSale(saleData) {
        const db = await openDB();
        if (!db) throw new Error('Offline storage is unavailable.');

        const offlineEntry = {
            created_at: new Date().toISOString(),
            offline_token: 'OFFLINE-' + Date.now() + '-' + Math.random().toString(36).substring(2, 7).toUpperCase(),
            sync_status: 'pending',
            retries: 0,
            sale_data: saleData
        };

        return new Promise((resolve, reject) => {
            const tx = db.transaction('pos_outbox', 'readwrite');
            const store = tx.objectStore('pos_outbox');
            const req = store.add(offlineEntry);

            req.onsuccess = (e) => {
                offlineEntry.local_id = e.target.result;
                resolve(offlineEntry);
            };
            req.onerror = () => reject(req.error);
        });
    },

    /**
     * Get all pending offline sales
     */
    async getPendingSales() {
        const db = await openDB();
        if (!db) return [];

        return new Promise((resolve, reject) => {
            const tx = db.transaction('pos_outbox', 'readonly');
            const store = tx.objectStore('pos_outbox');
            const req = store.getAll();

            req.onsuccess = () => {
                const all = req.result || [];
                // Return items not yet synced
                const pending = all.filter(item => item.sync_status !== 'synced');
                resolve(pending);
            };
            req.onerror = () => reject(req.error);
        });
    },

    /**
     * Mark an offline sale as synced or failed
     */
    async updateSaleStatus(localId, status, details = {}) {
        const db = await openDB();
        if (!db) return;

        return new Promise((resolve, reject) => {
            const tx = db.transaction('pos_outbox', 'readwrite');
            const store = tx.objectStore('pos_outbox');
            const req = store.get(localId);

            req.onsuccess = () => {
                const record = req.result;
                if (!record) {
                    resolve(null);
                    return;
                }
                record.sync_status = status;
                record.synced_at = status === 'synced' ? new Date().toISOString() : null;
                record.last_error = details.error || null;
                if (details.server_sale_id) {
                    record.server_sale_id = details.server_sale_id;
                }
                const updateReq = store.put(record);
                updateReq.onsuccess = () => resolve(record);
                updateReq.onerror = () => reject(updateReq.error);
            };
            req.onerror = () => reject(req.error);
        });
    },

    /**
     * Remove synced records older than 7 days to conserve space
     */
    async cleanupSynced() {
        const db = await openDB();
        if (!db) return;

        const tx = db.transaction('pos_outbox', 'readwrite');
        const store = tx.objectStore('pos_outbox');
        const req = store.getAll();

        req.onsuccess = () => {
            const all = req.result || [];
            const sevenDaysAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
            all.forEach((item) => {
                if (item.sync_status === 'synced' && item.synced_at) {
                    if (new Date(item.synced_at).getTime() < sevenDaysAgo) {
                        store.delete(item.local_id);
                    }
                }
            });
        };
    },

    /**
     * Synchronize all pending sales to the server
     */
    async syncAllPending(axiosInstance, onProgress = null) {
        if (!navigator.onLine) {
            return { success: false, message: 'Currently offline' };
        }

        const pending = await this.getPendingSales();
        if (pending.length === 0) {
            return { success: true, count: 0, message: 'All items are synced' };
        }

        let syncedCount = 0;
        let failedCount = 0;

        for (const item of pending) {
            try {
                await this.updateSaleStatus(item.local_id, 'syncing');
                if (onProgress) {
                    onProgress({ current: item, remaining: pending.length - syncedCount });
                }

                // Send to backend POS store route with offline token tag
                const payload = {
                    ...item.sale_data,
                    offline_token: item.offline_token,
                    is_offline_sync: true
                };

                const res = await axiosInstance.post('/admin/pos/store', payload, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const serverSaleId = res.data?.sale?.id || res.data?.id || null;
                await this.updateSaleStatus(item.local_id, 'synced', { server_sale_id: serverSaleId });
                syncedCount++;
            } catch (err) {
                console.error('Failed to sync offline sale #' + item.local_id, err);
                const errorMsg = err.response?.data?.message || err.response?.data?.error || err.message || 'Sync failed';
                await this.updateSaleStatus(item.local_id, 'failed', { error: errorMsg });
                failedCount++;
            }
        }

        await this.cleanupSynced();

        return {
            success: failedCount === 0,
            syncedCount,
            failedCount,
            total: pending.length
        };
    }
};
