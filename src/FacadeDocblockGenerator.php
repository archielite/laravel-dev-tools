<?php

namespace ArchiElite\LaravelDevTools;

use ArrayAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionMethodDecorator;

class FacadeDocblockGenerator
{
    public function resolveFacades(Collection $facades)
    {
        return $facades->each(function (ReflectionClass $facade) {
            $proxies = $this->resolveDocSees($facade);

            $resolvedMethods = $proxies
                ->flatMap(fn (ReflectionClass $class) => [$class, ...$this->resolveDocMixins($class)])
                ->flatMap(fn (ReflectionClass $class) => $this->resolveDocMethods($class))
                ->reject(fn (ReflectionMethodDecorator $method) => $this->isMagic($method))
                ->reject(fn (ReflectionMethodDecorator $method) => $this->isInternal($method))
                ->reject(fn (ReflectionMethodDecorator $method) => $this->isDeprecated($method))
                ->reject(fn (ReflectionMethodDecorator $method) => $this->fulfillsBuiltinInterface($method))
                ->reject(fn (ReflectionMethodDecorator $method) => $this->conflictsWithFacade($facade, $method))
                ->unique(fn (ReflectionMethodDecorator $method) => $this->resolveName($method))
                ->map(fn (ReflectionMethodDecorator $method) => $this->normalizeDetails($method))
            ;
        });
    }

    protected function resolveDocSees(ReflectionClass $class): Collection
    {
        return $this->resolveDocTags($class->getDocComment() ?: '', '@see')
            ->reject(fn (string $tag) => Str::startsWith($tag, 'https://'))
            ->filter(fn ($class) => class_exists($class))
            ->map(fn ($class) => new ReflectionClass($class));
    }

    protected function resolveDocMixins(ReflectionClass $class): Collection
    {
        return $this->resolveDocTags($class->getDocComment() ?: '', '@mixin')
            ->reject(fn (string $tag) => Str::startsWith($tag, 'https://'))
            ->filter(fn ($class) => class_exists($class))
            ->map(fn ($class) => new ReflectionClass($class))
            ->flatMap(fn ($class) => [$class, ...$this->resolveDocMixins($class)]);
    }

    protected function resolveDocMethods(ReflectionClass $class): Collection
    {
        return collect($class->getMethods(ReflectionMethod::IS_PUBLIC))
            ->map(fn ($method) => new ReflectionMethodDecorator($method, $class->getName()))
            ->merge($this->resolveDocMethods($class));
    }

    protected function resolveDocTags(string $docblock, string $tag): Collection
    {
        return Str::of($docblock)
            ->explode("\n")
            ->skip(1)
            ->reverse()
            ->skip(1)
            ->reverse()
            ->map(fn ($line) => ltrim($line, ' \*'))
            ->filter(fn ($line) => Str::startsWith($line, $tag))
            ->map(fn ($line) => Str::of($line)->after($tag)->trim()->toString())
            ->values();
    }

    protected function resolveName(ReflectionMethodDecorator|string $method): string
    {
        return is_string($method)
            ? Str::of($method)->after(' ')->before('(')->toString()
            : $method->getName();
    }

    protected function isMagic(ReflectionMethodDecorator|string $method): bool
    {
        return Str::startsWith(is_string($method) ? $method : $method->getName(), '__');
    }

    protected function isInternal(ReflectionMethodDecorator|string $method): bool
    {
        if (is_string($method)) {
            return false;
        }

        return resolveDocTags($method->getDocComment(), '@internal')->isNotEmpty();
    }

    protected function isDeprecated(ReflectionMethodDecorator|string $method): bool
    {
        if (is_string($method)) {
            return false;
        }

        return $method->isDeprecated() || resolveDocTags($method->getDocComment(), '@deprecated')->isNotEmpty();
    }

    protected function fulfillsBuiltinInterface(ReflectionMethodDecorator|string $method): bool
    {
        if (is_string($method)) {
            return false;
        }

        if ($method->sourceClass()->implementsInterface(ArrayAccess::class)) {
            return in_array($method->getName(), ['offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset']);
        }

        return false;
    }

    protected function conflictsWithFacade(ReflectionClass $facade, ReflectionMethodDecorator|string $method): bool
    {
        return collect($facade->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC))
            ->map(fn ($method) => $method->getName())
            ->contains(is_string($method) ? $method : $method->getName());
    }

    protected function normalizeDetails(ReflectionMethodDecorator|string $method): array|string
    {
        return is_string($method) ? $method : [
            'name' => $method->getName(),
            'parameters' => resolveParameters($method)
                ->map(fn ($parameter) => [
                    'name' => '$'.$parameter->getName(),
                    'optional' => $parameter->isOptional() && ! $parameter->isVariadic(),
                    'default' => $parameter->isDefaultValueAvailable()
                        ? $parameter->getDefaultValue()
                        : "❌ Unknown default for [{$parameter->getName()}] in [{$parameter->getDeclaringClass()?->getName()}::{$parameter->getDeclaringFunction()->getName()}] ❌",
                    'variadic' => $parameter->isVariadic(),
                    'type' => resolveDocParamType($method, $parameter) ?? resolveType($parameter->getType()) ?? 'void',
                ]),
            'returns' => resolveReturnDocType($method) ?? resolveType($method->getReturnType()) ?? 'void',
        ];
    }
}
