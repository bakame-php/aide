<?php

declare(strict_types=1);

namespace Bakame\Aide\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConvertTest extends TestCase
{
    #[Test]
    public function it_can_get_information_from_a_pure_enumeration(): void
    {
        self::assertSame([], Direction::toAssociative());
        $expected = <<<JS
const Direction = Object.freeze({
  Top: Symbol(0),
  Down: Symbol(1),
  Left: Symbol(2),
  Right: Symbol(3)
})

JS;
        self::assertSame($expected, Direction::toJavaScript());
    }

    #[Test]
    public function it_can_get_information_from_a_backed_enumeration(): void
    {
        self::assertSame(['North' => 'north', 'South' => 'south', 'East' => 'east', 'West' => 'west'], Cardinal::toAssociative());
        $expected = <<<JS
const Cardinal = Object.freeze({
  North: "north",
  South: "south",
  East: "east",
  West: "west"
})

JS;
        self::assertSame($expected, Cardinal::toJavaScript());
    }
}
