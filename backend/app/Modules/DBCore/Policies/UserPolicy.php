<?php

namespace Modules\DBCore\Policies;

use Modules\DBCore\Models\Core\User;

class UserPolicy
{
    public function update(User $user, User $model): bool
    {
        return $model->created_by === $user->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $model->created_by === $user->id;
    }
}
