<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'name',
        'max_admins',
        'max_lawyers',
        'max_clients',
        'max_documents_per_user',
    ];

    public function lawFirms()
    {
        return $this->hasMany(LawFirm::class);
    }
}
