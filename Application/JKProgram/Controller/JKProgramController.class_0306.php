<?php
/**
 * 所属项目 jkapp.
 * 开发者: luoj
 * 创建日期: 2016年5月26日
 * 创建时间: 下午4:04:09
 * 版权所有 重庆艾锐森科技有限责任公司(www.irosn.com)
 */
namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminTreeListBuilder;
use Think\Model;
use JKProgram;
use JKProgram\MhtFileMacker;
use Think\Template;
//use JKProgram\excel;
require(dirname(dirname(__FILE__))."/excel/PHPExcel.php");
require(dirname(dirname(__FILE__))."/excel/PHPExcel/Reader/Excel5.php");

require(dirname(dirname(__FILE__))."/excel/PHPExcel/IOFactory.php");
/**
 * Class ShopController
 * 
 * @package Admin\controller
 *          @luoj
 */
class JKProgramController extends AdminController
{

    public static $proId;

    protected $shopModel;

    protected $shop_configModel;

    protected $shop_categoryModel;

    function _initialize()
    {
        if (! IS_ROOT) {
            $map['id'] = array(
                'in',
                get_my_projects(UID)
            );
        }
        /*
         * $list = M('jk_project')->where($map)->select();
         * $id=I('get.id', 0, 'intval');
         * $id=$id>0?$id:$_SESSION['proId'];
         * $find = M('jk_project')->where("id=$id")->getField('name');
         *
         * $this->assign('thisPro',$find);
         * $this->assign('proList', $list);
         */
        
        $this->shopModel = D('JKProgram/JKProject');
        $this->shop_configModel = D('JKProgram/JKProjectConfig');
        $this->shop_categoryModel = D('JKProgram/JKProjectCategory');
        parent::_initialize();
    }

