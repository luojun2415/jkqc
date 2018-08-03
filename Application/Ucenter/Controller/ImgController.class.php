<?php 
namespace Ucenter\Controller;

use Think\Controller;
class ImgController extends Controller{
	public $access_token;
	public function _initialize() {
	
		$api_key='GuW3c7iT7VRQu54VIQwGrSzE';
		$api_secret='O67ipD9lL8w1N2wQe0OeQdyvAFHhexfa';
		$grant_type='client_credentials';
		$data='grant_type='.$grant_type.'&client_id='.$api_key.'&client_secret='.$api_secret;
		//获取token
		$ret=$this->https_post('https://openapi.baidu.com/oauth/2.0/token ', $data);
		//var_dump($ret);
		$respon=json_decode($ret,true);
		$this->access_token=$respon['access_token'];
	}
	public function index(){
	
	
	}
	
	public function add(){
		if(IS_POST){
			$data=array();
			$data['keyword'] = $_POST['keyword'];//关键词
			$data['precisions'] = $_POST['precisions'];//关键词类型
			$data['writer'] = $_POST['writer'];//作者
				
			$data['text'] = $_POST['text'];//简介
			$data['pic'] = $_POST['pic'];//封面图片地址
			$data['showpic'] = $_POST['showpic'];//是否显示封面
				
			$data['is_focus'] = $_POST['is_focus'];//是否关注
			$data['info'] = $_POST['info'];
			$data['voicetype'] = $_POST['voicetype'];//声音类行
			$isId = M('img')->data($data)->add();
			if($isId){
				$this->success('操作成功', U('Index/index'));
			} else {
				$this->error('图文新增失败');
			}
		}
	
		$this->display();
	}
	
	public function edit(){
		if (!isset($_SESSION['account'])) {
			$this->redirect(U('Member/login',array('token'=>$token)));
		}
		$token=$_GET['token'];
		$id = $_GET['id'];
		if(IS_POST){
			$id = $_POST['id'];
			$data=array();
			//$data['keyword'] = $_POST['keyword'];//关键词
			//$data['precisions'] = $_POST['precisions'];//关键词类型
			$uid = $_SESSION['id'];//作者
			$data['writer'] = M('VcardUserdetail')->where("loginid=$uid")->order('id asc')->getField('id');
			$data['title'] = $_POST['title'];
			$data['text'] = $_POST['text'];//简介
			$data['pic'] = $_POST['pic'];//封面图片地址
			$data['showpic'] = false;//是否显示封面
			$data['tag'] = $_POST['tag'];//分类
			$data['is_focus'] = false;//是否关注
			$data['info'] = $_POST['info'];
				
			$data['token'] = $token;
			if(!$data['title']||!$data['text']||!$data['pic']||!$data['tag']||!$data['info']){
				$this->error('数据未填写完整！');
			}
			$type = $_POST['voicetype'];//声音类型
			$ret=$this->getVoice($data['info'],$type);
			if($ret['err_msg'])
				$this->error($ret['err_msg']);
			$data['voice']=$ret['url'];
			$myshow=M('Classify')->where("name='脉秀' and token='$token'")->find();
			//var_dump($myshow.M('Classify')->_sql());
			if($myshow){
				$data['classid'] = $myshow['id'];//脉秀文章分类
				$data['classname'] = $myshow['name'];
				if($id){
					$data['uptatetime'] = time();
					M('Img')->where("id=$id")->save($data);
				} else {
					$data['createtime'] = time();
					$re=M('Img')->add($data);
					if ($re) {
						mc_add_action($re, 'publish', $data['tag'],$_SESSION['id']);//发布文章action
					}
					else
						$this->error('插入数据错误',U('article/index',array('token'=>$token)));
					//var_dump(M('Img')->_sql());
				}
				$this->success('编辑成功', U('article/index',array('token'=>$token)));
			}
			//dump($myshow);
			else
				$this->error('数据错误', U('article/index',array('token'=>$token)));
		}else {
			$info = M('img')->where("id= $id")->find();
			$this->assign('info',$info);
			$this->assign('token',$token);
			$this->display();
		}
	}
	public function del(){
	
	}
	public function insert(){
		//dump($_POST['voicetype']);
		$info=$_POST['info'];
		$type=$_POST['voicetype'];
		if($info&&$type>=0){
			$ret=$this->getVoice($info,$type);
			if($ret['err_msg'])
				$this->error($ret['err_msg'],U('Img/index'));
			$_POST['voice']=$ret['url'];
			 
		}
		parent::insert();
	}
	public function upsave(){
		// dump($_POST['voicetype']);
		//dump(M('Img')->getDbFields());
		$info=$_POST['info'];
		$type=$_POST['voicetype'];
		if($info&&$type>=0){
			$id=$_POST['id'];
			$find=M('Img')->where("id=$id")->getField('info');
			if ($info!=$find) {
				$ret=$this->getVoice($info,$type);
				if($ret['err_msg'])
					$this->error($ret['err_msg'],U('Img/index'));
				$_POST['voice']=$ret['url'];
			}
		}
		 
		parent::upsave();
	}
	public function editClass(){
	
		$this->display();
	}
	public function editUsort(){
	
	}
	public function multiImgDel(){
	
	}
	public function multi(){
	
		$this->display();
	}
	public function multiSave(){
		parent::multiSave();
	}
	public function diyTool(){
		$this->display();
	}
	
