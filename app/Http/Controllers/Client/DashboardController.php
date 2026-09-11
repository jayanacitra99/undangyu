<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Landing page of the client dashboard.
 *
 * Invitation cards and quota meters land here in Session 12, once invitations
 * and entitlements exist.
 */
final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('client.dashboard');
    }
}
