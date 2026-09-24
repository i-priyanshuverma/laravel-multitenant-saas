<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdatePasswordRequest;
use App\Http\Requests\Tenant\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display user profile settings.
     */
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Settings/Profile', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Update user profile information.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->update($request->validated());

        return back()->with('success', 'Profile information updated successfully.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->update([
            'password' => Hash::make((string) $request->validated('password')),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
