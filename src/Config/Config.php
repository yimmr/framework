<?php
namespace Impack\Config;

use ArrayAccess;
use Impack\Contracts\Config\Loader;
use Impack\Contracts\Config\Repository;
use Impack\Contracts\Foundation\Application;
use Impack\Support\Arr;

class Config implements ArrayAccess, Repository
{
    protected $items = [];

    protected $loaders = [];

    protected $keysegs = [];

    public function __construct(Application $app)
    {
        $this->loaders['file'] = new \Impack\Config\FileLoader($app);
    }

    /**
     * 是否存在配置项。若自身缓存区没有则调用加载器的判断
     *
     * @param string $key
     * @return bool
     */
    public function has($key): bool
    {
        return (bool) Arr::has($this->items($key), $key);
    }

    /**
     * 返回配置项的值
     *
     * @param string|array $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!is_array($key)) {
            return Arr::get($this->items($key), $key, $default);
        }

        return $this->getMany($key);
    }

    /**
     * 设置配置项的值
     *
     * @param string|array $key
     * @param mixed $value
     * @param bool $sync
     * @return void
     */
    public function set($key, $value = '', $sync = true)
    {
        $this->items($key);
        Arr::set($this->items, $key, $value);

        if ($sync) {
            $this->syncToLoader($key, 'update');
        }
    }

    /**
     * 移除配置项
     *
     * @param string $key
     * @return void
     */
    public function forget($key)
    {
        $this->items($key);
        Arr::forget($this->items, $key);
        $this->syncToLoader($key, 'delete');
    }

    /**
     * 返回多组配置值
     *
     * @param  array  $keys
     * @return array
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $config[$key] = Arr::get($this->items($key), $key, $default);
        }

        return $config;
    }

    /**
     * 配置项数组值的前面加值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend($key, $value)
    {
        if (\is_array($array = $this->get($key))) {
            array_unshift($array, $value);
            $this->set($key, $array);
        }
    }

    /**
     * 配置项数组值的后面追加值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        if (\is_array($array = $this->get($key))) {
            $array[] = $value;
            $this->set($key, $array);
        }
    }

    /**
     * 返回缓存区配置项，传Key可添加配置项到缓存区
     *
     * @param string $key
     * @return array
     */
    public function items($key = '')
    {
        if (empty($key) || !\is_string($key) || Arr::has($this->items, $key)) {
            return $this->items;
        }

        $keyseg = $this->keyseg($key);

        $this->getLoader($keyseg[0])->load($keyseg, $this->items);

        return $this->items;
    }

    /**
     * 添加可加载配置项的实例，Name是操作配置的第一个键
     *
     * @param string $name
     * @param \Impack\Contracts\Config\Loader $loader
     * @return void
     */
    public function loader($name, Loader $loader)
    {
        $this->loaders[$name] = $loader;
    }

    /**
     * 返回可用加载器
     *
     * @param string $name
     * @return \Impack\Contracts\Config\Loader
     */
    protected function getLoader($name = 'file')
    {
        return $this->loaders[$name] ?? $this->loaders['file'];
    }

    /**
     * 缓存区的操作与加载器同步
     *
     * @param string $key
     * @param string $method
     * @return void
     */
    protected function syncToLoader($key, $method = 'update')
    {
        $keyseg = $this->keyseg($key);

        $this->getLoader($keyseg[0])->{$method}($keyseg, $this->items);
    }

    /**
     * 点分隔的字符拆成数组
     *
     * @param string $key
     * @return array
     */
    protected function keyseg(string $key)
    {
        return $this->keysegs[$key] ?? $this->keysegs[$key] = \explode('.', $key);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->forget($offset);
    }
}