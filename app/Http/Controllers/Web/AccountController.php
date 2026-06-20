<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AccountDashboardService;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        return view('account.index', [
            'user' => $user,
            'roleLabel' => $user->role->label(),
            'hasPremium' => $user->hasPremiumAccess(),
            'isMember' => $user->role === UserRole::Subscriber,
            'kpis' => AccountDashboardService::kpis($user),
            'submissions' => AccountDashboardService::submissions($user),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }
}
