<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Tests;

use JetBrains\PhpStorm\Language;
use Serafim\Contracts\Attribute\Ensure;
use Serafim\Contracts\Attribute\Invariant;
use Serafim\Contracts\Attribute\Verify;
use Serafim\Contracts\Metadata\Info\AttributeMetadata;
use Serafim\Contracts\Metadata\Info\ClassMetadata;
use Serafim\Contracts\Metadata\Info\ClassModifier;
use Serafim\Contracts\Metadata\Info\EnumMetadata;
use Serafim\Contracts\Metadata\Info\InterfaceMetadata;
use Serafim\Contracts\Metadata\Info\TraitMetadata;
use Serafim\Contracts\Metadata\ReaderInterface;
use Serafim\Contracts\Metadata\RoaveReader;
use Serafim\Contracts\Tests\Stub\AbstractClassStub;
use Serafim\Contracts\Tests\Stub\ChildClassStub;
use Serafim\Contracts\Tests\Stub\EnumStub;
use Serafim\Contracts\Tests\Stub\FinalClassStub;
use Serafim\Contracts\Tests\Stub\FirstInterfaceStub;
use Serafim\Contracts\Tests\Stub\FirstTraitStub;
use Serafim\Contracts\Tests\Stub\SecondInterfaceStub;
use Serafim\Contracts\Tests\Stub\SecondTraitStub;

class MetadataTestCase extends TestCase
{
    /**
     * @return array<non-empty-string, array{ReaderInterface}>
     */
    public function readersProvider(): array
    {
        return [
            RoaveReader::class => [new RoaveReader()],
        ];
    }

    private function attribute(string $name, array $args): AttributeMetadata
    {
        return new AttributeMetadata($name, $args);
    }

    private function invariant(#[Language('PHP')] mixed ...$arguments): AttributeMetadata
    {
        return $this->attribute(Invariant::class, $arguments);
    }

    private function verify(#[Language('PHP')] mixed ...$arguments): AttributeMetadata
    {
        return $this->attribute(Verify::class, $arguments);
    }

    private function ensure(#[Language('PHP')] mixed ...$arguments): AttributeMetadata
    {
        return $this->attribute(Ensure::class, $arguments);
    }

    /**
     * @dataProvider readersProvider
     */
    public function testAbstractClass(ReaderInterface $reader): void
    {
        $info = $reader->read(AbstractClassStub::class);

        $this->assertInstanceOf(ClassMetadata::class, $info);
        $this->assertSame(AbstractClassStub::class, $info->name);
        $this->assertCount(3, $info->methods);
        $this->assertEquals([$this->invariant('is_string("AbstractClassStub")')], $info->invariants);
        $this->assertSame([ClassModifier::ABSTRACT], $info->modifiers);
        $this->assertNull($info->parent);
        $this->assertCount(0, $info->interfaces);
        $this->assertCount(0, $info->traits);
    }

    /**
     * @dataProvider readersProvider
     */
    public function testClass(ReaderInterface $reader): void
    {
        $info = $reader->read(ChildClassStub::class);

        $this->assertInstanceOf(ClassMetadata::class, $info);
        $this->assertSame(ChildClassStub::class, $info->name);
        $this->assertCount(3, $info->methods);
        $this->assertEquals([$this->invariant('is_string("ChildClassStub")')], $info->invariants);
        $this->assertSame([], $info->modifiers);
        $this->assertInstanceOf(ClassMetadata::class, $info->parent);
        $this->assertCount(0, $info->interfaces);
        $this->assertCount(0, $info->traits);
    }

    /**
     * @dataProvider readersProvider
     */
    public function testEnum(ReaderInterface $reader): void
    {
        $info = $reader->read(EnumStub::class);

        $this->assertInstanceOf(EnumMetadata::class, $info);
        $this->assertSame(EnumStub::class, $info->name);
        $this->assertCount(3, $info->methods);
        $this->assertEquals([$this->invariant('is_string("EnumStub")')], $info->invariants);
        $this->assertCount(2, $info->interfaces);
    }

    /**
     * @dataProvider readersProvider
     */
    public function testFinalClass(ReaderInterface $reader): void
    {
        $info = $reader->read(FinalClassStub::class);

        $this->assertInstanceOf(ClassMetadata::class, $info);
        $this->assertSame(FinalClassStub::class, $info->name);
        $this->assertCount(3, $info->methods);
        $this->assertEquals([$this->invariant('is_string("FinalClassStub")')], $info->invariants);
        $this->assertSame([ClassModifier::FINAL], $info->modifiers);
        $this->assertInstanceOf(ClassMetadata::class, $info->parent);
        $this->assertCount(2, $info->interfaces);
        $this->assertCount(2, $info->traits);
    }

    /**
     * @dataProvider readersProvider
     */
    public function testInterface(ReaderInterface $reader): void
    {
        $info = $reader->read(FirstInterfaceStub::class);

        $this->assertInstanceOf(InterfaceMetadata::class, $info);
        $this->assertSame(FirstInterfaceStub::class, $info->name);
        $this->assertCount(1, $info->methods);
        $this->assertEquals([$this->invariant('is_string("FirstInterfaceStub")')], $info->invariants);
        $this->assertCount(1, $info->interfaces);

        $parent = \reset($info->interfaces);

        $this->assertInstanceOf(InterfaceMetadata::class, $parent);
        $this->assertSame(SecondInterfaceStub::class, $parent->name);
        $this->assertCount(1, $parent->methods);
        $this->assertEquals([$this->invariant('is_string("SecondInterfaceStub")')], $parent->invariants);
        $this->assertCount(0, $parent->interfaces);
    }

    /**
     * @dataProvider readersProvider
     */
    public function testTrait(ReaderInterface $reader): void
    {
        $info = $reader->read(FirstTraitStub::class);

        $this->assertInstanceOf(TraitMetadata::class, $info);
        $this->assertSame(FirstTraitStub::class, $info->name);
        $this->assertCount(3, $info->methods);
        $this->assertEquals([$this->invariant('is_string("FirstTraitStub")')], $info->invariants);
        $this->assertCount(1, $info->traits);

        $parent = \reset($info->traits);

        $this->assertInstanceOf(TraitMetadata::class, $parent);
        $this->assertSame(SecondTraitStub::class, $parent->name);
        $this->assertCount(3, $parent->methods);
        $this->assertEquals([$this->invariant('is_string("SecondTraitStub")')], $parent->invariants);
        $this->assertCount(0, $parent->traits);
    }

    /**
     * @dataProvider readersProvider
     */
    public function testIdentityMap(ReaderInterface $reader): void
    {
        /** @var ClassMetadata $root */
        $root = $reader->read(FinalClassStub::class);

        $this->assertSame($reader->read(ChildClassStub::class), $root->parent);
        $this->assertSame($reader->read(AbstractClassStub::class), $root->parent->parent);

        $this->assertSame($reader->read(FirstTraitStub::class), $root->traits[0]);
        $this->assertSame($reader->read(SecondTraitStub::class), $root->traits[1]);
        $this->assertSame($reader->read(SecondTraitStub::class), $root->traits[0]->traits[0]);

        $this->assertSame($reader->read(FirstInterfaceStub::class), $root->interfaces[0]);
        $this->assertSame($reader->read(SecondInterfaceStub::class), $root->interfaces[1]);
        $this->assertSame($reader->read(SecondInterfaceStub::class), $root->interfaces[0]->interfaces[0]);
    }
}
