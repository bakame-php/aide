<?php

declare(strict_types=1);

namespace Bakame\Aide\Error;

use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use const E_ALL;
use const E_DEPRECATED;
use const E_NOTICE;
use const E_STRICT;
use const E_WARNING;

final class CloakTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cloak::silentOnError();
    }

    #[Test]
    public function it_returns_information_about_its_error_reporting_level(): void
    {
        $lambda = Cloak::warning(touch(...));
        $res = $lambda('/foo');

        self::assertFalse($res);
        self::assertTrue($lambda->errorLevel()->contains(E_WARNING));
        self::assertFalse($lambda->errorLevel()->contains(E_NOTICE));
        self::assertCount(1, $lambda->errors());
    }

    #[Test]
    public function it_will_include_nothing_in_case_of_success(): void
    {
        $lambda = Cloak::userWarning(strtoupper(...));
        $res = $lambda('foo');

        self::assertSame('FOO', $res);
        self::assertCount(0, $lambda->errors());
    }

    public function testGetErrorReporting(): void
    {
        $lambda = Cloak::deprecated(strtoupper(...));

        self::assertTrue($lambda->errorLevel()->contains(E_DEPRECATED));
    }

    public function testCapturesTriggeredError(): void
    {
        $lambda = Cloak::all(trigger_error(...));
        $lambda('foo');

        self::assertSame('foo', $lambda->errors()->last()?->getMessage());
    }

    public function testCapturesSilencedError(): void
    {
        $lambda = Cloak::warning(fn (string $x) => @trigger_error($x));
        $lambda('foo');

        self::assertTrue($lambda->errors()->isEmpty());
    }

    public function testErrorTransformedIntoARuntimeException(): void
    {
        $this->expectException(CloakedErrors::class);

        Cloak::throwOnError();
        $touch = Cloak::warning(touch(...));
        $touch('/foo');
    }

    public function testErrorTransformedIntoAnInvalidArgumentException(): void
    {
        Cloak::throwOnError();
        $this->expectException(CloakedErrors::class);

        $touch = Cloak::all(touch(...));
        $touch('/foo');
    }

    public function testSpecificBehaviourOverrideGeneralErrorSetting(): void
    {
        $this->expectNotToPerformAssertions();

        Cloak::throwOnError();
        $touch = Cloak::all(touch(...), Cloak::SILENT);
        $touch('/foo');
    }

    public function testCaptureNothingThrowNoException(): void
    {
        Cloak::throwOnError();
        $strtoupper = Cloak::notice(strtoupper(...));

        self::assertSame('FOO', $strtoupper('foo'));
    }

    #[Test]
    public function it_can_detect_the_level_to_include(): void
    {
        $touch = new Cloak(
            touch(...),
            Cloak::THROW,
            E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED
        );

        $errorLevel = $touch->errorLevel();

        self::assertFalse($errorLevel->contains(E_NOTICE));
        self::assertTrue($touch->errorsAreThrown());
        self::assertFalse($touch->errorsAreSilenced());
    }

    #[Test]
    public function it_can_collection_all_errors(): void
    {
        $closure = function (string $path): array|false {
            touch($path);

            return file($path);
        };

        $lambda = Cloak::warning($closure);
        $res = $lambda('/foobar');
        $errors = $lambda->errors();
        self::assertFalse($res);
        self::assertCount(2, $errors);
        self::assertStringContainsString('touch(): Unable to create file /foobar because', $errors->first()?->getMessage() ?? '');
        self::assertSame('file(/foobar): Failed to open stream: No such file or directory', $errors->last()?->getMessage() ?? '');
    }

    #[Test]
    public function it_does_not_interfer_with_exception(): void
    {
        $this->expectException(Exception::class);

        $lambda = Cloak::warning(fn () => throw new Exception());
        $lambda();
    }
}
