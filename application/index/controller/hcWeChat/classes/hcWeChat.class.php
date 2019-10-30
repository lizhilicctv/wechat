<?php

/*
 * hcWeChat - 核心类
 * 作者 : 深海 5213606@qq.com
 * 官网 : http://www.hcoder.net/hcwt
 */

class hcWeChat
{
    public $appID;                       //公众号 appID
    public $appsecret;                   //公众号 appsecret
    public $validToken;                  //Token 用于接口认证
    public $openId;                      //客户openid
    public $ourOpenId;                   //公众号openid
    public $msg;                         //消息对象
    public $msgType;                     //消息类型
    public $msgContent;                  //消息内容
    public $event;                       //具体事件
    private $accessTokenFile;             //access token 文件路径
    public $accessToken;                 //access token
    public $DS;                          //系统分隔符
    public $_dir;                        //类库所在目录
    public $error;                       //错误信息

    public function __construct()
    {
        $this->accessTokenFile = HCWT_CACHES . 'accessTokenFile.php';
        $this->appID = HCWT_APPID;
        $this->appsecret = HCWT_APPSECRET;
        $this->validToken = HCWT_VALIDTOKEN;
    }

    //短连接生成
    public function makeLink($link)
    {
        $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/shorturl?access_token=' . $this->accessToken;
        $data = array(
            'action' => 'long2short',
            'long_url' => $link
        );
        $res = $this->curlPost($url, json_encode($data));
        $urlData = json_decode($res, true);
        if (!empty($urlData['short_url'])) {
            return $urlData['short_url'];
        }
        return false;
    }

    //上传临时素材
    public function uploadMedia($mediaFile, $type = 'image')
    {
        $mediaFile = realpath($mediaFile);
        if (!file_exists($mediaFile)) {
            $this->jsonMsg(array('status' => 'error', 'msg' => '本地文件不存在'));
        }
		//dump(mime_content_type($mediaFile)); //这个方法被弃用了l
		$image = exif_imagetype($mediaFile);//换了一个办法但是只能使用,图片
        $miniType = image_type_to_mime_type($image);
        $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=' . $this->accessToken . '&type=' . $type;
        $data = array('media' => '@' . $mediaFile);
        if (class_exists('CurlFile')) {
            $media = new CurlFile($mediaFile);
            $media->setMimeType($miniType);
            $data = array('media' => $media);
        }
        $res = json_decode($this->curlPost($url, $data), true);
        if (empty($res) || empty($res['media_id'])) {
            return false;
        }
        return $res['media_id'];
    }

    //下载临时素材
    public function downloadMedia($mediaId, $saveFileName)
    {
        $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token=' . $this->accessToken . '&media_id=' . $mediaId;
        $res = $this->curlGet($url);
        file_put_contents($saveFileName, $res);
        return $saveFileName;
    }

    //生成二维码
    public function makeQrcode($data, $fileNmae, $expire = 2592000)
    {
        $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->accessToken;
        if ($expire == 'allTime') {
            $postData = array(
                'action_name' => 'QR_LIMIT_SCENE',
                'action_info' => array(
                    'scene' => $data
                )
            );
            if (!empty($data['scene_str'])) {
                $postData['action_name'] = 'QR_LIMIT_STR_SCENE';
            }
        } else {
            $postData = array(
                'action_name' => 'QR_SCENE',
                'expire_seconds' => $expire,
                'action_info' => array(
                    'scene' => $data
                )
            );
        }
        $res = $this->curlPost($url, json_encode($postData));
        $qrcode = json_decode($res, true);
        if (empty($qrcode['ticket'])) {
            $this->jsonMsg(array('status' => 'error', 'msg' => '二维码创建失败'));
        }
        $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . $qrcode['ticket'];
        $res = $this->curlGet($url);
        file_put_contents($fileNmae . '.png', $res);
        return $fileNmae . '.png';
    }

    //获取jsapi_ticket用于网页开发
    public function getJsTicket($url)
    {
        $jssdk = new \jssdk($this->appID, $this->appsecret, $this->getAccessToken(), HCWT_CACHES);
        return $jssdk->GetSignPackage($url);
    }

    //发送模板消息
    public function templateMsg($msg)
    {
        $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $this->accessToken;
		//dump();
        $res = $this->curlPost($url, $msg);
        if (empty($res)) {
            return false;
        }
        return $res;
    }

    //创建自定义菜单
    public function createMenu($menu)
    {
        $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->accessToken;
        $res = $this->curlPost($url, $menu);
        echo $res;
    }

    //获取自定义菜单
    public function getMenu()
    {
        $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token=' . $this->accessToken;
        $res = $this->curlGet($url);
        echo $res;
    }

    //获取用户信息
    public function getUser($openId = null)
    {
        if ($openId == null) {
            $openId = $this->openId;
        }
        $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $this->accessToken . '&openid=' . $openId . '&lang=zh_CN';
        $res = $this->curlGet($url);
        $res .= '';
        if (empty($res)) {
            return false;
        }
        $user = json_decode($res, true);
        if (!empty($user['errcode'])) {
            $this->error = $res;
            return false;
        }
        if (empty($user) || empty($user['openid'])) {
            return false;
        }
        //过滤昵称特殊字符
        if ($user['subscribe'] != 0) {
            $user['nickname'] = $this->filterName($user['nickname']);
            if (empty($user['nickname'])) {
                $user['nickname'] = '微信用户';
            }
        }
        return $user;
    }

