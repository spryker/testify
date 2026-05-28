<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\Testify\OpenApi3\Primitive;

class TypePrimitive extends AbstractPrimitive
{
    /**
     * OpenAPI 3.1 / JSON Schema allows `type` to be either a single type string
     * or a list of type strings (e.g. `['string', 'null']` for nullable fields).
     *
     * @param mixed $value
     *
     * @return array<string>|string
     */
    protected function cast($value)
    {
        if (is_array($value)) {
            return array_map('strval', $value);
        }

        return (string)$value;
    }
}
