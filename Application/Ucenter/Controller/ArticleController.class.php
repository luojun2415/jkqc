<?php 
namespace Ucenter\Controller;

use Think\Controller;

class ArticleController extends Controller{
	public function index() {
		if(isset($_SESSION['account']))
		{
			$uid=$_SESSION['id'];
			$type=$_GET['type']?$_GET['type']:'myindex';
	
			$cardid=M('VcardUserdetail')->where("loginid=$uid")->order('id asc')->getField('id');
			// var_dump($cardid);
			if($type=='myshare'||$type=='myclick'){
				$aid=M('Action')->where("action_key='$type' and user_id=$uid")->getField('page_id',true);
				//             var_dump(M('Action')->getLastSql());
				if($aid){
					$map['id']=array('in',$aid);
					$tags=M('Img')->field('tag')->where($map)->group('tag')->order('id desc')->select();
					if ($tags) {
						foreach ($tags as $tag){
							if($tag[tag]!=''){
								$tag=$tag['tag'];
								$map['tag']=$tag;
								$data=M('Img')->field('id,text,title,info,createtime,share,click,tag,pic')
								->where($map)->order('id desc')->select();
								$list[$tag]=$data;
								$list[$tag][0]['count']=count($data);
							}
	
							 
						}
					}
				}
	
				if($type=='myshare'){
					$this->assign("title", '我的分享');
				}
				else
					$this->assign("title", '我的浏览');
				//var_dump($list);
				// var_dump(M('Img')->_sql());
			}
			else{
				$tags=M('Img')->field('tag')->where("writer=$cardid")->group('tag')->order('id desc')->select();
	
				if ($tags) {
					foreach ($tags as $tag){
						$tag=$tag['tag'];
						$data=M('Img')->field('id,text,title,info,createtime,share,click,tag,pic,writer')
						->where("writer='$cardid' and tag='$tag'")->order('id desc')->select();
						//var_dump(M('Img')->_sql());
						$list[$tag]=$data;
						$list[$tag][0]['count']=count($data);
						 
					}
				}
				$this->assign("title", '我的文章');
			}
	
			//var_dump($list);
			// var_dump(M('Img')->_sql());
			$this->assign("cardid", $cardid);
			$this->assign('token',$this->token);
			$this->assign('wecha_id',$this->wecha_id);
			$this->assign("list", $list);
			$this->assign("type", $type);
			$this->display();
		}
		else{
			$this->redirect('Member/login',array('token'=>$_GET['token']));
		}
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
	public function showindex() {
		if(isset($_SESSION['account']))
		{
			//var_dump($_SESSION['account']);
			$myshow=M('Classify')->where("name='脉秀' and token='$this->token'")->find();
			//var_dump(M('Classify')->_sql());
			if($myshow){
				$map['classid']=$myshow['id'];
				$map['token']=$this->token;
				$data=M('Img')->field('id,text,title,createtime,share,click,tag,pic,writer')
				->where($map)->order('click desc')->limit(20)->select();
				//                  var_dump(M('Img')->_sql());
				//             var_dump($data);
				$this->assign('list',$data);
			}
			else{
				$this->error('数据错误！');
			}
			$this->assign('title','浏览文章');
	
			$this->assign('token',$this->token);
			$this->assign('wecha_id',$this->wecha_id);
			$this->display();
		}
		else{
			$this->redirect('Member/login',array('token'=>$_GET['token']));
		}
	}

}

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