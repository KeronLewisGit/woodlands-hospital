<?php

use App\Models\Setting;

if (! function_exists('woodlands')) {
    function woodlands(string $key, mixed $default = null): mixed
    {
        return config('woodlands.'.$key, $default);
    }
}

if (! function_exists('setting')) {
    /** An editable value from the settings table (dashboard → Settings), with SettingsSeeder defaults. */
    function setting(string $key, ?string $default = null): ?string
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('gy_digits')) {
    /** Full international digits for a Guyana number written any common way (7 local digits → 592…), or null. */
    function gy_digits(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';
        if (strlen($digits) === 7) {
            $digits = '592'.$digits;
        }

        return strlen($digits) === 10 && str_starts_with($digits, '592') ? $digits : null;
    }
}

if (! function_exists('tel_href')) {
    /** tel: link. With an extension, phones that support it dial the extension after a pause. */
    function tel_href(?string $number, ?string $extension = null): string
    {
        $digits = gy_digits($number);
        $base = $digits ? 'tel:+'.$digits : 'tel:'.preg_replace('/[^\d+]/', '', (string) $number);

        return $extension ? $base.','.preg_replace('/\D+/', '', $extension) : $base;
    }
}

if (! function_exists('format_phone')) {
    /** 225-4050 for Guyana numbers (how they are written locally), the input unchanged otherwise. */
    function format_phone(?string $number, bool $international = false): string
    {
        $digits = gy_digits($number);
        if (! $digits) {
            return (string) $number;
        }
        $local = substr($digits, 3, 3).'-'.substr($digits, 6);

        return $international ? '+592 '.$local : $local;
    }
}

if (! function_exists('whatsapp_href')) {
    /** wa.me deep link with an optional pre-filled message. */
    function whatsapp_href(?string $number, ?string $text = null): string
    {
        $digits = gy_digits($number) ?? preg_replace('/\D+/', '', (string) $number);

        return 'https://wa.me/'.$digits.($text ? '?text='.rawurlencode($text) : '');
    }
}

if (! function_exists('input_string')) {
    /** A request value as a string. Arrays (?type[]=x) and other junk become ''. */
    function input_string(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}

if (! function_exists('phone_rule')) {
    /** Validation rule for a phone number typed any common way: 7 to 15 digits, any separators. */
    function phone_rule(): string
    {
        return 'regex:/^(?:\\D*\\d){7,15}\\D*$/';
    }
}

if (! function_exists('md_plain')) {
    /**
     * Text typed by a visitor, made safe for a Markdown mail line: Markdown punctuation is
     * backslash-escaped, so "[Click](https://…)" in a name arrives as plain text, never as a link.
     */
    function md_plain(?string $text): string
    {
        return (string) preg_replace('/([\\\\`*_\[\]()#!<>|~])/', '\\\\$1', (string) $text);
    }
}

if (! function_exists('lines_to_array')) {
    /**
     * Textarea with one item per line, bullets optional, to a clean list.
     *
     * @return list<string>
     */
    function lines_to_array(?string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $text) ?: [];
        $clean = array_map(fn (string $l) => trim((string) preg_replace('/^(?:[-*]|•)\s*/u', '', $l)), $lines);

        return array_values(array_filter($clean, fn (string $l) => $l !== ''));
    }
}

if (! function_exists('extension_list')) {
    /**
     * The "useful extensions" table: fixed rows plus the free-form extra_extensions setting ("Label | 265" per line).
     *
     * @return list<array{label: string, ext: string}>
     */
    function extension_list(): array
    {
        $rows = [
            ['label' => 'Doctors’ clinic times & appointments', 'ext' => (string) setting('ext_clinics')],
            ['label' => 'Radiology & ultrasound appointments', 'ext' => (string) setting('ext_radiology')],
            ['label' => 'Medical laboratory', 'ext' => (string) setting('ext_lab')],
            ['label' => 'Ambulance', 'ext' => (string) setting('ambulance_ext')],
        ];
        foreach (lines_to_array(setting('extra_extensions')) as $line) {
            [$label, $ext] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            $rows[] = ['label' => $label, 'ext' => $ext];
        }

        return array_values(array_filter($rows, fn (array $r) => $r['label'] !== '' && $r['ext'] !== ''));
    }
}

if (! function_exists('department')) {
    /** @return array<string, mixed>|null */
    function department(?string $key): ?array
    {
        return $key ? config('woodlands.departments.'.$key) : null;
    }
}

if (! function_exists('department_options')) {
    /**
     * Department key => name, for selects. Optionally only some groups.
     *
     * @param  list<string>|null  $groups
     * @return array<string, string>
     */
    function department_options(?array $groups = null): array
    {
        return collect((array) config('woodlands.departments'))
            ->filter(fn (array $d) => $groups === null || in_array($d['group'], $groups, true))
            ->map(fn (array $d) => (string) $d['name'])
            ->all();
    }
}

if (! function_exists('compact_number')) {
    /** 1,284 → 1.3K, used on stat tiles. */
    function compact_number(int|float $n): string
    {
        return match (true) {
            abs($n) >= 1_000_000 => round($n / 1_000_000, 1).'M',
            abs($n) >= 10_000 => round($n / 1_000).'K',
            default => number_format($n),
        };
    }
}

if (! function_exists('photo')) {
    /**
     * Responsive <img> attributes for a photo in public/img (WebP at 480/960/1920 wide).
     *
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    function photo(string $key): array
    {
        $meta = config('woodlands.photos.'.$key) ?? ['w' => 1920, 'h' => 1280];
        $url = fn (int $w) => asset("img/{$key}-{$w}.webp");

        return [
            'src' => $url(960),
            'srcset' => $url(480).' 480w, '.$url(960).' 960w, '.$url(1920).' 1920w',
            'width' => (int) $meta['w'],
            'height' => (int) $meta['h'],
        ];
    }
}
