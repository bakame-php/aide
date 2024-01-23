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
        self::assertSame(['Top', 'Down', 'Left', 'Right'], Direction::names());
        self::assertSame([], Direction::values());
        self::assertNull(Direction::nameOf('Up'));
        $expected = <<<JS
const Direction = Object.freeze({
  Top: Symbol(0),
  Down: Symbol(1),
  Left: Symbol(2),
  Right: Symbol(3),
})

JS;
        self::assertSame($expected, Direction::toJavaScript());
    }

    #[Test]
    public function it_can_get_information_from_a_backed_enumeration(): void
    {
        self::assertTrue(Cardinal::isBacked());
        self::assertFalse(Cardinal::isPure());
        self::assertSame(4, Direction::size());
        self::assertSame(['North', 'South', 'East', 'West'], Cardinal::names());
        self::assertSame(['north', 'south', 'east', 'west'], Cardinal::values());
        self::assertSame('West', Cardinal::nameOf('west'));
        $expected = <<<JS
const Cardinal = Object.freeze({
  North: "north",
  South: "south",
  East: "east",
  West: "west",
})

JS;
        self::assertSame($expected, Cardinal::toJavaScript());
    }
}

enum Direction
{
    use Info;
    use Convert;

    case Top;
    case Down;
    case Left;
    case Right;
}

enum Cardinal: string
{
    use Info;
    use Convert;

    case North = 'north';
    case South = 'south';
    case East = 'east';
    case West = 'west';
}
