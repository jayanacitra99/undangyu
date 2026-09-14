<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Package;

class UpdatePackageRequest extends PackageRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->package()) ?? false;
    }

    /**
     * The slug is frozen after creation: orders and the pricing page both point
     * at it, so renaming a package must not move its URL.
     */
    protected function resolvedSlug(): string
    {
        return $this->package()->slug;
    }

    private function package(): Package
    {
        /** @var Package $package */
        $package = $this->route('package');

        return $package;
    }
}
