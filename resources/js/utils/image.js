/**
 * Resolves the public URL for an uploaded asset (product image, brand logo, category icon, etc.).
 * Supports relative storage paths, full URLs, blob previews, and data URLs.
 * Handles both tenant-specific assets and central storage assets seamlessly.
 *
 * @param {string|object|null|undefined} path
 * @returns {string|null}
 */
export function getImageUrl(path) {
    if (!path) return null;

    if (typeof path === 'object' && path !== null) {
        path = path.url || path.image_url || path.image || '';
    }

    if (!path || typeof path !== 'string') return null;

    const trimmed = path.trim();
    if (!trimmed) return null;

    // Preserve temporary blob and inline base64 data URLs
    if (trimmed.startsWith('blob:') || trimmed.startsWith('data:')) {
        return trimmed;
    }

    let clean = trimmed;

    // If it's a full URL (http://... or https://...), extract the pathname
    if (clean.startsWith('http://') || clean.startsWith('https://')) {
        try {
            const urlObj = new URL(clean);
            clean = urlObj.pathname;
        } catch {
            clean = clean.replace(/^https?:\/\/[^\/]+/, '');
        }
    }

    // Remove leading slashes
    clean = clean.replace(/^\/+/, '');

    // Normalize any tenant asset wrappers or storage prefixes
    if (clean.startsWith('tenancy/assets/storage/')) {
        clean = clean.replace(/^tenancy\/assets\/storage\//, '');
    } else if (clean.startsWith('tenancy/assets/')) {
        clean = clean.replace(/^tenancy\/assets\//, '');
    }

    if (clean.startsWith('storage/')) {
        clean = clean.replace(/^storage\//, '');
    }

    // Remove any remaining leading slashes
    clean = clean.replace(/^\/+/, '');

    if (!clean) return null;

    // Return root-relative /storage/ path which routes through our smart tenant storage router
    return `/storage/${clean}`;
}

export default getImageUrl;

