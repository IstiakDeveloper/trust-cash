<template>
    <div class="pwa-manager-root font-sans">
        <!-- 1. Offline / Back Online Network Status Pill -->
        <transition
            enter-active-class="transform transition ease-out duration-300"
            enter-from-class="-translate-y-8 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-8 opacity-0"
        >
            <div
                v-if="!isOnline"
                class="fixed top-3 left-1/2 -translate-x-1/2 z-[9999] px-4 py-2 bg-amber-500/95 dark:bg-amber-600/95 text-white text-xs sm:text-sm font-semibold rounded-full shadow-lg backdrop-blur-md flex items-center gap-2 border border-amber-300/40 pointer-events-auto"
            >
                <span class="w-2.5 h-2.5 rounded-full bg-white animate-ping"></span>
                <i class="fas fa-wifi-slash text-xs"></i>
                <span>অফলাইন মোড সক্রিয় (Offline Mode) — ইন্টারনেট সংযোগ নেই</span>
            </div>
            <div
                v-else-if="showBackOnlineToast"
                class="fixed top-3 left-1/2 -translate-x-1/2 z-[9999] px-4 py-2 bg-emerald-600/95 text-white text-xs sm:text-sm font-semibold rounded-full shadow-lg backdrop-blur-md flex items-center gap-2 border border-emerald-400/40 pointer-events-auto"
            >
                <i class="fas fa-check-circle text-xs"></i>
                <span>ইন্টারনেট সংযোগ ফিরে এসেছে (Back Online)</span>
            </div>
        </transition>

        <!-- 2. Pending Offline Sync Queue Pill -->
        <transition
            enter-active-class="transform transition ease-out duration-300"
            enter-from-class="translate-y-8 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-8 opacity-0"
        >
            <div
                v-if="pendingSyncCount > 0"
                class="fixed bottom-4 left-4 z-[9998] bg-slate-900/95 text-white p-3 rounded-2xl shadow-2xl border border-slate-700/80 backdrop-blur-md max-w-xs flex items-center justify-between gap-3 text-xs"
            >
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="relative flex h-3 w-3 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                    </span>
                    <div class="truncate">
                        <p class="font-bold text-slate-100">
                            {{ pendingSyncCount }} টি অফলাইন বিক্রি বাকি
                        </p>
                        <p class="text-[10px] text-slate-400">
                            {{ isSyncing ? 'সিঙ্ক করা হচ্ছে...' : (isOnline ? 'স্বয়ংক্রিয় সিঙ্ক প্রস্তুত' : 'ইন্টারনেটের অপেক্ষায়') }}
                        </p>
                    </div>
                </div>
                <button
                    v-if="isOnline && !isSyncing"
                    @click="triggerSync"
                    class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-[11px] shrink-0 transition-colors"
                >
                    সিঙ্ক করুন
                </button>
                <i v-else-if="isSyncing" class="fas fa-spinner fa-spin text-emerald-400 shrink-0 mr-1"></i>
            </div>
        </transition>

        <!-- 3. Professional PWA Installation Alert Modal / Banner -->
        <transition
            enter-active-class="transform transition ease-out duration-300"
            enter-from-class="translate-y-12 sm:translate-y-0 sm:scale-95 opacity-0"
            enter-to-class="translate-y-0 sm:scale-100 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 sm:scale-100 opacity-100"
            leave-to-class="translate-y-12 sm:scale-95 opacity-0"
        >
            <div
                v-if="showInstallAlert"
                class="fixed bottom-4 right-4 z-[9999] max-w-sm w-[calc(100vw-2rem)] bg-white dark:bg-slate-850 border border-slate-200 dark:border-slate-700/80 rounded-2xl shadow-2xl p-4 sm:p-5 backdrop-blur-xl dark:bg-slate-900/95"
            >
                <div class="flex items-start gap-3.5">
                    <!-- App Logo Icon -->
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-700 p-0.5 shadow-md shrink-0 flex items-center justify-center overflow-hidden">
                        <img src="/icons/icon-192x192.png" alt="TrustCash" class="w-full h-full object-cover rounded-xl" />
                    </div>

                    <!-- App Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                                TrustCash App
                                <span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 text-[10px] font-extrabold px-1.5 py-0.5 rounded">
                                    PWA
                                </span>
                            </h3>
                            <button
                                @click="dismissInstall"
                                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs p-1"
                                title="Close"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                            সহজে ও দ্রুত ব্যবহারের জন্য অ্যাপটি ডিভাইসে ইনস্টল করুন। অফলাইনে ক্যাশ কাউন্টার ও রসিদ সুবিধা চালু থাকবে।
                        </p>
                    </div>
                </div>

                <!-- Feature tags -->
                <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1">
                        <i class="fas fa-bolt text-amber-500"></i> সুপার ফাস্ট
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                        <i class="fas fa-cloud-arrow-down text-emerald-500"></i> অফলাইন রেডি
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                        <i class="fas fa-shield-alt text-indigo-500"></i> নিরাপদ
                    </span>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-2 mt-3.5">
                    <button
                        @click="dismissInstall"
                        class="flex-1 py-2 px-3 text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-colors text-center"
                    >
                        পরে করব (Later)
                    </button>
                    <button
                        @click="installApp"
                        class="flex-1 py-2 px-3 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-1.5"
                    >
                        <i class="fas fa-download text-xs"></i>
                        <span>ইনস্টল করুন (Install)</span>
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { offlineSync } from '../services/offlineSync'

