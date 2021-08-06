<?php
require '../vendor/autoload.php';
ini_set("display_errors", "On");

use Phptool\Pay;

$config = [
    "merNo"      => "10011111",//商户编号
    "merAccount" => "your merAccount",//商户标识
    //私钥
    "privateKey" => "your privatekey",
    //公钥
    "publicKey"  => "your publickey",
];

try {
    $s    = new Pay($config);
//    下单
    $data = array(
        'orderId'     => 'OR_000000112311231111111111111111111111',//订单号
        'amount'      => '1',//交易金额(分)
        'product'     => '手机',//商品
        'productDesc' => 'iphone',//商品描述
        'returnUrl'   => 'https://deer.funengyun.cn/test.html',//前端页面回调地址
        'notifyUrl'   => 'https://api.deer.funengyun.cn/api/officialAccount/pay/userPaymentNotify',//后台回调地址
        'openId'      => 'openid',
    );
    $result = $s->pay($data);
    print_r($result);exit;

//    支付成功回调信息解析
    $result = $s->decryptNotifyData(function($data) {
        print_r($data);exit;
    });
    print_r($result);exit;

//    订单查询
    $order = [
        'mbOrderId' => 'xxxxxx',
        'orderId' => 'xxxxx',
    ];
    $result = $s->orderInfo($order);
    print_r($result);exit;

//    订单退款
    $refund = [
        "merchantRefundNo" => 'xxx',
        "mbOrderId" => 'xxxx',
        "orderId" => 'xxxxxx',
        "refundAmt" => '1',
        "refundCause" => '质量不行',
    ];
    $result = $s->refund($refund);
    print_r($result);exit;

} catch (Exception $e) {
    echo $e->getMessage();
}

