<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Actions\Invitations\CheckPublishReadiness;
use App\Actions\Invitations\UpdateInvitationBasics;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\UpdateInvitationRequest;
use App\Models\Invitation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * The client's invitations and the builder shell around one (M4.1, M4.2).
 *
 * The builder is one page with tabs, not eight routes: the tab is a query
 * parameter, the content pane is a partial, and each later session fills one
 * partial in. That keeps the URL of "my invitation" stable while the editors
 * land one at a time.
 */
final class InvitationController extends Controller
{
    public const PER_PAGE = 12;

    /**
     * The builder's tabs, in the order 16.2 lists them. The key names the
     * partial under client/invitations/tabs; a tab whose editor has not landed
     * yet renders the placeholder and says which session brings it.
     *
     * @var array<string, array{label: string, icon: string}>
     */
    public const TABS = [
        'dasar' => ['label' => 'Dasar', 'icon' => 'bi-sliders'],
        'mempelai' => ['label' => 'Mempelai', 'icon' => 'bi-people'],
        'acara' => ['label' => 'Acara', 'icon' => 'bi-calendar-event'],
        'cerita' => ['label' => 'Cerita', 'icon' => 'bi-clock-history'],
        'galeri' => ['label' => 'Galeri', 'icon' => 'bi-images'],
        'hadiah' => ['label' => 'Hadiah', 'icon' => 'bi-gift'],
        'tema' => ['label' => 'Tema', 'icon' => 'bi-palette'],
        'pengaturan' => ['label' => 'Pengaturan', 'icon' => 'bi-gear'],
    ];

    public const DEFAULT_TAB = 'dasar';

    public function index(): View
    {
        Gate::authorize('viewAny', Invitation::class);

        return view('client.invitations.index', [
            // The listing is scoped twice: OwnedByUserScope on the model, and
            // the explicit user_id below. A global scope that someone later
            // removes must not turn this page into every client's invitations.
            'invitations' => Invitation::query()
                ->where('user_id', auth()->id())
                ->with(['eventType:id,name', 'package:id,name', 'firstEvent'])
                ->latest('created_at')
                ->paginate(self::PER_PAGE),
        ]);
    }

    public function edit(Invitation $invitation, CheckPublishReadiness $readiness): View
    {
        // `view`, not `update`: a suspended invitation stays readable by its
        // owner. The form inside is what gets disabled.
        Gate::authorize('view', $invitation);

        $invitation->load(['eventType', 'template:id,name,slug,status,min_package_id', 'package:id,name,sort_order', 'order']);

        $requirements = $readiness($invitation);

        return view('client.invitations.edit', [
            'invitation' => $invitation,
            'tabs' => self::TABS,
            'tab' => $this->resolveTab(),
            'requirements' => $requirements,
            'isReady' => CheckPublishReadiness::isReady($requirements),
            'canEdit' => Gate::allows('update', $invitation),
        ]);
    }

    /**
     * The "Dasar" tab posts here as an ordinary form. The Vue islands of
     * Sessions 17–19 post the same payload to the JSON endpoint instead —
     * same request class, same action, two surfaces.
     */
    public function update(
        UpdateInvitationRequest $request,
        Invitation $invitation,
        UpdateInvitationBasics $update,
    ): RedirectResponse {
        $update($invitation, $request->validated());

        return redirect()
            ->route('client.invitations.edit', [$invitation, 'tab' => $this->resolveTab()])
            ->with('status', __('Perubahan tersimpan.'));
    }

    private function resolveTab(): string
    {
        $tab = (string) request()->query('tab', self::DEFAULT_TAB);

        return array_key_exists($tab, self::TABS) ? $tab : self::DEFAULT_TAB;
    }
}
