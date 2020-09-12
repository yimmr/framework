<?php
namespace Impack\Foundation\Exceptions;

use Impack\Foundation\ErrEnum;
use Impack\Http\Response;

trait Render
{
    public function __construct($key = '')
    {
        $array = $this->errorData($key);
        parent::__construct(\strval($array[1]), \intval($array[0]));
    }

    /** 覆盖这个方法实现自定义渲染 */
    public function render($request): Response
    {
        return new Response([
            'code'    => $this->getCode(),
            'message' => $this->getMessage(),
            'data'    => [],
        ]);
    }

    /** 获取错误的相关信息 */
    protected function errorData($key)
    {
        // 若没有指定枚举对象则读取默认枚举对象的值
        if (\property_exists($this, 'enum') && \is_callable($this->enum, $key)) {
            return $this->enum::$key()->getValue();
        }

        if (ErrEnum::exist($key)) {
            return ErrEnum::$key()->getValue();
        }

        return ErrEnum::UNKNOWN_ERR;
    }
}