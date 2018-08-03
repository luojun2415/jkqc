<?php
/**
 * 时间：2016-4-7
 * 微名片前端展示control
 * 
 */
namespace Ucenter\Controller;

use Think\Controller;

class VcardController extends Controller{
	
	function __construct(){
		parent::__construct();
		import("@.ORG.WechatShare");
		$appdata=M('Wxuser')->where("token='$this->token'")->find();
		$share 	= new WechatShare($appdata['appid'],$appdata['appsecret'],$this->token,$this->wecha_id);
		$this->shareScript=$share->getSgin();
		$this->hideScript=$share->getSgin1();
		$this->assign('shareScript',$this->shareScript);
		$this->assign('hideScript',$this->hideScript);
	}
	
	/**
	 * 15s更新一次页面停留时间
	 */
	public function showTime(){
		$id = $_POST['id'];
		$db = M('vcard_userdetail');
		$showtime = $db->where("id=$id")->getfield('showtime');
		$newtime = $showtime+15;
		$db-> where("id=$id")->setField('showtime',$newtime);
	}
	
	/**
	 * 手机创建名片
	 */
	public function cardcreate(){
		$id=$_GET['id'];
		$token=$_GET['token'];
		$this->assign("title","编辑名片");
		$this->assign("token",$token);
		if($id == null){
			$id=$_SESSION['id'];
			$userinfo = M('vcard_userdetail')->where("loginid = $id and name = ''")->find();
			//dump(M('vcard_userdetail')->getLastSql());
			if($userinfo != null){//如果之前有创建一张空信息的名片就不在继续新建
				$id = $userinfo['id'];
				$this->assign('userid',$id);
				$this->assign('userinfo',$userinfo);
				$this->display();
			}else{//新增名片表记录
				$data['loginid'] = $id;
				$data['id'] = time();
				M('vcard_userdetail')->data($data)->add();
				$this->assign('userid',$data['id']);
				$this->display();
			}
		} else{
			$db = M('vcard_userdetail');
			$userinfo = $db->where("id=$id")->find();
			$this->assign('userid',$id);
			$this->assign('userinfo',$userinfo);
			$this->display();
		}
	}
	
	/**
	 * 页面编辑
	 * 传入ID值表示进行修改，没有传入表示新建
	 */
	public function edit(){
		$id=$_GET['id'];
		if($id == null){
			$id=$_SESSION['id'];
			$userinfo = M('vcard_userdetail')->where("loginid = $id and name = ''")->find();
			//dump(M('vcard_userdetail')->getLastSql());
			if($userinfo != null){//如果之前有创建一张空信息的名片就不在继续新建
				$id = $userinfo['id'];
				$this->assign('userid',$id);
				$this->assign('userinfo',$userinfo);
				$this->display();
			}else{//新增名片表记录
				$data['loginid'] = $id;
				$data['id'] = time();
				M('vcard_userdetail')->data($data)->add();
				$this->assign('userid',$data['id']);
				$this->display();
			}
		} else{
			$db = M('vcard_userdetail');
			$userinfo = $db->where("id=$id")->find();
			//单位下方自定义字段
			$diyInfo = getMetaArray($id, "cdiy");
			//联系方式自定义字段
			$lxfsInfo = getMetaArray($id, "lxfs");
			//邮箱自定义字段
			$emailInfo = getMetaArray($id, "email");
			//网址自定义字段
			$siteInfo = getMetaArray($id, "site");
			//地址自定义字段
			$addressInfo = getMetaArray($id, "address");
			//传真自定义字段
			$faxInfo = getMetaArray($id, "fax");
			$this->assign('userid',$id);
				
			$this->assign('addInfo',$diyInfo);
			$this->assign('addInfo1',$diyInfo);
				
			$this->assign('lxfsInfo',$lxfsInfo);
			$this->assign('lxfsInfo1',$lxfsInfo);
				
			$this->assign('faxInfo',$faxInfo);
			$this->assign('faxInfo1',$faxInfo);
				
			$this->assign('emailInfo',$emailInfo);
			$this->assign('emailInfo1',$emailInfo);
				
			$this->assign('siteInfo',$siteInfo);
			$this->assign('siteInfo1',$siteInfo);
				
			$this->assign('addressInfo',$addressInfo);
			$this->assign('addressInfo1',$addressInfo);
				
			$this->assign('userinfo',$userinfo);
				
			$this->display();
		}
	}
	
