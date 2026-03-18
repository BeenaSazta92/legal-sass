<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\AuditLogs;

class DocumentShare extends Model
{
    use AuditLogs;
    protected $fillable = [
        'firm_id',
        'document_id',
        'shared_with_user_id',
        'permission',
    ];

    public function firm()
    {
        return $this->belongsTo(LawFirm::class);
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
