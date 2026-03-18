<?php

namespace App\Policies;
use App\Models\LawFirm;
use App\Models\User;

class LawFirmPolicy
{
    public function viewAny(User $user)
    {
        return $user->isPlatformAdmin();
    }

    public function view(User $user, LawFirm $firm)
    {
        return $user->isPlatformAdmin();
    }

    public function create(User $user)
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, LawFirm $firm)
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, LawFirm $firm)
    {
        return $user->isPlatformAdmin();
    }

    public function restore(User $user, LawFirm $firm)
    {
        return $user->isPlatformAdmin();
    }

    public function forceDelete(User $user, LawFirm $firm)
    {
        return $user->isPlatformAdmin();
    }
}