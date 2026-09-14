/*
| Island entry for the catalog index tables (docs/05 § 2).
|
| Mounts into every [data-island="sortable-rows"] on the page and reads its props
| from data-* attributes, JSON-encoded. AdminLTE keeps the rest of the page.
*/
import { createApp } from 'vue';
import SortableRows from '@/components/shared/SortableRows.vue';

document.querySelectorAll('[data-island="sortable-rows"]').forEach((el) => {
    createApp(SortableRows, {
        rows: JSON.parse(el.dataset.rows),
        columns: JSON.parse(el.dataset.columns),
        reorderUrl: el.dataset.reorderUrl,
        editUrlTemplate: el.dataset.editUrlTemplate,
        deleteUrlTemplate: el.dataset.deleteUrlTemplate,
        csrfToken: el.dataset.csrfToken,
        deleteConfirm: el.dataset.deleteConfirm,
    }).mount(el);
});
