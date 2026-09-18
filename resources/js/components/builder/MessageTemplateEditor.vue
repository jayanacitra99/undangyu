<script setup>
/*
| Message template editor (26.2, M8.2).
|
| A picker of saved texts, a body with a variable palette, and a preview
| resolved against a real guest. The preview comes from the server: the
| substitution has one implementation, and it is the one that will actually
| send the 400 messages.
*/
import { computed, ref, watch } from 'vue';

const props = defineProps({
    templates: { type: Array, required: true },
    variables: { type: Object, required: true },
    selectedId: { type: [Number, null], default: null },
    body: { type: String, default: '' },
    preview: { type: String, default: '' },
    previewName: { type: String, default: '' },
    saving: { type: Boolean, default: false },
    error: { type: String, default: null },
    canEdit: { type: Boolean, default: true },
});

const emit = defineEmits(['select', 'update:body', 'save', 'save-as', 'destroy']);

const textarea = ref(null);
const newName = ref('');
const showSaveAs = ref(false);

const selected = computed(
    () => props.templates.find((template) => template.id === props.selectedId) ?? null,
);

const editable = computed(() => props.canEdit && selected.value?.is_editable === true);

/*
| Insert at the caret, not at the end: a client clicking {guest_name} means
| "here", and appending it to the bottom of a five-line message is a small
| betrayal they have to undo by hand every time.
*/
function insert(name) {
    const field = textarea.value;
    const token = `{${name}}`;

    if (!field) {
        emit('update:body', `${props.body}${token}`);

        return;
    }

    const start = field.selectionStart ?? props.body.length;
    const end = field.selectionEnd ?? start;
    const next = props.body.slice(0, start) + token + props.body.slice(end);

    emit('update:body', next);

    // The caret belongs after what was just inserted, and the field has to be
    // refocused because the click moved focus to the button.
    requestAnimationFrame(() => {
        field.focus();
        field.setSelectionRange(start + token.length, start + token.length);
    });
}

watch(
    () => props.selectedId,
    () => {
        showSaveAs.value = false;
        newName.value = '';
    },
);

function saveAs() {
    if (newName.value.trim() === '') {
        return;
    }

    emit('save-as', newName.value.trim());
    newName.value = '';
    showSaveAs.value = false;
}
</script>

<template>
    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <label class="form-label small mb-1" for="message-template">Templat pesan</label>
            <div class="input-group input-group-sm mb-2">
                <select id="message-template" class="form-select" :value="selectedId"
                        @change="emit('select', Number($event.target.value))">
                    <option v-for="template in templates" :key="template.id" :value="template.id">
                        {{ template.name }}{{ template.is_system ? ' (bawaan)' : '' }}
                    </option>
                </select>
                <button v-if="editable" type="button" class="btn btn-outline-danger"
                        :aria-label="`Hapus ${selected?.name}`" @click="emit('destroy')">
                    <i class="bi bi-trash"></i>
                </button>
            </div>

            <div class="d-flex flex-wrap gap-1 mb-2">
                <button v-for="(label, name) in variables" :key="name" type="button"
                        class="btn btn-sm btn-outline-secondary py-0" :title="label" @click="insert(name)">
                    {{ '{' + name + '}' }}
                </button>
            </div>

            <textarea ref="textarea" class="form-control form-control-sm font-monospace" rows="10"
                      :value="body" :readonly="!canEdit" maxlength="4000"
                      @input="emit('update:body', $event.target.value)"></textarea>

            <p v-if="selected?.is_system" class="form-text mb-0">
                Templat bawaan tidak bisa diubah. Simpan sebagai templat baru untuk menyunting.
            </p>
            <p v-if="error" class="text-danger small mb-0">{{ error }}</p>

            <div v-if="canEdit" class="d-flex flex-wrap gap-2 mt-2">
                <button v-if="editable" type="button" class="btn btn-sm btn-primary" :disabled="saving"
                        @click="emit('save')">
                    Simpan perubahan
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="showSaveAs = !showSaveAs">
                    Simpan sebagai templat baru
                </button>
            </div>

            <div v-if="showSaveAs" class="input-group input-group-sm mt-2">
                <input v-model="newName" type="text" class="form-control" placeholder="Nama templat"
                       maxlength="120" @keyup.enter="saveAs">
                <button type="button" class="btn btn-primary" :disabled="saving" @click="saveAs">Simpan</button>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="small text-secondary mb-1">
                Pratinjau
                <span v-if="previewName">— {{ previewName }}</span>
            </div>
            <div class="border rounded p-3 bg-body-tertiary" style="white-space: pre-wrap; min-height: 12rem;">{{ preview }}</div>
        </div>
    </div>
</template>
