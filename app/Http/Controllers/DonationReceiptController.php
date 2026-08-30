<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Lead;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DonationReceiptController extends Controller
{
    public function preview(Lead $lead, Donation $donation): BinaryFileResponse
    {
        $this->authorizeReceipt($lead, $donation);

        $response = response()->file(
            Storage::disk('local')->path($donation->receipt_path),
            $this->privateHeaders(),
        );
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }
    public function download(Lead $lead, Donation $donation): BinaryFileResponse
    {
        $this->authorizeReceipt($lead, $donation);

        $response = response()->download(
            Storage::disk('local')->path($donation->receipt_path),
            $donation->receipt_original_name ?: 'donation-receipt',
            $this->privateHeaders(),
        );
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    private function authorizeReceipt(Lead $lead, Donation $donation): void
    {
        abort_unless((int) $donation->lead_id === (int) $lead->id, 404);
        Gate::authorize('view', $lead);
        abort_if(
            blank($donation->receipt_path)
            || ! Storage::disk('local')->exists($donation->receipt_path),
            404,
        );
    }

    /** @return array<string, string> */
    private function privateHeaders(): array
    {
        return [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
