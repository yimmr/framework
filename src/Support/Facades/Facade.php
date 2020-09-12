<?php
namespace Impack\Support\Facades;

use Impack\Contracts\Foundation\Application;
use RuntimeException;

abstract class Facade
{
    protected static $app;

    protected static $instances = [];

    public static function clearInstance($name = '')
    {
        if ($name) {
            unset(static::$instances[$name]);
        } else {
            static::$instances = [];
        }
    }

    public static function getApp()
    {
        return static::$app;
    }

    public static function setApp(Application $app)
    {
        static::$app = $app;
    }

    protected static function getAccessor()
    {
        throw new RuntimeException('门面类需实现静态方法：getAccessor');
    }

    /** 若是对象直接返回 */
    protected static function resolve($name)
    {
        if (is_object($name)) {
            return $name;
        }

        if (isset(static::$instances[$name])) {
            return static::$instances[$name];
        }

        if (static::$app) {
            return static::$instances[$name] = static::$app[$name];
        }
    }

    public static function __callStatic($name, $params)
    {
        $facade   = static::getAccessor();
        $instance = static::resolve($facade);

        if (!$instance) {
            throw new RuntimeException("[$facade]门面类未设置");
        }

        return $instance->$name(...$params);
    }
}