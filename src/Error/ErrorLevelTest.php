<?php

declare(strict_types=1);

namespace Bakame\Aide\Error;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use ValueError;

use const E_ALL;
use const E_DEPRECATED;
use const E_NOTICE;
use const E_WARNING;

final class ErrorLevelTest extends TestCase
{
    #[Test]
    #[DataProvider('provideErrorLevelContains')]
    public function it_can_tell_wether_the_error_level_is_contained(int $errorLevel, int|string $test, bool $expected): void
    {
        self::assertSame($expected, ErrorLevel::fromValue($errorLevel)->contains($test));
    }

    /**
     * @return iterable<string, array{errorLevel:int, test:int|string, expected:bool}>
     */
    public static function provideErrorLevelContains(): iterable
    {
        yield '-1 contains everything (1)' => [
            'errorLevel' => -1,
            'test' => 0,
            'expected' => true,
        ];

        yield '-1 contains everything (2)' => [
            'errorLevel' => -1,
            'test' => E_ALL | E_WARNING,
            'expected' => true,
        ];

        yield '0 contains nothing (1)' => [
            'errorLevel' => 0,
            'test' => -1,
            'expected' => false,
        ];

        yield '0 contains nothing (2)' => [
            'errorLevel' => 0,
            'test' => E_DEPRECATED | E_NOTICE,
            'expected' => false,
        ];

        yield 'union error level (1)' => [
            'errorLevel' => E_DEPRECATED | E_NOTICE,
            'test' => 'E_DEPRECATED',
            'expected' => true,
        ];

        yield 'union error level (2)' => [
            'errorLevel' => E_WARNING | E_NOTICE,
            'test' => E_NOTICE,
            'expected' => true,
        ];

        yield 'exclusion error level (1)' => [
            'errorLevel' => E_ALL & ~E_NOTICE,
            'test' => E_WARNING,
            'expected' => true,
        ];

        yield 'exclusion error level (2)' => [
            'errorLevel' => E_ALL & ~E_WARNING,
            'test' => E_WARNING,
            'expected' => false,
        ];
    }

    #[Test]
    public function it_can_create_error_level_by_exclusion(): void
    {
        self::assertSame(
            ErrorLevel::fromExclusion(E_WARNING, 'E_WARNING')->value(),
            ErrorLevel::fromValue(E_ALL & ~E_WARNING)->value(),
        );
    }

    #[Test]
    public function it_can_create_error_level_from_environment(): void
    {
        self::assertSame(
            ErrorLevel::fromEnvironment()->value(),
            ErrorLevel::fromValue(error_reporting())->value(),
        );
    }

    #[Test]
    public function it_can_create_error_level_by_inclusion(): void
    {
        self::assertSame(
            ErrorLevel::fromInclusion(E_WARNING, 'E_WARNING')->value(),
            ErrorLevel::fromValue(E_WARNING)->value(),
        );
    }

    #[Test]
    public function it_fails_to_create_error_level_by_exclusion(): void
    {
        $this->expectException(ValueError::class);

        ErrorLevel::fromExclusion(-2, 'E_WARNING')->value();
    }

    #[Test]
    public function it_fails_to_create_error_level_by_inclusion(): void
    {
        $this->expectException(ValueError::class);

        ErrorLevel::fromInclusion(-2, 'E_FOOBAR')->value();
    }

    #[Test]
    public function it_can_create_error_level_by_name(): void
    {
        self::assertSame(
            ErrorLevel::fromName('E_WARNING')->value(),
            ErrorLevel::fromValue(E_WARNING)->value(),
        );
    }

    #[Test]
    public function it_fails_to_create_error_level_by_name(): void
    {
        $this->expectException(ValueError::class);

        ErrorLevel::fromName('E_FOOBAR');
    }

    #[Test]
    public function it_fails_to_create_error_level_by_value(): void
    {
        $this->expectException(ValueError::class);

        ErrorLevel::fromValue(-2);
    }

    #[Test]
    public function it_fails_to_create_error_level_by_inclusion_with_invalid_positive_integer(): void
    {
        $this->expectException(ValueError::class);

        ErrorLevel::fromInclusion(23);
    }

    #[Test]
    public function it_fails_to_create_error_level_by_exclusion_with_invalid_positive_integer(): void
    {
        $this->expectException(ValueError::class);

        ErrorLevel::fromExclusion(23);
    }
}
