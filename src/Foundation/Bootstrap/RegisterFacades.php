<?php
namespace Impack\Foundation\Bootstrap;

use Impack\Foundation\Application;
use Impack\Support\Facades\Facade;

class RegisterFacades
{
    protected static $aliases = [];

    protected static $registered = false;

    public function bootstrap(Application $app)
    {
        Facade::clearInstance();
        Facade::setApp($app);
        static::$aliases = $app->make('config')->get('app.aliases');
        static::register();
    }

    protected static function register()
    {
        if (!static::$registered) {
            spl_autoload_register([static::class, 'load'], true, true);
            static::$registered = true;
        }
    }

    protected static function load($alias)
    {
        if (isset(static::$aliases[$alias])) {
            return class_alias(static::$aliases[$alias], $alias);
        }
    }
}