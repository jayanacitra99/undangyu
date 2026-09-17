<script setup>
/*
| Gallery editor island (18.4).
|
| Drag-and-drop upload with per-file progress, a reorderable grid, caption
| editing, cover selection, delete with confirm, plus the video embed and audio
| library controls (18.5, 18.6).
|
| Uploads go through XMLHttpRequest rather than fetch: progress events are the
| whole point of a progress bar, and fetch still cannot report upload progress.
*/
import { computed, ref } from 'vue';
import draggable from 'vuedraggable';
import { useCollectionEditor } from '@/composables/useCollectionEditor';
import { compressImage, formatBytes } from '@/composables/useImageCompression';

const props = defineProps({
    media: { type: Array, required: true },
    quota: { type: Object, required: true },
    tracks: { type: Array, default: () => [] },
    canUseMusic: { type: Boolean, default: false },
    uploadUrl: { type: String, required: true },
    embeddedUrl: { type: String, required: true },
    reorderUrl: { type: String, required: true },
    itemUrlTemplate: { type: String, required: true },
    coverUrlTemplate: { type: String, required: true },
    maxUploadMb: { type: Number, default: 10 },
    csrfToken: { type: String, required: true },
    canEdit: { type: Boolean, default: true },
});

const { items, orderStatus, remove, touch, flushNow, persistOrder, request, statusOf, errorFor } =
    useCollectionEditor({
        initial: props.media,
        storeUrl: props.uploadUrl,
        reorderUrl: props.reorderUrl,
        itemUrlTemplate: props.itemUrlTemplate,
        csrfToken: props.csrfToken,
    });

const quota = ref({ ...props.quota });
const uploads = ref([]);
const uploadError = ref(null);
const dragOver = ref(false);
const videoUrl = ref('');
const videoError = ref(null);
const selectedTrack = ref(null);

const photos = computed(() => items.value.filter((item) => item.type === 'image'));
const videos = computed(() => items.value.filter((item) => item.type === 'video'));
const audio = computed(() => items.value.find((item) => item.type === 'audio') ?? null);

const photoQuotaLabel = computed(() => {
    const { used, limit } = quota.value.photos;

    return limit === null ? `${used} foto` : `${used} dari ${limit} foto`;
});

const photosFull = computed(() => {
    const { used, limit } = quota.value.photos;

    return limit !== null && used >= limit;
});

/*
| One XHR per file so each gets its own progress bar. The rows live in
| `uploads` until the server answers, then move into the grid.
*/
function upload(file, originalSize, compressedSize) {
    return new Promise((resolve) => {
        const entry = {
            id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
            name: file.name,
            originalSize,
            compressedSize,
            progress: 0,
            error: null,
        };

        uploads.value = [...uploads.value, entry];

        const body = new FormData();
        body.append('file', file);
        body.append('type', file.type.startsWith('video/') ? 'video' : 'image');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', props.uploadUrl);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', props.csrfToken);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.withCredentials = true;

        xhr.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) {
                return;
            }

            const percent = Math.round((event.loaded / event.total) * 100);
            uploads.value = uploads.value.map((row) =>
                row.id === entry.id ? { ...row, progress: percent } : row,
            );
        });

        xhr.addEventListener('load', () => {
            const payload = JSON.parse(xhr.responseText || '{}');

            if (xhr.status >= 200 && xhr.status < 300) {
                items.value = [...items.value, payload.data];

                if (payload.quota) {
                    quota.value = payload.quota;
                }

                uploads.value = uploads.value.filter((row) => row.id !== entry.id);
                resolve(true);

                return;
            }

            // 422 carries the quota message the server wrote, which is the
            // upsell 18.2 asks for — show it as it came rather than inventing
            // a generic failure.
            const message = payload.errors?.file?.[0] ?? payload.message ?? 'Gagal mengunggah.';

            uploads.value = uploads.value.map((row) =>
                row.id === entry.id ? { ...row, error: message } : row,
            );

            resolve(false);
        });

        xhr.addEventListener('error', () => {
            uploads.value = uploads.value.map((row) =>
                row.id === entry.id ? { ...row, error: 'Koneksi terputus.' } : row,
            );

            resolve(false);
        });

        xhr.send(body);
    });
}