// State
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true)
const showBackOnlineToast = ref(false)
const showInstallAlert = ref(false)
const deferredPrompt = ref(null)
const pendingSyncCount = ref(0)
const isSyncing = ref(false)

// Check if running in standalone mode (already installed)
const isStandalone = () => {
    if (typeof window === 'undefined') return false
    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true ||
        document.referrer.includes('android-app://')
    )
}

// Update pending outbox count
const checkPendingOutbox = async () => {
    try {
        const pending = await offlineSync.getPendingSales()
        pendingSyncCount.value = pending.length
    } catch (e) {
        console.warn('Pending outbox check warning:', e)
    }
}

// Trigger background sync
const triggerSync = async () => {
    if (!navigator.onLine || isSyncing.value) return
    isSyncing.value = true
    try {
        await offlineSync.syncAllPending(axios)
        await checkPendingOutbox()
    } catch (err) {
        console.error('PwaManager sync error:', err)
    } finally {
        isSyncing.value = false
    }
}

// Install App method
const installApp = async () => {
    if (!deferredPrompt.value) {
        // Fallback tip if event not yet fired or Safari iOS
        alert('ব্রাউজার মেনু থেকে "Add to Home Screen" বা "Install" সিলেক্ট করুন।')
        showInstallAlert.value = false
        return
    }

    try {
        deferredPrompt.value.prompt()
        const choice = await deferredPrompt.value.userChoice
        if (choice.outcome === 'accepted') {
            console.log('User installed TrustCash PWA')
        }
        deferredPrompt.value = null
        showInstallAlert.value = false
    } catch (err) {
        console.error('Install prompt error:', err)
    }
}

// Dismiss install banner
const dismissInstall = () => {
    showInstallAlert.value = false
    try {
        localStorage.setItem('trustcash_pwa_dismissed', Date.now().toString())
    } catch (e) {}
}

// Network status listeners
const handleOnline = () => {
    isOnline.value = true
    showBackOnlineToast.value = true
    setTimeout(() => {
        showBackOnlineToast.value = false
    }, 4500)

    // Automatically sync any offline pending actions
    triggerSync()
}

const handleOffline = () => {
    isOnline.value = false
}

// Service worker registration
const registerServiceWorker = () => {
    if ('serviceWorker' in navigator && process.env.NODE_ENV !== 'test') {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then((reg) => {
                    console.log('TrustCash Service Worker registered successfully:', reg.scope)
                })
                .catch((err) => {
                    console.warn('TrustCash Service Worker registration failed:', err)
                })
        })
    }
}

onMounted(() => {
    // 1. Network event listeners
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)

    // 2. Check pending sync queue
    checkPendingOutbox()
    const outboxInterval = setInterval(checkPendingOutbox, 8000)

    // 3. Register Service Worker
    registerServiceWorker()

    // 4. Capture beforeinstallprompt
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent default browser banner
        e.preventDefault()
        deferredPrompt.value = e

        // Check if previously dismissed within 7 days
        const dismissedAt = localStorage.getItem('trustcash_pwa_dismissed')
        const sevenDays = 7 * 24 * 60 * 60 * 1000
        if (!isStandalone()) {
            if (!dismissedAt || (Date.now() - Number(dismissedAt) > sevenDays)) {
                // Show installation alert
                setTimeout(() => {
                    showInstallAlert.value = true
                }, 2000)
            }
        }
    })

    // Listen for manual install triggers from any menu
    window.addEventListener('trustcash:prompt-install', () => {
        showInstallAlert.value = true
    })

    // App installed event
    window.addEventListener('appinstalled', () => {
        showInstallAlert.value = false
        deferredPrompt.value = null
        console.log('TrustCash PWA was successfully installed!')
    })

    // Cleanup interval on unmount
    onUnmounted(() => {
        clearInterval(outboxInterval)
        window.removeEventListener('online', handleOnline)
        window.removeEventListener('offline', handleOffline)
    })
})
</script>
