<?php

namespace Impack\Support;

class Func
{
    /**
     * 返回类名或对象类名的名称部分
     *
     * @param  string|object  $class
     * @return string
     */
    public static function classBaseName($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}