async function handleFiles(fileList) {
    uploadError.value = null;

    const files = Array.from(fileList ?? []);

    for (const file of files) {
        const { file: prepared, originalSize, compressedSize } = await compressImage(file);

        if (prepared.size > props.maxUploadMb * 1024 * 1024) {
            uploadError.value = `${file.name} lebih besar dari ${props.maxUploadMb} MB.`;

            continue;
        }

        // Sequential, not parallel: a phone on 3G uploading six photos at once
        // finishes all six slowly and shows six bars crawling.
        await upload(prepared, originalSize, compressedSize);
    }
}

function onDrop(event) {
    dragOver.value = false;

    if (!props.canEdit) {
        return;
    }

    handleFiles(event.dataTransfer?.files);
}

function onPick(event) {
    handleFiles(event.target.files);
    event.target.value = '';
}

function dismissUpload(entry) {
    uploads.value = uploads.value.filter((row) => row.id !== entry.id);
}

async function setCover(item) {
    const { ok } = await request(props.coverUrlTemplate.replace('__ID__', item.id), { method: 'POST' });

    if (!ok) {
        return;
    }

    items.value = items.value.map((row) => ({ ...row, is_cover: row.id === item.id }));
}

async function removeItem(item) {
    if (!window.confirm('Hapus media ini?')) {
        return;
    }

    const { ok, payload } = await request(props.itemUrlTemplate.replace('__ID__', item.id), {
        method: 'DELETE',
    });

    if (!ok) {
        return;
    }

    items.value = items.value.filter((row) => row.id !== item.id);

    if (payload?.quota) {
        quota.value = payload.quota;
    }
}

async function addVideo() {
    videoError.value = null;

    const { ok, payload } = await request(props.embeddedUrl, {
        method: 'POST',
        body: { kind: 'video', url: videoUrl.value },
    });

    if (!ok) {
        videoError.value = payload?.errors?.url?.[0] ?? payload?.message ?? 'Gagal menambah video.';

        return;
    }

    items.value = [...items.value, payload.data];
    videoUrl.value = '';
}

async function chooseTrack() {
    const { ok, payload } = await request(props.embeddedUrl, {
        method: 'POST',
        body: { kind: 'audio', track: selectedTrack.value },
    });

    if (!ok) {
        return;
    }

    items.value = [...items.value.filter((row) => row.type !== 'audio'), payload.data];
}
</script>

