<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\Testify\OpenApi3\Property;

use Spryker\Glue\Testify\OpenApi3\SchemaFieldInterface;

interface PropertyValueInterface
{
    public function getDefinition(): PropertyDefinition;

    public function getValue(): SchemaFieldInterface;
}
