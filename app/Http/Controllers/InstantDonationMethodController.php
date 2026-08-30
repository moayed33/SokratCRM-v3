<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InstantDonationMethod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstantDonationMethodController extends Controller
{
    public function index(): View
    {
        return view('collections.methods', [
            'methods' => InstantDonationMethod::query()->orderBy('position')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('instant_donation_methods', 'code')],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'accounts' => ['nullable'],
        ]);

        $accounts = $this->parseAccountsInput($request->input('accounts'));

        InstantDonationMethod::query()->create([
            'code' => $validated['code'],
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'] ?? null,
            'position' => (int) ($validated['position'] ?? 0),
            'accounts' => $accounts,
            'is_active' => true,
        ]);

        return back()->with('success', __('crm.instant_method_created_success'));
    }

    public function update(Request $request, InstantDonationMethod $instantDonationMethod): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'position' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
            'accounts' => ['nullable'],
        ]);

        $accounts = $this->parseAccountsInput($request->input('accounts'));

        $instantDonationMethod->update([
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'] ?? null,
            'position' => (int) $validated['position'],
            'is_active' => $request->boolean('is_active'),
            'accounts' => $accounts,
        ]);

        return back()->with('success', __('crm.instant_method_updated_success'));
    }

    /**
     * @return list<string>
     */
    private function parseAccountsInput(mixed $input): array
    {
        if (is_string($input)) {
            return collect(preg_split('/\r\n|\r|\n/', $input))
                ->map(static fn ($line): string => trim((string) $line))
                ->filter(static fn (string $line): bool => $line !== '')
                ->values()
                ->all();
        }

        if (is_array($input)) {
            return collect($input)
                ->map(static function ($item): string {
                    if (is_array($item)) {
                        $num = trim((string) ($item['number'] ?? $item['account'] ?? ''));
                        $label = trim((string) ($item['label'] ?? $item['name'] ?? ''));

                        return $label !== '' && $label !== $num ? "{$num} ({$label})" : $num;
                    }

                    return trim((string) $item);
                })
                ->filter(static fn (string $item): bool => $item !== '')
                ->values()
                ->all();
        }

        return [];
}
    }
