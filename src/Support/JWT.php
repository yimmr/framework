<?php

namespace Impack\Support;

use Exception;

class JWT
{
    protected static $algMap = ['HS256' => 'sha256'];

    /**
     * 返回指定算法的JWT头部
     *
     * @param  string  $alg
     * @return array
     */
    public static function getHeader(string $alg)
    {
        return ['alg' => $alg, 'typ' => 'JWT'];
    }

    /**
     * 使用有效载荷编码成 Token
     *
     * @param  array    $payload
     * @param  string   $secret
     * @param  string   $alg
     * @return string
     */
    public static function encode(array $payload, string $secret, string $alg = 'HS256')
    {
        $header  = static::getHeader($alg);
        $token   = [static::base64UrlEncode(json_encode($header)), static::base64UrlEncode(json_encode($payload))];
        $token[] = static::signature(implode('.', $token), $secret, $header['alg']);
        return implode('.', $token);
    }

    /**
     * 从Token解码有效载荷
     *
     * @param  string        $token
     * @param  string        $secret
     * @return false|array
     */
    public static function decode(string $token, string $secret)
    {
        $jwt = explode('.', $token);

        if (count($jwt) != 3) {
            return false;
        }

        [$header, $payload, $signature] = $jwt;

        $headers = json_decode(static::base64UrlDecode($header), true);
        if (empty($headers['alg']) || !in_array($headers['alg'], array_keys(static::$algMap))) {
            return false;
        }

        if (static::signature("$header.$payload", $secret, $headers['alg']) != $signature) {
            return false;
        }

        return json_decode(static::base64UrlDecode($payload), true);
    }

    /**
     * 生成签名
     *
     * @param  string      $data
     * @param  string      $secret
     * @param  string      $alg
     * @throws Exception
     * @return string
     */
    public static function signature(string $data, string $secret, string $alg)
    {
        if (!in_array($alg, array_keys(static::$algMap))) {
            throw new Exception("使用不支持的JWT算法: {$alg}");
        }

        return static::base64UrlEncode(hash_hmac(static::$algMap[$alg], $data, $secret, true));
    }

    /**
     * 使用 Base64URL 编码数据
     *
     * @param  string   $data
     * @return string
     */
    public static function base64UrlEncode(string $data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * 解码用 Base64URL 编码的数据
     *
     * @param  string   $data
     * @return string
     */
    public static function base64UrlDecode(string $data)
    {
        if ($remainder = strlen($data) % 4) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}