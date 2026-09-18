<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\MessageChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreMessageTemplateRequest;
use App\Http\Requests\Client\UpdateMessageTemplateRequest;
use App\Http\Resources\MessageTemplateResource;
use App\Models\Invitation;
use App\Models\MessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * The client's message texts (M8.2, 26.2).
 */
final class MessageTemplateController extends Controller
{
    public function index(Invitation $invitation): AnonymousResourceCollection
    {
        Gate::authorize('view', $invitation);

        return MessageTemplateResource::collection(
            MessageTemplate::query()->availableTo($invitation)->get()
        );
    }

    public function store(StoreMessageTemplateRequest $request, Invitation $invitation): JsonResponse
    {
        $template = MessageTemplate::query()->create([
            ...$request->validated(),
            'invitation_id' => $invitation->getKey(),
            'user_id' => null,
            'channel' => $request->validated()['channel'] ?? MessageChannel::Whatsapp->value,
            'is_system' => false,
        ]);

        return MessageTemplateResource::make($template)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateMessageTemplateRequest $request, MessageTemplate $template): MessageTemplateResource
    {
        $template->fill($request->validated())->save();

        return MessageTemplateResource::make($template);
    }

    public function destroy(MessageTemplate $template): Response
    {
        Gate::authorize('delete', $template);

        $template->delete();

        return response()->noContent();
    }
}