	/**
	 * 名片展示
	 * 每次打开名片页需要做的逻辑处理有
	 * 1、访问数+1；
	 * 2、更新访问日志表；
	 * 3、根据日志表更新访客数；
	 * 4、更新页面停留时间，小于15s按15s计算；
	 */
	public function cardShow() {
		$wecha_id=$this->wecha_id;
		$id=$_GET['id'];
		//获取用户OpenID用于判定用户关注，如果不为空或者用户处登录状态查看名片则村一条Action，默认其关注此名片
		$ViewId = M('member')->where("wecha_id = '$wecha_id'")->getField('id');
		if ($ViewId != "" && $ViewId != null){
			$ViewId = $ViewId;
		}else{
			$ViewId = $_SESSION['id'];
		}
		if($ViewId != "" && $ViewId != null){
			//判断先前是否有关注，如果没有，则关注，有跳过
			$aid=M('Action')->where("action_key='guanzhu' and user_id= '$ViewId' and action_value='1' and page_id = '$id'")->getField('id',true);
			if(!$aid){
				mc_add_action($id, "guanzhu", "1",$ViewId);
			}
		}
	
		$db = M('vcard_userdetail');
		$userinfo = $db->where("id=$id")->find();
		$viewnum = $userinfo['viewnum'];
		if ($viewnum == null) {
			$viewnum = 0;
		}
		//访问次数+1
		$viewnum++;
		//访问时间
		$showtime = $userinfo['showtime'];
		if ($showtime == "") {
			$showtime = 0;
		}
		$newtime = $showtime+15;
	
		//访问地区以及设备型号存日志
		$obj = new MemberAction();
		$loginid = $userinfo['loginid'];
		$obj->getView($loginid,$id);
		//通过日志表ip数更新访客数量
		$storeObj = new Model ();
		$sql="SELECT COUNT(*) FROM(SELECT ip FROM irosn_vcard_viewlogs WHERE targetid = $id GROUP BY ip) AS num";
		$result = $storeObj->query($sql);
		$visitornum =$result[0]['COUNT(*)'];
		$data = array('viewnum'=>$viewnum,'showtime'=>$newtime,'visitornum'=>$visitornum);
		$db->where("id=$id")->setField($data);
		//分享功能
		$url=C('SITE_DOMAIN');
		$this->assign('url',$url);
		//		import("@.ORG.WechatShare");
		// 		$share = new WechatShare('wxbf7392ebd1782686','4b7baac9e61b5be660c8144bcea9ac66',"eeodbq1422333040","oZX3Ws6YW_mPOWzTAM3jqXshmcQU",$id);
		// 		$this->shareScript=$share->getSgin();
		// 		$this->assign('shareScript',$this->shareScript);
		//单位下方自定义字段
		$diyInfo = getMetaArray($id, "cdiy");
		//联系方式自定义字段
		$lxfsInfo = getMetaArray($id, "lxfs");
		//邮箱自定义字段
		$emailInfo = getMetaArray($id, "email");
		//网址自定义字段
		$siteInfo = getMetaArray($id, "site");
		//地址自定义字段
		$addressInfo = getMetaArray($id, "address");
		//传真自定义字段
		$faxInfo = getMetaArray($id, "fax");
	
		$this->assign('addInfo',$diyInfo);
		$this->assign('lxfsInfo',$lxfsInfo);
		$this->assign('faxInfo',$faxInfo);
		$this->assign('emailInfo',$emailInfo);
		$this->assign('siteInfo',$siteInfo);
		$this->assign('addressInfo',$addressInfo);
		$this->assign('userinfo',$userinfo);
	
		$tplId = $userinfo['selectedtpl'];
		if($tplId == '1'){
			$this->display('./Apps/Tpl/default/Vcard/cardshow_qingxin.html');
		}elseif ($tplId == '3'){
			$this->display('./Apps/Tpl/default/Vcard/cardshow_jianyue.html');
		}elseif ($tplId == '4'){
			$this->display('./Apps/Tpl/default/Vcard/cardshow_ios.html');
		}else{
			$this->display();
		}
	}
	
