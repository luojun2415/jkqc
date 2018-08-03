<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 朱亚杰 <zhuyajie@topthink.net>
// +----------------------------------------------------------------------

namespace Admin\Controller;

use Admin\Model\AuthRuleModel;
use Admin\Model\AuthGroupModel;
use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use JKProgram\Controller\JKProgram;


/**
 * 权限管理控制器
 * Class AuthManagerController
 * @author 朱亚杰 <zhuyajie@topthink.net>
 */
class AuthManagerController extends AdminController
{

    /**
     * 后台节点配置的url作为规则存入auth_rule
     * 执行新节点的插入,已有节点的更新,无效规则的删除三项任务
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function updateRules()
    {
        //需要新增的节点必然位于$nodes
        $nodes = $this->returnNodes(false);

        $AuthRule = M('AuthRule');
        $map = array('module' => 'admin', 'type' => array('in', '1,2'));//status全部取出,以进行更新
        //需要更新和删除的节点必然位于$rules
        $rules = $AuthRule->where($map)->order('name')->select();

        //构建insert数据
        $data = array();//保存需要插入和更新的新节点
        foreach ($nodes as $value) {
            $temp['name'] = $value['url'];
            $temp['title'] = $value['title'];
            $temp['module'] = 'admin';
            if ($value['pid'] > 0) {
                $temp['type'] = AuthRuleModel::RULE_URL;
            } else {
                $temp['type'] = AuthRuleModel::RULE_MAIN;
            }
            $temp['status'] = 1;
            $data[strtolower($temp['name'] . $temp['module'] . $temp['type'])] = $temp;//去除重复项
        }

        $update = array();//保存需要更新的节点
        $ids = array();//保存需要删除的节点的id
        foreach ($rules as $index => $rule) {
            $key = strtolower($rule['name'] . $rule['module'] . $rule['type']);
            if (isset($data[$key])) {//如果数据库中的规则与配置的节点匹配,说明是需要更新的节点
                $data[$key]['id'] = $rule['id'];//为需要更新的节点补充id值
                $update[] = $data[$key];
                unset($data[$key]);
                unset($rules[$index]);
                unset($rule['condition']);
                $diff[$rule['id']] = $rule;
            } elseif ($rule['status'] == 1) {
                $ids[] = $rule['id'];
            }
        }
        if (count($update)) {
            foreach ($update as $k => $row) {
                if ($row != $diff[$row['id']]) {
                    $AuthRule->where(array('id' => $row['id']))->save($row);
                }
            }
        }
        if (count($ids)) {
            $AuthRule->where(array('id' => array('IN', implode(',', $ids))))->save(array('status' => -1));
            //删除规则是否需要从每个用户组的访问授权表中移除该规则?
        }
        if (count($data)) {
            $AuthRule->addAll(array_values($data));
        }
        if ($AuthRule->getDbError()) {
            trace('[' . __METHOD__ . ']:' . $AuthRule->getDbError());
            return false;
        } else {
            return true;
        }
    }


    /**
     * 权限管理首页
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function index($id=0)
    {
        if($id){
        
            int_to_string($list);
            $where['module']='admin';
            $where['pid']=$id;
            $list = $this->lists2('auth_group', $where);
            $this->assign('_list', $list);
            $this->meta_title = L('用户组管理');
            $content = $this->fetch('grouplist');
            //             dump($content);
            echo $content;
            return;
        }
        $map=array();
        $list = M('AuthGroup')->where(" `status` >= 0 AND `module` = 'admin'")->order('sort DESC')->select();
		
        if(IS_ROOT){
            $alist=list_to_tree($list,'id','pid','_');
            $list = int_to_string($list);
        }
        else{
            
            $group_ids = M('auth_group_access')->where("uid=".UID)->field('group_id')->select();
            $alist = array();
            $oldlist = array();
            $iflag=0;
            foreach ($group_ids as $v) {
                $map  = array('status' => array('gt', 0),'id'=>$v['group_id']);
                $glist=M('AuthGroup')->field('id,title,pid,cate,sort,status,gysCode')->where($map)->find();
//                 if($glist['cate']!=1){
//                     $glist=getCatePath('AuthGroup',$glist['id'],1);
//                 }
                foreach ( $oldlist as $value){
                    if($value[$glist['id']]){
                        $iflag=1;
                    }
                }
                if($iflag){
                    $iflag=0;
                    continue;
                }
                $glistTem=list_to_tree($list,'id','pid','_',$v['group_id']);
                if($glistTem){
                    $glist['_']=$glistTem;
                    // dump($glist);
                }
                $alist[] = $glist;
                $temlist=tree_to_listwx($alist,'_');
                foreach ($temlist as $v){
                    $ids[]=$v['id'];
                }
                $oldlist[]=$temlist;
            }
          
            $list=tree_to_list($alist,'_');
        }

        //$this->assign('total', count($list));
        $where['id']=array('in', $ids);
      
        //给权限组根据sort重新排序
        $newArr=array();
        for($j=0;$j<count($alist);$j++){
            //echo $alist[$j]['sort'];
            $newArr[]=$alist[$j]['sort'];
        }
        array_multisort($newArr,SORT_DESC,$alist,SORT_DESC);
        $list = $this->lists2('auth_group', $where);
    	$this->assign('nodeList',$alist);
    	$this->assign('_list',$list);
    
    	$this->meta_title = L('用户组管理');
    	$this->display();    	
    	
    }
    
    /**
    *函数用途描述：获取用户组html
    *@date：2016年11月21日 下午3:24:53
    *@author：luoj
    *@param：
    *@return：
    **/
    public function grouplist($id=0) {
        if($id){
            //$list = $this->lists('AuthGroup', array('module' => 'admin'), 'id asc');
//             if(IS_ROOT){
                
//                 $list = int_to_string($list);
//             }
//             else{
//                 $map['status'] = array('gt', -1);
//                 $map['pid'] = $id;
               
//                 $list = M('auth_group')->where($map)->order('id')->select();
//             }
            $map['status'] = array('gt', -1);
            $map['pid'] = $id;
            $list = $this->lists('auth_group', $map);
            int_to_string($list);
        	$this->assign('_list',$list);
        	$this->meta_title = L('_PRIVILEGE_MANAGEMENT_');
        	//$content = $this->fetch();
            //             dump($content);
            $this->display();
        }else{
        	if(!IS_ROOT){
        		$group_ids = M('auth_group_access')->where("uid=".UID)->getField('group_id',true);
        		$map['pid'] = array('in',$group_ids);
        	}
        	
            $map['status'] = array('gt', -1);
            $list = $this->lists('auth_group', $map);
            int_to_string($list);
            $this->assign('_list',$list);
            $this->meta_title = L('_PRIVILEGE_MANAGEMENT_');
            //$content = $this->fetch();
            //             dump($content);
            $this->display();
        }
         
    }

