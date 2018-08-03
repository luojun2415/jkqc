<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

// OneThink常量定义
use Admin\Model\AuthRuleModel;
const ONETHINK_VERSION = '1.0.131218';
const ONETHINK_ADDON_PATH = './Addons/';


require_once(APP_PATH . '/Common/Common/pagination.php');
require_once(APP_PATH . '/Common/Common/query_user.php');
require_once(APP_PATH . '/Common/Common/thumb.php');
require_once(APP_PATH . '/Common/Common/api.php');
require_once(APP_PATH . '/Common/Common/time.php');
require_once(APP_PATH . '/Common/Common/match.php');
require_once(APP_PATH . '/Common/Common/seo.php');
require_once(APP_PATH . '/Common/Common/type.php');
require_once(APP_PATH . '/Common/Common/cache.php');
require_once(APP_PATH . '/Common/Common/vendors.php');
require_once(APP_PATH . '/Common/Common/parse.php');
require_once(APP_PATH . '/Common/Common/user.php');
require_once(APP_PATH . '/Common/Common/limit.php');
require_once(APP_PATH . '/Common/Common/role.php');
require_once(APP_PATH . '/Common/Common/ext_parse.php');
require_once(APP_PATH . '/Common/Common/collect.php');
/*require_once(APP_PATH . '/Common/Common/extend.php');*/


/**
 * 系统公共库文件
 * 主要定义系统公共函数库
 */

/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function is_login()
{

    if(is_api_login()){
        return is_api_login();
    }

    $user = session('user_auth');
    if (empty($user)) {
        return  0;
    } else {
        return session('user_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
    }
}

function is_api_login()
{
    if(function_exists('I_POST')){
        $user =R('Api/Base/isLogin');
        if (empty($user)) {
            return 0;
        } else {
            return  $user;
        }
    }else{
        return 0;
    }
}


/**
 * 构造用户配置表 D('UserConfig')查询条件
 * @param string $name 表中name字段的值(配置标识)
 * @param string $model 表中model字段的值(模块标识)
 * @param int $uid 用户uid
 * @param int $role_id 登录的角色id
 * @return array 查询条件 $map
 * @author 郑钟良<zzl@ourstu.com>
 */
function getUserConfigMap($name = '', $model = '', $uid = 0, $role_id = 0)
{
    $uid = $uid ? $uid : is_login();
    $role_id = $role_id ? $role_id : get_role_id($uid);
    $map = array();
    //构造查询条件
    $map['uid'] = $uid;
    $map['name'] = $name;
    if ($role_id != -1) {
        $map['role_id'] = $role_id;
    }
    $map['model'] = $model;
    return $map;
}

//生成token
function get_token($randLength=6,$attatime=1,$includenumber=0){
    if ($includenumber){
        $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNPQEST123456789';
    }else {
        $chars='abcdefghijklmnopqrstuvwxyz';
    }
    $len=strlen($chars);
    $randStr='';
    for ($i=0;$i<$randLength;$i++){
        $randStr.=$chars[rand(0,$len-1)];
    }
    $tokenvalue=$randStr;
    if ($attatime){
        $tokenvalue=$randStr.time();
    }
    return $tokenvalue;
}


function get_uid()
{
    return is_login();
}

/**
 * 检测权限
 */
function CheckPermission($uids)
{
    if (is_administrator()) {
        return true;
    }
    if (in_array(is_login(), $uids)) {
        return true;
    }
    return false;
}

function check_auth($rule = '', $except_uid = -1, $type = AuthRuleModel::RULE_URL)
{
    if (is_administrator()) {
        return true;//管理员允许访问任何页面
    }
    if ($except_uid != -1) {
        if (!is_array($except_uid)) {
            $except_uid = explode(',', $except_uid);
        }
        if (in_array(is_login(), $except_uid)) {
            return true;
        }
    }
    $rule = empty($rule) ? MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME : $rule;
    // 检测是否有该权限
    if (!M('auth_rule')->where(array('name' => $rule, 'status' => 1))->find()) {
        return false;
    }
    static $Auth = null;
    if (!$Auth) {
        $Auth = new \Think\Auth();
    }
    if (!$Auth->check($rule, get_uid(), $type)) {
        return false;
    }
    return true;

}


/**
 * 检测当前用户是否为管理员
 * @return boolean true-管理员，false-非管理员
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function is_administrator($uid = null)
{
    $uid = is_null($uid) ? is_login() : $uid;
    $admin_uids = explode(',', C('USER_ADMINISTRATOR'));//调整验证机制，支持多管理员，用,分隔
    //dump($admin_uids);exit;
    return $uid && (in_array(intval($uid), $admin_uids));//调整验证机制，支持多管理员，用,分隔
}

function get_administrator()
{
    $admin_uids = explode(',', C('USER_ADMINISTRATOR')); //调整验证机制，支持多管理员，用,分隔
    return $admin_uids;
}

/**获得具有某个权限节点的全部用户UID数组
 * @param string $rule
 */
function get_auth_user($rule = '')
{
    $rule = D('AuthRule')->where(array('name' => $rule))->find();
    $groups = D('AuthGroup')->select();
    $uids = array();
    foreach ($groups as $v) {
        $auth_rule = explode(',', $v['rules']);
        if (in_array($rule['id'], $auth_rule)) {
            $gid = $v['id'];
            $temp_uids = (array)D('AuthGroupAccess')->where(array('group_id' => $gid))->getField('uid');
            if ($temp_uids !== null) {
                $uids = array_merge($uids, $temp_uids);
            }
        }
    }
    $uids = array_merge($uids, get_administrator());
    $uids = array_unique($uids);
    return $uids;
}

/**
 * 字符串转换为数组，主要用于把分隔符调整到第二个参数
 * @param  string $str 要分割的字符串
 * @param  string $glue 分割符
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function str2arr($str, $glue = ',')
{
    return explode($glue, $str);
}

/**
 * 数组转换为字符串，主要用于把分隔符调整到第二个参数
 * @param  array $arr 要连接的数组
 * @param  string $glue 分割符
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function arr2str($arr, $glue = ',')
{
    return implode($glue, $arr);
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key 加密密钥
 * @param int $expire 过期时间 单位 秒
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_encrypt($data, $key = '', $expire = 0)
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time() : 0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
    }
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}

/**
 * 系统解密方法
 * @param  string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param  string $key 加密密钥
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_decrypt($data, $key = '')
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data = substr($data, 10);

    if ($expire > 0 && $expire < time()) {
        return '';
    }
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

/**
 * 数据签名认证
 * @param  array $data 被认证的数据
 * @return string       签名
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function data_auth_sign($data)
{
    //数据类型检测
    if (!is_array($data)) {
        $data = (array)$data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}

/**
 * 对查询结果集进行排序
 * @access public
 * @param array $list 查询结果
 * @param string $field 排序的字段名
 * @param array $sortby 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
 * @return array
 */
function list_sort_by($list, $field, $sortby = 'asc')
{
    if (is_array($list)) {
        $refer = $resultSet = array();
        foreach ($list as $i => $data)
            $refer[$i] = &$data[$field];
        switch ($sortby) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc': // 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ($refer as $key => $val)
            $resultSet[] = &$list[$key];
        return $resultSet;
    }
    return false;
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function list_to_treewx($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
{


    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[$key] =& $list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][$key] =& $list[$key];
                }
            }
        }
    }

    return $tree;
}


/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
{


    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }

    return $tree;
}

/**
 * 将list_to_tree的树还原成列表
 * @param  array $tree 原来的树
 * @param  string $child 孩子节点的键
 * @param  string $order 排序显示的键，一般是主键 升序排列
 * @param  array $list 过渡用的中间数组，
 * @return array        返回排过序的列表数组
 * @author yangweijie <yangweijiester@gmail.com>
 */
function tree_to_list($tree, $child = '_child', $order = 'id', &$list = array())
{
    if (is_array($tree)) {
        $refer = array();
        foreach ($tree as $key => $value) {
            $reffer = $value;
            if (isset($reffer[$child])) {
                unset($reffer[$child]);
                tree_to_list($value[$child], $child, $order, $list);
            }
            $list[] = $reffer;
        }
        $list = list_sort_by($list, $order, $sortby = 'asc');
    }
    return $list;
}

/**
 * 将list_to_tree的树还原成列表
 * @param  array $tree 原来的树
 * @param  string $child 孩子节点的键
 * @param  array $list 过渡用的中间数组，
 * @return array        返回排过序的列表数组
 * @author luoj
 */
function tree_to_listwx($tree, $child = '_child',$pk = 'id',  &$list = array())
{
    if (is_array($tree)) {
        $refer = array();
        foreach ($tree as $key => $value) {
            $reffer = $value;
            if (isset($reffer[$child])) {
                unset($reffer[$child]);
                tree_to_listwx($value[$child], $child,$pk, $list);
            }
            $list[$reffer[$pk]] = $reffer;
        }
    }
    return $list;
}

/**
 * 格式化字节大小
 * @param  number $size 字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function format_bytes($size, $delimiter = '')
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 设置跳转页面URL
 * 使用函数再次封装，方便以后选择不同的存储方式（目前使用cookie存储）
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function set_redirect_url($url)
{
    cookie('redirect_url', $url);
}

/**
 * 获取跳转页面URL
 * @return string 跳转页URL
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_redirect_url()
{
    $url = cookie('redirect_url');
    return empty($url) ? __APP__ : $url;
}

/**
 * 处理插件钩子
 * @param string $hook 钩子名称
 * @param mixed $params 传入参数
 * @return void
 */
function hook($hook, $params = array())
{
    \Think\Hook::listen($hook, $params);
}

/**
 * 获取插件类的类名
 * @param strng $name 插件名
 */
function get_addon_class($name)
{
    $class = "Addons\\{$name}\\{$name}Addon";
    return $class;
}

/**
 * 获取插件类的配置文件数组
 * @param string $name 插件名
 */
function get_addon_config($name)
{
    $class = get_addon_class($name);
    if (class_exists($class)) {
        $addon = new $class();
        return $addon->getConfig();
    } else {
        return array();
    }
}

/**
* 函数用途描述：获取用户权限分类
* @date: 2016年11月1日 上午11:08:40
* @author: luojun
* @param: 
* @return:
*/
function get_my_auth($uid) {
    if($uid){
        $myGroupId=M("auth_group_access")->where("uid=$uid")->getField('group_id');
        $myAccess=M("auth_group")->where("id=$myGroupId")->getField('cate');
        if($myAccess>10){
            $myAccess=M("auth_group")->where("id=$myAccess")->getField('cate');
        }
        return $myAccess;
    }
}

