<?php

namespace Diana\Rendering\Helpers;

use Ramsey\Uuid\Uuid as RamseyUuid;

class UUID
{
    /**
     * The callback that should be used to generate UUIDs.
     *
     * @var callable|null
     */
    protected static $factory;

    /**
     * Generate a UUID (version 4).
     *
     * @return RamseyUuid
     */
    public static function v4()
    {
        return static::$factory
            ? call_user_func(static::$factory)
            : RamseyUuid::uuid4();
    }

    /**
     * Set the callable that will be used to generate UUIDs.
     *
     * @param  callable|null  $factory
     * @return void
     */
    public static function createUuidsUsing(callable $factory = null)
    {
        static::$factory = $factory;
    }

    /**
     * Indicate that UUIDs should be created normally and not using a custom factory.
     *
     * @return void
     */
    public static function createUuidsNormally()
    {
        static::$factory = null;
    }
}