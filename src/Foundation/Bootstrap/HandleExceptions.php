<?php
namespace Impack\Foundation\Bootstrap;

use ErrorException;
use Exception;
use Impack\Foundation\Application;

class HandleExceptions
{
    protected $app;

    public function bootstrap(Application $app)
    {
        $this->app = $app;

        error_reporting($this->app->startedDebug() ? -1 : 0);

        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handleException']);

        register_shutdown_function([$this, 'handleShutdown']);
    }

    /** 发生异常时处理，自定义渲染或报告方式 */
    public function handleException($e)
    {
        if (!$e instanceof Exception) {
            $e = new ErrorException($e);
        }

        try {
            $this->getExceptionHandler()->report($e);
        } catch (Exception $e) {}

        $this->getExceptionHandler()->render($this->app['request'], $e)->send();
    }

    /** 致命错误转异常，非致命错误调用自定义提示 */
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            if ($this->isFatal($level)) {
                throw new ErrorException($message, 0, $level, $file, $line);
            } else {
                $this->getExceptionHandler()->notice(...\func_get_args());
            }
        }
    }

    /** 程序结束后若有致命错误则转成异常 */
    public function handleShutdown()
    {
        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            throw new ErrorException(
                $error['message'], 0, $error['type'], $error['file'], $error['line']
            );
        }
    }

    // 获取处理异常的实例
    protected function getExceptionHandler()
    {
        return $this->app->make('ExceptionHandler');
    }

    protected function isFatal($type)
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }
}