/**
*函数用途描述：获取用户组所有用户
*@date：2016年11月21日 下午4:50:57
*@author：luoj
*@param：
*@return：
**/
function get_groups_users($gid){
    $map = array('status' => array('gt', 0));
    $glist = M('AuthGroup')->field('id,title,pid')->where($map)->select();
     
    $glist = list_to_tree($glist, 'id', 'pid', '_', $gid);
    $glist = tree_to_list($glist,'_');
    $ids=array();
    $ids[]=$gid;
    foreach ($glist as $v){
        $ids[]=$v['id'];
    }
     
    $where['group_id']=array('in', $ids);
    $uids=M('auth_group_access')->where($where)->field('uid')->select();
  
    $cod=$ids=array();
    foreach ($uids as $v){
        $ids[]=$v['uid'];
    }
    $cod['uid']=array('in', $ids);
    $cod['status']    =   array('egt', 0);
     
     
    $aUsersList = M('Member')->where($cod)->select();
    return $aUsersList;
}
/**
 *函数用途描述：获取所有用户
 *@date：2017年07月04日 下午1:54:57
 *@author：tanjiewen
 *@param：
 *@return：
 **/
function get_all_groups_users(){
	$cod['status']    =   array('egt', 0);
	$aUsersList = M('Member')->where($cod)->field("uid,username")->select();
	return $aUsersList;
}
/**
* 函数用途描述： 获取用户可访问项目
* @date: 2016年11月1日 上午11:08:08
* @author: luojun
* @param: 
* @return:
*/
function get_my_projects($gid=0){
    if(!$gid){
         //$myGroupId = M("auth_group_access")->where("uid=".is_login())->getField('group_id');
         $myGroupIds = M("auth_group_access")->where("uid=".UID)->field('group_id')->select();
    }
    else{
        $myGroupIds[]=array('group_id'=>$gid);
    }
    $map = array(
        'status' => array(
            'gt',
            0
        )
    );
    $prolist = M('AuthGroup')->field('id,title,pid')
    ->where($map)
    ->select();
    $myProjects=array();
    foreach ($myGroupIds as $v){
        
        if (is_administrator(UID)) {
            $initPid = 0;
        } 
        else{
            $con  = array('status' => array('gt', 0),'id'=>$v['group_id']);
            
            $glist=M('AuthGroup')->field('id,title,pid,cate')->where($con)->find();
          
            //如果该用户组下没有项目
            $has_pro = M('jk_project')->where("pid=".$glist['id'])->count();
            //echo $has_pro;
            if($glist['cate']!=1 || $has_pro==0){
            	
				
				$glist=getCatePath('AuthGroup',$glist['id'],1);
				//查看是否为第五级

				//echo get_group_level($glist['pid'])."--";
                $level=get_group_level($glist['pid']);
				if($level==4){
					$glist=getCatePath('AuthGroup',$glist['pid'],1);
				}
                file_put_contents('appRequst.log', $level.json_encode($glist)."\n", FILE_APPEND);
               
            }
			
            $initPid = $glist['id'];
        }    
        
        $_SESSION['groId']=$initPid;	
        $list = list_to_tree($prolist, 'id', 'pid', '_', $initPid);
     
        $list = tree_to_listwx($list, '_');
        // dump($initPid);
       
        // $ids=array('id'=>$initPid);
        $id=array();
    	$ids[]=$initPid;
        foreach ($list as $var){
            $ids[]=$var['id'];
        }
    	
        $_SESSION['groId']=$ids[0];
        //默认项目
        $map=array();
        $map['status']    =   array('gt', 0);
        if(!is_administrator(UID)||$gid){
            $map['pid']    =   array('in', $ids);
        }
        $projects=M("jk_project")->where($map)->order('id DESC')->field('id')->select();
//         file_put_contents('sql.txt','upsql:'.M("jk_project")->_sql()."\r\n", FILE_APPEND);
    	foreach($projects as $value){
    		$myProjects[]=$value['id'];
    	}
    	
    }
    if($myProjects && !$_SESSION['proId']){
        $_SESSION['proId']=$myProjects[0];
    }
    
    return $myProjects;
}
/**
 * 函数用途描述： 获取用户所在层级
 * @date: 2017年11月2日 
 * @author: tanjiewen
 * @param:
 * @return:
 */
function get_group_level($pid){
	$level = 0;
	while($pid!=0){
		$pid = M('auth_group')->where("id=".$pid)->getField('pid');
		$level++;
		if(!$pid){
			return;
		}
	
	}
	return $level;
}
/**
 * 函数用途描述： 获取用户可访问项目
 * @date: 2016年11月1日 上午11:08:08
 * @author: luojun
 * @param:
 * @return:
 */
function new_get_select_projects($gid=0){
	if(!$gid){
		//$myGroupId = M("auth_group_access")->where("uid=".is_login())->getField('group_id');
		$myGroupIds = M("auth_group_access")->where("uid=".UID)->field('group_id')->select();
	}
	else{
		$myGroupIds[]=array('group_id'=>$gid);
	}
	$map = array(
			'status' => array(
					'gt',
					0
			)
	);
	$prolist = M('AuthGroup')->field('id,title,pid')
	->where($map)
	->select();
	$myProjects=array();
	foreach ($myGroupIds as $v){

		if (is_administrator(UID)) {
			$initPid = $gid;
		}
		else{
			$con  = array('status' => array('gt', 0),'id'=>$v['group_id']);
			
			$glist=M('AuthGroup')->field('id,title,pid,cate')->where($con)->find();
			
			//如果该用户组下没有项目
			$has_pro = M('jk_project')->where("pid=".$glist['id'])->count();
			//echo $has_pro;
			if($glist['cate']!=1 || $has_pro==0){
				 
			
				$glist=getCatePath('AuthGroup',$glist['id'],1);
				//查看是否为第五级
				//echo get_group_level($glist['pid'])."--";
				if(get_group_level($glist['pid'])==4){
					$glist=getCatePath('AuthGroup',$glist['pid'],1);
				}
			
				 
			}
				
			$initPid = $glist['id'];
		}
		
           
		$list = list_to_tree($prolist, 'id', 'pid', '_', $initPid);
		$list = tree_to_listwx($list, '_');
		
		// $ids=array('id'=>$initPid);
		$id=array();
		$ids[]=$initPid;
		foreach ($list as $var){
			$ids[]=$var['id'];
		}
		 
		$map=array();
		$map['status']    =   array('gt', 0);
		if(is_administrator(UID) && !$gid){
			
		}else{
			$map['pid']    =   array('in', $ids);
		}

		$projects=M("jk_project")->where($map)->order('id DESC')->field('id')->select();
		//         file_put_contents('sql.txt','upsql:'.M("jk_project")->_sql()."\r\n", FILE_APPEND);
		foreach($projects as $value){
			$myProjects[]=$value['id'];
		}
		 
	}


	return $myProjects;
}
/**
 * 函数用途描述： 获取用户选择的可访问项目
 * @date: 2017年7月12日 上午11:08:08
 * @author: tanjiewen
 * @param:
 * @return:
 */
function get_select_projects($gid=0){

 	if(!$gid){
         //$myGroupId = M("auth_group_access")->where("uid=".is_login())->getField('group_id');
         $myGroupIds = M("auth_group_access")->where("uid=".UID)->field('group_id')->select();
    }
    else{
        $myGroupIds[]=array('group_id'=>$gid);
    }
	$map = array(
			'status' => array(
					'gt',
					0
			)
	);
	$prolist = M('AuthGroup')->field('id,title,pid')
	->where($map)
	->select();
	$myProjects=array();
	foreach ($myGroupIds as $v){

	
		$con  = array('status' => array('gt', 0),'id'=>$v['group_id']);

		$glist=M('AuthGroup')->field('id,title,pid,cate')->where($con)->find();
		// echo M()->getLastSql();
// 		if($glist['cate']!=1){

// 			$glist=getCatePath('AuthGroup',$glist['id'],1);
// 			// dump($glist);
// 		}
		//return $glist['id'];
		$initPid = $glist['id'];
		$list = list_to_tree($prolist, 'id', 'pid', '_', $initPid);
		$list = tree_to_listwx($list, '_');
		// dump($initPid);
		// $ids=array('id'=>$initPid);
		$id=array();
		$ids[]=$initPid;
		foreach ($list as $var){
			$ids[]=$var['id'];
		}
		
		//默认项目
		$map=array();
		$map['status']    =   array('gt', 0);
		if($gid){
			$map['pid']    =   array('in', $ids);
		}
		$projects=M("jk_project")->where($map)->order('id DESC')->field('id')->select();
		//         file_put_contents('sql.txt','upsql:'.M("jk_project")->_sql()."\r\n", FILE_APPEND);
		foreach($projects as $value){
			$myProjects[]=$value['id'];
		}
		 
	}
	return $myProjects;
}
/**
 * 函数用途描述：读取当前页的完整域名
 * @date: 2017年07月12日 下午14:08:08
 * @author: 谭杰文
 * @param:
 * @return:
 */
