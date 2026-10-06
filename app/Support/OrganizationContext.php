<?php

namespace App\Support;

/**
 * The organization the current request is acting inside.
 *
 * Empty by default, and empty means "no restriction": the public guest portal, webhooks, queue
 * jobs and the platform owner's admin pages all run with no organization set, exactly as they did
 * before organizations existed. Only SetOrganizationContext (the organization console) sets it,
 * and OrganizationScope narrows every organization-owned model while it is set.
 */
class OrganizationContext
{
    private static ?int $id = null;

    public static function id(): ?int
    {
        return self::$id;
    }

    public static function set(?int $id): void
    {
        self::$id = $id;
    }

    public static function clear(): void
    {
        self::$id = null;
    }

    /**
     * Run a callback inside an organization (or, with null, outside any), then put the previous
     * context back. For jobs and commands that act for one organization.
     */
    public static function run(?int $id, callable $callback): mixed
    {
        $previous = self::$id;
        self::$id = $id;

        try {
            return $callback();
        } finally {
            self::$id = $previous;
        }
    }
}
