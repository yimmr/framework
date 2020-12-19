<?php
namespace Impack\Contracts\Config;

interface Repository
{
    /**
     * 是否存在配置项
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key);

    /**
     * 返回指定配置值
     *
     * @param  string|array  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * 设置配置项
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @param  bool  $sync
     */
    public function set($key, $value = '', $sync = true);

    /**
     * 移除配置项
     *
     * @param  string  $key
     */
    public function forget($key);
}