function curPageURL()
{
	$pageURL = 'http';

	if ($_SERVER["HTTPS"] == "on")
	{
		$pageURL .= "s";
	}
	$pageURL .= "://";

	if ($_SERVER["SERVER_PORT"] != "80")
	{
		$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
	}
	else
	{
		$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

/**
 * 函数用途描述： 获取用户组下是否挂了项目
 * @date: 2016年11月1日 上午11:08:08
 * @author: 谭杰文
 * @param:
 * @return:
 */
function get_has_projects($gid=0){
    if(!$gid){
        //$myGroupId = M("auth_group_access")->where("uid=".is_login())->getField('group_id');
        $myGroupIds = M("auth_group_access")->where("uid=".UID)->field('group_id')->select();
    }
    else{
        $myGroupIds[]=array('group_id'=>$gid);
    }
    $map = array(
        'status' => array(
            'gt',
            0
        )
    );
    $list = M('AuthGroup')->field('id,title,pid')
    ->where($map)
    ->select();

    $myProjects=array();
    foreach ($myGroupIds as $v){

        if (is_administrator(UID)) {
            $initPid = 0;
        }
        else{
            $con  = array('status' => array('gt', 0),'id'=>$v['group_id']);

            $glist=M('AuthGroup')->field('id,title,pid,cate')->where($con)->find();

//             if($glist['cate']!=1){

//                 $glist=getCatePath('AuthGroup',$glist['id'],1);
//                 // dump($glist);
//             }
            	
            $initPid = $glist['id'];
        }

        $_SESSION['groId']=$initPid;
        $list = list_to_tree($list, 'id', 'pid', '_', $initPid);

        $list = tree_to_listwx($list, '_');
        // dump($initPid);
        // $ids=array('id'=>$initPid);
        $ids[]=$initPid;
        foreach ($list as $var){
            $ids[]=$var['id'];
        }
         
        $_SESSION['groId']=$ids[0];
        //默认项目
        $map['status']    =   array('gt', 0);
        if(!is_administrator(UID)||$gid){
            $map['pid']    =   array('in', $ids);
        }
        $projects=M("jk_project")->where($map)->order('id DESC')->field('id')->select();

        foreach($projects as $value){
            $myProjects[]=$value['id'];
        }
         
    }
    if($myProjects){
    	if(!$_SESSION['proId'])
       		$_SESSION['proId']=$myProjects[0];
    	//else 
    		//echo $_SESSION['proId'];
    }

    return $myProjects;
}

/**
 * 函数用途描述： 获取对应项目的上级用户组信息
 * @date: 2017年3月20日 下午18:08:08
 * @author: 谭杰文
 * @param:
 * @return:
 */
function getgroup($groupid,$name='',$level=1){
	//根据该项目id对应的用户组id
	$info=M("AuthGroup")->where("id='$groupid'")->Field('id,pid,title')->find();
	if($name!='')
		$name =  $info['title']."-".$name;
	else
		$name =  $info['title'];
	//echo $name;
	$level++;
	if($info && $info['pid']!=0 && $level<5){//如果不是最上级就递归
		return getgroup($info['pid'],$name,$level);
	}
	else{
		//echo $name;die;
		return $name;
	}	
}
/**
 * 函数用途描述： 获取对应项目的楼层信息
 * @date: 2016年11月1日 上午11:08:08
 * @author: 谭杰文
 * @param:
 * @return:
 */
function getpointinfo($id,$floor_detail=''){
	//     	var data = $api.getStorage('optionList');
	 
	//     	if (data[id]['pid'] > 0) {
	//     		idDatail = id + "," + idDatail;
	//     		getPointInfo(data[id]['pid']);
	//     	} else {
	 
	//     		idDatail = id + "," + idDatail;
	//     		cid = data[id]["cid"];
	//     	}
	//$projectid=$_SESSION['proId'];
	$data=M("jk_floor")->where("id=".$id)->field('pid,title')->find();
	return $data['title'];
 	if($data['pid']>0){
 		$floor_detail .= $data['title']+"-";
 		
 		getPointInfo($data['pid'],$floor_detail);
 	}else{
 		$floor_detail .=  $data['title'];
 	}
 	return $floor_detail;
}
/**
 * 函数用途描述： 获取父级对应的楼栋名
 * @date: 2017年10月25日 上午11:08:08
 * @author: 谭杰文
 * @param:
 * @return:
 */
function get_build_name($id){
	$data=M("jk_floor")->where("id=".$id)->field('pid,title')->find();
	
	if($data['pid']!=0){
	
		return get_build_name($data['pid']);
	}else{
		return $data['title'];
	}
	
}
/**
 * 函数用途描述： 获取父级对应的楼栋ID
 * @date: 2017年10月25日 上午11:08:08
 * @author: 谭杰文
 * @param:
 * @return:
 */
function get_build_id($id){
	$data=M("jk_floor")->where("id=".$id)->field('pid,id')->find();
	
	if($data['pid']!=0){
		return get_build_id($data['pid']);
	}else{
		
		return $data['id'];
	}

}
/**
* 函数用途描述：获取根选项信息
* @date: 2016年11月5日 下午3:07:30
* @author: luojun
* @param: 
* @return:
*/
function get_root_option($id, $type=1){
    $map['id'] = $id;
    if($type==1){
        $find=M('jk_option')->where($map)->field('id,pid,title')->find();
        while (1){
            $ret=$find;
            if($find['pid']==0){
                break;
            }
            $map['id'] = $find['pid'];
            $find=M('jk_option')->where($map)->field('id,pid,title')->find();
        }
        return $ret;
    }
    
}

/**
*函数用途描述：根据id串获取对应路径
*@date：2016年10月12日 下午3:48:42
*@author：luoj
*@param：$ids：图片id串；
*@return：图片路径，多个图片用','隔开；NULL 参数错误
**/
function coverIds2Path($ids,$arr=0){
    $aIds=explode(',', $ids);
    if (!$ids||!$aIds) {
        return NULL;
    }
//     $map['id']=array('in',$aIds);
    foreach ($aIds as $v){
        $aPath[] = M('picture')->where("id=$v")->getField('path');
    }
   // $aPath = M('picture')->where($map)->getField('path');
    //dump($aPath);
    if ($arr) {
        return $aPath;
    }
    return implode(',', $aPath);
}

/**
 *函数用途描述：根据问题或回复id获取对应路径
 *@date：2017年08月22日 下午3:48:42
 *@author：tanjiewen
 *@param：$id：问题或回复id；
 *@return：图片路径，多个图片用','隔开；NULL 参数错误
 **/
function get_image_by_id($id,$arr=0){
	$aPath = M('picture')->where("target_id='".$id."'")->getField('path',true);
	if ($arr) {
		return $aPath;
	}
	return implode(',', $aPath);
}
/**
 * 插件显示内容里生成访问插件的url
 * @param string $url url
 * @param array $param 参数
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function addons_url($url, $param = array(), $suffix = true, $domain = false)
{
    $url = parse_url($url);
    $case = C('URL_CASE_INSENSITIVE');
    $addons = $case ? parse_name($url['scheme']) : $url['scheme'];
    $controller = $case ? parse_name($url['host']) : $url['host'];
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');

    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }

    /* 基础参数 */
    $params = array(
        '_addons' => $addons,
        '_controller' => $controller,
        '_action' => $action,
    );
    $params = array_merge($params, $param); //添加额外参数
    if (strtolower(MODULE_NAME) == 'admin') {
        return U('Admin/Addons/execute', $params, $suffix, $domain);
    } else {
        return U('Home/Addons/execute', $params, $suffix, $domain);

    }

}
/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 * @author huajie <banhuajie@163.com>
 */
function time_format($time = NULL, $format = 'Y-m-d H:i')
{
	$time = $time === NULL ? NOW_TIME : intval($time);
	return date($format, $time);
}
/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 * @author huajie <banhuajie@163.com>
 */
function time_format1($time = NULL, $format = 'Y-m-d')
{
    $time = $time === NULL ? NOW_TIME : intval($time);
    return date($format, $time);
}

/**
 * 根据用户ID获取用户名
 * @param  integer $uid 用户ID
 * @return string       用户名
 */
function get_username($uid = 0)
{
    static $list;
    if (!($uid && is_numeric($uid))) { //获取当前登录用户名
        return $_SESSION['ocenter']['user_auth']['username'];
    }

    /* 获取缓存数据 */
    if (empty($list)) {
        $list = S('sys_active_user_list');
    }

    /* 查找用户信息 */
    $key = "u{$uid}";
    if (isset($list[$key])) { //已缓存，直接使用
        $name = $list[$key];
    } else { //调用接口获取用户信息
        $User = new User\Api\UserApi();
        $info = $User->info($uid);
        if ($info && isset($info[1])) {
            $name = $list[$key] = $info[1];
            /* 缓存用户 */
            $count = count($list);
            $max = C('USER_MAX_CACHE');
            while ($count-- > $max) {
                array_shift($list);
            }
            S('sys_active_user_list', $list);
        } else {
            $name = '';
        }
    }
    return $name;
}

/**
 * 根据用户ID获取用户昵称
 * @param  integer $uid 用户ID
 * @return string       用户昵称
 */
function get_nickname($uid = null)
{
    $user=query_user('nickname',$uid);
    return $user['nickname'];
}

/**
 * 获取分类信息并缓存分类
 * @param  integer $id 分类ID
 * @param  string $field 要获取的字段名
 * @return string         分类信息
 */
function get_category($id, $field = null)
{
    static $list;

    /* 非法分类ID */
    if (empty($id) || !is_numeric($id)) {
        return '';
    }

    /* 读取缓存数据 */
    if (empty($list)) {
        $list = S('sys_category_list');
    }

    /* 获取分类名称 */
    if (!isset($list[$id])) {
        $cate = M('Category')->find($id);
        if (!$cate || 1 != $cate['status']) { //不存在分类，或分类被禁用
            return '';
        }
        $list[$id] = $cate;
        S('sys_category_list', $list); //更新缓存
    }
    return is_null($field) ? $list[$id] : $list[$id][$field];
}

/* 根据ID获取分类标识 */
function get_category_name($id)
{
    return get_category($id, 'name');
}

/* 根据ID获取分类名称 */
function get_category_title($id)
{
    return get_category($id, 'title');
}

/**
 * 获取文档模型信息
 * @param  integer $id 模型ID
 * @param  string $field 模型字段
 * @return array
 */
function get_document_model($id = null, $field = null)
{
    static $list;

    /* 非法分类ID */
    if (!(is_numeric($id) || is_null($id))) {
        return '';
    }

    /* 读取缓存数据 */
    if (empty($list)) {
        $list = S('DOCUMENT_MODEL_LIST');
    }

    /* 获取模型名称 */
    if (empty($list)) {
        $map = array('status' => 1, 'extend' => 1);
        $model = M('Model')->where($map)->field(true)->select();
        foreach ($model as $value) {
            $list[$value['id']] = $value;
        }
        S('DOCUMENT_MODEL_LIST', $list); //更新缓存
    }

    /* 根据条件返回数据 */
    if (is_null($id)) {
        return $list;
    } elseif (is_null($field)) {
        return $list[$id];
    } else {
        return $list[$id][$field];
    }
}

/**
 * 解析UBB数据
 * @param string $data UBB字符串
 * @return string 解析为HTML的数据
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function ubb($data)
{
    //TODO: 待完善，目前返回原始数据
    return $data;
}

/**
 * 记录行为日志，并执行该行为的规则
 * @param string $action 行为标识
 * @param string $model 触发行为的模型名
 * @param int $record_id 触发行为的记录id
 * @param int $user_id 执行行为的用户id
 * @return boolean
 * @author huajie <banhuajie@163.com>
 */
function action_log($action = null, $model = null, $record_id = null, $user_id = null)
{

    //参数检查
    if (empty($action) || empty($model) || empty($record_id)) {
        return L('_PARAMETERS_CANT_BE_EMPTY_');
    }
    if (empty($user_id)) {
        $user_id = is_login();
    }

    //查询行为,判断是否执行
    $action_info = M('Action')->getByName($action);
    if ($action_info['status'] != 1) {
        return L('_THE_ACT_IS_DISABLED_OR_DELETED_');
    }

    //插入行为日志
    $data['action_id'] = $action_info['id'];
    $data['user_id'] = $user_id;
    $data['action_ip'] = ip2long(get_client_ip());
    $data['model'] = $model;
    $data['record_id'] = $record_id;
    $data['create_time'] = NOW_TIME;

    //解析日志规则,生成日志备注
    if (!empty($action_info['log'])) {
        if (preg_match_all('/\[(\S+?)\]/', $action_info['log'], $match)) {
            $log['user'] = $user_id;
            $log['record'] = $record_id;
            $log['model'] = $model;
            $log['time'] = NOW_TIME;
            $log['data'] = array('user' => $user_id, 'model' => $model, 'record' => $record_id, 'time' => NOW_TIME);
            foreach ($match[1] as $value) {
                $param = explode('|', $value);
                if (isset($param[1])) {
                    $replace[] = call_user_func($param[1], $log[$param[0]]);
                } else {
                    $replace[] = $log[$param[0]];
                }
            }
            $data['remark'] = str_replace($match[0], $replace, $action_info['log']);
        } else {
            $data['remark'] = $action_info['log'];
        }
    } else {
        //未定义日志规则，记录操作url
        $data['remark'] = '操作url：' . $_SERVER['REQUEST_URI'];
    }


    $log_id = M('ActionLog')->add($data);

    if (!empty($action_info['rule'])) {
        //解析行为
        $rules = parse_action($action, $user_id);
        //执行行为
        $res = execute_action($rules, $action_info['id'], $user_id, $log_id);
    }
}

/**
 * 解析行为规则
 * 规则定义  table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
 * 规则字段解释：table->要操作的数据表，不需要加表前缀；
 *              field->要操作的字段；
 *              condition->操作的条件，目前支持字符串，默认变量{$self}为执行行为的用户
 *              rule->对字段进行的具体操作，目前支持四则混合运算，如：1+score*2/2-3
 *              cycle->执行周期，单位（小时），表示$cycle小时内最多执行$max次
 *              max->单个周期内的最大执行次数（$cycle和$max必须同时定义，否则无效）
 * 单个行为后可加 ； 连接其他规则
 * @param string $action 行为id或者name
 * @param int $self 替换规则里的变量为执行用户的id
 * @return boolean|array: false解析出错 ， 成功返回规则数组
 * @author huajie <banhuajie@163.com>
 */
function parse_action($action = null, $self)
{
    if (empty($action)) {
        return false;
    }

    //参数支持id或者name
    if (is_numeric($action)) {
        $map = array('id' => $action);
    } else {
        $map = array('name' => $action);
    }

    //查询行为信息
    $info = M('Action')->where($map)->find();

    if (!$info || $info['status'] != 1) {
        return false;
    }


    //解析规则:table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
    $rules = unserialize($info['rule']);
    foreach ($rules as $key => &$rule) {
        foreach ($rule as $k => &$v) {
            if (empty($v)) {
                unset($rule[$k]);
            }
        }
        unset($k, $v);
    }
    unset($key, $rule);

    /*    $rules = str_replace('{$self}', $self, $rules);
        $rules = explode(';', $rules);
        $return = array();
        foreach ($rules as $key => &$rule) {
            $rule = explode('|', $rule);
            foreach ($rule as $k => $fields) {
                $field = empty($fields) ? array() : explode(':', $fields);
                if (!empty($field)) {
                    $return[$key][$field[0]] = $field[1];
                }
            }
            //cycle(检查周期)和max(周期内最大执行次数)必须同时存在，否则去掉这两个条件
            if (!array_key_exists('cycle', $return[$key]) || !array_key_exists('max', $return[$key])) {
                unset($return[$key]['cycle'], $return[$key]['max']);
            }
        }*/


    return $rules;
}

/**
 * 执行行为
 * @param array $rules 解析后的规则数组
 * @param int $action_id 行为id
 * @param array $user_id 执行的用户id
 * @return boolean false 失败 ， true 成功
 * @author huajie <banhuajie@163.com>
 */
function execute_action($rules = false, $action_id = null, $user_id = null, $log_id = null)
{
    $log_score = '';

    hook('handleAction', array('action_id' => $action_id, 'user_id' => $user_id, 'log_id' => $log_id, 'log_score' => &$log_score));

    if (!$rules || empty($action_id) || empty($user_id)) {
        return false;
    }
    $return = true;

    $action_log = M('ActionLog')->where(array('id' => $log_id))->find();
    foreach ($rules as $rule) {
        //检查执行周期
        $map = array('action_id' => $action_id, 'user_id' => $user_id);
        $map['create_time'] = array('gt', NOW_TIME - intval($rule['cycle']) * 3600);
        $exec_count = M('ActionLog')->where($map)->count();
        if ($exec_count > $rule['max']) {
            continue;
        }
        //执行数据库操作
        $Model = M(ucfirst($rule['table']));
        $field = 'score' . $rule['field'];


        $rule['rule'] = (is_bool(strpos($rule['rule'], '+')) ? '+' : '') . $rule['rule'];
        $rule['rule'] = is_bool(strpos($rule['rule'], '-')) ? $rule['rule'] : substr($rule['rule'], 1);
        $res = $Model->where(array('uid' => is_login(), 'status' => 1))->setField($field, array('exp', $field . $rule['rule']));

        $scoreModel = D('Ucenter/Score');

        $scoreModel->cleanUserCache(is_login(), $rule['field']);


        $sType = D('ucenter_score_type')->where(array('id' => $rule['field']))->find();
        $log_score .= '【' . $sType['title'] . '：' . $rule['rule'] . $sType['unit'] . '】';

        $action = strpos($rule['rule'], '-') ? 'dec' : 'inc';
        $scoreModel->addScoreLog(is_login(), $rule['field'], $action, substr($rule['rule'], 1, strlen($rule['rule']) - 1), $action_log['model'], $action_log['record_id'], $action_log['remark'] . '【' . $sType['title'] . '：' . $rule['rule'] . $sType['unit'] . '】');

        if (!$res) {
            $return = false;
        }
    }
    if ($log_score) {
        cookie('score_tip', $log_score, 30);
        M('ActionLog')->where(array('id' => $log_id))->setField('remark', array('exp', "CONCAT(remark,'" . $log_score . "')"));
    }
    return $return;
}

//基于数组创建目录和文件
function create_dir_or_files($files)
{
    foreach ($files as $key => $value) {
        if (substr($value, -1) == '/') {
            mkdir($value);
        } else {
            @file_put_contents($value, '');
        }
    }
}

function array_gets($array, $fields)
{
    $result = array();
    foreach ($fields as $e) {
        if (array_key_exists($e, $array)) {
            $result[$e] = $array[$e];
        }
    }
    return $result;
}

if (!function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null)
    {
        $result = array();
        if (null === $indexKey) {
            if (null === $columnKey) {
                $result = array_values($input);
            } else {
                foreach ($input as $row) {
                    $result[] = $row[$columnKey];
                }
            }
        } else {
            if (null === $columnKey) {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row;
                }
            } else {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row[$columnKey];
                }
            }
        }
        return $result;
    }
}

