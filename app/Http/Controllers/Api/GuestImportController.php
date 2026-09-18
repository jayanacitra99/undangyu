<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\GuestImportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreGuestImportRequest;
use App\Http\Resources\GuestImportResource;
use App\Jobs\ProcessGuestImportJob;
use App\Models\GuestImport;
use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Guest spreadsheet uploads and their progress (25.3, 25.5).
 *
 * The upload stores the file and queues the work (hard rule 9). Parsing 500
 * rows, generating 500 tokens and writing them is not something a client's
 * browser waits for.
 */
final class GuestImportController extends Controller
{
    public function store(
        StoreGuestImportRequest $request,
        Invitation $invitation,
    ): JsonResponse {
        $file = $request->file('file');

        $import = $invitation->guestImports()->create([
            'user_id' => $request->user()?->getKey(),
            'file_path' => $file->store(GuestImport::DIRECTORY, GuestImport::DISK),
            'original_filename' => mb_substr((string) $file->getClientOriginalName(), 0, 255),
            'status' => GuestImportStatus::Queued,
            // Written rather than left to the column defaults: the response is
            // the model as created, and an unhydrated default reads back as
            // null — which the panel would print as "null berhasil".
            'total_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
        ]);

        ProcessGuestImportJob::dispatch((int) $import->getKey());

        return GuestImportResource::make($import)
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * What the panel polls while the job runs. Bound by the import itself, so
     * the policy is what decides — the same shape as every other child.
     */
    public function show(GuestImport $import): GuestImportResource
    {
        Gate::authorize('view', $import);

        return GuestImportResource::make($import);
    }
}
