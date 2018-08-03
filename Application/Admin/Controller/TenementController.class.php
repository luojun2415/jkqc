<?php

namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminSortBuilder;
use Common\Model\MemberModel;
use User\Api\UserApi;
use Admin\Builder\AdminTreeListBuilder;

/**
 * 物业部门管理 控制器
 * @author yuchihcuan
 * @date 
 */
class TenementController extends AdminController{
    
    /****************** 问题列表  ******************/
    public function question_list($page=1,$r=20) {

        //接收搜索时间
        $time_search1 = date("Y-m-d H:i:s",I('get.usearch1',''));
        $time_search2 = date("Y-m-d H:i:s",I('get.usearch2',''));
        if(!empty($time_search1))
            $map['_string'] = "submit_time>='$time_search1'";
        if(!empty($time_search2))
            $map['_string'] .= "and submit_time<='$time_search2'";
        
        //接收下拉框项目筛选
     	$project_id = I('get.ownid','');

        if($project_id){
        	$proids=get_select_projects($project_id);
        	
        	$map['project_id'] = array('in', $proids);
        	 
        }
        
        //接收下拉框状态筛选
        $status = I('get.status','');
        if(!empty($status) && $status != 'all')
            $map['problem_status'] = $status;
        
        //接收下拉框楼栋筛选
        $building_title = I('get.title','');
        if(!empty($building_title) && $building_title != ''){
            $building_id = M('JkFloor')->where(['title'=>$building_title])->getField('id',true);
            $building_ids = '('.implode(',',$building_id).')';
            $map['building_id'] = ['in',$building_id];
        }
            
        //列表数据查询
        $que_list = D('JkAcprogram')->relation(true)->where($map)
        ->field('problem_id,project_id,building_id,unit_id,floor_id,room_id,check_item,problem_describe,contractor_id,submit_user,submit_time,problem_status')
        ->order('submit_time desc')
        ->page($page,$r)
        ->select();
        $totalcount = M('JkAcprogram')->where($map)->count();
        
        //列表数据拼接及转换
        foreach($que_list as &$v) {
            foreach($v as &$vv){
                if($vv == 'null')
                    $vv = '';
            }
            $v['position'] = $v['building'].'-'.$v['unit'].'-'.$v['floor'].'-'.$v['room'];
            $v['id'] = $v['problem_id'];
            switch($v['problem_status']){
                case 2 :
                    $v['problem_status'] = '待派单';
                    break;
                case 1 :
                    $v['problem_status'] = '待整改';
                    break;
                case 3 :
                    $v['problem_status'] = '已整改';
                    break;
                case 4 :
                    $v['problem_status'] = '已通过';
                    break;
                case 5 :
                    $v['problem_status'] = '已作废';
                    break;
                case 6 :
                    $v['problem_status'] = '待审核';
                    break;
	            case 7 :
	                $v['problem_status'] = '待复核';
	                break;
            } 
        }

        //建立模板
        $builder = new AdminListBuilder();
        $builder->title(L('问题列表'));
        $builder->meta_title = '问题列表';
        
        //搜索框
        $builder->setSearchPostUrl(U('Tenement/question_list'))
        ->search('时间从','usearch1','timer','','','','');
        $builder->search('到','usearch2','timer','','','','');

        //导出
        $attr['target-form'] = 'ids';
        $attr['href'] = U('ex_word');
        $attr['class']='a_jump';
        $builder->button('导出问题详情', $attr);
        
        //项目筛选下拉框
        //构造项目数组
        $projectwhere['status']    =   array('gt', 0);
        if(!IS_ROOT){
            $projectwhere['id']    =   array('in', get_my_projects());
        }
        $list = M('jk_project')->where($projectwhere)->field('id,name')->select();
        $projectArr = array();
        $projectArr[0]['id'] = 'all';
        $projectArr[0]['value'] = '全部';
        $i = 1;
        foreach ($list as $value){
            $projectArr[$i]['id'] = $value['id'];
            $projectArr[$i]['value'] = $value['name'];
            $i++;
        }
        $builder->setSelectPostUrl(U('Tenement/question_list').'&ownid='.$project_id."&usearch1=".($time_search1/1000));
        //$builder->select(L('项目：'), 'project_id', 'select', L('项目'), '', '', $projectArr);
        //项目筛选
        $builder->buttonModalPopup(U('JKProgram/selectproject'),
        		'',
        		'根据项目筛选',
        		array('data-title' => ('选择项目')));
        //$builder->select(L('项目：'), 'project_id', 'select', L('项目'), '', '', $projectArr);
        
        //状态筛选下拉框
        $astatus = array(
            array('id'=>'all','value'=>'全部'),
            array('id'=>2,'value'=>'待派单'),
            array('id'=>1,'value'=>'待整改'),
            array('id'=>3,'value'=>'已整改'),
            array('id'=>4,'value'=>'已通过'),
            array('id'=>5,'value'=>'已作废'),
            array('id'=>6,'value'=>'待审核'),
            array('id'=>7,'value'=>'待复核'),
        );
        $builder->select(L('状态：'), 'status', 'select', L('选择状态'), '', '', $astatus);

        //楼栋筛选下拉框
//         if(!empty($project_id)){
//             if($project_id!='all')
//                 $floorlist=M('jk_floor')->where("status=1 and pid=0 and projectid='".$project_id."'")->order('create_time desc')->select();
//             else
//                 $floorlist=M('jk_floor')->where("1=2")->order('create_time desc')->select();//不放置楼栋信息
//         }else{
//             $floorlist=M('jk_floor')->where("status=1 and pid=0 and projectid='".$_SESSION['proId']."'")->order('create_time desc')->select();
//         }
        //构建最顶级的楼栋列表
        if(!empty($project_id)){
        	if($project_id!='all'){
        		$floorwhere['projectid'] = array('in',$proids);
        		$floorlist  = M('jk_floor')->where("status=1 and pid=0")->where($floorwhere)->order('create_time desc')->select();
        	}
        	else{
        		$floorlist=M('jk_floor')->where("1=2")->order('create_time desc')->select();//不放置楼栋信息
        	}
        }else{
        	$floorlist=M('jk_floor')->where("status=1 and pid=0 and projectid='".$_SESSION['proId']."'")->order('create_time desc')->select();
        }
        $a=array();
        $a[0]['id']='';
        $a[0]['value']='全部';
        $j=1;
        foreach ($floorlist as $topfloor){
            $a[$j]['id']=$topfloor['title'];
            $a[$j]['value']=$topfloor['title'];
            $j++;
        }
        $builder->select(L('楼栋：'), 'title', 'select', L('选择楼栋'), '', '', $a);
        
        //列表
        $builder->keyText('name',L('项目名称'))
        ->keyText('position',L('问题位置'))
        ->keyText('check_item',L('检查项'))
        ->keyText('problem_describe',L('问题描述'))
        ->keyText('auth_title',L('整改单位'))
        ->keyText('submit_user',L('问题提交人'))
        ->keyText('submit_time',L('提交时间'))
        ->keyText('problem_status',L('状态'))
        ->keyDoAction('question_info?id=###','详情');

        //循环数据分页输出
        $builder->data($que_list);
        $builder->pagination($totalcount,$r);
        $builder->display();
    }

