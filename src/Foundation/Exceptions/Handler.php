<?php
namespace Impack\Foundation\Exceptions;

use Exception;
use Impack\Foundation\Application;
use Impack\Http\Response;
use Impack\Support\Str;

class Handler
{
    const APP_FATAL_ERR = ['-1', '程序内部发生异常 :('];

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /** 报告异常 */
    public function report(Exception $e)
    {
        if (\method_exists($e, 'report')) {
            \call_user_func([$e, 'report']);
        }
    }

    /** 渲染异常 */
    public function render($request, Exception $e)
    {
        // 若有render方法将从该方法返回响应对象
        if (\method_exists($e, 'render')) {
            return \call_user_func([$e, 'render'], $request);
        }

        // 若是异步请求错误将以JSON格式返回
        if ($request->ajax()) {
            $data = $this->apiErrFormat($this->app->startedDebug() ? $e : null);
        } else {
            $data = $this->app->startedDebug() ? $this->renderBug($e) : $this->userErrMsg($e);
        }

        return new Response($data);
    }

    /** 发生致命错误时用户可见的内容 */
    public function userErrMsg(Exception $e)
    {
        return static::APP_FATAL_ERR[1];
    }

    /** 返回bug相关信息 */
    public function renderBug(Exception $e)
    {
        if (\function_exists('dump')) {
            \ob_start();
            \call_user_func('dump', $e);
            return \ob_get_clean();
        }
        return $this->errorToHtml($e);
    }

    /** 输出提示性错误 */
    public function notice($level, $message, $file = '', $line = 0, $context = [])
    {
        \printf('<p style="font-weight:bold">%s < %s > %s %s : %s</p>',
            Str::__('发生错误'), $message, Str::__('文件'), $file, $line);
    }

    /** api响应格式 */
    protected function apiErrFormat(Exception $e = null)
    {
        return [
            'code'    => ($e ? $e->getCode() : static::APP_FATAL_ERR[0]),
            'message' => ($e ? $e->getMessage() : static::APP_FATAL_ERR[1]),
            'data'    => [],
        ];
    }

    public function errorToHtml(Exception $e)
    {
        $html = '<h3>' . Str::before($e->getMessage(), 'Stack trace:') . '</h3>';
        $html .= '<p>' . $e->getFile() . ' : ' . $e->getLine() . '</p>';
        return $html . $this->traceToHtml($e->getTrace());
    }

    public function traceToHtml(array $trace)
    {
        $head = '<tr><th>Trace</th><th>Func</th><th>File</th><th>Args</th><th>Line</th></tr>';
        $tr   = '';
        foreach ($trace as $array) {
            foreach ($array['args'] as $error) {
                foreach ($error->getTrace() as $index => $file) {
                    $tr .= \sprintf(
                        '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                        $index,
                        $file['function'] ?? '',
                        $file['file'] ?? '',
                        \json_encode($file['args'] ?? [], JSON_PRETTY_PRINT),
                        $file['line'] ?? '',
                    );
                }
            }
        }
        return '<table border="1" style="border-collapse: collapse">' . $head . $tr . '</table>';
    }
}