    /**
     * 创建管理员用户组
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function createGroup()
    {
        $cates=M('auth_group')->where("status=-2")->getfield('id,id,title as name,rules');
        
		if(IS_POST){
		    $pid=$_POST["pid"];
		   
		   if ($pid){
    			$_POST['module'] = 'admin';
    			$_POST['type'] = AuthGroupModel::TYPE_ADMIN;
    			$edit=$_POST['projectedit'];
    			
                $AuthGroup = D('AuthGroup');
                
                $data = $AuthGroup->create();
                if ($data) {
                    $data['rules'] = $cates[$data['cate']]['rules'];
                    //判断是否有同名用户组
//                     $where1['title']=$_POST["title"];                  
//                     $authname=$AuthGroup->where($where1)->select();
//                     if($authname)
//                         $this->error(L('已经创建了同名用户组'));
//                     else
                    //如果未填写简称，默认为跟全称一样
                    if($data['short_title']=='' || $data['short_title']==null)
                        $data['short_title']=$data['title'];
                    $r = $AuthGroup->add($data);
                    $getID=mysql_insert_id();//获取才插入的数据ID
                    if ($edit=="edit"){//是否创建同名项目
                        $addProj=M('jk_project');
                        $name=$_POST['title'];
                        $where["name"]=$name;
                        $where['pid']=$getID;
                        // echo $pid;echo $id;die;
                        //   $data["other_name"]=$name;
                        //  $this->error(L($pid."-".$id));
                        $oldname=$addProj->where($where)->select();
                        	
                        if ($oldname){//是否已经有了相同的项目名
                            $this->error(L('已经创建了同名项目'));
                        }else {
                             
                            $addProj->name=$name;
                            $addProj->other_name=$name;
                            $addProj->periods=5;
                            $addProj->batch=5;
                            $addProj->blocks=5;
                            $addProj->create_time=time();
                            $addProj->update_time=time();
                            $addProj->status=1;
                            $addProj->pid=$getID;
                            $addProj->add();
                        }
                        	
                    }
                    if ($r === false) {
                        $this->error(L('操作失败！') . $AuthGroup->getError());
                    } else {
                        $this->success('操作成功!',U('AuthManager/index'));
                        
                    }
                } else {
                    $this->error(L('_FAIL_OPERATE_') . $AuthGroup->getError());
                }
           }else{
               $this->error('未选择组织架构');
               }
       }
        /* if (empty($this->auth_group)) {
            $this->assign('auth_group', array('title' => null, 'id' => null, 'description' => null, 'rules' => null, 'pid' => null,));//排除notice信息
        }
        $group=M('auth_group')->field('id,title')->where('status=1')->select();
        $this->assign('topgroup',$group); */
    	//dump(UID);die();
    	
       	$auth_group['pid']=UID;
       	$this->assign('authgroup_pid',UID);
       	
       	$auth_group = M('auth_group_access')->where(array('uid' => UID))->find();      	
       	$topgroup=M('auth_group')->field('id,title')->where("id=".$auth_group['group_id'])->find();
       	
       
       	$map  = array('status' => array('gt', 0));
        $list=M('AuthGroup')->field('id,title,pid,sort')->where($map)->select();
       	//dump($list);
       	if(IS_ROOT){
			$list=list_to_tree($list, 'id', 'pid', '_', 0);
			$isfind=1;
		}
		else{
			
			$initPid = $auth_group['id'];
			$group_ids = M('auth_group_access')->where("uid=".UID)->field('group_id')->select();
            $alist = array();
            $oldlist = array();
            $iflag=0;
            $isfind=0;//是否可以创建同名项目的权限是否找到
            foreach ($group_ids as $v) {
                $map  = array('status' => array('gt', 0),'id'=>$v['group_id']);
                $glist=M('AuthGroup')->field('id,title,pid,cate,rules,sort')->where($map)->find();
//                 if($glist['cate']!=1){
//                     $glist=getCatePath('AuthGroup',$glist['id'],1);
//                 }
                //判断该用户是否有创建同名项目的权限
                $rules=explode(',', $glist['rules']);
                if(!$isfind){
                    foreach ($rules as $vv){                 
                        if($vv=='10451'){
                          
                            $isfind=1;//如果找到该权限则设置isfind=1;                    
                        }
                    
                    }
                  
                }
             
                foreach ( $oldlist as $value){
                    if($value[$glist['id']]){
                        $iflag=1;
                    }
                }
                if($iflag){
                    $iflag=0;
                    continue;
                }
                $glistTem=list_to_tree($list,'id','pid','_',$v['group_id']);
                if($glistTem){
                    $glist['_']=$glistTem;
                    // dump($glist);
                }
                $alist[] = $glist;
                $temlist=tree_to_listwx($alist,'_');
                $oldlist[]=$temlist;
            }
            
            $list=$alist;
		}
		//给权限组根据sort重新排序
		$newArr=array();
		for($j=0;$j<count($list);$j++){
		
		    $newArr[]=$list[$j]['sort'];
		}
		