    /**
     * 状态修改
     */
    public function changeStatus($method = null)
    {
        if (empty($_REQUEST['id'])) {
            $this->error(L('_PLEASE_CHOOSE_TO_OPERATE_THE_DATA_'));
        }
        switch (strtolower($method)) {
            case 'forbidgroup':
                $this->forbid('jk_houseimage_data');
                break;
            case 'resumegroup':
                $this->resume('jk_houseimage_data');
                break;
            case 'delete':
                $this->delete('jk_houseimage_data');
                break;
            default:
                $this->error($method . L('_ILLEGAL_'));
        }
    }

    /**
     * 函数用途描述：分户验收户型配置列表
     * @date: 2018年06月20日 上午 11:15:05
     * @author: luojun
     * @param:
     * @return:
     */
    public function house_config($page=1, $r=20){
        $db=M('jk_houseimage_data');
        $map['status'] = array('egt', 0);

        $pro_id = $_SESSION['proId'];
        $map['project_id'] =$pro_id;
        $goodsList = $db->where($map)->order('sort')->page($page, $r)->select();
        $totalCount = $db->where($map)->count();

        $builder = new AdminListBuilder();

        $builder->buttonNew(U('edithouse'));

        $project = M('jk_project')->where("id=$pro_id")->find();
        $title='户型配置（'.$project['name'].'）';
        $i=1;

        foreach ($goodsList as &$v){
            $v['no']=$i;
            $id= $v['id'];
            $images=M('picture')->where("projectid=$pro_id AND target_id=$id")->field('id')->select();
            foreach ($images as $k=>$im){
                $images[$k]['title'] = '户型图'.($k+1);
            }
            $v['_images']=$images;
            $i++;
        }
//        dump(M('picture')->_sql());
//        dump($goodsList);
//        $builder->keyText('num', L('序号'))
//            ->keyText('title', L('名称'))->keyText('remark', L('备注'))
//            ->keyStatus('status', L('状态'))
//            ->keyDoActionEdit('edithouse?id=###')
//            ->keyDoAction('house_detail?id=###',L('绘制'));
//
//        $builder->data($goodsList);
//        $builder->pagination($totalCount, $r);
//        $builder->display();

        $page = new \Think\Page($totalCount, $r, $_REQUEST);
        if ($totalCount > $r) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        //     echo $p;
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $totalCount);

