<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\VoipExtensionAssignment;
use Illuminate\Validation\ValidationException;

class VoipExtensionManager
{
    public function sync(User $user, ?string $previousExtension, ?string $newExtension): void
    {
        $previousExtension = $this->normalize($previousExtension);
        $newExtension = $this->normalize($newExtension);

        if ($previousExtension === $newExtension) {
            $this->ensureCurrentAssignment($user, $newExtension);

            return;
        }

        if ($newExtension !== null) {
            $alreadyAssigned = User::query()
                ->whereKeyNot($user->id)
                ->where('voip_extension', $newExtension)
                ->exists();

            if ($alreadyAssigned) {
                throw ValidationException::withMessages([
                    'voip_extension' => __('crm.voip_extension_already_assigned'),
                ]);
            }
        }

        VoipExtensionAssignment::query()
            ->where('user_id', $user->id)
            ->whereNull('assigned_until')
            ->update(['assigned_until' => now()]);

        if ($newExtension !== null) {
            VoipExtensionAssignment::query()->create([
                'user_id' => $user->id,
                'extension' => $newExtension,
                'assigned_from' => now(),
            ]);
        }
    }

    private function ensureCurrentAssignment(User $user, ?string $extension): void
    {
        if ($extension === null) {
            return;
        }

        VoipExtensionAssignment::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'extension' => $extension,
                'assigned_until' => null,
            ],
            ['assigned_from' => $user->created_at ?? now()],
        );
    }

    private function normalize(?string $extension): ?string
    {
        $extension = trim((string) $extension);

        return $extension !== '' ? $extension : null;
    }
}
