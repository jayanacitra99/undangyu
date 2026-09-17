<script setup>
/*
| Story timeline editor island (19.2).
|
| Repeatable entries: a date, a title, the story, and a photo. The date is a
| plain date — "Juli 2019" is a memory, not an appointment — so there is no
| timezone anywhere in this component.
*/
import { ref } from 'vue';
import draggable from 'vuedraggable';
import { useCollectionEditor } from '@/composables/useCollectionEditor';

const props = defineProps({
    stories: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    reorderUrl: { type: String, required: true },
    itemUrlTemplate: { type: String, required: true },
    imageUrlTemplate: { type: String, required: true },
    csrfToken: { type: String, required: true },
    canEdit: { type: Boolean, default: true },
});

const { items, orderStatus, add, remove, touch, flushNow, persistOrder, request, statusOf, errorFor, setStatus, setErrors } =
    useCollectionEditor({
        initial: props.stories,
        storeUrl: props.storeUrl,
        reorderUrl: props.reorderUrl,
        itemUrlTemplate: props.itemUrlTemplate,
        csrfToken: props.csrfToken,
    });

const addError = ref(null);

async function addEntry() {
    addError.value = null;

    const result = await add({ title: 'Momen baru' });

    if (!result.ok) {
        addError.value = Object.values(result.errors ?? {})[0]?.[0] ?? 'Gagal menambah momen.';
    }
}

async function removeEntry(story) {
    if (!window.confirm(`Hapus "${story.title}"?`)) {
        return;
    }

    await remove(story);
}

async function uploadImage(story, event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    setStatus(story.id, 'saving');

    const body = new FormData();
    body.append('image', file);

    const { ok, status, payload } = await request(
        props.imageUrlTemplate.replace('__ID__', story.id),
        { method: 'POST', body, json: false },
    );

    event.target.value = '';

    if (!ok) {
        if (status === 422) {
            setErrors(story.id, payload?.errors ?? {});
        }

        setStatus(story.id, 'failed');

        return;
    }

    items.value = items.value.map((row) =>
        row.id === story.id ? { ...row, image: payload.data.image, image_url: payload.data.image_url } : row,
    );

    setErrors(story.id, {});
    setStatus(story.id, 'saved');
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
            <template #item="{ element: story }">
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span v-if="canEdit" class="drag-handle text-secondary" style="cursor: grab" title="Geser untuk mengurutkan">
                            <i class="bi bi-grip-vertical"></i>
                        </span>
                        <strong class="me-auto">{{ story.title }}</strong>

                        <span v-if="statusOf(story) !== 'idle'" :class="`badge ${statusClass[statusOf(story)]}`">
                            {{ statusLabel[statusOf(story)] }}
                        </span>

                        <button v-if="canEdit" type="button" class="btn btn-sm btn-outline-danger" @click="removeEntry(story)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <img
                                        v-if="story.image_url"
                                        :src="story.image_url"
                                        :alt="story.title"
                                        class="img-fluid rounded mb-2"
                                    >
                                    <div
                                        v-else
                                        class="bg-body-secondary rounded d-flex align-items-center justify-content-center mb-2"
                                        style="height: 6rem"
                                    >
                                        <i class="bi bi-image fs-3 text-secondary"></i>
                                    </div>

                                    <label class="btn btn-sm btn-outline-secondary w-100" :class="{ disabled: !canEdit }">
                                        <i class="bi bi-upload me-1"></i>Foto
                                        <input
                                            type="file"
                                            class="d-none"
                                            accept="image/jpeg,image/png,image/webp"
                                            :disabled="!canEdit"
                                            @change="(event) => uploadImage(story, event)"
                                        >
                                    </label>

                                    <div v-if="errorFor(story, 'image')" class="text-danger small">
                                        {{ errorFor(story, 'image') }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-9">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label" :for="`title-${story.id}`">Judul</label>
                                        <input
                                            :id="`title-${story.id}`"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errorFor(story, 'title') }"
                                            :value="story.title"
                                            :disabled="!canEdit"
                                            @input="(event) => touch(story, 'title', event.target.value)"
                                            @blur="flushNow(story)"
                                        >
                                        <div v-if="errorFor(story, 'title')" class="invalid-feedback d-block">
                                            {{ errorFor(story, 'title') }}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label" :for="`date-${story.id}`">Tanggal</label>
                                        <input
                                            :id="`date-${story.id}`"
                                            type="date"
                                            class="form-control"
                                            :class="{ 'is-invalid': errorFor(story, 'date') }"
                                            :value="story.date ?? ''"
                                            :disabled="!canEdit"
                                            @change="(event) => { touch(story, 'date', event.target.value || null); flushNow(story); }"
                                        >
                                        <div v-if="errorFor(story, 'date')" class="invalid-feedback d-block">
                                            {{ errorFor(story, 'date') }}
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" :for="`description-${story.id}`">Cerita</label>
                                        <textarea
                                            :id="`description-${story.id}`"
                                            rows="3"
                                            class="form-control"
                                            :class="{ 'is-invalid': errorFor(story, 'description') }"
                                            :value="story.description ?? ''"
                                            :disabled="!canEdit"
                                            @input="(event) => touch(story, 'description', event.target.value)"
                                            @blur="flushNow(story)"
                                        ></textarea>
                                        <div v-if="errorFor(story, 'description')" class="invalid-feedback d-block">
                                            {{ errorFor(story, 'description') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </draggable>

        <p v-if="items.length === 0" class="text-secondary">Belum ada momen di linimasa.</p>

        <button v-if="canEdit" type="button" class="btn btn-primary" @click="addEntry">
            <i class="bi bi-plus-lg me-1"></i>Tambah momen
        </button>

        <div v-if="addError" class="text-danger small mt-2">{{ addError }}</div>
    </div>
</template>