        $this->assign('title', $title);
        $this->assign('_list', $goodsList);
        $this->display();
    }

    /**
     * 函数用途描述：分户验收户型适配
     * @date: 2018年07月18日 上午 11:15:05
     * @author: luojun
     * @param:
     * @return:
     */
    public function house_match($id=0,$mid=0){
        if($id<=0||$mid<=0){
            $this->error('户型图参数错误');
        }
        $pro_id = $_SESSION['proId'];
        $proName= M('jk_project')->where("id=$pro_id")->getField('name');
        $type = M('jk_houseimage_data')->where("id=$id")->field('id,title,remark')->find();
        $typeList = M('jk_floor')->where("status>0 AND projectid=$pro_id AND pid=0")->getField('id,id,title',true);

        $imgPath = M('picture')->where("id=$mid")->getField('path');
        $type['path'] = $imgPath;
        $type['mid'] = $mid;
        $this->assign('_types', $typeList);
        $this->assign('data', $type);
        $this->assign('title', $proName);
//        dump(M('jk_house_typelist')->_sql());
//        dump(M('jk_houseimage_data')->_sql());
//        dump($type);
//        dump($typeList);
        $this->display();

    }

    /**
     * 函数用途描述：分户验收户型适配数据保存
     * @date: 2018年07月18日 上午 11:15:05
     * @author: luojun
     * @param:
     * @return:
     */
    public function save_house($mid=0){
        if($mid<=0){
            $this->error('户型图参数错误');
        }
        $id = array_unique((array)I('id', 0));
        $map = array('id' => array('in', $id));

        $ret = M('jk_floor')->where($map)->save(array('house_img_id'=>$mid));
        if($ret){
           $this->success('操作成功');
        }
        else{
           $this->error('操作失败');
        }

    }


    /**
     * 函数用途描述：分户验收户型适配楼栋详情
     * @date: 2018年07月18日 上午 11:15:05
     * @author: luojun
     * @param:
     * @return:
     */
    public function select_building($id, $mid){
//        $sta = microtimeStr();
//        js_log($sta);
        //先查询出该楼栋下的所有子级
        $find = M('jk_floor')->where('id=' . $id)->find();
        $proId = $find['projectid'];
        $name = $find['title'];
        $ulist = M()->table('irosn_jk_unit_tmp ut, irosn_jk_floor f')
            ->where("ut.id = f.id AND ut.build_id=$id AND f.projectid=$proId AND f.status=1")->field('f.id,f.title,f.sort,f.pid,f.status,
            f.imgpath')->order('f.sort')->select();

//         dump("last:".(microtimeStr()-$sta));
        $flist = M()->table('irosn_jk_floor f,irosn_jk_floor_tmp t ')
            ->where("t.id = f.id AND t.build_id=$id AND f.projectid=$proId AND f.status=1")->field('f.id,f.title,f.sort,f.pid,f.status,
            f.imgpath')->order('f.sort DESC')->select();
//         dump("last:".(microtimeStr()-$sta));
//         dump(M()->_sql());
        $rlist = M()->table('irosn_jk_floor f,irosn_jk_room_tmp t')
            ->where("t.id = f.id AND t.build_id=$id AND f.projectid=$proId AND f.status=1")->field('f.id,f.title,f.sort,f.pid,f.status,
            f.imgpath,f.house_img_id')->order('f.sort')->select();

        foreach ($rlist as &$r){
            if($r['house_img_id']>0){
                $imgId=$r['house_img_id'];
                $r['house_img_path'] = M('picture')->where("id='$imgId'")->getField('path');
            }
        }

//         dump("last:".(microtimeStr()-$sta));
//        js_log('cost:'.(microtimeStr()-$sta));
        $list = array_merge($ulist, $flist, $rlist);
//         $unit_ids  = M('jk_unit_tmp')->where('build_id='.$id)->field('id')->select();
//         $floor_ids = M('jk_floor_tmp')->where('build_id='.$id)->field('id')->select();
//         $room_ids  = M('jk_room_tmp')->where('build_id='.$id)->field('id')->select();
//         $all_ids   = array_merge($unit_ids,$floor_ids,$room_ids);
//         foreach ($all_ids as $v){
//         	$ids[]=$v['id'];
//         }
//      	$position_where['status'] = 1;
//      	$position_where['id']     = array('in',$ids);
//         $list = M('jk_floor')->where($position_where)->field('id,title,sort,pid,status,imgpath')->select();
//         dump("last:".(microtimeStr()-$sta));
        $data = list_to_tree($list, 'id', 'pid', '_', $id);
//         dump("last:".(microtimeStr()-$sta));
        foreach ($data as &$v) {//单元
            //计算一个单元有多少楼层
            $v['count'] = count($v['_']) + 2;
            $v['room_count'] = 1;
            foreach ($v['_'] as $k => $vv) {//楼层
                $count = count($vv['_']);//一层楼最多有多少房间

                if ($count > $v['room_count'] - 1) {
                    //echo $count;
                    $v['room_count'] = $count + 1;
                    //记录是第几条
                    $v['max'] = $vv['_'];

                }
            }

        }
        unset($v);
        unset($r);

        $this->assign('arr_floor', $data);
        $this->assign('mid', $mid);
        $this->assign('title', $name);

        $html=$this->fetch();
        $this->success($html);
    }

    /**
     * 函数用途描述：分户验收户型配置列表
     * @date: 2018年06月20日 上午 11:15:05
     * @author: luojun
     * @param:
     * @return:
     */
    public function house_detail($id=0,$mid=0){
        if($id<=0||$mid<=0){
            $this->error('户型图参数错误');
        }
        $pro_id = $_SESSION['proId'];
        $type = M('jk_houseimage_data')->where("id=$id")->field('id,title,remark')->find();
        $typeList = M('jk_house_typelist')->where("status>=0")->getField('id,id,title',true);
        if(IS_POST){
            $points=$_POST['points'];
            $tid=$_POST['tid'];
            $data['points'] = json_encode($points);
            $data['house_typeid'] = $tid;
            $data['house_id'] = $id;
            $data['house_imageid'] = $mid;
            $data['pro_id'] = $pro_id;
            $data['title'] = $typeList[$tid]['title'];

            $data_id =  M('jk_housedata')->where("house_id=$id AND house_imageid=$mid AND house_typeid=$tid AND pro_id = $pro_id")->getField('id');
            if($data_id){
                $data['update_time'] = $mid;
                $ret=M('jk_housedata')->where("id=$data_id")->save($data);
            }
            else{
                $data['create_time'] =$data['update_time'] = $mid;
                $ret=M('jk_housedata')->add($data);
            }
            if($ret){
                $this->success('绘制数据保存成功！');
            }
            else{
                $this->error('绘制数据保存失败！');
            }
            return;
        }

        $imgPath = M('picture')->where("id=$mid")->getField('path');
        $type['path'] = $imgPath;
        $type['mid'] = $mid;
        $this->assign('_types', $typeList);
        $this->assign('data', $type);
//        dump(M('jk_house_typelist')->_sql());
//        dump(M('jk_houseimage_data')->_sql());
//        dump($type);
//        dump($typeList);
        $this->display();

    }

    /**
     * 函数用途描述：新增编辑分户验收户型配置
     * @date: 2018年06月20日 上午 11:15:05
     * @author: luojun
     * @param:
     * @return:
     */
    public function edithouse($id = 0){
        $db=M('jk_houseimage_data');
        $opt = M('jk_house_type')->getField('id,title',true);

        if (IS_POST) {
            $title=$id?L('_EDIT_'):L('_ADD_');
            $data = $db->create();
            $data['title'] = $opt[$data['type_id']];
            $image_code = $_POST['image_code'];
            $data['project_id'] =  $_SESSION['proId'];
            if($id>0){
                $ret = $db->where("id=$id")->save($data);
                if($image_code){
                    M('picture')->where("id in ($image_code)")->save(array('type'=>'house','target_id'=>$id));
                }
            }
            else{
                $ret = $db->add($data);
                if($image_code){
                    M('picture')->where("id in ($image_code)")->save(array('type'=>'house','target_id'=>$ret));
                }
            }

            if ($ret) {
                $this->success($title.L('_SUCCESS_').L('_PERIOD_'), U('Tenement/house_config'));
            } else {
                $this->error($title.L('_FAIL_').L('_EXCLAMATION_'));
            }
        } else {
            $builder = new AdminConfigBuilder();

            $category='';
            if ($id != 0) {
                $category = $db->where("id=$id")->find();
                $category['image_code']=M('picture')->where("target_id=$id AND type='house'")->getField('id',true);
                $category['image_code'] = implode(',',$category['image_code']);
            }
//            dump($category);
            $builder->title(L('新增户型配置'))->keyId()
                ->keySelect('type_id', L('户型名称'), L('选择户型名称'), $opt)
                ->keyText('remark', L('户型描述'))
                ->keySelect('check_type', L('装修类型'), L('选择装修类型'), array(0=>'毛坯房',1=>'精装修'))
                ->keyMultiImage('image_code','上传户型图','',10)
                ->data($category)
                ->buttonSubmit(U('Tenement/edithouse'))->buttonBack()->display();

        }

    }

    /**
     * 函数用途描述：分户验收历史问题数据列表
     * @date: 2017年08月02日 上午 11:15:05
     * @author: tanjiewen
     * @param: 
     * @return:
     */
    public function history_problem($page=1,$r=20){
    	//接收搜索时间
    	$time_search1 = date("Y-m-d H:i:s",I('get.usearch1',''));
    	$time_search2 = date("Y-m-d H:i:s",I('get.usearch2',''));
    	if(!empty($time_search1))
    		$map['_string'] = "submit_time>='$time_search1'";
    	if(!empty($time_search2))
    		$map['_string'] .= "and submit_time<='$time_search2'";
    	
    	//接收下拉框项目筛选
    	$project_id = I('get.ownid','');
    	
    	if($project_id){
    		$proids=get_select_projects($project_id);		 
    		$map['project_id'] = array('in', $proids);
    	
    	}	
    	//接收下拉框状态筛选
    	$status = I('get.status','');
    	if(!empty($status) && $status != 'all')
    		$map['problem_status'] = $status;   	  	
    	//列表数据查询
   
    	$que_list   = M('jk_history_problem1')->where($map)->order('submit_time desc')->page($page,$r)->select();
    	$totalcount = M('jk_history_problem1')->where($map)->count();
    	foreach ($que_list as &$v){
    		if($v['problem_status']==1){
    			$v['problem_status']="待派单";
    		}else if($v['problem_status']==2){
    			$v['problem_status']="待整改";
    		}else if($v['problem_status']==3){
    			$v['problem_status']="已整改";
    		}else if($v['problem_status']==4){
    			$v['problem_status']="已通过";
    		}else if($v['problem_status']==5){
    			$v['problem_status']="已作废";
    		}

    		$v['position']=$v['build']."-".$v['unit']."-".$v['floor']."-".str_replace("jinpinzhi-", '', $v['room']);

    	}
   
    	
    	//建立模板
    	$builder = new AdminListBuilder();
    	$builder->title(L('历史问题列表'));
    	$builder->meta_title = '历史问题列表';
    	
    	//搜索框
    	//$builder->setSearchPostUrl(U('Tenement/question_list'))
    	//->search('时间从','usearch1','timer','','','','');
    	//$builder->search('到','usearch2','timer','','','','');
    	
    	//导出
//     	$attr['target-form'] = 'ids';
//     	$attr['href'] = U('ex_word');
//     	$attr['class']='a_jump';
//     	$builder->button('导出问题详情', $attr);
    	
    	//项目筛选下拉框
    	//构造项目数组
//     	$projectwhere['status']    =   array('gt', 0);
//     	if(!IS_ROOT){
//     		$projectwhere['id']    =   array('in', get_my_projects());
//     	}
//     	$list = M('jk_project')->where($projectwhere)->field('id,name')->select();
//     	$projectArr = array();
//     	$projectArr[0]['id'] = 'all';
//     	$projectArr[0]['value'] = '全部';
//     	$i = 1;
//     	foreach ($list as $value){
//     		$projectArr[$i]['id'] = $value['id'];
//     		$projectArr[$i]['value'] = $value['name'];
//     		$i++;
//     	}
//     	$builder->setSelectPostUrl(U('Tenement/question_list').'&ownid='.$project_id."&usearch1=".($time_search1/1000));
//     	//$builder->select(L('项目：'), 'project_id', 'select', L('项目'), '', '', $projectArr);
//     	//项目筛选
//     	$builder->buttonModalPopup(U('JKProgram/selectproject'),
//     			'',
//     			'根据项目筛选',
//     			array('data-title' => ('选择项目')));
    	//$builder->select(L('项目：'), 'project_id', 'select', L('项目'), '', '', $projectArr);
    	
    	//状态筛选下拉框
    	$astatus = array(
    			array('id'=>'all','value'=>'全部'),
    			array('id'=>1,'value'=>'待派单'),
    			array('id'=>2,'value'=>'待整改'),
    			array('id'=>3,'value'=>'已整改'),
    			array('id'=>4,'value'=>'已通过'),
    			array('id'=>5,'value'=>'已作废'),    			
    	);
     	$builder->select(L('状态：'), 'status', 'select', L('选择状态'), '', '', $astatus);
    	
    	//楼栋筛选下拉框
    	//         if(!empty($project_id)){
    	//             if($project_id!='all')
    		//                 $floorlist=M('jk_floor')->where("status=1 and pid=0 and projectid='".$project_id."'")->order('create_time desc')->select();
    		//             else
    			//                 $floorlist=M('jk_floor')->where("1=2")->order('create_time desc')->select();//不放置楼栋信息
    			//         }else{
    			//             $floorlist=M('jk_floor')->where("status=1 and pid=0 and projectid='".$_SESSION['proId']."'")->order('create_time desc')->select();
    			//         }
    		//构建最顶级的楼栋列表
//     		if(!empty($project_id)){
//     			if($project_id!='all'){
//     				$floorwhere['projectid'] = array('in',$proids);
//     				$floorlist  = M('jk_floor')->where("status=1 and pid=0")->where($floorwhere)->order('create_time desc')->select();
//     			}
//     			else{
//     				$floorlist=M('jk_floor')->where("1=2")->order('create_time desc')->select();//不放置楼栋信息
//     			}
//     		}else{
//     			$floorlist=M('jk_floor')->where("status=1 and pid=0 and projectid='".$_SESSION['proId']."'")->order('create_time desc')->select();
//     		}
//     		$a=array();
//     		$a[0]['id']='';
//     		$a[0]['value']='全部';
//     		$j=1;
//     		foreach ($floorlist as $topfloor){
//     			$a[$j]['id']=$topfloor['title'];
//     			$a[$j]['value']=$topfloor['title'];
//     			$j++;
//     		}
//     		$builder->select(L('楼栋：'), 'title', 'select', L('选择楼栋'), '', '', $a);
    	
    		//列表
    		$builder->keyText('project_name',L('项目名称'))
    		->keyText('position',L('问题位置'))
    		->keyText('regional_name',L('具体部位'))
    		->keyText('check_item_name',L('检查项'))
    		->keyText('problem_describe_name',L('问题描述'))
    		->keyText('additional_remark',L('补充描述'))
    		->keyText('real_name',L('问题提交人'))
    		->keyText('submit_time',L('提交时间'))
    		->keyText('problem_status',L('状态'));
    		//->keyDoAction('question_info?id=###','详情');
    	
    		//循环数据分页输出
    		$builder->data($que_list);
    		$builder->pagination($totalcount,$r);
    		$builder->display();
    	
    }

    /**
     * 函数作用描述：验收阶段
     * @date:2018-6-20 15:05
     * @author:罗俊
     */
    public function stageList($page = 1, $r = 20){
        $map['status'] = array('egt', 0);

        $goodsList = M('jk_stageoption')->where($map)->order('stage_id')->page($page, $r)->select();
        $totalCount = M('jk_stageoption')->where($map)->count();

        $builder = new AdminListBuilder();

        $builder->buttonNew(U('editStage'));

        $builder->title(L('验收阶段'));
        $builder->meta_title = L('验收阶段');
        $i=1;
        foreach ($goodsList as &$v){
            $v['num']=$i;
            $i++;
        }
//        dump($goodsList);
        $builder->keyText('num', L('序号'))
            ->keyText('title', L('名称'))->keyText('remark', L('备注'))
            ->keyStatus('status', L('状态'))
            ->keyDoActionEdit('editStage?id=###');

        $builder->data($goodsList);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }

    /****************** 问题详情页 ******************/
    public function question_info($id) {
        //查询问题详情
        $info = D('JkAcprogram')->relation(true)->find($id);
        
        //查询问题图片
        $problem_imgs = M('JkAcprogramImage')->where(['problem_id'=>$id])->getField('photo_url',true);
        
        //查询回复及图片
        $replies = D('JkComment')->relation(true)->where(['program_id'=>$id])->select();
        
        //查询验收信息
        $recheck = M('jk_acprogram_recheck')->where("problem_id = '$id'")->select();
        //一共多少验收次数
        $recheckcount = M('jk_acprogram_recheck')->where("problem_id = '$id'")->count();
        //查询验收附件
        $recheck_etx = M('jk_acprogram_recheck_etx')->where("problem_id = '$id'")->select();
        if($recheck){
             //把附件图片放入验收信息中
            foreach ($recheck as $v){
                $newrecheck['id'] = $v['accept_id'];
                $newrecheck['program_id'] = $v['problem_id'];
                $newrecheck['name'] = $v['accept_user'];
                $newrecheck['phone'] = $v['accept_user_phone'];
                $newrecheck['content'] = $v['reason'];
                $newrecheck['create_time'] = $v['create_time'];
                $newrecheck['update_time'] = $v['last_modify_time'];
                $newrecheck['state'] = $v['state'];
                $newrecheck['reply_time'] = $v['reply_time'];
                $newrecheck['isrecheck'] = 1;  
                $imgs = [];
                foreach ($recheck_etx as $vv){
                    if ($v['accept_id'] == $vv['accept_id']){
                        $imgurl['path'] = $vv['attachment_url'];
                        $imgs[] = $imgurl;
                    }
                    
                }
                $newrecheck['imgs'] = $imgs;
                $newrec = [];
                $newrec[] = $newrecheck;
                $replies = array_merge($replies,$newrec);//合并数组
                
            }
            //dump($replies);
            
        }
        $replies = array_sort($replies,'create_time');//按照create_time进行升序
        //dump($problem_imgs);

        $info['position'] = $info['building'].'-'.$info['unit'].'-'.$info['floor'].'-'.$info['room'];
        $this->assign('recheckcount',$recheckcount);
        $this->assign('replies',$replies);
        $this->assign('problem_imgs',$problem_imgs);
        $this->assign('info',$info);
        $this->display();
    }
    
    
    
    /****************** 验收进度  ******************/
    public function progress($page=1,$r=20) {

        //接收搜索时间
        $time_search1 = date("Y-m-d H:i:s",I('get.usearch1',''));
        $time_search2 = date("Y-m-d H:i:s",I('get.usearch2',''));
        if(!empty($time_search1))
            $map['_string'] = "create_time>='$time_search1'";
        if(!empty($time_search2))
            $map['_string'] .= "and create_time<='$time_search2'";
        
        //接收下拉框项目筛选
        $project_id = I('get.ownid','');
//         $projectids = get_my_projects();
//         if(!empty($project_id)){
//             if($project_id != 'all')
//                 $map['pro_id'] = array('eq', $project_id);
//             else
//                 $map['pro_id'] = array('in', $projectids);
//         }else{
//             $map['pro_id'] = array('in', $projectids);
//         }
        if($project_id){
        	$proids=get_select_projects($project_id);
        	$map['pro_id'] = array('in', $proids);
        	 
        }
        //接收下拉框进度筛选
        $rate = I('get.rate','');
        if(!empty($rate) && $rate != 'all')
            $map['rate'] =['between',$rate];
        
        //数据查询
        $info_list = D('JkTask')->relation(true)->where($map)->order("create_time DESC")->page($page,$r)->select();
        

        foreach($info_list as &$info) {
            $info['rate'] = $info['rate'].'%';
            
            //计算 销项率
            $map_t['task_id'] = $info['id'];
            $total = M('JkAcprogram')->where($map_t)->count();    //总的问题
            
            $map_a['task_id'] = $info['id'];
            $map_a['problem_status'] = ['in','2,3,4,5'];
            $agreed = M('JkAcprogram')->where($map_a)->count();   //已销项
            $info['agree_rate'] = sprintf("%0.2f",$agreed/$total*100).'%';
        }

        //建立模板
        $builder = new AdminListBuilder();
        $builder->title(L('验收进度'));
        $builder->meta_title = '验收进度';
        
        //搜索框
        $builder->setSearchPostUrl(U('Tenement/progress'))
        ->search('时间从','usearch1','timer','','','','');
        $builder->search('到','usearch2','timer','','','','');
        
        //项目筛选下拉框
        //构造项目数组
        $projectwhere['status']    =   array('gt', 0);
        if(!IS_ROOT){
            $projectwhere['id']    =   array('in', get_my_projects());
        }
        $list = M('jk_project')->where($projectwhere)->field('id,name')->select();
        $projectArr = array();
        $projectArr[0]['id'] = 'all';
        $projectArr[0]['value'] = '全部';
        $i = 1;
        foreach ($list as $value){
            $projectArr[$i]['id'] = $value['id'];
            $projectArr[$i]['value'] = $value['name'];
            $i++;
        }
        $builder->setSelectPostUrl(U('Tenement/progress').'&ownid='.$project_id."&usearch1=".($time_search1/1000));
        //$builder->select(L('项目：'), 'project_id', 'select', L('项目'), '', '', $projectArr);
        //项目筛选
        $builder->buttonModalPopup(U('JKProgram/selectproject'),
        		'',
        		'根据项目筛选',
        		array('data-title' => ('选择项目')));
        //验收进度筛选下拉框
        $rate_arr = [
            ['id'=>'all','value'=>'全部'],
            ['id'=>'0,20','value'=>"0-20%"],
            ['id'=>'20,40','value'=>"20%-40%"],
            ['id'=>'40,60','value'=>"40%-60%"],
            ['id'=>'60,80','value'=>"60%-80%"],
            ['id'=>'80,100','value'=>"80%-100%"],
        ];
        $builder->select(L('验收进度：'), 'rate', 'select', L('验收进度'), '', '', $rate_arr);
        
        //按销项率筛选
        $agree_rate_arr = [
            ['id'=>'all','value'=>'全部'],
            ['id'=>'0,20','value'=>"0-20%"],
            ['id'=>'20,40','value'=>"20%-40%"],
            ['id'=>'40,60','value'=>"40%-60%"],
            ['id'=>'60,80','value'=>"60%-80%"],
            ['id'=>'80,100','value'=>"80%-100%"],
        ];
        $builder->select(L('销项率：'), 'agree_rate', 'select', L('销项率'), '', '', $agree_rate_arr);
        //接收下拉框收到的数据
        $get_agree = I('get.agree_rate','');
        //进行比较和筛选
        if(!empty($get_agree) && $get_agree != 'all'){
            $get_agree = explode(',',$get_agree);
            foreach($info_list as $key=>$sel){
                $choped = chop($sel['agree_rate'],'%');
                if(!( ($choped >= $get_agree[0]) && ($choped <= $get_agree[1]) )){
                    unset($info_list[$key]);
                }
            }
        }
        
        //列表
        $builder->keyText('task_num',L('任务编号'))
        ->keyText('pro_name',L('项目名称'))
        ->keyText('position_name',L('验收范围'))
        ->keyText('owner_name',L('提交人'))
        ->keyText('create_time',L('创建时间'))
        ->keyText('rate',L('验收进度'))
        ->keyText('agree_rate',L('销项率'));
        
        //循环数据分页输出
        $totalcount = count($info_list);
        $info_list = $this->set_num($info_list); //增加任务编号
        $builder->data($info_list);
        $builder->pagination($totalcount,$r);
        $builder->display();
    }
    
    /****************** 房间列表 ******************/
    public function room_list($page=1,$r=20) {
        
        //接收下拉框项目筛选
        $project_id = I('get.ownid','');
        
        if($project_id){
        	$proids=get_select_projects($project_id);
        	 
        	$map['project_id'] = array('in', $proids);
        
        }
        //房间列表查询
        $room_list = D('JkAcprogram')->relation(true)->where($map)
        ->field('project_id,building_id,unit_id,floor_id,room_id,stage_id')
        ->group('room_id,stage_id')->page($page,$r)->select();
     
        $totalcount = count($room_list);
        foreach($room_list as &$room){
            $room['position'] = $room['building'].'-'.$room['unit'].'-'.$room['floor'].'-'.$room['room'];
            $room['id'] = $room['room_id'];
            
            $room['stage']=M("jk_check_option")->where("item_id=".$room['stage_id']."")->getField("item_name");
        }
        
        //建立模板
        $builder = new AdminListBuilder();
        $builder->title(L('房间列表'));
        $builder->meta_title = '房间列表';
        
        //项目筛选下拉框
        //构造项目数组
        $projectwhere['status']    =   array('gt', 0);
        if(!IS_ROOT){
            $projectwhere['id']    =   array('in', get_my_projects());
        }
        $list = M('jk_project')->where($projectwhere)->field('id,name')->select();
        $projectArr = array();
        $projectArr[0]['id'] = 'all';
        $projectArr[0]['value'] = '全部';
        $i = 1;
        foreach ($list as $value){
            $projectArr[$i]['id'] = $value['id'];
            $projectArr[$i]['value'] = $value['name'];
            $i++;
        }
       
        $builder->setSelectPostUrl(U('Tenement/room_list').'&ownid='.$project_id);
        //$builder->select(L('项目：'), 'project_id', 'select', L('项目'), '', '', $projectArr);
        //项目筛选
        $builder->buttonModalPopup(U('JKProgram/selectproject'),
        		'',
        		'根据项目筛选',
        		array('data-title' => ('选择项目')));
        //$builder->select(L('项目：'), 'project_id', 'select', L('项目'), '', '', $projectArr);
		//导出房间详情
//         $attr['target-form'] = 'ids';
//         $attr['href'] = U('ex_room_word');
//         $attr['class']='a_jump';
//         $builder->button('导出', $attr);
        //列表
        $builder->keyText('name',L('项目名称'))
        ->keyText('position',L('房间名称'))
        ->keyText('stage',L('检查阶段'))
        ->keyDoAction('room_info?id=###',L('详情'));
        
        $builder->data($room_list);
        $builder->pagination($totalcount,$r);
        $builder->display();
    }
    
    /****************** 房间列表详情 ******************/
    public function room_info() {
        $id = I('get.id');
        
        //查询该房间下所有问题
        $rooms = D('JkAcprogram')->relation(true)->where(['room_id'=>$id])->select();
        $rooms[0]['stage']=M("jk_check_option")->where("item_id=".$rooms[0]['stage_id']."")->getField("item_name");
        //查询户型图
        $map['house_type_picture_id'] = $rooms[0]['house_img_id'];

        $house_img_url = M('JkHouseImage')->where($map)->getField('picture_url');
        //如果没有户型图，就不存
        if(!$house_img_url || $house_img_url==null || $house_img_url==''){                   
        }else{
            //图片地址、文件路径、文件名称，类型
            $file_url = './house_img';
            $file_name=$id.'.jpg';//房间id
            $upload_img=getImage($house_img_url,$file_url,$file_name);
           // var_dump($upload_img);
            $house_img_url=$upload_img['save_path'];
        }
        //查询房间里所有问题坐标
        $coordinates = "";
        foreach($rooms as $room){
            $coordinates .= M('JkAcprogramPoint')->where(['problem_id'=>$room['problem_id'],'state'=>'normal'])->getField('coordinate')."|";    
        }

        $rooms[0]['position'] = $rooms[0]['building'].'-'.$rooms[0]['unit'].'-'.$rooms[0]['floor'].'-'.$rooms[0]['room'];
        foreach($rooms as &$v){
            foreach($v as &$vv){
                if($vv == 'null' || $vv == '')
                    $vv = '&ensp;';
            }
        }

        //导出
//         $attr['target-form'] = 'ids';
//         $attr['href'] = U('ex_word');
//         $attr['class']='a_jump';
//         $builder->button('导出通知单', $attr);
        $this->assign('house_picture_url',$house_img_url);
        $this->assign("room_id",$id);
        $this->assign('rooms',$rooms);
        
        $coordinates=substr($coordinates,0,strlen($coordinates)-1);
        $this->assign('coordinates',$coordinates);
        $this->display();
    }
    
    /**
     * 构造任务编号
     * @param: 二维数组 $arr
     * @return: array $arr
     */
    private function set_num($arr) {
        $i = 1;
        foreach($arr as &$v){
            $v['task_num'] = $i++;
        }
        return $arr;
    }
    
    /**
     * 获取floor表里的位置
     * @param: int $id 位置id
     * @return: string $position 具体位置
     */
    private function get_position($id,$position='') {
        $info = M('JkFloor')->field('title,pid')->find($id);
        $position = $info['title'].$position;
        if($info['pid'] != 0){
            $this->get_position($info['pid'],$position);
        }else{
            return $position;
        }
    }
    
    public function ex_word() {
        $arr = json_decode($_COOKIE['prids']);
        if (! $arr) {
            $this->error("未选择操作数据！");
        }
        foreach ($arr as $obj) {
            $ids[] = $obj->value;
        }
//         $map['problem_id'] = array(
//             'in',
//             $ids
//         );
//         $list = D("jk_acprogram")->relation(true)->where($map)->select();
        $counts = count($ids);
        foreach ($ids as $id){//每一个id
            $info = D('JkAcprogram')->relation(true)->find($id);
            //查询问题图片
            $problem_imgs = M('JkAcprogramImage')->where(['problem_id'=>$id])->getField('photo_url',true);
            //查询回复及图片
            $replies = D('JkComment')->relation(true)->where(['program_id'=>$id])->select();
            //dump($replies);
            //查询验收信息
            $recheck = M('jk_acprogram_recheck')->where("problem_id = '$id'")->select();
            //一共多少验收次数
            $recheckcount = M('jk_acprogram_recheck')->where("problem_id = '$id'")->count();
            //查询验收附件
            $recheck_etx = M('jk_acprogram_recheck_etx')->where("problem_id = '$id'")->select();
            if($recheck){
                //把附件图片放入验收信息中
                foreach ($recheck as $v){
                    $newrecheck['id'] = $v['accept_id'];
                    $newrecheck['program_id'] = $v['problem_id'];
                    $newrecheck['name'] = $v['accept_user'];
                    $newrecheck['phone'] = $v['accept_user_phone'];
                    $newrecheck['content'] = $v['reason'];
                    $newrecheck['create_time'] = $v['create_time'];
                    $newrecheck['update_time'] = $v['last_modify_time'];
                    $newrecheck['state'] = $v['state'];
                    $newrecheck['reply_time'] = $v['reply_time'];
                    $newrecheck['isrecheck'] = 1;
                    $imgs = [];
                    foreach ($recheck_etx as $vv){
                        if ($v['accept_id'] == $vv['accept_id']){
                            $imgurl['path'] = $vv['attachment_url'];
                            $imgs[] = $imgurl;
                        }
                    }
                    $newrecheck['imgs'] = $imgs;
                    $newrec = [];
                    $newrec[] = $newrecheck;
                    $replies = array_merge($replies,$newrec);//合并数组
                }
                //dump($replies);
            }
            $replies = array_sort($replies,'create_time');//按照create_time进行升序
            $info['position'] = $info['building'].'-'.$info['unit'].'-'.$info['floor'].'-'.$info['room'];
            $reckcount[] = $recheckcount;//复核次数
            $rep[] = $replies;//所有的回复信息
            $pro_img[] = $problem_imgs;
            $proinfo[] = $info;
        }
//         dump($counts);
//         dump($reckcount);
//         dump($rep);
//         dump($pro_img);
//         dump($proinfo);
        $this->assign('counts',$counts);
        $this->assign('reckcount',$reckcount);
        $this->assign('rep',$rep);
        $this->assign('pro_img',$pro_img);
        $this->assign('proinfo',$proinfo);
        $this->assign("url","http://" . $_SERVER['HTTP_HOST'] . "/");
        //$this->display();die;
        
        $content=$this->fetch();
        
      //  $content = $this->fetch('/JKProgram@JKProgram/exWord'.$_COOKIE['areanum']);
         
         
        //$flieName =  "问题详情";
        //$content = str_replace("src=\"./Uploads/", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/Uploads/", $content);
        //echo $content;die;
        //cword($content,$flieName);
        
        //header("location:问题详情.doc");die;
        // $fileContent = $this->getWordDocument($content,$_SERVER['HTTP_HOST'].'/',0);
        $flieName = iconv("UTF-8", "GBK", "detail.doc");
    
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">'; // 这句不能少，否则不能识别图片
      
        $fp = fopen($flieName, 'w');
        // dump($html.$content.'</html>');
        fwrite($fp, $html . $content . '</html>');
        fclose($fp);
        header("location:detail.doc");
        
        // $this->display('/JKProgram@JKProgram/exWord');
    }
    /**
     * 函数用途描述：导出房间详情word
     * @date: 2017年06月22日 上午09:05:16
     *
     * @author : tanjiewen
     * @param
     *            :
     * @return :
     */
    public function ex_room_word1()
    {
    	//选中的房间id
    	$arr = json_decode($_COOKIE['prids']);
    	//var_dump($arr);die;
    	if (! $arr) {
    		$this->error("未选择操作数据！");
    	}
    	$content="";
    	//遍历每个房间，查出该房间对应的问题
    	foreach ($arr as $obj) {
    		$map['room_id'] = $obj->value;
    		 //查询该房间下所有问题
	        $rooms = D('JkAcprogram')->relation(true)->where($map)->select();
	
	        //查询户型图
	        $map['house_type_picture_id'] = $rooms[0]['house_img_id'];
	
	        $house_img_url = M('JkHouseImage')->where($map)->getField('picture_url');
	
	        //查询房间里所有问题坐标
	        $coordinates = "";
	        foreach($rooms as $room){
	            $coordinates .= M('JkAcprogramPoint')->where(['problem_id'=>$room['problem_id'],'state'=>'normal'])->getField('coordinate')."|";    
	        }
	
	        $rooms[0]['position'] = $rooms[0]['building'].'-'.$rooms[0]['unit'].'-'.$rooms[0]['floor'].'-'.$rooms[0]['room'];
	        foreach($rooms as &$v){
	            foreach($v as &$vv){
	                if($vv == 'null' || $vv == '')
	                    $vv = '&ensp;';
	            }
	        }
	
	        //导出
	//         $attr['target-form'] = 'ids';
	//         $attr['href'] = U('ex_word');
	//         $attr['class']='a_jump';
	//         $builder->button('导出通知单', $attr);
	        $this->assign('house_picture_url',$house_img_url);
	       
	        $this->assign('rooms',$rooms);
	        
	        $coordinates=substr($coordinates,0,strlen($coordinates)-1);
	        $this->assign('coordinates',$coordinates);
    		$this->display();die;
    		//var_dump($house_img_url);die;
	        $this->assign('coordinates',$coordinates);
	        $this->assign("url","http://" . $_SERVER['HTTP_HOST'] . "/");
    		$content .= $this->fetch();
    		$title="分户验收专用表";
    		$location= (cword($content,iconv("UTF-8","GB2312//IGNORE",$title)));
    		header("location:".$location);
    		//echo $content;die;
    	}   	  
    	// $fileContent = $this->getWordDocument($content,$_SERVER['HTTP_HOST'].'/',0);
    	$flieName = iconv("UTF-8", "GBK", "分户验收专用表.doc");
    
    	//$content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了
    
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
    	header("location:分户验收专用表.doc");
    
    	// $this->display('/JKProgram@JKProgram/exWord');
    }
    /**
     * 函数用途描述：预览房间详情
     * @date: 2017年06月22日 上午11:50:16
     *
     * @author : tanjiewen
     * @param
     *            :
     * @return :
     */
    public function look_room_word($room_id=0)
    {   
    		$map['room_id'] = $room_id;
    		//查询该房间下所有问题
    		$rooms = D('JkAcprogram')->relation(true)->where($map)->select();
    
    		//查询户型图
    		$map['house_type_picture_id'] = $rooms[0]['house_img_id'];
    
    		$house_img_url = M('JkHouseImage')->where($map)->getField('picture_url');
    
    		//查询房间里所有问题坐标
    		$coordinates = "";
    		foreach($rooms as $room){
    			$coordinates .= M('JkAcprogramPoint')->where(['problem_id'=>$room['problem_id'],'state'=>'normal'])->getField('coordinate')."|";
    		}
    
    		$rooms[0]['position'] = $rooms[0]['building'].'-'.$rooms[0]['unit'].'-'.$rooms[0]['floor'].'-'.$rooms[0]['room'];
    		foreach($rooms as &$v){
    			foreach($v as &$vv){
    				if($vv == 'null' || $vv == '')
    					$vv = '&ensp;';
    			}
    		}
        
//     		//用正则匹配出外联CSS样式表的地址，转成base64编码，再加入
//     		$css1 = file_get_contents('http://mall.irosn.com.cn/Public/assets/global/plugins/bootstrap/css/bootstrap.min.css');
//     		$css2 = file_get_contents('http://mall.irosn.com.cn/Public/js/ext/magnific/magnific-popup.css');
//     		$css1 = base64_encode($css1);
//     		$css2 = base64_encode($css2);
    		
//     		$js1 = file_get_contents('Public/js/jquery-2.0.3.min.js');
//     		$js2 = file_get_contents('Public/js/ext/magnific/jquery.magnific-popup.min.js');
    		//$js1 = base64_encode($js1);
    		//$js2 = base64_encode($js2);
    		
    		
    		//导出
    		//         $attr['target-form'] = 'ids';
    		//         $attr['href'] = U('ex_word');
    		//         $attr['class']='a_jump';
    		//         $builder->button('导出通知单', $attr);
    		$this->assign('house_picture_url',$house_img_url);
    
    		$this->assign('rooms',$rooms);
    		$this->assign('css1',$css1);
    		$this->assign('css2',$css2);
    		$this->assign('js1',$js1);
    		$this->assign('js2',$js2);
    		 
    		$coordinates=substr($coordinates,0,strlen($coordinates)-1);
    		$this->assign('coordinates',$coordinates);
    		$this->display();    		   	   	
    }
    /**
     * 函数用途描述：导出房间详情word
     * @date: 2017年06月22日 下午13:50:16
     *
     * @author : tanjiewen
     * @param
     *            :
     * @return :
     */
     public function ex_room_word(){  	
//      	$title="分户验收专用表";
//      	$location= (cword($content,iconv("UTF-8","GB2312//IGNORE",$title)));
//      	header("location:".$location);
		//获取参数 
        $room_name= $_POST['room_name'];
        $room_id  = $_POST['room_id'];
     	$img_html = $_POST['img_html'];
     	
     	//将base64位转为图片并存到本地返回url
     	$base_img = str_replace('data:image/png;base64,', '', $img_html);
     
     	// 设置存储的文件路径以及文件名称
     	
     	$path = "./point_image/";
     	//创建保存目录
        if (!is_dir($path)){
            mkdir($path,0777);  // 创建文件夹test,并给777的权限（所有权限）
        }
     	$output_file =  $room_id .'.png';
     	
     	$path = $path.$output_file;
//      	$a = file_put_contents($path, base64_decode($base_img));//返回的是字节数
//      	echo $path;return;
    
     	
     	//  创建将数据流文件写入我们创建的文件内容中
     	$ifp = fopen( $path, "wb" );
     	fwrite( $ifp, base64_decode( $base_img) );
     	fclose( $ifp );
        
   
     	//根据房间ID查询对应消息
     	
     	$map['room_id'] = $room_id;
     	//查询该房间下所有问题
     	$rooms = D('JkAcprogram')->relation(true)->where($map)->select();
     	$rooms[0]['position'] = $rooms[0]['building'].'-'.$rooms[0]['unit'].'-'.$rooms[0]['floor'].'-'.$rooms[0]['room'];
     	$rooms[0]['stage']=M("jk_check_option")->where("item_id=".$rooms[0]['stage_id']."")->getField("item_name");
     	$this->assign('rooms',$rooms);

     	$this->assign('img_html',"http://" . $_SERVER['HTTP_HOST']."/".$path);

     	//echo "http://" . $_SERVER['HTTP_HOST'] .$path;die;
     	//dump($rooms);die;
     	$content =$this->fetch();
     	//echo $content;die;
     	$flieName = iconv("UTF-8", "GBK", $room_id."xiangqing.doc");
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">'; // 这句不能少，否则不能识别图片
        $fp = fopen($flieName, 'w');
        // dump($html.$content.'</html>');
        fwrite($fp, $html . $content . '</html>');
        fclose($fp);
     	echo $room_id."xiangqing.doc";
     }


     public function house_typelist($page=1, $r=20){
         $db=M('jk_house_typelist');
         $map['status'] = array('egt', 0);

         $goodsList = $db->where($map)->order('id')->page($page, $r)->select();
         $totalCount = $db->where($map)->count();

         $builder = new AdminListBuilder();

         $builder->buttonNew(U('edithousetype'));

         $builder->title(L('房间类型列表'));
         $builder->meta_title = L('房间类型列表');
         $i=1;
         foreach ($goodsList as &$v){
             $v['num']=$i;
             $i++;
         }
//        dump($goodsList);
         $builder->keyText('num', L('序号'))
             ->keyText('title', L('名称'))
             ->keyStatus('status', L('状态'))
             ->keyDoActionEdit('edithousetype?id=###');

         $builder->data($goodsList);
         $builder->pagination($totalCount, $r);
         $builder->display();
     }

}   //class end


