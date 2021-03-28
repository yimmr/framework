<?php
namespace Impack\Contracts\Foundation;

use Impack\Contracts\Container\Container;

interface Application extends Container
{
    /**
     * 返回应用运行目录
     *
     * @return string
     */
    public function path(string $path = '');

    /**
     * 返回配置文件目录
     *
     * @return string
     */
    public function configPath(string $path = '');

    /**
     * 返回公共资源目录
     *
     * @return string
     */
    public function publicPath(string $path = '');

    /**
     * 是否在调试模式下运行
     *
     * @return bool
     */
    public function isDebugging();
}