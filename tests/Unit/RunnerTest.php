<?php

namespace Tests\Unit;

use App\Models\Runner;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RunnerTest extends TestCase
{
    #[DataProvider('phoneProvider')]
    public function test_phone_normalisation(string $input, string $expected): void
    {
        $this->assertSame($expected, Runner::normalisePhone($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function phoneProvider(): array
    {
        return [
            'already digits-only' => ['60145332637', '60145332637'],
            'plus and spaces and dashes' => ['+60 14-533 2637', '60145332637'],
            'local format with leading zero' => ['014-533 2637', '60145332637'],
        ];
    }

    public function test_the_whatsapp_url_accessor(): void
    {
        $runner = new Runner(['phone' => '60145332637']);

        $this->assertSame('https://wa.me/60145332637', $runner->whatsapp_url);
    }
}
