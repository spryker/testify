<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Module;

class BrowserHelper extends Module
{
    /**
     * Disables native HTML5 client-side validation
     *
     * @param string $selector
     *
     * @return void
     */
    public function disableBrowserNativeValidation(string $selector): void
    {
        /** @var \Codeception\Module\WebDriver $webdriver */
        $webdriver = $this->getModule('WebDriver');
        $webdriver->executeJS(
            <<<EOF
            document.querySelectorAll('{$selector}')
                .forEach(
                    function(element) {
                        element.setAttribute('novalidate','novalidate')
                    }
                );
EOF
        );
    }
}