/**
 * 获取表名（不含表前缀）
 * @param string $model_id
 * @return string 表名
 * @author huajie <banhuajie@163.com>
 */
function get_table_name($model_id = null)
{
    if (empty($model_id)) {
        return false;
    }
    $Model = M('Model');
    $name = '';
    $info = $Model->getById($model_id);
    if ($info['extend'] != 0) {
        $name = $Model->getFieldById($info['extend'], 'name') . '_';
    }
    $name .= $info['name'];
    return $name;
}

/**
 * 获取属性信息并缓存
 * @param  integer $id 属性ID
 * @param  string $field 要获取的字段名
 * @return string         属性信息
 */
function get_model_attribute($model_id, $group = true)
{
    static $list;

    /* 非法ID */
    if (empty($model_id) || !is_numeric($model_id)) {
        return '';
    }

    /* 读取缓存数据 */
    if (empty($list)) {
        $list = S('attribute_list');
    }

    /* 获取属性 */
    if (!isset($list[$model_id])) {
        $map = array('model_id' => $model_id);
        $extend = M('Model')->getFieldById($model_id, 'extend');

        if ($extend) {
            $map = array('model_id' => array("in", array($model_id, $extend)));
        }
        $info = M('Attribute')->where($map)->select();
        $list[$model_id] = $info;
        //S('attribute_list', $list); //更新缓存
    }

    $attr = array();
    foreach ($list[$model_id] as $value) {
        $attr[$value['id']] = $value;
    }

    if ($group) {
        $sort = M('Model')->getFieldById($model_id, 'field_sort');

        if (empty($sort)) { //未排序
            $group = array(1 => array_merge($attr));
        } else {
            $group = json_decode($sort, true);

            $keys = array_keys($group);
            foreach ($group as &$value) {
                foreach ($value as $key => $val) {
                    $value[$key] = $attr[$val];
                    unset($attr[$val]);
                }
            }

            if (!empty($attr)) {
                $group[$keys[0]] = array_merge($group[$keys[0]], $attr);
            }
        }
        $attr = $group;
    }
    return $attr;
}

/**
 * 调用系统的API接口方法（静态方法）
 * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
 * api('Admin/User/getName','id=5');  调用Admin模块的User接口
 * @param  string $name 格式 [模块名]/接口名/方法名
 * @param  array|string $vars 参数
 */
function api($name, $vars = array())
{
    $array = explode('/', $name);

    $method = array_pop($array);
    $classname = array_pop($array);
    $module = $array ? array_pop($array) : 'Common';
    $callback = $module . '\\Api\\' . $classname . 'Api::' . $method;
    if (is_string($vars)) {
        parse_str($vars, $vars);
    }
    return call_user_func_array($callback, $vars);
}

/**
 * 根据条件字段获取指定表的数据
 * @param mixed $value 条件，可用常量或者数组
 * @param string $condition 条件字段
 * @param string $field 需要返回的字段，不传则返回整个数据
 * @param string $table 需要查询的表
 * @author huajie <banhuajie@163.com>
 */
function get_table_field($value = null, $condition = 'id', $field = null, $table = null)
{
    if (empty($value) || empty($table)) {
        return false;
    }

    //拼接参数
    $map[$condition] = $value;
    $info = M(ucfirst($table))->where($map);
    if (empty($field)) {
        $info = $info->field(true)->find();
    } else {
        $info = $info->getField($field);
    }
    return $info;
}

/**
 * 获取链接信息
 * @param int $link_id
 * @param string $field
 * @return 完整的链接信息或者某一字段
 * @author huajie <banhuajie@163.com>
 */
function get_link($link_id = null, $field = 'url')
{
    $link = '';
    if (empty($link_id)) {
        return $link;
    }
    $link = M('Url')->getById($link_id);
    if (empty($field)) {
        return $link;
    } else {
        return $link[$field];
    }
}


