<script setup>
/*
| The share workflow (26.3-26.5, M5.5-M5.7).
|
| What a client actually does with 400 guests: pick the wording, then work
| down the list opening one WhatsApp chat after another. The position is
| tracked, because nobody does 400 in one sitting and coming back to "where
| was I" is the difference between a feature used once and one used daily.
|
| sent_at is marked as they go — the client's own word for "I sent this one".
| WhatsApp is opened in their app and never reports back, so there is nothing
| else to record.
*/
import { computed, ref, watch } from 'vue';
import MessageTemplateEditor from '@/components/builder/MessageTemplateEditor.vue';
import { useClipboard } from '@/composables/useClipboard';

const props = defineProps({
    invitationKey: { type: String, required: true },
    selectedIds: { type: Array, required: true },
    templatesUrl: { type: String, required: true },
    templateStoreUrl: { type: String, required: true },
    templateItemUrlTemplate: { type: String, required: true },
    resolveUrl: { type: String, required: true },
    markSentUrl: { type: String, required: true },
    variables: { type: Object, required: true },
    request: { type: Function, required: true },
    canEdit: { type: Boolean, default: true },
});

const emit = defineEmits(['sent']);

const { copy, toast } = useClipboard();

const templates = ref([]);
const selectedId = ref(null);
const body = ref('');
const preview = ref('');
const previewName = ref('');
const messages = ref([]);
const position = ref(0);
const open = ref(false);
const busy = ref(false);
const error = ref(null);

const POSITION_KEY = `undangyu:share-position:${props.invitationKey}`;

const templateUrl = (id) => props.templateItemUrlTemplate.replace('__ID__', id);

const current = computed(() => messages.value[position.value] ?? null);
const remaining = computed(() => Math.max(0, messages.value.length - position.value));

/*
| Per-viewer convenience only. The durable record of who has been messaged is
| sent_at on the guest; this just stops a reload from sending the client back
| to guest one.
*/
function rememberPosition() {
    try {
        window.localStorage.setItem(POSITION_KEY, String(position.value));
    } catch {
        // Private windows and blocked site data. Losing the place is survivable.
    }
}

function restorePosition(length) {
    try {
        const stored = Number(window.localStorage.getItem(POSITION_KEY) ?? 0);

        position.value = Number.isFinite(stored) ? Math.min(Math.max(0, stored), Math.max(0, length - 1)) : 0;
    } catch {
        position.value = 0;
    }
}

async function loadTemplates() {
    const { ok, payload } = await props.request(props.templatesUrl);

    if (!ok) {
        return;
    }

    templates.value = payload.data;
    selectedId.value = templates.value[0]?.id ?? null;
    body.value = templates.value[0]?.body ?? '';

    await refreshPreview();
}

let previewTimer = null;

// Debounced: the preview is resolved server-side, and a request per keystroke
// against a 400-guest invitation is a request per keystroke too many.
watch(body, () => {
    window.clearTimeout(previewTimer);
    previewTimer = window.setTimeout(refreshPreview, 400);
});

async function refreshPreview() {
    const { ok, payload } = await props.request(props.resolveUrl, {
        method: 'POST',
        body: { body: body.value, ids: props.selectedIds.slice(0, 1) },
    });

    if (!ok) {
        return;
    }

    preview.value = payload.data[0]?.message ?? '';
    previewName.value = payload.data[0]?.name ?? '';
}

function selectTemplate(id) {
    selectedId.value = id;
    body.value = templates.value.find((template) => template.id === id)?.body ?? '';
}

async function save() {
    busy.value = true;
    error.value = null;

    const { ok, payload } = await props.request(templateUrl(selectedId.value), {
        method: 'PATCH',
        body: { body: body.value },
    });

    busy.value = false;

    if (!ok) {
        error.value = Object.values(payload?.errors ?? {})[0]?.[0] ?? 'Gagal menyimpan templat.';

        return;
    }

    templates.value = templates.value.map((template) => (template.id === payload.data.id ? payload.data : template));
}

async function saveAs(name) {
    busy.value = true;
    error.value = null;

    const { ok, payload } = await props.request(props.templateStoreUrl, {
        method: 'POST',
        body: { name, body: body.value },
    });

    busy.value = false;

    if (!ok) {
        error.value = Object.values(payload?.errors ?? {})[0]?.[0] ?? 'Gagal menyimpan templat.';

        return;
    }

    templates.value = [payload.data, ...templates.value];
    selectedId.value = payload.data.id;
}

async function destroy() {
    if (!window.confirm('Hapus templat ini?')) {
        return;
    }

    const { ok } = await props.request(templateUrl(selectedId.value), { method: 'DELETE' });

    if (ok) {
        await loadTemplates();
    }
}

