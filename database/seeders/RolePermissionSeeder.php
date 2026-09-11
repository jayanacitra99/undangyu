<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Implements the role & permission matrix in docs/04-flowcharts.md § 11.
 *
 * `super-admin` is deliberately assigned nothing — it passes every check through
 * the `Gate::before` hook in AppServiceProvider, so the matrix has exactly one
 * place that grants blanket access instead of a list that drifts out of date.
 *
 * A 👁 (read-only) cell in the matrix means the role gets the `viewAny`
 * permission for that resource but not the matching `manage` one.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Every permission in the system, grouped by the matrix row it comes from.
     *
     * @var array<string, list<string>>
     */
    public const PERMISSIONS = [
        'Manage settings & roles' => ['settings.manage', 'roles.manage'],
        'Manage templates/packages' => ['templates.manage', 'packages.manage'],
        'Manage all orders' => ['orders.viewAny', 'orders.manage'],
        'Verify manual payments' => ['payments.verify'],
        'Manage all users' => ['users.viewAny', 'users.manage'],
        'Impersonate client' => ['users.impersonate'],
        'Own invitations CRUD' => [
            'invitations.viewAny',
            'invitations.create',
            'invitations.update',
            'invitations.delete',
        ],
        'Guest list CRUD' => [
            'guests.viewAny',
            'guests.create',
            'guests.update',
            'guests.delete',
        ],
        'Send blasts' => ['blasts.send'],
        'Check-in scanner' => ['checkins.scan'],
        'Affiliate dashboard' => ['affiliates.view'],
        'Approve withdrawals' => ['withdrawals.approve'],
    ];

    /**
     * The six roles from M1.4, with the permissions each column of the matrix ticks.
     *
     * @var array<string, list<string>>
     */
    public const ROLES = [
        // Everything, via Gate::before — never by assigning the full list.
        'super-admin' => [],

        'admin' => [
            'templates.manage',
            'packages.manage',
            'orders.viewAny',
            'orders.manage',
            'payments.verify',
            'users.viewAny',
            'users.manage',
            'users.impersonate',
            'invitations.viewAny',
            'invitations.create',
            'invitations.update',
            'invitations.delete',
            'guests.viewAny',
            'guests.create',
            'guests.update',
            'guests.delete',
            'blasts.send',
            'checkins.scan',
            'affiliates.view',
            'withdrawals.approve',
        ],

        'support' => [
            'orders.viewAny',
            'users.viewAny',
            'users.impersonate',
        ],

        'client' => [
            'invitations.viewAny',
            'invitations.create',
            'invitations.update',
            'invitations.delete',
            'guests.viewAny',
            'guests.create',
            'guests.update',
            'guests.delete',
            'blasts.send',
            'checkins.scan',
        ],

        'reseller' => [
            'invitations.viewAny',
            'invitations.create',
            'invitations.update',
            'invitations.delete',
            'guests.viewAny',
            'guests.create',
            'guests.update',
            'guests.delete',
            'blasts.send',
            'checkins.scan',
            'affiliates.view',
        ],

        'usher' => [
            'checkins.scan',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::permissionNames() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        return array_merge(...array_values(self::PERMISSIONS));
    }
}
