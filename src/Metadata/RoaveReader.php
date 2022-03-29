<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\Reflector;
use Serafim\Contracts\Attribute\Contract;
use Serafim\Contracts\Attribute\Ensure;
use Serafim\Contracts\Attribute\Invariant;
use Serafim\Contracts\Attribute\Verify;
use Serafim\Contracts\Metadata\Info\AttributeMetadata;
use Serafim\Contracts\Metadata\Info\ClassLikeMetadata;
use Serafim\Contracts\Metadata\Info\ClassMetadata;
use Serafim\Contracts\Metadata\Info\ClassModifier;
use Serafim\Contracts\Metadata\Info\EnumMetadata;
use Serafim\Contracts\Metadata\Info\InterfaceMetadata;
use Serafim\Contracts\Metadata\Info\MethodMetadata;
use Serafim\Contracts\Metadata\Info\MethodModifier;
use Serafim\Contracts\Metadata\Info\MethodVisibility;
use Serafim\Contracts\Metadata\Info\TraitMetadata;

/**
 * @psalm-type AttributeAwareReflection = ( ReflectionClass
 *                                        | ReflectionClassConstant
 *                                        | ReflectionEnumCase
 *                                        | ReflectionFunction
 *                                        | ReflectionMethod
 *                                        | ReflectionParameter
 *                                        | ReflectionProperty )
 *
 * @see Contract
 * @see ReflectionClass
 * @see ReflectionClassConstant
 * @see ReflectionEnumCase
 * @see ReflectionFunction
 * @see ReflectionMethod
 * @see ReflectionParameter
 * @see ReflectionProperty
 */
final class RoaveReader implements ReaderInterface
{
    /**
     * @var array<class-string>
     */
    private const NON_CLONEABLE_CLASSES = [
        // Exceptions
        \Throwable::class,
        \Exception::class,
        \Error::class,
        \ValueError::class,
        \TypeError::class,
        \ParseError::class,
        \ArgumentCountError::class,
        \ArithmeticError::class,
        \CompileError::class,
        \DivisionByZeroError::class,
        \UnhandledMatchError::class,
        \ErrorException::class,

        // Enums
        \UnitEnum::class,
        \BackedEnum::class,
    ];

    /**
     * @var Reflector
     */
    private readonly Reflector $reflector;

    /**
     * @var array<class-string, ClassLikeMetadata>
     */
    private array $metadata = [];

    /**
     * @param Reflector|null $reflector
     */
    public function __construct(
        Reflector $reflector = null,
    ) {
        $this->reflector = $reflector ?? (new BetterReflection())->reflector();
    }

    /**
     * {@inheritDoc}
     * @psalm-suppress PossiblyInvalidArrayOffset
     * @psalm-suppress PropertyTypeCoercion
     */
    public function read(string $class): ClassLikeMetadata
    {
        return $this->metadata[\trim($class, '\\')] ??= $this->create(
            $this->reflector->reflectClass($class)
        );
    }

    /**
     * @param ReflectionClass $class
     * @return ClassLikeMetadata
     */
    private function create(ReflectionClass $class): ClassLikeMetadata
    {
        return $this->metadata[$class->getName()] ??= match(true) {
            $class->isTrait() => $this->fromTrait($class),
            $class->isInterface() => $this->fromInterface($class),
            $class->isEnum() => $this->fromEnum($class),
            default => $this->fromClass($class),
        };
    }

    /**
     * @param ReflectionClass|null $class
     * @return ClassLikeMetadata|null
     */
    private function createOrNull(?ReflectionClass $class): ?ClassLikeMetadata
    {
        if ($class === null) {
            return null;
        }

        return $this->create($class);
    }

    /**
     * @param iterable<ReflectionClass> $classes
     * @return iterable<ClassLikeMetadata>
     */
    private function createMany(iterable $classes): iterable
    {
        foreach ($classes as $class) {
            if ($class->isUserDefined()) {
                yield $this->create($class);
            }
        }
    }

