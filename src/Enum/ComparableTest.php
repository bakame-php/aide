<?php

declare(strict_types=1);

namespace Bakame\Aide\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ComparableTest extends TestCase
{
    #[Test]
    public function it_will_compare_unit_enum(): void
    {
        self::assertTrue(HttpStatusCode::HTTP_OK->equals(HttpStatusCode::HTTP_OK));
        self::assertTrue(HttpStatusCode::HTTP_OK->notEquals(HttpStatusCode::HTTP_SERVER_ERROR));
        self::assertTrue(HttpStatusCode::HTTP_OK->is(200));
        self::assertTrue(HttpStatusCode::HTTP_OK->is(HttpStatusCode::HTTP_NOT_FOUND, 'HTTP_OK', 303));
        self::assertTrue(HttpStatusCode::HTTP_OK->isNot('http_ok'));
        self::assertTrue(HttpStatusCode::HTTP_OK->isNot(HttpStatusCode::HTTP_NOT_FOUND, 'HTTP_KO', 303));
    }
}

enum HttpStatusCode: int
{
    use Comparable;

    case HTTP_OK = 200;
    case HTTP_REDIRECTION = 302;
    case HTTP_NOT_FOUND = 404;
    case HTTP_SERVER_ERROR = 500;
}
