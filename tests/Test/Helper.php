<?php

namespace Test;

use ReflectionClass;

class Helper
{
    private static $reflection_class_cache = [];

    private static function getReflectionClass($object)
    {
        $class = get_class($object);

        if (isset(self::$reflection_class_cache[$class])) {
            return self::$reflection_class_cache[$class];
        }

        return self::$reflection_class_cache[$class] = new ReflectionClass($class);
    }

    public static function getPropertyValue($object, string $property_name)
    {
        $reflection = self::getReflectionClass($object);

        $property = $reflection->getProperty($property_name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

}
