<?php

namespace App\Support;

use App\Models\HomeCard;
use App\Models\HomeDoctor;
use App\Models\HomeTestimony;
use App\Models\HomeText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HomeContentCache
{
    public const CACHE_KEY = 'home.content.v1';

    /**
     * Get cached public home page content or build and cache it forever.
     * Automatically heals corrupted or incomplete cache.
     *
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (! $this->isValid($cached)) {
            $cached = $this->build();
            Cache::forever(self::CACHE_KEY, $cached);
        }

        return $cached;
    }

    /**
     * Build fresh home content payload.
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return [
            'texts' => HomeText::pluck('value', 'id')->all(),
            'aboutCards' => HomeCard::where('section', 'about')->orderBy('sort_order')->get(),
            'missionCards' => HomeCard::where('section', 'mission')->orderBy('sort_order')->get(),
            'coreValuesCards' => HomeCard::where('section', 'core_values')->orderBy('sort_order')->get(),
            'serviceCards' => HomeCard::where('section', 'service')->orderBy('sort_order')->get(),
            'doctors' => HomeDoctor::orderBy('sort_order')->get(),
            'testimonials' => HomeTestimony::latest()->get(),
        ];
    }

    /**
     * Validate that cached data is structurally complete and contains no incomplete classes.
     */
    protected function isValid(mixed $data): bool
    {
        if (! is_array($data)) {
            return false;
        }

        $requiredKeys = [
            'texts',
            'aboutCards',
            'missionCards',
            'coreValuesCards',
            'serviceCards',
            'doctors',
            'testimonials',
        ];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $data)) {
                return false;
            }

            $val = $data[$key];
            if (is_object($val) && get_class($val) === '__PHP_Incomplete_Class') {
                return false;
            }

            if ($val instanceof Collection || is_array($val)) {
                foreach ($val as $item) {
                    if (is_object($item) && get_class($item) === '__PHP_Incomplete_Class') {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Invalidate cached home content.
     */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
