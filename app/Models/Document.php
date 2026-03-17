<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'firm_id',
        'owner_id',
        'title',
        'description',
        'file_path',
    ];

    public function firm()
    {
        return $this->belongsTo(LawFirm::class, 'firm_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function shares()
    {
        return $this->hasMany(DocumentShare::class);
    }

    public function sharedWithUsers()
    {
        return $this->belongsToMany(User::class, 'document_shares', 'document_id', 'shared_with_user_id');
    }
}
