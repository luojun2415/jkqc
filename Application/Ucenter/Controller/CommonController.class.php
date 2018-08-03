<?php 
namespace Ucenter\Controller;

use Think\Controller;  
/*前台动作基类*/
class CommonAction extends Action {
	public $wecha_id;
	public $token;
	//初始化
	function _initialize(){
		//var_dump("证明我来过！");
		header("Content-Type:text/html; charset=utf-8");
		//import('@.ORG.Util.Cookie');
		//微信中获取openid
		$this->token='nldukp1453896231';
		//var_dump($this->token);
		if(!$this->wecha_id){
			if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ){
				require_once APP_PATH.'/Lib/Action/WapAction.class.php';
				$wapObj =new WapAction();
				$wecha_id=$wapObj->wecha_id;
				$this->wecha_id=$wecha_id;
				if($wapObj->token){
					$this->token=$wapObj->token;
				}
				//var_dump($this->token);
				//var_dump($wecha_id);
	
				//                 var_dump($wecha_id);
				//生成认证条件
				$map=array();
				// 支持使用绑定帐号登录
				$map['wecha_id'] = $wecha_id;
				$map["status"]=array('eq',1);
	
				$Member=M('Member');
				$authInfo=$Member->where($map)->find();
				//var_dump($authInfo);
				if($authInfo){
					$_SESSION['id']=$authInfo['id'];
					$_SESSION['account']=$authInfo['account'];
					$_SESSION['nickname']=$authInfo['nickname'];
					$_SESSION['email']=$authInfo['email'];
					$_SESSION['lastLoginTime']=$authInfo['last_login_time'];
					$_SESSION['login_count']=$authInfo['login_count'];
					$ip=get_client_ip();
					$time=time();
					$data = array();
					$data['id']=$authInfo['id'];
					$data['last_login_time']=$time;
					$data['login_count']=array('exp','login_count+1');
					$data['last_login_ip']=$ip;
					$Member->save($data);
				}
				//                  var_dump($wecha_id);
				//                 var_dump($this->token);
				//                 var_dump($_GET);
			}
		}
	
	
	
		//栏目导航
		$nav_list = D('Category')->where('pid=0 AND status=1')->order('listorder')->select();
		if(is_array($nav_list)){
			foreach ($nav_list as $key=>$val){
				$nav_list[$key] = $this->changurl($val);
				$nav_list[$key]['sub_nav'] = D('Category')->where('pid='.$val['id'].' AND status=1')->select();
				foreach ($nav_list[$key]['sub_nav'] as $key2=>$val2){
					$nav_list[$key]['sub_nav'][$key2] = $this->changurl($val2);
				}
			}
		}
		//var_dump($nav_list);
		$this->assign('nav_list',$nav_list);
	
		//每日流量统计
		$tjdate=D('Tjdate');
		$map['create_date']=array('eq',date('Ymd',time()));
		$vl=$tjdate->where($map)->find();
		if($vl){
			$tjdate->id=$vl['id'];
			$tjdate->create_num=$vl['create_num']+1;
			$tjdate->save();
		}else{
			$tjdate->create_date=date('Ymd',time());
			$tjdate->create_num=1;
			$tjdate->add();
		}
	
