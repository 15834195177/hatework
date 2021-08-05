<?php
/**
 * Created by PhpStorm.
 * User: 实体赋能技术部
 * Date: 2021/8/5
 * Time: 11:37
 */

namespace Phptool;

use Phptool\KeyWorker as KeyWorker;

class Pay
{
    private $privateKey = '';
    private $publicKey = '';
    private $merNo = '';
    private $merAccount = '';

    public function __construct($config)
    {
        $this->privateKey = $config['privateKey'];
        $this->publicKey  = $config['publicKey'];
        $this->merNo      = $config['merNo'];
        $this->merAccount = $config['merAccount'];

        $this->check_config();
    }

    private function check_config()
    {
        if (empty($this->privateKey)) {
            throw new \Exception("缺少私钥参数");
        }

        if (empty($this->publicKey)) {
            throw new \Exception("缺少公钥参数");
        }

        if (empty($this->merNo)) {
            throw new \Exception("缺少商户编号参数");
        }

        if (empty($this->merAccount)) {
            throw new \Exception("缺少商户标识参数");
        }
    }

    //支付
    public function pay($data)
    {
        $url     = "https://pay.mihuajinfu.com/paygateway/mbpay/order/v1";
        $ch      = curl_init($url);
        $timeout = 6000;
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查

        $data['merAccount'] = $this->merAccount;
        $data['merNo']      = $this->merNo;
        $data['time']       = time();
        $data['payWay']     = !empty($data['payWay']) ?: "WEIXIN";
        $data['payType']    = !empty($data['payType']) ?: "JSAPI_WEIXIN";
        $data['userIp']     = !empty($data['userIp']) ?: $_SERVER['REMOTE_ADDR'];
        $data['sign']       = $this->getSign($data);
        $encode_data        = $this->encryptData($data);

        $post_data = array(
            'merAccount' => $this->merAccount,//商户标识
            'data'       => $encode_data
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        $ret = curl_exec($ch);
        curl_close($ch);
        $retjson = json_decode($ret, true);

        if ($retjson['code'] == 000000) {
            if ($this->checkSign($retjson["data"])) {
                return $retjson;
            } else {
                return ['error' => 0, 'err_msg' => '返回数据验证签名失败'];
            }
        } else {
            return ['error' => 1, 'err_msg' => $retjson['msg']];
        }
    }

    //验签
    protected function checkSign($params)
    {
        ksort($params);
        $psign = "";
        $data  = "";

        foreach ($params as $key => $value) {
            if ($key == "sign") {
                $psign = $value;
            } else {
                $data .= trim($value);
            }
        }

        $sign = strtoupper(md5($data . $this->publicKey));

        if ($psign == $sign) {
            return true;
        } else {
            return false;
        };
    }

    protected function getSign($params)
    {
        ksort($params);
        $data = "";

        foreach ($params as $key => $value) {
            $data .= trim($value);
        }

        $sign = strtoupper(md5($data . $this->privateKey));

        return $sign;
    }

    protected function encryptData($params)
    {
        ksort($params);
        $privateWorker = new KeyWorker($this->privateKey, 1);
        $data          = $privateWorker->encrypt(json_encode($params));

        return $data;
    }
}