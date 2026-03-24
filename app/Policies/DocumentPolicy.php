<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($user->isLawyer()) {
            return $document->owner_id === $user->id;
        }
        if ($user->isFirmAdmin()) {
            return $document->firm_id === $user->firm_id;
        }
        if ($user->isClient()) {
            return $document->shares()->where('shared_with_user_id', $user->id)->exists();
        }
        return false;
    }

    public function update(User $user, Document $document): bool
    {
        return $user->isFirmAdmin() || ($user->isLawyer() && $document->owner_id === $user->id);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->isFirmAdmin() || ($user->isLawyer() && $document->owner_id === $user->id);
    }

    public function share(User $user, Document $document)
    {
        // Only lawyers can share
        if (!$user->isLawyer()) {
            return false;
        }
        // Document must belong to the same firm
        return $user->firm_id === $document->firm_id;
    }
    


}