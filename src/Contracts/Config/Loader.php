<?php
namespace Impack\Contracts\Config;

interface Loader
{
    /**
     * 加载数据到配置缓存区
     *
     * @param array $keyseg
     * @param array $items
     * @return void
     */
    public function load($keyseg, &$items);

    /**
     * 更新配置项的值
     *
     * @param array $keyseg
     * @param array $items
     * @return void
     */
    public function update($keyseg, &$items);

    /**
     * 移除配置项
     *
     * @param array $keyseg
     * @param array $items
     * @return void
     */
    public function delete($keyseg, &$items);
}