<?php

declare(strict_types=1);

namespace Bakame\Aide\Enum;

use BackedEnum;
use Closure;
use UnitEnum;
use ValueError;

final class JavascriptConverter
{
    private const EXPORT = 'export ';
    private const EXPORT_DEFAULT = 'export default ';
    private const EXPORT_NONE = '';

    private function __construct(
        private readonly bool $useSymbol,
        private readonly bool $useImmutability,
        private readonly ?Closure $propertyNameCasing,
        private readonly int $indentSize,
        private readonly string $export,
        private readonly int $unitEnumStartAt
    ) {
    }

    public static function new(): self
    {
        return new self(
            useSymbol: false,
            useImmutability: true,
            propertyNameCasing: null,
            indentSize: 2,
            export: self::EXPORT_NONE,
            unitEnumStartAt: 0,
        );
    }

    public function propertyNameCase(Closure $casing = null): self
    {
        return new self(
            $this->useSymbol,
            $this->useImmutability,
            $casing,
            $this->indentSize,
            $this->export,
            $this->unitEnumStartAt,
        );
    }

    public function useImmutability(): self
    {
        return match ($this->useImmutability) {
            true => $this,
            default => new self(
                $this->useSymbol,
                true,
                $this->propertyNameCasing,
                $this->indentSize,
                $this->export,
                $this->unitEnumStartAt,
            ),
        };
    }

    public function ignoreImmutability(): self
    {
        return match ($this->useImmutability) {
            false => $this,
            default => new self(
                $this->useSymbol,
                false,
                $this->propertyNameCasing,
                $this->indentSize,
                $this->export,
                $this->unitEnumStartAt,
            ),
        };
    }

    public function useSymbol(): self
    {
        return match ($this->useSymbol) {
            true => $this,
            default => new self(
                true,
                $this->useImmutability,
                $this->propertyNameCasing,
                $this->indentSize,
                $this->export,
                $this->unitEnumStartAt,
            ),
        };
    }

    public function ignoreSymbol(): self
    {
        return match ($this->useSymbol) {
            false => $this,
            default => new self(
                false,
                $this->useImmutability,
                $this->propertyNameCasing,
                $this->indentSize,
                $this->export,
                $this->unitEnumStartAt,
            ),
        };
    }

    public function useExportDefault(): self
    {
        return match ($this->export) {
            self::EXPORT_DEFAULT => $this,
            default => new self(
                $this->useSymbol,
                $this->useImmutability,
                $this->propertyNameCasing,
                $this->indentSize,
                self::EXPORT_DEFAULT,
                $this->unitEnumStartAt,
            ),
        };
    }

    public function useExport(): self
    {
        return match ($this->export) {
            self::EXPORT => $this,
            default => new self(
                $this->useSymbol,
                $this->useImmutability,
                $this->propertyNameCasing,
                $this->indentSize,
                self::EXPORT,
                $this->unitEnumStartAt,
            ),
        };
    }

    public function ignoreExport(): self
    {
        return match ($this->export) {
            self::EXPORT_NONE => $this,
            default => new self(
                $this->useSymbol,
                $this->useImmutability,
                $this->propertyNameCasing,
                $this->indentSize,
                self::EXPORT_NONE,
                $this->unitEnumStartAt,
            ),
        };
    }

    public function startAt(int $startAt): self
    {
        return match (true) {
            $startAt === $this->unitEnumStartAt => $this,
            default => new self(
                $this->useSymbol,
                $this->useImmutability,
                $this->propertyNameCasing,
                $this->indentSize,
                $this->export,
                $startAt,
            ),
        };
    }

    public function indentSize(int $indentSize): self
    {
        return match (true) {
            $indentSize < 0 => throw new ValueError('indentation size can no be negative.'),
            $indentSize === $this->indentSize => $this,
            default => new self(
                $this->useSymbol,
                $this->useImmutability,
                $this->propertyNameCasing,
                $indentSize,
                $this->export,
                $this->unitEnumStartAt,
            ),
        };
    }

