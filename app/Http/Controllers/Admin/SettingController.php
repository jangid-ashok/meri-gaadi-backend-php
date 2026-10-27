<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    private const SHARED_SETTINGS = [
        'site_name' => ['group' => 'general', 'type' => 'string', 'rules' => ['required', 'string', 'max:255']],
        'site_description' => ['group' => 'general', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:5000']],
        'contact_email' => ['group' => 'general', 'type' => 'string', 'rules' => ['required', 'email', 'max:255']],
        'contact_phone' => ['group' => 'general', 'type' => 'string', 'rules' => ['nullable', 'string', 'max:50']],
        'default_country' => ['group' => 'general', 'type' => 'string', 'rules' => ['nullable', 'string', 'max:120']],
        'default_timezone' => ['group' => 'general', 'type' => 'string', 'rules' => ['nullable', 'string', 'max:120']],
        'facebook_url' => ['group' => 'social', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
        'instagram_url' => ['group' => 'social', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
        'youtube_url' => ['group' => 'social', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
        'linkedin_url' => ['group' => 'social', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
        'twitter_url' => ['group' => 'social', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
        'default_pagination' => ['group' => 'system', 'type' => 'integer', 'rules' => ['required', 'integer', 'min:10', 'max:100']],
        'maintenance_mode' => ['group' => 'system', 'type' => 'boolean', 'rules' => ['required', 'boolean']],
        'date_format' => ['group' => 'system', 'type' => 'string', 'rules' => ['required', 'string', 'max:30']],
        'time_format' => ['group' => 'system', 'type' => 'string', 'rules' => ['required', 'string', 'max:30']],
    ];

    public function index(Request $request)
    {
        $this->ensureSettingsExist();

        $request->validate(['group' => ['nullable', Rule::in(['general', 'social', 'system'])]]);

        $settings = Setting::query()->when($request->filled('group'), fn ($query) => $query->where('group', $request->input('group')))->orderBy('group')->orderBy('key')->get();

        if ($request->expectsJson()) {
            return response()->json(['data' => $settings]);
        }

        return view('admin.settings.index', [
            'settings' => $settings,
            'groups' => ['general', 'social', 'system'],
            'page_title' => 'Settings',
            'leftMenuActive' => 'settings',
        ]);
    }

    public function byGroup(string $group, Request $request)
    {
        $this->ensureSettingsExist();

        $request->validate(['group' => ['nullable', Rule::in(['general', 'social', 'system'])]]);

        $group = in_array($group, ['general', 'social', 'system'], true) ? $group : abort(404);

        return response()->json(['data' => Setting::query()->where('group', $group)->orderBy('key')->get()]);
    }

    public function update(Request $request)
    {
        $this->ensureSettingsExist();

        $allowedKeys = array_keys(self::SHARED_SETTINGS);
        $allowedRequestKeys = array_diff(array_keys($request->all()), ['_token', '_method']);
        $unknownKeys = array_diff($allowedRequestKeys, $allowedKeys);

        $rules = [
            'site_name' => ['sometimes', 'required', 'string', 'max:255'],
            'site_description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'contact_email' => ['sometimes', 'required', 'email', 'max:255'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'default_country' => ['sometimes', 'nullable', 'string', 'max:120'],
            'default_timezone' => ['sometimes', 'nullable', 'string', 'max:120'],
            'facebook_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'instagram_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'youtube_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'linkedin_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'twitter_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'default_pagination' => ['sometimes', 'required', 'integer', 'min:10', 'max:100'],
            'maintenance_mode' => ['sometimes', 'required', 'boolean'],
            'date_format' => ['sometimes', 'required', 'string', 'max:30'],
            'time_format' => ['sometimes', 'required', 'string', 'max:30'],
        ];

        $validator = Validator::make($request->all(), $rules);

        $messages = $validator->errors()->toArray();
        foreach ($unknownKeys as $key) {
            $messages[$key] = ['The selected setting is invalid.'];
        }

        if ($validator->fails() || $unknownKeys !== []) {
            throw \Illuminate\Validation\ValidationException::withMessages($messages);
        }

        $data = $validator->validated();

        $payload = [];
        foreach ($data as $key => $value) {
            $definition = self::SHARED_SETTINGS[$key];
            $payload[$key] = $this->normalizeValue($definition['type'], $value);
        }

        DB::transaction(function () use ($payload) {
            foreach ($payload as $key => $value) {
                $setting = Setting::query()->where('key', $key)->firstOrFail();
                $setting->update(['value' => $value]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['data' => Setting::query()->whereIn('key', array_keys($payload))->orderBy('key')->get()]);
        }

        return redirect()->route('admin.settings.index')->with('status', 'Settings updated successfully.');
    }

    private function normalizeValue(string $type, mixed $value): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            default => is_string($value) ? trim($value) : $value,
        };
    }

    private function ensureSettingsExist(): void
    {
        foreach (self::SHARED_SETTINGS as $key => $definition) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                [
                    'group' => $definition['group'],
                    'type' => $definition['type'],
                    'value' => '',
                    'description' => null,
                    'is_public' => false,
                ]
            );
        }
    }
}
