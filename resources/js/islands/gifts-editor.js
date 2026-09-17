/*
| Island entry for the builder's "Hadiah" tab (19.1).
*/
import { createApp } from 'vue';
import GiftsEditor from '@/components/builder/GiftsEditor.vue';

document.querySelectorAll('[data-island="gifts-editor"]').forEach((el) => {
    createApp(GiftsEditor, {
        gifts: JSON.parse(el.dataset.gifts),
        types: JSON.parse(el.dataset.types),
        storeUrl: el.dataset.storeUrl,
        reorderUrl: el.dataset.reorderUrl,
        itemUrlTemplate: el.dataset.itemUrlTemplate,
        imageUrlTemplate: el.dataset.imageUrlTemplate,
        csrfToken: el.dataset.csrfToken,
        canEdit: el.dataset.canEdit === '1',
    }).mount(el);
});
