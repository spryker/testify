<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\TestInterface;
use Spryker\Client\CategoryStorage\Storage\CategoryNodeStorage;
use Spryker\Client\GlossaryStorage\Storage\GlossaryStorageReader;
use Spryker\Client\ProductStorage\Storage\ProductAbstractStorageReader;
use Spryker\Client\ProductStorage\Storage\ProductConcreteStorageReader;
use Spryker\Client\Storage\StorageClient;
use Spryker\Client\Store\Reader\StoreReader;
use Spryker\Client\StoreStorage\Reader\StoreListReader;
use Spryker\Zed\CompanyRole\Persistence\CompanyRoleRepository;

/**
 * Empties the static read caches the storage clients keep, so that what a test publishes is what
 * the next request sees.
 *
 * The clients memoise storage reads in statics that outlive both the container reset between test
 * methods and any database rollback. Two consequences, and the second one bites hardest: a value
 * read in one test is still visible in the next, and — because a miss is cached just as eagerly as
 * a hit — a request made *before* the arrange step pins the empty result for the rest of the
 * process. {@see \Spryker\Client\StoreStorage\Reader\StoreListReader} is the one to remember; it
 * caches an empty store list and makes a correctly published collection look unreachable.
 *
 * Resetting happens before every test. Call {@see resetStorageCaches()} directly when a single test
 * reads, publishes and then reads again.
 *
 * Further caches can be added from the suite's `codeception.yml` without touching this class:
 *
 * ```yaml
 * - \SprykerTest\Shared\Testify\Helper\StorageCacheHelper:
 *       caches:
 *           \Some\Client\Reader: [staticPropertyName]
 * ```
 */
class StorageCacheHelper extends AbstractHelper
{
    use StaticVariablesHelper;

    protected const string CONFIG_KEY_CACHES = 'caches';

    /**
     * The read caches on the storage path, as class => static properties. `null` restores a
     * property to null rather than to an empty array — {@see StoreListReader} uses null to mean
     * "not read yet" and an array to mean "read, and this is the answer".
     *
     * Not every entry is a storage client: {@see CompanyRoleRepository} keys its cache on an md5 of
     * the query criteria, i.e. on the company user id, and the transaction rollback hands that same
     * id to the next test — which then reads the previous test's roles.
     *
     * @var array<string, array<string, array<mixed>|null>>
     */
    protected const array DEFAULT_CACHES = [
        StorageClient::class => [
            'cachedKeys' => null,
            'bufferedValues' => null,
            'bufferedDecodedValues' => null,
        ],
        StoreListReader::class => ['storeListCache' => null],
        StoreReader::class => ['storeCache' => []],
        CategoryNodeStorage::class => ['categoryNodeDataCache' => []],
        GlossaryStorageReader::class => ['translationsCache' => []],
        ProductAbstractStorageReader::class => ['productsAbstractDataCache' => []],
        ProductConcreteStorageReader::class => ['productsConcreteDataCache' => []],
        CompanyRoleRepository::class => ['companyRoleCollectionCache' => []],
    ];

    public function _before(TestInterface $test): void
    {
        $this->resetStorageCaches();
    }

    public function _after(TestInterface $test): void
    {
        $this->resetStaticCaches();
    }

    public function resetStorageCaches(): void
    {
        foreach ($this->getCaches() as $className => $properties) {
            if (!class_exists($className)) {
                continue;
            }

            foreach ($properties as $propertyName => $emptyValue) {
                $this->cleanupStaticCache($className, $propertyName, $emptyValue);
            }
        }
    }

    /**
     * @return array<string, array<string, array<mixed>|null>>
     */
    protected function getCaches(): array
    {
        /** @var array<string, array<string, array<mixed>|null>> $configuredCaches */
        $configuredCaches = $this->config[static::CONFIG_KEY_CACHES] ?? [];

        return array_merge(static::DEFAULT_CACHES, $configuredCaches);
    }
}
