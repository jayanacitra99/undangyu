<script setup>
/*
| Drag-to-sort table body for the catalog index pages (M3.1, M3.2).
|
| An island, not a page takeover: it owns one <table> and nothing else. Rows
| arrive already rendered to plain text by the server — no HTML is passed in, so
| nothing here needs v-html.
|
| vuedraggable is the project's drag library (docs/05 § 4); Session 19 reorders
| invitation sections with the same component.
*/
import { ref } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps({
    rows: { type: Array, required: true },
    columns: { type: Array, required: true },
    reorderUrl: { type: String, required: true },
    editUrlTemplate: { type: String, required: true },
    deleteUrlTemplate: { type: String, required: true },
    csrfToken: { type: String, required: true },
    deleteConfirm: { type: String, default: 'Hapus baris ini?' },
});

const items = ref([...props.rows]);
const status = ref('idle');

/*
| Reordering always posts ids, but a model bound on something else — templates
| bind on their slug — sends route_key for the edit and delete links.
*/
const urlFor = (template, row) => template.replace('__ID__', row.route_key ?? row.id);

/*
| Two-state resources send is_active and get Aktif/Nonaktif. A resource with
| more states than that — templates are draft, published or archived — sends
| its own badge_label and badge_class per row instead.
*/
const badgeLabelFor = (row) => row.badge_label ?? (row.is_active ? 'Aktif' : 'Nonaktif');

const badgeClassFor = (row) =>
    `badge ${row.badge_class ?? (row.is_active ? 'text-bg-success' : 'text-bg-secondary')}`;

async function persistOrder() {
    status.value = 'saving';

    try {
        const response = await fetch(props.reorderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': props.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ ids: items.value.map((item) => item.id) }),
        });

        status.value = response.ok ? 'saved' : 'failed';
    } catch {
        status.value = 'failed';
    }
}
</script>

<template>
    <div>
        <div class="d-flex justify-content-end mb-2" aria-live="polite">
            <span v-if="status === 'saving'" class="badge text-bg-secondary">Menyimpan urutan…</span>
            <span v-else-if="status === 'saved'" class="badge text-bg-success">Urutan tersimpan</span>
            <span v-else-if="status === 'failed'" class="badge text-bg-danger">Gagal menyimpan urutan</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col" style="width: 3rem"><span class="visually-hidden">Urutan</span></th>
                        <th v-for="column in columns" :key="column" scope="col">{{ column }}</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>

                <draggable
                    v-model="items"
                    tag="tbody"
                    item-key="id"
                    handle=".drag-handle"
                    ghost-class="table-active"
                    :animation="150"
                    @end="persistOrder"
                >
                    <template #item="{ element: row }">
                        <tr :data-id="row.id">
                            <td>
                                <span class="drag-handle text-secondary" style="cursor: grab" title="Geser untuk mengurutkan">
                                    <i class="bi bi-grip-vertical"></i>
                                </span>
                            </td>
                            <td v-for="(cell, index) in row.cells" :key="index">
                                <span :class="{ 'fw-semibold': index === 0 }">{{ cell }}</span>
                            </td>
                            <td class="text-end">
                                <span :class="badgeClassFor(row)">{{ badgeLabelFor(row) }}</span>
                                <a :href="urlFor(editUrlTemplate, row)" class="btn btn-sm btn-outline-primary ms-2">Ubah</a>
                                <form
                                    :action="urlFor(deleteUrlTemplate, row)"
                                    method="POST"
                                    class="d-inline"
                                    @submit="(event) => { if (!confirm(deleteConfirm)) event.preventDefault(); }"
                                >
                                    <input type="hidden" name="_token" :value="csrfToken">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    </template>
                </draggable>
            </table>
        </div>
    </div>
</template>