/**
 * 检查$pos(推荐位的值)是否包含指定推荐位$contain
 * @param number $pos 推荐位的值
 * @param number $contain 指定推荐位
 * @return boolean true 包含 ， false 不包含
 * @author huajie <banhuajie@163.com>
 */
function check_document_position($pos = 0, $contain = 0)
{
    if (empty($pos) || empty($contain)) {
        return false;
    }

    //将两个参数进行按位与运算，不为0则表示$contain属于$pos
    $res = $pos & $contain;
    if ($res !== 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取数据的所有子孙数据的id值
 * @author 朱亚杰 <xcoolcc@gmail.com>
 */

function get_stemma($pids, Model &$model, $field = 'id')
{
    $collection = array();

    //非空判断
    if (empty($pids)) {
        return $collection;
    }

    if (is_array($pids)) {
        $pids = trim(implode(',', $pids), ',');
    }
    $result = $model->field($field)->where(array('pid' => array('IN', (string)$pids)))->select();
    $child_ids = array_column((array)$result, 'id');

    while (!empty($child_ids)) {
        $collection = array_merge($collection, $result);
        $result = $model->field($field)->where(array('pid' => array('IN', $child_ids)))->select();
        $child_ids = array_column((array)$result, 'id');
    }
    return $collection;
}

function get_stemmaEX($pids, Model &$model, $field = 'id')
{
    $collection = array();

    //非空判断
    if (empty($pids)) {
        return $collection;
    }

    if (is_array($pids)) {
        $pids = trim(implode(',', $pids), ',');
    }
    $result = $model->field($field)->where(array('pid' => array('IN', (string)$pids),'status'=>1))->select();
    $child_ids = array_column((array)$result, 'id');

    while (!empty($child_ids)) {
        $collection = array_merge($collection, $result);
        $result = $model->field($field)->where(array('pid' => array('IN', $child_ids),'status'=>1))->select();
        $child_ids = array_column((array)$result, 'id');
    }
    return $collection;
}
/**
 * 获取导航URL
 * @param  string $url 导航URL
 * @return string      解析或的url
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_nav_url($url)
{
    switch ($url) {
        case 'http://' === substr($url, 0, 7):
        case '#' === substr($url, 0, 1):
            break;
        default:
            $url = U($url);
            break;
    }
    return $url;
}

/**
 * @param $url 检测当前url是否被选中
 * @return bool|string
 * @auth 陈一枭
 */
function get_nav_active($url)
{
    switch ($url) {
        case 'http://' === substr($url, 0, 7):
            if (strtolower($url) === strtolower($_SERVER['HTTP_REFERER'])) {
                return 1;
            }
        case '#' === substr($url, 0, 1):
            return 0;
            break;
        default:
            $url_array = explode('/', $url);
            if ($url_array[0] == '') {
                $MODULE_NAME = $url_array[1];
            } else {
                $MODULE_NAME = $url_array[0]; //发现模块就是当前模块即选中。

            }
            if (strtolower($MODULE_NAME) === strtolower(MODULE_NAME)) {
                return 1;
            };
            break;

    }
    return 0;
}

/**
 * 获取列表总行数
 * @param  string $category 分类ID
 * @param  integer $status 数据状态
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_list_count($category, $status = 1)
{
    static $count;
    if (!isset($count[$category])) {
        $count[$category] = D('Document')->listCount($category, $status);
    }
    return $count[$category];
}

/**
 * t函数用于过滤标签，输出没有html的干净的文本
 * @param string text 文本内容
 * @return string 处理后内容
 */
function op_t($text, $addslanshes = false)
{
    $text = nl2br($text);
    $text = real_strip_tags($text);
    if ($addslanshes)
        $text = addslashes($text);
    $text = trim($text);
    return $text;
}

/**过滤函数，别名函数，op_t的别名
 * @param $text
 * @auth 陈一枭
 */
function text($text, $addslanshes = false)
{
    return op_t($text, $addslanshes);
}

/**过滤函数，别名函数，op_h的别名
 * @param $text
 * @auth 陈一枭
 */
function html($text)
{
    return op_h($text);
}

/**
 * h函数用于过滤不安全的html标签，输出安全的html
 * @param string $text 待过滤的字符串
 * @param string $type 保留的标签格式
 * @return string 处理后内容
 */
function op_h($text, $type = 'html')
{
    // 无标签格式
    $text_tags = '';
    //只保留链接
    $link_tags = '<a>';
    //只保留图片
    $image_tags = '<img>';
    //只存在字体样式
    $font_tags = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
    //标题摘要基本格式
    $base_tags = $font_tags . '<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike>';
    //兼容Form格式
    $form_tags = $base_tags . '<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
    //内容等允许HTML的格式
    $html_tags = $base_tags . '<ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed><param>';
    //专题等全HTML格式
    $all_tags = $form_tags . $html_tags . '<!DOCTYPE><meta><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
    //过滤标签
    $text = real_strip_tags($text, ${$type . '_tags'});
    // 过滤攻击代码
    if ($type != 'all') {
        // 过滤危险的属性，如：过滤on事件lang js
        while (preg_match('/(<[^><]+)(ondblclick|onclick|onload|onerror|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|action|background[^-]|codebase|dynsrc|lowsrc)([^><]*)/i', $text, $mat)) {
            $text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
        }
        while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
            $text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
        }
    }
    return $text;
}

function real_strip_tags($str, $allowable_tags = "")
{
    // $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    return strip_tags($str, $allowable_tags);
}

/**span
 * 获取楼层信息
 * @param $k
 */
function getLou($k)
{
    $lou = array(
        2 => L('_SOFA_'),
        3 => L('_BENCH_'),
        4 => L('_FLOOR_')
    );
    !empty($lou[$k]) && $res = $lou[$k];
    empty($lou[$k]) && $res = $k . '楼';
    return $res;
}

/**获取当前的积分
 * @return mixed
 * @auth 陈一枭
 */
/**获取当前的积分
 * @param string $score_name
 * @return mixed
 * @auth 陈一枭
 */
function getMyScore($score_name = 'score1')
{
    $user = query_user(array($score_name), is_login());
    $score = $user[$score_name];
    return $score;
}

/**根据积分的变动返回提示文本
 * @param $before 变动前的积分
 * @param $after 变动后的积分
 * @return string
 * @auth 陈一枭
 */
function getScoreTip($before, $after)
{
    $score_change = $after - $before;
    $tip = '';
    if ($score_change) {
        $tip = L('_INTEGRAL_') . ($score_change > 0 ? '加&nbsp;' . $score_change : '减&nbsp;' . $score_change) . ' 。';
    }
    return $tip;
}


function action_log_and_get_score($action = null, $model = null, $record_id = null, $user_id = null)
{
    $score_before = getMyScore();
    action_log($action, $model, $record_id, $user_id);
    $score_after = getMyScore();
    return $score_after - $score_before;
}

function is_ie()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $pos = strpos($userAgent, ' MSIE ');
    if ($pos === false) {
        return false;
    } else {
        return true;
    }
}

function array_subtract($a, $b)
{
    return array_diff($a, array_intersect($a, $b));
}


function tox_addons_url($url, $param)
{
    // 拆分URL
    $url = explode('/', $url);
    $addon = $url[0];
    $controller = $url[1];
    $action = $url[2];

    // 调用u函数
    $param['_addons'] = $addon;
    $param['_controller'] = $controller;
    $param['_action'] = $action;
    return U("Home/Addons/execute", $param);
}


/**
 * 取一个二维数组中的每个数组的固定的键知道的值来形成一个新的一维数组
 * @param $pArray 一个二维数组
 * @param $pKey 数组的键的名称
 * @return 返回新的一维数组
 */
function getSubByKey($pArray, $pKey = "", $pCondition = "")
{
    $result = array();
    if (is_array($pArray)) {
        foreach ($pArray as $temp_array) {
            if (is_object($temp_array)) {
                $temp_array = (array)$temp_array;
            }
            if (("" != $pCondition && $temp_array[$pCondition[0]] == $pCondition[1]) || "" == $pCondition) {
                $result[] = ("" == $pKey) ? $temp_array : isset($temp_array[$pKey]) ? $temp_array[$pKey] : "";
            }
        }
        return $result;
    } else {
        return false;
    }
}


/**
 * create_rand随机生成一个字符串
 * @param int $length 字符串的长度
 * @param string $type 类型
 * @return string
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
function create_rand($length = 8, $type = 'all')
{
    $num = '0123456789';
    $letter = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if ($type == 'num') {
        $chars = $num;
    } elseif ($type == 'letter') {
        $chars = $letter;
    } else {
        $chars = $letter . $num;
    }

    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;

}


/**
 * curl_get_headers 获取链接header
 * @param $url
 * @return array
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
function curl_get_headers($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $f = curl_exec($ch);
    curl_close($ch);
    $h = explode("\n", $f);
    $r = array();
    foreach ($h as $t) {
        $rr = explode(":", $t, 2);
        if (count($rr) == 2) {
            $r[$rr[0]] = trim($rr[1]);
        }
    }
    return $r;
}


/**
 * 生成系统AUTH_KEY
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function build_auth_key()
{
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // $chars .= '`~!@#$%^&*()_+-=[]{};:"|,.<>/?';
    $chars = str_shuffle($chars);
    return substr($chars, 0, 40);
}

require_once('./api/config.php');


/**
 * get_some_day  获取n天前0点的时间戳
 * @param int $some n天
 * @param null $day 当前时间
 * @return int|null
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
function get_some_day($some = 30, $day = null)
{
    $time = $day ? $day : time();
    $some_day = $time - 60 * 60 * 24 * $some;
    $btime = date('Y-m-d' . ' 00:00:00', $some_day);
    $some_day = strtotime($btime);
    return $some_day;
}

/**
 * 用户扩展资料可添加关联字段
 * @param string $id 关联数据表ID
 * @param string $field 需要返回的字段内容
 * @param string $table 关联数据表
 * @return array string
 * @author MingYang <xint5288@126.com>
 */
function get_userdata_join($id = null, $field = null, $table = null)
{
    if (empty($table) || empty($field)) {
        return false;
    }
    if (empty($id)) {
        $data = D($table)->select();
        foreach ($data as $key => $val) {
            $list[$key] = $val;
        }
        return $list;
    } else {
        if (is_array($id)) {
            $map['id'] = array('in', $id);
            $data = D($table)->where($map)->getField($field, true);
            return implode(',', $data);
        } else {
            $map['id'] = $id;
            $data = D($table)->where($map)->getField($field);
            return $data;
        }
    }
}

