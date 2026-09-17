<script setup>
/*
| Sections manager island (19.3).
|
| The invitation's blocks in the order a guest scrolls them: drag to reorder,
| a switch to hide, a field to retitle. A built-in section can be hidden but
| not deleted — it is what the template renders. A custom one is the client's
| own and can be removed.
|
| The body is a plain textarea, not a rich text editor: CLAUDE.md forbids
| v-html on user content, and markup without a sanitiser on the way in is
| markup we cannot safely render.
*/
import { ref } from 'vue';
import draggable from 'vuedraggable';
import { useCollectionEditor } from '@/composables/useCollectionEditor';

const props = defineProps({
    sections: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    reorderUrl: { type: String, required: true },
    itemUrlTemplate: { type: String, required: true },
    csrfToken: { type: String, required: true },
    canEdit: { type: Boolean, default: true },
});

const { items, orderStatus, add, remove, touch, flushNow, persistOrder, statusOf, errorFor } =
    useCollectionEditor({
        initial: props.sections,
        storeUrl: props.storeUrl,
        reorderUrl: props.reorderUrl,
        itemUrlTemplate: props.itemUrlTemplate,
        csrfToken: props.csrfToken,
    });

const addError = ref(null);
const newTitle = ref('');

async function addSection() {
    addError.value = null;

    const result = await add({ title: newTitle.value || 'Bagian baru' });

    if (!result.ok) {
        addError.value = Object.values(result.errors ?? {})[0]?.[0] ?? 'Gagal menambah bagian.';

        return;
    }

    newTitle.value = '';
}

async function removeSection(section) {
    if (!window.confirm(`Hapus bagian "${section.heading}"?`)) {
        return;
    }

    await remove(section);
}

function toggleVisible(section, event) {
    touch(section, 'is_visible', event.target.checked);
    flushNow(section);
}
</script>

<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-secondary small">Urutan di sini adalah urutan yang dilihat tamu.</span>
            <span v-if="orderStatus === 'saving'" class="badge text-bg-secondary">Menyimpan urutan…</span>
            <span v-else-if="orderStatus === 'saved'" class="badge text-bg-success">Urutan tersimpan</span>
            <span v-else-if="orderStatus === 'failed'" class="badge text-bg-danger">Gagal menyimpan urutan</span>
        </div>

        <draggable
            v-model="items"
            item-key="id"
            handle=".drag-handle"
            ghost-class="border-primary"
            :animation="150"
            :disabled="!canEdit"
            class="list-group mb-3"
            @end="persistOrder"
        >
            <template #item="{ element: section }">
                <div class="list-group-item">
                    <div class="d-flex align-items-center gap-2">
                        <span v-if="canEdit" class="drag-handle text-secondary" style="cursor: grab" title="Geser untuk mengurutkan">
                            <i class="bi bi-grip-vertical"></i>
                        </span>

                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2">
                                <input
                                    type="text"
                                    class="form-control form-control-sm"
                                    :class="{ 'is-invalid': errorFor(section, 'title') }"
                                    :placeholder="section.default_label"
                                    :value="section.title ?? ''"
                                    :disabled="!canEdit"
                                    @input="(event) => touch(section, 'title', event.target.value)"
                                    @blur="flushNow(section)"
                                >

                                <span v-if="!section.is_custom" class="badge text-bg-light text-secondary">
                                    {{ section.default_label }}
                                </span>
                            </div>

                            <textarea
                                v-if="section.is_custom"
                                rows="2"
                                class="form-control form-control-sm mt-2"
                                placeholder="Isi bagian ini"
                                :value="section.body ?? ''"
                                :disabled="!canEdit"
                                @input="(event) => touch(section, 'body', event.target.value)"
                                @blur="flushNow(section)"
                            ></textarea>
                        </div>

                        <div class="form-check form-switch mb-0">
                            <input
                                :id="`visible-${section.id}`"
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                :checked="section.is_visible"
                                :disabled="!canEdit"
                                @change="(event) => toggleVisible(section, event)"
                            >
                            <label class="form-check-label small" :for="`visible-${section.id}`">Tampil</label>
                        </div>

                        <button
                            v-if="section.is_custom && canEdit"
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            @click="removeSection(section)"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    <div v-if="statusOf(section) === 'saved'" class="small text-success mt-1">Tersimpan</div>
                    <div v-else-if="statusOf(section) === 'failed'" class="small text-danger mt-1">Gagal menyimpan</div>
                </div>
            </template>
        </draggable>

        <div v-if="canEdit" class="d-flex gap-2">
            <input
                v-model="newTitle"
                type="text"
                class="form-control"
                placeholder="Judul bagian baru"
                style="max-width: 20rem"
            >
            <button type="button" class="btn btn-outline-primary" @click="addSection">
                <i class="bi bi-plus-lg me-1"></i>Tambah bagian
            </button>
        </div>

        <div v-if="addError" class="text-danger small mt-2">{{ addError }}</div>
    </div>
</template>
