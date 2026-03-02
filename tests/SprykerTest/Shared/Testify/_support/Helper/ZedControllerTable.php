<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Codeception\TestInterface;

class ZedControllerTable extends Module implements DependsOnModule
{
    /**
     * @var \SprykerTest\Shared\Testify\Helper\ZedBootstrap
     */
    protected $zedBootstrap;

    /**
     * @var array
     */
    protected $currentData = [];

    public function _depends(): array
    {
        return [
            ZedBootstrap::class => 'Should be used with ZedBootstrap only',
        ];
    }

    public function _before(TestInterface $test): void
    {
        $this->currentData = [];
    }

    public function _inject(ZedBootstrap $bootstrap): void
    {
        $this->zedBootstrap = $bootstrap;
    }

    /**
     * @param string $uri
     * @param array<string, mixed> $params
     *
     * @return void
     */
    public function listDataTable(string $uri, array $params = []): void
    {
        $this->zedBootstrap->client->request('GET', $uri, $params);
        $response = $this->zedBootstrap->client->getInternalResponse();
        $this->zedBootstrap->seeResponseCodeIs(200);
        $this->currentData = json_decode($response->getContent(), true);
    }

    public function seeDataTable(): void
    {
        if (!isset($this->currentData['recordsTotal'])) {
            $this->fail('recordsTotal value not set; Run successful ->listDataTable before');
        }
    }

    public function seeNumRecordsInTable(int $num): void
    {
        if (!isset($this->currentData['recordsTotal'])) {
            $this->fail('recordsTotal value not set; Run successful ->listDataTable before');
        }
        $this->assertSame($num, $this->currentData['recordsTotal'], 'records in table');
    }

    public function seeInTable(int $row, array $expectedRow): void
    {
        if (!isset($this->currentData['data'])) {
            $this->fail('data for table not set; Run successful ->listDataTable before');
        }
        $data = $this->currentData['data'];
        if (!isset($data[$row])) {
            $this->fail("No row #$row inside in a list, current number of rows: " . count($data));
        }
        $actualRow = $data[$row];
        $this->assertSame(
            count($expectedRow),
            count(array_intersect_assoc($expectedRow, $actualRow)),
            "Row does not contain the provided data\n"
            . '- <info>' . var_export($expectedRow, true) . "</info>\n"
            . '+ ' . var_export($actualRow, true),
        );
    }

    public function clickDataTableEditButton(int $rowPosition = 0): void
    {
        $this->clickDataTableButton('Edit', $rowPosition);
    }

    public function clickDataTableViewButton(int $rowPosition = 0): void
    {
        $this->clickDataTableButton('View', $rowPosition);
    }

    public function clickDataTableDeleteButton(int $rowPosition = 0): void
    {
        $this->clickDataTableButton('Delete', $rowPosition);
    }

    public function clickDataTableButton(string $name, int $rowPosition = 0): void
    {
        if (!isset($this->currentData['data'])) {
            $this->fail('Data for table not set; Run successful ->listDataTable before');
        }

        if (count($this->currentData['data']) < $rowPosition) {
            $this->fail(sprintf(
                'Current data set has only "%d" number of entries. The requested row "%d" doesn\'t exists.',
                count($this->currentData['data']),
                $rowPosition,
            ));
        }

        $rowData = $this->currentData['data'][$rowPosition];

        $rowData = array_reverse($rowData);

        foreach ($rowData as $rowContent) {
            $xhtml = sprintf('<html>%s</html>', $rowContent);
            $xhtml = simplexml_load_string($xhtml);
            $xpath = sprintf('//a[contains(., "%1$s")] | //button[contains(., "%1$s")]', $name);

            $elements = $xhtml->xpath($xpath);
            $link = null;
            if ($elements) {
                $element = current($xhtml->xpath($xpath));
                /** @var \SimpleXMLElement $attributes */
                $attributes = (array)$element->attributes();

                $link = $attributes['@attributes']['href'];
            }

            if ($link !== null) {
                $this->zedBootstrap->amOnPage($link);
                $this->zedBootstrap->seeResponseCodeIs(200);

                return;
            }
        }

        $this->fail(sprintf('Couldn\'t find "%s" link in row "%d"', $name, $rowPosition));
    }

    public function seeInLastRow(array $expectedRow): void
    {
        if (!isset($this->currentData['data'])) {
            $this->fail('data for table not set; Run successful ->listDataTable before');
        }
        $rowNum = count($this->currentData['data']) - 1;

        $this->seeInTable($rowNum, $expectedRow);
    }

    public function seeInFirstRow(array $expectedRow): void
    {
        $this->seeInTable(0, $expectedRow);
    }

    public function dontSeeInTable(int $row, array $expectedRow): void
    {
        if (!isset($this->currentData['data'])) {
            $this->assertTrue(true);

            return;
        }
        $data = $this->currentData['data'];
        if (!isset($data[$row])) {
            $this->assertTrue(true);

            return;
        }
        $actualRow = $data[$row];
        $this->assertNotEquals(
            count($expectedRow),
            count(array_intersect_assoc($expectedRow, $actualRow)),
            "Row accidentally contains the provided data\n"
            . '- <info>' . var_export($expectedRow, true) . "</info>\n"
            . '+ ' . var_export($actualRow, true),
        );
    }

    public function dontSeeInLastRow(array $expectedRow): void
    {
        if (!isset($this->currentData['data'])) {
            $this->assertTrue(true);

            return;
        }
        $rowNum = count($this->currentData['data']) - 1;

        $this->dontSeeInTable($rowNum, $expectedRow);
    }

    public function dontSeeInFirstRow(array $expectedRow): void
    {
        $this->dontSeeInTable(0, $expectedRow);
    }
}
