<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\ReorderCatalog;
use App\Actions\Commerce\SavePackage;
use App\Enums\FeatureKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderCatalogRequest;
use App\Http\Requests\Admin\StorePackageRequest;
use App\Http\Requests\Admin\UpdatePackageRequest;
use App\Models\Package;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Admin CRUD for packages and their feature flags (M2.1, M2.2).
 */
final class PackageController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Package::class);

        $packages = Package::query()->withCount('features')->ordered()->get();

        return view('admin.packages.index', [
            'columns' => [__('Nama'), __('Harga'), __('Masa aktif'), __('Fitur')],
            'rows' => $packages->map(fn (Package $package): array => [
                'id' => $package->id,
                'route_key' => $package->id,
                'cells' => [
                    $package->name.($package->is_featured ? ' ★' : ''),
                    'Rp '.number_format($package->effectivePrice(), 0, ',', '.'),
                    $package->active_days.' '.__('hari'),
                    (string) $package->features_count,
                ],
                'is_active' => $package->is_active,
            ])->all(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Package::class);

        return view('admin.packages.create', [
            ...$this->formOptions(),
            'package' => new Package([
                'currency' => 'IDR',
                'active_days' => 365,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => (int) Package::query()->max('sort_order') + 1,
            ]),
            'featureRows' => [],
        ]);
    }

    public function store(StorePackageRequest $request, SavePackage $save): RedirectResponse
    {
        $package = $save(null, $request->payload(), $request->featureRows());

        return to_route('admin.packages.edit', $package)
            ->with('status', __('Paket ditambahkan.'));
    }

    public function edit(Package $package): View
    {
        Gate::authorize('update', $package);

        $package->load('features');

        return view('admin.packages.edit', [
            ...$this->formOptions(),
            'package' => $package,
            // The rows come back in insert order, which reads as random. The
            // enum is the order the pricing table uses, so use it here too.
            'featureRows' => $package->features
                ->sortBy(fn ($feature): int => array_search($feature->feature_key, FeatureKey::cases(), true))
                ->values()
                ->map(fn ($feature): array => [
                    'feature_key' => $feature->feature_key->value,
                    'feature_value' => $feature->feature_value,
                    'is_unlimited' => $feature->is_unlimited
                        || (! $feature->feature_key->isQuota() && $feature->feature_value === 'true'),
                ])
                ->all(),
        ]);
    }

    public function update(UpdatePackageRequest $request, Package $package, SavePackage $save): RedirectResponse
    {
        $save($package, $request->payload(), $request->featureRows());

        return to_route('admin.packages.index')
            ->with('status', __('Paket diperbarui.'));
    }

    public function destroy(Package $package): RedirectResponse
    {
        Gate::authorize('delete', $package);

        // Session 8 adds orders.package_id with a restricting foreign key; that
        // is what will stop a package being deleted out from under an order.
        $package->delete();

        return to_route('admin.packages.index')
            ->with('status', __('Paket dihapus.'));
    }

    public function reorder(ReorderCatalogRequest $request, ReorderCatalog $reorder): JsonResponse
    {
        Gate::authorize('reorder', Package::class);

        $reorder(Package::class, $request->orderedIds());

        return response()->json(['status' => 'ok']);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'featureKeys' => array_map(fn (FeatureKey $key): array => [
                'value' => $key->value,
                'label' => $key->label(),
                'is_quota' => $key->isQuota(),
            ], FeatureKey::cases()),
        ];
    }
}
