<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\ReorderCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderCatalogRequest;
use App\Http\Requests\Admin\StoreTemplateCategoryRequest;
use App\Http\Requests\Admin\UpdateTemplateCategoryRequest;
use App\Models\TemplateCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Admin CRUD for the template categories (M3.2).
 */
final class TemplateCategoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', TemplateCategory::class);

        return view('admin.template-categories.index', [
            'columns' => [__('Nama'), __('Slug'), __('Deskripsi')],
            'rows' => TemplateCategory::query()->ordered()->get()->map(fn (TemplateCategory $category): array => [
                'id' => $category->id,
                'cells' => [
                    $category->name,
                    $category->slug,
                    (string) $category->description,
                ],
                'is_active' => $category->is_active,
            ])->all(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', TemplateCategory::class);

        return view('admin.template-categories.create', [
            'category' => new TemplateCategory([
                'is_active' => true,
                'sort_order' => (int) TemplateCategory::query()->max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(StoreTemplateCategoryRequest $request): RedirectResponse
    {
        TemplateCategory::query()->create($request->validated());

        return to_route('admin.template-categories.index')
            ->with('status', __('Kategori ditambahkan.'));
    }

    public function edit(TemplateCategory $templateCategory): View
    {
        Gate::authorize('update', $templateCategory);

        return view('admin.template-categories.edit', [
            'category' => $templateCategory,
        ]);
    }

    public function update(UpdateTemplateCategoryRequest $request, TemplateCategory $templateCategory): RedirectResponse
    {
        $templateCategory->update($request->validated());

        return to_route('admin.template-categories.index')
            ->with('status', __('Kategori diperbarui.'));
    }

    public function destroy(TemplateCategory $templateCategory): RedirectResponse
    {
        Gate::authorize('delete', $templateCategory);

        // Session 6 adds templates.template_category_id; the FK there is what
        // will stop a category being deleted out from under a template.
        $templateCategory->delete();

        return to_route('admin.template-categories.index')
            ->with('status', __('Kategori dihapus.'));
    }

    public function reorder(ReorderCatalogRequest $request, ReorderCatalog $reorder): JsonResponse
    {
        Gate::authorize('reorder', TemplateCategory::class);

        $reorder(TemplateCategory::class, $request->orderedIds());

        return response()->json(['status' => 'ok']);
    }
}
