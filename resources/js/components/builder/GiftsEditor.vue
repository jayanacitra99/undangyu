<script setup>
/*
| Gifts editor island (19.1).
|
| Repeatable cards with a type switch: bank, e-wallet, QRIS or a postal
| address. Which fields a card shows follows its type, because an address has
| no account number and a QRIS code is an image rather than a string.
*/
import { ref } from 'vue';
import draggable from 'vuedraggable';
import { useCollectionEditor } from '@/composables/useCollectionEditor';

const props = defineProps({
    gifts: { type: Array, required: true },
    types: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    reorderUrl: { type: String, required: true },
    itemUrlTemplate: { type: String, required: true },
    imageUrlTemplate: { type: String, required: true },
    csrfToken: { type: String, required: true },
    canEdit: { type: Boolean, default: true },
});

const { items, orderStatus, add, remove, touch, flushNow, persistOrder, request, statusOf, errorFor, setStatus, setErrors } =
    useCollectionEditor({
        initial: props.gifts,
        storeUrl: props.storeUrl,
        reorderUrl: props.reorderUrl,
        itemUrlTemplate: props.itemUrlTemplate,
        csrfToken: props.csrfToken,
    });

const addError = ref(null);
const newType = ref('bank');

/*
| Mirrors SaveInvitationGift::FIELDS_BY_TYPE. The server is what enforces it;
| this is what stops the client filling in a box that will be cleared.
*/
const FIELDS_BY_TYPE = {
    bank: ['provider_name', 'account_name', 'account_number'],
    ewallet: ['provider_name', 'account_name', 'account_number'],
    qris: ['provider_name', 'account_name'],
    address: ['recipient_name', 'address'],
};

const LABELS = {
    provider_name: 'Nama bank / dompet',
    account_name: 'Nama pemilik',
    account_number: 'Nomor rekening',
    recipient_name: 'Nama penerima',
    address: 'Alamat',
};

const fieldsFor = (gift) => FIELDS_BY_TYPE[gift.type] ?? [];
const showsQris = (gift) => ['qris', 'ewallet'].includes(gift.type);
const typeLabel = (value) => props.types.find((type) => type.value === value)?.label ?? value;

async function addGift() {
    addError.value = null;

    const defaults = {
        bank: { provider_name: 'BCA', account_name: '—', account_number: '—' },
        ewallet: { provider_name: 'GoPay', account_name: '—', account_number: '—' },
        qris: { provider_name: 'QRIS' },
        address: { recipient_name: '—', address: '—' },
    };

    const result = await add({ type: newType.value, ...defaults[newType.value] });

    if (!result.ok) {
        addError.value = Object.values(result.errors ?? {})[0]?.[0] ?? 'Gagal menambah.';
    }
}

async function removeGift(gift) {
    if (!window.confirm(`Hapus ${typeLabel(gift.type)}?`)) {
        return;
    }

    await remove(gift);
}

async function uploadQris(gift, event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    setStatus(gift.id, 'saving');

    const body = new FormData();
    body.append('image', file);

    const { ok, status, payload } = await request(
        props.imageUrlTemplate.replace('__ID__', gift.id),
        { method: 'POST', body, json: false },
    );

    event.target.value = '';

    if (!ok) {
        if (status === 422) {
            setErrors(gift.id, payload?.errors ?? {});
        }

        setStatus(gift.id, 'failed');

        return;
    }

    items.value = items.value.map((row) =>
        row.id === gift.id ? { ...row, qris_image: payload.data.qris_image, qris_url: payload.data.qris_url } : row,
    );

    setErrors(gift.id, {});
    setStatus(gift.id, 'saved');
}

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
            <span v-else class="text-secondary small">Perubahan tersimpan otomatis.</span>
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
            <template #item="{ element: gift }">
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span v-if="canEdit" class="drag-handle text-secondary" style="cursor: grab" title="Geser untuk mengurutkan">
                            <i class="bi bi-grip-vertical"></i>
                        </span>
                        <strong class="me-auto">{{ typeLabel(gift.type) }}</strong>

                        <span v-if="statusOf(gift) !== 'idle'" :class="`badge ${statusClass[statusOf(gift)]}`">
                            {{ statusLabel[statusOf(gift)] }}
                        </span>

                        <button v-if="canEdit" type="button" class="btn btn-sm btn-outline-danger" @click="removeGift(gift)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" :for="`type-${gift.id}`">Jenis</label>
                                <select
                                    :id="`type-${gift.id}`"
                                    class="form-select"
                                    :value="gift.type"
                                    :disabled="!canEdit"
                                    @change="(event) => { touch(gift, 'type', event.target.value); flushNow(gift); }"
                                >
                                    <option v-for="type in types" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </option>
                                </select>
                            </div>

                            <div v-for="field in fieldsFor(gift)" :key="field" class="col-md-4">
                                <label class="form-label" :for="`${field}-${gift.id}`">{{ LABELS[field] }}</label>
                                <input
                                    :id="`${field}-${gift.id}`"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': errorFor(gift, field) }"
                                    :value="gift[field] ?? ''"
                                    :disabled="!canEdit"
                                    @input="(event) => touch(gift, field, event.target.value)"
                                    @blur="flushNow(gift)"
                                >
                                <div v-if="errorFor(gift, field)" class="invalid-feedback d-block">
                                    {{ errorFor(gift, field) }}
                                </div>
                            </div>

                            <div v-if="showsQris(gift)" class="col-md-4">
                                <label class="form-label">Gambar QRIS</label>
                                <div class="d-flex align-items-center gap-2">
                                    <img
                                        v-if="gift.qris_url"
                                        :src="gift.qris_url"
                                        alt="QRIS"
                                        class="rounded border"
                                        style="height: 3.5rem"
                                    >
                                    <label class="btn btn-sm btn-outline-secondary" :class="{ disabled: !canEdit }">
                                        <i class="bi bi-upload me-1"></i>Unggah
                                        <input
                                            type="file"
                                            class="d-none"
                                            accept="image/jpeg,image/png,image/webp"
                                            :disabled="!canEdit"
                                            @change="(event) => uploadQris(gift, event)"
                                        >
                                    </label>
                                </div>
                                <div v-if="errorFor(gift, 'image')" class="text-danger small">
                                    {{ errorFor(gift, 'image') }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" :for="`notes-${gift.id}`">Catatan</label>
                                <input
                                    :id="`notes-${gift.id}`"
                                    type="text"
                                    class="form-control"
                                    :value="gift.notes ?? ''"
                                    :disabled="!canEdit"
                                    @input="(event) => touch(gift, 'notes', event.target.value)"
                                    @blur="flushNow(gift)"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </draggable>

        <p v-if="items.length === 0" class="text-secondary">Belum ada tujuan hadiah.</p>

        <div v-if="canEdit" class="d-flex gap-2 align-items-center">
            <select v-model="newType" class="form-select" style="max-width: 16rem">
                <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
            </select>
            <button type="button" class="btn btn-primary" @click="addGift">
                <i class="bi bi-plus-lg me-1"></i>Tambah
            </button>
        </div>

        <div v-if="addError" class="text-danger small mt-2">{{ addError }}</div>
    </div>
</template>