    /**
     * 商品分类
     * 
     * @author luoj
     */
    public function shopCategory()
    { /*
       * //显示页面
       * $builder = new AdminTreeListBuilder();
       * $attr['class'] = 'btn ajax-post';
       * $attr['target-form'] = 'ids';
       *
       * $tree = $this->shop_categoryModel->getTree(0, 'id,title,sort,pid,status');
       * $builder->title('楼栋管理')
       * ->buttonNew(U('JKProgram/addMore'),'快速新建楼栋')
       * ->data($tree)
       * ->display();
       */
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $_SESSION['proId'] = $id;
            action_log('show_project', 'JkProjcet', $id, UID);
        }
        $map['status'] = array(
            'gt',
            - 1
        );
        $map['pid'] = array(
            'eq',
            0
        );
        $map['projectid'] = $_SESSION['proId'];
        //$floordata = M('jk_floor')->where($map)->order('sort,id DESC')->select();
        $floordata = M('jk_floor')->where($map)->order('sort DESC')->select();
        // dump(M('jk_floor')->_sql());
        $this->assign('floordata', $floordata);
        $this->display('/JKProgram@JKProgram/shopCategory');
    }

    /**
     * 进入某个楼栋
     */
    public function selectfloor($id, $name)
    {
        // $data=$this->createdata($id);
        $data = $this->shop_categoryModel->getTree($id, 'id,title,sort,pid,status,imgpath');
      // echo '<pre>'; var_dump($data); echo '</pre>';die;
        $this->assign('arr_floor', $data['_']);
        $this->assign('floor_pid', $id);
        $this->assign('floor_name', $name);
        
        $this->display('/JKProgram@JKProgram/selectfloor1');
    }

    /**
    * 函数用途描述：保存实测图信息
    * @date: 2016年12月9日 上午9:25:37
    * @author: luojun
    * @param: 
    * @return:
    */
    public function saveflooroption(){
        if(IS_POST){
            $id = $_POST['ids'];
            $ids=explode(',', $id);
            $proId=$_SESSION['proId'];
            $oid = $_POST['oid'];
            if(!$oid){
                $this->error('未选择实测项');
            }
            $map['project_id'] = $proId;
            $map['measure_id'] = $oid;
            $map['floor_id'] = array('in',$ids);
            
            $infos=M('jk_measure_image')->where($map)->field('floor_id')->select();
            
            
            if(is_array($infos)){
                foreach ($infos as $v){
                    $inIds[]=$v['floor_id'];
                }
                $outIds = array_diff($ids, $inIds);
//                 $this->error('in'.json_encode($outIds));
            }
            else
                $outIds=$ids;
            //$this->error(json_encode($outIds));
            $data['project_id'] = $proId;
            $data['measure_id'] = $oid;
           
            $data['imgid']=$_POST['mapid'];;
            $data['imgurl']=coverIds2Path($data['imgid']);
            $data['createtime']=microtimeStr();
            $data['updatetime']=$data['createtime'];
            foreach ($outIds as $v){
                $data['floor_id'] = $v;
                M('jk_measure_image')->add($data);
            }
            if($inIds){
                $map['floor_id'] = array('in',$inIds);
                M('jk_measure_image')->where($map)->save(array('imgid'=>$data['imgid'],'imgurl'=>$data['imgurl']));
                
            }
            //修改图片type
            M('Picture')->where("id=". $data['imgid'])->save(array('type'=>'detail'));
            $this->success('操作完成');
        }
        
    }
    
    /**
    * 函数用途描述：楼层实测数据
    * @date: 2016年12月7日 下午4:02:18
    * @author: luojun
    * @param: 
    * @return:
    */
    public function surfloor()
    {
//         $id = array_unique((array)I('id', 0));
               
//        // $id = is_array($id) ? implode(',', $id) : $id;
//         $floorInfo="";
// 		if(is_array($id)){
// 	        foreach ($id as $v){
// 	        	$map['id'] = $v;
// 	        	$data = M('jk_floor')->where($map)
// 	        	->field('id,title,imgid')
// 	        	->find();
// 	        	$floorInfo .= $data['id'].",";
// 	        }
// 		}else{
// 			$data = M('jk_floor')->where($map)->field('id,title,imgid')->find();
// 			$floorInfo = $data['id'];
// 		}
//         $nodelist = D('JKProject/JKProjectSurvey')->getTree(0, 'id,title,sort,pid,status'); 
//         $this->assign('ids', $floorInfo);
//         $this->assign('nodeList', $nodelist);
//         $this->meta_title = L('实测实量图信息');
//         $this->display('/JKProgram@JKProgram/surfloor');
    	$id = array_unique((array)I('id', 0));
    	 
    	$id = is_array($id) ? implode(',', $id) : $id;
    	
    	if(empty($id)){
    		$this->error('未选择操作数据！');
    	}
    	//$a=getfloorinfo($id);
    	//         $a=getpointinfo($id);
    	//         echo '<pre>';
    	//         var_dump($a);
    	//         echo '</pre>';
    	//         die;
    	$nodelist = D('JKProject/JKProjectSurvey')->getTree(0, 'id,title,sort,pid,status');
    	$this->assign('ids', $id);
    	$this->assign('nodeList', $nodelist);
    	$this->meta_title = L('实测实量图信息');
    	$this->display('/JKProgram@JKProgram/surfloor');
    }

    public function createdata($pid)
    {
        $arr = array();
        $map['status'] = array(
            'gt',
            - 1
        );
        $map['pid'] = array(
            'eq',
            $pid
        );
        $data = M('jk_floor')->where($map)->select();
        if ($data) {
            foreach ($data as $v) {
                $map['status'] = array(
                    'gt',
                    - 1
                );
                $map['pid'] = array(
                    'eq',
                    $v['id']
                );
                $data2 = M('jk_floor')->where($map)->select();
                if ($data2) {
                    
                    foreach ($data2 as $v2) {
                        $map['pid'] = array(
                            'eq',
                            $v2['id']
                        );
                        $data3 = M('jk_floor')->where($map)->select();
                        if ($data3) {
                            $v2['childer'] = $data3;
                        }
                        $v['child'] = $data2;
                    }
                    
                    $arr[] = $v;
                }
            }
        }
        return $arr;
    }

    /**
     * 添加层
     */
    public function addlouc($id)
    {
        if (IS_POST) {
            
            $data = M('jk_floor');
            $datainfo = $data->create();
            
            $datainfo['imgpath'] = M('picture')->where(array(
                'id' => $_POST['himgid']
            ))->getField('path');
        } else {
            $data['pid'] = $id;
            $builder = new AdminConfigBuilder();
            $builder->title('新建楼层')
                ->keyHidden('pid', '楼栋id')
                ->keyText('title', '楼层名称')
                ->keyText('hnum', '添加户数')
                ->keySingleImage('himgid', '每户默认图片')
                ->data($data)
                ->buttonSubmit(U('JKProgram/addlouc'))
                ->buttonBack()
                ->display();
            /*
             * $this->assign('floor_pid',$_GET['id']);
             * $this->dispaly();
             */
        }
    }

    /**
     * 分类添加
     * 
     * @param int $id            
     * @param int $pid            
     * @author luoj
     */
    public function add($id = 0, $pid = 0)
    {
        if (IS_POST) {
            $title = $id ? L('_EDIT_') : L('_ADD_');
            if ($this->shop_categoryModel->editData()) {
                $this->success($title . L('_SUCCESS_') . L('_PERIOD_'), U('JKProgram/shopCategory'));
            } else {
                $this->error($title . L('_FAIL_') . L('_EXCLAMATION_') . $this->shop_categoryModel->getError());
            }
        } else {
            //             施工单位信息
            $proid=$_SESSION['proId'];
            $pid=M('jk_project')->where("id=$proid")->getField('pid');
            if(!$pid){
                $this->error('未选择项目对应组织架构', U('jkprogram/goodsedit/',array('id'=>$proid)));
            }
            $where  = array('status' => array('gt', 0),'pid'=>$pid);
            $list=M('AuthGroup')->where($where)->getField('id,title,cate');
            if(!$list){
                $this->error('未创建项目下属施工单位', U('auth_manager/index'));
            }
            else{
                foreach ($list as $k=>$v){
                    if(3==$v['cate']){//原施工单位类型
                        continue;
                    }
                    if($v['cate']>10){//新类型
                        $attr = get_cate_attr('AuthGroup', $v['id'], 3);//获取属性类别
						
                        if(3==$attr['cate']){
                            continue;
                        }
                    }
					
                    unset($list[$k]);
                }
                
                if(!$list){
                    $this->error('未创建项目下属施工单位', U('auth_manager/index'));
                }
            }
			$list = array_column($list, 'title', 'id');
            $list['0']='请选择';
            
            if ($id != 0) {
                $category = $this->shop_categoryModel->find($id);
            }
            $builder = new AdminConfigBuilder();
            $map['id'] = $_SESSION['proId'];
            $find = M('jk_project')->where($map)
            ->field('periods,blocks,batch')
            ->find();
            $maxPriod = $find['periods'] ? $find['periods'] : 5;
            $maxBlocks = $find['blocks'] ? $find['blocks'] : 5;
            $maxBatch = $find['batch'] ? $find['batch'] : 5;
            $arrPriod = $arrBlock = $arrBatch = array();
            for ($i = 1; $i <= $maxPriod; $i ++) {
                $arrPriod[$i] = $i;
            }
            for ($i = 1; $i <= $maxBlocks; $i ++) {
                $arrBlock[$i] = $i;
            }
            for ($i = 1; $i <= $maxBatch; $i ++) {
                $arrBatch[$i] = $i;
            }
            
            $builder->keyHidden('id', '')->title('快速新建楼栋')
            ->keyText('title', '楼栋名称*')
            ->keyText('sort', '楼栋排序')
            ->keySelect('cid', L('选择施工单位'), '', $list)
            
            ->keySingleImage('limgid', '楼层默认图片')
            ->keySingleImage('himgid', '每户默认图片')
            ->keySelect('periods', L('所属分期'), '', $arrPriod)
            ->keySelect('blocks', L('所属标段'), '', $arrBlock)
            ->keySelect('batch', L('所属批次'), '', $arrBatch)
            ->data($category)
            ->buttonSubmit(U('JKProgram/add'))
            ->buttonBack()
            ->display();
        }
    }

    /**
     * 快速创建楼栋
     * 
     * @author Duanmeiahua
     *         createTime 2016-10-14
     */
    public function addMore($id = 0, $title = '',$floorNum=1, $cid=0,$stratnum = '', $endnum = '', $imgid = '', $pid = 0,
         $periods = 0, $blocks = 0,$sort=999)
    {
        if (IS_POST) {
            if ($title == '' || $title == null) {
                $this->error(L('请输楼栋名称'));
            }
            if (!$cid) {
                $this->error(L('请选择施工单位'));
            }
            if ( $endnum <= 0 || intval($endnum) <= 0) {
                $this->error(L('请正确输入楼层数据'));
            }
            if($title=='室外环境')
            	$sort=1000;
            if($floorNum=="")
            	$floorNum=1;
            $data = M('jk_floor');
            $info = $data->create();
            $floorNum=intval($floorNum)>20?20:intval($floorNum);
            for ($jf = 0; $jf < $floorNum; $jf++) {
                $datainfo=$info;
//                 $datainfo['title'] = $datainfo['title'];
                $datainfo['projectid'] = $_SESSION['proId'];
                $datainfo['status'] = 1;
                $datainfo['create_time'] = time();
                $datainfo['update_time'] = time();
                $datainfo['pid'] = 0;
                
                if ($pid = $data->add($datainfo)) {
                    // for($i=$stratnum;$i<=$endnum;$i++){
                
                    // 判断是否有单元
                    $stratdynum = (int) $_POST['stratdynum'];
                    $enddynum = (int) $_POST['enddynum'];
                    if ($stratdynum > 0 && $endnum > 0) {
                        // 组建值
                        for ($d = $_POST['stratdynum']; $d <= $_POST['enddynum']; $d ++) {
                            $datainfo['title'] = $d . "单元";
                            $datainfo['pid'] = $pid;
                            $datainfo['imgpath'] = '';
                            $datainfo['imgid'] = 0;
                            if ($did = $data->add($datainfo)) {
                                for ($i = $_POST['stratnum']; $i <= $_POST['endnum']; $i ++) {
                                    if ($i == 0) {
                                        continue;
                                    } else {
                                        $datainfo['title'] = $i . "F";
                                        $datainfo['pid'] = $did;
                                        $datainfo['imgpath'] = M('picture')->where(array(
                                            'id' => $_POST['limgid']
                                        ))->getField('path');
                                        $datainfo['imgid'] = $_POST['limgid'];
                                        if ($cid = $data->add($datainfo)) {
                                            for ($j = 1; $j <= $_POST['hnum']; $j ++) {
                                                if ($i < 0) {
                                                    $hao = $i * 100 - $j;
                                                } else {
                                                    $hao = $i * 100 + $j;
                                                }
                                                $datainfo['title'] = $hao;
                                                $datainfo['pid'] = $cid;
                                                $datainfo['imgid'] = $_POST['himgid'];
                                                $datainfo['imgpath'] = M('picture')->where(array(
                                                    'id' => $_POST['himgid']
                                                ))->getField('path');
                                                $res = $data->add($datainfo);
                                            }
                                        } // if
                                    }
                                } // for添加层结束
                            } // 判断是否添加成功
                        } // 循环添加单元结束
                    } else {
                        $datainfo['title'] = '';
                        $datainfo['pid'] = $pid;
                        $datainfo['imgpath'] = '';
                        $datainfo['imgid'] = 0;
                        $did = $data->add($datainfo);
                        if ($did) {
                            for ($i = $_POST['stratnum']; $i <= $_POST['endnum']; $i ++) {
                                if ($i == 0) {
                                    continue;
                                } else {
                                    $datainfo['title'] = $i . "F";
                                    $datainfo['pid'] = $did;
                                    $datainfo['imgpath'] = M('picture')->where(array(
                                        'id' => $_POST['limgid']
                                    ))->getField('path');
                                    $datainfo['imgid'] = $_POST['limgid'];
                                    if ($cid = $data->add($datainfo)) {
                                        for ($j = 1; $j <= $_POST['hnum']; $j ++) {
                                            if ($i < 0) {
                                                $hao = $i * 100 - $j;
                                            } else {
                                                $hao = $i * 100 + $j;
                                            }
                                            $datainfo['title'] = $hao;
                                            $datainfo['pid'] = $cid;
                                            $datainfo['imgid'] = $_POST['himgid'];
                                            $datainfo['imgpath'] = M('picture')->where(array(
                                                'id' => $_POST['himgid']
                                            ))->getField('path');
                                            $res = $data->add($datainfo);
                                        }
                                    } // if
                                }
                            } // for添加层结束
                        }
                    } // 判断单元结束
                } // if 添加栋结束
                
            }
            
            if ($pid) {
                $this->success($title .'添加'. L('_SUCCESS_'), U('JKProgram/shopCategory'));
            } else {
                $this->error($title .'添加'. L('_FAIL_'));
            }
        }
        else{
//             施工单位信息
            $proid=$_SESSION['proId'];
            $pid=M('jk_project')->where("id=$proid")->getField('pid');
            if(!$pid){
                $this->error('未选择项目对应组织架构', U('jkprogram/goodsedit/',array('id'=>$proid)));
            }
            $where  = array('status' => array('gt', 0),'pid'=>$pid);
            $list=M('AuthGroup')->where($where)->getField('id,title,cate');
            if(!$list){
                $this->error('未创建项目下属施工单位', U('auth_manager/index'));
            }
            else{
                foreach ($list as $k=>$v){
                    if(3==$v['cate']){//原施工单位类型
                        continue;
                    }
                    if($v['cate']>10){//新类型
                        $attr = get_cate_attr('AuthGroup', $v['id'], 3);//获取属性类别
						
                        if(3==$attr['cate']){
                            continue;
                        }
                    }
					
                    unset($list[$k]);
                }
                
                if(!$list){
                    $this->error('未创建项目下属施工单位', U('auth_manager/index'));
                }
            }
			$list = array_column($list, 'title', 'id');
            $list['0']='请选择';
            
            $builder = new AdminConfigBuilder();
            $map['id'] = $_SESSION['proId'];
            $find = M('jk_project')->where($map)
                ->field('periods,blocks,batch')
                ->find();
            $maxPriod = $find['periods'] ? $find['periods'] : 5;
            $maxBlocks = $find['blocks'] ? $find['blocks'] : 5;
            $maxBatch = $find['batch'] ? $find['batch'] : 5;
            $arrPriod = $arrBlock = $arrBatch = array();
            for ($i = 1; $i <= $maxPriod; $i ++) {
                $arrPriod[$i] = $i;
            }
            for ($i = 1; $i <= $maxBlocks; $i ++) {
                $arrBlock[$i] = $i;
            }
            for ($i = 1; $i <= $maxBatch; $i ++) {
                $arrBatch[$i] = $i;
            }
            
            $builder->title('快速新建楼栋')
                ->keyText('title', '楼栋名称*')
                ->keyText('floorNum', '同类楼栋数量*','默认为1,最多一次创建20栋')
              //  ->keyText('sort', '楼栋排序','默认为999')
                ->keySelect('cid', L('选择施工单位'), '', $list)
                ->keyText('stratdynum', '开始单元数(没有单元请填0)')
                ->keyText('enddynum', '结束单元数(没有单元请填0)')
                ->keyInteger('stratnum', '开始楼层数*')
                ->keyInteger('endnum', '结束楼层数*')
                ->keyInteger('hnum', '每层户数*')
                //->keySingleImage('limgid', '楼层默认图片')
                //->keySingleImage('himgid', '每户默认图片')
                ->keySelect('periods', L('所属分期'), '', $arrPriod)
                ->keySelect('blocks', L('所属标段'), '', $arrBlock)
                ->keySelect('batch', L('所属批次'), '', $arrBatch)
                ->buttonSubmit(U('JKProgram/addMore'))
                ->buttonBack()
                ->display();
        }
    }

    /**
     * 分类回收站
     * 
     * @param int $page            
     * @param int $r            
     * @author luoj
     */
    public function categoryTrash($page = 1, $r = 20, $model = '')
    {
        $builder = new AdminListBuilder();
        $builder->clearTrash($model);
        // 读取微博列表
        $map = array(
            'status' => - 1
        );
        $list = $this->shop_categoryModel->where($map)
            ->page($page, $r)
            ->select();
        $totalCount = $this->shop_categoryModel->where($map)->count();
        
        // 显示页面
        
        $builder->title(L('_SHOP_CATEGORY_TRASH_'))
            ->setStatusUrl(U('setStatus'))
            ->buttonRestore()
            ->buttonClear('ShopCategory')
            ->keyId()
            ->keyText('title', L('_TITLE_'))
            ->keyStatus()
            ->keyCreateTime()
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
     * 设置商品分类状态：删除=-1，禁用=0，启用=1
     * 
     * @param
     *            $ids
     * @param
     *            $status
     * @author luoj
     */
    public function setStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        // $builder->doSetStatus('shopCategory', $ids, $status);
        // $builder->doSetStatus('jk_floor', $ids, $status);
        $rs = M('jk_floor')->where(array(
            'id' => array(
                'in',
                $ids
            )
        ))->save(array(
            'status' => $status
        ));
        if ($rs === false) {
            $this->error(L('_ERROR_SETTING_') . L('_PERIOD_'));
        }
        $this->success(L('_SUCCESS_SETTING_'), $_SERVER['HTTP_REFERER']);
    }

    /**
     * 设置商品状态：删除=-1，禁用=0，启用=1
     * 
     * @param
     *            $ids
     * @param
     *            $status
     * @author luoj
     */
    public function setGoodsStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('jk_project', $ids, $status);
    }

    /**
     * 商品列表
     * 
     * @param int $page            
     * @param int $r            
     * @author luoj
     */
    public function goodsList($page = 1, $r = 20)
    {
        action_log('show_project', 'JkProjcet', I('get.id', 0, 'intval'), UID);
        $map['status'] = array(
            'egt',
            0
        );
        $list = $this->shopModel->where($map)
            ->order('create_time desc')
            ->find();
        
        $this->meta_title = L('项目信息');
        $this->assign('list', $list);
        $this->display('/JKProgram@JKProgram/goodslist');
    }

    /**
     * 设置是否为新品
     * 
     * @param int $id            
     * @author luoj
     */
    public function setNew($id = 0)
    {
        if ($id == 0) {
            $this->error(L('_GOODS_SELECT_'));
        }
        $is_new = intval(! $this->shopModel->where(array(
            'id' => $id
        ))->getField('is_new'));
        $rs = $this->shopModel->where(array(
            'id' => $id
        ))->setField(array(
            'is_new' => $is_new,
            'changetime' => time()
        ));
        if ($rs) {
            $this->success(L('_SUCCESS_SETTING_') . L('_EXCLAMATION_'));
        } else {
            $this->error(L('_ERROR_SETTING_') . L('_EXCLAMATION_'));
        }
    }

    /**
     * 商品回收站
     * 
     * @param int $page            
     * @param int $r            
     * @author luoj
     */
    public function goodsTrash($page = 1, $r = 20, $model = '')
    {
        $builder = new AdminListBuilder();
        $builder->clearTrash($model);
        // 读取微博列表
        $map = array(
            'status' => - 1
        );
        $goodsList = $this->shopModel->where($map)
            ->order('changetime desc')
            ->page($page, $r)
            ->select();
        $totalCount = $this->shopModel->where($map)->count();
        
        // 显示页面
        
        $builder->title(L('_GOODS_TRASH_'))
            ->setStatusUrl(U('setGoodsStatus'))
            ->buttonRestore()
            ->buttonClear('JKProject/Shop')
            ->keyId()
            ->keyLink('goods_name', L('_TITLE_'), 'JKProject/goodsEdit?id=###')
            ->keyCreateTime()
            ->keyStatus()
            ->data($goodsList)
            ->pagination($totalCount, $r)
            ->display();
    }

    public function shopConfig()
    {
        $builder = new AdminConfigBuilder();
        $data = $builder->handleConfig();
        
        // 初始化数据
        ! isset($data['SHOP_SCORE_TYPE']) && $data['SHOP_SCORE_TYPE'] = '1';
        ! isset($data['SHOP_HOT_SELL_NUM']) && $data['SHOP_HOT_SELL_NUM'] = '10';
        
        // 读取数据
        $map = array(
            'status' => array(
                'GT',
                - 1
            )
        );
        $model = D('Ucenter/Score');
        $score_types = $model->getTypeList($map);
        $score_type_options = array();
        foreach ($score_types as $val) {
            $score_type_options[$val['id']] = $val['title'];
        }
        
        $builder->title(L('_SHOP_CONF_'))
            ->keySelect('SHOP_SCORE_TYPE', L('_SHOP_EXCHANGE_POINT_'), '', $score_type_options)
            ->keyInteger('SHOP_HOT_SELL_NUM', L('_SHOP_HOT_SELL_LEVEL_'), L('_SHOP_HOT_SELL_LEVEL_VICE_'))
            ->keyDefault('SHOP_HOT_SELL_NUM', 10)
            ->
        keyText('SHOP_SHOW_TITLE', L('_TITLE_NAME_'), L('_HOME_BLOCK_TITLE_'))
            ->keyDefault('SHOP_SHOW_TITLE', '热门商品')
            ->keyText('SHOP_SHOW_COUNT', '显示积分商品的个数', '只有在网站首页模块中启用了积分商城模块之后才会显示')
            ->keyDefault('SHOP_SHOW_COUNT', 4)
            ->keyRadio('SHOP_SHOW_TYPE', '推荐的范围', '', array(
            '1' => '新品',
            '0' => L('_EVERYTHING_')
        ))
            ->keyDefault('SHOP_SHOW_TYPE', 0)
            ->keyRadio('SHOP_SHOW_ORDER_FIELD', L('_SORT_VALUE_'), L('_TIP_SORT_VALUE_'), array(
            'sell_num' => '售出数量',
            'createtime' => L('_DELIVER_TIME_'),
            'changetime' => L('_UPDATE_TIME_')
        ))
            ->keyDefault('SHOP_SHOW_ORDER_FIELD', 'sell_num')
            ->keyRadio('SHOP_SHOW_ORDER_TYPE', L('_SORT_TYPE_'), L('_TIP_SORT_TYPE_'), array(
            'desc' => L('_COUNTER_'),
            'asc' => L('_DIRECT_')
        ))
            ->keyDefault('SHOP_SHOW_ORDER_TYPE', 'desc')
            ->keyText('SHOP_SHOW_CACHE_TIME', L('_CACHE_TIME_'), L('_TIP_CACHE_TIME_'))
            ->keyDefault('SHOP_SHOW_CACHE_TIME', '600')
            ->
        group(L('_BASIC_CONF_'), 'SHOP_SCORE_TYPE,SHOP_HOT_SELL_NUM')
            ->group(L('_HOME_SHOW_CONF_'), 'SHOP_SHOW_TITLE,SHOP_SHOW_TYPE,SHOP_SHOW_COUNT,SHOP_SHOW_TITLE,SHOP_SHOW_ORDER_TYPE,SHOP_SHOW_ORDER_FIELD,SHOP_SHOW_CACHE_TIME')
            ->groupLocalComment(L('_LOCAL_COMMENT_CONF_'), 'goodsDetail')
            ->data($data)
            ->buttonSubmit()
            ->buttonBack()
            ->display();
    }

    /**
     * 已完成交易列表
     * 
     * @param int $page            
     * @param int $r            
     * @author luoj
     */
    public function goodsBuySuccess($page = 1, $r = 20)
    {
        // 读取列表
        $map['status'] = 1;
        $map['is_back'] = 0;
        $map['use_status'] = array(
            'gt',
            0
        );
        $model = M('jk_shoporders');
        $list = $model->where($map)
            ->page($page, $r)
            ->order('sendtime DESC')
            ->select();
        $totalCount = $model->where($map)->count();
        
        foreach ($list as &$val) {
            $val['goods_name'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('name'));
            
            $val['phone'] = op_t(M('jk_users')->where('id=' . $val['user_id'])->getField('phone'));
            $val['address'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('adress'));
            
            $val['phone'] = op_t(M('jk_users')->where('id=' . $val['user_id'])->getField('phone'));
        }
        unset($val);
        // 显示页面
        $builder = new AdminListBuilder();
        
        $builder->title(L('_TRADE_ACCOMPLISHED_'));
        $builder->meta_title = L('_TRADE_ACCOMPLISHED_');
        
        $builder->keyId()
            ->keyText('goods_name', L('_GOODS_NAME_'))
            ->keyText('phone', '用户手机')
            ->keyText('address', L('所属店铺'))
            ->keyText('paytime', L('_BUY_TIME_'))
            ->keyUpdateTime('sendtime', L('发货时间'))
            ->key('use_status', L('_STATUS_'), 'status', array(
            1 => L('未收货'),
            2 => L('已收货')
        ))
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
     * 待发货交易列表
     * 
     * @param int $page            
     * @param int $r            
     * @author luoj
     */
    public function verify($page = 1, $r = 20)
    {
        // 读取列表
        $map = array(
            'status' => 1,
            'use_status' => 0,
            'is_back' => 0
        );
        $model = M('jk_shoporders');
        $list = $model->where($map)
            ->page($page, $r)
            ->order('paytime DESC')
            ->select();
        $totalCount = $model->where($map)->count();
        foreach ($list as &$val) {
            
            $val['goods_name'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('name'));
            $val['name'] = op_t(M('jk_users')->where('id=' . $val['user_id'])->getField('name'));
            $val['phone'] = op_t(M('jk_users')->where('id=' . $val['user_id'])->getField('phone'));
            $val['address'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('adress'));
        }
        unset($val);
        
        // 显示页面
        $builder = new AdminListBuilder();
        
        $builder->title(L('_GOODS_WAIT_DELIVER_'));
        $builder->meta_title = L('_GOODS_WAIT_DELIVER_');
        
        $builder->buttonEnable(U('setGoodsBuyStatus'), L('_DELIVER_'))
            ->keyId()
            ->keyText('goods_name', L('_GOODS_NAME_'))
            ->keyText('name', '用户名')
            ->keyText('phone', '用户手机')
            ->keyText('address', L('所属店铺'))
            ->keyText('paytime', L('_BUY_TIME_'))
            ->key('status', L('_STATUS_'), 'status', array(
            0 => L('未付款'),
            1 => L('已付款，未发货'),
            2 => L('已收货'),
            3 => L('交易完毕'),
            4 => L('申请退款'),
            5 => L('退款完毕')
        ))
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
     * 函数用途描述：虚拟商品列表
     * @date: 2016年6月14日 下午3:53:47
     * 
     * @author : jun
     * @param
     *            : variable
     * @return :
     */
    public function virtual($page = 1, $r = 20)
    {
        // 读取列表
        $staut = I('get.isuse', 0, 'intval');
        $map = $where = "1=1";
        if ($staut > 0) {
            $map = "isuse = $staut";
            $where = "o.isuse = $staut";
        }
        
        $model = M('jk_goodmsg');
        $prefix = C("DB_PREFIX");
        $list = M()->table($prefix . 'jk_goodmsg AS o,' . $prefix . 'jk_users AS u,' . $prefix . 'jk_shoplist AS s')
            ->field('o.id,o.aid,u.phone,s.name,o.no,o.isuse,o.used_time')
            ->where($where . " AND o.shop_id=s.id AND o.uid=u.id")
            ->page($page, $r)
            ->select();
        
        // $list = $model->where($map)->page($page, $r)->select();
        $totalCount = $model->where($map)->count();
        foreach ($list as &$val) {
            if ($val['isuse'] == 1 && $val['aid'] > 0) {
                $info = M('jk_users')->where("id=" . $val['aid'])
                    ->field('name,phone')
                    ->find();
                $val['admin'] = $info['name'] . "[" . $info['phone'] . "]";
            }
        }
        unset($val);
        // 显示页面
        $astauts = array(
            array(
                'id' => 0,
                'value' => L('_ALL_')
            ),
            array(
                'id' => 2,
                'value' => L('未使用')
            ),
            array(
                'id' => 1,
                'value' => L('已使用')
            )
        );
        $builder = new AdminListBuilder();
        $builder->setSelectPostUrl(U('Shop/virtual'))->select(L('商品状态：'), 'isuse', 'select', L('选择状态'), '', '', $astauts);
        
        $builder->title(L('虚拟商品列表'));
        $builder->meta_title = L('虚拟商品列表');
        
        $builder->keyId()
            ->keyText('name', L('_GOODS_NAME_'))
            ->keyText('phone', '用户手机')
            ->keyText('no', L('商品短信码'))
            ->keyUpdateTime('used_time', '使用时间')
            ->key('isuse', L('_STATUS_'), 'status', array(
            2 => L('未使用'),
            1 => L('已使用')
        ))
            ->keyText('admin', L('核销人'))
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
     * 函数用途描述：发货
     * @date: 2016年9月8日 上午9:48:17
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function setGoodsBuyStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $status = 1;
        if (empty($ids)) {
            $this->error('请选择要操作的数据!');
        }
        if ($status == 1) {
            $gettime = time();
            foreach ($ids as $id) {
                $data = array();
                $data['sendtime'] = time();
                $data['use_status'] = 1;
                M('jk_shoporders')->where('id=' . $id)->save($data);
            }
        }
        $this->success('操作成功');
    }

    /**
     * 商城日志
     * 
     * @param int $page            
     * @param int $r            
     * @author luoj
     */
    public function shopLog($page = 1, $r = 20)
    {
        // 读取列表
        $model = M('jk_shop_log');
        $list = $model->page($page, $r)
            ->order('create_time desc')
            ->select();
        $totalCount = $model->count();
        // 显示页面
        $builder = new AdminListBuilder();
        
        $builder->title(L('_SHOP_MESSAGE_RECORD_'));
        $builder->meta_title = L('_SHOP_MESSAGE_RECORD_');
        
        $builder->keyId()
            ->keyText('message', L('_MESSAGE_'))
            ->keyUid()
            ->keyCreateTime()
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
     * 函数用途描述：新增修改卡券
     * @date: 2016年7月25日 下午1:48:46
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function cardEdit($id = 0, $name = '', $starttime = '', $endtime = '', $status = '', $type = 0, $price = '')
    {
        $isEdit = $id ? 1 : 0;
        if (IS_POST) {
            if ($name == '' || $name == null) {
                $this->error(L('请输入卡券名称'));
            }
            if ($price == '' || $price <= 0) {
                $this->error(L('请输入卡券面额'));
            }
            $goods = M('jk_cards')->create();
            
            if ($isEdit) {
                $rs = M('jk_cards')->where('id=' . $id)->save($goods);
            } else {
                // 商品名存在验证
                $map['status'] = array(
                    'egt',
                    0
                );
                $map['name'] = $name;
                if (M('jk_cards')->where($map)->count()) {
                    $this->error(L('卡券名称重复'));
                }
                
                $goods['createtime'] = time();
                $rs = M('jk_cards')->add($goods);
            }
            if ($rs) {
                $this->success($isEdit == 0 ? L('_SUCCESS_ADD_') : L('_SUCCESS_EDIT_'), U('Shop/shopCard'));
            } else {
                $this->error($isEdit == 0 ? L('_FAIL_ADD_') : L('fail_Edit'));
            }
        } else {
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? L('编辑卡券') : L('新增卡券'));
            $builder->meta_title = $isEdit ? L('编辑卡券') : L('新增卡券');
            
            $builder->keyId()
                ->keyText('name', L('卡券名称'))
                ->keyTime('starttime', '开始时间')
                ->keyTime('endtime', '结束时间')
                ->keySelect('price', L('卡券类型'), '', array(
                '1' => '抵用券',
                '2' => '礼品券'
            ))
                ->keyText('price', L('卡券面额'));
            
            if ($isEdit) {
                $goods = M('jk_cards')->where('id=' . $id)->find();
                // dump($goods);
                $builder->data($goods);
                $builder->buttonSubmit(U('Shop/cardEdit'));
                $builder->buttonBack();
                $builder->display();
            } else {
                $goods['status'] = 1;
                
                $builder->buttonSubmit(U('Shop/cardEdit'));
                $builder->buttonBack();
                $builder->data($goods);
                $builder->display();
            }
        }
    }

    /**
     * 函数用途描述：设置卡券状态
     * @date: 2016年7月25日 下午2:00:50
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function setCardsStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('jk_cards', $ids, $status);
    }

    /**
     * 函数用途描述：设置优惠活动状态
     * @date: 2016年7月25日 下午2:01:49
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function setActivityStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('jk_activity', $ids, $status);
    }

    /**
     * 函数用途描述：商城卡券管理
     * @date: 2016年7月21日 上午9:29:35
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function shopCard($page = 1, $r = 20)
    {
        $map['status'] = array(
            'egt',
            0
        );
        $goodsList = M('jk_cards')->where($map)
            ->order('createtime desc')
            ->page($page, $r)
            ->select();
        $totalCount = M('jk_cards')->where($map)->count();
        $builder = new AdminListBuilder();
        $builder->title(L('活动列表'));
        $builder->meta_title = L('活动列表');
        foreach ($goodsList as &$val) {
            $val['type'] = $val['type'] == 1 ? '抵用券' : '礼品券';
        }
        unset($val);
        $builder->buttonNew(U('Shop/cardEdit'))
            ->buttonDelete(U('setCardsStatus'))
            ->setStatusUrl(U('setCardsStatus'));
        $builder->keyId()
            ->keyText('name', L('卡券名称'))
            ->keyText('price', L('卡券面额'))
            ->keyText('type', L('卡券类型'))
            ->keyUpdateTime('starttime', L('开始时间'))
            ->keyUpdateTime('endtime', L('结束时间'))
            ->keyStatus('status', L('状态'))
            ->keyDoActionEdit('Shop/cardEdit?id=###');
        $builder->data($goodsList);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }

    /**
     * 函数用途描述：新增修改活动
     * @date: 2016年7月21日 上午10:37:35
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function strategyEdit($id = 0, $name = '', $face_code = '', $description = '', $imgs_code = '', $starttime = '', $endtime = '', $status = '', $aword = 0)
    {
        $isEdit = $id ? 1 : 0;
        if (IS_POST) {
            if ($name == '' || $name == null) {
                $this->error(L('请输入活动名称'));
            }
            if (! $face_code) {
                $this->error(L('请上传活动封面图'));
            }
            
            if ($description == '' || $description == null) {
                $this->error(L('请输入活动描述简介'));
            }
            
            $goods = M('jk_activity')->create();
            
            if ($face_code) {
                $goods['face_img'] = 'http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . get_cover($goods['face_code'], 'path');
            }
            if ($imgs_code) {
                $tempData = array();
                $temp = explode(',', $imgs_code);
                foreach ($temp as $v) {
                    $tempData[] = 'http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . get_cover($v, 'path');
                }
                $goods['imgs'] = implode(',', $tempData);
            }
            
            if ($isEdit) {
                $rs = M('jk_activity')->where('id=' . $id)->save($goods);
            } else {
                // 商品名存在验证
                $map['status'] = array(
                    'egt',
                    0
                );
                $map['name'] = $name;
                if (M('jk_activity')->where($map)->count()) {
                    $this->error(L('活动名称重复'));
                }
                
                $goods['createtime'] = time();
                $rs = M('jk_activity')->add($goods);
            }
            if ($rs) {
                $this->success($isEdit == 0 ? L('_SUCCESS_ADD_') : L('_SUCCESS_EDIT_'), U('Shop/shopStrategy'));
            } else {
                $this->error($isEdit == 0 ? L('_FAIL_ADD_') : L('fail_Edit'));
            }
        } else {
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? L('编辑活动') : L('新增活动'));
            $builder->meta_title = $isEdit ? L('编辑活动') : L('新增活动');
            
            // 获取分类列表
            $category_map['status'] = array(
                'egt',
                0
            );
            $goods_category_list = M('jk_cards')->where($category_map)
                ->order('id desc')
                ->select();
            $options = array_combine(array_column($goods_category_list, 'id'), array_column($goods_category_list, 'name'));
            
            $builder->keyId()
                ->keyText('name', L('活动名称'))
                ->keyText('description', L('活动描述'))
                ->keyTime('starttime', '开始时间')
                ->keyTime('endtime', '结束时间')
                ->keySelect('aword', L('活动奖品'), '', array(
                '' => '无'
            ) + $options)
                ->keySingleImage('face_code', L('活动封面'))
                ->keyMultiImage('imgs_code', L('活动详情'), '', 20)
                ->keyText('url', L('活动外部链接'));
            
            if ($isEdit) {
                $goods = M('jk_activity')->where('id=' . $id)->find();
                // dump($goods);
                $builder->data($goods);
                $builder->buttonSubmit(U('Shop/strategyEdit'));
                $builder->buttonBack();
                $builder->display();
            } else {
                $goods['status'] = 1;
                
                $builder->buttonSubmit(U('Shop/strategyEdit'));
                $builder->buttonBack();
                $builder->data($goods);
                $builder->display();
            }
        }
    }

    /**
     * 函数用途描述：新增编辑秒杀
     * @date: 2016年7月25日 下午2:31:09
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function scareEdit($id = 0, $name = '', $description = '', $starttime = '', $endtime = '', $status = '', $aword = '', $activity_price = '')
    {
        // keyCheckBox
        $isEdit = $id ? 1 : 0;
        if (IS_POST) {
            if ($name == '' || $name == null) {
                $this->error(L('请输入秒杀活动名称'));
            }
            if ($activity_price == '' || $activity_price <= 0) {
                $this->error(L('请输入秒杀价'));
            }
            if ($description == '' || $description == null) {
                $this->error(L('请输入活动描述简介'));
            }
            if ($aword == '' || $aword == null) {
                $this->error(L('请选择秒杀商品'));
            }
            $goods = M('jk_activity')->create();
            
            if ($isEdit) {
                $rs = M('jk_activity')->where('id=' . $id)->save($goods);
            } else {
                // 商品名存在验证
                $map['status'] = array(
                    'egt',
                    0
                );
                $map['name'] = $name;
                if (M('jk_activity')->where($map)->count()) {
                    $this->error(L('活动名称重复'));
                }
                $goods['type'] = 1;
                $goods['createtime'] = time();
                $rs = M('jk_activity')->add($goods);
            }
            if ($rs) {
                $this->success($isEdit == 0 ? L('_SUCCESS_ADD_') : L('_SUCCESS_EDIT_'), U('Shop/scareBuy'));
            } else {
                $this->error($isEdit == 0 ? L('_FAIL_ADD_') : L('fail_Edit'));
            }
        } else {
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? L('编辑秒杀活动') : L('新增秒杀活动'));
            $builder->meta_title = $isEdit ? L('编辑秒杀活动') : L('新增秒杀活动');
            
            // 获取分类列表
            $category_map['status'] = array(
                'egt',
                0
            );
            $category_map['createtime'] = array(
                'gt',
                0
            );
            $goods_category_list = M('jk_shoplist')->where($category_map)
                ->order('id desc')
                ->select();
            $options = array_combine(array_column($goods_category_list, 'id'), array_column($goods_category_list, 'name'));
            
            $builder->keyId()
                ->keyText('name', L('秒杀活动名称'))
                ->keyText('description', L('秒杀活动描述'))
                ->keyText('activity_price', L('秒杀价格'))
                ->keyTime('starttime', '开始时间')
                ->keyTime('endtime', '结束时间')
                ->keySelect('aword', L('选择秒杀商品'), '', $options);
            
            if ($isEdit) {
                $goods = M('jk_activity')->where('id=' . $id)->find();
                // dump($goods);
                $builder->data($goods);
                $builder->buttonSubmit(U('Shop/scareEdit'));
                $builder->buttonBack();
                $builder->display();
            } else {
                $goods['status'] = 1;
                
                $builder->buttonSubmit(U('Shop/scareEdit'));
                $builder->buttonBack();
                $builder->data($goods);
                $builder->display();
            }
        }
    }

    /**
     * 函数用途描述：秒杀活动管理
     * @date: 2016年7月22日 上午10:52:49
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function scareBuy($page = 1, $r = 20)
    {
        $map['status'] = array(
            'egt',
            0
        );
        $map['type'] = 1;
        $goodsList = M('jk_activity')->where($map)
            ->order('createtime desc')
            ->page($page, $r)
            ->select();
        $totalCount = M('jk_activity')->where($map)->count();
        $builder = new AdminListBuilder();
        $builder->title(L('秒杀活动列表'));
        $builder->meta_title = L('秒杀活动列表');
        foreach ($goodsList as &$val) {
            $category = M('jk_shoplist')->where('id=' . $val['aword'])->getField('name');
            
            $val['aword'] = $category;
            unset($category);
        }
        unset($val);
        $builder->buttonNew(U('Shop/scareEdit'))
            ->buttonDelete(U('setActivityStatus'))
            ->setStatusUrl(U('setActivityStatus'));
        $builder->keyId()
            ->keyText('name', L('活动名称'))
            ->keyText('aword', L('秒杀商品'))
            ->keyText('description', '说明')
            ->keyUpdateTime('starttime', L('开始时间'))
            ->keyUpdateTime('endtime', L('结束时间'))
            ->keyStatus('status', L('状态'))
            ->keyDoActionEdit('Shop/scareEdit?id=###');
        $builder->data($goodsList);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }

    /**
     * 函数用途描述：新增编辑店面
     * @date: 2016年7月28日 下午5:30:39
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function hotelEdit($id = 0, $name = '', $tel = '', $description = '', $code = '', $address = '', $status = '', $lan = '', $lat = '')
    {
        // keyCheckBox
        $isEdit = $id ? 1 : 0;
        if (IS_POST) {
            if ($name == '' || $name == null) {
                $this->error(L('请填写店面名称'));
            }
            if ($tel == '') {
                $this->error(L('请填写联系电话'));
            }
            if ($address == '') {
                $this->error(L('请填写店面地址'));
            }
            if ($description == '' || $description == null) {
                $this->error(L('请填写店面描述简介'));
            }
            if ($code == '' || $code == null) {
                $this->error(L('请上传封面图片'));
            }
            
            $goods = M('jk_hotels')->create();
            if ($goods['code']) {
                $goods['img'] = 'http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . get_cover($goods['code'], 'path');
            }
            if ($isEdit) {
                $rs = M('jk_hotels')->where('id=' . $id)->save($goods);
            } else {
                // 商品名存在验证
                $map['status'] = array(
                    'egt',
                    0
                );
                $map['name'] = $name;
                if (M('jk_activity')->where($map)->count()) {
                    $this->error(L('店面名称重复'));
                }
                
                $goods['createtime'] = time();
                $rs = M('jk_hotels')->add($goods);
            }
            if ($rs) {
                $this->success($isEdit == 0 ? L('_SUCCESS_ADD_') : L('_SUCCESS_EDIT_'), U('Shop/hotelList'));
            } else {
                $this->error($isEdit == 0 ? L('_FAIL_ADD_') : L('fail_Edit'));
            }
        } else {
            if ($isEdit) {
                $goods = M('jk_hotels')->where('id=' . $id)->find();
            } else {
                $goods['status'] = 1;
            }
            $this->assign('info', $goods);
            $this->meta_title = $isEdit ? L('编辑店面位置') : L('新增店面位置');
            $this->display('/Shop@shop/hoteledit');
        }
    }

    /**
     * 函数用途描述：修改店面状态
     * @date: 2016年7月28日 下午2:52:06
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function setHotelStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('jk_hotels', $ids, $status);
    }

    /**
     * 函数用途描述：酒店位置信息
     * @date: 2016年7月26日 上午11:49:34
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function hotelList($page = 1, $r = 20)
    {
        $map['status'] = array(
            'egt',
            0
        );
        
        $goodsList = M('jk_hotels')->where($map)
            ->order('createtime desc')
            ->page($page, $r)
            ->select();
        $totalCount = M('jk_hotels')->where($map)->count();
        
        $builder = new AdminListBuilder();
        
        $builder->buttonNew(U('Shop/hotelEdit'))
            ->buttonDelete(U('setHotelStatus'))
            ->setStatusUrl(U('setHotelStatus'));
        
        $builder->title(L('店铺分布'));
        $builder->meta_title = L('店铺分布');
        
        $builder->keyId()
            ->keyText('name', L('店铺名称'))
            ->keyText('address', L('店铺位置'))
            ->keyStatus('status', L('状态'))
            ->keyDoActionEdit('Shop/hotelEdit?id=###');
        
        $builder->data($goodsList);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }

    /**
     * 函数用途描述：优惠活动管理
     * @date: 2016年7月21日 上午9:30:16
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function shopStrategy($page = 1, $r = 20)
    {
        $map['status'] = array(
            'egt',
            0
        );
        $map['type'] = 0;
        $goodsList = M('jk_activity')->where($map)
            ->order('createtime desc')
            ->page($page, $r)
            ->select();
        $totalCount = M('jk_activity')->where($map)->count();
        $builder = new AdminListBuilder();
        $builder->title(L('活动列表'));
        $builder->meta_title = L('活动列表');
        foreach ($goodsList as &$val) {
            $category = M('jk_cards')->where('id=' . $val['aword'])->getField('name');
            
            $val['aword'] = $category;
            unset($category);
        }
        unset($val);
        $builder->buttonNew(U('Shop/strategyEdit'))
            ->buttonDelete(U('setActivityStatus'))
            ->setStatusUrl(U('setActivityStatus'));
        $builder->keyId()
            ->keyText('name', L('活动名称'))
            ->keyText('aword', L('活动奖品'))
            ->keyText('description', '说明')
            ->keyUpdateTime('starttime', L('开始时间'))
            ->keyUpdateTime('endtime', L('结束时间'))
            ->keyStatus('status', L('状态'))
            ->keyDoActionEdit('Shop/strategyEdit?id=###');
        $builder->data($goodsList);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }

    /**
     * 函数用途描述：支付宝退款列表
     * @date: 2016年9月2日 下午6:03:13
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function aliRefund($page = 1, $r = 20)
    {
        // 读取列表
        $staut = I('get.is_back', 0, 'intval');
        $map['is_back'] = 1;
        if ($staut > 1) {
            $map['is_back'] = $staut;
        }
        
        $map['status'] = array(
            'egt',
            0
        );
        
        $map['paytype'] = '支付宝';
        
        $model = M('jk_shoporders');
        $list = $model->where($map)
            ->page($page, $r)
            ->select();
        // dump($model->_sql());
        $totalCount = $model->where($map)->count();
        foreach ($list as &$val) {
            
            $val['goods_name'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('name'));
            $val['phone'] = op_t(M('jk_users')->where('id=' . $val['user_id'])->getField('phone'));
            $val['name'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('adress'));
            $val['address'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('adress'));
        }
        unset($val);
        
        // 显示页面
        $astauts = array(
            array(
                'id' => 2,
                'value' => L('已退款')
            ),
            array(
                'id' => 0,
                'value' => L('未退款')
            )
        );
        
        $builder = new AdminListBuilder();
        $builder->setSelectPostUrl(U('Shop/aliRefund'))->select(L('退款状态：'), 'is_back', 'select', L('选择退款状态'), '', '', $astauts);
        $builder->title(L('支付宝退款申请列表'));
        // $builder->title(L('退款申请列表'));
        $builder->meta_title = L('退款申请列表');
        
        $builder->keyId()
            ->keyText('goods_name', L('_GOODS_NAME_'))
            ->keyText('phone', '用户手机')
            ->keyText('name', '用户名')
            ->keyText('address', L('所属店铺'))
            ->keyText('paytime', L('_BUY_TIME_'))
            ->keyText('backreason', L('退款理由'))
            ->key('is_back', L('退款状态'), 'status', array(
            0 => L('未申请付款'),
            1 => L('未退款'),
            2 => L('已退款')
        ))
            ->key('use_status', L('订单状态'), 'status', array(
            0 => L('未发货'),
            1 => L('已发货，未收货'),
            2 => L('已收货')
        ));
        // ->keyDoActionModalPopup('Shop/refundIframe?id=###','退款','退款操作');
        if ($staut <= 1) {
            // 支付宝退款
            $builder->keyDoActionModalPopup('Shop/refundIframe?id=###', '支付宝退款', '退款操作');
        }
        
        $builder->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
     * 函数用途描述：微信退款申请
     * @date: 2016年8月16日 下午2:09:23
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function Refund($page = 1, $r = 20)
    {
        // 读取列表
        $staut = I('get.is_back', 0, 'intval');
        $map['is_back'] = 1;
        // dump($staut);
        if ($staut > 1) {
            $map['is_back'] = $staut;
        }
        
        $map['status'] = array(
            'egt',
            0
        );
        
        $map['_string'] = "paytype='微信' OR paytype='公众号'";
        
        $model = M('jk_shoporders');
        $list = $model->where($map)
            ->page($page, $r)
            ->select();
        // dump($model->_sql());
        $totalCount = $model->where($map)->count();
        foreach ($list as &$val) {
            
            $val['goods_name'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('name'));
            $val['phone'] = op_t(M('jk_users')->where('id=' . $val['user_id'])->getField('phone'));
            $val['name'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('adress'));
            $val['address'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('adress'));
        }
        unset($val);
        
        // 显示页面
        $astauts = array(
            array(
                'id' => 2,
                'value' => L('已退款')
            ),
            array(
                'id' => 0,
                'value' => L('未退款')
            )
        );
        
        $builder = new AdminListBuilder();
        $builder->setSelectPostUrl(U('Shop/refund'))->select(L('退款状态：'), 'is_back', 'select', L('选择退款状态'), '', '', $astauts);
        $builder->title(L('微信退款申请列表'));
        // $builder->title(L('退款申请列表'));
        $builder->meta_title = L('退款申请列表');
        
        $builder->keyId()
            ->keyText('goods_name', L('_GOODS_NAME_'))
            ->keyText('phone', '用户手机')
            ->keyText('name', '用户名')
            ->keyText('address', L('所属店铺'))
            ->keyText('paytime', L('_BUY_TIME_'))
            ->keyText('backreason', L('退款理由'))
            ->key('is_back', L('退款状态'), 'status', array(
            0 => L('未申请付款'),
            1 => L('未退款'),
            2 => L('已退款')
        ))
            ->key('use_status', L('订单状态'), 'status', array(
            0 => L('未发货'),
            1 => L('已发货，未收货'),
            2 => L('已收货')
        ));
        // ->keyDoActionModalPopup('Shop/refundIframe?id=###','退款','退款操作');
        if ($staut <= 1) {
            $builder->keyDoAction('Shop/refundOrder?id=###', '微信退款', '退款操作', array(
                'class' => 'ajax-get'
            ));
        }
        
        $builder->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
     * 函数用途描述：还款模态框
     * @date: 2016年8月25日 上午11:18:24
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function refundIframe($id)
    {
        $this->assign('id', $id);
        $this->display('/Shop@shop/refundIframe');
        return;
    }

    /**
     * 函数用途描述：订单退款
     * @date: 2016年8月17日 下午3:14:56
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function refundOrder($id)
    {
        if (! $id) {
            $this->error('退款' . L('_FAIL_') . L('未指定订单！'));
        }
        $type = M('jk_shoporders')->where("id=$id")->getField('paytype');
        
        if ($type == '微信' || $type == '公众号') {
            $afind = M('jk_shoporders')->where("id=$id")
                ->field('ordernumber,price,transfee,status,
                is_back')
                ->find();
            if ($afind['status'] != 1) {
                $this->error('退款' . L('_FAIL_') . L('未支付的订单！'));
                return;
            }
            if ($afind['is_back'] == 2) {
                $this->error('退款' . L('_FAIL_') . L('已退款订单！'));
                return;
            }
            if ($afind['is_back'] == 0) {
                $this->error('退款' . L('_FAIL_') . L('客户未申请退款！'));
                return;
            }
            
            include_once __APPLICATION__ . "Vip\Controller\JKAppWxPayController.class.php";
            $Wxpay = new \Vip\Controller\JKAppWxPayController();
            $tradeNo = $afind['ordernumber'];
            $totalMoney = floatval($afind['price']) + floatval($afind['transfee']);
            // $this->error('退款'.L('_FAIL_').$type);
            $result = $Wxpay->new_Refund($tradeNo, $totalMoney, $type);
            // dump($result);
            if ($result['result_code'] == "SUCCESS" && $result['return_msg'] == 'OK') {
                
                $data['is_back'] = 2;
                $data['backDetail'] = $result['result_code'];
                M('jk_shoporders')->where("id=$id")->save($data);
                // echo '退款成功!';
                $this->success('退款成功!');
            } else {
                // dump($result);
                // echo '退款遇到问题!'.$result['err_code_des'];
                $this->error('退款遇到问题!' . $result['err_code_des']);
            }
            return;
        } else 
            if ($type == '支付宝') {
                $afind = M('jk_shoporders')->where("id=$id")
                    ->field('trade_no,price,transfee')
                    ->find();
                
                $refundNo = date('YmdHis', time()) . rand(111, 999);
                M('jk_shoporders')->where("id=$id")->save(array(
                    'alirefundno' => $refundNo
                ));
                include_once __APPLICATION__ . "Vip\Controller\AlipayController.class.php";
                $Alipay = new \Vip\Controller\AlipayController();
                
                $WIDdetail_data = $afind['trade_no'] . "^" . (floatval($afind['price']) + floatval($afind['transfee'])) . "^" . "协商退款"; // 支付宝交易号^退款金额^备注
                
                $aliUrl = $Alipay->aliRefund($refundNo, 1, $WIDdetail_data);
                
                echo $aliUrl;
            } else {
                $this->error('退款' . L('_FAIL_') . L('未知的支付方式！'));
                // echo '退款遇到问题!'.$result['err_code_des'];
            }
    }

    /**
     * 项目图片列表
     * 段美华
     * 2016-10-09
     */
    public function projectImgList($page = 1, $r = 20)
    {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $_SESSION['proId'] = $id;
            action_log('show_project', 'JkProjcet', $id, UID);
        }
        
        $map['projectid'] = $_SESSION['proId'];
        
        $map['status'] = array(
            'egt',
            0
        );
        $goodsList = M('picture')->where($map)
            ->order('create_time desc')
            ->page($page, $r)
            ->select();
        $totalCount = M('picture')->where($map)->count();
        $builder = new AdminListBuilder();
        $builder->title('项目图片信息');
        // $builder->meta_title = L('_GOODS_LIST_');
        $builder->meta_title = '项目图片信息'; // L('_GOODS_LIST_');
        $builder->buttonDelete(U('deleteImage'))->setStatusUrl(U('setGoodsStatus'));
        $builder->keyId()
            ->keyImage('path', '文件')
            ->keyTime('create_time', '时间')
            ->
        keyDoAction('JKProgram/setImgstatus?ids=###&status=-1', L('_DELETE_'));
        $builder->data($goodsList);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }

    public function setImgstatus($ids, $status)
    {
        $aGroupId = I('ids', 0, 'intval');
        $rs = M('picture')->where(array(
            'id' => $aGroupId
        ))->save(array(
            'status' => $status
        ));
        if ($rs) {
            $this->success(L('_DELETE_SUCCESS_'));
        } else {
            $this->error(L('_DELETE_FAILED_'));
        }
    }
    
    // 设置图片状态
    public function deleteImage($ids, $status = -1)
    {
        // $this->error(json_encode($ids));
        /*
         * $aGroupId = I('ids', 0, 'intval');
         * if (!$aGroupId) {
         * $this->error(L('_PARAMETER_ERROR_'));
         * }
         * $status=-1;
         */
        $builder = new AdminListBuilder();
        $builder->doSetStatus('picture', $ids, $status);
        /*
         * $rs = M('picture')->where("id in (".$ids.")")->save(array('status' => $status));
         * if ($rs) {
         * $this->success(L('_DELETE_SUCCESS_'));
         * } else {
         * $this->error(L('_DELETE_FAILED_'));
         * }
         */
    }

   

    /* ===================================== */
    // 项目问题列表
    public function ProjectProblemList($page = 1, $r = 20){
    $id=I('get.id', 0, 'intval');
    if($id){
    $_SESSION['proId']=$id;
    action_log('show_project', 'JkProjcet', $id, UID);
    }
    $map['ownid'] = $_SESSION['proId'];
    $map['status'] = array('egt', 0);
    //接收时间条件（转换为毫秒）
    $aSearch1 = I('get.usearch1','')*1000;
    $aSearch2 = I('get.usearch2','')*1000;
  
    //设置搜索条件
    if(!empty($aSearch1)){
        //$map.=" AND o.used_time >= '$aSearch1' ";
        $map['_string']="create_time>='$aSearch1' ";
    }
    if(!empty($aSearch2)){
        $map['_string'].="and create_time<='$aSearch2' ";
    }
    //状态
    $status=I('get.status','');
    if(!empty($status)){
        if($status!=4)
          $map['status'] = array('eq', $status);
    }
    if($status==0)
        $map['status'] = array('eq', $status);
    //问题类型
    $type=I('get.type','');
    if($type==null || $type=='')
    	$type=3;
    if(!empty($type) && $type!=null){
    	if($type!=3)
    		$map['type'] = array('eq', $type);
    }
    if($type==0)
    	$map['type'] = array('eq', $type);
    
    //检查项
//     $option=I('get.option','');
//     if(!empty($option)){    	
//     	$map['option_id'] = array('eq', $option);
//     }
 
    $goodsList = M('jk_program')->where($map)->order('create_time desc')->page($page, $r)->select();
  //  dump(M()->getLastSql());
    $totalCount = M('jk_program')->where($map)->count();
    $builder = new AdminListBuilder();
    $builder->title('项目问题列表');
    $builder->meta_title = '项目问题列表';
    $title=I('get.title','');
    $newtotalCount=0;
    $newgoodsList=array();
    //选项问题
    foreach ($goodsList as &$val) {
        //问题选项【检查项】
        $val['id']=$val['init_id'];
        if($val['type']>0){
            $option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
        }
        else
            $option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');
        $val['option_id'] = $option;
        unset($option);
        
        //楼栋数字截取
        $loudong_j=substr($val['project_ids'],-1);
        if($loudong_j==','){
        $val['project_ids']=substr($val['project_ids'], 0,-1);
        }
        
        //楼栋【问题具体位置】
        $floor=M('jk_floor')->where("id in (".$val['project_ids'].")")->field('title')->select();
        $loudong="";
        foreach ($floor as $value){
        $loudong=$loudong.$value['title'].'、';
        }
        $changdu=mb_strlen($loudong,'utf-8');
        $hangye=mb_substr($loudong,0,$changdu-1,'utf-8');
        $val['project_ids']=$hangye;
        
        //问题提交人
        $val['authid'] = M('member')->where('uid=' . $val['authid'])->getField('username');
        
        $val['target']=M('auth_group')->where('id=' . $val['target_id'])->getField('title');
        
        //时间处理
        if(strlen($val['create_time'])>10){
        $val['create_time'] = substr($val['create_time'],0,10);
        }
        if(strlen($val['update_time'])>10){
        $val['update_time'] = substr($val['update_time'],0,10);
        }
        
        //所属项目
        $val['other_id'] = M('jk_project')->where('id=' . $val['ownid'])->getField('other_id');
        $val['ownid'] = M('jk_project')->where('id=' . $val['ownid'])->getField('name');
        
        //状态
        if($val['status']==0){
        $val['status']="待整改";
        }else if($val['status']==1){
        $val['status']="正常已关闭";
        }else if($val['status']==2){
        $val['status']="待复查";
        }else if($val['status']==3){
        $val['status']="强制关闭";
        }
        if($floor[0][title]==$title){
            $newgoodsList[]=$val;
            $newtotalCount+=1;
        }
    }
    unset($val);
    //确定当前项目的模板：项目对应的areaID
   
    $areaID=M('jk_project')->where('id=' .  $_SESSION['proId'])->getField('areaID');
    //($_COOKIE['areanum'])
    setcookie('areanum',$areaID);
    $attr['target-form'] = 'ids';
    $attr['href'] = U('exWord');
    $attr['class']='a_jump';
    $builder->button('导出通知单', $attr)->setStatusUrl(U('setGoodsStatus1'));
    $attr['href'] = U('exhuiWord');
    $builder->button('导出通知回复单', $attr);
    $attr['href'] = U('exstopWord');
    $builder->button('导出暂停令', $attr);
    $attr['href'] = U('excontactWord');
    $builder->button('导出联系单', $attr);
    //判断是否是四川项目
    $sichuan = M('jk_project')->where('id=' . $_SESSION['proId'])->getField('areaID');
     
    if($sichuan==2){
	    $attr['href'] = U('exstartWord');
	    $builder->button('导出复工单', $attr);
    }
    $builder->setSelectPostUrl(U('JKProgram/ProjectProblemList'));
    //根据类型筛选
    $typeArr=array();
    $typeArr[0]['id']='3';
    $typeArr[0]['value']='全部';
    $typeArr[1]['id']='0';
    $typeArr[1]['value']='日常巡查';
    $typeArr[2]['id']='1';
    $typeArr[2]['value']='实测实量';
    $builder->select(L('问题类型：'), 'type', 'select', L('问题类型'), '', '', $typeArr);
    //根据检查项搜索1.先构建检查项数组
//     if($type==1){
// 	    $allOption=M('jk_survey_option')->where('status!=-1')->field('id,title')->select();
// 	    $optionArr=array();
// 	    $optionArr[0]['id']='';
// 	    $optionArr[0]['value']='全部';
// 	    $i=1;
// 	    foreach ($allOption as $eachOption){
// 	    	$optionArr[$i]['id']=$eachOption['id'];
// 	    	$optionArr[$i]['value']=$eachOption['title'];
// 	    	$i++;
// 	    }
//     }else{
//     	$allOption=M('jk_option')->where('status!=-1')->field('id,title')->select();
//     	$optionArr=array();
//     	$optionArr[0]['id']='';
//     	$optionArr[0]['value']='全部';
//     	$i=1;
//     	foreach ($allOption as $eachOption){
//     		$optionArr[$i]['id']=$eachOption['id'];
//     		$optionArr[$i]['value']=$eachOption['title'];
//     		$i++;
//     	}
//     }
//     $builder->select(L('检查项：'), 'option', 'select', L('检查项'), '', '', $optionArr);
    //根据下拉列表筛选
    $astauts=array(array('id' => 4, 'value' => L('全部')),array('id' => 0, 'value' => L('待整改')),array('id' => 2, 'value' => L('待复查')),
        array('id' => 1, 'value' => L('正常已关闭')),array('id' => 3, 'value' => L('强制关闭')));
 
    $builder->select(L('状态：'), 'status', 'select', L('选择状态'), '', '', $astauts);
    //根据下拉列表筛选--楼栋
    //构建最顶级的楼栋列表
    $floorlist=M('jk_floor')->where("status=1 and pid=0 and projectid='".$_SESSION['proId']."'")->order('create_time desc')->select();
    $a=array();
    $a[0]['id']='';
    $a[0]['value']='请选择';
    $i=1;
    foreach ($floorlist as $topfloor){
        $a[$i]['id']=$topfloor['title'];
        $a[$i]['value']=$topfloor['title'];
        $i++;
    }  
    $builder->setSelectPostUrl(U('JKProgram/ProjectProblemList'))
    ->select(L('楼栋：'), 'title', 'select', L('选择楼栋'), '', '', $a);
 
    //搜索框
    $builder->setSearchPostUrl(U('JKProgram/ProjectProblemList'))
    ->search('时间从','usearch1','timer','','','','');
    $builder->search('到','usearch2','timer','','','','');
    //列表
    $builder->keyId('other_id','项目编号')
    ->keyText('ownid', '项目名称')
    ->keyText('project_ids', '问题位置')
    ->keyText('option_id', '检查项')
    ->keyText('info', '问题描述')
    ->keyText('target', '整改单位')
    ->keyText('authid', '问题提交人')
    ->keyUpdateTime('create_time', '提交时间')
    ->keyText('status', '状态')
    ->keyDoActionEdit('JKProgram/programsedit?init_id=###', '详情');
   // $builder->data($goodsList);
  //  $builder->pagination($totalCount, $r);
    if($title!=''){
        //var_dump($newgoodsList);
        $builder->data($newgoodsList);
        $builder->pagination($newtotalCount, $r);
    }
    else {
        $builder->data($goodsList);
        $builder->pagination($totalCount, $r);
    }
    $builder->display();
    }
    /**
     * 函数用途描述：选择区域
     * @date: 2017年02月09日
     *
     * @author : 谭杰文
     * @return :
     */
    public function selectarea($page = 1, $r = 20){
    	$builder = new AdminListBuilder();
    	$builder->title('选择区域格式');
    	$builder->meta_title = '区域格式列表';
    	$areaList =array(
    			array('id'=>1,'name'=>'重庆'),
    			array('id'=>2,'name'=>'四川'),
    			array('id'=>3,'name'=>'北京'),
    			array('id'=>4,'name'=>'湖南'),
    			array('id'=>5,'name'=>'陕西'),
    			array('id'=>6,'name'=>'云南'),
    			array('id'=>7,'name'=>'郑州'),
    			array('id'=>8,'name'=>'贵州'),
    			array('id'=>9,'name'=>'新疆'),
    			array('id'=>10,'name'=>'安徽'),
    			array('id'=>11,'name'=>'江苏'),
    			array('id'=>12,'name'=>'山东'),
    	);
    	$aList =array(
    			array('id'=>1,'name'=>'重庆监理用表_监理通知单'),
    			array('id'=>2,'name'=>'重庆监理用表_工程暂停令'),
    			array('id'=>3,'name'=>'重庆监理用表_监理通知回复单'),
    			array('id'=>4,'name'=>'重庆监理用表_工作联系单'),
    			
    			array('id'=>5,'name'=>'四川监理用表_监理通知单'),
    			array('id'=>6,'name'=>'四川监理用表_工程暂停令'),
    			array('id'=>7,'name'=>'四川监理用表_监理通知回复单'),
    			array('id'=>8,'name'=>'四川监理用表_工作联系单'),
    			
    			array('id'=>9,'name'=>'北京监理用表_监理通知单'),
    			array('id'=>10,'name'=>'北京监理用表_工程暂停令'),
    			array('id'=>11,'name'=>'北京监理用表_监理通知回复单'),
    			array('id'=>12,'name'=>'北京监理用表_工作联系单'),
    			
    			array('id'=>13,'name'=>'湖南监理用表_监理通知单'),
    			array('id'=>14,'name'=>'湖南监理用表_工程暂停令'),
    			array('id'=>15,'name'=>'湖南监理用表_监理通知回复单'),
    			array('id'=>16,'name'=>'湖南监理用表_工作联系单'),
    			
    			array('id'=>17,'name'=>'陕西监理用表_监理通知单'),
    			array('id'=>18,'name'=>'陕西监理用表_工程暂停令'),
    			array('id'=>19,'name'=>'陕西监理用表_监理通知回复单'),
    			array('id'=>20,'name'=>'陕西监理用表_工作联系单'),
    			
    			array('id'=>21,'name'=>'云南监理用表_监理通知单'),
    			array('id'=>22,'name'=>'云南监理用表_工程暂停令'),
    			array('id'=>23,'name'=>'云南监理用表_监理通知回复单'),
    			array('id'=>24,'name'=>'云南监理用表_工作联系单'),
    			
    			array('id'=>25,'name'=>'郑州监理用表_监理通知单'),
    			array('id'=>26,'name'=>'郑州监理用表_工程暂停令'),
    			array('id'=>27,'name'=>'郑州监理用表_监理通知回复单'),
    			array('id'=>28,'name'=>'郑州监理用表_工作联系单'),
    			
    			array('id'=>29,'name'=>'贵州监理用表_监理通知单'),
    			array('id'=>30,'name'=>'贵州监理用表_工程暂停令'),
    			array('id'=>31,'name'=>'贵州监理用表_监理通知回复单'),
    			array('id'=>32,'name'=>'贵州监理用表_工作联系单'),
    			
    			array('id'=>33,'name'=>'新疆监理用表_监理通知单'),
    			array('id'=>34,'name'=>'新疆监理用表_工程暂停令'),
    			array('id'=>35,'name'=>'新疆监理用表_监理通知回复单'),
    			array('id'=>36,'name'=>'新疆监理用表_工作联系单'),
    			
    			array('id'=>37,'name'=>'安徽监理用表_监理通知单'),
    			array('id'=>38,'name'=>'安徽监理用表_工程暂停令'),
    			array('id'=>39,'name'=>'安徽监理用表_监理通知回复单'),
    			array('id'=>40,'name'=>'安徽监理用表_工作联系单'),
    			
    			array('id'=>41,'name'=>'江苏监理用表_监理通知单'),
    			array('id'=>42,'name'=>'江苏监理用表_工程暂停令'),
    			array('id'=>43,'name'=>'江苏监理用表_监理通知回复单'),
    			array('id'=>44,'name'=>'江苏监理用表_工作联系单'),
    			
    			array('id'=>45,'name'=>'山东监理用表_监理通知单'),
    			array('id'=>46,'name'=>'山东监理用表_工程暂停令'),
    			array('id'=>47,'name'=>'山东监理用表_监理通知回复单'),
    			array('id'=>48,'name'=>'山东监理用表_工作联系单'),
    	);
//     	$attr['target-form'] = 'ids';
//     	$attr['href'] = U('exALLWord');
//     	$attr['class']='a_jump1';
//     	$builder->button('导出', $attr)->setStatusUrl(U('selectarea'));
    	$attr['target-form'] = 'ids';
    	$attr['href'] = U('exword');
    	$attr['class']='a_jump1';
    	$builder->button('导出整改单', $attr)->setStatusUrl(U('selectarea'));
     	$attr['href'] = U('exhuiword');
     	$builder->button('导出整改回复单', $attr)->setStatusUrl(U('selectarea'));
     	$attr['href'] = U('exstopword');
     	$builder->button('导出暂停令', $attr)->setStatusUrl(U('selectarea'));
     	$attr['href'] = U('excontactword');
     	$builder->button('导出联系单', $attr)->setStatusUrl(U('selectarea'));
     	$attr['href'] = U('exstartword');
     	$builder->button('导出复工令', $attr)->setStatusUrl(U('selectarea'));
    	$builder->setSelectPostUrl(U('JKProgram/selectarea'));
    	$builder->keyId('id','区域编号')
    	->keyText('name', '区域名');
    	$builder->data($areaList);
    	$builder->display();
    }
    /**
     * 函数用途描述：实测表列表
     * @date: 2016年11月13日
     *
     * @author : luojun
     * @return :
     */
    public function measureList($page = 1, $r = 20){
        $id=I('get.id', 0, 'intval');
        if($id){
            $_SESSION['proId']=$id;
            action_log('show_project', 'JkProjcet', $id, UID);
        }
        //接收时间条件（转换为毫秒）
        $aSearch1 = I('get.usearch1','')*1000;
        $aSearch2 = I('get.usearch2','')*1000;
      
        //设置搜索条件
        if(!empty($aSearch1)){
            //$map.=" AND o.used_time >= '$aSearch1' ";
            $map['_string']="create_time>='$aSearch1' ";
        }
        if(!empty($aSearch2)){
            $map['_string'].="and create_time<='$aSearch2' ";
        }
        $map['projectid'] = $_SESSION['proId'];
        $map['status'] = 1;
        //接收筛选条件（状态）
        $is_out_range=I('get.is_out_range','');
        if(!empty($is_out_range)){
            if($is_out_range!=4)
                $map['is_out_range'] = array('eq', $is_out_range);
        }
        if($is_out_range==0)
            $map['is_out_range'] = array('eq', $is_out_range);
      
        //检查项
        $option=I('get.option','');
        if(!empty($option)){
        	$map['inspect'] = array('eq', $option);
        }
        //接收筛选条件（楼栋）
        $title=I('get.title','');
        $measureList = M('jk_check_point')->where($map)->order('create_time desc')->page($page, $r)->select();
        $totalCount = M('jk_check_point')->where($map)->count();
        $newtotalCount=0;
        $builder = new AdminListBuilder();
        $builder->title('实测实量列表');
        $builder->meta_title = '实测实量列表';
        
        //选项问题
        $newmeasureList=array();
        foreach ($measureList as &$val) {
            //问题选项【检查项】
            $option = M('jk_survey_option')->where('id=' . $val['inspect'])->getField('title');
            $val['inspect'] = $option;
            unset($option);
    
            //楼栋数字截取
            $val['project_ids']=$val['postion'];
            $loudong_j=substr($val['project_ids'],-1);
            if($loudong_j==','){
                $val['project_ids']=substr($val['project_ids'], 0,-1);
            }
          
            //var_dump( $val['project_ids']);
            //楼栋【问题具体位置】
            $floor=M('jk_floor')->where("id in (".$val['project_ids'].")")->field('title')->select();
            $loudong="";
            
            foreach ($floor as $value){
                $loudong=$loudong.$value['title'].'-';
            }
            $changdu=mb_strlen($loudong,'utf-8');
            $hangye=mb_substr($loudong,0,$changdu-1,'utf-8');
            $val['project_ids']=$hangye;
    		
            //问题提交人
            $val['authid'] = M('member')->where('uid=' . $val['userid'])->getField('username');
    
    
            //时间处理
            if(strlen($val['create_time'])>10){
                $val['create_time'] = substr($val['create_time'],0,10);
            }
            if(strlen($val['update_time'])>10){
                $val['update_time'] = substr($val['update_time'],0,10);
            }
    
            //所属项目
            $val['other_id'] = M('jk_project')->where('id=' . $val['projectid'])->getField('other_id');
            $val['ownid'] = M('jk_project')->where('id=' . $val['projectid'])->getField('name');
    
            //状态
            if($val['is_out_range']==0){
                $val['is_out_range']="合格";
            }else if($val['is_out_range']==1){
                $val['is_out_range']="需整改";
            }else if($val['is_out_range']==2){
                $val['is_out_range']="需质量锤";
            }
          
            if($floor[0][title]==$title){
                $newmeasureList[]=$val;
                $newtotalCount+=1;
            }
        }
        unset($val);
    
        $attr['target-form'] = 'ids';
        $attr['href'] = U('exMeasureWord');
        $attr['class']='a_jump';
        $builder->button('导出测量单', $attr)->setStatusUrl(U('setGoodsStatus'));
        //搜索框
        $builder->setSearchPostUrl(U('JKProgram/measureList'))
        ->search('时间从','usearch1','timer','','','','');
        $builder->search('到','usearch2','timer','','','','');
        $builder->setSelectPostUrl(U('JKProgram/measureList'));
       
        //根据检查项搜索1.先构建检查项数组
   
        	$allOption=M('jk_survey_option')->where('status!=-1')->field('id,title')->select();
        	$optionArr=array();
        	$optionArr[0]['id']='';
        	$optionArr[0]['value']='全部';
        	$i=1;
        	foreach ($allOption as $eachOption){
        		$optionArr[$i]['id']=$eachOption['id'];
        		$optionArr[$i]['value']=$eachOption['title'];
        		$i++;
        	}
      
        $builder->select(L('检查项：'), 'option', 'select', L('检查项'), '', '', $optionArr);
        //根据下拉列表筛选--状态
        $astauts=array(array('id' => 4, 'value' => L('全部')),array('id' => 0, 'value' => L('合格')),array('id' => 1, 'value' => L('需整改')),
            array('id' => 2, 'value' => L('需质量锤')));
     
        $builder->select(L('状态：'), 'is_out_range', 'select', L('选择状态'), '', '', $astauts);
        //根据下拉列表筛选--楼栋
        //构建最顶级的楼栋列表
        $floorlist=M('jk_floor')->where("status=1 and pid=0 and projectid='".$_SESSION['proId']."'")->order('create_time desc')->select();
        $a=array();
        $a[0]['id']='';
        $a[0]['value']='请选择';
        $i=1;
        foreach ($floorlist as $topfloor){
           $a[$i]['id']=$topfloor['title'];
           $a[$i]['value']=$topfloor['title'];
           $i++;
        }
//         echo '<pre>';
//         var_dump($a);
//         echo '</pre>';die;
//          $astauts=array(array('id' => 0, 'value' => L('合格')),array('id' => 1, 'value' => L('需整改')),
//              array('id' => 2, 'value' => L('需质量锤')));
        $builder->setSelectPostUrl(U('JKProgram/measureList'))
        ->select(L('楼栋：'), 'title', 'select', L('选择楼栋'), '', '', $a);
        
        $builder->keyId('other_id','项目编号')
        ->keyText('ownid', '项目名称')
        ->keyText('project_ids', '实测位置')
        ->keyText('inspect', '检查项')
        
        ->keyText('authid', '实测提交人')
        ->keyUpdateTime('create_time', '提交时间')
        ->keyText('is_out_range', '状态')
        ->keyDoActionEdit('JKProgram/measureedit?id=###', '详情');
        if($title!=''){
            $builder->data($newmeasureList);
            $builder->pagination($newtotalCount, $r);
        }
        else {
            $builder->data($measureList);
            $builder->pagination($totalCount, $r);
        }
        $builder->display();
    }
    /**
     * 函数用途描述：编辑项目
     * @date: 2016年11月13日
     * 
     * @author : luojun
     * @return :
     */
    public function goodsedit($id = 0, $name='')
    {
        $isEdit = $id ? 1 : 0;
        $title = $isEdit ? '修改项目' : '添加项目';
        $meta_title = $isEdit ? '修改项目' : '添加项目';
        
        $this->assign('meta_title', $meta_title);
        $this->assign('title', $title);
        if (IS_POST) {
            $pid=$_POST["pid"];
            if ($pid){
                if ($name == '' || $name == null) {
                    $this->error(L('请输项目名'));
                }
                
                $goods = $this->shopModel->create();
                
                $goods['status'] = 1;
                if ($isEdit) {
                    $goods['update_time'] = time();
                    $rs = $this->shopModel->where('id=' . $id)->save($goods);
                    // 修改节点表的数据
                    if ($rs) {
                       
                        $_SESSION['proId'] = $id;
                        $this->success(L('_SUCCESS_EDIT_'), U('JKProgram/shopcategory'));
                    }
                    $this->error(L('编辑失败！'));
                } else {
                    // 商品名存在验证
                    $map['status'] = array(
                        'gt',
                        0
                    );
                    $map['name'] = $name;
                    if ($this->shopModel->where($map)->count()) {
                        $this->error(L('项目名冲突！'));
                    }
                    
                    $goods['create_time'] = time();
                    $goods['update_time'] = time();
                    $rs = $this->shopModel->add($goods);
                    if ($rs) {
                        // 执行项目节点添加
                        $projectmenu = D('menu');
                        $menupid = $projectmenu->where("title='项目分配'")->getField('id');
                        if ($menupid > 0) {
                            
                            $_SESSION['proId'] = $rs;
                            $this->success(L('_SUCCESS_ADD_'), U('Index/index'));
                        } else {
                            $this->error(L('_FAIL_ADD_'));
                        }
                    } else {
                        $this->error(L('_FAIL_ADD_'));
                    }
                }
            }else{
            $this->error('未选择组织架构');
            }
        } else {
            
            // $builder->keyId()->keyText('other_id','项目编号')->keyText('name', '项目名称')
            // ->keyText('other_name', '项目别名')
            // ->keyText('periods', '项目分期数','默认值5')->keyText('blocks', '项目标段数','默认值5')
            // ->keyText('batch', '项目批次数','默认值5')
            // ->keyMultiImage('mapid', '图片详情','',10);
            $map = array(
                'status' => array(
                    'gt',
                    0
                ),
            );
            $list = M('AuthGroup')->field('id,title,pid')
            ->where($map)
            ->select();
            if(IS_ROOT){
    			$list=list_to_tree($list, 'id', 'pid', '_', 0);
    		}
    		else{   			
    			$initPid = $auth_group['id'];
    			$group_ids = M('auth_group_access')->where("uid=".UID)->field('group_id')->select();
                $alist = array();
                foreach ($group_ids as $v) {
    				$map  = array('status' => array('gt', 0),'id'=>$v['group_id']);
    				$glist=M('AuthGroup')->field('id,title,pid')->where($map)->find();
    				
                    $glistTem=list_to_tree($list,'id','pid','_',$v['group_id']);
            	    if($glistTem){
    					$glist['_']=$glistTem;
    					// dump($glist);
    				}
            	    $alist[] = $glist;
                }
                $list=$alist;
    		}
           
            $this->assign('nodeList', $list);
            
            
            $goods = array();
            if ($isEdit) {
                $goods = $this->shopModel->where('id=' . $id)->find();
                $topgroup = M('auth_group')->field('id,title')
                ->where("id=".$goods['pid'])
                ->find();
                $this->assign('pid', $topgroup['id']);
                $this->assign('group_title', $topgroup['title']);
            } else {
                $goods['status']  = 1;
                $goods['periods'] = 5;
                $goods['blocks']  = 5;
                $goods['batch']   = 5;
            }
            $area=M('jk_area')->select();
            $this->assign('area', $area);
            $this->assign('data', $goods);
            $this->display('/JKProgram@JKProgram/editProject');
        }
    }

    /**
    * 函数用途描述: 选择模板导出其他文件
    * @date: 2017年2月9日 上午11:11:56
    * @author: luojun
    * @param: 
    * @return:
    */
    public function exotherword(){
        $builder = new AdminListBuilder();
        $builder->title('导出文件模板列表');
        $builder->meta_title = '导出文件模板列表';
        
        $attr=array();
        $attr['target-form'] = 'ids';
        $attr['href'] = U('doexotherword');
        $attr['class']='a_jump2';
        $builder->button('导出文件', $attr);
               
        //列表
        $builder->keyId('id','模板编号')
        ->keyText('name', '模板名称');
       
        $aList =array(
            array('id'=>1,'name'=>'重庆监理用表_工程暂停令'),
            array('id'=>2,'name'=>'重庆监理用表_监理通知回复单'),
        	array('id'=>3,'name'=>'重庆监理用表_工作联系单'),
        	array('id'=>4,'name'=>'四川监理用表_工程暂停令'),
        	array('id'=>5,'name'=>'四川监理用表_监理通知回复单'),
        	array('id'=>6,'name'=>'四川监理用表_工作联系单'),
        	array('id'=>7,'name'=>'北京监理用表_工程暂停令'),
        	array('id'=>8,'name'=>'北京监理用表_监理通知回复单'),
        	array('id'=>9,'name'=>'北京监理用表_工作联系单'),
        	array('id'=>10,'name'=>'湖南监理用表_工程暂停令'),
        	array('id'=>11,'name'=>'湖南监理用表_监理通知回复单'),
        	array('id'=>12,'name'=>'湖南监理用表_工作联系单'),
        	array('id'=>13,'name'=>'陕西监理用表_工程暂停令'),
        	array('id'=>14,'name'=>'陕西监理用表_监理通知回复单'),
        	array('id'=>15,'name'=>'陕西监理用表_工作联系单'),
        	array('id'=>16,'name'=>'云南监理用表_工程暂停令'),
        	array('id'=>17,'name'=>'云南监理用表_监理通知回复单'),
        	array('id'=>18,'name'=>'云南监理用表_工作联系单'),
        	array('id'=>19,'name'=>'郑州监理用表_工程暂停令'),
        	array('id'=>20,'name'=>'郑州监理用表_监理通知回复单'),
        	array('id'=>21,'name'=>'郑州监理用表_工作联系单'),
        	array('id'=>22,'name'=>'贵州监理用表_工程暂停令'),
        	array('id'=>23,'name'=>'贵州监理用表_监理通知回复单'),
        	array('id'=>24,'name'=>'贵州监理用表_工作联系单'),
        	array('id'=>25,'name'=>'新疆监理用表_工程暂停令'),
        	array('id'=>26,'name'=>'新疆监理用表_监理通知回复单'),
        	array('id'=>27,'name'=>'新疆监理用表_工作联系单'),
        	array('id'=>28,'name'=>'安徽监理用表_工程暂停令'),
        	array('id'=>29,'name'=>'安徽监理用表_监理通知回复单'),
        	array('id'=>30,'name'=>'安徽监理用表_工作联系单'),
        	array('id'=>31,'name'=>'江苏监理用表_工程暂停令'),
        	array('id'=>32,'name'=>'江苏监理用表_监理通知回复单'),
        	array('id'=>33,'name'=>'江苏监理用表_工作联系单'),
        	array('id'=>34,'name'=>'山东监理用表_工程暂停令'),
        	array('id'=>35,'name'=>'山东监理用表_监理通知回复单'),
        	array('id'=>36,'name'=>'山东监理用表_工作联系单'),
        );
        $builder->data($aList);
        $builder->display();
    }
    
    /**
     * 函数用途描述: 导出其他文件
     * @date: 2017年2月9日 上午11:11:56
     * @author: luojun
     * @param:
     * @return:
     */
    public function doexotherword(){
        $ids = array_unique((array)I('ids', 0));
        if (! $ids) {
            $this->error("未选择操作数据！");
        }
        $date =  date('Y年m月d日',time());
        $area=json_decode($_COOKIE['templateid']);
        //构建区域模块变量
        foreach ($area as $a) {
        	$template = $a->value;
        }
        $year=date("Y");
        $this->assign("year", $year);
        
       // echo $template;die;
        $content = $this->fetch('/JKProgram@JKProgram/exotherWord'.$template);
        
        $flieName = iconv("UTF-8", "GBK", $date."导出文件.doc");
                
        $content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了
        
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片
                                                                                                                       // echo $html.$content.'</html>';
        $fp = fopen($flieName, 'w');
        fwrite($fp, $html . $content . '</html>');
        fclose($fp);
        
        
//         $this->display('/JKProgram@JKProgram/exotherWord1');
        header("location:".$date."导出文件.doc");
    }
    
    /**
     * 函数用途描述：导出word
     * @date: 2016年10月25日 上午10:20:16
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function exWord()
    {
        $arr = json_decode($_COOKIE['prids']);
     
        if (! $arr) {
            $this->error("未选择操作数据！");
        }
        foreach ($arr as $obj) {
            $ids[] = $obj->value;
        }
        $map['init_id'] = array(
            'in',
            $ids
        );
        $list = M("jk_program")->where($map)->select();
        foreach ($list as $v){
        	if($v['authid']!=session('user_auth.uid') && session('user_auth.uid')!=1){
        		//echo UID;
        		$this->error("请勿提交非本人提交的报表");
        	}
        }
        $data = array();
        $allIamges = '';
        // 选项问题
        foreach ($list as &$val) {
            // 问题选项【检查项】
            if($val['type']>0){
                $option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
            }
            else
                $option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');
           
            $val['id'] = $val['init_id'];
            
            $val['option_id'] = $option;
            unset($option);
            
            // 楼栋数字截取
            $loudong_j = substr($val['project_ids'], - 1);
            if ($loudong_j == ',') {
                $val['project_ids'] = substr($val['project_ids'], 0, - 1);
            }
            
            // 楼栋【问题具体位置】
            $floor = M('jk_floor')->where("id in (" . $val['project_ids'] . ")")
                ->field('title')
                ->select();
            $loudong = "";
            foreach ($floor as $value) {
            	if($value['title'] != '')
                	$loudong = $loudong . $value['title'] . '、';
            }
            $changdu = mb_strlen($loudong, 'utf-8');
            $hangye = mb_substr($loudong, 0, $changdu - 1, 'utf-8');
            $val['project_ids'] = $hangye;
            
            // 问题提交人
           // $val['authid'] = M('member')->where('uid=' . $val['authid'])->getField('nickname');
            $val['authid'] = M('member')->where('uid=' . $val['authid'])->getField('username');
        
            $val['target'] = M('auth_group')->where('id=' . $val['target_id'])->getField('title');
            // 时间处理
            if (strlen($val['create_time']) > 10) {
                $val['create_time'] = substr($val['create_time'], 0, 10);
            }
            $val['create_time'] = date("Y-m-d", $val['create_time']);
            // 所属项目
            $val['other_id'] = M('jk_project')->where('id=' . $val['ownid'])->getField('other_id');
            $val['ownid'] = M('jk_project')->where('id=' . $val['ownid'])->getField('name');
            
            $data[$targetGroupId][] = $val;
            $allIamges .= $val['mapid'];
        }
        unset($val);
        $allPaths = coverIds2Path($allIamges);
        $apath = explode(',', $allPaths);
   		$year=date("Y");
   		$this->assign("year", $year);
        $this->assign("data", $data);
        $this->assign("apath", $apath);
       
        $content = $this->fetch('/JKProgram@JKProgram/exWord'.$_COOKIE['areanum']);
      
        
       
        
        // $fileContent = $this->getWordDocument($content,$_SERVER['HTTP_HOST'].'/',0);
        $flieName = iconv("UTF-8", "GBK", "整改通知单.doc");
        
        $content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了
        
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片
                                                                                                                       // echo $html.$content.'</html>';
        $fp = fopen($flieName, 'w');
        // dump($html.$content.'</html>');
        fwrite($fp, $html . $content . '</html>');
        fclose($fp);
        // header('pragma:public');
        // header('Content-type:application/vnd.ms-word;charset=utf-8;name='.$flieName);
        // header("Content-Disposition:attachment;filename=$flieName");//attachment新窗口打印inline本窗口打印
        header("location:整改通知单.doc");
        
        // $this->display('/JKProgram@JKProgram/exWord');
    }
    /**
     * 函数用途描述：导出回复单word
     * @date: 2017年02月09日 下午13:52:16
     *
     * @author : 谭杰文
     * @param
     *            :
     * @return :
     */
    public function exhuiWord()
    {
    	$arr = json_decode($_COOKIE['prids']);
    	
    	if (! $arr) {
    		$this->error("未选择操作数据！");
    	}
    	foreach ($arr as $obj) {
    		$ids[] = $obj->value;
    	}
    	$map['init_id'] = array(
    			'in',
    			$ids
    	);
    	$list = M("jk_program")->where($map)->select();
    	foreach ($list as $v){
    		if($v['authid']!=session('user_auth.uid') && session('user_auth.uid')!=1){
    			//echo UID;
    			$this->error("请勿提交非本人提交的报表");
    		}
    	}
    	$data = array();
    	$allIamges = '';
    	// 选项问题
    	foreach ($list as &$val) {
    		// 问题选项【检查项】
    		if($val['type']>0){
    			$option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
    		}
    		else
    			$option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');
    		 
    		$val['id'] = $val['init_id'];
    
    		$val['option_id'] = $option;
    		unset($option);
    
    		// 楼栋数字截取
    		$loudong_j = substr($val['project_ids'], - 1);
    		if ($loudong_j == ',') {
    			$val['project_ids'] = substr($val['project_ids'], 0, - 1);
    		}
    
    		// 楼栋【问题具体位置】
    		$floor = M('jk_floor')->where("id in (" . $val['project_ids'] . ")")
    		->field('title')
    		->select();
    		$loudong = "";
    		foreach ($floor as $value) {
    			$loudong = $loudong . $value['title'] . '、';
    		}
    		$changdu = mb_strlen($loudong, 'utf-8');
    		$hangye = mb_substr($loudong, 0, $changdu - 1, 'utf-8');
    		$val['project_ids'] = $hangye;
    
    		// 问题提交人
    		$val['authid'] = M('member')->where('uid=' . $val['authid'])->getField('username');  		
    		$val['target'] = M('auth_group')->where('id=' . $val['target_id'])->getField('title');
    		// 时间处理
    		if (strlen($val['create_time']) > 10) {
    			$val['create_time'] = substr($val['create_time'], 0, 10);
    		}
    		$val['create_time'] = date("Y-m-d", $val['create_time']);
    		// 所属项目
    		$val['other_id'] = M('jk_project')->where('id=' . $val['ownid'])->getField('other_id');
    		$val['ownid'] = M('jk_project')->where('id=' . $val['ownid'])->getField('name');
    
    		$data[$targetGroupId][] = $val;
    		$allIamges .= $val['mapid'];
    	}
    	unset($val);
    	$allPaths = coverIds2Path($allIamges);
    	$apath = explode(',', $allPaths);
    	$year=date("Y");
    	$this->assign("year", $year);
    	$this->assign("data", $data);
    	$this->assign("apath", $apath);
   
    	$content .= $this->fetch('/JKProgram@JKProgram/exhuiWord'.$_COOKIE['areanum']);
    	
    	
    	$flieName = iconv("UTF-8", "GBK", "整改回复通知单.doc");
    	$content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了
    	$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片
    
    	$fp = fopen($flieName, 'w');
    	
    	fwrite($fp, $html . $content . '</html>');
    	fclose($fp);
    	header("location:整改回复通知单.doc");
    }
    public function exALLWord()
    {
    	$arr = json_decode($_COOKIE['prids']);
    	$area=json_decode($_COOKIE['areanum']);
    	if (! $arr) {
    		$this->error("未选择操作数据！");
    	}
    	foreach ($arr as $obj) {
    		$ids[] = $obj->value;
    	}
    	$map['init_id'] = array(
    			'in',
    			$ids
    	);
    	$list = M("jk_program")->where($map)->select();
    	$data = array();
    	$allIamges = '';
    	// 选项问题
    	foreach ($list as &$val) {
    		// 问题选项【检查项】
    		if($val['type']>0){
    			$option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
    		}
    		else
    			$option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');
    		 
    		$val['id'] = $val['init_id'];
    
    		$val['option_id'] = $option;
    		unset($option);
    
    		// 楼栋数字截取
    		$loudong_j = substr($val['project_ids'], - 1);
    		if ($loudong_j == ',') {
    			$val['project_ids'] = substr($val['project_ids'], 0, - 1);
    		}
    
    		// 楼栋【问题具体位置】
    		$floor = M('jk_floor')->where("id in (" . $val['project_ids'] . ")")
    		->field('title')
    		->select();
    		$loudong = "";
    		foreach ($floor as $value) {
    			$loudong = $loudong . $value['title'] . '、';
    		}
    		$changdu = mb_strlen($loudong, 'utf-8');
    		$hangye = mb_substr($loudong, 0, $changdu - 1, 'utf-8');
    		$val['project_ids'] = $hangye;
    
    		// 问题提交人
    		$val['authid'] = M('member')->where('uid=' . $val['authid'])->getField('username');
    		$val['target'] = M('auth_group')->where('id=' . $val['target_id'])->getField('title');
    		// 时间处理
    		if (strlen($val['create_time']) > 10) {
    			$val['create_time'] = substr($val['create_time'], 0, 10);
    		}
    		$val['create_time'] = date("Y-m-d", $val['create_time']);
    		// 所属项目
    		$val['other_id'] = M('jk_project')->where('id=' . $val['ownid'])->getField('other_id');
    		$val['ownid'] = M('jk_project')->where('id=' . $val['ownid'])->getField('name');
    
    		$data[$targetGroupId][] = $val;
    		$allIamges .= $val['mapid'];
    	}
    	unset($val);
    	$allPaths = coverIds2Path($allIamges);
    	$apath = explode(',', $allPaths);
    	$year=date("Y");
    	$this->assign("year", $year);
    	$this->assign("data", $data);
    	$this->assign("apath", $apath);
    	//构建区域模块变量
    	//var_dump($area);die;
    	$date =  date('Y年m月d日',time());
    	foreach ($area as $a) {
    		$template = $a->value;
    		$content .= $this->fetch('/JKProgram@JKProgram/exALLWord'.$template);
    	}
    	
    	$flieName = iconv("UTF-8", "GBK", $date."导出文件.doc");
    	$content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了
    	$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片
    
    	$fp = fopen($flieName, 'w');
    	
    	fwrite($fp, $html . $content . '</html>');
    	fclose($fp);
    	header("location:".$date."导出文件.doc");
    }
    /**
     * 函数用途描述：导出工作联系单word
     * @date: 2017年02月10日 下午11:52:16
     *
     * @author : 谭杰文
     * @param
     *            :
     * @return :
     */
    public function excontactWord()
    {
    	$arr = json_decode($_COOKIE['prids']);
    	if (! $arr) {
    		$this->error("未选择操作数据！");
    	}
    	foreach ($arr as $obj) {
    		$ids[] = $obj->value;
    	}
    	$map['init_id'] = array(
    			'in',
    			$ids
    	);

    	$list = M("jk_program")->where($map)->select();
    	foreach ($list as $v){
    		if($v['authid']!=session('user_auth.uid') && session('user_auth.uid')!=1){
    			//echo UID;
    			$this->error("请勿提交非本人提交的报表");
    		}
    	}
    	$data = array();
    	$allIamges = '';
    	// 选项问题
    	foreach ($list as &$val) {
    		// 问题选项【检查项】
    		if($val['type']>0){
    			$option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
    		}
    		else
    			$option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');
    		 
    		$val['id'] = $val['init_id'];
    
    		$val['option_id'] = $option;
    		unset($option);
    
    		// 楼栋数字截取
    		$loudong_j = substr($val['project_ids'], - 1);
    		if ($loudong_j == ',') {
    			$val['project_ids'] = substr($val['project_ids'], 0, - 1);
    		}
    
    		// 楼栋【问题具体位置】
    		$floor = M('jk_floor')->where("id in (" . $val['project_ids'] . ")")
    		->field('title')
    		->select();
    		$loudong = "";
    		foreach ($floor as $value) {
    			$loudong = $loudong . $value['title'] . '、';
    		}
    		$changdu = mb_strlen($loudong, 'utf-8');
    		$hangye = mb_substr($loudong, 0, $changdu - 1, 'utf-8');
    		$val['project_ids'] = $hangye;
    
    		// 问题提交人
    		// $val['authid'] = M('member')->where('uid=' . $val['authid'])->getField('nickname');
    		$val['authid'] = M('member')->where('uid=' . $val['authid'])->getField('username');
    		$val['target'] = M('auth_group')->where('id=' . $val['target_id'])->getField('title');
    		// 时间处理
    		if (strlen($val['create_time']) > 10) {
    			$val['create_time'] = substr($val['create_time'], 0, 10);
    		}
    		$val['create_time'] = date("Y-m-d", $val['create_time']);
    		// 所属项目
    		$val['other_id'] = M('jk_project')->where('id=' . $val['ownid'])->getField('other_id');
    		$val['ownid'] = M('jk_project')->where('id=' . $val['ownid'])->getField('name');
    
    		$data[$targetGroupId][] = $val;
    		$allIamges .= $val['mapid'];
    	}
    	unset($val);
    	$allPaths = coverIds2Path($allIamges);
    	$apath = explode(',', $allPaths);
    	$year=date("Y");
    	$this->assign("year", $year);
    	$this->assign("data", $data);
    	$this->assign("apath", $apath);
    	
    	$content .= $this->fetch('/JKProgram@JKProgram/excontactWord'.$_COOKIE['areanum']);
    	
    
    	$flieName = iconv("UTF-8", "GBK", "工作联系单.doc");
    
    	$content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了
    
    	$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片
    	$fp = fopen($flieName, 'w');
    	fwrite($fp, $html . $content . '</html>');
    	fclose($fp);
    	header("location:工作联系单.doc");
    }
    /**
     * 函数用途描述：导出暂停令word
     * @date: 2017年02月10日 下午11:52:16
     *
     * @author : 谭杰文
     * @param
     *            :
     * @return :
     */
    public function exstopWord()
    {
    	$arr = json_decode($_COOKIE['prids']);
    	if (! $arr) {
    		$this->error("未选择操作数据！");
    	}
    	foreach ($arr as $obj) {
    		$ids[] = $obj->value;
    	}
    	$map['init_id'] = array(
    			'in',
    			$ids
    	);
    	// $map['init_id'] = '1478054959678100';
    	// $map['status']=0;
    	$list = M("jk_program")->where($map)->select();
    	foreach ($list as $v){
    		if($v['authid']!=session('user_auth.uid') && session('user_auth.uid')!=1){
    			//echo UID;
    			$this->error("请勿提交非本人提交的报表");
    		}
    	}
    	$data = array();
    	$allIamges = '';
    	// 选项问题
    	//拼接暂停问题选项
    	$stopReason="";
    	foreach ($list as &$val) {
    		// 问题选项【检查项】
    		if($val['type']>0){
    			$option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
    		}
    		else
    			$option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');
    		 
    		$val['id'] = $val['init_id'];
    
    		$val['option_id'] = $option;
    		
    		unset($option);
    
    		// 楼栋数字截取
    		$loudong_j = substr($val['project_ids'], - 1);
    		if ($loudong_j == ',') {
    			$val['project_ids'] = substr($val['project_ids'], 0, - 1);
    		}
    
    		// 楼栋【问题具体位置】
    		$floor = M('jk_floor')->where("id in (" . $val['project_ids'] . ")")
    		->field('title')
    		->select();
    		$loudong = "";
    		foreach ($floor as $value) {
    			$loudong = $loudong . $value['title'] . '、';
    		}
    		$changdu = mb_strlen($loudong, 'utf-8');
    		$hangye = mb_substr($loudong, 0, $changdu - 1, 'utf-8');
    		$val['project_ids'] = $hangye;
    		$stopReason  .= $val['project_ids']."位置".$val['option_id'].";";
    		// 问题提交人
    		// $val['authid'] = M('member')->where('uid=' . $val['authid'])->getField('nickname');
    		$val['authid'] = M('member')->where('uid=' . $val['authid'])->getField('username');
    		
    		$val['target'] = M('auth_group')->where('id=' . $val['target_id'])->getField('title');
    		// 时间处理
    		if (strlen($val['create_time']) > 10) {
    			$val['create_time'] = substr($val['create_time'], 0, 10);
    		}
    		$val['create_time'] = date("Y-m-d", $val['create_time']);
    		// 所属项目
    		$val['other_id'] = M('jk_project')->where('id=' . $val['ownid'])->getField('other_id');
    		$val['ownid'] = M('jk_project')->where('id=' . $val['ownid'])->getField('name');
    
    		$data[$targetGroupId][] = $val;
    		$allIamges .= $val['mapid'];
    	}
    	unset($val);
    	$allPaths = coverIds2Path($allIamges);
    	$apath = explode(',', $allPaths);
    	$year=date("Y");
    	$this->assign("stopReason", $stopReason);
    	$this->assign("year", $year);
    	$this->assign("data", $data);
    	$this->assign("apath", $apath);
    	//构建区域模块变量
    	
    	$content .= $this->fetch('/JKProgram@JKProgram/exstopWord'.$_COOKIE['areanum']);
    	
    
    
    	// $fileContent = $this->getWordDocument($content,$_SERVER['HTTP_HOST'].'/',0);
    	$flieName = iconv("UTF-8", "GBK", "暂停令.doc");

    
    	$content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了
    
    	$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片
    	// echo $html.$content.'</html>';
    	$fp = fopen($flieName, 'w');
    	// dump($html.$content.'</html>');
    	fwrite($fp, $html . $content . '</html>');
    	fclose($fp);
    	// header('pragma:public');
    	// header('Content-type:application/vnd.ms-word;charset=utf-8;name='.$flieName);
    	// header("Content-Disposition:attachment;filename=$flieName");//attachment新窗口打印inline本窗口打印
    	header("location:暂停令.doc");
    
    	// $this->display('/JKProgram@JKProgram/exWord');
    }
    /**
     * 函数用途描述：导出暂停令word
     * @date: 2017年02月10日 下午11:52:16
     *
     * @author : 谭杰文
     * @param
     *            :
     * @return :
     */
    public function exstartWord()
    {
    	$arr = json_decode($_COOKIE['prids']);
    	if (! $arr) {
    		$this->error("未选择操作数据！");
    	}
    	foreach ($arr as $obj) {
    		$ids[] = $obj->value;
    	}
    	$map['init_id'] = array(
    			'in',
    			$ids
    	);
    	// $map['init_id'] = '1478054959678100';
    	// $map['status']=0;
    	$list = M("jk_program")->where($map)->select();
    	foreach ($list as $v){
    		if($v['authid']!=session('user_auth.uid') && session('user_auth.uid')!=1){
    			//echo UID;
    			$this->error("请勿提交非本人提交的报表");
    		}
    	}
    	$data = array();
    	$allIamges = '';
    	// 选项问题
    	//拼接暂停问题选项
    	$stopReason="";
    	foreach ($list as &$val) {
    		// 问题选项【检查项】
    		if($val['type']>0){
    			$option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
    		}
    		else
    			$option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');
    		 
    		$val['id'] = $val['init_id'];
    
    		$val['option_id'] = $option;
    
    		unset($option);
    
    		// 楼栋数字截取
    		$loudong_j = substr($val['project_ids'], - 1);
    		if ($loudong_j == ',') {
    			$val['project_ids'] = substr($val['project_ids'], 0, - 1);
    		}
    
    		// 楼栋【问题具体位置】
    		$floor = M('jk_floor')->where("id in (" . $val['project_ids'] . ")")
    		->field('title')
    		->select();
    		$loudong = "";
    		foreach ($floor as $value) {
    			$loudong = $loudong . $value['title'] . '、';
    		}
    		$changdu = mb_strlen($loudong, 'utf-8');
    		$hangye = mb_substr($loudong, 0, $changdu - 1, 'utf-8');
    		$val['project_ids'] = $hangye;
    		$stopReason  .= $val['project_ids']."位置".$val['option_id'].";";
    		// 问题提交人
    		// $val['authid'] = M('member')->where('uid=' . $val['authid'])->getField('nickname');
    		$val['authid'] = M('member')->where('uid=' . $val['authid'])->getField('username');
    
    		$val['target'] = M('auth_group')->where('id=' . $val['target_id'])->getField('title');
    		// 时间处理
    		if (strlen($val['create_time']) > 10) {
    			$val['create_time'] = substr($val['create_time'], 0, 10);
    		}
    		$val['create_time'] = date("Y-m-d", $val['create_time']);
    		// 所属项目
    		$val['other_id'] = M('jk_project')->where('id=' . $val['ownid'])->getField('other_id');
    		$val['ownid'] = M('jk_project')->where('id=' . $val['ownid'])->getField('name');
    
    		$data[$targetGroupId][] = $val;
    		$allIamges .= $val['mapid'];
    	}
    	unset($val);
    	$allPaths = coverIds2Path($allIamges);
    	$apath = explode(',', $allPaths);
    	$year=date("Y");
    	$this->assign("stopReason", $stopReason);
    	$this->assign("year", $year);
    	$this->assign("data", $data);
    	$this->assign("apath", $apath);
    	if($_COOKIE['areanum']!=2)
    		$this->error("所属城市没有复工令模板");
    	$content .= $this->fetch('/JKProgram@JKProgram/exstartWord'.$_COOKIE['areanum']);
    	
    
    	$flieName = iconv("UTF-8", "GBK", "复工令.doc");
    	$content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了    
    	$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片
    	$fp = fopen($flieName, 'w');
    	fwrite($fp, $html . $content . '</html>');
    	fclose($fp);
    	header("location:复工令.doc");
    
    	// $this->display('/JKProgram@JKProgram/exWord');
    }
    /**
     * 函数用途描述：导出实测word
     * @date: 2016年10月25日 上午10:20:16
     *
     * @author : tanjiewen
     * @param
     *            :
     * @return :
     */
    public function exMeasureWord()
    {
    	$arr = json_decode($_COOKIE['prids']);
    	if (! $arr) {
    		$this->error("未选择操作数据！");
    	}
    	foreach ($arr as $obj) {
    		$ids[] = $obj->value;
    	}
    	$map['id'] = array(
    			'in',
    			$ids
    	);
    	$list = M("jk_check_point")->where($map)->select();
    	$data = array();
   	    $measurezhi=array();
    	// 选项问题
    	foreach ($list as &$val) {
    		// 【检查项】
    		$option = M('jk_survey_option')->where('id=' . $val['inspect'])->getField('title');	 
    		$val['option_id'] = $option;
    		unset($option);
    		//检查项对应的合格值，整改值，质量锤值
    		$measureinfo= M('jk_survey_option')->where('id=' . $val['inspect'])->find();
    		$val['minqualified']=(float)$measureinfo['minqualified'];
    		$val['maxqualified']=(float)$measureinfo['maxqualified'];
    		//判断是否为特殊项：轴线偏差
    		if($val['inspect']!=15){
    		$val['minzhenggai']=$val['minqualified']*1.5;
    		$val['maxzhenggai']=$val['maxqualified']*1.5;
  
    		$val['mindestroy']=$measureinfo['mindestroy'];
    		$val['maxdestroy']=$measureinfo['maxdestroy'];
    		}else{
    			$val['minzhenggai']="";
    			$val['maxzhenggai']="";
    			$val['mindestroy']=$val['minqualified']*1.5;
    			$val['maxdestroy']=$val['maxqualified']*1.5;
    		}
    		if($val['inspect']==14){
    			$val['minzhenggai']="";
    			$val['maxzhenggai']="";
    			$val['mindestroy']="";
    			$val['maxdestroy']="";
    		}
    	
    		// 楼栋数字截取
    		$loudong_j = substr($val['postion'], - 1);
    		if ($loudong_j == ',') {
    			$val['project_ids'] = substr($val['postion'], 0, - 1);
    		}
    
    		// 楼栋【问题具体位置】
    		$floor = M('jk_floor')->where("id in (" . $val['project_ids'] . ")")
    		->field('title')
    		->select();
    		$loudong = "";
    		foreach ($floor as $value) {
    			$loudong = $loudong . $value['title'] . '-';
    		}
    		$changdu = mb_strlen($loudong, 'utf-8');
    		$hangye = mb_substr($loudong, 0, $changdu - 1, 'utf-8');
    		$val['project_ids'] = $hangye;
    
    		// 测量提交人
    		$val['authid'] = M('member')->where('uid=' . $val['userid'])->getField('username');
    		// 时间处理
    		if (strlen($val['create_time']) > 10) {
    			$val['create_time'] = substr($val['create_time'], 0, 10);
    		}
    		$val['create_time'] = date("Y-m-d", $val['create_time']);
    		// 所属项目
    		$val['other_id'] = M('jk_project')->where('id=' . $val['projectid'])->getField('other_id');
    		$val['ownid'] = M('jk_project')->where('id=' . $val['projectid'])->getField('name');
    		$val['info']=explode('|',$val['info']);
    		//当为特殊项时计算差值
    		$i=0;
    		foreach ($val['info'] as $vv){
    		    
		        $nums=explode(',',$vv);
		      
		        if(count($nums)>1){
            	    $min=min($nums);
            	    $max=max($nums);
            	    $cha=max-min;
            	    if($val['inspect']=='4')//如果为楼板厚度，则为后一个减去前一个
            	    {
            	       $cha=$nums[1]-$nums[0];
        	        } 
        	        $val['info'][$i] .= '('.$cha.')';
		        }
		        $i++;
    		}
//     		echo '<pre>';
//     		var_dump($val['info'][0]);
//     		echo '</pre>';die;
    		$data[] = $val;
    		
    	}
    	unset($val);
//     	echo '<pre>';
//     	var_dump($data);
//     	echo '</pre>';
//     	die;
    	//$this->assign("cha1", $cha1);
    	$this->assign("data", $data);
    	$this->assign("measurezhi", $measurezhi);
    	$this->assign("apath", $apath);
    	$content = $this->fetch('/JKProgram@JKProgram/exMeasureWord');
 
    	$flieName = iconv("UTF-8", "GBK", "测量通知单.doc");
    
    	$content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了
    
    	$html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片
    	// echo $html.$content.'</html>';
    	$fp = fopen($flieName, 'w');
    	// dump($html.$content.'</html>');
    	fwrite($fp, $html . $content . '</html>');
    	fclose($fp);
    	header("location:测量通知单.doc");
    
    	// $this->display('/JKProgram@JKProgram/exWord');
    }
    
    public function refundExcel(){
    	//echo dirname(__FILE__);
    	
    	//require("./ThinkPHP/library/Vendor/PHPExcel.php");
    	$objPHPExcel = new \PHPExcel();
    	$objPHPExcel->getProperties()->setCreator("measureExcel")
    	->setLastModifiedBy("measureExcel")
    	->setTitle("Office 2007 XLSX Test Document")
    	->setSubject("Office 2007 XLSX Test Document")
    	->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
    	->setKeywords("office 2007 openxml php")
    	->setCategory("Test result file");
    	//set width
    	$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
    	$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
    	$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
    	$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
    	$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
    	$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
    	$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(30);
    	$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(30);
    	//合并cell
    	$objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
    	header('Content-Type: application/vnd.ms-excel');
    	header('Content-Disposition: attachment;filename="销售额统计表().xls"');  //日期为文件名后缀
    	header('Cache-Control: max-age=0');
    	//$objPHPExcel->setActiveSheetIndex(0);
    	
    	
    	$arr = json_decode($_COOKIE['prids']);
    	if (! $arr) {
    		$this->error("未选择操作数据！");
    	}
    	foreach ($arr as $obj) {
    		$ids[] = $obj->value;
    	}
    	$map['id'] = array(
    			'in',
    			$ids
    	);
    	$list = M("jk_check_point")->where($map)->select();
    	$data = array();
    	$measurezhi=array();
    	// 选项问题
    	foreach ($list as &$val) {
    		// 【检查项】
    		$option = M('jk_survey_option')->where('id=' . $val['inspect'])->getField('title');
    		$val['option_id'] = $option;
    		unset($option);
    		//检查项对应的合格值，整改值，质量锤值
    		$measureinfo= M('jk_survey_option')->where('id=' . $val['inspect'])->find();
    		$val['minqualified']=(float)$measureinfo['minqualified'];
    		$val['maxqualified']=(float)$measureinfo['maxqualified'];
    		//判断是否为特殊项：轴线偏差
    		if($val['inspect']!=15){
    			$val['minzhenggai']=$val['minqualified']*1.5;
    			$val['maxzhenggai']=$val['maxqualified']*1.5;
    	
    			$val['mindestroy']=$measureinfo['mindestroy'];
    			$val['maxdestroy']=$measureinfo['maxdestroy'];
    		}else{
    			$val['minzhenggai']="";
    			$val['maxzhenggai']="";
    			$val['mindestroy']=$val['minqualified']*1.5;
    			$val['maxdestroy']=$val['maxqualified']*1.5;
    		}
    		if($val['inspect']==14){
    			$val['minzhenggai']="";
    			$val['maxzhenggai']="";
    			$val['mindestroy']="";
    			$val['maxdestroy']="";
    		}
    		 
    		// 楼栋数字截取
    		$loudong_j = substr($val['postion'], - 1);
    		if ($loudong_j == ',') {
    			$val['project_ids'] = substr($val['postion'], 0, - 1);
    		}
    	
    		// 楼栋【问题具体位置】
    		$floor = M('jk_floor')->where("id in (" . $val['project_ids'] . ")")
    		->field('title')
    		->select();
    		$loudong = "";
    		foreach ($floor as $value) {
    			$loudong = $loudong . $value['title'] . '-';
    		}
    		$changdu = mb_strlen($loudong, 'utf-8');
    		$hangye = mb_substr($loudong, 0, $changdu - 1, 'utf-8');
    		$val['project_ids'] = $hangye;
    	
    		// 测量提交人
    		$val['authid'] = M('member')->where('uid=' . $val['userid'])->getField('username');
    		// 时间处理
    		if (strlen($val['create_time']) > 10) {
    			$val['create_time'] = substr($val['create_time'], 0, 10);
    		}
    		$val['create_time'] = date("Y-m-d", $val['create_time']);
    		// 所属项目
    		$val['other_id'] = M('jk_project')->where('id=' . $val['projectid'])->getField('other_id');
    		$val['ownid'] = M('jk_project')->where('id=' . $val['projectid'])->getField('name');
    		$val['info']=explode('|',$val['info']);
    		$objPHPExcel->setActiveSheetIndex(0)
    		->setCellValue('A1', '工程名称'.$val['ownid'])->setCellValue('A2', '检查项:'.$val['option_id'])
    		->setCellValue('D2', '检查位置:'.$val['project_ids'])->setCellValue('G2', '检查人:'.$val['authid']);
    		//$data[] = $val;
    		
    	}
    	$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  //excel5为xls格式，excel2007为xlsx格式
    	$objWriter->save('php://output');die;
    }
    /**
     * 根据HTML代码获取word文档内容
     * 创建一个本质为mht的文档，该函数会分析文件内容并从远程下载页面中的图片资源
     * 该函数依赖于类MhtFileMaker
     * 该函数会分析img标签，提取src的属性值。但是，src的属性值必须被引号包围，否则不能提取
     *
     * @param string $content
     *            HTML内容
     * @param string $absolutePath
     *            网页的绝对路径。如果HTML内容里的图片路径为相对路径，那么就需要填写这个参数，来让该函数自动填补成绝对路径。这个参数最后需要以/结束
     * @param bool $isEraseLink
     *            是否去掉HTML内容中的链接
     */
    private function getWordDocument($content, $absolutePath = "", $isEraseLink = true)
    {
        $mht = new \JKProgram\Controller\MhtFileMaker();
        if ($isEraseLink)
            $content = preg_replace('/<a\s*.*?\s*>(\s*.*?\s*)<\/a>/i', '$1', $content); // 去掉链接
        
        $images = array();
        $files = array();
        $matches = array();
        // 这个算法要求src后的属性值必须使用引号括起来
        if (preg_match_all('/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i', $content, $matches)) {
            
            $arrPath = $matches[2];
            for ($i = 0; $i < count($arrPath); $i ++) {
                $path = $arrPath[$i];
                $imgPath = trim($path);
                if ($imgPath != "") {
                    
                    if (substr($imgPath, 0, 7) == 'http://') {
                        // 绝对链接，不加前缀
                        dump($imgPath);
                    } else {
                        $imgPath = 'http://' . $absolutePath . $imgPath;
                    }
                    $images[] = $imgPath;
                    $files[] = $imgPath;
                }
            }
        }
        $mht->AddContents("tmp.html", $mht->GetMimeType("tmp.html"), $content);
        
        for ($i = 0; $i < count($images); $i ++) {
            $image = $images[$i];
            if (@fopen($image, 'r')) {
                $imgcontent = @file_get_contents($image);
                if ($content)
                    $mht->AddContents($files[$i], $mht->GetMimeType($image), $imgcontent);
            } else {
                echo "file:" . $image . " not exist!<br />";
            }
        }
        
        return $mht->GetFile();
    }
    
    // 问题浏览
    public function programsedit($init_id = 0)
    {       
        $this->meta_title='问题详情';
        $find = M('jk_program')->where('init_id=' . $init_id)->find();
        // 所属项目
        $find['ownid'] = M('jk_project')->where("id=" . $find['ownid'])->getField('name');
        $find['info']=$find['info'];
        // 楼栋
        $map = array('id'=>array('in',$find['project_ids']));
        $floor = M('jk_floor')->where($map)
        ->field('id,title,pid,periods,batch,blocks')
        ->select();
        foreach ($floor as $v) {
            $sfloor = $sfloor . $v['title'] . ' ';
            if($v['pid']==0){//楼栋批次等信息
                $find['ownid'] .=' ';
                if($v['periods']){
                    $find['ownid'] = $find['ownid'].$v['periods'].'期';
                }
                if($v['batch']){
                    $find['ownid'] = $find['ownid'].$v['batch'].'批次';
                }
                if($v['blocks']){
                    $find['ownid'] = $find['ownid'].$v['blocks'].'标段';
                }
                
            }
        }
        $find['project_ids'] = $sfloor;//楼层拼接
        
        
        // 时间戳记截取13->10;
        if (strlen($find['create_time']) > 10) {
            $find['create_time'] = time_format(substr($find['create_time'], 0, 10));
        }
        else{
            $find['create_time'] = time_format($find['create_time']);
        }
        if (strlen($find['update_time']) > 10) {
            $find['update_time'] = time_format(substr($find['update_time'], 0, 10));
        }
        else{
            $find['update_time'] = time_format($find['update_time']);
        }
        // 问题提交人id->姓名
        $authInfo =  M('member')->where('uid=' . $find['authid'])->field('nickname,username')->find();
        
        $find['authid'] = $authInfo['nickname'];
        if ($authInfo['username']) {
            $find['authid']=$find['authid'].'['.$authInfo['username'].']';
        }
        
        //问题选项
        $optionDB=$find['type']>0?M('jk_survey_option'):M('jk_option');
        $map= array();
        $map['id'] = $find['option_id'];
        $option = $optionDB->where($map)
        ->field('id,title,pid')->find();
        
        $find['option_id']=$option['title'];
        if($option['pid']>0){
            $map['id'] = $option['pid'];
            $poption = M('jk_option')->where($map)
            ->field('id,title,pid')->find();
            $find['option_id']=$poption['title'].'  '.$option['title'];
        }
        
        //整改单位
        
        $map= array();
        $map['id'] = $find['target_id'];
        $find['target_id']=M('auth_group')->where($map)->getField('title');
        
//      状态：   0：待整改；1：正常关闭；2：待复查；3：强制关闭
        $starr=array('0'=>'待整改','1'=>'正常关闭','2'=>'待复查','3'=>'强制关闭');
        $find['status'] = $starr[$find['status']];
        //详情图组
        $find['imgpath'] = coverIds2Path($find['mapid'],1);
        
        //留言板
        $map= array();
        $map['problem_id'] = $init_id;
        $boardInfo=M('jk_problm_board')->where($map)->order('boardid ASC')->select();
        foreach ($boardInfo as &$v){
            $userInfo = M('member')->where('uid=' . $v['userid'])->field('nickname,username')->find();
            $v['username'] = $userInfo['nickname'];
            if ($userInfo['username']) {
                $v['username']=$v['username'].'['.$userInfo['username'].']';
            }//留言用户
            $v['imgpath']=coverIds2Path($v['images'],1);
            if (strlen($v['create_time']) > 10) {
                $v['create_time'] = time_format(substr($v['create_time'], 0, 10));
            }
            else{
                $v['create_time'] = time_format($v['create_time']);
            }
        }
        $find['problm_board']=$boardInfo;
//         dump($find);
        $this->assign('data',$find);
        $this->display('/JKProgram@JKProgram/programdetail');
        
    }
    //实测项预览
    public function measureedit($id = 0)
    {
        $this->meta_title='测量详情';
    
        $find = M('jk_check_point')->where('id=' . $id)->find();
        // 所属项目
        $find['ownid'] = M('jk_project')->where("id=" . $find['projectid'])->getField('name');
    
        // 楼栋
        $map = array('id'=>array('in',$find['postion']));
        $floor = M('jk_floor')->where($map)
        ->field('id,title,pid,periods,batch,blocks')
        ->select();
        foreach ($floor as $v) {
            $sfloor = $sfloor . $v['title'] . ' ';
            if($v['pid']==0){//楼栋批次等信息
                $find['ownid'] .=' ';
                if($v['periods']){
                    $find['ownid'] = $find['ownid'].$v['periods'].'期';
                }
                if($v['batch']){
                    $find['ownid'] = $find['ownid'].$v['batch'].'批次';
                }
                if($v['blocks']){
                    $find['ownid'] = $find['ownid'].$v['blocks'].'标段';
                }
    
            }
        }
        $find['project_ids'] = $sfloor;//楼层拼接
    
    
        // 时间戳记截取13->10;
        if (strlen($find['create_time']) > 10) {
            $find['create_time'] = time_format(substr($find['create_time'], 0, 10));
        }
        else{
            $find['create_time'] = time_format($find['create_time']);
        }
        if (strlen($find['update_time']) > 10) {
            $find['update_time'] = time_format(substr($find['update_time'], 0, 10));
        }
        else{
            $find['update_time'] = time_format($find['update_time']);
        }
        // 实测提交人id->姓名
        $authInfo =  M('member')->where('uid=' . $find['userid'])->field('nickname,username')->find();
    
        $find['authid'] = $authInfo['nickname'];
        if ($authInfo['username']) {
            $find['authid']=$find['authid'].'['.$authInfo['username'].']';
        }
    
        //实测选项
        $optionDB=M('jk_survey_option');
        $map= array();
        $map['id'] = $find['inspect'];
        $option = $optionDB->where($map)
        ->field('id,title,pid,minqualified,maxqualified,mindestroy,maxdestroy,pointlength')->find();
        $find['option_id']=$option['title'];
        //状态： 
        $starr=array('0'=>'合格','1'=>'需整改','2'=>'需质量锤');
        $find['status'] = $starr[$find['is_out_range']];
		//构建测量信息
        $info=explode('|',$find['info']);
        $infos="";
        $Rectification="";
        $destroy="";
        $i=1;
        foreach ($info as $v1){
        	if($v1!="" && $v1!=","){//未输入的值不显示
	        	$infos .= $i.'.'.$v1." ";
	        	//判断是否超过合格标准
	        	if($option['pointlength']==1){
        	        if ($option['maxdestroy'] != null || $option['mindestroy'] != null) {
        	
        	            if ($v1 > $option['maxdestroy'] || $v1 < $option['mindestroy']) {  
        	                //存入质量锤数组
        	                $destroy .= $i.'.'.$v1." ";
        	                
        	                
        	            } else if ($v1 > $option['maxqualified'] * 1.5 || $v1 < ($option['minqualified'] * 1.5)) {
        	                //存入整改数组
        	                $Rectification .= $i.'.'.$v1." ";
        	            }
        	            	
        	        } else//如果未设置则只判断是否需要整改
        	        {
        	            	
        	            if (($v1 > ($option['maxqualified'] * 1.5) || $v1 < ($option['minqualified'] * 1.5)) && $option['id'] != "14") {
        	                //存入整改数组
        	                $Rectification .= $i.'.'.$v1." ";
        	            }
        	            	
        	        }
        	        //轴线偏差
//         	        if ($option['id'] == "15") {
//         	            if ($v1 > ($option['maxqualified'] * 1.5) || $v1 < ($option['minqualified'] * 1.5)) {
//         	                //存入整改数组
//         	               // $Rectification .= $i.'.'.$v1." ";
//         	            }
        	            	
//         	        }
	        	    
	        	}
	        	else
	        	{
	        	    $nums=explode(',',$v1);
	        	    $min=min($nums);
	        	    $max=max($nums);
	        	    $cha=max-min;
	        	    if($option['id']=='4')//如果为楼板厚度，则为后一个减去前一个
	        	    {
	        	       $cha=$nums[1]-$nums[0];
	        	    } 
	        	    if ($option['maxdestroy'] != null || $option['mindestroy'] != null) {
	        	         
	        	        if ($cha > $option['maxdestroy'] || $cha < $option['mindestroy']) {
	        	            //存入质量锤数组
	        	            $destroy .= $i.'.'.$v1." ";
	        	             
	        	             
	        	        } else if ($cha > $option['maxqualified'] * 1.5 || $cha < ($option['minqualified'] * 1.5)) {
	        	            //存入整改数组
	        	            $Rectification .= $i.'.'.$v1." ";
	        	        }
	        	    
	        	    } else//如果未设置则只判断是否需要整改
	        	    {
	        	    
	        	        if ($cha > ($option['maxqualified'] * 1.5) || $cha < ($option['minqualified'] * 1.5)) {
	        	            //存入整改数组
	        	            $Rectification .= $i.'.'.$v1." ";
	        	        }
	        	    
	        	    }
	        	   
	        	}
        	}
        	$i++;
        }
        $find['info']=$infos;
        $find['Rectification']=$Rectification;
        $find['destroy']=$destroy;
        $this->assign('data',$find);
        $this->display('/JKProgram@JKProgram/measuredetail');
    
    }
    /**
     * 函数用途描述：概况统计
     * @date: 2017年1月04日 晚上209:38:06
     * 
     * @author : 谭杰文
     * @param
     *            :
     * @return :
     */
    public function detail()
    {
        
        $id=I('get.id', 0, 'intval');
        if($id){
            $_SESSION['proId']=$id;
            action_log('show_project', 'JkProjcet', $id, UID);
        }
        //接收时间条件（转换为毫秒）
        $aSearch1 = I('get.usearch1','')*1000;
        $aSearch2 = I('get.usearch2','')*1000;
        $map="ownid=".$_SESSION['proId'];
        //设置搜索条件
        if(!empty($aSearch1)){
            $map.=" AND create_time >= '$aSearch1'";
        }
        if(!empty($aSearch2)){
            
            $map.= " and create_time<='$aSearch2' ";
        }
       // $map['ownid'] = $_SESSION['proId'];
      
        //算出整改单位个数
        $problemlist = M('jk_program')->field("count(*) as count,target_id,ownid,create_time")->group("target_id")->having($map)->select();
        //var_dump($problemlist);die;
        $totalCount = M('jk_program')->field("count(*) as count,target_id,ownid,create_time")->group("target_id")->having($map)->count();
        $builder = new AdminListBuilder();
        $builder->title('项目问题统计');
        $builder->meta_title = '项目问题统计';
        //选项问题
        foreach ($problemlist as &$val) {
            //整改单位
            $val['target']=M('auth_group')->where('id=' . $val['target_id'])->getField('title');
            //分别算出接受数，总完成数，正常完成数，非正常完成数，待整改，待审核
            $val['total']=0;//总接受数
            $val['totalwc']=0;//总完成数
            $val['zc']=0;//正常完成
            $val['fzc']=0;//非正常完成
            $val['dzg']=0;//待整改
            $val['dsh']=0;//待审核
            $data=M('jk_program')->field("status,target_id,ownid")->where($map)->select();
            foreach ($data as $v)
            {
                if($v['target_id']==$val['target_id']){
                    $val['total']+=1;
                    if($v['status']==0)
                        $val['dzg']+=1;
                    elseif($v['status']==1){
                        $val['zc']+=1;
                        $val['totalwc']+=1;
                    }
                    elseif($v['status']==2)
                        $val['dsh']+=1;
                    elseif($v['status']==3){
                        $val['fzc']+=1;
                        $val['totalwc']+=1;
                    }
                }
                
            }
           ;
        }
        unset($val);
        //搜索框
        $builder->setSearchPostUrl(U('JKProgram/detail'))
        ->search('时间从','usearch1','timer','','','','');
        $builder->search('到','usearch2','timer','','','','');
        
        $builder
        ->keyText('target', '整改单位')
        ->keyText('total', '接受数')
        ->keyText('totalwc', '完成数')
        
        ->keyText('zc', '正常完成数')
        ->keyText('fzc', '非正常完成数')
        ->keyText('dzg', '待整改')
        ->keyText('dsh', '待审核');
        
        $builder->data($problemlist);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    /**
     * 函数用途描述：概况统计
     * @date: 2017年1月0日 晚上18:38:06
     *
     * @author : 谭杰文
     * @param
     *            :
     * @return :
     */
    public function problemSubmit()
    {
    
        $id=I('get.id', 0, 'intval');
        if($id){
            $_SESSION['proId']=$id;
            action_log('show_project', 'JkProjcet', $id, UID);
        }
        //接收时间条件（转换为毫秒）
        $aSearch1 = I('get.usearch1','')*1000;
        $aSearch2 = I('get.usearch2','')*1000;
        $map="ownid=".$_SESSION['proId'];
        $projectname=M('jk_project')->where("id=".$_SESSION['proId'])->getField('name');
        //设置搜索条件
        if(!empty($aSearch1)){
            $map.=" AND create_time >= '$aSearch1'";
        }
        if(!empty($aSearch2)){
            $map.= " and create_time<='$aSearch2' ";
        }
        //根据提交人分组
        $problemlist = M('jk_program')->field("count(*) as count,target_id,ownid,create_time,authid")->group("authid")->having($map)->select();
        //var_dump($problemlist);die;
        $totalCount = M('jk_program')->field("count(*) as count,target_id,ownid,create_time,authid")->group("authid")->having($map)->count();
        $builder = new AdminListBuilder();
        $builder->title('项目问题统计');
        $builder->meta_title = '项目问题统计';
        //选项问题
        foreach ($problemlist as &$val) {
            //对应项目
            $val['projectname']=$projectname;
            //整改人
            $val['target']=M('member')->where('uid=' . $val['authid'])->getField('username');
            //角色
            $val['targetrole']=M('member')->where('uid=' . $val['authid'])->getField('position');
            
            $val['department']=M('member')->where('uid=' . $val['authid'])->getField('department');
            //分别算出总提报数，已整改数，正常完成数，非正常完成数，待整改，待审核
            $val['total']=0;//总提报数
            $val['totalwc']=0;//总完成数
            $val['zc']=0;//正常完成
            $val['fzc']=0;//非正常完成
            $val['dzg']=0;//待整改
            $val['dsh']=0;//待审核
            $data=M('jk_program')->field("status,target_id,ownid,authid")->where($map)->select();
            foreach ($data as $v)
            {
                if($v['authid']==$val['authid']){
                    $val['total']+=1;
                    if($v['status']==0)
                        $val['dzg']+=1;
                    elseif($v['status']==1){
                        $val['zc']+=1;
                        $val['totalwc']+=1;
                    }
                    elseif($v['status']==2)
                    $val['dsh']+=1;
                    elseif($v['status']==3){
                        $val['fzc']+=1;
                        $val['totalwc']+=1;
                    }
                }
    
            }
            ;
        }
        unset($val);
        //搜索框
        $builder->setSearchPostUrl(U('JKProgram/problemSubmit'))
        ->search('时间从','usearch1','timer','','','','');
        $builder->search('到','usearch2','timer','','','','');
        $builder->keyText('projectname', '项目')
        ->keyText('target', '提报人')
        ->keyText('department', '部门') 
        ->keyText('targetrole', '岗位')
        ->keyText('total', '总提报数')
        ->keyText('totalwc', '提报完成数')
        ->keyText('zc', '正常完成数')
        ->keyText('fzc', '非正常完成数')
        ->keyText('dzg', '待整改')
        ->keyText('dsh', '待审核');
    
        $builder->data($problemlist);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    /**
    *函数用途描述：楼栋分布
    *@date：2016年11月23日 下午4:04:42
    *@author：luoj
    *@param：
    *@return：
    **/
    public function floorlist() {
        ;
        $this->display('/JKProgram@JKProgram/floorlist');
    }
    
    /**
     * 函数用途描述：统计-高级搜索
     * @date: 2016年11月8日 下午2:19:44
     * 
     * @author : luojun
     * @param
     *            :
     * @return :
     */
    public function statistic()
    {
        ;
        
        $this->display('/JKProgram@JKProgram/statistic');
    }
    
    /**
    * 函数用途描述：保存修改
    * @date: 2016年11月27日 下午2:58:26
    * @author: luojun
    * @param: 
    * @return:
    */
    public function savefloor() {
        if(IS_POST){
            $id = $_POST['id'];
            $ids=explode(',', $id);
            $data=M('jk_floor')->create();
            $map['id'] = array('in',$ids);
            unset($data['id']);
            $data['imgpath']=coverIds2Path($data['imgid']);
            $res=M('jk_floor')->where($map)->save($data);
            $root=getRoot('jk_floor',$ids[0]);
            if($res){
                $this->success('操作成功',U('j_k_program/selectfloor/',array('id'=>$root['id'],'name'=>$root['title'])));
            }
            else{
                $this->error('操作失败');
            }
            return;
        }
    }
    
    /**
    * 函数用途描述：编辑楼层与房间信息
    * @date: 2016年11月25日 上午9:32:38
    * @author: luojun
    * @param: 
    * @return:
    */
    public function editfloor() {
        $id = array_unique((array)I('id', 0));
        if(is_array($id)&&count($id)==1){
            $iflag=1;//编辑单个数据
        }
        
        $id = is_array($id) ? implode(',', $id) : $id;
        
        if(empty($id)){
            $this->error('未选择操作数据！');
        }
                
        $builder = new AdminConfigBuilder();
        if ($iflag) {
            $map['id'] = $id;
            $data = M('jk_floor')->where($map)
            ->field('id,title,imgid')
            ->find();
        }
        else{
            $data['id']=$id;
        }
        
        $data['submit']=1;
        $builder->keyReadOnly('id', '编号');
        if ($iflag) {
            $builder->keyText('title', '楼栋名称*');
        }
        
        $builder->keyHidden('submit', '')->title('修改楼层与房间信息')
        ->keySingleImage('imgid', '平面图')
        ->data($data)
        ->buttonSubmit(U('JKProgram/savefloor'))
        ->buttonBack()
        ->display();
        ;
    }
    
    /**
    * 函数用途描述：删除楼层与房间
    * @date: 2016年11月25日 上午9:33:05
    * @author: luojun
    * @param: 
    * @return:
    */
    public function delfloor() {
        $id = array_unique((array)I('id', 0));
        $map=array('id'=>array('in',$id));
        M('jk_floor')->where($map)->delete();
        $map=array('pid'=>array('in',$id));
        M('jk_floor')->where($map)->delete();
        $this->success('操作成功');
    }
}

?>