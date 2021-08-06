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

    const PAY_URL = 'https://pay.mihuajinfu.com/paygateway/mbpay/order/v1';
    const ORDER_INFO_URL = 'https://platform.mhxxkj.com/paygateway/mbpay/order/query/v1_1';
    const REFUND_URL = 'https://platform.mhxxkj.com/paygateway/mbrefund/orderRefund/v1';
    const REFUND_QUERY_URL = 'https://platform.mhxxkj.com/paygateway/mbrefund/orderRefundQuery/v1';

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
        $data['merNo']   = $this->merNo;
        $data['payWay']  = !empty($data['payWay']) ?: "WEIXIN";
        $data['payType'] = !empty($data['payType']) ?: "JSAPI_WEIXIN";
        $data['userIp']  = !empty($data['userIp']) ?: $_SERVER['REMOTE_ADDR'];

        $retjson            = $this->request(self::PAY_URL, $data);
        $retjson['payInfo'] = json_decode($retjson['payInfo'], true);

        return $retjson;
    }

    /**
     * 获取订单信息
     * @param $order
     * @return mixed
     * @throws \Exception
     */
    public function orderInfo($order)
    {
        return $this->request(self::ORDER_INFO_URL, $order);
    }

    /**
     * 订单退款
     * @param $order
     * @return mixed
     * @throws \Exception
     */
    public function refund($order)
    {
        return $this->request(self::REFUND_URL, $order);
    }

    /**
     * 退款状态查询
     * @param $url
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function refundQuery($refund_order)
    {
        return $this->request(self::REFUND_QUERY_URL, $refund_order);
    }

    /**
     * 解析支付回调数据
     * @param $function
     * @return bool
     * @throws \Exception
     */
    public function decryptNotifyData($function)
    {
        $data    = $_GET['data'];
        $data    = $this->decryptData($data, $this->publicKey);
        $retjson = json_decode($data, true);

        if (!$this->checkSign($retjson)) {
            throw  new \Exception('返回数据验证签名失败');
        }

        $function($retjson);

        return true;
    }


    /**
     * 解析退款回调数据
     * @param $function
     * @return bool
     * @throws \Exception
     */
    public function decryptRefundData($function)
    {
        $data    = $_GET['data'];
        $data    = $this->decryptData($data, $this->publicKey);
        $retjson = json_decode($data, true);

        if (!$this->checkSign($retjson)) {
            throw  new \Exception('返回数据验证签名失败');
        }

        $function($retjson);

        return true;
    }

    private function request($url, $data)
    {
        $data['merAccount'] = $this->merAccount;
        $data['time']       = time();
        $data['sign']       = $this->getSign($data);

        $ch      = curl_init($url);
        $timeout = 6000;
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查

        $encode_data = $this->encryptData($data);
        $post_data   = [
            'merAccount' => $this->merAccount,
            'data'       => $encode_data
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);

        if ($result['code'] == "000000") {
            if ($this->checkSign($result["data"])) {
                return $result['data'];
            }

            throw  new \Exception('返回数据验证签名失败');
        }

        throw new \Exception($result['msg']);

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

    protected function decryptData($params)
    {
        $publicWorker = new KeyWorker($this->publicKey, 1);
        $data         = $publicWorker->decrypt($params);
        return $data;
    }
}