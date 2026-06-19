<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Command;

/**
 * Pure, side-effect-free selection of which included Codeception apps (leaf
 * codeception.yml directories) should be booted, given the new CLI filter
 * axes. Extracted from the command so it can be unit-tested without a
 * Codeception runtime.
 *
 * Correctness contract: this is a *conservative pre-filter*. Native `-g`/`-x`
 * are always passed through to Codeception and remain the authority on which
 * tests run. An app is dropped only when it provably contributes zero matching
 * tests, so the selection can never change the result set — only avoid booting
 * suites that would run nothing.
 *
 * The group axes rely on Spryker's auto-generated namespace-shaped `@group`
 * annotations mirroring the directory path 1:1 (verified: zero files tagged
 * `@group Presentation` outside a `Presentation/` dir). A group that is not a
 * path segment of any candidate app is treated as a custom group and is NOT
 * used to pre-filter (native filtering still applies inside booted apps).
 */
class AppPathSelector
{
    /**
     * @param array<string> $appPaths Relative leaf-app dir paths (e.g. src/Spryker/Cart/tests/SprykerTest/Zed/Cart).
     * @param array<string> $includeGlobs `--include` path globs (fnmatch); empty means no inclusion constraint.
     * @param array<string> $excludeGlobs `--exclude` path globs (fnmatch).
     * @param array<string> $groups `-g` groups.
     * @param array<string> $skipGroups `-x` groups.
     *
     * @return array<string>
     */
    public function select(
        array $appPaths,
        array $includeGlobs,
        array $excludeGlobs,
        array $groups,
        array $skipGroups
    ): array {
        $segments = $this->collectSegments($appPaths);

        $selected = [];
        foreach ($appPaths as $appPath) {
            if (!$this->matchesIncludeGlobs($appPath, $includeGlobs)) {
                continue;
            }
            if ($this->matchesAnyGlob($appPath, $excludeGlobs)) {
                continue;
            }
            if (!$this->passesInclusiveGroups($appPath, $groups, $segments)) {
                continue;
            }
            if ($this->failsExclusiveGroups($appPath, $skipGroups, $segments)) {
                continue;
            }
            $selected[] = $appPath;
        }

        return $selected;
    }

    /**
     * @param array<string> $includeGlobs
     */
    protected function matchesIncludeGlobs(string $appPath, array $includeGlobs): bool
    {
        if ($includeGlobs === []) {
            return true;
        }

        return $this->matchesAnyGlob($appPath, $includeGlobs);
    }

    /**
     * @param array<string> $globs
     */
    protected function matchesAnyGlob(string $appPath, array $globs): bool
    {
        foreach ($globs as $glob) {
            if (fnmatch(rtrim($glob, '/'), $appPath) || fnmatch(rtrim($glob, '/') . '/*', $appPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Inclusive (`-g`) pre-filter. Applied only when *every* requested group is
     * namespace-shaped; otherwise a custom group could match tests in any app,
     * so we keep all apps and let native `-g` decide.
     *
     * @param array<string> $groups
     * @param array<string> $segments
     */
    protected function passesInclusiveGroups(string $appPath, array $groups, array $segments): bool
    {
        if ($groups === []) {
            return true;
        }
        foreach ($groups as $group) {
            if (!in_array($group, $segments, true)) {
                return true;
            }
        }

        return array_intersect($this->pathSegments($appPath), $groups) !== [];
    }

    /**
     * Exclusive (`-x`) pre-filter. An app whose path contains a namespace-shaped
     * skip-group segment has *all* its tests in that group, so it runs nothing
     * and is dropped. Custom skip-groups are ignored here (native `-x` handles
     * them inside booted apps).
     *
     * @param array<string> $skipGroups
     * @param array<string> $segments
     */
    protected function failsExclusiveGroups(string $appPath, array $skipGroups, array $segments): bool
    {
        $namespaceShaped = array_intersect($skipGroups, $segments);
        if ($namespaceShaped === []) {
            return false;
        }

        return array_intersect($this->pathSegments($appPath), $namespaceShaped) !== [];
    }

    /**
     * @param array<string> $appPaths
     *
     * @return array<string>
     */
    protected function collectSegments(array $appPaths): array
    {
        $segments = [];
        foreach ($appPaths as $appPath) {
            foreach ($this->pathSegments($appPath) as $segment) {
                $segments[$segment] = true;
            }
        }

        return array_keys($segments);
    }

    /**
     * @return array<string>
     */
    protected function pathSegments(string $appPath): array
    {
        return array_values(array_filter(explode('/', str_replace('\\', '/', $appPath)), static fn ($s): bool => $s !== ''));
    }
}
