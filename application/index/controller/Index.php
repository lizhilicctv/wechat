<?php
namespace app\index\controller;
use think\facade\Log;
use think\facade\Env;
use think\facade\Request;
use think\Controller;

class Index extends Controller
{
    public function index()
    {
		//主要框架可以正常使用,权限问题
        include dirname(__FILE__).'/hcWeChat/hcWeChat.php';
        $hcWeChat = new \hcWeChat();
		// echo $hcWeChat->getAccessToken(); 这个是getAccessToken 比较重要
		
		//短链接转换,用于生成二维码,
		// $url = $hcWeChat->makeLink('http://www.hcoder.net/course/info_229.html');
		// if($url){
		//     echo '短连接 : '.$url;
		// }
		
		//获取用户用户信息  这里面包含了,他是从那个二维码进来的
		// $res = $hcWeChat->getUser('oARFvwCbZ_nfSZoDxKmkvSI9Ok9s');
		// if(!$res){
		//   echo $hcWeChat->error; 
		// }
		// dump($res);
	
		$hcWeChat->getMsg();
		/*  接口地址验证方法，验证完毕请注释 */
		//$hcWeChat->valid();  //这个执行一次，之后可以注释掉
		// Log::write($hcWeChat->msgType == 'event','notice');
		//不能使用switch 应该是类型原因
		if($hcWeChat->msgType == 'event'){ //事件
		 Log::write($hcWeChat,'notice');
			 if($hcWeChat->event == 'subscribe'){
				
				 $user = $hcWeChat->getUser($hcWeChat->openId);
				 $hcWeChat->reTextMsg($user['nickname'].'您好，感谢关注 ^_^');
			 }else if($hcWeChat->event == 'unsubscribe'){
				 file_put_contents('unsub.txt', $hcWeChat->openId.'取消关注');	//这个可以使用,存在了public 下面
			 }else if($hcWeChat->event == 'CLICK'){ //这个是菜单事件
				if($hcWeChat->msg->EventKey == 'KEY01'){
					 $hcWeChat->reTextMsg('您点击了第一个菜单！');
				 }
			 }
		
		}
		if($hcWeChat->msgType == 'text'){  
		    //根据文本内容回复文本消息
		    switch($hcWeChat->msgContent){
		        case 'hi':
		            $hcWeChat->reTextMsg('hi...');
		        break;
		        case '图文': //在测试号上有问题,实际没有测试
		            $msg = array(
		                array(
		                    'MUI 视频教程 - app开发',
		                    'power by hcoder.net',
		                    'http://static.hcoder.net/public/course_images/57eb25950dea9.png',
		                    'http://www.hcoder.net/course/info_211.html'
		                ),
		                array(
		                    '阿里云开放图标库使用教程',
		                    'power by hcoder.net',
		                    'http://static.hcoder.net/public/course_images/586c95c94b214.png',
		                    'http://www.hcoder.net/tutorials/info_136.html'
		                )
		            );
		            $hcWeChat->reItemMsg($msg);
		        break;
		        default :
		            $hcWeChat->reTextMsg('您说的是 : '.$hcWeChat->msgContent);
		    }
		}
		
		if($hcWeChat->msgType == 'voice'){
		    /*
		    请注意，开通语音识别后，用户每次发送语音给公众号时，微信会在推送的语音消息XML数据包中，
		    增加一个Recognition字段（注：由于客户端缓存，开发者开启或者关闭语音识别功能，对新关注者立刻生效，
		    对已关注用户需要24小时生效。开发者可以重新关注此帐号进行测试。
		    */
		   Log::write($hcWeChat->msg->Recognition,'notice'); //注意这里需要重新关注
		    $hcWeChat->reTextMsg('语音内容 : '.$hcWeChat->msg->Recognition);
		    //下载语音素材可以利用 MediaId 字段
		}
		//地理位置 "Location_X":"34.233814","Location_Y":"108.903282","Scale":"16","Label":"位置信息"
		else if($hcWeChat->msgType == 'location'){
			 Log::write($hcWeChat->msg,'notice');
		    $hcWeChat->reTextMsg('您的位置 : '.$hcWeChat->msg->Label);
		}
		
    }
	public function update(){
		include dirname(__FILE__).'/hcWeChat/hcWeChat.php'; //实现了上传下载功能,1,现在只能识别图片,2,记得开启扩展
		$hcWeChat = new \hcWeChat();
		//媒体地址可以上传后获得，本示例以一个图片为例
		//媒体文件类型，分别有图片（image）、语音（voice）、视频（video）和缩略图（thumb）默认为 image
		$mediaFile = Env::get('root_path').'public/111.jpg'; //这个是在public 里面的
		
		$mediaId    = $hcWeChat->uploadMedia($mediaFile);
		if($mediaId){
		    echo '临时素材上传成功，mediaId : '.$mediaId;
		    //下载素材演示
		    //说明 临时素材还可以来自于微信客户端的照片选择、语音录制等
		    $downLoadFile = $hcWeChat->downloadMedia($mediaId, Env::get('root_path').'public/222.jpg');
		    echo '<br />下载临时素材成功，'.$downLoadFile;
		}
	}
	public function erweima(){//生成二维码
		include dirname(__FILE__).'/hcWeChat/hcWeChat.php'; //实现了上传下载功能,1,现在只能识别图片,2,记得开启扩展
		$hcWeChat = new \hcWeChat();
		//临时二维码(仅支持数字)
		$hcWeChat->makeQrcode(array('scene_id' => 123456), Env::get('root_path').'public/1');
		echo '临时二维码(仅支持数字)<img src="/1.png" /><br />';
		//永久二维码 字符型
		$hcWeChat->makeQrcode(array('scene_str' => 'hcoder'), Env::get('root_path').'public/2', 'allTime');
		echo '永久二维码 字符型<img src="/2.png" /><br />';
		//永久二维码 数字型
		$hcWeChat->makeQrcode(array('scene_id' => 1818), Env::get('root_path').'public/3', 'allTime');
		echo '永久二维码 数字型<img src="/3.png" /><br />';
	}
	public function moban(){//生成二维码
		include dirname(__FILE__).'/hcWeChat/hcWeChat.php'; //实现了上传下载功能,1,现在只能识别图片,2,记得开启扩展
		$hcWeChat = new \hcWeChat();
		//注意下面格式,尤其注意空格
		$msg = '{ 
		    "touser":"oARFvwCbZ_nfSZoDxKmkvSI9Ok9s",
		    "template_id":"awHejDax7kvZ6pr5h8OfU47mIM50Lmf07Lq2ENcN5vc",
		    "url":"http://hcoder.net",
		    "topcolor":"#FF0000",
		    "data":{
		        "first": {
		            "value":"您的快递已经领取",
		            "color":"#173177"
		        },
		        "keyword1":{
		            "value":"2017008668",
		            "color":"#173177"
		        },
		        "keyword2": {
		            "value":"2017-07-07",
		            "color":"#173177"
		        },
		        "remark":{
		            "value":"如不是本人操作请联系客服4008888888",
		            "color":"#FF0000"
		        }
		    }
		}';
		$res = $hcWeChat->templateMsg($msg);
		echo $res;
	}
	public function setmen()
    {
		include dirname(__FILE__).'/hcWeChat/hcWeChat.php';
		$hcWeChat = new \hcWeChat();
		$menu = '
		{
		    "button":
		    [
		        {    
		            "type":"click",
		            "name":"php编程",
		            "key":"KEY01"
		        },
		        {
		            "name":"html",
		            "sub_button":
		            [
		                {    
		                   "type":"view",
		                   "name":"网易",
		                   "url":"http://www.163.com/"
		                },
		                {
		                   "type":"view",
		                   "name":"腾讯",
		                   "url":"http://www.qq.com/"
		                }
		            ]
		       },
		       {
		               "name":"更多",
		            "sub_button":
		            [
		                {
		                   "type":"view",
		                "name":"我的",
		                "url":"http://wx.hcoder.net/demo/my.php"
		                },
		                {
		                   "type":"view",
		                "name":"支付",
		                "url":"http://wx.hcoder.net/demo/pay.php"
		                }
		            ]
		       }
		    ]
		 }';
		$hcWeChat->createMenu($menu);
	}
	public function js() //下面实例是分享
	{
		include dirname(__FILE__).'/hcWeChat/hcWeChat.php';
		$hcWeChat = new \hcWeChat();
		//获取js Ticket
		$jsConfig = $hcWeChat->getJsTicket(Request::url(true)); //需要传入当前的地址
		$this->assign('jsConfig',$jsConfig);
		//dump($hcWeChat->appID);
		$this->assign('appid',$hcWeChat->appID);
		return view();
	}
  public function shouquan() //下面授权登陆
  {
  	include dirname(__FILE__).'/hcWeChat/hcWeChat.php';
  	$hcWeChat = new \hcWeChat();
  	//这里是网站可以使用session 来判断是否为登陆状态
	$backUrl = 'http://xuexi.biaotian.ltd'; //地址必须为微信设置好的
	    $hcWeChat->wxLogin($backUrl);
	

  }
}
