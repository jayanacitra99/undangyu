/*
| Island entry for the section list on the builder's "Pengaturan" tab (19.3).
*/
import { createApp } from 'vue';
import SectionsManager from '@/components/builder/SectionsManager.vue';

document.querySelectorAll('[data-island="sections-manager"]').forEach((el) => {
    createApp(SectionsManager, {
        sections: JSON.parse(el.dataset.sections),
        storeUrl: el.dataset.storeUrl,
        reorderUrl: el.dataset.reorderUrl,
        itemUrlTemplate: el.dataset.itemUrlTemplate,
        csrfToken: el.dataset.csrfToken,
        canEdit: el.dataset.canEdit === '1',
    }).mount(el);
});
