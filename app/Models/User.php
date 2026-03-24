<?php

namespace App\Models;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Casts\RoleCast;
use App\Models\Traits\{HasRoleAndPermissions,BelongsToFirm};
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoleAndPermissions,BelongsToFirm;
    /**
     * Allowed user roles
     */
    public const ROLES = [
        'SYSTEM_ADMIN',
        'ADMIN',
        'LAWYER',
        'CLIENT',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'firm_id',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => RoleCast::class,
        ];
    }

    public function firm()
    {
        return $this->belongsTo(LawFirm::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'owner_id');
    }

    public function sharedDocuments()
    {
        return $this->belongsToMany(Document::class, 'document_shares', 'shared_with_user_id', 'document_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            if (!in_array($user->role, self::ROLES)) {
                throw new \InvalidArgumentException('Invalid role');
            }
        });
    }
    public function generateToken()
    {
        return $this->createToken('API Token')->plainTextToken;
    }
    public function getContext(): array
    {
        return [
            'role' => $this->role,
            'firm_id' => $this->firm_id,
        ];
    }
}