<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-secondary small">{{ photoQuotaLabel }}</span>
            <span v-if="orderStatus === 'saving'" class="badge text-bg-secondary">Menyimpan urutan…</span>
            <span v-else-if="orderStatus === 'saved'" class="badge text-bg-success">Urutan tersimpan</span>
            <span v-else-if="orderStatus === 'failed'" class="badge text-bg-danger">Gagal menyimpan urutan</span>
        </div>

        <div
            v-if="canEdit"
            class="border border-2 border-dashed rounded p-4 text-center mb-3"
            :class="dragOver ? 'border-primary bg-body-secondary' : 'border-secondary-subtle'"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="onDrop"
        >
            <i class="bi bi-cloud-arrow-up fs-2 text-secondary d-block mb-2"></i>

            <p v-if="photosFull" class="mb-2 text-danger">
                Kuota foto paket ini sudah penuh. Tingkatkan paket untuk menambah foto.
            </p>
            <p v-else class="mb-2 text-secondary">
                Tarik foto ke sini, atau
                <label class="text-primary" style="cursor: pointer">
                    pilih berkas
                    <input type="file" class="d-none" multiple accept="image/*" @change="onPick">
                </label>
            </p>

            <p class="small text-secondary mb-0">
                Foto dikecilkan otomatis sebelum diunggah. Maksimal {{ maxUploadMb }} MB per berkas.
            </p>
        </div>

        <div v-if="uploadError" class="alert alert-warning py-2">{{ uploadError }}</div>

        <div v-for="entry in uploads" :key="entry.id" class="mb-2">
            <div class="d-flex justify-content-between small">
                <span class="text-truncate me-2">{{ entry.name }}</span>
                <span class="text-secondary">
                    {{ formatBytes(entry.originalSize) }}
                    <template v-if="entry.compressedSize < entry.originalSize">
                        → {{ formatBytes(entry.compressedSize) }}
                    </template>
                </span>
            </div>

            <div v-if="entry.error" class="d-flex justify-content-between align-items-center">
                <span class="text-danger small">{{ entry.error }}</span>
                <button type="button" class="btn btn-sm btn-link" @click="dismissUpload(entry)">Tutup</button>
            </div>

            <div v-else class="progress" style="height: .35rem">
                <div class="progress-bar" :style="{ width: `${entry.progress}%` }"></div>
            </div>
        </div>

        <draggable
            v-model="items"
            item-key="id"
            handle=".drag-handle"
            ghost-class="opacity-50"
            :animation="150"
            :disabled="!canEdit"
            class="row g-3"
            @end="persistOrder"
        >
            <template #item="{ element: item }">
                <div v-if="item.type !== 'audio'" class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100">
                        <div class="position-relative">
                            <img
                                v-if="item.thumb_url"
                                :src="item.thumb_url"
                                :alt="item.caption ?? ''"
                                class="card-img-top"
                                style="aspect-ratio: 4 / 3; object-fit: cover"
                            >
                            <div
                                v-else
                                class="card-img-top bg-body-secondary d-flex align-items-center justify-content-center"
                                style="aspect-ratio: 4 / 3"
                            >
                                <i class="bi fs-2 text-secondary" :class="item.type === 'video' ? 'bi-play-btn' : 'bi-image'"></i>
                            </div>

                            <span v-if="item.is_cover" class="badge text-bg-primary position-absolute top-0 start-0 m-2">
                                Sampul
                            </span>

                            <span
                                v-if="canEdit"
                                class="drag-handle badge text-bg-dark position-absolute top-0 end-0 m-2"
                                style="cursor: grab"
                                title="Geser untuk mengurutkan"
                            >
                                <i class="bi bi-grip-vertical"></i>
                            </span>

                            <span
                                v-if="item.type === 'image' && !item.is_processed"
                                class="badge text-bg-secondary position-absolute bottom-0 start-0 m-2"
                            >
                                Diproses…
                            </span>
                        </div>

                        <div class="card-body p-2">
                            <input
                                type="text"
                                class="form-control form-control-sm mb-2"
                                :class="{ 'is-invalid': errorFor(item, 'caption') }"
                                placeholder="Keterangan"
                                :value="item.caption ?? ''"
                                :disabled="!canEdit"
                                @input="(event) => touch(item, 'caption', event.target.value)"
                                @blur="flushNow(item)"
                            >

                            <div class="d-flex justify-content-between align-items-center">
                                <button
                                    v-if="item.type === 'image' && canEdit"
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    :disabled="item.is_cover"
                                    @click="setCover(item)"
                                >
                                    Jadikan sampul
                                </button>
                                <span v-else class="small text-secondary">{{ formatBytes(item.file_size) }}</span>

                                <button
                                    v-if="canEdit"
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    @click="removeItem(item)"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            <div v-if="statusOf(item) === 'saved'" class="small text-success mt-1">Tersimpan</div>
                            <div v-else-if="statusOf(item) === 'failed'" class="small text-danger mt-1">Gagal menyimpan</div>
                        </div>
                    </div>
                </div>
            </template>
        </draggable>

        <p v-if="photos.length === 0 && videos.length === 0" class="text-secondary mt-3">
            Belum ada foto atau video.
        </p>

        <hr class="my-4">

        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label" for="video-url">Video YouTube atau Vimeo</label>
                <div class="input-group">
                    <input
                        id="video-url"
                        v-model="videoUrl"
                        type="url"
                        class="form-control"
                        :class="{ 'is-invalid': videoError }"
                        placeholder="https://youtu.be/…"
                        :disabled="!canEdit"
                    >
                    <button type="button" class="btn btn-outline-primary" :disabled="!canEdit || !videoUrl" @click="addVideo">
                        Tambah
                    </button>
                </div>
                <div v-if="videoError" class="text-danger small mt-1">{{ videoError }}</div>
                <div v-else class="form-text">Video yang disematkan tidak memakai kuota penyimpanan.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="audio-track">Musik latar</label>

                <div v-if="!canUseMusic" class="form-text">
                    Paket ini belum termasuk musik latar.
                </div>

                <div v-else class="input-group">
                    <select id="audio-track" v-model="selectedTrack" class="form-select" :disabled="!canEdit">
                        <option :value="null">Pilih lagu</option>
                        <option v-for="track in tracks" :key="track.key" :value="track.key">
                            {{ track.title }}<template v-if="track.artist"> — {{ track.artist }}</template>
                        </option>
                    </select>
                    <button type="button" class="btn btn-outline-primary" :disabled="!canEdit || !selectedTrack" @click="chooseTrack">
                        Pakai
                    </button>
                </div>

                <div v-if="audio" class="small text-secondary mt-1">
                    Sedang dipakai: {{ audio.caption }}
                </div>
            </div>
        </div>
    </div>
</template>
