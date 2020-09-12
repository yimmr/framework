<?php
namespace Impack\Foundation;

use Impack\Support\Enum;

class ErrEnum extends Enum
{
    const SUCCESS = [0, '成功'];

    const UNKNOWN_ERR = [1, '未知错误'];

    const UNAVAILABLE = [2, '服务暂不可用'];

    const METHOD_NO_EXIST = [3, '未知的方法'];

    const API_REQUEST_LIMIT = [4, '接口调用次数已达到设定的上限'];

    const UNAUTHORIZED_IP = [5, '请求来自未经授权的IP地址'];

    const UNAUTHORIZED_REFER = [6, '来自该refer的请求无访问权限'];
}