<?php
namespace Impack\Contracts\Foundation;

use Impack\Contracts\Container\Container;

interface Application extends Container
{
    /**
     * 返回应用运行目录
     *
     * @param  string  $path
     * @return string
     */
    public function path($path = '');

    /**
     * 返回配置文件目录
     *
     * @param  string  $path
     * @return string
     */
    public function configPath($path = '');

    /**
     * 返回公共资源目录
     *
     * @param  string  $path
     * @return string
     */
    public function publicPath($path = '');

    /**
     * 是否已启动引导程序
     *
     * @return bool
     */
    public function hasBootstrapped();

    /**
     * 启动全局引导程序
     *
     * @param  array  $bootstrappers
     */
    public function bootstrap(array $bootstrappers);

    /**
     * 是否在调试模式下运行
     *
     * @return bool
     */
    public function isDebugging();
}