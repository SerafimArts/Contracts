<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Metadata;

use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use Psalm\Node\VirtualAttribute;
use Roave\BetterReflection\BetterReflection;
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
use Serafim\Contracts\Exception\SpecificationException;
use Serafim\Contracts\Metadata\Attribute\AttributeArgument;
use Serafim\Contracts\Metadata\Attribute\InnerAttributeArgument;
use Serafim\Contracts\Metadata\ClassLike\ClassModifier;
use Serafim\Contracts\Metadata\Method\MethodModifier;
use Serafim\Contracts\Metadata\Method\MethodVisibility;

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
     * @var non-empty-string
     */
    private const ERROR_ATTR_ARGUMENT = 'Constant expression contains invalid operations';

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
     * @var ConstExprEvaluator
     */
    private ConstExprEvaluator $eval;

    /**
     * @param Reflector|null $reflector
     */
    public function __construct(
        Reflector $reflector = null,
    ) {
        $this->reflector = $reflector ?? (new BetterReflection())->reflector();
        $this->eval = new ConstExprEvaluator();
    }

    /**
     * {@inheritDoc}
     * @psalm-suppress PossiblyInvalidArrayOffset
     * @psalm-suppress PropertyTypeCoercion
     */
    public function read(string $class): ClassLikeMetadata
    {
        return $this->metadata[\trim($class, '\\')] ??= $this->reflectClassLike(
            $this->reflector->reflectClass($class)
        );
    }

    /**
     * @param ReflectionClass $class
     * @return ClassLikeMetadata
     * @throws ConstExprEvaluationException
     */
    private function reflectClassLike(ReflectionClass $class): ClassLikeMetadata
    {
        return $this->metadata[$class->getName()] ??= match(true) {
            $class->isTrait() => $this->reflectTrait($class),
            $class->isInterface() => $this->reflectInterface($class),
            $class->isEnum() => $this->reflectEnum($class),
            default => $this->reflectClass($class),
        };
    }

    /**
     * @param ReflectionClass|null $class
     * @return ClassLikeMetadata|null
     * @throws ConstExprEvaluationException
     */
    private function reflectClassLikeOrNull(?ReflectionClass $class): ?ClassLikeMetadata
    {
        if ($class === null) {
            return null;
        }

        return $this->reflectClassLike($class);
    }

    /**
     * @param iterable<ReflectionClass> $classes
     * @return iterable<ClassLikeMetadata>
     * @throws ConstExprEvaluationException
     */
    private function reflectMany(iterable $classes): iterable
    {
        foreach ($classes as $class) {
            if ($class->isUserDefined()) {
                yield $this->reflectClassLike($class);
            }
        }
    }

    /**
     * @param ReflectionClass $class
     * @return InterfaceMetadata
     * @throws ConstExprEvaluationException
     */
    private function reflectInterface(ReflectionClass $class): InterfaceMetadata
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress PossiblyNullArgument
         * @psalm-suppress InvalidScalarArgument
         */
        return new InterfaceMetadata(
            name: $class->getName(),
            location: new Location($class->getFileName(), $class->getStartLine()),
            methods: [...$this->reflectMethods($class)],
            invariants: [...$this->reflectInvariants($class)],
            interfaces: [...$this->reflectMany($class->getInterfaces())],
        );
    }

    /**
     * @param ReflectionClass $class
     * @return TraitMetadata
     * @throws ConstExprEvaluationException
     */
    private function reflectTrait(ReflectionClass $class): TraitMetadata
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress PossiblyNullArgument
         * @psalm-suppress InvalidScalarArgument
         */
        return new TraitMetadata(
            name: $class->getName(),
            location: new Location($class->getFileName(), $class->getStartLine()),
            methods: [...$this->reflectMethods($class)],
            invariants: [...$this->reflectInvariants($class)],
            traits: [...$this->reflectMany($class->getTraits())],
        );
    }

    /**
     * @param ReflectionClass $class
     * @return EnumMetadata
     * @throws ConstExprEvaluationException
     */
    private function reflectEnum(ReflectionClass $class): EnumMetadata
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress PossiblyNullArgument
         * @psalm-suppress InvalidScalarArgument
         */
        return new EnumMetadata(
            name: $class->getName(),
            location: new Location($class->getFileName(), $class->getStartLine()),
            methods: [...$this->reflectMethods($class)],
            invariants: [...$this->reflectInvariants($class)],
            interfaces: [...$this->reflectMany($class->getInterfaces())],
        );
    }

    /**
     * @param ReflectionClass $class
     * @return ClassMetadata
     * @throws ConstExprEvaluationException
     */
    private function reflectClass(ReflectionClass $class): ClassMetadata
    {
        $modifiers = match (true) {
            $class->isFinal() => [ClassModifier::FINAL],
            $class->isAbstract() => [ClassModifier::ABSTRACT],
            default => [],
        };

        $parent = $this->reflectClassLikeOrNull($class->getParentClass());
        assert($parent === null || $parent instanceof ClassMetadata);

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress PossiblyNullArgument
         * @psalm-suppress InvalidScalarArgument
         */
        return new ClassMetadata(
            name: $class->getName(),
            location: new Location($class->getFileName(), $class->getStartLine()),
            methods: [...$this->reflectMethods($class)],
            invariants: [...$this->reflectInvariants($class)],
            modifiers: $modifiers,
            parent: $parent,
            interfaces: [...$this->reflectMany($class->getInterfaces())],
            traits: [...$this->reflectMany($class->getTraits())],
        );
    }

    /**
     * @param ReflectionClass $class
     * @return iterable<non-empty-string, MethodMetadata>
     * @psalm-suppress MoreSpecificReturnType
     * @throws ConstExprEvaluationException
     */
    private function reflectMethods(ReflectionClass $class): iterable
    {
        foreach ($class->getMethods() as $method) {
            if (!$method->isUserDefined()) {
                continue;
            }

            $meta = $this->reflectMethod($class, $method);

            if ($meta->pre !== [] || $meta->post !== []) {
                yield $method->getName() => $meta;
            }
        }
    }

    /**
     * @param ReflectionClass $context
     * @param ReflectionMethod $fun
     * @return MethodMetadata
     * @throws ConstExprEvaluationException
     */
    private function reflectMethod(ReflectionClass $context, ReflectionMethod $fun): MethodMetadata
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress InvalidArgument
         */
        return new MethodMetadata(
            name: $fun->getName(),
            location: new Location($fun->getFileName(), $fun->getStartLine()),
            pre: [...$this->reflectAttributes($context, $fun, Verify::class)],
            post: [...$this->reflectAttributes($context, $fun, Ensure::class)],
            modifiers: [...$this->fetchMethodModifiers($context, $fun)],
            visibility: match (true) {
                $fun->isPrivate() => MethodVisibility::PRIVATE,
                $fun->isProtected() => MethodVisibility::PROTECTED,
                default => MethodVisibility::PUBLIC,
            },
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
     * @throws ConstExprEvaluationException
     */
    private function reflectInvariants(ReflectionClass $class): iterable
    {
        yield from $this->reflectAttributes($class, $class, Invariant::class);

        foreach ($class->getProperties() as $property) {
            yield from $this->reflectAttributes($class, $property, Invariant::class);
        }
    }

    /**
     * @template TAttribute of Contract
     * @see Contract
     *
     * @param ReflectionClass $context
     * @param AttributeAwareReflection $reflection
     * @param class-string<TAttribute> $class
     * @return iterable<AttributeMetadata<TAttribute>>
     * @throws ConstExprEvaluationException
     *
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-suppress InvalidReturnType
     */
    private function reflectAttributes(ReflectionClass $context, object $reflection, string $class): iterable
    {
        assert(\method_exists($reflection, 'getAttributesByInstance'));

        $ast = $reflection->getAst();

        foreach ($ast->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                if (!\is_a($attribute->name->toString(), $class, true)) {
                    continue;
                }

                /** @psalm-suppress ArgumentTypeCoercion */
                yield $this->reflectAttribute($context, $attribute);
            }
        }
    }

    /**
     * @param ReflectionClass $context
     * @param Attribute $attribute
     * @return AttributeMetadata
     * @throws ConstExprEvaluationException
     */
    private function reflectAttribute(ReflectionClass $context, Attribute $attribute): AttributeMetadata
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress PossiblyNullArgument
         */
        return new AttributeMetadata(
            name: $attribute->name->toString(),
            location: new Location($context->getFileName(), $attribute->getStartLine()),
            arguments: [...$this->reflectAttributeArguments($context, $attribute)],
        );
    }

    /**
     * @param ReflectionClass $context
     * @param Attribute $attribute
     * @return iterable<AttributeArgument>
     * @throws ConstExprEvaluationException
     */
    private function reflectAttributeArguments(ReflectionClass $context, Attribute $attribute): iterable
    {
        foreach ($attribute->args as $i => $arg) {
            yield ($arg->name?->toString() ?? $i) => $this->reflectAttributeArgument($context, $arg);
        }
    }

    /**
     * @param ReflectionClass $context
     * @param Arg $arg
     * @return AttributeArgument
     * @throws ConstExprEvaluationException
     */
    private function reflectAttributeArgument(ReflectionClass $context, Arg $arg): AttributeArgument
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress PossiblyNullArgument
         */
        $location = new Location($context->getFileName(), $arg->getLine());

        $value = $arg->value;
        if ($value instanceof New_ && $value->class instanceof Name) {
            $inner = new VirtualAttribute($value->class, $value->args);
            $inner->setAttributes($value->getAttributes());

            return new InnerAttributeArgument(
                value: $this->reflectAttribute($context, $inner),
                location: $location,
            );
        }

        try {
            /** @psalm-suppress MixedAssignment */
            $literal = $this->eval->evaluateDirectly($value);
        } catch (ConstExprEvaluationException $e) {
            throw SpecificationException::create(self::ERROR_ATTR_ARGUMENT, $location->file, $location->line);
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        return new AttributeArgument(
            value: $literal,
            location: $location,
        );
    }
}
