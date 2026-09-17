<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Sidebar menus
|--------------------------------------------------------------------------
|
| One array per surface (docs/05 § 2). App\Support\Menu filters these against
| the signed-in user before the sidebar renders, so no Blade file ever decides
| who sees what.
|
| Item keys:
|   label       Translation string shown in the sidebar.
|   route       Named route. An item whose route is not registered yet is
|               skipped — later sessions add the routes, not this config.
|   icon        Bootstrap Icons class.
|   permission  Spatie permission required. Null means every user of the
|               surface sees it.
|   active      Request path patterns that light the item up.
|   children    Nested items. A parent renders only when at least one child
|               survives filtering.
|
*/

return [

    'admin' => [
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'icon' => 'bi-speedometer2',
            'permission' => null,
            'active' => ['admin'],
        ],
        [
            'label' => 'Pengguna',
            'route' => 'admin.users.index',
            'icon' => 'bi-people',
            'permission' => 'users.viewAny',
            'active' => ['admin/users', 'admin/users/*'],
        ],
        [
            'label' => 'Pesanan',
            'route' => 'admin.orders.index',
            'icon' => 'bi-receipt',
            'permission' => 'orders.viewAny',
            'active' => ['admin/orders', 'admin/orders/*'],
        ],
        [
            'label' => 'Verifikasi Pembayaran',
            'route' => 'admin.payments.pending',
            'icon' => 'bi-cash-stack',
            'permission' => 'payments.verify',
            'active' => ['admin/payments', 'admin/payments/*'],
        ],
        [
            'label' => 'Katalog',
            'icon' => 'bi-collection',
            'permission' => null,
            'children' => [
                [
                    'label' => 'Template',
                    'route' => 'admin.templates.index',
                    'icon' => 'bi-palette',
                    'permission' => 'templates.manage',
                    'active' => ['admin/templates', 'admin/templates/*'],
                ],
                [
                    'label' => 'Jenis Acara',
                    'route' => 'admin.event-types.index',
                    'icon' => 'bi-calendar-event',
                    'permission' => 'templates.manage',
                    'active' => ['admin/event-types', 'admin/event-types/*'],
                ],
                [
                    'label' => 'Kategori Template',
                    'route' => 'admin.template-categories.index',
                    'icon' => 'bi-tags',
                    'permission' => 'templates.manage',
                    'active' => ['admin/template-categories', 'admin/template-categories/*'],
                ],
                [
                    'label' => 'Paket',
                    'route' => 'admin.packages.index',
                    'icon' => 'bi-box-seam',
                    'permission' => 'packages.manage',
                    'active' => ['admin/packages', 'admin/packages/*'],
                ],
            ],
        ],
        [
            'label' => 'Pengaturan',
            'route' => 'admin.settings.edit',
            'icon' => 'bi-gear',
            'permission' => 'settings.manage',
            'active' => ['admin/settings', 'admin/settings/*'],
        ],
    ],

    'client' => [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'icon' => 'bi-speedometer2',
            'permission' => null,
            'active' => ['dashboard'],
        ],
        [
            'label' => 'Undangan',
            'route' => 'client.invitations.index',
            'icon' => 'bi-envelope-heart',
            'permission' => 'invitations.viewAny',
            'active' => ['dashboard/invitations', 'dashboard/invitations/*'],
        ],
        [
            'label' => 'Tamu',
            'route' => 'client.guests.index',
            'icon' => 'bi-person-lines-fill',
            'permission' => 'guests.viewAny',
            'active' => ['dashboard/guests', 'dashboard/guests/*'],
        ],
        [
            'label' => 'Blast WhatsApp',
            'route' => 'client.blasts.index',
            'icon' => 'bi-whatsapp',
            'permission' => 'blasts.send',
            'active' => ['dashboard/blasts', 'dashboard/blasts/*'],
        ],
        [
            // Orders carry no permission: a client owns theirs, and OrderPolicy
            // is what decides whose rows they see.
            'label' => 'Pesanan',
            'route' => 'client.orders.index',
            'icon' => 'bi-receipt',
            'permission' => null,
            'active' => ['dashboard/orders', 'dashboard/orders/*'],
        ],
        [
            'label' => 'Affiliate',
            'route' => 'client.affiliate.index',
            'icon' => 'bi-cash-coin',
            'permission' => 'affiliates.view',
            'active' => ['dashboard/affiliate', 'dashboard/affiliate/*'],
        ],
    ],

];
