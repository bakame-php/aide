<?php

declare(strict_types=1);

namespace Bakame\Aide\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InfoTest extends TestCase
{
    #[Test]
    public function it_can_get_information_from_a_pure_enumeration(): void
    {
        self::assertFalse(Direction::isBacked());
        self::assertTrue(Direction::isPure());
        self::assertSame(4, Direction::size());
        self::assertSame([], Direction::associative());
        self::assertSame(['Top', 'Down', 'Left', 'Right'], Direction::names());
        self::assertSame([], Direction::values());
        self::assertNull(Direction::nameOf('Up'));
    }

    #[Test]
    public function it_can_get_information_from_a_backed_enumeration(): void
    {
        self::assertTrue(Cardinal::isBacked());
        self::assertFalse(Cardinal::isPure());
        self::assertSame(4, Direction::size());
        self::assertSame(['North' => 'north', 'South' => 'south', 'East' => 'east', 'West' => 'west'], Cardinal::associative());
        self::assertSame(['North', 'South', 'East', 'West'], Cardinal::names());
        self::assertSame(['north', 'south', 'east', 'west'], Cardinal::values());
        self::assertSame('West', Cardinal::nameOf('west'));
    }
}

enum Direction
{
    use Info;

    case Top;
    case Down;
    case Left;
    case Right;
}

enum Cardinal: string
{
    use Info;

    case North = 'north';
    case South = 'south';
    case East = 'east';
    case West = 'west';
}