	public function getVoice($content,$type=''){
		if ($content&&$this->access_token) {
			//语音测试
			$mac = new GetMacAddr(PHP_OS);
			//echo $mac->mac_addr;
	
			$info=html_entity_decode($content);
			$info = preg_replace("/<style>.+<\/style>/is", "", $info); //-----删除<style></style>和中间的部分
			$msg = preg_replace("/<[^>]+>/", "", $info); //-----是删除<>和中间的内容
			$msg = str_replace("&nbsp;", "", $msg); //-----是删除' '空格
	
			$replace = array('◆','♂','$','￥','[','/');
			$msg = str_replace($replace, "", $msg); //-----是删除' '空格
			$msg = str_replace("]", " ", $msg); //-----是删除' '空格
			$msg = str_replace("%", "个百分点", $msg);
			//$msg = str_replace("+", "加", $msg);
			// $msg = str_replace("+", "减", $msg);
			$len=mb_strlen( $msg, 'utf-8' ) ;
			//var_dump($len);
			$i=1;
			if ($len>512) {
				$i+=intval($len/512);
			}
	
			$voiceurl='http://tsn.baidu.com/text2audio';
	
			$lan='zh';//必填 	语言选择,填写zh
			$tok=$this->access_token;// 	必填 	开放平台获取到的开发者 access_token
			$ctp='1';// 	必填 	客户端类型选择，web端填写1
			$cuid=$mac->mac_addr;// 	必填 	用户唯一标识，用来区分用户，填写机器 MAC 地址或 IMEI 码，长度为60以内
			$spd='5';// 	选填 	语速，取值0-9，默认为5中语速
			$pit='5';// 	选填 	音调，取值0-9，默认为5中语调
			$vol='5';// 	选填 	音量，取值0-9，默认为5中音量
			$per=$type;// 	选填 	发音人选择，取值0-1, 0为女声，1为男声，默认为女声
			$j=0;
			$dir=date("Ymd",time());
			$name='./Uploads/'.$dir.'/content'.time().'.mp3';
			unlink($name);
			if (!file_exists('./Uploads/'.$dir)){ mkdir ('./Uploads/'.$dir);}
			while(($i-$j)>0){
				$tex=mb_substr($msg,$j*512,512,'utf-8');
				$j++;
				$data='tex='.$tex.'&lan='.$lan.'&tok='.$tok.'&ctp='.$ctp.'&cuid='.$cuid.'&spd='.spd.'&pit='
						.$pit.'&vol='.$vol.'&per='.$per;//默认女声
	
				//获取语音
				//var_dump($tex);
				$ret=$this->https_post($voiceurl, $data);
	
	
				//             var_dump($ret);
				$file  = $name;//要写入文件的文件名（可以是任意文件名），如果文件不存在，将会创建一个
				$f  = file_put_contents($file, $ret, FILE_APPEND);// 这个函数支持版本(PHP 5)
				$respon=json_decode($ret,true);
				if($respon['err_no']){
					return $respon;
				}
			}
			return array('url'=>$name);
		}
		return array('err_msg'=>'传入参数为空！');
	}
	public function https_post($url,$data)
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
}   //imgController结束

/**
 获取网卡的MAC地址原码；目前支持WIN/LINUX系统
 获取机器网卡的物理（MAC）地址
 **/
class GetMacAddr{
	var $return_array = array(); // 返回带有MAC地址的字串数组
	var $mac_addr;
	function GetMacAddr($os_type){
		switch ( strtolower($os_type) ){
			case "linux":
				$this->forLinux();
				break;
			case "solaris":
				break;
			case "unix":
				break;
			case "aix":
				break;
			default:
				$this->forWindows();
				break;
	
		}
		$temp_array = array();
		foreach ( $this->return_array as $value ){
		
			if (
					preg_match("/[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f]/i",$value,
							$temp_array ) ){
				$this->mac_addr = $temp_array[0];
				break;
			}
		
		}
		unset($temp_array);
		return $this->mac_addr;
	}
	function forWindows(){
		@exec("ipconfig /all", $this->return_array);
		if ( $this->return_array )
			return $this->return_array;
		else{
			$ipconfig = $_SERVER["WINDIR"]."\system32\ipconfig.exe";
			if ( is_file($ipconfig) )
				@exec($ipconfig." /all", $this->return_array);
			else
				@exec($_SERVER["WINDIR"]."\system\ipconfig.exe /all", $this->return_array);
			return $this->return_array;
		}
	}
	function forLinux(){
		@exec("ifconfig -a", $this->return_array);
		return $this->return_array;
	}
	
}

?>