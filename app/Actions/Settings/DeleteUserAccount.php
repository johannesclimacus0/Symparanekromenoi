<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class DeleteUserAccount
{
    /** @param array<string, mixed> $input */
    public function delete(User $user, array $input, Request $request): void
    {
        Validator::make($input, [
            'password' => ['required', 'string', 'current_password:web'],
        ])->validate();

        DB::transaction(function () use ($user): void {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $workspaceIds = $user->workspaceMemberships()->orderBy('workspace_id')->pluck('workspace_id');
            Workspace::withTrashed()->whereKey($workspaceIds)->orderBy('id')->lockForUpdate()->get();

            if ($user->workspaceMemberships()->where('role', WorkspaceRole::Owner->value)->exists()) {
                throw ValidationException::withMessages([
                    'account' => 'Transfer workspace ownership before deleting your account.',
                ]);
            }

            $user->workspaceMemberships()->delete();
            $user->delete();
        });

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::forgetGuards();
    }
}
