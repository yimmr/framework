<?php
namespace Impack\Contracts\Foundation;

use Impack\Contracts\Container\Container;

interface Application extends Container
{
    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version();

    /**
     * Get the base path of the Laravel installation.
     *
     * @param  string  $path
     * @return string
     */
    public function path($path = '');

    /**
     * Get the path to the application configuration files.
     *
     * @param  string  $path Optionally, a path to append to the config path
     * @return string
     */
    public function configPath($path = '');

    /**
     * Get the path to the resources directory.
     *
     * @param  string  $path
     * @return string
     */
    public function assetPath($path = '');

    /**
     * 是否在调试模式下运行
     *
     * @return bool
     */
    public function isDebugging();
}