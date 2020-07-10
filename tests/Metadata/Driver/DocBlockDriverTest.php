<?php

namespace JMS\Serializer\Tests\Metadata\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use JMS\Serializer\Metadata\Driver\AnnotationDriver;
use JMS\Serializer\Metadata\Driver\DocBlockDriver;
use JMS\Serializer\Metadata\Driver\TypedPropertiesDriver;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionOfClassesFromDifferentNamespace;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionOfClassesFromDifferentNamespaceUsingGroupAlias;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionOfClassesFromDifferentNamespaceUsingSingleAlias;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionOfClassesFromGlobalNamespace;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionOfClassesFromSameNamespace;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionOfClassesFromTrait;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionOfClassesFromTraitInsideTrait;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionOfClassesWithFullNamespacePath;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionOfClassesWithNull;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionOfNotExistingClasses;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionOfScalars;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionOfUnionClasses;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\CollectionTypedAsGenericClass;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\Details\ProductDescription;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\Details\ProductName;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\MapTypedAsGenericClass;
use JMS\Serializer\Tests\Fixtures\DocBlockType\Collection\Product;
use JMS\Serializer\Tests\Fixtures\DocBlockType\IncorrectDocBlock;
use JMS\Serializer\Tests\Fixtures\DocBlockType\SingleClassFromDifferentNamespaceTypeHint;
use JMS\Serializer\Tests\Fixtures\DocBlockType\SingleClassFromGlobalNamespaceTypeHint;
use Metadata\ClassMetadata;
use PHPUnit\Framework\TestCase;

class DocBlockDriverTest extends TestCase
{
    private function resolve(string $classToResolve): ClassMetadata
    {
        $baseDriver = new TypedPropertiesDriver(new AnnotationDriver(new AnnotationReader(), new IdenticalPropertyNamingStrategy()));
        $driver = new DocBlockDriver($baseDriver);

        $m = $driver->loadMetadataForClass(new \ReflectionClass($classToResolve));
        self::assertNotNull($m);

        return $m;
    }

    public function testInferDocBlockCollectionOfScalars()
    {
        $m = $this->resolve(CollectionOfScalars::class);

        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => 'string', 'params' => []]]],
            $m->propertyMetadata['productIds']->type
        );
    }

    public function testInferDocBlockCollectionOfClassesFromSameNamespace()
    {
        $m = $this->resolve(CollectionOfClassesFromSameNamespace::class);

        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => Product::class, 'params' => []]]],
            $m->propertyMetadata['productIds']->type
        );
    }

    public function testInferDocBlockCollectionOfClassesFromUsingFullNamespacePath()
    {
        $m = $this->resolve(CollectionOfClassesWithFullNamespacePath::class);

        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => Product::class, 'params' => []]]],
            $m->propertyMetadata['productIds']->type
        );
    }

    public function testInferDocBlockCollectionFromGenericLikeClass()
    {
        $m = $this->resolve(CollectionTypedAsGenericClass::class);

        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => Product::class, 'params' => []]]],
            $m->propertyMetadata['productIds']->type
        );
    }

    public function testInferDocBlockMapFromGenericLikeClass()
    {
        $m = $this->resolve(MapTypedAsGenericClass::class);

        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => "int", 'params' => []], ['name' => Product::class, 'params' => []]]],
            $m->propertyMetadata['productIds']->type
        );
    }

    public function testInferDocBlockCollectionOfClassesIgnoringNullTypeHint()
    {
        $m = $this->resolve(CollectionOfClassesWithNull::class);

        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => Product::class, 'params' => []]]],
            $m->propertyMetadata['productIds']->type
        );
    }

    public function testThrowingExceptionWhenUnionTypeIsUsedForCollection()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->resolve(CollectionOfUnionClasses::class);
    }

    public function testThrowingExceptionWhenNotExistingClassWasGiven()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->resolve(CollectionOfNotExistingClasses::class);
    }

    public function testInferDocBlockCollectionOfClassesFromDifferentNamespace()
    {
        $m = $this->resolve(CollectionOfClassesFromDifferentNamespace::class);

        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => ProductDescription::class, 'params' => []]]],
            $m->propertyMetadata['productDescriptions']->type
        );
    }

    public function testInferDocBlockCollectionOfClassesFromGlobalNamespace()
    {
        $m = $this->resolve(CollectionOfClassesFromGlobalNamespace::class);

        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => \stdClass::class, 'params' => []]]],
            $m->propertyMetadata['products']->type
        );
    }

    public function testInferDocBlockCollectionOfClassesFromDifferentNamespaceUsingSingleAlias()
    {
        $m = $this->resolve(CollectionOfClassesFromDifferentNamespaceUsingSingleAlias::class);

        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => ProductDescription::class, 'params' => []]]],
            $m->propertyMetadata['productDescriptions']->type
        );
    }

    public function testInferDocBlockCollectionOfClassesFromDifferentNamespaceUsingGroupAlias()
    {
        $m = $this->resolve(CollectionOfClassesFromDifferentNamespaceUsingGroupAlias::class);

        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => ProductDescription::class, 'params' => []]]],
            $m->propertyMetadata['productDescriptions']->type
        );
        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => ProductName::class, 'params' => []]]],
            $m->propertyMetadata['productNames']->type
        );
    }

    public function testInferDocBlockCollectionOfClassesFromTraits()
    {
        $m = $this->resolve(CollectionOfClassesFromTrait::class);

        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => ProductDescription::class, 'params' => []]]],
            $m->propertyMetadata['productDescriptions']->type
        );
        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => ProductName::class, 'params' => []]]],
            $m->propertyMetadata['productNames']->type
        );
    }

    public function testInferDocBlockCollectionOfClassesFromTraitInsideTrait()
    {
        $m = $this->resolve(CollectionOfClassesFromTraitInsideTrait::class);

        self::assertEquals(
            ['name' => 'array', 'params' => [['name' => ProductDescription::class, 'params' => []]]],
            $m->propertyMetadata['productDescriptions']->type
        );
    }

    public function testInferTypeForNonCollectionFromSameNamespaceType()
    {
        $m = $this->resolve(SingleClassFromGlobalNamespaceTypeHint::class);

        self::assertEquals(
            ['name' => \stdClass::class, 'params' => []],
            $m->propertyMetadata['data']->type
        );
    }

    public function testInferTypeForNonCollectionFromDifferentNamespaceType()
    {
        $m = $this->resolve(SingleClassFromDifferentNamespaceTypeHint::class);

        self::assertEquals(
            ['name' => ProductDescription::class, 'params' => []],
            $m->propertyMetadata['data']->type
        );
    }
}