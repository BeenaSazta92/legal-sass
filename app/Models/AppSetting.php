<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\{HasRoleAndPermissions,BelongsToFirm};

class AppSetting extends Model
{
    use BelongsToFirm;
    protected $table = 'app_settings';
    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key
     */
    public static function getSetting($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key
     */
    public static function setSetting($key, $value)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
