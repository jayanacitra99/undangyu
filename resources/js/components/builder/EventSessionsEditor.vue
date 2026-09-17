<script setup>
/*
| Event sessions editor island (17.3).
|
| Akad plus Resepsi is two rows of one invitation, so this is the same
| repeatable-card shape as PersonsEditor: datetime pickers, venue, address,
| Maps link, dress code, live stream, drag-reorder, autosave at 800ms.
|
| Times are wall-clock in the invitation's timezone in both directions. The
| server stores UTC and renders back; nothing here converts anything, which is
| the only way "19:00" survives a round trip intact.
*/
import { ref } from 'vue';
import draggable from 'vuedraggable';
import { useCollectionEditor } from '@/composables/useCollectionEditor';

const props = defineProps({
    events: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    reorderUrl: { type: String, required: true },
    itemUrlTemplate: { type: String, required: true },
    csrfToken: { type: String, required: true },
    timezoneLabel: { type: String, default: '' },
    canEdit: { type: Boolean, default: true },
});

const {
    items,
    orderStatus,
    add,
    remove,
    touch,
    flushNow,
    persistOrder,
    statusOf,
    errorFor,
} = useCollectionEditor({
    initial: props.events,
    storeUrl: props.storeUrl,
    reorderUrl: props.reorderUrl,
    itemUrlTemplate: props.itemUrlTemplate,
    csrfToken: props.csrfToken,
});

const addError = ref(null);

const TEXT_FIELDS = [
    { key: 'venue_name', label: 'Nama tempat' },
    { key: 'dress_code', label: 'Dress code' },
];

/*
| A new session starts on the next round hour, a week out: a date the client
| will change, not a blank field they have to fill before the card saves at all.
*/
function defaultStart() {
    const start = new Date();
    start.setDate(start.getDate() + 7);
    start.setHours(10, 0, 0, 0);

    const pad = (value) => String(value).padStart(2, '0');

    return `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}T${pad(start.getHours())}:00`;
}

async function addSession() {
    addError.value = null;

    const result = await add({
        name: items.value.length === 0 ? 'Akad Nikah' : 'Resepsi',
        start_at: defaultStart(),
        venue_name: 'Belum ditentukan',
    });

    if (!result.ok) {
        addError.value = Object.values(result.errors ?? {})[0]?.[0] ?? 'Gagal menambah acara.';
    }
}

async function removeSession(session) {
    if (!window.confirm(`Hapus acara ${session.name}?`)) {
        return;
    }

    await remove(session);
}

const hasCoordinates = (session) => session.latitude !== null && session.longitude !== null;

const statusLabel = {
    dirty: 'Belum tersimpan',
    saving: 'Menyimpan…',
    saved: 'Tersimpan',
    failed: 'Gagal menyimpan',
};

const statusClass = {
    dirty: 'text-bg-secondary',
    saving: 'text-bg-secondary',
    saved: 'text-bg-success',
    failed: 'text-bg-danger',
};
</script>

