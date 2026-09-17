/*
| Island entry for the builder's "Acara" tab (17.3).
*/
import { createApp } from 'vue';
import EventSessionsEditor from '@/components/builder/EventSessionsEditor.vue';

document.querySelectorAll('[data-island="event-sessions-editor"]').forEach((el) => {
    createApp(EventSessionsEditor, {
        events: JSON.parse(el.dataset.events),
        storeUrl: el.dataset.storeUrl,
        reorderUrl: el.dataset.reorderUrl,
        itemUrlTemplate: el.dataset.itemUrlTemplate,
        csrfToken: el.dataset.csrfToken,
        timezoneLabel: el.dataset.timezoneLabel ?? '',
        canEdit: el.dataset.canEdit === '1',
    }).mount(el);
});
