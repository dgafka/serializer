<?php


namespace JMS\Serializer\Tests\Fixtures\TypedProperties\Collection;

use JMS\Serializer\Tests\Fixtures\TypedProperties\Collection\Details\WithProductDescriptionTrait;

trait WithTraitInsideTrait
{
    use WithProductDescriptionTrait;
}