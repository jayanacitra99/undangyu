<script setup>
/*
| Guest table island (24.4, M5.1, M5.10).
|
| Server-side everything: search, group filter, sort, pagination. The list is
| specified to hold 1000+ rows and clients edit it on a phone, so the page only
| ever holds 25 of them.
|
| Editing is one row at a time, expanded in place. Not autosave, unlike the
| builder tabs: a guest is a short form a client fills in once, and a write per
| keystroke across a thousand rows is a write pattern nobody asked for.
*/
import { computed, ref } from 'vue';
import GuestGroupBar from '@/components/builder/GuestGroupBar.vue';
import GuestImportPanel from '@/components/builder/GuestImportPanel.vue';
import GuestShareWorkflow from '@/components/builder/GuestShareWorkflow.vue';
import { useGuestTable } from '@/composables/useGuestTable';
import { useClipboard } from '@/composables/useClipboard';

const props = defineProps({
    guests: { type: Array, required: true },
    pagination: { type: Object, required: true },
    groups: { type: Array, required: true },
    quota: { type: Object, required: true },
    titles: { type: Array, required: true },
    invitationUrl: { type: String, required: true },
    indexUrl: { type: String, required: true },
    storeUrl: { type: String, required: true },
    itemUrlTemplate: { type: String, required: true },
    bulkDeleteUrl: { type: String, required: true },
    bulkGroupUrl: { type: String, required: true },
    groupStoreUrl: { type: String, required: true },
    groupItemUrlTemplate: { type: String, required: true },
    latestImport: { type: Object, default: null },
    templateUrl: { type: String, required: true },
    templateCsvUrl: { type: String, required: true },
    exportUrl: { type: String, required: true },
    importUploadUrl: { type: String, required: true },
    importStatusUrlTemplate: { type: String, required: true },
    importErrorsUrlTemplate: { type: String, required: true },
    invitationKey: { type: String, required: true },
    messageVariables: { type: Object, required: true },
    templatesUrl: { type: String, required: true },
    templateStoreUrl: { type: String, required: true },
    templateItemUrlTemplate: { type: String, required: true },
    resolveUrl: { type: String, required: true },
    markSentUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
    canEdit: { type: Boolean, default: true },
});

const {
    guests,
    groups,
    quota,
    pagination,
    pages,
    filters,
    loading,
    failed,
    load,
    sortBy,
    request,
    selectedIds,
    isSelected,
    toggle,
    toggleAll,
    allSelected,
} = useGuestTable({
    indexUrl: props.indexUrl,
    initialGuests: props.guests,
    initialPagination: props.pagination,
    initialGroups: props.groups,
    initialQuota: props.quota,
    csrfToken: props.csrfToken,
});

const { copy, toast } = useClipboard();

const blank = () => ({
    title: 'Bapak',
    name: '',
    phone: '',
    email: '',
    address: '',
    guest_group_id: '',
    max_pax: 2,
    is_vip: false,
    table_number: '',
    notes: '',
});

const draft = ref(blank());
const adding = ref(false);
const addErrors = ref({});
const editingId = ref(null);
const editDraft = ref({});
const editErrors = ref({});
const busy = ref(false);
const bulkGroup = ref('');

const full = computed(() => quota.remaining !== null && quota.remaining <= 0);

const quotaLabel = computed(() =>
    quota.limit === null ? `${quota.used} tamu` : `${quota.used} dari ${quota.limit} tamu`,
);

const linkFor = (guest) => `${props.invitationUrl}?to=${guest.token}`;

const itemUrl = (guest) => props.itemUrlTemplate.replace('__ID__', guest.id);

// An empty string from a <select> is "no group", which the API wants as null.
const payloadOf = (source) => ({ ...source, guest_group_id: source.guest_group_id === '' ? null : source.guest_group_id });

