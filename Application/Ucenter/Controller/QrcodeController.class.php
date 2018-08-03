<?php 
namespace Ucenter\Controller;

use Think\Controller;

class QrcodeController extends Controller{
	public function _before_index(){
		session('_currentUrl_', __SELF__);
	}
	
	//根据模板创建二维码
	public function tpcreate() {
		 
		$text=$_POST["t"];
	
		$map['id']=$_POST['id'];
		$Qrcode=D('Qrcode');
		$data=$Qrcode->where($map)->find();
		$f=$data['fcolor'];
		$b=$data['bcolor'];
		$pt=$data['ptcolor'];
		$inpt=$data['inptcolor'];
		$s=  intval($data['style']);
		$level=$data['level'];
		if(!empty($data['logopath'])){
			$filelogo='Uploads'.$data['logopath'];
		}
		if(!empty($data['bgpath'])){
			$filebg='Uploads'.$data['bgpath'];
		}
	
		//生成二维码
		import("@.ORG.qrcode_img");
		//设置附件上传目录
		$y = date('Y',time());
		$m = date('m',time());
		$d = date('d',time());
	
		$dir='Uploads/qrcodetemp';
	
		if (!is_dir($dir)) {
			mkdir($dir, 0777);
		}
		$dir.='/'.$y;
		if (!is_dir($dir)) {
			mkdir($dir, 0777);
		}
		//删除本月以前的临时文件
		for($i=1;$i<$m;$i++){
			$dirm=$dir.'/'.$i;
			if(is_dir($dirm)){
				delete_dir($dirm);
			}
	
		}
		$dir.='/'.$m;
		if (!is_dir($dir)) {
			mkdir($dir, 0777);
	
		}
		//删除今天以前的临时文件
		for($i=1;$i<$d;$i++){
			$dird=$dir.'/'.$i;
			if(is_dir($dird)){
				delete_dir($dird);
			}
	
		}
		$dir.='/'.$d;
		if (!is_dir($dir)) {
			mkdir($dir, 0777);
		}
		$dir.='/';
		$filename=$dir.uniqid().'.png';
	
		$z=new Qrcode_image;
	
		//$z->set_qrcode_version($_GET['id']);           # set qrcode version 1
		$z->set_qrcode_error_correct($level);   # set ecc level H
		//$z->set_module_size(4);              # set module size 3pixel
		//$z->set_quietzone(5);                # set quietzone width 5 modules
	
		$z->qrcode_image_out($text,$filename,380,$filelogo,$filebg,$pt,$inpt,$f,$b,'#000000',$s);
	
		$this->ajaxReturn($filename);
	}
	//创建二维码
	public function create() {
	
		//生成二维码
		import("@.ORG.qrcode_img");
	
		if(isset($_POST["t"])){
			$data=$_POST["t"];
		}else{
			$data="欢迎访问脉秀网，http://www.irosn.com";
		}
		if(isset($_POST["f"])){
			$f=$_POST["f"];
		}else{
			$f='#B89537';
		}
		if(isset($_POST["b"])){
			$b=$_POST["b"];
		}else{
			$b='#FFFFFF';
		}
		if(isset($_POST["pt"])){
			$pt=$_POST["pt"];
		}else{
			$pt='#FF0000';
		}
		if(isset($_POST["inpt"])){
			$inpt=$_POST["inpt"];
		}else{
			$inpt='#3980F4';
		}
		//样式状态 液态 直角 圆圈
		if(isset($_POST["s"])){
			$s=intval($_POST["s"]);
		}else{
			$s=1;
		}
		//纠错等级
		if(isset($_POST["level"])){
			$level=$_POST["level"];
		}else{
			$level="L";
		}
	
		if(session('?logo')){
			$filelogo= 'Uploads/'.session('logo');
	
		}else{
			$filelogo='';
		}
		if(session('?bg')){
			$filebg= 'Uploads/'.session('bg');
	
		}else{
			$filebg='';
		}
		//设置附件上传目录
		$y = date('Y',time());
		$m = date('m',time());
		$d = date('d',time());
	
		$dir='Uploads/qrcodetemp';
	
		if (!is_dir($dir)) {
			mkdir($dir, 0777);
		}
		$dir.='/'.$y;
		if (!is_dir($dir)) {
			mkdir($dir, 0777);
	
		}
		//删除本月以前的临时文件
		for($i=1;$i<$m;$i++){
			$dirm=$dir.'/'.$i;
			if(is_dir($dirm)){
				delete_dir($dirm);
			}
	
		}
		$dir.='/'.$m;
		if (!is_dir($dir)) {
			mkdir($dir, 0777);
	
		}
		//删除今天以前的临时文件
		for($i=1;$i<$d;$i++){
			$dird=$dir.'/'.$i;
			if(is_dir($dird)){
				delete_dir($dird);
			}
	
		}
		$dir.='/'.$d;
		if (!is_dir($dir)) {
			mkdir($dir, 0777);
		}
		$dir.='/';
		$filename=$dir.uniqid().'.png';
	
		$z=new Qrcode_image;
	
		//$z->set_qrcode_version($_GET['id']);           # set qrcode version 1
		$z->set_qrcode_error_correct($level);   # set ecc level H
		//$z->set_module_size(4);              # set module size 3pixel
		//$z->set_quietzone(5);                # set quietzone width 5 modules
	
		$z->qrcode_image_out($data,$filename,380,$filelogo,$filebg,$pt,$inpt,$f,$b,'#000000',$s);
	
		$this->ajaxReturn($filename);
	}
	public function file() {
		//上传图片
		$this->_upload();
		if(isset($_POST['logo'])||isset($_POST['bg'])){
			session('logo', $_POST['logo']);
			session('bg', $_POST['bg']);
			$this->success('ok');
		}  else {
			$this->error("no");
		}
	
	}
	public function delfile(){
		session('logo',null);
		session('bg',null);
	}
	
	public function save(){
		if(!session('?account')){
			$this->error('index.php?m=Member&a=login');
		}
	
		//临时二维码路径
		$source=$_POST['qrcodepath'];
	
		//设置附件上传目录
		$y = date('Y',time());
		$m = date('m',time());
		$d = date('d',time());
	
		$dir='Uploads/qrcode';
	
		if (!is_dir($dir)) {
			mkdir($dir, 0777);
		}
		$dir.='/'.$y;
		if (!is_dir($dir)) {
			mkdir($dir, 0777);
		}
		$dir.='/'.$m;
		if (!is_dir($dir)) {
			mkdir($dir, 0777);
		}
		$dir.='/'.$d;
		if (!is_dir($dir)) {
			mkdir($dir, 0777);
		}
		$dir.='/';
		//保存二维码路径
		$target=$dir.uniqid().'.png';
	
		if(copy($source,$target)){
			$_POST['qrcodepath']=  str_replace('Uploads', '', $target);
		}else{
			$this->error('no');
		}
	
		$model=D('Memberqrcode');
		if (false === $model->create()) {
			$this->error($model->getError());
		}
		$model->memberid= session('id');
		$model->create_time=  time();
		//保存当前数据对象
		$list = $model->add();
		if ($list !== false) {
			//保存成功
			$this->success('ok');
		} else {
			//失败提示
			$this->error('no');
		}
	
	}
	//验证码
	public function verify() {
		$type=isset($_GET['type'])?$_GET['type']:'gif';
		import("@.ORG.Util.Image");
		Image::buildImageVerify(4,1,$type);
	}
	
	
}
?>