<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\Testify\Helper;

use Spryker\Shared\Config\Config;
use Spryker\Shared\Testify\TestifyConstants;

class OpenApi3 extends AbstractOpenApi3Helper
{
    /**
     * @return string
     */
    protected function getOpenApiSchemaFilePath(): string
    {
        return Config::get(TestifyConstants::GLUE_OPEN_API_SCHEMA);
    }
}