async function addGuest() {
    if (draft.value.name.trim() === '') {
        addErrors.value = { name: ['Nama wajib diisi.'] };

        return;
    }

    busy.value = true;
    addErrors.value = {};

    const { ok, payload } = await request(props.storeUrl, { method: 'POST', body: payloadOf(draft.value) });

    busy.value = false;

    if (!ok) {
        addErrors.value = payload?.errors ?? { name: ['Gagal menambah tamu.'] };

        return;
    }

    // Reload rather than unshift: the new guest belongs wherever the current
    // sort puts them, and the quota and group counts have both just moved.
    draft.value = blank();
    adding.value = false;
    await load();
}

function startEdit(guest) {
    editingId.value = guest.id;
    editErrors.value = {};
    editDraft.value = {
        title: guest.title ?? '',
        name: guest.name,
        phone: guest.phone ?? '',
        email: guest.email ?? '',
        address: guest.address ?? '',
        guest_group_id: guest.guest_group_id ?? '',
        max_pax: guest.max_pax,
        is_vip: guest.is_vip,
        table_number: guest.table_number ?? '',
        notes: guest.notes ?? '',
    };
}

async function saveEdit(guest) {
    busy.value = true;
    editErrors.value = {};

    const { ok, payload } = await request(itemUrl(guest), { method: 'PATCH', body: payloadOf(editDraft.value) });

    busy.value = false;

    if (!ok) {
        editErrors.value = payload?.errors ?? { name: ['Gagal menyimpan.'] };

        return;
    }

    // The saved row, not the draft: the server normalised the phone number and
    // may have trimmed the name.
    guests.value = guests.value.map((row) => (row.id === guest.id ? payload.data : row));
    editingId.value = null;
}

async function destroy(guest) {
    if (!window.confirm(`Hapus ${guest.display_name}?`)) {
        return;
    }

    const { ok } = await request(itemUrl(guest), { method: 'DELETE' });

    if (ok) {
        await load();
    }
}

async function bulkDelete() {
    const count = selectedIds.value.length;

    if (count === 0 || !window.confirm(`Hapus ${count} tamu terpilih?`)) {
        return;
    }

    busy.value = true;

    const { ok } = await request(props.bulkDeleteUrl, { method: 'POST', body: { ids: selectedIds.value } });

    busy.value = false;

    if (ok) {
        await load();
    }
}

async function bulkAssign() {
    if (selectedIds.value.length === 0) {
        return;
    }

    busy.value = true;

    const { ok } = await request(props.bulkGroupUrl, {
        method: 'POST',
        body: {
            ids: selectedIds.value,
            guest_group_id: bulkGroup.value === '' ? null : bulkGroup.value,
        },
    });

    busy.value = false;

    if (ok) {
        bulkGroup.value = '';
        await load();
    }
}

/*
| One guest, from the row (26.4). The message is resolved server-side against
| the workflow's current template, so the row button and the bulk run cannot
| send different wording.
*/
async function sendOne(guest) {
    const { ok, payload } = await request(props.resolveUrl, {
        method: 'POST',
        body: { ids: [guest.id] },
    });

    const message = ok ? payload?.data?.[0] : null;

    if (!message?.whatsapp_url) {
        window.alert(`${guest.display_name} belum punya nomor WhatsApp.`);

        return;
    }

    window.open(message.whatsapp_url, '_blank', 'noopener');

    await request(props.markSentUrl, { method: 'POST', body: { ids: [guest.id] } });
    await load();
}

const sortIcon = (column) => {
    if (filters.sort !== column) {
        return 'bi-arrow-down-up text-secondary';
    }

    return filters.direction === 'asc' ? 'bi-sort-down-alt' : 'bi-sort-up-alt';
};
</script>