<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span v-if="orderStatus === 'saving'" class="badge text-bg-secondary">Menyimpan urutan…</span>
            <span v-else-if="orderStatus === 'saved'" class="badge text-bg-success">Urutan tersimpan</span>
            <span v-else-if="orderStatus === 'failed'" class="badge text-bg-danger">Gagal menyimpan urutan</span>
            <span v-else class="text-secondary small">
                Jam ditulis dalam {{ timezoneLabel }}. Perubahan tersimpan otomatis.
            </span>
        </div>

        <draggable
            v-model="items"
            item-key="id"
            handle=".drag-handle"
            ghost-class="border-primary"
            :animation="150"
            :disabled="!canEdit"
            @end="persistOrder"
        >
            <template #item="{ element: session }">
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span v-if="canEdit" class="drag-handle text-secondary" style="cursor: grab" title="Geser untuk mengurutkan">
                            <i class="bi bi-grip-vertical"></i>
                        </span>
                        <strong class="me-auto">{{ session.name }}</strong>

                        <span v-if="statusOf(session) !== 'idle'" :class="`badge ${statusClass[statusOf(session)]}`">
                            {{ statusLabel[statusOf(session)] }}
                        </span>

                        <button
                            v-if="canEdit"
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            @click="removeSession(session)"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" :for="`name-${session.id}`">Nama acara</label>
                                <input
                                    :id="`name-${session.id}`"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': errorFor(session, 'name') }"
                                    :value="session.name"
                                    :disabled="!canEdit"
                                    @input="(event) => touch(session, 'name', event.target.value)"
                                    @blur="flushNow(session)"
                                >
                                <div v-if="errorFor(session, 'name')" class="invalid-feedback d-block">
                                    {{ errorFor(session, 'name') }}
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" :for="`start-${session.id}`">Mulai</label>
                                <input
                                    :id="`start-${session.id}`"
                                    type="datetime-local"
                                    class="form-control"
                                    :class="{ 'is-invalid': errorFor(session, 'start_at') }"
                                    :value="session.start_at"
                                    :disabled="!canEdit"
                                    @change="(event) => { touch(session, 'start_at', event.target.value); flushNow(session); }"
                                >
                                <div v-if="errorFor(session, 'start_at')" class="invalid-feedback d-block">
                                    {{ errorFor(session, 'start_at') }}
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" :for="`end-${session.id}`">Selesai</label>
                                <input
                                    :id="`end-${session.id}`"
                                    type="datetime-local"
                                    class="form-control"
                                    :class="{ 'is-invalid': errorFor(session, 'end_at') }"
                                    :value="session.end_at ?? ''"
                                    :disabled="!canEdit"
                                    @change="(event) => { touch(session, 'end_at', event.target.value || null); flushNow(session); }"
                                >
                                <div v-if="errorFor(session, 'end_at')" class="invalid-feedback d-block">
                                    {{ errorFor(session, 'end_at') }}
                                </div>
                            </div>

                            <div
                                v-for="field in TEXT_FIELDS"
                                :key="field.key"
                                class="col-md-6"
                            >
                                <label class="form-label" :for="`${field.key}-${session.id}`">{{ field.label }}</label>
                                <input
                                    :id="`${field.key}-${session.id}`"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': errorFor(session, field.key) }"
                                    :value="session[field.key] ?? ''"
                                    :disabled="!canEdit"
                                    @input="(event) => touch(session, field.key, event.target.value)"
                                    @blur="flushNow(session)"
                                >
                                <div v-if="errorFor(session, field.key)" class="invalid-feedback d-block">
                                    {{ errorFor(session, field.key) }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" :for="`address-${session.id}`">Alamat</label>
                                <textarea
                                    :id="`address-${session.id}`"
                                    rows="2"
                                    class="form-control"
                                    :class="{ 'is-invalid': errorFor(session, 'address') }"
                                    :value="session.address ?? ''"
                                    :disabled="!canEdit"
                                    @input="(event) => touch(session, 'address', event.target.value)"
                                    @blur="flushNow(session)"
                                ></textarea>
                                <div v-if="errorFor(session, 'address')" class="invalid-feedback d-block">
                                    {{ errorFor(session, 'address') }}
                                </div>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label" :for="`maps-${session.id}`">Tautan Google Maps</label>
                                <input
                                    :id="`maps-${session.id}`"
                                    type="url"
                                    class="form-control"
                                    :class="{ 'is-invalid': errorFor(session, 'maps_url') }"
                                    :value="session.maps_url ?? ''"
                                    :disabled="!canEdit"
                                    placeholder="https://www.google.com/maps/..."
                                    @input="(event) => touch(session, 'maps_url', event.target.value)"
                                    @blur="flushNow(session)"
                                >
                                <div v-if="errorFor(session, 'maps_url')" class="invalid-feedback d-block">
                                    {{ errorFor(session, 'maps_url') }}
                                </div>
                                <div v-else-if="hasCoordinates(session)" class="form-text text-success">
                                    Koordinat terbaca: {{ session.latitude }}, {{ session.longitude }}
                                </div>
                                <div v-else-if="session.maps_url" class="form-text">
                                    Koordinat tidak terbaca dari tautan ini. Isi manual jika perlu.
                                </div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label" :for="`lat-${session.id}`">Lintang</label>
                                <input
                                    :id="`lat-${session.id}`"
                                    type="text"
                                    inputmode="decimal"
                                    class="form-control"
                                    :class="{ 'is-invalid': errorFor(session, 'latitude') }"
                                    :value="session.latitude ?? ''"
                                    :disabled="!canEdit"
                                    @input="(event) => touch(session, 'latitude', event.target.value || null)"
                                    @blur="flushNow(session)"
                                >
                                <div v-if="errorFor(session, 'latitude')" class="invalid-feedback d-block">
                                    {{ errorFor(session, 'latitude') }}
                                </div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label" :for="`lng-${session.id}`">Bujur</label>
                                <input
                                    :id="`lng-${session.id}`"
                                    type="text"
                                    inputmode="decimal"
                                    class="form-control"
                                    :class="{ 'is-invalid': errorFor(session, 'longitude') }"
                                    :value="session.longitude ?? ''"
                                    :disabled="!canEdit"
                                    @input="(event) => touch(session, 'longitude', event.target.value || null)"
                                    @blur="flushNow(session)"
                                >
                                <div v-if="errorFor(session, 'longitude')" class="invalid-feedback d-block">
                                    {{ errorFor(session, 'longitude') }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" :for="`stream-${session.id}`">Tautan live streaming</label>
                                <input
                                    :id="`stream-${session.id}`"
                                    type="url"
                                    class="form-control"
                                    :class="{ 'is-invalid': errorFor(session, 'live_stream_url') }"
                                    :value="session.live_stream_url ?? ''"
                                    :disabled="!canEdit"
                                    @input="(event) => touch(session, 'live_stream_url', event.target.value)"
                                    @blur="flushNow(session)"
                                >
                                <div v-if="errorFor(session, 'live_stream_url')" class="invalid-feedback d-block">
                                    {{ errorFor(session, 'live_stream_url') }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" :for="`notes-${session.id}`">Catatan</label>
                                <input
                                    :id="`notes-${session.id}`"
                                    type="text"
                                    class="form-control"
                                    :value="session.notes ?? ''"
                                    :disabled="!canEdit"
                                    @input="(event) => touch(session, 'notes', event.target.value)"
                                    @blur="flushNow(session)"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </draggable>

        <div v-if="items.length === 0" class="text-secondary mb-3">
            Belum ada acara. Tambahkan akad, resepsi, atau acara utama.
        </div>

        <button v-if="canEdit" type="button" class="btn btn-primary" @click="addSession">
            <i class="bi bi-plus-lg me-1"></i>Tambah acara
        </button>

        <div v-if="addError" class="text-danger small mt-2">{{ addError }}</div>
    </div>
</template>