/*
| Resolve the message for every selected guest. Without a selection it is the
| whole current page's worth of ids, because "copy all messages" with nothing
| ticked should still mean something.
*/
async function resolveSelected() {
    busy.value = true;
    error.value = null;

    const { ok, payload } = await props.request(props.resolveUrl, {
        method: 'POST',
        body: { body: body.value, ids: props.selectedIds },
    });

    busy.value = false;

    if (!ok) {
        error.value = 'Gagal menyiapkan pesan.';

        return false;
    }

    messages.value = payload.data.filter((message) => message.guest_id !== null);
    restorePosition(messages.value.length);

    return true;
}

async function copyAll() {
    if (!(await resolveSelected())) {
        return;
    }

    const text = messages.value
        .map((message) => `--- ${message.name} ---\n${message.message}\n${message.invitation_url}`)
        .join('\n\n');

    await copy(text, `${messages.value.length} pesan disalin`);
}

async function startWalkthrough() {
    if (!(await resolveSelected())) {
        return;
    }

    open.value = true;
}

async function markSent(ids) {
    if (ids.length === 0) {
        return;
    }

    await props.request(props.markSentUrl, { method: 'POST', body: { ids } });

    emit('sent');
}

/*
| Open this guest's chat, record the send, move on. window.open rather than a
| link, because the client is clicking one button repeatedly and a popup
| blocked by the browser has to be visible as such.
*/
async function openCurrent() {
    const message = current.value;

    if (!message) {
        return;
    }

    if (message.whatsapp_url === null) {
        error.value = `${message.name} belum punya nomor WhatsApp.`;
        advance();

        return;
    }

    const opened = window.open(message.whatsapp_url, '_blank', 'noopener');

    if (!opened) {
        error.value = 'Browser memblokir jendela baru. Izinkan popup untuk situs ini.';

        return;
    }

    await markSent([message.guest_id]);
    advance();
}

function advance() {
    error.value = null;
    position.value = Math.min(position.value + 1, messages.value.length);
    rememberPosition();
}

function back() {
    position.value = Math.max(0, position.value - 1);
    rememberPosition();
}

function reset() {
    position.value = 0;
    rememberPosition();
}

loadTemplates();
</script>

<template>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <div>
                    <div class="fw-semibold">Kirim undangan lewat WhatsApp</div>
                    <div class="text-secondary small">
                        Setiap tamu mendapat tautan pribadinya sendiri.
                        <span v-if="selectedIds.length > 0">{{ selectedIds.length }} tamu terpilih.</span>
                        <span v-else>Tidak ada yang dipilih — semua tamu di halaman ini akan dipakai.</span>
                    </div>
                </div>

                <div class="ms-auto d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" :disabled="busy" @click="copyAll">
                        <i class="bi bi-clipboard me-1"></i>Salin semua pesan
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" :disabled="busy || !canEdit"
                            @click="startWalkthrough">
                        <i class="bi bi-whatsapp me-1"></i>Buka WhatsApp satu per satu
                    </button>
                </div>
            </div>

            <!-- The walkthrough: one guest at a time, with the place kept. -->
            <div v-if="open" class="border rounded p-3 mb-3">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="fw-semibold">
                        <template v-if="current">{{ position + 1 }} dari {{ messages.length }} · {{ current.name }}</template>
                        <template v-else>Selesai — {{ messages.length }} tamu</template>
                    </span>
                    <span v-if="current?.sent_at" class="badge text-bg-success">Sudah dikirim</span>
                    <span class="ms-auto text-secondary small">{{ remaining }} tersisa</span>
                </div>

                <div v-if="current" class="border rounded p-2 mb-2 bg-body-tertiary small"
                     style="white-space: pre-wrap;">{{ current.message }}</div>
                <p v-else class="text-secondary small mb-2">Semua tamu terpilih sudah dilalui.</p>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" :disabled="position === 0"
                            @click="back">
                        Sebelumnya
                    </button>
                    <button type="button" class="btn btn-sm btn-success" :disabled="!current" @click="openCurrent">
                        <i class="bi bi-whatsapp me-1"></i>Buka & tandai terkirim
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" :disabled="!current"
                            @click="advance">
                        Lewati
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            :disabled="!current" @click="copy(current.message, 'Pesan disalin')">
                        Salin pesan ini
                    </button>
                    <button type="button" class="btn btn-sm btn-link text-secondary ms-auto" @click="reset">
                        Mulai dari awal
                    </button>
                    <button type="button" class="btn btn-sm btn-link text-secondary" @click="open = false">
                        Tutup
                    </button>
                </div>
            </div>

            <MessageTemplateEditor :templates="templates" :variables="variables" :selected-id="selectedId"
                                   :body="body" :preview="preview" :preview-name="previewName" :saving="busy"
                                   :error="error" :can-edit="canEdit" @select="selectTemplate"
                                   @update:body="body = $event" @save="save" @save-as="saveAs" @destroy="destroy" />

            <div v-if="toast" class="position-fixed bottom-0 start-50 translate-middle-x mb-4" style="z-index: 1080;">
                <div class="alert alert-dark shadow-sm py-2 px-3 mb-0" role="status">{{ toast }}</div>
            </div>
        </div>
    </div>
</template>