		//页面流量统计
		$tjurl=D('Tjurl');
		$map['create_url']=__SELF__;
		$vla=$tjurl->where($map)->find();
		if($vla){
			$tjurl->id=$vla['id'];
			$tjurl->create_num=$vla['create_num']+1;
			$tjurl->save();
		}else{
			$tjurl->create_url=__SELF__;
			$tjurl->create_num=1;
			$tjurl->add();
		}
	
	}
	//SEO赋值
	public function seo($title,$keywords,$description,$positioin){
		$this->assign('title',$title);
		$this->assign('keywords',$keywords);
		$this->assign('description',$description);
		$this->assign('position',$positioin['id']);
		$this->assign('positionname',$positioin['catname']);
	}
	//URL转换
	public function changurl($ary){
		if(is_array($ary)){
			if(key_exists('modelname', $ary)){
				$ary['url']=U($ary['modelname'].'/index/',array('id'=>$ary['id']));
			}
			return $ary;
		}
	}
	
	public function index() {
	
		$id = $_GET['id'];
		$catdata = D('Category')->where('status=1')->find($id);
	
		//获取所有子类id
		$catlist = D('Category')->where('status=1')->select();
		$idlist = $id.','.arrToTree($catlist,$id);
		$idlist= substr($idlist, 0, strlen($idlist)-1);
		$map['catid'] = array('in',$idlist);
	
		$name = $this->getActionName();
	
		//获取分页设置
		$Model=M('Model');
		$map['table']=array('eq',$name);
		$pageinfo=$Model->where($map)->find();
	
		$Form   =   M($name);
		import("@.ORG.Page");       //导入分页类
		$count  = $Form->where($map)->count();    //计算总数
		$Page = new Page($count, $pageinfo['listrows']);
		$list   = $Form->where($map)->limit($Page->firstRow. ',' . $Page->listRows)->order('id desc')->select();
	
		// 设置分页显示
		$Page->setConfig('header', $pageinfo['header']);
		$Page->setConfig('first', $pageinfo['first']);
		$Page->setConfig('last', $pageinfo['last']);
		$Page->setConfig('prev', $pageinfo['prev']);
		$Page->setConfig('next', $pageinfo['next']);
		$Page->setConfig('theme',$pageinfo['theme']);
		$page = $Page->show();
	
		$this->assign("data", $catdata);
		$this->assign("page", $page);
		$this->assign("list", $list);
		$this->seo(($catdata['title'])?$catdata['title']:C(SITE_NAME), ($catdata['keywords'])?$catdata['keywords']:C(SITE_KEYWORDS), ($catdata['description'])?$catdata['description']:C(SITE_DESCRIPTION), D('Common')->getPosition($id));
	
		$this->display();
	}
	
	public function show()
	{
		$id= $_GET['id'];
		$name = $this->getActionName();
	
		D($name)->where('id='.$id)->setInc('hits',1);//浏览次数
		 
		$model=M($name);
	
		//当前记录
		$data=$model->find($id);
	
		//上一条记录
		$prevdata=$model->where('id<'.$id)->order('id desc')->limit('1')->find();
	
		//下一条记录
		$nextdata=$model->where('id>'.$id)->order('id asc')->limit('1')->find();
	
		$this->seo($data['title'], $data['keywords'], $data['description'], D('Common')->getPosition($data['catid']));
	
		$Chain=D('Chain');
		$ChainMap['status']=array('eq',1);
		$Chainlist=$Chain->where($ChainMap)->select();
	
		foreach ($Chainlist as $key => $value) {
			$data['content']=preg_replace('/'.$value['keyword'].'/i',"<a href=".$value['url']." target=".$value['target'].">".$value['keyword']."</a>", $data['content'],$value['number']);
		}
	
		$this->data=$data;
		$this->prevdata=$prevdata;
		$this->nextdata=$nextdata;
		//Cookie::set('_currentUrl_', __SELF__);
		session('_currentUrl_', __SELF__);
		$this->display();
	}
	public function foreverdelete() {
		//删除指定记录
		$name = $this->getActionName();
		$model = D($name);
		if (!empty($model)) {
			//获取主键名称
			$pk = $model->getPk();
			$id = $_REQUEST [$pk];
			 
			if (isset($id)) {
				$condition = array($pk => array('in', explode(',', $id)));
				if (false !== $model->where($condition)->delete()) {
					$this->success('删除成功！');
				} else {
					$this->error('删除失败！');
				}
			} else {
				$this->error('非法操作');
			}
		}
		//$this->forward();
	}
	public function _upload(){
	
		if(!empty($_FILES))
		{
			import("@.ORG.Util.Image");
			import("@.ORG.UploadFile");
			//导入上传类
			$upload = new UploadFile();
			//设置上传文件大小
			$upload->maxSize = 2097152;
			//设置上传文件类型
			$upload->allowExts = explode(',', 'jpg,gif,png,jpeg');
			//设置附件上传目录
			$y = date('Y',time());
			$m = date('m',time());
			$d = date('d',time());
	
			$dir='./Uploads/imgtemp';
	
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
			$upload->savePath =$dir;//'../Uploads/';
	
			// 设置引用图片类库包路径
			$upload->imageClassPath = '@.ORG.Util.Image';
			//设置需要生成缩略图，仅对图像文件有效
			//$upload->thumb = true;
			//设置需要生成缩略图的文件后缀
			//$upload->thumbPrefix = 'm_,s_';  //生产2张缩略图
			//设置缩略图最大宽度
			$upload->thumbMaxWidth = '46';
			//设置缩略图最大高度
			$upload->thumbMaxHeight = '46';
			//设置上传文件规则
			$upload->saveRule = uniqid;
			//删除原图
			$upload->thumbRemoveOrigin = TRUE;
	
			if (!$upload->upload()) {
				//捕获上传异常
				$strerror=$upload->getErrorMsg();
				if($strerror!="没有选择上传文件"){
					$this->error($strerror);
				}
	
			} else {
				//取得成功上传的文件信息
				$uploadList = $upload->getUploadFileInfo();
				foreach ($uploadList as $key => $value) {
					foreach ($_FILES as $key1 => $value1) {
						if($value['name']===$value1['name']){
	
							$_POST[$key1] = 'imgtemp/'.$y.'/'.$m.'/'.$d.'/'.$value['savename'];
	
						}
					}
	
				}
	
	
			}
		}
	}
	
	protected function all_save($name = '', $back = '/index') {
		$name = $name ? $name : MODULE_NAME;
		$db = D($name);
		if ($db->create() === false) {
			$this->error($db->getError());
		} else {
			$id = $db->save();
			if ($id) {
				$m_arr = array(
						'Img',
						'Text',
						'Voiceresponse',
						'Ordering',
						'Lottery',
						'Host',
						'Product',
						'Selfform',
						'Panorama',
						'Wedding',
						'Vote',
						'Estate',
						'Reservation',
						'Carowner',
						'Carset'
				);
				if (in_array($name, $m_arr)) {
					$this->handleKeyword(intval($_POST['id']) , $name, $_POST['keyword'], intval($_POST['precisions']));
				}
				 
				$this->success($this->sucflag, U(MODULE_NAME . $back));
			} else {
				 
				$this->error($this->errorflag, U(MODULE_NAME . $back));
			}
		}
	}
	
}     
?>