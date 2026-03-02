<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use Symfony\Component\Console\Formatter\OutputFormatter;

trait MessageFormatter
{
    /**
     * @var \Symfony\Component\Console\Formatter\OutputFormatter|null
     */
    protected $formatter;

    protected function format(string $message): string
    {
        $formatter = $this->getFormatter();

        return $formatter->format($message);
    }

    protected function getFormatter(): OutputFormatter
    {
        if ($this->formatter === null) {
            $this->formatter = new OutputFormatter(true);
        }

        return $this->formatter;
    }
}
