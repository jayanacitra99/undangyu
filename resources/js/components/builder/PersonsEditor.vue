<script setup>
/*
| Persons editor island (17.1).
|
| Repeatable cards, roles restricted to the event type's person_roles, photo
| upload, drag-reorder, autosave 800ms after typing stops, per-field errors and
| a save-state badge per card.
|
| Everything it renders came from the server as data — no HTML is passed in, so
| nothing here needs v-html.
*/
import { computed, ref } from 'vue';
import draggable from 'vuedraggable';
import { useCollectionEditor } from '@/composables/useCollectionEditor';

const props = defineProps({
    persons: { type: Array, required: true },
    roles: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    reorderUrl: { type: String, required: true },
    itemUrlTemplate: { type: String, required: true },
    photoUrlTemplate: { type: String, required: true },
    csrfToken: { type: String, required: true },
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
    request,
    statusOf,
    errorFor,
    setStatus,
    setErrors,
} = useCollectionEditor({
    initial: props.persons,
    storeUrl: props.storeUrl,
    reorderUrl: props.reorderUrl,
    itemUrlTemplate: props.itemUrlTemplate,
    csrfToken: props.csrfToken,
});

const addError = ref(null);
const uploading = ref({});

/*
| Roles the event type offers that nobody fills yet. A wedding wants one bride
| and one groom, so the "add" button suggests what is still missing rather than
| offering the same role twice.
*/
const unusedRoles = computed(() => {
    const taken = items.value.map((person) => person.role);

    return props.roles.filter((role) => !taken.includes(role.value));
});

const newRole = ref(null);

const roleLabel = (value) => props.roles.find((role) => role.value === value)?.label ?? value;

// The plain text fields, in the order they read on a card. Role, bio and the
// photo are laid out separately because they are not text inputs.
const TEXT_FIELDS = [
    { key: 'full_name', label: 'Nama lengkap' },
    { key: 'nickname', label: 'Nama panggilan' },
    { key: 'parent_father', label: 'Nama ayah' },
    { key: 'parent_mother', label: 'Nama ibu' },
    { key: 'child_order', label: 'Urutan anak' },
    { key: 'instagram', label: 'Instagram' },
];

async function addPerson() {
    addError.value = null;

    const role = newRole.value ?? unusedRoles.value[0]?.value ?? props.roles[0]?.value;

    if (!role) {
        return;
    }

    const result = await add({ role, full_name: roleLabel(role) });

    if (!result.ok) {
        addError.value = result.errors?.full_name?.[0] ?? result.errors?.role?.[0] ?? 'Gagal menambah.';

        return;
    }

    newRole.value = null;
}

async function removePerson(person) {
    if (!window.confirm(`Hapus ${person.full_name}?`)) {
        return;
    }

    await remove(person);
}

async function uploadPhoto(person, event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    uploading.value = { ...uploading.value, [person.id]: true };
    setStatus(person.id, 'saving');

    const body = new FormData();
    body.append('photo', file);

    const { ok, status, payload } = await request(
        props.photoUrlTemplate.replace('__ID__', person.id),
        { method: 'POST', body, json: false },
    );

    uploading.value = { ...uploading.value, [person.id]: false };
    event.target.value = '';

    if (!ok) {
        if (status === 422) {
            setErrors(person.id, payload?.errors ?? {});
        }

        setStatus(person.id, 'failed');

        return;
    }

    items.value = items.value.map((row) =>
        row.id === person.id ? { ...row, photo: payload.data.photo, photo_url: payload.data.photo_url } : row,
    );

    setErrors(person.id, {});
    setStatus(person.id, 'saved');
}

async function removePhoto(person) {
    const { ok } = await request(props.photoUrlTemplate.replace('__ID__', person.id), { method: 'DELETE' });

    if (!ok) {
        setStatus(person.id, 'failed');

        return;
    }

    items.value = items.value.map((row) =>
        row.id === person.id ? { ...row, photo: null, photo_url: null } : row,
    );
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
            <template #item="{ element: person }">
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span v-if="canEdit" class="drag-handle text-secondary" style="cursor: grab" title="Geser untuk mengurutkan">
                            <i class="bi bi-grip-vertical"></i>
                        </span>
                        <strong class="me-auto">{{ person.role_label }}</strong>

                        <span v-if="statusOf(person) !== 'idle'" :class="`badge ${statusClass[statusOf(person)]}`">
                            {{ statusLabel[statusOf(person)] }}
                        </span>

                        <button
                            v-if="canEdit"
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            @click="removePerson(person)"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <img
                                        v-if="person.photo_url"
                                        :src="person.photo_url"
                                        :alt="person.full_name"
                                        class="img-fluid rounded mb-2"
                                    >
                                    <div v-else class="bg-body-secondary rounded d-flex align-items-center justify-content-center mb-2" style="height: 8rem">
                                        <i class="bi bi-person fs-1 text-secondary"></i>
                                    </div>

                                    <label class="btn btn-sm btn-outline-secondary w-100" :class="{ disabled: !canEdit }">
                                        <i class="bi bi-upload me-1"></i>
                                        {{ uploading[person.id] ? 'Mengunggah…' : 'Foto' }}
                                        <input
                                            type="file"
                                            class="d-none"
                                            accept="image/jpeg,image/png,image/webp"
                                            :disabled="!canEdit"
                                            @change="(event) => uploadPhoto(person, event)"
                                        >
                                    </label>

                                    <button
                                        v-if="person.photo_url && canEdit"
                                        type="button"
                                        class="btn btn-sm btn-link text-danger"
                                        @click="removePhoto(person)"
                                    >
                                        Hapus foto
                                    </button>

                                    <div v-if="errorFor(person, 'photo')" class="text-danger small">
                                        {{ errorFor(person, 'photo') }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-9">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" :for="`role-${person.id}`">Peran</label>
                                        <select
                                            :id="`role-${person.id}`"
                                            class="form-select"
                                            :class="{ 'is-invalid': errorFor(person, 'role') }"
                                            :value="person.role"
                                            :disabled="!canEdit"
                                            @change="(event) => touch(person, 'role', event.target.value)"
                                        >
                                            <option v-for="role in roles" :key="role.value" :value="role.value">
                                                {{ role.label }}
                                            </option>
                                        </select>
                                        <div v-if="errorFor(person, 'role')" class="invalid-feedback d-block">
                                            {{ errorFor(person, 'role') }}
                                        </div>
                                    </div>

                                    <div
                                        v-for="field in TEXT_FIELDS"
                                        :key="field.key"
                                        class="col-md-6"
                                    >
                                        <label class="form-label" :for="`${field.key}-${person.id}`">{{ field.label }}</label>
                                        <input
                                            :id="`${field.key}-${person.id}`"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errorFor(person, field.key) }"
                                            :value="person[field.key] ?? ''"
                                            :disabled="!canEdit"
                                            @input="(event) => touch(person, field.key, event.target.value)"
                                            @blur="flushNow(person)"
                                        >
                                        <div v-if="errorFor(person, field.key)" class="invalid-feedback d-block">
                                            {{ errorFor(person, field.key) }}
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" :for="`bio-${person.id}`">Bio</label>
                                        <textarea
                                            :id="`bio-${person.id}`"
                                            rows="2"
                                            class="form-control"
                                            :class="{ 'is-invalid': errorFor(person, 'bio') }"
                                            :value="person.bio ?? ''"
                                            :disabled="!canEdit"
                                            @input="(event) => touch(person, 'bio', event.target.value)"
                                            @blur="flushNow(person)"
                                        ></textarea>
                                        <div v-if="errorFor(person, 'bio')" class="invalid-feedback d-block">
                                            {{ errorFor(person, 'bio') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </draggable>

        <div v-if="items.length === 0" class="text-secondary mb-3">
            Belum ada siapa pun. Tambahkan mempelai atau tuan rumah acara.
        </div>

        <div v-if="canEdit" class="d-flex gap-2 align-items-center">
            <select v-model="newRole" class="form-select" style="max-width: 16rem">
                <option :value="null">
                    {{ unusedRoles.length ? `Tambah ${unusedRoles[0].label.toLowerCase()}` : 'Pilih peran' }}
                </option>
                <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
            </select>

            <button type="button" class="btn btn-primary" @click="addPerson">
                <i class="bi bi-plus-lg me-1"></i>Tambah
            </button>
        </div>

        <div v-if="addError" class="text-danger small mt-2">{{ addError }}</div>
    </div>
</template>
