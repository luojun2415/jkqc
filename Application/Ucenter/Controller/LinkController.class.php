<?php 
namespace Ucenter\Controller;

use Think\Controller;
class LinkController extends Controller
{
	public function index(){
		//友情链接
		$model=D('Link');
		$list=$model->order('listorder asc')->select();
	
		$position=array('id'=>0,'catname'=>'首页');
		//$this->seo(C(SITE_NAME), C(SITE_KEYWORDS), C(SITE_DESCRIPTION), $position);
		$this->list=$list;
		$this->display();
	}
}
?>