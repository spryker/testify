<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\Testify\OpenApi3\Property;

use Spryker\Glue\Testify\OpenApi3\SchemaFieldInterface;

class PropertyValue implements PropertyValueInterface
{
    /**
     * @var \Spryker\Glue\Testify\OpenApi3\Property\PropertyDefinition
     */
    protected $definition;

    /**
     * @var \Spryker\Glue\Testify\OpenApi3\SchemaFieldInterface
     */
    protected $value;

    public function __construct(PropertyDefinition $definition, SchemaFieldInterface $value)
    {
        $this->definition = $definition;
        $this->value = $value;
    }

    public function getDefinition(): PropertyDefinition
    {
        return $this->definition;
    }

    public function getValue(): SchemaFieldInterface
    {
        return $this->value;
    }
}