/**
 * 获取指定表字段信息，可定义多个组合查询条件（查阅thinkphp）返回查询字段和ID
 * @param string $map 数组：条件字段以及条件（array('level'=>1,'name'=>array('like','%UUIMA'));）
 * @param string $field 需要返回的字段
 * @param string $table 查询表
 * @param string $yesnoid 是否返回ID(预留·)
 * @return  NULL, string, unknown, mixed, object>
 * @author MingYang<xint5288@126.com>
 */
function get_data_field_id($map = null, $field = null, $table = null, $yesnoid = '')
{
    if (empty($table) || empty($field)) {
        return false;
    }

    if (empty($map)) {
        $data = D($table)->select();
        foreach ($data as $key => $val) {
            $list[$key]['id'] = $val['id'];
            $list[$key]['value'] = $val[$field];
        }
        return $list;
    } else {
        if (empty($yesnoid)) {
            $data = D($table)->where($map)->select();
            foreach ($data as $key => $val) {
                $list[$key]['id'] = $val['id'];
                $list[$key]['value'] = $val[$field];
            }
        } else {
            $list = D($table)->where($map)->getField($field);
        }
        return $list;
    }
}


function UCenterMember()
{
    return D('User/UcenterMember');
}


function verify($id = 1)
{
    $type = C('VERIFY_TYPE');
    $verify = new \Think\Verify();
    switch ($type) {
        case 1 :
            $verify->useZh = true;
            break;
        case 2 :
            $verify->codeSet = 'abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY';
            break;
        case 3 :
            $verify->codeSet = '0123456789';
            break;
        case 4 :
            break;
        default:

    }
    $verify->entry($id);
}

function check_verify_open($open)
{
    $config = C('VERIFY_OPEN');

    if ($config) {
        $config = explode(',', $config);
        if (in_array($open, $config)) {
            return true;
        }
    }
    return false;
}


function check_is_in_config($key, $config)
{
    !is_array($config) && $config = explode(',', $config);
    return in_array($key, $config);

}

/**
 * convert_url_query  转换url参数为数组
 * @param $query
 * @return array|string
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
function convert_url_query($query)
{
    if (!empty($query)) {
        $query = urldecode($query);
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }
    return '';
}


/**
 * get_ip_lookup  获取ip地址所在的区域
 * @param null $ip
 * @return bool|mixed
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
function get_ip_lookup($ip = null)
{
    if (empty($ip)) {
        $ip = get_client_ip(0);
    }
    $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
    if (empty($res)) {
        return false;
    }
    $jsonMatches = array();
    preg_match('#\{.+?\}#', $res, $jsonMatches);
    if (!isset($jsonMatches[0])) {
        return false;
    }
    $json = json_decode($jsonMatches[0], true);
    if (isset($json['ret']) && $json['ret'] == 1) {
        $json['ip'] = $ip;
        unset($json['ret']);
    } else {
        return false;
    }
    return $json;
}

/**
 * cut_str  截取字符串
 * @param $search
 * @param $str
 * @param string $place
 * @return mixed
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
function cut_str($search, $str, $place = '')
{
    switch ($place) {
        case 'l':
            $result = preg_replace('/.*?' . addcslashes(quotemeta($search), '/') . '/', '', $str);
            break;
        case 'r':
            $result = preg_replace('/' . addcslashes(quotemeta($search), '/') . '.*/', '', $str);
            break;
        default:
            $result = preg_replace('/' . addcslashes(quotemeta($search), '/') . '/', '', $str);
    }
    return $result;
}


/**
 * array_search_key 搜索数组中某个键为某个值的数组
 * @param $array
 * @param $key
 * @param $value
 * @return bool
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
function array_search_key($array, $key, $value)
{
    foreach ($array as $k => $v) {
        if ($v[$key] == $value) {
            return $array[$k];
        }
    }
    return false;
}


/**
 * array_delete  删除数组中的某个值
 * @param $array
 * @param $value
 * @return mixed
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
function array_delete($array, $value)
{
    $key = array_search($value, $array);
    if ($key !== false)
        array_splice($array, $key, 1);
    return $array;
}


/**
 * get_upload_config  获取上传驱动配置
 * @param $driver
 * @return mixed
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
function get_upload_config($driver)
{
    if ($driver == 'local') {
        $uploadConfig = C("UPLOAD_{$driver}_CONFIG");
    } else {
        $name = get_addon_class($driver);
        $class = new $name();
        $uploadConfig = $class->uploadConfig();
    }
    return $uploadConfig;
}

/**
 * check_driver_is_exist 判断上传驱动插件是否存在
 * @param $driver
 * @return string
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
function check_driver_is_exist($driver)
{
    if ($driver == 'local') {
        return $driver;
    } else {
        $name = get_addon_class($driver);
        if (class_exists($name)) {
            return $driver;
        } else {
            return 'local';
        }
    }
}


function is_mobile()
{
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $mobile_agents = Array("240x320", "acer", "acoon", "acs-", "abacho", "ahong", "airness", "alcatel", "amoi", "android", "anywhereyougo.com", "applewebkit/525", "applewebkit/532", "asus", "audio", "au-mic", "avantogo", "becker", "benq", "bilbo", "bird", "blackberry", "blazer", "bleu", "cdm-", "compal", "coolpad", "danger", "dbtel", "dopod", "elaine", "eric", "etouch", "fly ", "fly_", "fly-", "go.web", "goodaccess", "gradiente", "grundig", "haier", "hedy", "hitachi", "htc", "huawei", "hutchison", "inno", "ipad", "ipaq", "ipod", "jbrowser", "kddi", "kgt", "kwc", "lenovo", "lg ", "lg2", "lg3", "lg4", "lg5", "lg7", "lg8", "lg9", "lg-", "lge-", "lge9", "longcos", "maemo", "mercator", "meridian", "micromax", "midp", "mini", "mitsu", "mmm", "mmp", "mobi", "mot-", "moto", "nec-", "netfront", "newgen", "nexian", "nf-browser", "nintendo", "nitro", "nokia", "nook", "novarra", "obigo", "palm", "panasonic", "pantech", "philips", "phone", "pg-", "playstation", "pocket", "pt-", "qc-", "qtek", "rover", "sagem", "sama", "samu", "sanyo", "samsung", "sch-", "scooter", "sec-", "sendo", "sgh-", "sharp", "siemens", "sie-", "softbank", "sony", "spice", "sprint", "spv", "symbian", "tablet", "talkabout", "tcl-", "teleca", "telit", "tianyu", "tim-", "toshiba", "tsm", "up.browser", "utec", "utstar", "verykool", "virgin", "vk-", "voda", "voxtel", "vx", "wap", "wellco", "wig browser", "wii", "windows ce", "wireless", "xda", "xde", "zte");
    $is_mobile = false;
    foreach ($mobile_agents as $device) {
        if (stristr($user_agent, $device)) {
            $is_mobile = true;
            break;
        }
    }
    return $is_mobile;
}


/**
 * check_sms_hook_is_exist  判断短信服务插件是否存在，不存在则返回none
 * @param $driver
 * @return string
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
function check_sms_hook_is_exist($driver)
{
    if ($driver == 'none') {
        return $driver;
    } else {
        $name = get_addon_class($driver);
        if (class_exists($name)) {
            return $driver;
        } else {
            return 'none';
        }
    }
}


function home_addons_url($url, $param = array(), $suffix = true, $domain = false)
{
    $url = parse_url($url);
    $case = C('URL_CASE_INSENSITIVE');
    $addons = $case ? parse_name($url['scheme']) : $url['scheme'];
    $controller = $case ? parse_name($url['host']) : $url['host'];
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');

    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }

    /* 基础参数 */
    $params = array(
        '_addons' => $addons,
        '_controller' => $controller,
        '_action' => $action,
    );

    $params = array_merge($params, $param); //添加额外参数
    return U('Home/Addons/execute', $params, $suffix, $domain);

}


function render_picture_path($path)
{
    $path = get_pic_src($path);
    return is_bool(strpos($path, 'http://')) ? 'http://' . str_replace('//', '/', $_SERVER['HTTP_HOST'] . '/' . $path) : $path;
}


function get_area_name($id)
{
    return M('district')->where(array('id' => $id))->getField('name');
}

function get_all_module_lang($common_lang = array())
{
    $file = scandir('./Application', 0);
    $module_lang = array();
    $list = array();
    $now_module = array();
    foreach ($file as $v) {
        if (($v != ".") and ($v != "..") and ($v != "Common")) {
            $file = './Application/' . $v . '/Lang/' . LANG_SET . '.php';
            if (is_file($file)) {
                if (MODULE_NAME == $v) {
                    $now_module = include $file;
                } else {
                    $list[] = include $file;
                }
            }
        }
    }
    $list[] = $common_lang;
    $list[] = $now_module;
    foreach ($list as $val) {
        $module_lang = array_merge($module_lang, (array)$val);
    }
    $lang = $module_lang ; // array_unique(array_merge($common_lang,$module_lang));
    return $lang;

}

/**
 * 获取对应类型的banner图
 */
function getbanners($type){
	$bannerimgs = M('jk_banner')->where("page = $type and status = 1")->order('name')->select();
	return $bannerimgs;
}
/**
 * 获取meta表信息，主要用于卡卷和收藏
 * @param 查询类型 $meta_key
 * @return查询信息
 */
function get_meta($uid,$meta_key){
	$info = M('jk_meta')->where("user_id = $uid and meta_key = '$meta_key'")->field('meta_value,status,updatetime')->select();
	return $info;
}

/**
 * 新增meta信息，主要用于卡卷和收藏
 * @param 查询类型 $meta_key
 * @return查询信息
 */
function add_meta($uid,$meta_key,$meta_value){
	$data['user_id'] = $uid;
	$data['meta_key'] = $meta_key;
	$data['meta_value'] = $meta_value;
	$data['updatetime'] = time();
	$meta_id = M('jk_meta')->where("user_id = $uid and meta_key = '$meta_key' and meta_value = '$meta_value'")->getField('id');
	if($meta_id){
		return 0;
	}else{
		$id = M('jk_meta')->data($data)->add();
		return $id;
	}
}
/**
 * 查询商品是在处于秒杀活动中
 * 2016-07-27
 * 如果处于秒杀活动则返回秒杀价格
 * 如果没有则不做处理
 */
function isFastshop($shopid){
	$now = time();
	$map="status = 1 and endtime > $now and type =1 and aword = '$shopid'";
	$isactid = M('jk_activity')->where($map)->getField('id');
	if($isactid){
		$activity_price = M('jk_activity')->where($map)->getField('activity_price');
	}else{
		$activity_price = "NO";
	}
	return $activity_price;
}
/**
 * 查询活动对应奖券
 * 2016-07-28
 * 返回的是奖券ID
 * 如果没有则不做处理
 */
