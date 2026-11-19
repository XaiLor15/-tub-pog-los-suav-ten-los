<?php

use App\Support\Format;
use Illuminate\Support\HtmlString;

/*
 * Thin global wrappers around App\Support\Format so Blade views stay short.
 * All formatting rules live (and are unit-tested) in Format.
 */

if (! function_exists('money')) {
    function money(int|float|string|null $amount, bool $signed = false): string
    {
        return Format::money($amount, $signed);
    }
}

if (! function_exists('lao_date')) {
    function lao_date(mixed $date, bool $withTime = false): string
    {
        return Format::date($date, $withTime);
    }
}

if (! function_exists('phone_display')) {
    function phone_display(?string $phone): string
    {
        return Format::phone($phone);
    }
}

if (! function_exists('percent')) {
    function percent(int|float|null $value, int $decimals = 1): string
    {
        return Format::percent($value, $decimals);
    }
}

if (! function_exists('initial')) {
    function initial(?string $name): string
    {
        return Format::initial($name);
    }
}

if (! function_exists('icon_svg')) {
    /** Raw Lucide SVG markup (root size/class stripped), cached per request. */
    function icon_svg(string $name): string
    {
        static $cache = [];

        return $cache[$name] ??= (function () use ($name) {
            $file = resource_path('icons/'.basename($name).'.svg');
            if (! is_file($file)) {
                return '';
            }
            $svg = preg_replace('/<!--.*?-->/s', '', (string) file_get_contents($file));
            // Strip size/class only from the root <svg> tag (inner <rect> keeps its width/height).
            $svg = preg_replace_callback('/<svg\b[^>]*>/', fn ($m) => preg_replace('/\s(class|width|height)="[^"]*"/', '', $m[0]), $svg, 1);

            return trim(preg_replace('/\s+/', ' ', $svg));
        })();
    }
}

if (! function_exists('icon')) {
    /**
     * Inline a vendored Lucide icon (resources/icons). Stroke width and motion come from CSS
     * ([data-icon] in resources/css/components.css), so every icon shares one style.
     */
    function icon(string $name, string $class = 'size-5'): HtmlString
    {
        return new HtmlString(
            str_replace('<svg ', '<svg aria-hidden="true" focusable="false" data-icon="'.e($name).'" class="'.e($class).' shrink-0" ', icon_svg($name))
        );
    }
}

if (! function_exists('icon_sprite')) {
    /**
     * Hidden <symbol> sprite so JavaScript can render the same icons: <svg><use href="#i-x"/></svg>.
     *
     * @param  array<int, string>  $names
     */
    function icon_sprite(array $names): HtmlString
    {
        $symbols = array_map(function (string $name) {
            $svg = icon_svg($name);
            if ($svg === '') {
                return '';
            }
            $inner = preg_replace('/^<svg\b[^>]*>|<\/svg>$/', '', $svg);

            return '<symbol id="i-'.e($name).'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">'.$inner.'</symbol>';
        }, $names);

        return new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="position:absolute;width:0;height:0;overflow:hidden">'.implode('', $symbols).'</svg>');
    }
}

if (! function_exists('tone_classes')) {
    /** Badge/pill colours by semantic tone (StatusBadge). */
    function tone_classes(string $tone): string
    {
        return match ($tone) {
            'success' => 'bg-success-bg text-success',
            'warning' => 'bg-warning-bg text-warning',
            'destructive' => 'bg-destructive-bg text-destructive',
            'info' => 'bg-info-bg text-info',
            'primary' => 'bg-primary-soft text-primary',
            default => 'bg-surface-2 text-muted',
        };
    }
}
