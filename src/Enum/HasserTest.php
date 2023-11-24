<?php

declare(strict_types=1);

namespace Bakame\Aide\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HasserTest extends TestCase
{
    #[Test]
    public function it_can_tell_whether_a_name_is_in_use(): void
    {
        self::assertTrue(WeekDays::hasName('Monday'));
        self::assertFalse(WeekDays::hasName('Saturday'));
        self::assertTrue(WeekEnd::hasName('Saturday'));
        self::assertFalse(WeekEnd::hasName('Monday'));
    }

    #[Test]
    public function it_can_tell_whether_a_value_is_in_use(): void
    {
        self::assertTrue(WeekDays::hasValue(1));
        self::assertFalse(WeekDays::hasValue(7));
        self::assertFalse(WeekDays::hasValue('Saturday'));
        self::assertFalse(WeekEnd::hasValue('Saturday'));
    }

    #[Test]
    public function it_can_tell_whether_a_value_or_name_is_in_use(): void
    {
        self::assertTrue(WeekDays::has(1));
        self::assertTrue(WeekDays::has('Monday'));
        self::assertFalse(WeekDays::has(7));
        self::assertFalse(WeekDays::has('Saturday'));
        self::assertTrue(WeekEnd::has('Saturday'));
        self::assertFalse(WeekEnd::has('Wednesday'));
    }

    #[Test]
    public function it_can_tell_whether_a_case_is_available(): void
    {
        self::assertTrue(WeekDays::hasCase('Monday', 1));
        self::assertFalse(WeekDays::hasCase('Monday', 7));
        self::assertFalse(WeekDays::hasCase('Saturday', 1));
        self::assertFalse(WeekDays::hasCase('Saturday', 7));
        self::assertFalse(WeekEnd::hasCase('Saturday', 3));
    }
}

enum WeekDays: int
{
    use Hasser;

    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
}

enum WeekEnd
{
    use Hasser;

    case Saturday;
    case Sunday;
}
