<?php

namespace Laravel\Horizon\Attributes;

use Attribute;

/**
 * An Attribute to mark properties of a Job to be included as tag.
 */
#[Attribute]
class Tag
{
    /**
     * @param  string  $attribute
     */
    public function __construct(public $attribute = null)
    {
        //
    }
}