	/**
	 * 地图定位（高德地图）
	 */
	public function mapLocation() {
		$lat= $_GET['lat'];
		$lng = $_GET['lng'];
		$this->assign('lat',$lat);
		$this->assign('lng',$lng);
		$this->display();
	}
	
	/**
	 * 通讯录
	 */
	public function myusers() {
		if(isset($_SESSION['account']))
		{
			$user=$_SESSION['id'];
			$find=M('Action')->where("action_key='guanzhu' and user_id=$user")->getField('page_id',true);
				
			foreach ($find as $v){
				$list[]=$v;
			}
			$map['name'] != '';
			$map['id']=array('in',$list);
			$data=M('VcardUserdetail')->field('id,img,name')->where($map)->select();
			foreach ($data as $name){
				$index[]=mb_substr( $name['name'], 0, 1, 'utf-8');
			}
				
				
			foreach ($index as $v)
			{
				if(!in_array($v,$k) && !empty($v)){
					$v = iconv('UTF-8', 'GBK', $v);
					$k[] = $v;
				}
			}
			asort($k);
			foreach($k as $v){
				$v = iconv( 'GBK', 'UTF-8',$v);
				if(!in_array($v,$tlist)){//转回来仍然要去重
					$tlist[]=$v;//这里重新给个数组就是为了获得新的KEY，后面模板输出要用到换行
				}
			}
			foreach ($tlist as $t){
				foreach ($data as $n){
					$in=mb_substr( $n['name'], 0, 1, 'utf-8');
					 
					if($t==$in){
						$info[$t][]=$n;
					}
				}
			}
	
			//var_dump($data);
			$this->assign('index',json_encode($tlist));
			$this->assign('info',$info);
			$this->assign('token',$this->token);
			$this->assign('title','通讯录');
			$this->display();
		}
		else{
			$this->redirect('Member/login',array('token'=>$_GET['token']));
		}
	}
	
	/**
	 * 地图定位（百度地图）
	 */
	public function baidumapLocation() {
		$lat= $_GET['lat'];
		$lng = $_GET['lng'];
		$this->assign('lat',$lat);
		$this->assign('lng',$lng);
		$this->display();
	}
	
	public function editmap() {
	
		$this->display();
	}
	
	/**
	 * 删除名片
	 */
	public function deleteCard() {
		$id = $_POST['id'];
		if($id){
			M('vcard_userdetail')->where("id=$id")->delete();
			echo 1;
		} else {
			echo 0;
		}
			
	}
	
	/**
	 * 储存的方法
	 */
	public function cardSave() {
		$db = M('vcard_userdetail');
		$data=array();
		$id = $_POST['id'];
		$data['id'] = dowith_sql($_POST['id']);
		$data['name'] = dowith_sql($_POST['name']);
		$data['company'] = dowith_sql($_POST['company']);
		$data['job'] = dowith_sql($_POST['job']);
		$data['mobile'] = dowith_sql($_POST['mobile']);
		$data['tel'] = dowith_sql($_POST['tel']);
		$data['fax'] = dowith_sql($_POST['fax']);
		$data['qq'] = dowith_sql($_POST['qq']);
		$data['wechat'] = dowith_sql($_POST['wechat']);
		$data['address'] = dowith_sql($_POST['address']);
		$data['description'] = dowith_sql($_POST['description']);
		$data['img'] = dowith_sql($_POST['img']);
		$data['selectedtpl'] = dowith_sql($_POST['selectedtpl']);
		$data['email'] = dowith_sql($_POST['email']);
		$data['weibo'] = dowith_sql($_POST['weibo']);
	
		$site = dowith_sql($_POST['site']);
	
		$site = str_replace("http://", "",$site);
		$site = str_replace("https://", "",$site);
	
		$data['site'] = $site;
		$data['showbg'] = dowith_sql($_POST['showbg']);
		$data['lat'] = $_POST['lat'];
		$data['lng'] = $_POST['lng'];
		$data['loginid'] = $_SESSION['id'];
		//通讯录二维码
		$errorCorrectionLevel ="L";
		$str = "BEGIN:VCARD
VERSION:3.0
N:".$data['name']."
TEL;TYPE=CELL;VOICE:".$data['mobile']."
EMAIL;TYPE=PREF,INTERNET:".$data['email']."
URL;WORK:http://".$_SERVER['SERVER_NAME']."/myshow/index.php?m=vcard&a=cardShow&id=".$id."
ORG:".$data['company']."
TITLE:".$data['job'] ."
ADR;TYPE=WORK:;;".$data['address']."
NOTE;ENCODING=QUOTED-PRINTABLE:".$data['description']."
END:VCARD";
	
		// 		POSTAL:".$data['company']."
		//美化地址名片
		$qr1 = "http://".$_SERVER['SERVER_NAME']."/myshow/index.php?m=Vcard&a=cardShow&id=".$id;
		$linkqrcode ="./vcardQrcode/".$id."linkqrcode.png";
		$this->qrBeautify($data['id'],$linkqrcode,$qr1,$data['img']);
		//美化通讯录名片
		$vcfqrcode="./vcardQrcode/".$id."vcfqrcode.png";
		$this->qrBeautify($data['id'],$vcfqrcode,$str,$data['img']);
		$data['linkqrcode'] = $linkqrcode;
		$data['vcfqrcode'] = $vcfqrcode;
		$db->where("id=$id")->save($data);
		//存cookie
		setcookie("cardid",$id,0);
		$_COOKIE["cardid"]=$id;
	
		echo $linkqrcode;
	}
	
