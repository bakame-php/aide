<?php

declare(strict_types=1);

namespace Bakame\Aide\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValueError;

final class JavaScriptConverterTest extends TestCase
{
    #[Test]
    public function it_will_fails_with_a_negative_indent_size(): void
    {
        $this->expectException(ValueError::class);

        JavaScriptConverter::new()->indentSize(-1);
    }

    #[Test]
    public function it_will_fails_converting_a_non_enum(): void
    {
        $this->expectException(ValueError::class);

        JavaScriptConverter::new()->convertToObject(self::class); /* @phpstan-ignore-line */
    }

    #[Test]
    public function it_will_convert_to_a_javascript_immutable_object_by_default(): void
    {
        $expected = <<<JS
const HttpStatusCode = Object.freeze({
  HTTP_OK: 200,
  HTTP_REDIRECTION: 302,
  HTTP_NOT_FOUND: 404,
  HTTP_SERVER_ERROR: 500,
})

JS;
        $converter = JavaScriptConverter::new();
        $altConverter = $converter
            ->useImmutability()
            ->ignoreExport()
            ->ignoreSymbol()
            ->indentSize(2);

        self::assertSame($expected, $converter->convertToObject(HttpStatusCode::class));
        self::assertSame($expected, $altConverter->convertToObject(HttpStatusCode::class));
    }

    #[Test]
    public function it_can_convert_to_a_javascript_mutable_object(): void
    {
        $expected = <<<JS
export const Foobar = {
    http_ok: Symbol(200),
    http_redirection: Symbol(302),
    http_not_found: Symbol(404),
    http_server_error: Symbol(500),
}

JS;
        self::assertSame(
            $expected,
            JavaScriptConverter::new()
                ->ignoreImmutability()
                ->propertyNameCase(strtolower(...))
                ->useSymbol()
                ->useExport()
                ->indentSize(4)
                ->convertToObject(HttpStatusCode::class, 'Foobar')
        );
    }

    #[Test]
    public function it_will_convert_to_a_javascript_class_by_default(): void
    {
        $expected = <<<JS
class HttpStatusCode {
  static HTTP_OK = new HttpStatusCode(200)
  static HTTP_REDIRECTION = new HttpStatusCode(302)
  static HTTP_NOT_FOUND = new HttpStatusCode(404)
  static HTTP_SERVER_ERROR = new HttpStatusCode(500)

  constructor(name) {
    this.name = name
  }
}

JS;
        $converter = JavaScriptConverter::new();
        $altConverter = $converter
            ->useImmutability()
            ->ignoreExport()
            ->ignoreSymbol()
            ->indentSize(2);

        self::assertSame($expected, $converter->convertToClass(HttpStatusCode::class));
        self::assertSame($expected, $altConverter->convertToClass(HttpStatusCode::class));
    }

    #[Test]
    public function it_can_convert_to_a_javascript_class(): void
    {
        $pascalCase = fn (string $word): string => implode('', array_map(
            ucfirst(...),
            explode(
                ' ',
                strtolower(str_replace(['_', '-'], [' ', ' '], $word))
            )
        ));

        $expected = <<<JS
export default class Foobar {
    static Ok = new Foobar(200)
    static Redirection = new Foobar(302)
    static NotFound = new Foobar(404)
    static ServerError = new Foobar(500)

    constructor(name) {
        this.name = name
    }
}

JS;
        $converter = JavaScriptConverter::new()
            ->useImmutability()
            ->ignoreSymbol()
            ->indentSize(4)
            ->useExportDefault()
            ->propertyNameCase(fn (string $name) => $pascalCase(strtolower(str_replace('HTTP_', '', $name))));

        self::assertSame(
            $expected,
            $converter->convertToClass(HttpStatusCode::class, 'Foobar')
        );
    }

    #[Test]
    public function it_can_convert_to_a_javascript_object_with_export_default_and_a_variable_name(): void
    {
        $pascalCase = fn (string $word): string => implode('', array_map(
            ucfirst(...),
            explode(
                ' ',
                strtolower(str_replace(['_', '-'], [' ', ' '], $word))
            )
        ));

        $expected = <<<JS
const StatusCode = Object.freeze({
    Ok: Symbol(200),
    Redirection: Symbol(302),
    NotFound: Symbol(404),
    ServerError: Symbol(500),
});
export default StatusCode;

JS;
        $actual = JavaScriptConverter::new()
            ->useImmutability()
            ->useExportDefault()
            ->useSymbol()
            ->indentSize(4)
            ->propertyNameCase(fn (string $name) => $pascalCase(strtolower(str_replace('HTTP_', '', $name))))
            ->convertToObject(HttpStatusCode::class, 'StatusCode');

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function it_can_convert_to_a_javascript_object_with_export_default_and_no_variable(): void
    {
        $pascalCase = fn (string $word): string => implode('', array_map(
            ucfirst(...),
            explode(
                ' ',
                strtolower(str_replace(['_', '-'], [' ', ' '], $word))
            )
        ));

        $expected = <<<JS
export default Object.freeze({Ok: Symbol(200),Redirection: Symbol(302),NotFound: Symbol(404),ServerError: Symbol(500),})
JS;
        $actual = JavaScriptConverter::new()
            ->useImmutability()
            ->useExportDefault()
            ->useSymbol()
            ->indentSize(0)
            ->propertyNameCase(fn (string $name) => $pascalCase(strtolower(str_replace('HTTP_', '', $name))))
            ->convertToObject(HttpStatusCode::class, null);

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function it_can_convert_a_pure_enum_with_different_starting_value(): void
    {
        $actualStartAtZero = JavaScriptConverter::new()
            ->indentSize(0)
            ->convertToObject(Dir::class);

        $actualStartAtFortyTwo = JavaScriptConverter::new()
            ->indentSize(0)
            ->valueStartAt(42)
            ->convertToObject(Dir::class);

        $expectedZero = <<<JS
const Dir = Object.freeze({Top: Symbol(0),Down: Symbol(1),Left: Symbol(2),Right: Symbol(3),})
JS;
        $expectedFortyTwo = <<<JS
const Dir = Object.freeze({Top: Symbol(42),Down: Symbol(43),Left: Symbol(44),Right: Symbol(45),})
JS;

        self::assertNotSame($actualStartAtFortyTwo, $actualStartAtZero);
        self::assertSame($expectedZero, $actualStartAtZero);
        self::assertSame($expectedFortyTwo, $actualStartAtFortyTwo);
    }
}

enum Dir
{
    case Top;
    case Down;
    case Left;
    case Right;
}