<template>
    <div>
        <!-- Import, export and the template live above the table: they are
             how a real list of 400 gets in there in the first place. -->
        <GuestImportPanel :latest="latestImport" :template-url="templateUrl" :template-csv-url="templateCsvUrl"
                          :export-url="exportUrl" :upload-url="importUploadUrl"
                          :status-url-template="importStatusUrlTemplate"
                          :errors-url-template="importErrorsUrlTemplate" :csrf-token="csrfToken" :can-edit="canEdit"
                          @imported="load()" />

        <!-- Distribution (26.3-26.5): the template, and the run down the list. -->
        <GuestShareWorkflow :invitation-key="invitationKey" :selected-ids="selectedIds" :templates-url="templatesUrl"
                            :template-store-url="templateStoreUrl"
                            :template-item-url-template="templateItemUrlTemplate" :resolve-url="resolveUrl"
                            :mark-sent-url="markSentUrl" :variables="messageVariables" :request="request"
                            :can-edit="canEdit" @sent="load()" />

        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <div class="input-group input-group-sm" style="max-width: 20rem">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input v-model="filters.search" type="search" class="form-control"
                       placeholder="Cari nama, telepon, email, meja" aria-label="Cari tamu">
            </div>

            <select v-model="filters.vip" class="form-select form-select-sm" style="max-width: 10rem"
                    aria-label="Saring VIP">
                <option value="">Semua tamu</option>
                <option value="1">Hanya VIP</option>
                <option value="0">Tanpa VIP</option>
            </select>

            <span class="badge text-bg-light">{{ quotaLabel }}</span>
            <span v-if="loading" class="text-secondary small">Memuat…</span>
            <span v-if="failed" class="text-danger small">Gagal memuat. Coba lagi.</span>

            <button v-if="canEdit" type="button" class="btn btn-sm btn-primary ms-auto" :disabled="full"
                    @click="adding = !adding">
                <i class="bi bi-person-plus me-1"></i>Tambah tamu
            </button>
        </div>

        <p v-if="full" class="alert alert-warning py-2 small">
            Kuota tamu paket ini sudah penuh ({{ quota.limit }} tamu). Tingkatkan paket untuk menambah tamu.
        </p>

        <div class="mb-3">
            <GuestGroupBar :groups="groups" :active="filters.group" :store-url="groupStoreUrl"
                           :item-url-template="groupItemUrlTemplate" :can-edit="canEdit" :request="request"
                           @filter="filters.group = $event" @changed="load()" />
        </div>

        <!-- The add form, opened from the button rather than always on: an
             empty row above a thousand guests is a row nobody scrolled past. -->
        <div v-if="adding && canEdit" class="card mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Sebutan</label>
                        <select v-model="draft.title" class="form-select form-select-sm">
                            <option value="">—</option>
                            <option v-for="title in titles" :key="title" :value="title">{{ title }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1">Nama</label>
                        <input v-model="draft.name" type="text" class="form-control form-control-sm"
                               :class="{ 'is-invalid': addErrors.name }" maxlength="190" @keyup.enter="addGuest">
                        <div v-if="addErrors.name" class="invalid-feedback">{{ addErrors.name[0] }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">WhatsApp</label>
                        <input v-model="draft.phone" type="tel" class="form-control form-control-sm"
                               :class="{ 'is-invalid': addErrors.phone }" placeholder="0812…" maxlength="30">
                        <div v-if="addErrors.phone" class="invalid-feedback">{{ addErrors.phone[0] }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Grup</label>
                        <select v-model="draft.guest_group_id" class="form-select form-select-sm">
                            <option value="">Tanpa grup</option>
                            <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Jumlah orang</label>
                        <input v-model.number="draft.max_pax" type="number" min="1" max="20"
                               class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input id="new-guest-vip" v-model="draft.is_vip" class="form-check-input" type="checkbox">
                            <label class="form-check-label small" for="new-guest-vip">VIP</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary" :disabled="busy" @click="addGuest">
                            Simpan tamu
                        </button>
                        <button type="button" class="btn btn-sm btn-link text-secondary" @click="adding = false">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk bar, only once something is selected. -->
        <div v-if="canEdit && selectedIds.length > 0" class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="small text-secondary">{{ selectedIds.length }} terpilih</span>

            <select v-model="bulkGroup" class="form-select form-select-sm" style="max-width: 12rem"
                    aria-label="Pindahkan ke grup">
                <option value="">Tanpa grup</option>
                <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-primary" :disabled="busy" @click="bulkAssign">
                Pindahkan
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" :disabled="busy" @click="bulkDelete">
                <i class="bi bi-trash me-1"></i>Hapus terpilih
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead>
                    <tr>
                        <th v-if="canEdit" scope="col" style="width: 2rem">
                            <input class="form-check-input" type="checkbox" :checked="allSelected"
                                   aria-label="Pilih semua di halaman ini" @change="toggleAll">
                        </th>
                        <th scope="col">
                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none"
                                    @click="sortBy('name')">
                                Nama <i class="bi" :class="sortIcon('name')"></i>
                            </button>
                        </th>
                        <th scope="col">Grup</th>
                        <th scope="col">WhatsApp</th>
                        <th scope="col" class="text-center">Pax</th>
                        <th scope="col">
                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none"
                                    @click="sortBy('table_number')">
                                Meja <i class="bi" :class="sortIcon('table_number')"></i>
                            </button>
                        </th>
                        <th scope="col">
                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none"
                                    @click="sortBy('opened_at')">
                                Dibuka <i class="bi" :class="sortIcon('opened_at')"></i>
                            </button>
                        </th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="guest in guests" :key="guest.id">
                        <tr>
                            <td v-if="canEdit">
                                <input class="form-check-input" type="checkbox" :checked="isSelected(guest)"
                                       :aria-label="`Pilih ${guest.display_name}`" @change="toggle(guest)">
                            </td>
                            <td>
                                <span v-if="guest.is_vip" class="badge text-bg-warning me-1">VIP</span>
                                <i v-if="guest.sent_at" class="bi bi-send-check text-success me-1"
                                   title="Undangan sudah dikirim"></i>
                                {{ guest.display_name }}
                                <div class="text-secondary small font-monospace">{{ guest.token }}</div>
                            </td>
                            <td>
                                <span v-if="guest.group" class="badge"
                                      :style="{ backgroundColor: guest.group.color ?? '#6c757d' }">
                                    {{ guest.group.name }}
                                </span>
                                <span v-else class="text-secondary small">—</span>
                            </td>
                            <td class="small">{{ guest.phone ?? '—' }}</td>
                            <td class="text-center">{{ guest.max_pax }}</td>
                            <td class="small">{{ guest.table_number ?? '—' }}</td>
                            <td class="small">
                                <span v-if="guest.opened_at">{{ guest.open_count }}×</span>
                                <span v-else class="text-secondary">Belum</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        :aria-label="`Salin tautan ${guest.display_name}`" @click="copy(linkFor(guest))">
                                    <i class="bi bi-link-45deg"></i>
                                </button>
                                <button v-if="canEdit" type="button" class="btn btn-sm btn-outline-success ms-1"
                                        :aria-label="`Kirim WhatsApp ke ${guest.display_name}`"
                                        :disabled="!guest.whatsapp_phone" @click="sendOne(guest)">
                                    <i class="bi bi-whatsapp"></i>
                                </button>
                                <button v-if="canEdit" type="button" class="btn btn-sm btn-outline-secondary ms-1"
                                        :aria-label="`Ubah ${guest.display_name}`"
                                        @click="editingId === guest.id ? (editingId = null) : startEdit(guest)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button v-if="canEdit" type="button" class="btn btn-sm btn-outline-danger ms-1"
                                        :aria-label="`Hapus ${guest.display_name}`" @click="destroy(guest)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>

                        <tr v-if="editingId === guest.id">
                            <td :colspan="canEdit ? 8 : 7" class="bg-body-tertiary">
                                <div class="row g-2">
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small mb-1">Sebutan</label>
                                        <select v-model="editDraft.title" class="form-select form-select-sm">
                                            <option value="">—</option>
                                            <option v-for="title in titles" :key="title" :value="title">{{ title }}</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <label class="form-label small mb-1">Nama</label>
                                        <input v-model="editDraft.name" type="text" class="form-control form-control-sm"
                                               :class="{ 'is-invalid': editErrors.name }" maxlength="190">
                                        <div v-if="editErrors.name" class="invalid-feedback">{{ editErrors.name[0] }}</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small mb-1">WhatsApp</label>
                                        <input v-model="editDraft.phone" type="tel" class="form-control form-control-sm"
                                               :class="{ 'is-invalid': editErrors.phone }" maxlength="30">
                                        <div v-if="editErrors.phone" class="invalid-feedback">{{ editErrors.phone[0] }}</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small mb-1">Email</label>
                                        <input v-model="editDraft.email" type="email" class="form-control form-control-sm"
                                               :class="{ 'is-invalid': editErrors.email }" maxlength="190">
                                        <div v-if="editErrors.email" class="invalid-feedback">{{ editErrors.email[0] }}</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small mb-1">Grup</label>
                                        <select v-model="editDraft.guest_group_id" class="form-select form-select-sm">
                                            <option value="">Tanpa grup</option>
                                            <option v-for="group in groups" :key="group.id" :value="group.id">
                                                {{ group.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small mb-1">Jumlah orang</label>
                                        <input v-model.number="editDraft.max_pax" type="number" min="1" max="20"
                                               class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small mb-1">Meja</label>
                                        <input v-model="editDraft.table_number" type="text"
                                               class="form-control form-control-sm" maxlength="20">
                                    </div>
                                    <div class="col-6 col-md-2 d-flex align-items-end">
                                        <div class="form-check">
                                            <input :id="`vip-${guest.id}`" v-model="editDraft.is_vip"
                                                   class="form-check-input" type="checkbox">
                                            <label class="form-check-label small" :for="`vip-${guest.id}`">VIP</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label small mb-1">Catatan</label>
                                        <input v-model="editDraft.notes" type="text" class="form-control form-control-sm"
                                               maxlength="500">
                                    </div>
                                    <div class="col-12 d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-primary" :disabled="busy"
                                                @click="saveEdit(guest)">
                                            Simpan
                                        </button>
                                        <button type="button" class="btn btn-sm btn-link text-secondary"
                                                @click="editingId = null">
                                            Batal
                                        </button>
                                        <span class="ms-auto small text-secondary align-self-center font-monospace">
                                            {{ linkFor(guest) }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr v-if="guests.length === 0">
                        <td :colspan="canEdit ? 8 : 7" class="text-center text-secondary py-4">
                            Belum ada tamu yang cocok.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="small text-secondary">
                Halaman {{ pagination.current_page }} dari {{ pagination.last_page }} · {{ pagination.total }} tamu
            </span>

            <nav class="ms-auto" aria-label="Navigasi halaman tamu">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                        <button type="button" class="page-link" @click="load(pagination.current_page - 1)">
                            Sebelumnya
                        </button>
                    </li>
                    <li v-for="page in pages" :key="page" class="page-item"
                        :class="{ active: page === pagination.current_page }">
                        <button type="button" class="page-link" @click="load(page)">{{ page }}</button>
                    </li>
                    <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                        <button type="button" class="page-link" @click="load(pagination.current_page + 1)">
                            Berikutnya
                        </button>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- The copy confirmation. Fixed, because the button that triggers it
             may be halfway down a thousand-row table. -->
        <div v-if="toast" class="position-fixed bottom-0 start-50 translate-middle-x mb-4" style="z-index: 1080;">
            <div class="alert alert-dark shadow-sm py-2 px-3 mb-0" role="status">{{ toast }}</div>
        </div>
    </div>
</template>
