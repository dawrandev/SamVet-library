<?php

/**
 * Every literal __() string on the CLIENT site must exist in lang/ru.json and
 * lang/kk.json.
 *
 * Without this, an untranslated string is invisible: Laravel falls back to the
 * key, so a Russian or Karakalpak visitor simply sees Uzbek on an otherwise
 * translated page and nothing anywhere reports it. That is exactly how the
 * articles page shipped with an untranslated subtitle, alongside eight strings
 * on the statistics page that had been missing for far longer.
 *
 * Client side only: the admin panel is Uzbek-only by project decision
 * (CLAUDE.md), so its keys are deliberately absent from these files.
 */

/** @return array<string, array<int, string>> key => files it appears in */
function clientTranslationKeys(): array
{
    $roots = [
        'resources/views/pages/site',
        'resources/views/partials/site',
        'resources/views/components/site',
    ];

    $files = [
        'resources/views/layouts/site.blade.php',
        'resources/views/layouts/site-auth.blade.php',
        // Enum labels the client site renders.
        'app/Enums/CatalogResourceType.php',
        'app/Enums/CatalogFormat.php',
        'app/Enums/CatalogSort.php',
        'app/Enums/CatalogSearchScope.php',
        'app/Enums/PublicationKind.php',
        'app/Enums/BookFormat.php',
    ];

    foreach ($roots as $root) {
        $path = base_path($root);

        if (! is_dir($path)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
                $files[] = $root.'/'.$file->getFilename();
            }
        }
    }

    $keys = [];

    foreach ($files as $relative) {
        $path = base_path($relative);

        if (! is_file($path)) {
            continue;
        }

        $source = file_get_contents($path);

        // Only literal single/double-quoted arguments — a __($variable) cannot
        // be resolved statically, and guessing at one would make this test lie.
        preg_match_all("/(?:__|@lang)\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $source, $single);
        preg_match_all('/(?:__|@lang)\(\s*"((?:[^"\\\\]|\\\\.)*)"/', $source, $double);

        foreach (array_merge($single[1], $double[1]) as $raw) {
            $key = str_replace(["\\'", '\\"', '\\\\'], ["'", '"', '\\'], $raw);
            $keys[$key][] = $relative;
        }
    }

    return $keys;
}

it('extracts a plausible number of client-site strings', function () {
    expect(count(clientTranslationKeys()))->toBeGreaterThan(200);
});

it('translates every client-site string into :locale', function (string $locale) {
    $translations = json_decode(file_get_contents(base_path("lang/{$locale}.json")), true);

    expect($translations)->toBeArray("lang/{$locale}.json is not valid JSON");

    $missing = [];

    foreach (clientTranslationKeys() as $key => $files) {
        if (! array_key_exists($key, $translations)) {
            $missing[] = $key.'   ← '.implode(', ', array_unique($files));
        }
    }

    expect($missing)->toBe([], sprintf(
        "lang/%s.json is missing %d client-site string(s):\n  %s",
        $locale,
        count($missing),
        implode("\n  ", $missing)
    ));
})->with(['ru', 'kk']);
