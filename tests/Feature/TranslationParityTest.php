<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Enforces the invariant the README's drift-check snippet checks by hand:
 * lang/en/landing.php and lang/ms/landing.php must have identical key structures.
 */
class TranslationParityTest extends TestCase
{
    public function test_english_and_malay_landing_copy_have_the_same_keys(): void
    {
        $en = $this->flatten(require lang_path('en/landing.php'));
        $ms = $this->flatten(require lang_path('ms/landing.php'));

        sort($en);
        sort($ms);

        $this->assertSame($en, $ms, 'lang/en/landing.php and lang/ms/landing.php keys have drifted.');
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
