<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class RoleCast implements CastsAttributes
{
    /**
     * The allowed roles
     */
    private const ALLOWED_ROLES = [
        'SYSTEM_ADMIN',
        'ADMIN',
        'LAWYER',
        'CLIENT',
    ];

    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if (!in_array($value, self::ALLOWED_ROLES, true)) {
            throw new InvalidArgumentException(
                "Invalid role '{$value}'. Allowed roles are: " . implode(', ', self::ALLOWED_ROLES)
            );
        }

        return $value;
    }
}