<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.thinkphp.cn>
// +----------------------------------------------------------------------

/**
 * 前台配置文件
 * 所有除开系统级别的前台配置
 */

return array(

    // 预先加载的标签库
    'TAGLIB_PRE_LOAD' => 'OT\\TagLib\\Article,OT\\TagLib\\Think',

    /* 主题设置 */
    'DEFAULT_THEME' => 'default', // 默认模板主题名称

    /* SESSION 和 COOKIE 配置 */
    'COOKIE_PREFIX' => 'onethink_home_', // Cookie前缀 避免冲突

    /* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/static',
    	'__FONTS__' => __ROOT__ . '/Application/' .MODULE_NAME. '/Static/fonts',
        '__IMG__' => __ROOT__ . '/Application/' .MODULE_NAME. '/Static/image',
        '__CSS__' => __ROOT__ . '/Application/' .MODULE_NAME . '/Static/css',
        '__JS__' =>__ROOT__ . '/Application/' .MODULE_NAME. '/Static/js',
        '__PUBLIC__' => __ROOT__ . '/Public',
        '__CORE_IMAGE__'=>__ROOT__.'/Application/Core/Static/images',
        '__CORE_CSS__'=>__ROOT__.'/Application/Core/Static/css',
        '__CORE_JS__'=>__ROOT__.'/Application/Core/Static/js',
        '__APPLICATION__'=>__ROOT__.'/Application/'
    ),
    'LANG_SWITCH_ON' => true,
    'LANG_AUTO_DETECT' => false, // 自动侦测语言 开启多语言功能后有效
    'LANG_LIST'        => 'zh-cn,en', // 允许切换的语言列表 用逗号分隔
    'VAR_LANGUAGE'     => 'l', // 默认语言切换变量
    'DEFAULT_LANG'=>'zh-cn',
	'URL_CASE_INSENSITIVE'=>false,

    'NEED_VERIFY' => true,//此处控制默认是否需要审核，该配置项为了便于部署起见，暂时通过在此修改来设定。
    
    //支付宝配置参数
    'alipay_config'=>array(
    		'partner' =>'2088221923135431',   //这里是你在成功申请支付宝接口后获取到的PID；
    		'key'=>'63s6zk5u0wkolyukfddatx3keknno0no',//这里是你在成功申请支付宝接口后获取到的Key
    		'sign_type'=>strtoupper('MD5'),
    		'input_charset'=> strtolower('utf-8'),
    		'cacert'=> getcwd().'\Application\Vip\Conf\\cacert.pem',
    		'transport'=> 'http',
    		'payment_type'=>'1',
    		'service'=>'create_direct_pay_by_user',
    	//	$_SERVER['SERVER_NAME'].
			'notify_url'=>'http://dress.irosn.com.cn/notify.php',
//     		//这里是页面跳转通知url，提交到项目的Pay控制器的returnurl方法；
     		'return_url'=>'http://dress.irosn.com.cn/index.php?s=/Vip/Alipay/returnurl',
//    		'notify_url'=>'http://jkh.jkhotel.com/notify.php',
    		//这里是页面跳转通知url，提交到项目的Pay控制器的returnurl方法；
//    		'return_url'=>'http://jkh.jkhotel.com/index.php?s=/Vip/Alipay/returnurl',
    		
    		'seller_email'=>'jkjdgl@qq.com',
    		'successpage'=>'Vip/JKAppPcPage/warelist',
    		//支付失败跳转到的页面
    		'errorpage'=>'Vip/JKAppPcPage/warelist'
    ),
    
    /*微信支付配置*/
    define('WEB_HOST', 'http://dress.irosn.com.cn'),
    /*微信支付配置*/
    'WxPayConf_pub'=>array(
        'APPID' => 'wx6a4b27cb500ed308',
        'MCHID' => '1239271502',
        'KEY' => 'qwertyuiopasdfghjklzxcvbnm098765',
        'APPSECRET' => '6e8e2a3e188620bfee1d245c0aec38eb',
        'JS_API_CALL_URL' => urlencode('http://dress.irosn.com.cn/index.php?s=/Vip/JKAppWxPay/jsApiCall'),
        'SSLCERT_PATH' => getcwd().'\Application\Vip\Conf\apiclient_cert.pem',
        'SSLKEY_PATH' => getcwd().'\Application\Vip\Conf\apiclient_key.pem',
        'NOTIFY_URL' => 'http://dress.irosn.com.cn/notice.php',
        'CURL_TIMEOUT' => 30
    )
// 	define('WEB_HOST', 'http://jkh.jkhotel.com'),
//     'WxPayConf_pub'=>array(
//     		'APPID' => 'wxf5bd9fe1a5d41f69',
//     		'MCHID' => '1358652202',
//     		'KEY' => 'wFKNyStkV1KeOGYbNcH9X8yygt5MJgSQ',
//     		'APPSECRET' => '1ff7dab7c79a247a0ea9204358a0dd59',
//     		'JS_API_CALL_URL' => urlencode('http://jkh.jkhotel.com/index.php?s=/Vip/JKAppWxPay/jsApiCall'),
//     		'SSLCERT_PATH' => getcwd().'\Application\Vip\Conf\apiclient_cert.pem',
//     		'SSLKEY_PATH' => getcwd().'\Application\Vip\Conf\apiclient_key.pem',
//     		'NOTIFY_URL' => 'http://jkh.jkhotel.com/notice.php',
//     		'CURL_TIMEOUT' => 30
//     )  
);

