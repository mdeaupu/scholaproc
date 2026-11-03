<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isSuperAdmin();
    }

    public function view(User $actor, User $target): bool
    {
        return $this->isManagedAdmin($actor, $target);
    }

    public function create(User $actor): bool
    {
        return $actor->isSuperAdmin();
    }

    public function update(User $actor, User $target): bool
    {
        return $this->isManagedAdmin($actor, $target);
    }

    public function changeStatus(User $actor, User $target): bool
    {
        return $this->isManagedAdmin($actor, $target)
            && $actor->id !== $target->id;
    }

    public function resetPassword(User $actor, User $target): bool
    {
        return $this->isManagedAdmin($actor, $target)
            && $actor->id !== $target->id;
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->isManagedAdmin($actor, $target)
            && $actor->id !== $target->id
            && ! $target->hasTransactionalHistory();
    }

    private function isManagedAdmin(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin()
            && $target->isAdmin();
    }
}
