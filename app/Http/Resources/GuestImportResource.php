<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GuestImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One import, as the progress panel polls it (25.5).
 *
 * `errors` goes out in full so the panel can show the first few inline — a
 * client whose file had two bad rows should not have to download a report to
 * find out which.
 *
 * @mixin GuestImport
 */
class GuestImportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_filename' => $this->original_filename,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_running' => $this->status->isRunning(),
            'total_rows' => $this->total_rows,
            'success_rows' => $this->success_rows,
            'failed_rows' => $this->failed_rows,
            'errors' => $this->errors ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
