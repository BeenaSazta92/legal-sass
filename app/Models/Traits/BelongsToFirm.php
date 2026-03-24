<?php
namespace App\Models\Traits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
trait BelongsToFirm
{
    protected static function bootBelongsToFirm()
    {
        static::addGlobalScope('firm', function (Builder $builder) {
            //if (app()->runningInConsole()) return;
            if (Auth::check() && !Auth::user()->isPlatformAdmin()) {
                $builder->where('firm_id', Auth::user()->firm_id);
            }
        });

        static::creating(function ($model) {
            if (Auth::check() && !Auth::user()->isPlatformAdmin()) {
                $model->firm_id = Auth::user()->firm_id;
            }
        });
    }
}
?>