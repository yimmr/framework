<?php
namespace Impack\Foundation;

use Impack\Container\Container;
use Impack\Contracts\Foundation\Application as ApplicationContract;

class Application extends Container implements ApplicationContract
{
    const VERSION = '1.0';

    protected $basePath;

    protected $hasBootstrapped = false;

    protected $debug = false;

    public function __construct($basePath = null)
    {
        $this->setBasePath($basePath);
        $this->registerBaseBindings();
        $this->registerCoreAliases();
    }

    public function version()
    {
        return $this->version;
    }

    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath ?: '', '\/');
        return $this;
    }

    /** 返回运行目录下的路径 */
    public function path($base = '', $path = '')
    {
        $base = $this->basePath . ($base ? DIRECTORY_SEPARATOR . $base : $base);
        return $path ? $base . DIRECTORY_SEPARATOR . $path : $base;
    }

    /** 配置文件目录 */
    public function configPath($path = '')
    {
        return $this->path('config', $path);
    }

    /** 静态资源目录 */
    public function assetPath($path = '')
    {
        return $this->path('assets', $path);
    }

    /** 是否已启动引导程序 */
    public function hasBootstrapped()
    {
        return $this->hasBootstrapped;
    }

    /** 启动全局引导程序 */
    public function bootstrap(array $bootstrappers)
    {
        $this->hasBootstrapped = true;
        foreach ($bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap($this);
        }
    }

    /** 启动调试模式 */
    public function debug()
    {
        $this->debug = true;
    }

    /**
     * 是否已开启调试
     *
     * @return array
     */
    public function startedDebug()
    {
        return $this->debug;
    }

    /** 绑定核心实例 */
    protected function registerBaseBindings()
    {
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->singleton('config', \Impack\Config\Config::class);
    }

    /** 设置核心别名 */
    protected function registerCoreAliases()
    {

        foreach ([
            'app'        => [self::class, ApplicationContract::class, \Impack\Contracts\Container\Container::class],
            'config'     => [\Impack\Config\Config::class, \Impack\Contracts\Config\Repository::class],
            'filesystem' => [\Impack\Filesystem\Filesystem::class, \Impack\Contracts\Filesystem\Filesystem::class],
            'request'    => [\Impack\Http\Request::class, \Symfony\Component\HttpFoundation\Request::class],
        ] as $id => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($id, $alias);
            }
        }
    }
}