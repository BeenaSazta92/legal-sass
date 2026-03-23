<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\AuditLogs;
use App\Models\Traits\{HasRoleAndPermissions,BelongsToFirm};

class DocumentShare extends Model
{
    use BelongsToFirm,HasRoleAndPermissions,AuditLogs;
    protected $fillable = [
        'firm_id',
        'document_id',
        'shared_with_user_id',
        'permission',
    ];

    public function firm()
    {
        return $this->belongsTo(LawFirm::class,'firm_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function sharedWithUser()
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }
}
