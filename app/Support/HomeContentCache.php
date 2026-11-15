<?php

namespace App\Support;

use App\Models\HomeCard;
use App\Models\HomeDoctor;
use App\Models\HomeTestimony;
use App\Models\HomeText;
use Illuminate\Support\Facades\Cache;

class HomeContentCache
{
    public const CACHE_KEY = 'home.content.v1';

    /**
     * Get cached public home page content or build and cache it forever.
     *
     * @return array<string, mixed>
     */
    public function get(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return [
                'texts' => HomeText::pluck('value', 'id'),
                'aboutCards' => HomeCard::where('section', 'about')->orderBy('sort_order')->get(),
                'missionCards' => HomeCard::where('section', 'mission')->orderBy('sort_order')->get(),
                'coreValuesCards' => HomeCard::where('section', 'core_values')->orderBy('sort_order')->get(),
                'serviceCards' => HomeCard::where('section', 'service')->orderBy('sort_order')->get(),
                'doctors' => HomeDoctor::orderBy('sort_order')->get(),
                'testimonials' => HomeTestimony::latest()->get(),
            ];
        });
    }

    /**
     * Invalidate cached home content.
     */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
