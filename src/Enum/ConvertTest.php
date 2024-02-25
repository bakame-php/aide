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
        self::assertSame($expected, Direction::toJavaScriptObject());
    }

    #[Test]
    public function it_can_get_information_from_a_backed_enumeration(): void
    {
        $expectedAssociative = [
            'North' => 'north',
            'South' => 'south',
            'East' => 'east',
            'West' => 'west',
        ];

        $expectedObject = <<<JS
const Cardinal = Object.freeze({
  North: "north",
  South: "south",
  East: "east",
  West: "west"
})

JS;
        $expectedClass = <<<JS
class Cardinal {
  static North = new Cardinal("north")
  static South = new Cardinal("south")
  static East = new Cardinal("east")
  static West = new Cardinal("west")

  constructor(name) {
    this.name = name
  }
}

JS;

        self::assertSame($expectedAssociative, Cardinal::toAssociative());
        self::assertSame($expectedObject, Cardinal::toJavaScriptObject());
        self::assertSame($expectedClass, Cardinal::toJavaScriptClass());
    }
}
