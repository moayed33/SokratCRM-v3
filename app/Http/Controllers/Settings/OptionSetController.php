<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\CrmDatabaseGuard;
use App\Support\CrmOptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OptionSetController extends Controller
{
    public function index(): View
    {
        CrmDatabaseGuard::ensureConnected();

        return view('settings.option-sets.index', [
            'sets' => CrmOptions::sets(),
            'usageHints' => self::usageHints(),
        ]);
    }

    public function edit(string $setKey): View
    {
        CrmDatabaseGuard::ensureConnected();

        $setKey = $this->normalizeKey($setKey);

        return view('settings.option-sets.edit', [
            'setKey' => $setKey,
            'optionsInput' => old('options_input', $this->optionsToText($setKey)),
            'usageHint' => self::usageHints()[$setKey] ?? null,
        ]);
    }

    /**
     * Renders stored options for an option set back into textarea format (value | label_ar | label_en).
     */
    public function optionsToText(string $setKey): string
    {
        return collect(CrmOptions::get($setKey))
            ->map(static fn (array $option): string => implode(' | ', array_filter([
                (string) ($option['value'] ?? ''),
                (string) ($option['label_ar'] ?? ''),
                (string) ($option['label_en'] ?? ''),
            ], static fn (string $part) => $part !== '')))
            ->implode("\n");
    }
    public function store(Request $request): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        $validated = $request->validate([
            'set_key' => [
                'required',
                'string',
                'regex:/^[a-z][a-z0-9_]{0,49}$/',
                Rule::unique('crm_options', 'set_key'),
            ],
            'options_input' => ['required', 'string', 'max:20000'],
        ], [], ['set_key' => __('crm.os_set_key')]);

        $setKey = $this->normalizeKey((string) $validated['set_key']);
        self::replaceOptions($setKey, (string) $validated['options_input']);

        return redirect()
            ->route('v2.settings.option-sets.edit', ['set' => $setKey])
            ->with('success', __('crm.os_created_success'));
    }

    public function update(Request $request, string $setKey): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        $setKey = $this->normalizeKey($setKey);

        $validated = $request->validate([
            'options_input' => ['required', 'string', 'max:20000'],
        ]);

        self::replaceOptions($setKey, (string) $validated['options_input']);

        return redirect()
            ->route('v2.settings.option-sets.edit', ['set' => $setKey])
            ->with('success', __('crm.os_updated_success'));
    }

    public function destroy(string $setKey): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        $setKey = $this->normalizeKey($setKey);

        if (! in_array($setKey, self::systemSets(), true)) {
            DB::table('crm_options')->where('set_key', $setKey)->delete();
            CrmOptions::flush();
        }

        return redirect()
            ->route('v2.settings.option-sets.index')
            ->with('success', __('crm.os_deleted_success'));
    }

    /**
     * Replaces every option of one set with the submitted textarea content.
     */
    public static function replaceOptions(string $setKey, string $input): void
    {
        $options = LeadFieldController::parseOptionsInput($input);

        DB::transaction(static function () use ($setKey, $options): void {
            DB::table('crm_options')->where('set_key', $setKey)->delete();

            foreach (array_values($options) as $index => $option) {
                DB::table('crm_options')->insert([
                    'set_key' => $setKey,
                    'value' => $option['value'],
                    'label_ar' => $option['label_ar'],
                    'label_en' => $option['label_en'],
                    'position' => ($index + 1) * 10,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        CrmOptions::flush($setKey);
    }

    /**
     * Sets consumed by core screens — they can be edited but not deleted.
     *
     * @return list<string>
     */
    public static function systemSets(): array
    {
        return ['phone_label', 'relation_type'];
    }

    /**
     * @return array<string,string>
     */
    public static function usageHints(): array
    {
        return [
            'phone_label' => __('crm.os_hint_phone_label'),
            'relation_type' => __('crm.os_hint_relation_type'),
        ];
    }

    private function normalizeKey(string $key): string
    {
        return mb_strtolower(trim($key));
    }
}
