<script setup>
/*
| Guest groups (24.5, M5.2).
|
| The filter row and the group editor are the same strip: a client clicks a
| group to filter by it, and edits it in place from the same list. Splitting
| them would mean two screens to answer "which of these is Kantor".
*/
import { ref } from 'vue';

const props = defineProps({
    groups: { type: Array, required: true },
    active: { type: String, default: '' },
    storeUrl: { type: String, required: true },
    itemUrlTemplate: { type: String, required: true },
    canEdit: { type: Boolean, default: true },
    request: { type: Function, required: true },
});

const emit = defineEmits(['filter', 'changed']);

const PALETTE = ['#0d6efd', '#198754', '#dc3545', '#fd7e14', '#6f42c1', '#0dcaf0', '#d63384'];

const editing = ref(null);
const draftName = ref('');
const draftColor = ref(PALETTE[0]);
const newName = ref('');
const newColor = ref(PALETTE[0]);
const error = ref(null);
const busy = ref(false);

const itemUrl = (group) => props.itemUrlTemplate.replace('__ID__', group.id);

// Groups are read back from the server rather than patched locally: the count
// on each chip is the server's, and a bulk assign changes several of them at
// once.
const reload = () => emit('changed');

const firstError = (payload) => Object.values(payload?.errors ?? {})[0]?.[0] ?? 'Gagal menyimpan.';

async function create() {
    if (newName.value.trim() === '') {
        return;
    }

    busy.value = true;
    error.value = null;

    const { ok, payload } = await props.request(props.storeUrl, {
        method: 'POST',
        body: { name: newName.value.trim(), color: newColor.value },
    });

    busy.value = false;

    if (!ok) {
        error.value = firstError(payload);

        return;
    }

    newName.value = '';
    newColor.value = PALETTE[(props.groups.length + 1) % PALETTE.length];
    reload();
}

function startEdit(group) {
    editing.value = group.id;
    draftName.value = group.name;
    draftColor.value = group.color ?? PALETTE[0];
    error.value = null;
}

async function saveEdit(group) {
    busy.value = true;
    error.value = null;

    const { ok, payload } = await props.request(itemUrl(group), {
        method: 'PATCH',
        body: { name: draftName.value.trim(), color: draftColor.value },
    });

    busy.value = false;

    if (!ok) {
        error.value = firstError(payload);

        return;
    }

    editing.value = null;
    reload();
}

async function destroy(group) {
    // Said plainly, because "delete group" reads like it deletes the guests.
    if (!window.confirm(`Hapus grup "${group.name}"? Tamu di dalamnya tidak ikut terhapus.`)) {
        return;
    }

    const { ok } = await props.request(itemUrl(group), { method: 'DELETE' });

    if (ok) {
        reload();
    }
}
</script>

<template>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <button type="button" class="btn btn-sm" :class="active === '' ? 'btn-primary' : 'btn-outline-secondary'"
                @click="emit('filter', '')">
            Semua
        </button>

        <template v-for="group in groups" :key="group.id">
            <div v-if="editing === group.id" class="d-flex align-items-center gap-1">
                <input v-model="draftName" type="text" class="form-control form-control-sm" style="width: 10rem"
                       maxlength="100" @keyup.enter="saveEdit(group)">
                <input v-model="draftColor" type="color" class="form-control form-control-color form-control-sm"
                       aria-label="Warna grup">
                <button type="button" class="btn btn-sm btn-primary" :disabled="busy" @click="saveEdit(group)">
                    Simpan
                </button>
                <button type="button" class="btn btn-sm btn-link text-secondary" @click="editing = null">Batal</button>
            </div>

            <div v-else class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn"
                        :class="active === String(group.id) ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="emit('filter', String(group.id))">
                    <span class="d-inline-block rounded-circle me-1 align-middle"
                          :style="{ width: '.6rem', height: '.6rem', backgroundColor: group.color ?? '#adb5bd' }"></span>
                    {{ group.name }}
                    <span class="badge text-bg-light ms-1">{{ group.guests_count ?? 0 }}</span>
                </button>
                <button v-if="canEdit" type="button" class="btn btn-outline-secondary" :aria-label="`Ubah ${group.name}`"
                        @click="startEdit(group)">
                    <i class="bi bi-pencil"></i>
                </button>
                <button v-if="canEdit" type="button" class="btn btn-outline-secondary" :aria-label="`Hapus ${group.name}`"
                        @click="destroy(group)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </template>

        <button type="button" class="btn btn-sm" :class="active === 'none' ? 'btn-primary' : 'btn-outline-secondary'"
                @click="emit('filter', 'none')">
            Tanpa grup
        </button>

        <div v-if="canEdit" class="d-flex align-items-center gap-1 ms-auto">
            <input v-model="newName" type="text" class="form-control form-control-sm" style="width: 10rem"
                   placeholder="Grup baru" maxlength="100" @keyup.enter="create">
            <input v-model="newColor" type="color" class="form-control form-control-color form-control-sm"
                   aria-label="Warna grup baru">
            <button type="button" class="btn btn-sm btn-outline-primary" :disabled="busy" @click="create">
                Tambah
            </button>
        </div>

        <div v-if="error" class="w-100 text-danger small">{{ error }}</div>
    </div>
</template>
