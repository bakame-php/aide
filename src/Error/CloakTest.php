<?php

declare(strict_types=1);

namespace Bakame\Aide\Error;

use ErrorException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use const E_ALL;
use const E_DEPRECATED;
use const E_NOTICE;
use const E_STRICT;

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
        self::assertTrue($lambda->includeWarning());
        self::assertFalse($lambda->includeNotice());
        self::assertInstanceOf(ErrorException::class, $lambda->lastError());
    }

    #[Test]
    public function it_will_include_nothing_in_case_of_success(): void
    {
        $lambda = Cloak::userWarning(strtoupper(...));
        $res = $lambda('foo');

        self::assertSame('FOO', $res);
        self::assertNull($lambda->lastError());
    }

    public function testGetErrorReporting(): void
    {
        $lambda = Cloak::deprecated(strtoupper(...));

        self::assertTrue($lambda->includeDeprecated());
    }

    public function testCapturesTriggeredError(): void
    {
        $lambda = Cloak::all(trigger_error(...));
        $lambda('foo');

        self::assertSame('foo', $lambda->lastError()?->getMessage());
    }

    public function testCapturesSilencedError(): void
    {
        $lambda = Cloak::warning(fn (string $x) => @trigger_error($x));
        $lambda('foo');

        self::assertNull($lambda->lastError());
    }

    public function testErrorTransformedIntoARuntimeException(): void
    {
        $this->expectException(ErrorException::class);

        Cloak::throwOnError();
        $touch = Cloak::warning(touch(...));
        $touch('/foo');
    }

    public function testErrorTransformedIntoAnInvalidArgumentException(): void
    {
        Cloak::throwOnError();
        $this->expectException(ErrorException::class);

        $touch = Cloak::all(touch(...));
        $touch('/foo');
    }

    public function testSpecificBehaviourOverrideGeneralErrorSetting(): void
    {
        Cloak::throwOnError();

        $touch = Cloak::all(touch(...), Cloak::SILENT);
        $touch('/foo');

        self::assertInstanceOf(ErrorException::class, $touch->lastError());
    }

    public function testCaptureNothingThrowNoException(): void
    {
        Cloak::throwOnError();
        $strtoupper = Cloak::strict(strtoupper(...));

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

        self::assertTrue($touch->includeAll());
        self::assertFalse($touch->includeStrict());
        self::assertFalse($touch->includeDeprecated());
        self::assertFalse($touch->includeNotice());
        self::assertTrue($touch->includeUserNotice());
        self::assertTrue($touch->includeUserDeprecated());
        self::assertTrue($touch->includeUserWarning());
        self::assertTrue($touch->errorsAreThrown());
        self::assertFalse($touch->errorsAreSilenced());
    }
}