	/**
	 * 登出
	 */
	public function logo_out() {
		unset($_COOKIE);
		$this->success('退出成功',U("Card/VcardUser/login"));
	}
	/**
	 * 美化二维码
	 */
	public function qrbeauty(){
		if(IS_POST){
			$img = $_POST['img'];
	
		}
		$this->display();
	}
	/**
	 * 生成默认二维码
	 */
	public function qrBeautify($id,$type,$str,$img) {
		//设置附件上传目录
		import("@.ORG.qrcode_img");
		$z=new Qrcode_image;
		$level ="L";
		$z->set_qrcode_error_correct($level);   # set ecc level H
		$pt='#000000';
		$inpt='#000000';
		$f='#000000';
		$b='#FFFFFF';
		$s=1;
		$z->qrcode_image_out($str,$type,380,$img,$filebg,$pt,$inpt,$f,$b,'#000000',$s);
	}
	
	/**
	 *上传图片
	 */
	public function uploadimg(){
		$type=$_GET['type'];
		$id=$_GET['id'];
		$res["error"] = "";//错误信息
		$res["msg"] = "";//提示信息
		$photo_types=array('image/jpg', 'image/jpeg','image/png','image/pjpeg','image/gif','image/bmp','image/x-png');//定义上传格式
		$max_size=1021*1021*2;    //上传照片大小限制,默认700k
		$photo_folder="uploads/".$this->user_id."/"; //上传照片路径
		///////////////////////////////////////////////////开始处理上传
		if(!file_exists($photo_folder))//检查照片目录是否存在
		{
			mkdir($photo_folder, 0777, true);  //mkdir("temp/sub, 0777, true);
		}
		$upfile=$_FILES['fileToUpload'];
		$name=$upfile['name'];
		//$type=$upfile['type'];
		$size=$upfile['size'];
		$tmp_name=$upfile['tmp_name'];
		$file = $_FILES["fileToUpload"];
		$photo_name=$file["tmp_name"];
		$photo_size = getimagesize($photo_name);
		if($max_size < $file["size"])//检查文件大小
			$res["error"] = "文件必须小于2M";       //echo "<script>alert('对不起，文件超过规定大小!');history.go(-1);</script>";
		else if(!in_array($file["type"], $photo_types))//检查文件类型
			$res["error"] = "必须选择图片（jpeg,png）";       //echo "<script>alert('对不起，文件类型不符!');history.go(-1);</script>";
		else{
			$pinfo=pathinfo($file["name"]);
			$photo_type=$pinfo['extension'];//上传文件扩展名
			$photo_server_folder = $photo_folder.time().".".$photo_type;//以当前时间和7位随机数作为文件名，这里是上传的完整路径
			$pinfo=pathinfo($photo_server_folder);
			$fname=$pinfo['basename'];
		  
			if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'],$photo_server_folder)){
				if ($type=="coverImag") {
					$Max_width=640;
					$Max_height=1020;
					//$res["error"] = $thumb;
				}
				if ($type=="image") {
					$Max_width=200;
					$Max_height=200;
					//$res["error"] = $thumb;
				}
				if ($type=="face") {
					$Max_width=300;
					$Max_height=300;
					//$res["error"] = $thumb;
				}
				if ($type=="logo"||$type=="logo2"||$type=="logo3") {
					$Max_width=200;
					$Max_height=200;
					//$res["error"] = $thumb;
				}
				if ($type=="site") {
					$Max_width=400;
					$Max_height=600;
					//$res["error"] = $thumb;
				}
				if ($type=="wx_qrcode"||$type=="com1_qrcode"||$type=="com3_qrcode"||$type=="com2_qrcode") {
	
					$Max_width=500;
					$Max_height=500;
					//$res["error"] = $thumb;
				}
				$thumb=$photo_folder.time().$type.".".$photo_type;
				//$res["error"] = $type;
				import("@.ORG.Util.Image");
				$Img = new Image();//实例化图片类对象
				$image_path = './图片路径';//若当前php文件在Thinkphp的中APP_PATH路径中，'./'就是index.php的上一级文件。因为APP_PATH是通过index.php定义和加载的。
				$image_info = $Img::getImageInfo($image_path);//获取图片信息
				//生成缩略图:
				if($file["size"]<(100*1024)){
					$thumb=$photo_server_folder;
				}
				else{
					$Img::thumb($photo_server_folder,$thumb,$photo_type,$Max_width,$Max_height);
					imagedestroy($photo_server_folder);
					@unlink ($photo_server_folder);
				}
				$db = M('vcard_userdetail');
				//   $data['img']= $thumb;
				//   $data['id']= $id;
				$find = $db ->where("id=$id")->select('img');
				if($find){
					@unlink ($find);
				}
				//   $db ->where("id=$id")->save($data);
				$res["msg"] = $thumb;
			}else{
				//$res["msg"] = $thumb;
				$res["error"] = "图片传输失败";
			}
		}
		echo json_encode($res);
		return;
	}
	/**
	 * 自定义字段处理
	 */
	public function diySave(){
		$card_id =  dowith_sql($_POST['card_id']);
		$meta_key = dowith_sql($_POST['meta_key']);
		$meta_value = dowith_sql($_POST['meta_value']);
		$type = dowith_sql($_POST['type']);
		$tid = dowith_sql($_POST['tid']);
		$inid = dowith_sql($_POST['inid']);
		$meta_id = saveMeta($card_id, $meta_key, $meta_value,$type,$tid,$inid);
		echo $meta_id;
	}
	
	/**
	 * 自定义字段删除
	 */
	public function deleteDiy(){
		$id =  $_POST['id'];
		deleteMeta($id);
	}
	
	/**
	 * 模版选择
	 */
	public function selecttpl(){
		$db = M('vcard_template');
		//获取免费模板
		$tpls = $db->where("price = '0'")->select();
		$this->assign("tpls",$tpls);
		//菜单列表
		$infoTitle = $db->where("price = '0'")->field("id,name")->select();
		//var_dump($db->_sql());
		$this->assign("infoTitle",$infoTitle);
		$this->display();
	}
	/**
	 * 模版选择
	 */
	public function mobileselecttpl(){
		$card_id=$_GET['card_id'];
		$db = M('vcard_template');
		//获取免费模板
		$tpls = $db->where("price = '0'")->select();
		$this->assign("tpls",$tpls);
		//菜单列表
		$infoTitle = $db->where("price = '0'")->field("id,name")->select();
		//var_dump($db->_sql());
		$this->assign("infoTitle",$infoTitle);
		$this->assign("title","选择模版");
		$this->assign("card_id",$card_id);
		$this->display();
	}
	public function selectedtpl(){
		$db = M('vcard_userdetail');
		$id = $_POST['card_id'];
		$selectedtpl = $_POST['selectedtpl'];
		$db->where("id=$id")->setField('selectedtpl',$selectedtpl);
		echo 1;
	}
	public function test(){
		$this->display();
	}
		
}