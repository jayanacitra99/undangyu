<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Landing page of the admin panel.
 *
 * The real counters arrive with the modules that own them — orders in Session
 * 9, invitations in Session 12. This renders the shell they will fill.
 */
final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard');
    }
}
