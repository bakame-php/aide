<?php

declare(strict_types=1);

namespace Bakame\Aide\Enum;

trait Convert
{
    /**
     * Converts the enum into an associative array.
     *
     * @return array<string, string|int>
     */
    public static function toAssociative(): array
    {
        return array_column(static::cases(), 'value', 'name');
    }

    /**
     * Convert the Enum into a Javascript object.
     */
    public static function toJavaScriptObject(): string
    {
        return JavaScriptConverter::new()->convertToObject(static::class);
    }

    /**
     * Convert the Enum into a Javascript class.
     */
    public static function toJavaScriptClass(): string
    {
        return JavaScriptConverter::new()->convertToClass(static::class);
    }
}
