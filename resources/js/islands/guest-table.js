/*
| Island entry for the guest list page (24.4).
*/
import { createApp } from 'vue';
import GuestTable from '@/components/builder/GuestTable.vue';

document.querySelectorAll('[data-island="guest-table"]').forEach((el) => {
    createApp(GuestTable, {
        guests: JSON.parse(el.dataset.guests),
        pagination: JSON.parse(el.dataset.pagination),
        groups: JSON.parse(el.dataset.groups),
        quota: JSON.parse(el.dataset.quota),
        titles: JSON.parse(el.dataset.titles),
        invitationUrl: el.dataset.invitationUrl,
        indexUrl: el.dataset.indexUrl,
        storeUrl: el.dataset.storeUrl,
        itemUrlTemplate: el.dataset.itemUrlTemplate,
        bulkDeleteUrl: el.dataset.bulkDeleteUrl,
        bulkGroupUrl: el.dataset.bulkGroupUrl,
        groupStoreUrl: el.dataset.groupStoreUrl,
        groupItemUrlTemplate: el.dataset.groupItemUrlTemplate,
        csrfToken: el.dataset.csrfToken,
        canEdit: el.dataset.canEdit === '1',
    }).mount(el);
});
