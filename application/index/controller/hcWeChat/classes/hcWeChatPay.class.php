<?php

/*
 * 微信支付接口类
 * 作者 : 深海 5213606@qq.com
 * 官网 : http://www.hcoder.net/hcwt
 */

class hcWeChatPay extends hcWeChat
{
    public function __construct()
    {
        parent::__construct();
    }

    //异步验证接口
    public function payBack()
    {
        if (PHP_VERSION >= 5.6) {
            $data = file_get_contents("php://input");
        } else {
            $data = $GLOBALS["HTTP_RAW_POST_DATA"];
        }
        if (empty($data)) {
            $this->jsonMsg(array('status' => 'error', 'msg' => '数据为空'));
        }
        libxml_disable_entity_loader(true);
        $msg = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($msg->result_code != 'SUCCESS') {
            $this->jsonMsg(array('status' => 'error', 'msg' => '数据为空'));
        }
        //查询订单
        $res = $this->getOrder($msg->transaction_id);
        $order = $this->xmlToArray($res);
        if ($order['result_code'] != 'SUCCESS') {
            $this->jsonMsg(array('status' => 'error', 'msg' => '订单状态错误'));
        }
        if ($order['trade_state'] != 'SUCCESS') {
            $this->jsonMsg(array('status' => 'error', 'msg' => '订单状态错误'));
        }
        return $order;
    }

    //订单查询接口
    public function getOrder($transaction_id)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $array = array();
        $array['appid'] = HCWT_WXPAY_APPID;
        $array['mch_id'] = HCWT_WXPAY_MCHID;
        $array['transaction_id'] = $transaction_id;
        $array['nonce_str'] = md5(uniqid());
        $array['sign'] = $this->sign($array);
        $xml = $this->arrayToXml($array);
        $res = $this->curlPost($url, $xml);
        return $res;
    }

    //统一下单接口
    public function createOrder($order)
    {
        $order['appid'] = HCWT_WXPAY_APPID;
        $order['mch_id'] = HCWT_WXPAY_MCHID;
        $order['nonce_str'] = uniqid();
        $order['spbill_create_ip'] = hcWeChatIp::getIp();
        $order['trade_type'] = 'JSAPI';
        $order['sign'] = $this->sign($order);
        $xml = $this->arrayToXml($order);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $res = $this->curlPost($url, $xml);
        $arr = $this->xmlToArray($res);
        if ($arr['return_code'] == 'FAIL') {
            exit(json_encode(array('status' => 'error', 'msg' => $arr['return_msg'])));
        }
        if ($arr['result_code'] == 'FAIL') {
            exit(json_encode(array('status' => 'error', 'msg' => $arr['err_code_des'])));
        }
        //返回前端所需的支付数据
        $arrPay = array();
        $arrPay['appId'] = HCWT_WXPAY_APPID;
        $arrPay['timeStamp'] = time() . '';
        $arrPay['nonceStr'] = uniqid();
        $arrPay['package'] = "prepay_id=" . $arr['prepay_id'];
        $arrPay['signType'] = "MD5";
        $arrPay['paySign'] = $this->sign($arrPay);
        $arrPay['status'] = 'yes';
        exit(json_encode($arrPay));
    }

    //签名函数
    public function sign($array)
    {
        ksort($array);
        $string = '';
        foreach ($array as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $string .= $k . "=" . $v . "&";
            }
        }
        $string = trim($string, "&");
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . HCWT_WXPAY_KEY;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    //数组转xml函数
    public function arrayToXml($arr)
    {
        $xml = '<xml>';
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= '</xml>';
        return $xml;
    }

    //xml转数组函数
    public function xmlToArray($xml)
    {
        libxml_disable_entity_loader(true);
        $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $arr;
    }
}