<?php

declare(strict_types=1);

namespace Bakame\Aide\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValueError;

final class FactoryTest extends TestCase
{
    #[Test]
    public function it_can_instantiate_a_non_backed_enum_from_name(): void
    {
        self::assertSame(HttpMethod::Get, HttpMethod::tryFromName('Get'));
        self::assertSame(HttpMethod::Get, HttpMethod::tryFrom('Get'));
        self::assertSame(HttpMethod::Get, HttpMethod::from('Get'));

        self::assertNull(HttpMethod::tryFrom('Unknown'));
    }

    #[Test]
    public function it_can_instantiate_a_backed_enum_from_name(): void
    {
        self::assertSame(HttpMethodString::Get, HttpMethodString::tryFromName('Get'));
        self::assertSame(HttpMethodString::Get, HttpMethodString::fromName('Get'));
        self::assertSame(HttpMethodString::Get, HttpMethodString::tryFrom('GET'));
        self::assertSame(HttpMethodString::Get, HttpMethodString::from('GET'));

        self::assertNull(HttpMethodString::tryFrom('Get'));
    }

    #[Test]
    public function it_fails_if_the_name_is_unknown(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('"GET" is not a valid name for "'.HttpMethod::class.'" enumeration.');

        HttpMethod::fromName('GET');
    }
}

enum HttpMethod
{
    use Factory;

    case Get;
    case Post;
    case Put;
    case Head;
    case Options;
}

enum HttpMethodString: string
{
    use Factory;

    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Head = 'HEAD';
    case Options = 'OPTIONS';
}
