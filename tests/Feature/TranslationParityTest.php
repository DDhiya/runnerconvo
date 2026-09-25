<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Enforces the invariant the README's drift-check snippet checks by hand: every
 * lang/en/*.php file has a key-identical lang/ms twin. Iterates from en, so the
 * deliberately partial, ms-only lang/ms/validation.php is naturally excluded.
 */
class TranslationParityTest extends TestCase
{
    public function test_every_english_lang_file_has_a_key_identical_malay_twin(): void
    {
        $files = glob(lang_path('en/*.php'));
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $name = basename($file);
            $twin = lang_path('ms/'.$name);

            $this->assertFileExists($twin, "lang/ms/{$name} is missing.");

            $en = $this->flatten(require $file);
            $ms = $this->flatten(require $twin);

            sort($en);
            sort($ms);

            $this->assertSame($en, $ms, "lang/en/{$name} and lang/ms/{$name} keys have drifted.");
        }
    }

    /**
     * @param  array<array-key, mixed>  $array
     * @return list<string>
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $keys = [];

        foreach ($array as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flatten($value, $path));
            } else {
                $keys[] = $path;
            }
        }

        return $keys;
    }
}
