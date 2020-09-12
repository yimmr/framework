<?php
namespace Impack\Contracts\Config;

interface Repository
{
    /**
     * 是否存在配置项
     *
     * @param string $key
     * @return bool
     */
    public function has($key): bool;

    /**
     * 返回配置项的值
     *
     * @param string|array $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * 设置配置项的值
     *
     * @param string|array $key
     * @param mixed $value
     * @param bool $sync
     * @return void
     */
    public function set($key, $value = '', $sync = true);

    /**
     * 移除配置项
     *
     * @param string $key
     * @return void
     */
    public function forget($key);
}