function activityCard($activityid){
	$now = time();
	$awardid = M('jk_activity')->where("id=$activityid")->getField('aword');
	if($awardid){
		$res = $awardid;
	}else{
		$res = "NO";
	}
	return $res;
}
/**
 * 函数用途描述：curl_post
 * @date: 2016年5月17日 下午5:32:56
 * @author: jun
 * @param:
 * @return:
 */
function http_post_data($url,$data)
{
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	 
	$result = curl_exec($curl);
	if (curl_errno($curl)) {
		return 'Errno'.curl_error($curl);
	}
	curl_close($curl);
	return $result;
}

/**
 * 函数用途描述：curl_get
 * @date: 2016年6月23日 下午2:58:22
 * @author: luojun
 * @param:
 * @return:
 */
function http_get_data($url)
{
	$curl = curl_init();
	$this_header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8");

	curl_setopt($curl,CURLOPT_HTTPHEADER,$this_header);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	 
	$result = curl_exec($curl);
	if (curl_errno($curl)) {
		return 'Errno'.curl_error($curl);
	}
	curl_close($curl);
	return $result;
}

/**
* 函数用途描述：构造项目选择菜单
* @date: 2016年11月13日
* @author: luojun
* @return:
*/
function menuList($list,&$html){
	
    if($list){
		foreach($list as $data){
			
			if($data['_']){
				$html.='<li  class="mymenu dropdown-submenu">';
				$html.='<a href="/index.php?s=/admin/index/index/gid/'.$data['id'].'"> '.$data['title'].' </a><ul class="dropdown-menu">';
				menuList($data['_'],$html);
				$html.='</ul></li>';
			}
			else{
				$html.='<li  class="mymenu"><a href="/index.php?s=/admin/index/index/gid/'.$data['id'].'">'.$data['title'].'</a></li>';
				
			}
		}
    }
	
}

/**
*函数用途描述：构建组织节点
*@date：2016年11月15日 上午10:02:30
*@author：luoj
*@param：
*@return：
**/
function nodeList($list,&$nodehtml,$i=0){

    if($list){
        foreach($list as $data){
            	
            if($data['_']){
                if($data['short_title']!=null && $data['short_title']!=""){
                    if($i<1)
                        $nodehtml.='<li data-jstree=\'{ "opened" : true ,"icon" : "fa fa-group icon-state-success" }\'><a onclick="mynode('.$data['id'].')">'.$data['short_title'];
                    else
                        $nodehtml.='<li data-jstree=\'{ "opened" : false ,"icon" : "fa fa-group icon-state-success" }\'><a onclick="mynode('.$data['id'].')">'.$data['short_title'];
                }else{
                    if($i<1)
                        $nodehtml.='<li data-jstree=\'{ "opened" : true ,"icon" : "fa fa-group icon-state-success" }\'><a onclick="mynode('.$data['id'].')">'.$data['title'];
                    else 
                        $nodehtml.='<li data-jstree=\'{ "opened" : false ,"icon" : "fa fa-group icon-state-success" }\'><a onclick="mynode('.$data['id'].')">'.$data['title'];
                }
                $nodehtml.='</a><ul>';
                $i++;
                nodeList($data['_'],$nodehtml,$i);
                $i--;
                $nodehtml.='</ul></li>';
            }
            else{
                if($data['short_title']!=null && $data['short_title']!="")
                    $nodehtml.='<li data-jstree=\'{ "icon" : "fa fa-group icon-state-success" }\'><a onclick="mynode('.$data['id'].')">'.$data['short_title'].'</a></li>';
                else
                    $nodehtml.='<li data-jstree=\'{ "icon" : "fa fa-group icon-state-success" }\'><a onclick="mynode('.$data['id'].')">'.$data['title'].'</a></li>';

            }
        }
        $i++;
    }

    // dump($html);
}
/**
 *函数用途描述：构建组织节点（用户管理专用）
 *@date：2017年6月21日 下午14:55:30
 *@author：tanjiewen
 *@param：
 *@return：
 **/
function user_nodeList($list,&$nodehtml){
	if($list){
		foreach($list as $data){
			 
			if($data['_']){
				if($data['short_title']!=null && $data['short_title']!=""){
					$data['short_title']="".$data['short_title']."";
					$nodehtml.='<li data-jstree=\'{ "opened" : false ,"icon" : "fa fa-group icon-state-success" }\'><a onclick="mynode('.$data['id'].',"'.$data['short_title'].'")">'.$data['short_title'];
				}
				else{
					$data['title']="".$data['title']."";
					$nodehtml.='<li data-jstree=\'{ "opened" : false ,"icon" : "fa fa-group icon-state-success" }\'><a onclick="mynode('.$data['id'].',"'.$data['title'].'")">'.$data['title'];
				}
					$nodehtml.='</a><ul>';
				nodeList($data['_'],$nodehtml);
				$nodehtml.='</ul></li>';
			}
			else{
				if($data['short_title']!=null && $data['short_title']!=""){
					$data['short_title']="".$data['short_title']."";
					$nodehtml.='<li data-jstree=\'{ "icon" : "fa fa-group icon-state-success" }\'><a onclick="mynode('.$data['id'].',"'.$data['short_title'].'")">'.$data['short_title'].'</a></li>';
				}
				else{
					$data['short_title']="".$data['short_title']."";
					$nodehtml.='<li data-jstree=\'{ "icon" : "fa fa-group icon-state-success" }\'><a onclick="mynode('.$data['id'].',"'.$data['title'].'")">'.$data['title'].'</a></li>';
				}
			}
		}
	}

	// dump($html);
}

/**
* 函数用途描述：构造只有项目节点能点击的组织架构
* @date: 2017年5月15日 上午10:02:57
* @author: luojun
* @param: 
* @return:
*/
function nodeListwithNO($list,&$nodehtml){

    if($list){
        foreach($list as $data){
             
            if($data['_']){
                if($data['short_title']!=null && $data['short_title']!="")
                    $nodehtml.='<li data-jstree=\'{ "opened" : true ,"icon" : "fa fa-group icon-state-success" }\'><a onclick="nonode('.$data['id'].')">'.$data['short_title'];
                else
                    $nodehtml.='<li data-jstree=\'{ "opened" : true ,"icon" : "fa fa-group icon-state-success" }\'><a onclick="nonode('.$data['id'].')">'.$data['title'];
                $nodehtml.='</a><ul>';
                nodeList($data['_'],$nodehtml);
                $nodehtml.='</ul></li>';
            }
            else{
                if($data['short_title']!=null && $data['short_title']!="")
                    $nodehtml.='<li data-jstree=\'{ "icon" : "fa fa-group icon-state-success" }\'><a onclick="mynode('.$data['id'].')">'.$data['short_title'].'</a></li>';
                else
                    $nodehtml.='<li data-jstree=\'{ "icon" : "fa fa-group icon-state-success" }\'><a onclick="mynode('.$data['id'].')">'.$data['title'].'</a></li>';

            }
        }
    }

    // dump($html);
}

/**
*函数用途描述：构建组织节点（checkbox）
*@date：2016年11月15日 下午3:47:33
*@author：luoj
*@param：
*@return：
**/
function nodeListCheck($list,&$nodehtml){
// <input class="auth_groups" type="radio" name="group_id[]" value="24">
    if($list){
        
        foreach($list as $data){
             
            if($data['_']){
                $nodehtml.='<li data-jstree=\'{"opened" : true , "icon" : "fa fa-group icon-state-success" }\'>'
                    .'<a onclick="mynode('.$data['id'].')">'.$data['title'].
                '<input id="nodeId_'.$data['id'].'" onclick="mynode1('.$data['id'].')" class="auth_groups" type="checkbox" name="group_id[]" value="'.$data['id'].'">';
                $nodehtml.='</a><ul>';
                nodeListCheck($data['_'],$nodehtml);
                $nodehtml.='</ul></li>';
            }
            else{
                $nodehtml.='<li data-jstree=\'{ "icon" : "fa fa-group icon-state-success" }\'>'
                    .'<a onclick="mynode('.$data['id'].')">'.$data['title']
                    .'<input id="nodeId_'.$data['id'].'" onclick="mynode1('.$data['id'].')" class="auth_groups" type="checkbox" name="group_id[]" value="'.$data['id'].'"></a></li>';

            }
        }
    }

    // dump($html);
}

/**
* 函数用途描述:获取子节点的根节点
* @date: 2016年11月27日 下午3:07:26
* @author: luojun
* @param: 
* @return:
*/
function getRoot($db,$id){
    $path = array();
    $nav = M($db)->where("id=$id")->field('id,pid,title')->find();
    
    $root = $nav;
    if($nav['pid'] >0){
        $root = getRoot($db,$nav['pid']);
    }
    return $root;
}

/**
* 函数用途描述:获取子节点的根节点
* @date: 2016年11月27日 下午3:07:26
* @author: luojun
* @param: 
* @return:
*/
function getPath($db,$id){
    $path = array();
    $nav = M($db)->where("id=$id")->field('id,pid,title')->find();
    $path[] = $nav;
    if($nav['pid'] >0){
        $path = array_merge(getPath($db,$nav['pid']),$path);
    }
    return $path;
}

/**
 * 函数用途描述:获取子节点的上级节点
 * @date: 2016年11月27日 下午3:07:26
 * @author: luojun
 * @param:
 * @return:
 */
function getCatePath($db,$id,$cateid){
    $path = array();
    $nav = M($db)->where("id=$id")->field('id,pid,title,cate')->find();
    $path = $nav;
	$attr_cate= get_cate_attr($db,$id,$cateid);
	// dump($attr_cate);
    if($nav['pid'] >0&&$attr_cate['cate']!=$cateid){
		
        $path = getCatePath($db,$nav['pid'],$cateid);
       
    }
    return $path;
}


/**
* 函数用途描述: 判断节点属性类别
* @date: 2017年2月8日 上午9:42:56
* @author: luojun
* @param: 
* @return:
*/
function get_cate_attr($db,$id,$attr) {
    $path = array();
    $nav = M($db)->where("id=$id")->field('id,pid,title,cate')->find();
    $path = $nav;
    if($nav['cate'] >10&&$nav['cate']!=$attr){
        $path = get_cate_attr($db,$nav['cate'],$attr);
    }
    return $path;
}


/**
 * 函数用途描述：毫秒级时间戳
 * @date: 2016年10月15日 下午2:06:01
 * @author: luojun
 * @param:
 * @return:
 */
