<?php

declare(strict_types=1);

namespace App\Modules\Workspaces\Queries;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ListUserWorkspaces
{
    /** @return BelongsToMany<Workspace, User> */
    public function query(User $user): BelongsToMany
    {
        return $user->workspaces()->orderBy('workspaces.uuid');
    }
}
