<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update user preferences including language, timezone, etc.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:en,ru'],
            'timezone' => ['required', 'string'],
            'date_format' => ['required', 'string'],
            'email_notifications' => ['nullable', 'boolean'],
            'review_reminders' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $user->locale = $validated['locale'];
        $user->timezone = $validated['timezone'];
        $user->date_format = $validated['date_format'];
        $user->email_notifications = $request->has('email_notifications');
        $user->review_reminders = $request->has('review_reminders');
        $user->save();

        // Update session locale
        app()->setLocale($user->locale);
        session(['locale' => $user->locale]);

        return Redirect::route('profile.edit')->with('status', __('Preferences updated successfully'));
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required'],
        ]);

        $user = $request->user();

        // Manually check password
        if (! \Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => __('The provided password is incorrect.')], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
