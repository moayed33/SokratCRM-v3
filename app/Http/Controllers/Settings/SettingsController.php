<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Contracts\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'usersCount' => User::query()->count(),
            'activeUsersCount' => User::query()
                ->where('is_active', true)
                ->count(),
            'groupsCount' => Group::query()->count(),
            'permissionsCount' => Permission::query()->count(),
        ]);
    }
}