    /**
     * @param ReflectionClass $class
     * @return InterfaceMetadata
     */
    private function fromInterface(ReflectionClass $class): InterfaceMetadata
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress InvalidScalarArgument
         */
        return new InterfaceMetadata(
            name: $class->getName(),
            methods: [...$this->createMethods($class)],
            invariants: [...$this->fetchInvariants($class)],
            interfaces: [...$this->createMany($class->getInterfaces())],
        );
    }

    /**
     * @param ReflectionClass $class
     * @return TraitMetadata
     */
    private function fromTrait(ReflectionClass $class): TraitMetadata
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress InvalidScalarArgument
         */
        return new TraitMetadata(
            name: $class->getName(),
            methods: [...$this->createMethods($class)],
            invariants: [...$this->fetchInvariants($class)],
            traits: [...$this->createMany($class->getTraits())],
        );
    }

    /**
     * @param ReflectionClass $class
     * @return EnumMetadata
     */
    private function fromEnum(ReflectionClass $class): EnumMetadata
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress InvalidScalarArgument
         */
        return new EnumMetadata(
            name: $class->getName(),
            methods: [...$this->createMethods($class)],
            invariants: [...$this->fetchInvariants($class)],
            interfaces: [...$this->createMany($class->getInterfaces())],
        );
    }

    /**
     * @param ReflectionClass $class
     * @return ClassMetadata
     */
    private function fromClass(ReflectionClass $class): ClassMetadata
    {
        $modifiers = match (true) {
            $class->isFinal() => [ClassModifier::FINAL],
            $class->isAbstract() => [ClassModifier::ABSTRACT],
            default => [],
        };

        $parent = $this->createOrNull($class->getParentClass());
        assert($parent === null || $parent instanceof ClassMetadata);

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress InvalidScalarArgument
         */
        return new ClassMetadata(
            name: $class->getName(),
            methods: [...$this->createMethods($class)],
            invariants: [...$this->fetchInvariants($class)],
            modifiers: $modifiers,
            parent: $parent,
            interfaces: [...$this->createMany($class->getInterfaces())],
            traits: [...$this->createMany($class->getTraits())],
        );
    }

    /**
     * @param ReflectionClass $class
     * @return iterable<non-empty-string, MethodMetadata>
     * @psalm-suppress MoreSpecificReturnType
     */
    private function createMethods(ReflectionClass $class): iterable
    {
        foreach ($class->getMethods() as $method) {
            $meta = $this->createMethod($class, $method);

            if ($meta->pre !== [] || $meta->post !== []) {
                yield $method->getName() => $meta;
            }
        }
    }

    /**
     * @param ReflectionClass $context
     * @param ReflectionMethod $fun
     * @return MethodMetadata
     */
    private function createMethod(ReflectionClass $context, ReflectionMethod $fun): MethodMetadata
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress InvalidArgument
         */
        return new MethodMetadata(
            name: $fun->getName(),
            pre: [...$this->fetchAttributes($fun, Verify::class)],
            post: [...$this->fetchAttributes($fun, Ensure::class)],
            modifiers: [...$this->fetchMethodModifiers($context, $fun)],
            visibility: match (true) {
                $fun->isPrivate() => MethodVisibility::PRIVATE,
                $fun->isProtected() => MethodVisibility::PROTECTED,
                default => MethodVisibility::PUBLIC,
            }
        );
    }

    /**
     * @param ReflectionClass $context
     * @param ReflectionMethod $fun
     * @return list<MethodModifier>
     */
    private function fetchMethodModifiers(ReflectionClass $context, ReflectionMethod $fun): iterable
    {
        if ($fun->isFinal()) {
            yield MethodModifier::FINAL;
        }

        if ($fun->isAbstract()) {
            yield MethodModifier::ABSTRACT;
        }

        if ($fun->isStatic()) {
            yield MethodModifier::STATIC;
        }

        // Old ($old) statement not allowed
        if ($this->isNonCloneableClass($context)) {
            yield MethodModifier::NO_CLONE;
        }
    }

    /**
     * @param ReflectionClass $class
     * @return bool
     */
    private function isNonCloneableClass(ReflectionClass $class): bool
    {
        if (\in_array($class->getName(), self::NON_CLONEABLE_CLASSES, true)) {
            return true;
        }

        foreach($class->getInterfaces() as $interface) {
            if ($this->isNonCloneableClass($interface)) {
                return true;
            }
        }

        if ($parent = $class->getParentClass()) {
            return $this->isNonCloneableClass($parent);
        }

        return false;
    }

    /**
     * @param ReflectionClass $class
     * @return iterable<AttributeMetadata<Invariant>>
     */
    private function fetchInvariants(ReflectionClass $class): iterable
    {
        yield from $this->fetchAttributes($class, Invariant::class);

        foreach ($class->getProperties() as $property) {
            yield from $this->fetchAttributes($property, Invariant::class);
        }
    }

    /**
     * @template TAttribute of Contract
     * @see Contract
     *
     * @param AttributeAwareReflection $reflection
     * @param class-string<TAttribute> $class
     * @return iterable<AttributeMetadata<TAttribute>>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    private function fetchAttributes(object $reflection, string $class): iterable
    {
        assert(\method_exists($reflection, 'getAttributesByInstance'));

        /** @var ReflectionAttribute $attribute */
        foreach ($reflection->getAttributesByInstance($class) as $attribute) {
            /** @psalm-suppress ArgumentTypeCoercion */
            yield new AttributeMetadata(
                name: $attribute->getName(),
                arguments: $attribute->getArguments(),
            );
        }
    }
}
