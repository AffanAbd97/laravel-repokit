<?php

namespace Sazl\LaravelRepokit\Utils;

use Illuminate\Support\Str;

class NameResolver
{
    /**
     * Resolve a repository class name from arbitrary input.
     *
     * Examples:
     *   "item"              → "ItemRepository"
     *   "itemrepository"    → "ItemRepository"
     *   "ItemREPOSITORY"    → "ItemRepository"
     *   "user_profile"      → "UserProfileRepository"
     */
    public static function repository(string $input, bool $isInterface = false): string
    {
        return self::resolve($input, $isInterface ? 'RepositoryInterface' : 'Repository');
    }

    /**
     * Resolve a service class name from arbitrary input.
     *
     * Examples:
     *   "item"          → "ItemService"
     *   "itemservice"   → "ItemService"
     *   "ItemSERVICE"   → "ItemService"
     *   "user_profile"  → "UserProfileService"
     */
    public static function service(string $input, bool $isInterface = false): string
    {
        return self::resolve($input, $isInterface ? 'ServiceInterface' : 'Service');
    }

    /**
     * Core resolver: PascalCase the input, strip any existing suffix (case-insensitive), append the correct one.
     */
    private static function resolve(string $input, string $suffix): string
    {
        $pascal = Str::studly($input);

        if (str_ends_with(strtolower($pascal), strtolower($suffix))) {
            $base = substr($pascal, 0, -strlen($suffix));
            return $base . $suffix;
        }

        return $pascal . $suffix;
    }
}
