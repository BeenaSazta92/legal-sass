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

    public function firmSubscriptions()
    {
        return $this->hasMany(FirmSubscription::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {

            foreach (['max_admins', 'max_lawyers', 'max_clients', 'max_documents_per_user'] as $field) {
                if (!is_int($model->$field) || $model->$field < 0) {
                    throw new \Exception("Invalid value for $field in Subscription");
                }
            }
        });
    }
}
