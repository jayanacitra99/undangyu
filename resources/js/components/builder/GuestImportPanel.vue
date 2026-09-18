<script setup>
/*
| Guest import panel (25.2, 25.3, 25.5, 25.6).
|
| Download the template, upload a filled one, watch it run, read what failed.
| The progress is polled rather than pushed: an import takes seconds, the
| client is looking at the page while it happens, and a websocket for that
| would be infrastructure bought for one screen.
*/
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    latest: { type: Object, default: null },
    templateUrl: { type: String, required: true },
    templateCsvUrl: { type: String, required: true },
    exportUrl: { type: String, required: true },
    uploadUrl: { type: String, required: true },
    statusUrlTemplate: { type: String, required: true },
    errorsUrlTemplate: { type: String, required: true },
    csrfToken: { type: String, required: true },
    canEdit: { type: Boolean, default: true },
});

const emit = defineEmits(['imported']);

const POLL_INTERVAL = 2000;

const current = ref(props.latest);
const uploading = ref(false);
const error = ref(null);
const fileInput = ref(null);

let timer = null;

const running = computed(() => current.value?.is_running === true);

const statusUrl = (id) => props.statusUrlTemplate.replace('__ID__', id);
const errorsUrl = (id) => props.errorsUrlTemplate.replace('__ID__', id);

const percent = computed(() => {
    const done = (current.value?.success_rows ?? 0) + (current.value?.failed_rows ?? 0);
    const total = current.value?.total_rows ?? 0;

    // While the job runs, total_rows is still 0 — it is only known once the
    // file is parsed. An indeterminate bar is the honest answer until then.
    return total === 0 ? null : Math.min(100, Math.round((done / total) * 100));
});

async function poll() {
    const id = current.value?.id;

    if (!id) {
        return;
    }

    const response = await fetch(statusUrl(id), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        stop();

        return;
    }

    const payload = await response.json().catch(() => null);
    const previous = current.value;
    current.value = payload?.data ?? current.value;

    if (!running.value) {
        stop();

        // The table below is showing a page that predates the import.
        emit('imported');

        return;
    }

    // Rows landed since the last poll: the table's counts are already stale.
    if ((current.value?.success_rows ?? 0) !== (previous?.success_rows ?? 0)) {
        emit('imported');
    }
}

function start() {
    stop();
    timer = window.setInterval(poll, POLL_INTERVAL);
}

function stop() {
    window.clearInterval(timer);
    timer = null;
}

onBeforeUnmount(stop);

if (running.value) {
    start();
}

async function upload(event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    uploading.value = true;
    error.value = null;

    const body = new FormData();
    body.append('file', file);

    const response = await fetch(props.uploadUrl, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': props.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body,
    });

    uploading.value = false;

    // The input is cleared either way: picking the same file twice after a
    // failure has to fire a change event.
    if (fileInput.value) {
        fileInput.value.value = '';
    }

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        error.value = Object.values(payload?.errors ?? {})[0]?.[0] ?? 'Gagal mengunggah berkas.';

        return;
    }

    current.value = payload.data;
    start();
}
</script>

<template>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div>
                    <div class="fw-semibold">Impor dari spreadsheet</div>
                    <div class="text-secondary small">
                        Unduh templat, isi daftar tamu Anda, lalu unggah kembali.
                    </div>
                </div>

                <div class="ms-auto d-flex flex-wrap gap-2">
                    <a :href="templateUrl" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-download me-1"></i>Templat XLSX
                    </a>
                    <a :href="templateCsvUrl" class="btn btn-sm btn-outline-secondary">CSV</a>
                    <a :href="exportUrl" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Ekspor tamu
                    </a>

                    <label v-if="canEdit" class="btn btn-sm btn-primary mb-0">
                        <i class="bi bi-upload me-1"></i>
                        {{ uploading ? 'Mengunggah…' : 'Unggah berkas' }}
                        <input ref="fileInput" type="file" class="d-none" accept=".csv,.xlsx,.xls"
                               :disabled="uploading || running" @change="upload">
                    </label>
                </div>
            </div>

            <p v-if="error" class="text-danger small mb-0 mt-2">{{ error }}</p>

            <div v-if="current" class="mt-3">
                <div class="d-flex justify-content-between align-items-center small mb-1">
                    <span>
                        <span class="badge me-1" :class="{
                            'text-bg-secondary': current.status === 'queued',
                            'text-bg-info': current.status === 'processing',
                            'text-bg-success': current.status === 'completed',
                            'text-bg-danger': current.status === 'failed',
                        }">{{ current.status_label }}</span>
                        {{ current.original_filename }}
                    </span>
                    <span class="text-secondary">
                        {{ current.success_rows }} berhasil · {{ current.failed_rows }} gagal
                    </span>
                </div>

                <div v-if="running" class="progress" role="progressbar" aria-label="Kemajuan impor"
                     :aria-valuenow="percent ?? 0" aria-valuemin="0" aria-valuemax="100" style="height: .4rem;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         :style="{ width: percent === null ? '100%' : `${percent}%` }"></div>
                </div>

                <div v-if="current.errors.length > 0" class="mt-2">
                    <ul class="list-unstyled small mb-1">
                        <li v-for="(row, index) in current.errors.slice(0, 5)" :key="index" class="text-danger">
                            Baris {{ row.row }}<span v-if="row.name"> ({{ row.name }})</span>: {{ row.message }}
                        </li>
                    </ul>
                    <a :href="errorsUrl(current.id)" class="small">
                        Unduh laporan kesalahan ({{ current.failed_rows }} baris)
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
