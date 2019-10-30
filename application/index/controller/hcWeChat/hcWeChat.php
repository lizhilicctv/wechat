<?php
/*
 * hcWeChat - 核心入口文件
 * 作者 : 深海 5213606@qq.com
 * 版本 v 1.0.2
 * 官网 : http://www.hcoder.net/hcwt
 */
//自定义配置
define('HCWT_APPID', 'wx7145839b789af75a');        //公众号APPID
define('HCWT_APPSECRET', 'fe86f3e81f762787700e558941a794f8');        //公众号APPSECRET
define('HCWT_VALIDTOKEN', 'weixin');        //Token 用于接口认证

//微信支付需要的配置
define('HCWT_WXPAY_APPID', '');       //微信支付对应的公众号APPID
define('HCWT_WXPAY_MCHID', '');       //微信支付对应的商户ID
define('HCWT_WXPAY_KEY', '');       //微信支付对应的秘钥

//核心配置
define('HCWT_DS', DIRECTORY_SEPARATOR);         //系统分隔符
define('HCWT_DIR', dirname(__FILE__) . HCWT_DS);   //核心文件夹路径
define('HCWT_CLASSES', HCWT_DIR . 'classes' . HCWT_DS);  //类库文件夹路径
define('HCWT_CACHES', HCWT_DIR . 'caches' . HCWT_DS);   //缓存文件路径，包含 access token 及 ticket

//Mysql 数据库配置
define('HCWT_DB_HOST', 'localhost'); //数据库地址
define('HCWT_DB_USER', 'root'); //数据库账户
define('HCWT_DB_PWD', '123456'); //数据库密码
define('HCWT_DB_NAME', 'divine'); //数据库名称
define('HCWT_DB_PRE', '');  //数据表前缀
define('HCWT_DB_CHARSET', 'utf8mb4');   //字符集

//运行日志跟踪
define('HCWT_LOG', 'true'); //是否记录微信交互数据

//auto load 机制
function hcWeChatClassLoad($className)
{
    $fileName = HCWT_CLASSES . $className . '.class.php';
    if (file_exists($fileName)) {
        require $fileName;
    }
}

spl_autoload_register('hcWeChatClassLoad');

//数据模型方法
function hcm($tableName)
{
    return hcDb::getInstance($tableName);
}