    /**
     * Converts the Enum into a Javascript object.
     *
     * <ul>
     *     <li>If the object name is null the object is not assign to const variable</li>
     *     <li>If the object name is the empty string the PHP namespaced class name will be used</li>
     *     <li>If the object name is a non-empty string, it will be used as is as the const variable name</li>
     * </ul>
     *
     * @param class-string<BackedEnum> $enumClass
     */
    public function convertToObject(string $enumClass, ?string $objectName = ''): string
    {
        $space = '';
        $eol = '';
        if (0 < $this->indentSize) {
            $space = str_repeat(' ', $this->indentSize);
            $eol = "\n";
        }

        $body = $this->getObjectBody($enumClass, $space, $eol);
        $output = '{'.$eol.$body.'}';
        if ($this->useImmutability) {
            $output = "Object.freeze($output)";
        }

        $objectName = $this->sanitizeName($objectName, $enumClass);
        if (null !== $objectName) {
            $output = "const $objectName = $output";
            if (self::EXPORT_DEFAULT === $this->export) {
                return $output.';'.$eol.$this->export.$objectName.';'.$eol;
            }
        }

        return $this->export.$output.$eol;
    }

    /**
     * @param class-string<UnitEnum> $enumClass
     */
    private function getObjectBody(string $enumClass, string $space, string $eol): string
    {
        $this->filterBackedEnum($enumClass);

        $output = [];
        foreach ($enumClass::cases() as $offset => $enum) {
            $output[] = $space.$this->formatPropertyName($enum).': '.$this->formatPropertyValue($enum, $offset).',';
        }

        return implode($eol, $output).$eol;
    }

    /**
     * Converts the Enum into a Javascript class.
     *
     * <ul>
     *     <li>If the class name is the empty string the PHP namespaced class name will be used</li>
     *     <li>If the class name is a non-empty string, it will be used as is as the class name</li>
     * </ul>
     *
     * @param class-string<UnitEnum> $enumClass
     */
    public function convertToClass(string $enumClass, string $className = ''): string
    {
        $space = '';
        $eol = '';
        if (0 < $this->indentSize) {
            $space = str_repeat(' ', $this->indentSize);
            $eol = "\n";
        }

        /** @var string $className */
        $className = $this->sanitizeName($className, $enumClass);
        $body = $this->getClassBody($enumClass, $className, $space, $eol);
        $output = 'class '.$className.' {'.$eol.$body.$eol.$space.'constructor(name) {'.$eol.$space.$space.'this.name = name'.$eol.$space.'}'.$eol.'}';

        return $this->export.$output.$eol;
    }

    /**
     * @param class-string<UnitEnum> $enumClass
     */
    private function getClassBody(string $enumClass, string $className, string $space, string $eol): string
    {
        $this->filterBackedEnum($enumClass);

        $output = [];
        foreach ($enumClass::cases() as $offset => $enum) {
            $output[] = $space."static {$this->formatPropertyName($enum)} = new $className({$this->formatPropertyValue($enum, $offset)})";
        }

        return implode($eol, $output).$eol;

    }

    /**
     * @param class-string<UnitEnum> $enumClass
     *
     * @throws ValueError If the given string does not represent a Backed Enum class
     */
    private function filterBackedEnum(string $enumClass): void
    {
        if (!enum_exists($enumClass)) {
            throw new ValueError($enumClass.' is not a valid PHP Enum.');
        }
    }

    private function sanitizeName(?string $className, string $enumClass): ?string
    {
        if ('' !== $className) {
            return $className;
        }

        $parts = explode('\\', $enumClass);

        return (string) array_pop($parts);
    }

    private function formatPropertyName(UnitEnum $enum): string
    {
        return match ($this->propertyNameCasing) {
            null => $enum->name,
            default => ($this->propertyNameCasing)($enum->name),
        };
    }

    private function formatPropertyValue(UnitEnum $enum, int $offset = 0): string|int
    {
        $isBackedEnum = $enum instanceof BackedEnum;
        $value = $isBackedEnum ? $enum->value : $offset + $this->unitEnumStartAt;
        $value = is_string($value) ? '"'.$value.'"' : $value;

        return match ($isBackedEnum) {
            true => match ($this->useSymbol) {
                true => 'Symbol('.$value.')',
                default => $value,
            },
            false => 'Symbol('.$value.')',
        };
    }
}
