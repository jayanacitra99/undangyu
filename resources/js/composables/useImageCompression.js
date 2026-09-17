/*
| Client-side compression before upload (18.1).
|
| A phone photo is 4000px and 8MB; the largest rendition anyone ever sees is
| 2000px. Sending the original costs the client their data plan and the upload
| its timeout, so the browser resizes first and the server never sees the 8MB.
|
| The server still resizes what arrives (ProcessMediaJob) — this is an economy,
| not a guarantee, and a request that skips the island must still be safe.
*/
export const MAX_EDGE = 2000;
export const QUALITY = 0.85;

const COMPRESSIBLE = ['image/jpeg', 'image/png', 'image/webp'];

export function formatBytes(bytes) {
    if (bytes === null || bytes === undefined) {
        return '—';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit += 1;
    }

    return `${value.toFixed(value >= 10 ? 0 : 1)} ${units[unit]}`;
}

function readImage(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            URL.revokeObjectURL(url);
            resolve(image);
        };

        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('Gambar tidak bisa dibaca.'));
        };

        image.src = url;
    });
}

/*
| Returns { file, originalSize, compressedSize }. On anything it cannot
| handle — a video, an unsupported type, a canvas that refuses — it returns the
| original untouched rather than failing the upload.
*/
export async function compressImage(file) {
    const originalSize = file.size;

    if (!COMPRESSIBLE.includes(file.type)) {
        return { file, originalSize, compressedSize: originalSize };
    }

    try {
        const image = await readImage(file);
        const longest = Math.max(image.width, image.height);
        const scale = longest > MAX_EDGE ? MAX_EDGE / longest : 1;

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(image.width * scale);
        canvas.height = Math.round(image.height * scale);

        const context = canvas.getContext('2d');
        context.drawImage(image, 0, 0, canvas.width, canvas.height);

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', QUALITY));

        // A canvas re-encode of an already-small photo can come out bigger.
        // Then the original is the better file and the resize bought nothing.
        if (blob === null || blob.size >= originalSize) {
            return { file, originalSize, compressedSize: originalSize };
        }

        const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';

        return {
            file: new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }),
            originalSize,
            compressedSize: blob.size,
        };
    } catch {
        return { file, originalSize, compressedSize: originalSize };
    }
}