function microtimeStr()
{
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
}
/**
 * 函数用途描述：获取日常检查项最上级检查项id
 * @date: 2017年05月16日 下午3:36:01
 * @author: tanjiewen
 * @param:
 * @return:
 */
 function get_option_pid($id){
 	$data=M("jk_option")->where("id='$id'")->field('id,pid')->find();
 	if($data['pid']==0){
 		return $data['id'];
 	}else{
 		return get_option_pid($data['pid']);
 	}
 }
 /**
  * 函数用途描述：获取实测项最上级检查项id
  * @date: 2017年05月16日 下午3:36:01
  * @author: tanjiewen
  * @param:
  * @return:
  */
 function get_survey_pid($id){
 	$data=M("jk_survey_option")->where("id='$id'")->field('id,pid')->find();
 	if($data['pid']==0){
 		return $data['id'];
 	}else{
 		return get_survey_pid($data['pid']);
 	}
 }
 /**
  * 函数用途描述：计算合格率
  * @date: 2017年05月16日 下午3:36:01
  * @author: tanjiewen
  * @param:
  * @return:
  */
 function getrate($find){
 	//构建测量信息
 	$info=explode('|',$find['info']);
 	//实测选项
 	$optionDB=M('jk_survey_option');
 	$map= array();
 	$map['id'] = $find['inspect'];
 	$option = $optionDB->where($map)
 	->field('id,title,pid,minqualified,maxqualified,mindestroy,maxdestroy,pointlength')->find();
 	//计算合格率
 	$notqualified=0;//不合格数量
 	$totalnum=0;//总填写数量
 	foreach ($info as $v1){
 		if($v1!="" && $v1!="," && $v1!=",," && $v1!=",,," && $v1!=",,,,"){//未输入的值不显示
 			$totalnum+=1;	
 			//判断是否超过合格标准
 			if($option['pointlength']==1){
 				if ($option['maxdestroy'] != null || $option['mindestroy'] != null) {
 					if ($v1 > $option['maxdestroy'] || $v1 < $option['mindestroy']) {
 						$notqualified+=1;				
 					} else if ($v1 > $option['maxqualified'] * 1.0 || $v1 < ($option['minqualified'] * 1.0)) {
 						$notqualified+=1;				
 					}
 				} else//如果未设置则只判断是否需要整改
 				{ 	
 					if (($v1 > ($option['maxqualified'] * 1.0) || $v1 < ($option['minqualified'] * 1.0)) && $option['id'] != "14") {
 						//存入整改数组
 						$notqualified+=1;					
 					}	
 				}	
 			}
 			else
 			{
 				$nums=explode(',',$v1); 				 
 				$min=min($nums);
 				$max=max($nums);
 				$cha=$max-$min;
 				//var anarr=['4','38','39','40','41','42','43','44','45','46','47','48','49','50','51'];
 				//构建特殊项数组
 				$arr=array('4','38','39','40','41','42','43','44','45','46','47','48','49','50','51');
 				if(in_array($option['id'], $arr))//如果为特殊项，则为后一个减去前一个
 				{
 					$cha=$nums[1]-$nums[0];
 				}
 				if ($option['maxdestroy'] != null || $option['mindestroy'] != null) {
 						
 					if ($cha > $option['maxdestroy'] || $cha < $option['mindestroy']) {
 						//存入质量锤数组
 						$notqualified+=1;		 
 					} else if ($cha > $option['maxqualified'] * 1.0 || $cha < ($option['minqualified'] * 1.0)) {
 						//存入整改数组
 						$notqualified+=1;			
 					}
 	
 				} else//如果未设置则只判断是否需要整改
 				{		 
 					if ($cha > ($option['maxqualified'] * 1.0) || $cha < ($option['minqualified'] * 1.0)) {
 						//存入整改数组
 						$notqualified+=1;				
 					}
 				}		 
 			}
 		}
 		$i++;
 	}
 	$rate=($totalnum-$notqualified)/$totalnum*100;//合格率
 	$arr['nonum']=$notqualified;
 	$arr['totalnum']=$totalnum;
 	$arr['rate']=round($rate, 2);
 	return $arr;
 }
 /**
  * 函数用途描述：计算测量任务对应的测量图纸id和url
  * @date: 2017年07月3日 下午6:36:01
  * @author: tanjiewen
  * @param:项目id 位置id 检查项id 
  * @return:
  */
 function get_measure_id_url($projectid,$floorid,$measureid){
 	//根据检查项
 	$imgids="";
 	$imgurls="";
 	//根据检查项分割后遍历
 	$measureids=explode(",", $measureid);
 	foreach ($measureids as $v){
 		$where['project_id'] = $projectid;
 		$where['floor_id'] = $floorid;
 		$where['measure_id'] = $v;
 		$adata=M('jk_measure_image')->where($where)->field('id,imgurl')->find();
 		if($adata){
 			$imgids .= $adata['id'].",";
 			$imgurls .= $adata['imgurl'].",";
 		}else{
 			$imgids .= ",";
 			$imgurls .= ",";
 		}
 	}
 	$arr['imgid']=$imgids;
 	$arr['imgurl']=$imgurls;
 	return $arr;
 }
 /**
  * 函数用途描述：根据父类获取子级的id
  * @date: 2017年05月27日 下午2:06:01
  * @author: tanjiewen
  * @param:
  * @return:
  */
 function getids($pid,$type){
 	//根据type判断是日常巡查检查项还是实测实量检查项
 	if($type==0)
 		$table=M('jk_option');
 	else
 		$table=M('survey_option');
 	//根据父类id获取对应子级检查项
 	$list = $table->field('id,pid,sort')->select();
 	//实测项
 	$surveyInfo =list_to_tree($list, 'id', 'pid', '_',$pid);
 	$surveyLists =tree_to_listwx($surveyInfo, $child = '_');
 	$str="(";
 	foreach ($surveyLists as $surveyList){
 	    $str .= $surveyList['id'].",";    
 	}
 	$str=substr($str, 0,-1);
 	$str .= ")";
 	return $str;
 }
 /**
  * 函数用途描述：根据父类获取分户验收检查项子级的id
  * @date: 2017年06月21日 上午9:40:01
  * @author: tanjiewen
  * @param:  pid:父检查项id
  * @return: 子级ids
  */
 function get_check_ids($pid){
 	$table=M('jk_acoption');
 
 	//根据父类id获取对应子级检查项
 	$list = $table->field('id AS item_id,pid AS parent_id,sort AS sort_number')->select();
 	//实测项
 	$surveyInfo =list_to_tree($list, 'item_id', 'parent_id', '_',$pid);
 	$surveyLists=tree_to_list($surveyInfo,"_","item_id");
 //	$surveyLists =tree_to_listwx($surveyInfo, $child = '_');
 	$str="";
 	foreach ($surveyLists as $surveyList){
 		$str .= $surveyList['item_id'].",";
 	}
 	$str=substr($str, 0,-1);
 	return $str;
 }
 /**
  * 函数用途描述：根据项目构建项目列表中的数组
  * @date: 2017年10月23日
  * @author: tanjiewen
  * @param:  
  * @return: 
  */
 function sort_project($pros){
 	//分为有组织架构的和没组织架构的
 	$no_arr = array();
 	$or_arr = array();
 	$error_arr = array();
 	foreach ($pros as $v){
 		if(!$v['pid']){//无组织架构的
 			$no_arr[] = $v;
 		}else{	
 			//判断数组中是否存在该区域公司
 			$qv_id = get_auth_pid($v['pid'],2);
 			//如果是没挂的项目就放入no_arr
 			if(!$qv_id){
 				$no_arr[] = $v;
 				continue;
 			}
 			$is_error = get_error_pid($v['pid'],4);
 			if(!$is_error){
 				$error_arr[] = $v;
 				continue;
 			}
 			if(!is_array($or_arr[$qv_id])){
 				$or_arr[$qv_id]      = M('auth_group')->field('id,title')->where('status=1 and id='.$qv_id)->find();			
 				$or_arr[$qv_id]['_'] = array();  
 				
 			}
 			//判断数组中是否存在该城市公司
 			$cs_id = get_auth_pid($v['pid'],1);
 			if(!is_array($or_arr[$qv_id]['_'][$cs_id])){
 				$or_arr[$qv_id]['_'][$cs_id]      = M('auth_group')->field('id,title')->where('status=1 and id='.$cs_id)->find();
 				$or_arr[$qv_id]['_'][$cs_id]['_'] = array();
 			}

 			//查询分期，根据分期生成多条数据
 			$stages = M('jk_stage')->field('id,StagesCode,StagesName')->where("status=1 and ParentCode!='' and  ParentCode='".$v['ProjectNumber']."'")
			->order('UpdateDataTime DESC')->group('StagesCode')->select();
 			if($stages){
 				//构建项目-分期数组
 				foreach ($stages as $stage){
 					$v['StagesCode'] = $stage['StagesCode'];
 					$v['StagesName'] = $stage['StagesName'];
 					$or_arr[$qv_id]['_'][$cs_id]['_'][$stage['StagesCode']] = $v;
 				}
 			
 			}else{
 				$v['StagesName'] = '暂无分期';
 				$or_arr[$qv_id]['_'][$cs_id]['_'][$v['id']] = $v;
 			}
 			
 		}
 	
 	}

 	$arr['or_arr']    = $or_arr;
 	$arr['no_arr']    = $no_arr;
 	$arr['error_arr'] = $error_arr;
 	return $arr;
 }
 /**
  * 函数用途描述：根据项目的所属用户组查询上级用户组
  * @date: 2017年10月23日
  * @author: tanjiewen
  * @param: $id:用户组ID  level:向上层级 
  * @return: 上级的ID
  */
 function get_auth_pid($id,$level=1){
 	
 	for($i=0;$i<$level;$i++){
 		$id = M('auth_group')->where("status=1 and id=".$id)->getField('pid');
 	}
 	return $id;
 	
 }
 /**
  * 函数用途描述：根据项目的所属用户组查询是否为错误组织架构
  * @date: 2017年10月23日
  * @author: tanjiewen
  * @param: $id:用户组ID  level:向上层级
  * @return: 上级的ID
  */
 function get_error_pid($id,$level=4){
 
 	for($i=0;$i<$level;$i++){
 		if($id<=0){
 			return false;
 		}
 		$id = M('auth_group')->where("status=1 and id=".$id)->getField('pid');
 		
 	}
 	if($id!=0){
 		return false;
 	}else{
 		return true;
 	}

 }

/**
 * 函数用途描述：在页面console输出调试信息
 * @date: 2018年5月23日
 * @author: luoj
 * @param:
 * @return:
 */
function js_log($msg){
    echo '<script language="JavaScript">';
    echo "console.log('$msg')";
    echo '</script>';
}
