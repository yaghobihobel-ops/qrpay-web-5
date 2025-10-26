<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Admin\Language;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreferencesController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $languageCodes = Language::where('status', true)->pluck('code')->merge(['en', 'zh', 'ru'])->unique()->values();

        $validated = $request->validate([
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
            'language' => ['required', 'string', Rule::in($languageCodes->all())],
            'notifications' => ['sometimes', 'array'],
            'notifications.email' => ['sometimes', 'boolean'],
            'notifications.sms' => ['sometimes', 'boolean'],
            'notifications.push' => ['sometimes', 'boolean'],
        ]);

        $notifications = array_merge([
            'email' => true,
            'sms' => false,
            'push' => true,
        ], data_get($validated, 'notifications', []));

        $user->preferred_theme = $validated['theme'];
        $user->preferred_language = $validated['language'];
        $user->notification_preferences = $notifications;
        $user->save();

        session()->put('lang', $validated['language']);
        app()->setLocale($validated['language']);

        return response()->json([
            'status' => 'ok',
            'preferences' => [
                'theme' => $user->preferred_theme,
                'language' => $user->preferred_language,
                'notifications' => $notifications,
            ],
        ]);
    }
}
