<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ResetAdminPassword
{
    public function handle(User $actor, User $target, string $newPassword): void
    {
        Gate::forUser($actor)->authorize('resetPassword', $target);

        DB::transaction(function () use ($target, $newPassword) {
            $target->forceFill([
                'password' => $newPassword,
                'remember_token' => null,
            ])->save();
        });

        $target->terminateSessions();

        activity('admin')
            ->causedBy($actor)
            ->performedOn($target)
            ->log('admin-password-reset');
    }
}
