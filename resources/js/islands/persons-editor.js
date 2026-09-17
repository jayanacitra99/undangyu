/*
| Island entry for the builder's "Mempelai" tab (17.1).
|
| Props arrive JSON-encoded on data-* attributes, per docs/05 § 2. AdminLTE
| keeps the rest of the page; this owns one div.
*/
import { createApp } from 'vue';
import PersonsEditor from '@/components/builder/PersonsEditor.vue';

document.querySelectorAll('[data-island="persons-editor"]').forEach((el) => {
    createApp(PersonsEditor, {
        persons: JSON.parse(el.dataset.persons),
        roles: JSON.parse(el.dataset.roles),
        storeUrl: el.dataset.storeUrl,
        reorderUrl: el.dataset.reorderUrl,
        itemUrlTemplate: el.dataset.itemUrlTemplate,
        photoUrlTemplate: el.dataset.photoUrlTemplate,
        csrfToken: el.dataset.csrfToken,
        canEdit: el.dataset.canEdit === '1',
    }).mount(el);
});
