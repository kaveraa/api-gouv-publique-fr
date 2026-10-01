<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Tests\Support;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use SplFileInfo;

/** Describes the public, non-internal surface of the core (src/ without the framework bridges). */
final class PublicApi
{
    /** @return array<string, array<string, mixed>> Sorted by class name. */
    public static function describe(): array
    {
        $root = dirname(__DIR__, 2).'/src';
        $api = [];
        foreach (self::phpFiles($root) as $file) {
            $relative = substr($file->getPathname(), strlen($root) + 1);
            if (str_starts_with($relative, 'Laravel') || str_starts_with($relative, 'Symfony')) {
                continue;
            }
            $class = 'Kaveraa\\ApiGouv\\'.str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relative);
            if (! class_exists($class) && ! interface_exists($class)) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            if (self::isInternal($reflection->getDocComment())) {
                continue;
            }
            $api[$class] = self::describeClass($reflection);
        }
        ksort($api);

        return $api;
    }

    /**
     * @param  array<string, mixed>  $expected
     * @param  array<string, mixed>  $current
     * @return list<string> Human readable differences, empty when equal.
     */
    public static function diff(array $expected, array $current): array
    {
        $before = self::flatten($expected);
        $after = self::flatten($current);
        $lines = [];
        foreach (array_diff_key($before, $after) as $path => $value) {
            $lines[] = "removed: {$path} = {$value}";
        }
        foreach (array_diff_key($after, $before) as $path => $value) {
            $lines[] = "added: {$path} = {$value}";
        }
        foreach (array_intersect_key($before, $after) as $path => $value) {
            if ($after[$path] !== $value) {
                $lines[] = "changed: {$path}: {$value} -> {$after[$path]}";
            }
        }
        sort($lines);

        return $lines;
    }

    /** @return iterable<SplFileInfo> */
    private static function phpFiles(string $root): iterable
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        $files = [];
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }
        usort($files, static fn (SplFileInfo $a, SplFileInfo $b) => strcmp($a->getPathname(), $b->getPathname()));

        return $files;
    }

    /** @return array<string, mixed> */
    private static function describeClass(ReflectionClass $class): array
    {
        $constants = [];
        foreach ($class->getReflectionConstants(\ReflectionClassConstant::IS_PUBLIC) as $constant) {
            $constants[$constant->getName()] = var_export($constant->getValue(), true);
        }
        $properties = [];
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (self::isInternal($property->getDocComment())) {
                continue;
            }
            $properties[$property->getName()] = [
                'type' => self::typeName($property->getType(), $class->getName()),
                'readonly' => $property->isReadOnly(),
                'static' => $property->isStatic(),
            ];
        }
        $methods = [];
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $class->getName() || self::isInternal($method->getDocComment())) {
                continue;
            }
            $methods[$method->getName()] = [
                'static' => $method->isStatic(),
                'returns' => self::typeName($method->getReturnType(), $class->getName()),
                'parameters' => array_map(
                    static fn (ReflectionParameter $parameter) => self::describeParameter($parameter, $class->getName()),
                    $method->getParameters(),
                ),
            ];
        }
        ksort($constants);
        ksort($properties);
        ksort($methods);
        // The engine does not promise an order for interface names, so sort them.
        $interfaces = $class->getInterfaceNames();
        sort($interfaces);

        return [
            'kind' => $class->isInterface() ? 'interface' : ($class->isEnum() ? 'enum' : 'class'),
            'final' => $class->isFinal(),
            'readonly' => $class->isReadOnly(),
            'abstract' => $class->isAbstract() && ! $class->isInterface(),
            'parent' => $class->getParentClass() !== false ? $class->getParentClass()->getName() : null,
            'interfaces' => $interfaces,
            'constants' => $constants,
            'properties' => $properties,
            'methods' => $methods,
        ];
    }

    /** @return array<string, mixed> */
    private static function describeParameter(ReflectionParameter $parameter, string $class): array
    {
        $type = $parameter->getType();

        return [
            'name' => $parameter->getName(),
            'type' => self::typeName($type, $class),
            'nullable' => $type === null || $type->allowsNull(),
            'default' => $parameter->isDefaultValueAvailable() ? var_export($parameter->getDefaultValue(), true) : null,
            'variadic' => $parameter->isVariadic(),
            'byReference' => $parameter->isPassedByReference(),
        ];
    }

    /** PHP 8.5 prints "self" as the class name, older versions print "self": normalise so the snapshot is stable. */
    private static function typeName(?\ReflectionType $type, string $class): string
    {
        $name = (string) $type;

        return (string) preg_replace('/\b(self|static)\b/', str_replace('\\', '\\\\', $class), $name);
    }

    private static function isInternal(string|false $docComment): bool
    {
        return $docComment !== false && str_contains($docComment, '@internal');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string> "path" => scalar as string
     */
    private static function flatten(array $data, string $prefix = ''): array
    {
        $flat = [];
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) {
                if ($value === []) {
                    $flat[$path] = '[]';
                } else {
                    $flat += self::flatten($value, $path);
                }
            } else {
                $flat[$path] = var_export($value, true);
            }
        }

        return $flat;
    }
}
