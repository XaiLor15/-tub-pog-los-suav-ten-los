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

if (! function_exists('icon')) {
    /** Inline a vendored Lucide SVG (resources/icons) with the given classes. */
    function icon(string $name, string $class = 'size-5'): HtmlString
    {
        static $cache = [];
        $cache[$name] ??= (function () use ($name) {
            $file = resource_path('icons/'.basename($name).'.svg');
            if (! is_file($file)) {
                return '';
            }
            $svg = preg_replace('/<!--.*?-->/s', '', (string) file_get_contents($file));
            // Strip size/class only from the root <svg> tag (inner <rect> keeps its width/height).
            $svg = preg_replace_callback('/<svg\b[^>]*>/', fn ($m) => preg_replace('/\s(class|width|height)="[^"]*"/', '', $m[0]), $svg, 1);

            return trim(preg_replace('/\s+/', ' ', $svg));
        })();

        return new HtmlString(
            str_replace('<svg ', '<svg aria-hidden="true" focusable="false" class="'.e($class).' shrink-0" ', $cache[$name])
        );
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