    public function filterName($str)
    {
        $str = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '', $str);
        $str = preg_replace('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S', '?', $str);
        $str = str_replace(' ', '', $str);
        return $str;
    }

    //获取微信服务器IP
    public function getWxIp()
    {
        $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=' . $this->accessToken;
        return $this->curlGet($url);
    }

    //获取access_token
    public function getAccessToken()
    {
        $accessToken = require($this->accessTokenFile);
        if (time() > $accessToken['expires_date'] + 7100) {
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->appID . '&secret=' . $this->appsecret;
            $res = $this->curlGet($url);
            $res .= '';
            if (empty($res)) {
                $this->jsonMsg(array('status' => 'error', 'msg' => '获取 Access Token 失败'));
            }
            $arr = json_decode($res, true);
            if (!empty($arr['errcode'])) {
                $this->jsonMsg(array('status' => 'error', 'msg' => '获取Token失败 : ' . $arr['errmsg']));
            }
            $str = "<?php return array('access_token' => '" . $arr['access_token'] . "', 'expires_date' => " . time() . ");?>";
            file_put_contents($this->accessTokenFile, $str);
            $accessToken = require($this->accessTokenFile);
        }
        $this->accessToken = $accessToken['access_token'];
        return $accessToken['access_token'];
    }

    //跳转至微信登录界面
    public function wxLogin($backUrl)
    {
        $_SESSION['state'] = uniqid();
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' .
            $this->appID .
            '&redirect_uri=' . urlencode($backUrl) .
            '&response_type=code&scope=snsapi_userinfo&state=' . $_SESSION['state'] . '#wechat_redirect';
        header('location:' . $url);
        exit();
    }

    //获取用户授权Token
    public function wxLoginBack()
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->appID .
            '&secret=' . $this->appsecret . '&code=' . $_GET['code'] .
            '&grant_type=authorization_code';
        $res = $this->curlGet($url);
        $res .= '';
        $user = json_decode($res, true);
        if (empty($user) || empty($user['openid'])) {
            $this->jsonMsg(array('status' => 'error', 'msg' => '登录失败'));
        }
        $_SESSION['openid'] = $user['openid'];
        return $user['openid'];
    }

    //回复文本消息
    public function reTextMsg($msg)
    {
        $xml = '<xml><ToUserName><![CDATA[' . $this->openId . ']]></ToUserName><FromUserName><![CDATA[' . $this->ourOpenId . ']]></FromUserName><CreateTime>' . time() . '</CreateTime>
<MsgType><![CDATA[text]]></MsgType><Content><![CDATA[' . $msg . ']]></Content></xml>';
        echo $xml;
    }

    /* 回复图文消息
     * $msg格式
     * $msg = array(
     * 	array('项目标题', '描述', '图片地址', '点击项目打开的Url'),
     * 	array('项目标题', '描述', '图片地址', '点击项目打开的Url')......
     * );
     */
    public function reItemMsg($msg)
    {
        $xml = '<xml>
	    			<ToUserName><![CDATA[' . $this->openId . ']]></ToUserName>
	    			<FromUserName><![CDATA[' . $this->ourOpenId . ']]></FromUserName>
	    			<CreateTime>' . time() . '</CreateTime>
	    			<MsgType><![CDATA[news]]></MsgType>
	    			<ArticleCount>' . count($msg) . '</ArticleCount><Articles>';
        foreach ($msg as $val) {
            $xml .= '<item>
						<Title><![CDATA[' . $val[0] . ']]></Title>
						<Description><![CDATA[' . $val[1] . ']]></Description>
						<PicUrl><![CDATA[' . $val[2] . ']]></PicUrl>
						<Url><![CDATA[' . $val[3] . ']]></Url>
					</item>';
        }
        $xml .= '</Articles></xml>';
		file_put_contents('unsub.txt', $xml);
        echo $xml;
    }

    /*
     * 消息接收并解析接口
     * 注意 : php 需要配置 always_populate_raw_post_data = -1
     */
    public function getMsg()
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
        $this->msg = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        $this->openId = $this->msg->FromUserName;
        $this->ourOpenId = $this->msg->ToUserName;
        $this->msgType = $this->msg->MsgType;
        $this->msgContent = $this->msg->Content;
        $this->event = $this->msg->Event;
        if (HCWT_LOG) {
            $this->msgLog();
        }
    }

    //json 消息输出函数
    public function jsonMsg($array)
    {
        exit(json_encode($array));
    }

    //接口地址认证检查
    public function valid()
    {
        $tmpArr = array($this->validToken, $_GET["timestamp"], $_GET["nonce"]);
        sort($tmpArr, SORT_STRING);
        $tmpStr = sha1(implode($tmpArr));
        if ($tmpStr == $_GET["signature"]) {
            exit($_GET["echostr"]);
        }
        exit('接口消息验证错误');
    }

    //日志记录
    public function msgLog()
    {
        $str = '<html>
				<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				</head>
				<body>';
        $str .= "时间: " . date('Y-m-d H:i:s') . '<br />微信原始数据：' . json_encode($this->msg);
        $str .= '<br />解析后的数据：<br />';
        foreach ($this->msg as $k => $v) {
            $str .= "{$k} : {$v}<br />";
        }
        $str .= '</body></html>';
        file_put_contents('log.html', $str);
    }

    /*
     * curl GET 方式
     * 参数1 $url
     * 参数2 $data 格式 array('name'=>'test', 'age' => 18)
     */
    public function curlGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /*
     * curl POST 方式
     * 参数1 $url
     * 参数2 $data 格式 array('name'=>'test', 'age' => 18)
     */
    public function curlPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}