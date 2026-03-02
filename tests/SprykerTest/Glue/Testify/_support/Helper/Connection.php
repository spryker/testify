<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\Testify\Helper;

interface Connection
{
    public function getRequestUrl(): string;

    public function getRequestMethod(): string;

    /**
     * @return object|array|string
     */
    public function getRequestParameters();

    public function getRequestFiles(): array;

    public function getResponseBody(): string;

    public function getResponseCode(): int;

    public function getResponseContentType(): string;
}
