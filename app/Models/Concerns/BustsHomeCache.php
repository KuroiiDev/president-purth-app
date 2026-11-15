<?php

namespace App\Models\Concerns;

use App\Support\HomeContentCache;
use Illuminate\Database\Eloquent\SoftDeletes;

trait BustsHomeCache
{
    public static function bootBustsHomeCache(): void
    {
        static::saved(function () {
            HomeContentCache::flush();
        });

        static::deleted(function () {
            HomeContentCache::flush();
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function () {
                HomeContentCache::flush();
            });

            static::forceDeleted(function () {
                HomeContentCache::flush();
            });
        }
    }
}