		array_multisort($newArr,SORT_DESC,$list,SORT_DESC);
        $this->assign('isfind',$isfind);
         //dump($list);
        $this->assign('cates',$cates);
        $this->assign('nodeList',$list);
       	
        $this->meta_title = L('_NEW_USER_GROUP_');
        $this->display('editgroup');
    }

    /**
     * 函数用途描述：创建供应商
     * @date: 2017年10月26日 下午1:39:06
     * @author: luojun
     * @param:
     * @return:
     */
    public function createOtherGroup()
    {
        $cates=M('auth_group')->where("status=-2 ")->getfield('id,id,title as name,rules');

        if(IS_POST){
            $pid=$_POST["pid"];
            if($_POST["cate"]==0){
                $this->error('未选择分类！');
            }
            if ($pid){
				if($pid==$_POST["id"]){
					$this->error('请勿选择自己为上级节点！');
				}
                $_POST['module'] = 'admin';
                $_POST['type'] = AuthGroupModel::TYPE_ADMIN;
                $edit=$_POST['projectedit'];

                $AuthGroup = D('AuthGroup');

                $data = $AuthGroup->create();
                if ($data) {
                    $data['rules'] = $cates[$data['cate']]['rules'];
                    $data['gysCode']=$_POST['mid'] ;
					if($data['cate']==156||$data['cate']==157){
						$where['Providernumber']=$data['gysCode'];
						$where['ProviderName']=$data['title'];
						$count = M('jk_provider_mdm')->where($where)->count();
						if($count <1){
							$this->error(L('供应商数据错误，请点击检索按钮从认证服务获取供应商数据！'));
						}
					}
                    //判断是否有同名用户组
                    //                     $where1['title']=$_POST["title"];
                    //                     $authname=$AuthGroup->where($where1)->select();
                    //                     if($authname)
                    //                         $this->error(L('已经创建了同名用户组'));
                    //                     else
                    //如果未填写简称，默认为跟全称一样
                    if($data['short_title']=='' || $data['short_title']==null)
                        $data['short_title']=$data['title'];
                    if($_POST['id']>0){
                        $r = $AuthGroup->where("id=".$_POST['id'])->save($data);
                    }
                    else{
                        $r = $AuthGroup->add($data);
                    }

                    if ($r === false) {
                        $this->error(L('操作失败！') . $AuthGroup->getError());
                    } else {
                        $this->success('操作成功!',U('AuthManager/index'));

                    }
                } else {
                    $this->error(L('_FAIL_OPERATE_') . $AuthGroup->getError());
                }
            }else{
                $this->error('未选择组织架构');
            }
        }
        /* if (empty($this->auth_group)) {
         $this->assign('auth_group', array('title' => null, 'id' => null, 'description' => null, 'rules' => null, 'pid' => null,));//排除notice信息
         }
         $group=M('auth_group')->field('id,title')->where('status=1')->select();
         $this->assign('topgroup',$group); */
        //dump(UID);die();
		
        $auth_group = M('auth_group_access')->where(array('uid' => UID))->find();
        $topgroup=M('auth_group')->field('id,title')->where("id=".$auth_group['group_id'])->find();
     

        $map  = array('status' => array('gt', 0));
        $list=M('AuthGroup')->field('id,title,pid,sort')->where($map)->select();      
        
        if(IS_ROOT){
            $list=list_to_tree($list, 'id', 'pid', '_', 0);

        }
        else{

            $initPid = $auth_group['id'];
            $group_ids = M('auth_group_access')->where("uid=".UID)->field('group_id')->select();
            $alist = array();
            $oldlist = array();
            $iflag=0;

            foreach ($group_ids as $v) {
                $map  = array('status' => array('gt', 0),'id'=>$v['group_id']);
                $glist=M('AuthGroup')->field('id,title,pid,cate,rules,sort')->where($map)->find();
                //                 if($glist['cate']!=1){
                //                     $glist=getCatePath('AuthGroup',$glist['id'],1);
                //                 }

                foreach ( $oldlist as $value){
                    if($value[$glist['id']]){
                        $iflag=1;
                    }
                }
                if($iflag){
                    $iflag=0;
                    continue;
                }
                $glistTem=list_to_tree($list,'id','pid','_',$v['group_id']);
                if($glistTem){
                    $glist['_']=$glistTem;
                    // dump($glist);
                }
                $alist[] = $glist;
                $temlist=tree_to_listwx($alist,'_');
                $oldlist[]=$temlist;
            }

            $list=$alist;
        }
        //给权限组根据sort重新排序
        $newArr=array();
        for($j=0;$j<count($list);$j++){

            $newArr[]=$list[$j]['sort'];
        }

        array_multisort($newArr,SORT_DESC,$list,SORT_DESC);   
        
        $this->assign('isfind',$isfind);
        //dump($list);
        $this->assign('cates',$cates);
        $this->assign('nodeList',$list);
        $auth_group = M('AuthGroup')->where(array('module' => 'admin', 'type' => AuthGroupModel::TYPE_ADMIN))
            ->find((int)$_GET['id']);
        $this->assign('auth_group', $auth_group);
        //获取已绑定的组织架构
        if($_GET['id']){
        	$group_title = '';
        	$pid = $auth_group['pid'];
        	 
        	while ($pid != 0) {
        		$auth = M('auth_group')->where('id=' . $pid)->field('pid,title')->find();
        		$group_title = $auth['title'] . "-" . $group_title;
        		$pid = $auth['pid'];
        	}
        	if ($group_title) {
        		$group_title = '(' . $group_title . ')';
        	}
        	$this->assign('group_title', $group_title);
        }
        $this->meta_title = L('_NEW_USER_GROUP_');
        $this->display('editothergroup');
    }

    /**
     * 编辑管理员用户组
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function editGroup()
    {
        $cates=M('auth_group')->where("status=-2")->getfield('id,id,cate,title as name,rules');
        
        if(IS_POST){
            $pid=$_POST["pid"];
            
            $edit=$_POST['projectedit'];
            	
		    if ($pid){
                $AuthGroup = D('AuthGroup');
                $data = $AuthGroup->create();
                if ($data) {
                    if($data['id']==$data['pid']){
                        unset($data['pid']);
                    }
                    $data['rules'] = $cates[$data['cate']]['rules'];
                    //如果未填写简称，默认为跟全称一样
                    if($data['short_title']=='' || $data['short_title']==null)
                        $data['short_title']=$data['title'];
                    $r = $AuthGroup->save($data);
                    //$sql=M()->getLastSql();
                  
                    if ($edit=="edit"){//是否创建同名项目
                        $addProj=M('jk_project');
                        $name=$_POST['title'];
                        $where["name"]=$name;
                        $where['pid']=$data['id'];
                        // echo $pid;echo $id;die;
                        //   $data["other_name"]=$name;
                        //  $this->error(L($pid."-".$id));
                        $oldname=$addProj->where($where)->select();
                        	
                        if ($oldname){//是否已经有了相同的项目名
                            $this->error(L('已经创建了同名项目'));
                        }else {
                             
                            $addProj->name=$name;
                            $addProj->other_name=$name;
                            $addProj->periods=5;
                            $addProj->batch=5;
                            $addProj->blocks=5;
                            $addProj->create_time=time();
                            $addProj->update_time=time();
                            $addProj->status=1;
                            $addProj->pid=$data['id'];
                            $addProj->add();
                        }
                        	
                    }           
                    if ($r === false) {
                        $this->error(L('操作失败！') . $AuthGroup->getError());
                    } else {
                        $this->success('操作成功!',U('AuthManager/index'));
                    }
                } else {
                    $this->error(L('_FAIL_OPERATE_') . $AuthGroup->getError());
                }
            }else{
            	$this->error('未选择组织架构');
            }
        }else{
	        $auth_group = M('AuthGroup')->where(array('module' => 'admin', 'type' => AuthGroupModel::TYPE_ADMIN))
	            ->find((int)$_GET['id']);
	        $this->assign('auth_group', $auth_group);
	        $topgroup=M('auth_group')->field('id,title')->where("id=".$auth_group['pid'])->find();
	        
	        $map  = array('status' => array('gt', 0));
	        $list=M('AuthGroup')->field('id,title,pid,sort')->order("sort DESC")->where($map)->select();
			
			if(IS_ROOT){
				$list=list_to_tree($list, 'id', 'pid', '_', 0);
				$isfind=1;
			}
			else{
				
				$initPid = $auth_group['id'];
				$group_ids = M('auth_group_access')->where("uid=".UID)->field('group_id')->select();
	            $alist = array();
	            $oldlist = array();
	            $iflag=0;
	            $isfind=0;
	            foreach ($group_ids as $v) {
	                $map  = array('status' => array('gt', 0),'id'=>$v['group_id']);
	                $glist=M('AuthGroup')->field('id,title,pid,cate,rules,sort')->where($map)->find();
	                //判断该用户是否有创建同名项目的权限
	                $rules=explode(',', $glist['rules']);
	                if(!$isfind){
	                    foreach ($rules as $vv){
	                    
	                        if($vv=='10451'){                
	                            $isfind=1;//如果找到该权限则设置isfind=1;
	                        }
	                
	                    }
	                
	                }
	                foreach ( $oldlist as $value){
	                    if($value[$glist['id']]){
	                        $iflag=1;
	                    }
	                }
	                if($iflag){
	                    $iflag=0;
	                    continue;
	                }
	                $glistTem=list_to_tree($list,'id','pid','_',$v['group_id']);
	                if($glistTem){
	                    $glist['_']=$glistTem;
	                    // dump($glist);
	                }
	                $alist[] = $glist;
	                $temlist=tree_to_listwx($alist,'_');
	                $oldlist[]=$temlist;
	            }
	            $list=$alist;
			}
			//给权限组根据sort重新排序
			$newArr=array();
			for($j=0;$j<count($list);$j++){
			    
			    $newArr[]=$list[$j]['sort'];
			}
		
			array_multisort($newArr,SORT_DESC,$list,SORT_DESC);
		
	// 		 dump($cates);
			$this->assign('isfind',$isfind);
	        $this->assign('cates',$cates);
	        $this->assign('nodeList',$list);
	        
	        $this->assign('pid',$auth_group['pid']);
	        $this->assign('group_title',$topgroup['title']);
	        $this->meta_title = L('_EDIT_USER_GROUP_');
	        $this->display();
        }
    }




    /**
     * 管理员用户组数据写入/更新
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function writeGroup()
    {
        if (isset($_POST['rules'])) {
            sort($_POST['rules']);
            $_POST['rules'] = implode(',', array_unique($_POST['rules']));
        }
        $_POST['module'] = 'admin';
        $_POST['type'] = AuthGroupModel::TYPE_ADMIN;
        $AuthGroup = D('AuthGroup');
        $data = $AuthGroup->create();
    
       
        if ($data) {
            $oldGroup = $AuthGroup->find($_POST['id']);
            $data['rules'] = $this->getMergedRules($oldGroup['rules'], explode(',', $_POST['rules']), 'eq');
            if (empty($data['id'])) {
                $r = $AuthGroup->add($data);
            } else {
                $r = $AuthGroup->save($data);
            }
           //$this->error(L(M()->getLastSql()));
            if ($r === false) {
                $this->error(L('_FAIL_OPERATE_') . $AuthGroup->getError());
            } else {
                if (-2==$oldGroup['status']) {
                    $rulse = array('rules'=>$data['rules']);
                    $AuthGroup->where("cate = ".$oldGroup['id'])->save($rulse);
                }
                $this->success('操作成功!',U('AuthManager/authattr'));
            }
        } else {
            $this->error(L('_FAIL_OPERATE_') . $AuthGroup->getError());
        }
    }
    
    /**
    * 函数用途描述：编辑权限属性
    * @date: 2016年10月28日 上午10:04:24
    * @author: luojun
    * @param: 
    * @return:
    */
    public function editManage($id=0,$name='',$info='') {
        $isEdit = $id ? 1 : 0;
        if (IS_POST) {
        	
            if ($name == '' || $name == null) {
                $this->error(L('请输入属性名'));
            }
               
         
            $goods = M('jk_auth_cate')->create();

            $goods['status'] = 1;
            if ($isEdit) {
                
                $rs = M('jk_auth_cate')->where('id=' . $id)->save($goods);
                //修改节点表的数据
                if($rs){                	
            	    $this->success(L('编辑成功') , U('authCategory'));                	
                }                
                $this->error(L('编辑失败！') );
            } else {
                //分类名存在验证
                
                $map['name'] = $name;
                if (M('jk_auth_cate')->where($map)->count()) {
                    $this->error(L('属性名冲突！'));
                }

                $rs = M('jk_auth_cate')->add($goods);
                if ($rs) {
                    
                    $this->success(L('新增成功') , U('authCategory'));                     
                } else {
                    $this->error(L('新增失败'));
                }
            }
            
        } else {
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? '编辑属性' : '新增属性');
            $builder->meta_title = $isEdit ?  '编辑属性' : '新增属性' ;

           
            $builder->keyId()->keyText('name','属性名称')->keyText('info', '备注');
            
            if ($isEdit) {
                //获取分类
                $map['status'] = array('egt', 0);
                $map['id'] = $id;
                $find = M('jk_auth_cate')->where($map)->find();
                
                $builder->data($find);
                $builder->buttonSubmit(U('editManage'));
                $builder->buttonBack();
                $builder->display();
            } else {            	
                $builder->buttonSubmit(U('editManage'));
                $builder->buttonBack();
                
                $builder->display();
            }
        }
    } 
        
    /**
     * 状态修改
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function changeStatus($method = null)
    {
        if (empty($_REQUEST['id'])) {
            $this->error(L('_PLEASE_CHOOSE_TO_OPERATE_THE_DATA_'));
        }
        switch (strtolower($method)) {
            case 'forbidgroup':
                $this->forbid('AuthGroup');
                break;
            case 'resumegroup':
                $this->resume('AuthGroup');
                break;
            case 'deletegroup':
                $this->delete('AuthGroup');
                break;
            default:
                $this->error($method . L('_ILLEGAL_'));
        }
    }

    /**
     * 用户组授权用户列表
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function user($group_id)
    {
        if (empty($group_id)) {
            $this->error(L('_PARAMETER_ERROR_'));
        }

        //$auth_group = M('AuthGroup')->where(array('status' => array('egt', '0'), 'module' => 'admin', 'type' => AuthGroupModel::TYPE_ADMIN))
        //    ->getfield('id,id,title,rules');
        $map['status'] = array('gt', -1);
        $map['pid'] = UID;
        $auth_group=M('auth_group')->where($map)->order('id asc')->select();
        $prefix = C('DB_PREFIX');
        $l_table = $prefix . (AuthGroupModel::MEMBER);
        $r_table = $prefix . (AuthGroupModel::AUTH_GROUP_ACCESS);
        $model = M()->table($l_table . ' m')->join($r_table . ' a ON m.uid=a.uid');
        $_REQUEST = array();
        $list = $this->lists($model, array('a.group_id' => $group_id, 'm.status' => array('egt', 0)), 'm.uid asc', null, 'm.uid,m.nickname,m.last_login_time,m.last_login_ip,m.status');
        int_to_string($list);
        $this->assign('_list', $list);
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $auth_group[(int)$_GET['group_id']]);
        $this->meta_title = L('_MEMBER_AUTHORITY_');
        $this->display();
    }



    public function tree($tree = null)
    {
        $this->assign('tree', $tree);
        $this->display('tree');
    }

    /**
     * 将用户添加到用户组的编辑页面
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function group()
    {
        $uid = I('uid');
        $auth_groups = D('AuthGroup')->getGroups();
    	if(IS_ROOT){
    	    $list = list_to_tree($auth_groups, 'id', 'pid', '_', 0);
    	}
    	
        $user_groups = AuthGroupModel::getUserGroup($uid);
        $ids = array();
        foreach ($user_groups as $value) {
            $ids[] = $value['group_id'];
        }
        $nickname = D('Member')->getNickName($uid);
        $this->assign('nickname', $nickname);
        $this->assign('auth_groups', $auth_groups);
        $this->assign('user_groups', implode(',', $ids));
        
        if(!IS_ROOT){
            $group_ids = M('auth_group_access')->where("uid=".UID)->field('group_id')->select();
            $alist = array();
            $oldlist = array();
            $iflag=0;
            foreach ($group_ids as $v) {
                $map  = array('status' => array('gt', 0),'id'=>$v['group_id']);
                $glist=M('AuthGroup')->field('id,title,pid,cate,sort')->where($map)->find();

                foreach ( $oldlist as $value){
                    if($value[$glist['id']]){
                        $iflag=1;
                    }
                }
                if($iflag){
                    $iflag=0;
                    continue;
                }
                $glistTem=list_to_tree($auth_groups,'id','pid','_',$v['group_id']);
                if($glistTem){
                    $glist['_']=$glistTem;
                    // dump($glist);
                }
                $alist[] = $glist;
                $temlist=tree_to_listwx($alist,'_');
                $oldlist[]=$temlist;
            }
            
            $list=$alist;
           
            
        }
        //给权限组根据sort重新排序
        $newArr=array();
        for($j=0;$j<count($list);$j++){
           
            $newArr[]=$list[$j]['sort'];
        }
        array_multisort($newArr,SORT_DESC,$list,SORT_DESC);
        $this->assign('nodeList', $list);
        
        $this->display();
    }

    /**
     * 将用户添加到用户组,入参uid,group_id
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function addToGroup()
    {
        $uid = I('uid');
        $gid = I('group_id');
        if (empty($uid)) {
            $this->error(L('_PARAMETER_IS_INCORRECT_'));
        }
        $AuthGroup = D('AuthGroup');
        if (is_numeric($uid)) {
            if (is_administrator($uid)) {
                $this->error(L('_THE_USER_IS_A_SUPER_ADMINISTRATOR_'));
            }
            if (!M('Member')->where(array('uid' => $uid))->find()) {
                $this->error(L('_ADMIN_USER_DOES_NOT_EXIST_'));
            }
        }

        if ($gid && !$AuthGroup->checkGroupId($gid)) {
            $this->error($AuthGroup->error);
        }
        if ($AuthGroup->addToGroup($uid, $gid)) {
            $this->success(L('_SUCCESS_OPERATE_'),U('user/index'));
        } else {
            $this->error($AuthGroup->getError().json_encode($gid));
        }
    }

    /**
     * 将用户从用户组中移除  入参:uid,group_id
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function removeFromGroup()
    {
        $uid = I('uid');
        $gid = I('group_id');
        if ($uid == UID) {
            $this->error(L('_NOT_ALLOWED_TO_RELEASE_ITS_OWN_AUTHORITY_'));
        }
        if (empty($uid) || empty($gid)) {
            $this->error(L('_PARAMETER_IS_INCORRECT_'));
        }
        $AuthGroup = D('AuthGroup');
        if (!$AuthGroup->find($gid)) {
            $this->error(L('_USER_GROUP_DOES_NOT_EXIST_'));
        }
        if ($AuthGroup->removeFromGroup($uid, $gid)) {
            $this->success(L('_SUCCESS_OPERATE_'));
        } else {
            $this->error(L('_FAIL_OPERATE_'));
        }
    }

    /**
     * 将分类添加到用户组  入参:cid,group_id
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function addToCategory()
    {
        $cid = I('cid');
        $gid = I('group_id');
        if (empty($gid)) {
            $this->error(L('_PARAMETER_IS_INCORRECT_'));
        }
        $AuthGroup = D('AuthGroup');
        if (!$AuthGroup->find($gid)) {
            $this->error(L('_USER_GROUP_DOES_NOT_EXIST_'));
        }
        if ($cid && !$AuthGroup->checkCategoryId($cid)) {
            $this->error($AuthGroup->error);
        }
        if ($AuthGroup->addToCategory($gid, $cid)) {
            $this->success(L('_SUCCESS_OPERATE_'));
        } else {
            $this->error(L('_FAIL_OPERATE_'));
        }
    }

    /**
     * 将模型添加到用户组  入参:mid,group_id
     * @author 朱亚杰 <xcoolcc@gmail.com>
     */
    public function addToModel()
    {
        $mid = I('id');
        $gid = I('get.group_id');
        if (empty($gid)) {
            $this->error(L('_PARAMETER_IS_INCORRECT_'));
        }
        $AuthGroup = D('AuthGroup');
        if (!$AuthGroup->find($gid)) {
            $this->error(L('_USER_GROUP_DOES_NOT_EXIST_'));
        }
        if ($mid && !$AuthGroup->checkModelId($mid)) {
            $this->error($AuthGroup->error);
        }
        if ($AuthGroup->addToModel($gid, $mid)) {
            $this->success(L('_SUCCESS_OPERATE_'));
        } else {
            $this->error(L('_FAIL_OPERATE_'));
        }
    }

    public function addNode()
    {
        if (empty($this->auth_group)) {
            $this->assign('auth_group', array('title' => null, 'id' => null, 'description' => null, 'rules' => null,));//排除notice信息
        }
        if (IS_POST) {
            $Rule = D('AuthRule');
            $data = $Rule->create();
            if ($data) {
                if (intval($data['id']) == 0) {
                    $id = $Rule->add();
                } else {
                    $Rule->save($data);
                    $id = $data['id'];
                }

                if ($id) {
                    // S('DB_CONFIG_DATA',null);
                    //记录行为
                    $this->success(L('_SUCCESS_EDIT_'));
                } else {
                    $this->error(L('_EDIT_FAILED_'));
                }
            } else {
                $this->error($Rule->getError());
            }
        } else {
            $aId = I('id', 0, 'intval');
            if ($aId == 0) {
                $info['module']=I('module','','op_t');
            }else{
                $info = D('AuthRule')->find($aId);
            }

            $this->assign('info', $info);
            //  $this->assign('info', array('pid' => I('pid')));
            $modules = D('Common/Module')->getAll();
            $this->assign('Modules', $modules);
            $this->meta_title = L('_NEW_FRONT_DESK_RIGHT_NODE_');
            $this->display();
        }

    }

    public function deleteNode(){
        $aId=I('id',0,'intval');
        if($aId>0){
            $result=   M('AuthRule')->where(array('id'=>$aId))->delete();
            if($result){
                $this->success(L('_DELETE_SUCCESS_'));
            }else{
                $this->error(L('_DELETE_FAILED_'));
            }
        }else{
            $this->error(L('_YOU_MUST_SELECT_THE_NODE_'));
        }
    }
    /**
     * 访问授权页面
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function access()
    {
        $this->updateRules();
		$auth_group = M('AuthGroup')->where(array('module' => 'admin', 'type' => AuthGroupModel::TYPE_ADMIN))
            ->getfield('id,id,title,rules');
        
       
        $map = array('module' => 'admin', 'type' => AuthRuleModel::RULE_MAIN, 'status' => 1);
        $main_rules = M('AuthRule')->where($map)->getField('name,id');
        $map = array('module' => 'admin', 'type' => AuthRuleModel::RULE_URL, 'status' => 1);
        $child_rules = M('AuthRule')->where($map)->getField('name,id');

		if(!IS_ROOT){
			$nodes = M('Menu')->where("hide=0")->field('id,title,url,tip,pid')->order('sort asc')->select();
            foreach ($nodes as $key => $value) {
                if (stripos($value['url'], MODULE_NAME) !== 0) {
                    $nodes[$key]['url'] = MODULE_NAME . '/' . $value['url'];
                }
            }
			$node_list = $nodes;
			// dump($node_list);
			$group_ids = M('auth_group_access')->where("uid=".UID)->field('group_id')->select();
			
			foreach ($group_ids as $v) {
				$sruls=M('AuthGroup')->where("id=".$v['group_id'])->getField('rules');
				$alist .= $sruls;
			}
			$map['status'] = 1;
			$map['id'] =  array('in', explode(',',$alist));
			$map['type'] = AuthRuleModel::RULE_MAIN;
			$amgroup=M('AuthRule')->where($map)->getField('name,id');
			$map['type'] = AuthRuleModel::RULE_URL;
			$acgroup=M('AuthRule')->where($map)->getField('name,id');
			
			foreach($node_list as $k=>$v){
				if(!$amgroup[$v['url']]&&!$acgroup[$v['url']]){
					unset($node_list[$k]);
				}
				
			}
			
			$node_list = list_to_tree($node_list, $pk = 'id', $pid = 'pid', $child = 'operator', $root = 0);
			// dump($node_list);
            foreach ($node_list as $key => $value) {
                if (!empty($value['operator'])) {
                    $node_list[$key]['child'] = $value['operator'];
                    unset($node_list[$key]['operator']);
                }
            }
			
		}
		else{
			 $node_list = $this->returnNodes();
		}
        // dump($main_rules);  //主要的节点
        //dump($child_rules); //子节点
        // dump($node_list);   //节点数组
        //dump($auth_group);  //角色
        //die();
        // dump($auth_group[(int)$_GET['group_id']]);
       // dump($auth_group);
        $this->assign('main_rules', $main_rules);
        $this->assign('auth_rules', $child_rules);
        $this->assign('node_list', $node_list);
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $auth_group[(int)$_GET['group_id']]);
        
        $this->meta_title = L('_ACCESS_AUTHORIZATION_');
        $this->display('');
    }

    public function accessUser()
    {
        $aId = I('get.group_id', 0, 'intval');

        if (IS_POST) {
            $aId = I('id', 0, 'intval');
            $aOldRule = I('post.old_rules', '', 'text');
            $aRules = I('post.rules', array());
            $rules = $this->getMergedRules($aOldRule, $aRules);
            $authGroupModel = M('AuthGroup');
            $group = $authGroupModel->find($aId);
            $group['rules'] = $rules;
            $result = $authGroupModel->save($group);
            if ($result) {
                $this->success(L('_RIGHT_TO_SAVE_SUCCESS_'));
            } else {
                $this->error(L('_RIGHT_SAVE_FAILED_'));
            }

        }
        $this->updateRules();
        $auth_group = M('AuthGroup')->where(array('status' => array('egt', '0'), 'type' => AuthGroupModel::TYPE_ADMIN))
            ->getfield('id,id,title,rules');
        $node_list = $this->getNodeListFromModule(D('Common/Module')->getAll());

        //  $node_list   =M('AuthRule')->where(array('module'=>array('neq','admin'),'type'=>AuthRuleModel::RULE_URL,'status'=>1))->select();

        $map = array('module' => array('neq', 'admin'), 'type' => AuthRuleModel::RULE_MAIN, 'status' => 1);
        $main_rules = M('AuthRule')->where($map)->getField('name,id');
        $map = array('module' => array('neq', 'admin'), 'type' => AuthRuleModel::RULE_URL, 'status' => 1);
        $child_rules = M('AuthRule')->where($map)->getField('name,id');

        $group = M('AuthGroup')->find($aId);
        $this->assign('main_rules', $main_rules);
        $this->assign('auth_rules', $child_rules);
        $this->assign('node_list', $node_list);
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $group);

        $this->meta_title = L('_USER_FRONT_DESK_AUTHORIZATION_');
        $this->display('');
    }

    private function getMergedRules($oldRules, $rules, $isAdmin = 'neq')
    {
        $map = array('module' => array($isAdmin, 'admin'), 'status' => 1);
        $otherRules = M('AuthRule')->where($map)->field('id')->select();
        $oldRulesArray = explode(',', $oldRules);
        $otherRulesArray = getSubByKey($otherRules, 'id');

        //1.删除全部非Admin模块下的权限，排除老的权限的影响
        //2.合并新的规则
        foreach ($otherRulesArray as $key => $v) {
            if (in_array($v, $oldRulesArray)) {
                $key_search = array_search($v, $oldRulesArray);
                if ($key_search !== false)
                    array_splice($oldRulesArray, $key_search, 1);
            }
        }

        return str_replace(',,', ',', implode(',', array_unique(array_merge($oldRulesArray, $rules))));


    }

    //预处理规则，去掉未安装的模块
    public function getNodeListFromModule($modules)
    {
        $node_list = array();
        foreach ($modules as $module) {
            if ($module['is_setup']) {

                $node = array('name' => $module['name'], 'alias' => $module['alias']);
                $map = array('module' => $module['name'], 'type' => AuthRuleModel::RULE_URL, 'status' => 1);

                $node['child'] = M('AuthRule')->where($map)->select();
                $node_list[] = $node;
            }

        }
        return $node_list;
    }

	
    /**
     * 函数作用描述：权限属性列表
     * @date:2016-10-29 18:05
     * @author:duanmeihua
     */
    public function authCategory($page = 1, $r = 20){
        $map['status'] = array('egt', 0);
        
        $goodsList = M('jk_auth_cate')->where($map)->order('id')->page($page, $r)->select();
        $totalCount = M('jk_auth_cate')->where($map)->count();
        
        $builder = new AdminListBuilder();
        
        $builder->buttonNew(U('editManage'));
        
        $builder->title(L('用户属性'));
        $builder->meta_title = L('用户属性');
        $i=1;
        foreach ($goodsList as &$v){
        	$v['num']=$i;
        	$i++;
        }
        $builder->keyText('num', L('序号'))
        ->keyText('name', L('属性名称'))->keyText('info', L('备注'))
        ->keyStatus('status', L('状态'))
        ->keyDoActionEdit('editManage?id=###');
         
        $builder->data($goodsList);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    
    /**
     * 函数作用描述：权限分类列表
     * @date:2016-10-29 18:05
     * @author:duanmeihua
     */
    public function authAttr($page=1,$r=20) {
        $map['status'] = -2;
        
        $goodsList = M('auth_group')->where($map)->order('id')->page($page, $r)->select();
        
        $totalCount = M('auth_group')->where($map)->count();
        
        $builder = new AdminListBuilder();
        
        $builder->buttonNew(U('editAttr'));
        
        $builder->title(L('用户分类'));
        $builder->meta_title = L('用户分类');
        $i++;
        foreach ($goodsList as &$v){
        	$v['num']=$i;
        	$i++;
        }
        $builder->keyText('num', L('序号'))
       // $builder->keyId()
        ->keyText('title', L('分类名称'))->keyText('description', L('备注'))
        ->keyDoActionEdit('access?group_id=###','授权')
        ->keyDoActionEdit('editAttr?id=###');
//         U('AuthManager/access?group_name='.$vo['title'].'&group_id='.$vo['id'])
        $builder->data($goodsList);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    
    /**
     * 函数用途描述：编辑权限分类
     * @date: 2016年10月28日 上午10:04:24
     * @author: luojun
     * @param:
     * @return:
     */
    public function editAttr($id=0) {
        $isEdit = $id ? 1 : 0;
        //echo $isEdit;die;
        if (IS_POST) {
            
//         构造分类
            $_POST['module'] = 'admin';
            $_POST['type'] = AuthGroupModel::TYPE_ADMIN;
            $AuthGroup = D('AuthGroup');
            $data = $AuthGroup->create();
            if ($data) {

                if ($data['title'] == '' || $data['title'] == null) {
                    $this->error(L('请输入分类名').json_encode($data));
                }
                $map['title'] = $data['title'];
                $map['status'] = -2;
                if ($AuthGroup->where($map)->count()) {
                    $this->error(L('分类名冲突！'));
                }
                $data['status'] = -2;
                if ($isEdit) {
                
                    $rs = $AuthGroup->where('id='.$id)->save($data);
                    
                    //修改节点表的数据
                    if($rs){
                        $this->success(L('编辑成功') , U('authCategory'));
                    }
                    $this->error(L('编辑失败！') );
                } else {
                    //分类名存在验证
                
                    $rs = $AuthGroup->add($data);
                    if ($rs) {
                
                        $this->success(L('新增成功') , U('authAttr'));
                    } else {
                        $this->error(L('新增失败'));
                    }
                }
                
            }
        } else {
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? '编辑分类' : '新增分类');
            $builder->meta_title = $isEdit ?  '编辑分类' : '新增分类' ;
            
            $map['status'] = array('egt', 0);
            
            $cate_list = M('jk_auth_cate')->where($map)->order('id')->field('id,name')->select();
            
            $options = array_combine(array_column($cate_list, 'id'), array_column($cate_list, 'name'));
             
            $builder->keyId()->keyText('title','分类名称')
            ->keySelect('cate', L('选择分类属性'), '', $options)
            ->keyText('description', '备注');
    
            if ($isEdit) {
                //获取分类
                $map['status'] = -2;
                $map['id'] = $id;
                $find = M('auth_group')->where($map)->find();
            
                $builder->buttonSubmit(U('editAttr'));
          	    $builder->keyHidden('id', '');
          	    $builder->data($find);
                $builder->buttonBack();
                $builder->display();
            } else {
                $builder->buttonSubmit(U('editAttr'));
                $builder->buttonBack();
    
                $builder->display();
            }
        }
    }
    


}//class  结束
