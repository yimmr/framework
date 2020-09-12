<?php
namespace Impack\Config;

use Impack\Contracts\Config\Loader;
use Impack\Contracts\Foundation\Application;

class FileLoader implements Loader
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function load($keyseg, &$items)
    {
        if (\file_exists($file = $this->path($keyseg[0]))) {
            $items[$keyseg[0]] = require $file;
        }
    }

    public function update($keyseg, &$items)
    {
        $filename = $keyseg[0];

        $data = $items[$filename];
        $data = is_array($data) ? var_export($data, true) : $data;
        $data = "<?php\r\nreturn {$data};";

        return (bool) \file_put_contents($this->path($filename), $data);
    }

    public function delete($keyseg, &$items)
    {
        $filename = $keyseg[0];

        // 不存在文件名的配置时，理解为删除源文件
        if (!isset($items[$filename])) {
            return @unlink($this->path($filename));
        }

        return $this->update($keyseg, $items);
    }

    /**
     * 返回配置文件路径
     *
     * @param sttring $filename
     * @return string
     */
    protected function path($filename)
    {
        return $this->app->configPath($filename . '.php');
    }
}