<?php
/**
 * Github OAuth 2.0 获取用户资料类
 */
namespace \Despote\OAuth;

class Github
{
    //////////////
    // 预定义常量 //
    //////////////
    // OAuth 授权接口域名
    const AUTH_DOMAIN = 'https://github.com/';
    // API 接口域名
    const API_DOMAIN = 'https://api.github.com/';

    //////////////
    // 开发者配置 //
    //////////////
    // 接口 APPID
    private $appid;
    // 接口密钥
    private $appSecret;
    // 回调地址
    private $callbackUrl;
    // 申请权限列表
    private $scope;
    // HTTP 请求类
    private $http;
    // 唯一标识
    private $state;

    /**
     * [__construct description]
     * @param [type] $appid       [description]
     * @param [type] $appSecret   [description]
     * @param [type] $callbackUrl [description]
     * @param [type] $scope       [description]
     */
    public function __construct($appid, $appSecret, $callbackUrl, $scope)
    {
        $this->appid       = $appid;
        $this->appSecret   = $appSecret;
        $this->callbackUrl = $callbackUrl;
        $this->scope       = $scope;
        // $this->http        = new HttpRequest;
    }

    public function getAuthUrl($callbackUrl = null, $state = null, $scope = null)
    {
        $params = [
            // APPID
            'client_id'    => $this->appid,
            // 回调地址
            'redirect_uri' => null === $callbackUrl ? $this->callbackUrl : $callbackUrl,
            // 获取的权限声明
            'scope'        => null === $scope ? $this->scope : $scope,
            // 获取 TOKEN
            'state'        => $this->getState(),
        ];

        return self::AUTH_DOMAIN . 'login/oauth/authorize' . '?' . \http_build_query($params);
    }

    /**
     * 获取 TOKEN
     * @return String 用户唯一标识
     */
    private function getState($state = null)
    {
        if (null === $state) {
            if (null === $this->state) {
                // 获取唯一标识并加密为 MD5
                $this->state = md5(\uniqid('', true));
            }
        } else {
            $this->state = $state;
        }

        return $this->state;
    }

    public function getAccessToken($code)
    {
        $data               = $this->httpGet(AUTH_DOMAIN . 'login/oauth/access_token?client_id=' . $this->appid . '&client_secret=' . $this->appSecret . '&code=' . $code);
        $access_token_info  = explode('&', $data)[0];
        $access_token       = explode('=', $access_token_info)[1];
        $this->access_token = $access_token;

        return $access_token;
    }

    public function getUserInfo()
    {
        isset($this->access_token) || $this->getAccessToken();

        return json_decode($this->httpGet(API_DOMAIN . 'user?access_token=' . $this->access_token), true);
    }

    private function httpGet($url, $data = null, $json = false)
    {
        // 初始化 curl
        $curl = curl_init();
        // 设置 curl 地址
        curl_setopt($curl, CURLOPT_URL, $url);
        // 关闭 SSL 证书校验与安全检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // 使用 Chrome User Agent
        $ua = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.86 Safari/537.36';
        // 设置 CURL User Agent
        curl_setopt($curl, CURLOPT_USERAGENT, $ua);

        // 如果需要发送数据不为空，则附加数据
        if (!empty($data)) {
            // 如果是发送 json 数据并传入数组，则自动编码为 json
            if ($json && is_array($data)) {
                $data = json_encode($data);
            }
            // 使用 POST 提交数据
            curl_setopt($curl, CURLOPT_POST, 1);
            // 添加 POST 数据
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

            // 如果使用 json 则需要设置页面头信息
            if ($json) {
                // 发送 json 数据
                curl_setopt($curl, CURLOPT_HEADER, 0);
                // 设置发送数据包头，包括格式和数据长度
                curl_setopt($curl, CURLOPT_HTTPHEADER,
                    [
                        'Content-Type: application/json; charset=utf-8',
                        'Content-Length:' . strlen($data),
                    ]
                );
            }
        }

        // 网页返回数据不输出，而是返回为字符串
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 开始 curl 抓取数据
        $res = curl_exec($curl);
        // 获取抓取过程中的错误信息，如果存在的话~
        $errorno = curl_errno($curl);

        // 错误处理，如果出错了则返回错误信息
        if ($errorno) {
            return [
                'errorno' => false,
                'errmsg' => $errorno,
            ];
        }

        // 释放 curl 对象
        curl_close($curl);

        // 返回获取到的页面数据
        return $res;
    }
}
