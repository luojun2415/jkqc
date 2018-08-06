<?php

/**
 * 所属项目 金科质量管理系统.
 * 开发者: 李国军
 * 创建日期: 2016年9月20日
 * 版权所有 重庆艾锐森科技有限责任公司(www.irosn.com)
 */

namespace App\Controller;

use Think\Controller;
use User\Api\UserApi;


class JkProjectForAppController extends Controller
{

    function _initialize()
    {
        // parent::_initialize();
        $map = array();
        $map['status'] = 1;
        // 		$map['projectid']=array('in',$projects);
        $map['projectid'] = 535;
        $map['id'] = 535;
        file_put_contents('time.log', '332', FILE_APPEND);
        M('jk_floor')->where($map)->select();
        file_put_contents('time.log', $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] . $_SERVER["QUERY_STRING"] . "\r\n", FILE_APPEND);
    }

    /**
     * 函数用途描述：用户名+密码登陆
     * @date: 2016-10-20
     * @author: 李国军
     * @param: username：用户名，pwd：密码
     * @return: 用户ID
     */
    public function login($from = 1)
    {
        $res['status'] = 0;
        $username = $_POST['username'];
        $password = $_POST['password'];
        /* 调用UC登录接口登录 */
        $User = new UserApi;
        $uid = $User->login($username, $password, 1, $from);
// 	    $uid=200;
        if (0 < $uid) { //UC登录成功
            $Member = D('Member');
            $Member->login($uid);
            $res['status'] = 1;
            if ($uid > 0 && UID == 0) {
                define('UID', $uid);
            }
        } else {
            //echo '';
            //return ;
            $res['status'] = 2;
            echo json_encode($res);
            return;
        }

        $res['info'] = M('member')->where("status>0 AND uid=$uid")->find();
// 		$res['info'] = $uid;
        $myProjects = get_my_projects();
        $res['cate'] = get_my_auth($uid);
        if ($res['info']['now_proid'] != 0 && $res['cate'] == 1 && in_array($res['info']['now_proid'], $myProjects)) {
            $map['id'] = $res['info']['now_proid'];
        } else {
            $map['id'] = $myProjects[0];
        }


        $aProjectInfo = M('jk_project')->field('id,name,other_name,mapid,pid')->where($map)->find();
        $res['proInfo'] = $aProjectInfo;
        echo json_encode($res);
    }

    /**
     *函数用途描述：获取某个项目的楼栋信息
     * @date：2016年11月21日 下午4:33:38
     * @author：luoj
     * @param：
     * @return：
     **/
    public function floorInfo($proid = 0)
    {
        $map['status'] = 1;
        // 		$map['projectid']=array('in',$projects);
        $map['projectid'] = $proid;
        $gpid = M('jk_project')->where("id=$proid")->getField('pid');

        $aUsersList = get_groups_users($gpid);

        $map = array();
        $map['status'] = 1;
        // 		$map['projectid']=array('in',$projects);
        $map['projectid'] = $proid;
        $aOptionTree = D('JKProgram/JKProjectCategory')->getTree(0, 'id,title,sort,pid,cid', $map);
        //dump($aOptionTree);
        $aOptions = tree_to_listwx($aOptionTree, $child = '_');

        $ret['optionTree'] = $aOptionTree;
        $ret['optionList'] = $aOptions;

        $ret['userList'] = $aUsersList;
        //         $ret['projectindex'] = $aProjectindex;
        //         dump($ret);
        echo json_encode($ret);
    }

    /**
     *函数用途描述：获取初始项目信息
     * @date：2016年10月12日 下午2:27:18
     * @author：Administrator
     * @param：
     * @return：json：projectInfos 项目信息，projectTree 项目架构（多维数组）， projectList项目架构列表（一维数组）
     *                optionTree问题选项架构（多维数组）， optionList 问题选项列表（一维数组），userList 整改人列表
     **/
    public function info($uid = 320, $proid = 21)
    {
        if ($uid > 0 && UID == 0) {
            define('UID', $uid);
        }
        $map['status'] = 1;
        $projects = get_my_projects();
        if (!is_administrator($uid)) {
            $map['id'] = array('in', get_my_projects());
        }
        $aProjectInfo = M('jk_project')->field('id,name,other_name,mapid,pid')->where($map)->select();

        foreach ($aProjectInfo as &$aInfo) {
            $aInfo['mappath'] = coverIds2Path($aInfo['mapid']);
            $aPids[] = $aInfo['pid'];
//             dump($aInfo['mappath']);
        }
        $aProjectTree = D('JKProject/JKProjectCategory')->getTree(0, 'id,title,sort,pid');

        $aProjects = tree_to_listwx($aProjectTree, $child = '_');
        $map = array();
        $map['status'] = 1;
// 		$map['projectid']=array('in',$projects);
        $map['projectid'] = $proid;

        $aOptionTree = D('JKProgram/JKProjectCategory')->getTree(0, 'id,title,sort,pid,cid,imgid,house_img_id', $map, 'sort');

        //echo M()->getLastsql();
        //dump($aOptionTree);
        $aOptions = tree_to_listwx($aOptionTree, $child = '_');
        //获取同组用户列表
        $gpid = M('jk_project')->where("id=$proid")->getField('pid');

        //$aUsersList = get_groups_users($gpid);
        $aUsersList = get_all_groups_users();

        //用户单位
        $aUsers = M('auth_group_access')->where("uid=$uid")->field('group_id')->select();
        foreach ($aUsers as $v) {

            $aUserGroup[] = $v['group_id'];//当前用户对应用户组
        }

        $list = M('jk_survey_option')->where("STATUS > 0")->select();

        //实测项
        $surveyInfo = list_to_tree($list, 'id', 'pid', '_');
        $surveyList = tree_to_listwx($surveyInfo, $child = '_');
        //获取项目级别信息
// 		$aProjectindex = M('jk_project')->where("pid = 0 AND STATUS > 0")->order('create_time DESC')->select();

// 		组织架构
        $map = array(
            'status' => array(
                'gt',
                0
            ),
// 		    'cate'=>1,
// 		    'id' => array('in', $aPids),
        );
        $list = M('AuthGroup')->field('id,title,pid')
            ->where($map)
            ->select();

        $group_ids = M('auth_group_access')->where("uid=" . $uid)->field('group_id')->order('group_id')->select();
        $alist = array();
        $oldlist = array();
        $iflag = 0;
        foreach ($group_ids as $v) {
            $map = array('status' => array('gt', 0), 'id' => $v['group_id']);
            $glist = M('AuthGroup')->field('id,title,pid,cate')->where($map)->find();

            foreach ($oldlist as $value) {
                if ($value[$glist['id']]) {
                    $iflag = 1;
                }
            }
            if ($iflag) {
                $iflag = 0;
                continue;
            }
            $glistTem = list_to_tree($list, 'id', 'pid', '_', $v['group_id']);
            if ($glistTem) {
                $glist['_'] = $glistTem;
                // dump($glist);
            }
            $alist[] = $glist;
            $temlist = tree_to_listwx($alist, '_');
            $oldlist[] = $temlist;
        }


        //dump($list);
        $ret['projectInfos'] = $aProjectInfo;
        $ret['projectTree'] = $aProjectTree;
        $ret['projectList'] = $aProjects;

        $ret['optionTree'] = $aOptionTree;
        //dump($aOptionTree);
        $ret['optionList'] = $aOptions;

        $ret['surveyTree'] = $surveyInfo;
        $ret['surveyList'] = $surveyList;
        $ret['userGroup'] = $aUserGroup;
        $ret['userList'] = $aUsersList;
        $ret['nodeTree'] = $alist;
//         $ret['projectindex'] = $aProjectindex;
//         dump($ret);
        echo json_encode($ret);
    }

    //测试接口
    public function test()
    {
        $updateTime = microtimeStr();
        $ameasure = M('jk_measuring_tasks')->where("tid='1496748702504320'")->select();
        foreach ($ameasure as $k => $b) {
            $measuretid = $b['tid'];
            $measuret = M('jk_measuring_tasks')->where("tid='$measuretid'")->find();
            $b['updatetime'] = $updateTime;
            //重置进度和合格率
            $b['rate_1'] = $b['rate_2'] = $b['rate_3'] = "";
            $b['jindu_1'] = $b['jindu_2'] = $b['jindu_3'] = "";
            //计算各个单位的每个检查项对应的合格率和进度
            //将该任务的检查项分割为数组
            $option_arr = explode(",", $b['optionid']);
            $point_arr = explode(",", $b['pointnum']);
            //查询出所有属于该任务的测量数据
            $info = M("jk_check_point")->where("tid='$measuretid' and type=0")->select();

            //遍历该任务的检查项，分别计算对应合格率和进度
            $newarr = array();//用该数组来存储合格率和进度
            foreach ($option_arr as $kk => $option) {
                foreach ($info as $vv) {
                    if ($vv['level'] == 1) {//集团工程部
                        if ($vv['inspect'] == $option) {
                            //得到不合格点数和总测量点数
                            //echo $vv['nonum'].'--'.$vv['totalnum']."<br />";
                            $newarr[$kk]['nonum_1'] += $vv['nonum'];
                            $newarr[$kk]['totalnum_1'] += $vv['totalnum'];
                        }
                    } elseif ($vv['level'] == 2) {//监理单位
                        if ($vv['inspect'] == $option) {
                            //得到不合格点数和总测量点数
                            $newarr[$kk]['nonum_2'] += $vv['nonum'];
                            $newarr[$kk]['totalnum_2'] += $vv['totalnum'];
                        }
                    } elseif ($vv['level'] == 3) {//施工单位
                        if ($vv['inspect'] == $option) {
                            //得到不合格点数和总测量点数
                            $newarr[$kk]['nonum_3'] += $vv['nonum'];
                            $newarr[$kk]['totalnum_3'] += $vv['totalnum'];
                        }
                    }
                }
                //计算3个单位对应各个检查项合格率并保存
                $newarr[$kk]['rate_1'] = ($newarr[$kk]['totalnum_1'] - $newarr[$kk]['nonum_1']) * 100 / $newarr[$kk]['totalnum_1'];
                $newarr[$kk]['rate_2'] = ($newarr[$kk]['totalnum_2'] - $newarr[$kk]['nonum_2']) * 100 / $newarr[$kk]['totalnum_2'];
                $newarr[$kk]['rate_3'] = ($newarr[$kk]['totalnum_3'] - $newarr[$kk]['nonum_3']) * 100 / $newarr[$kk]['totalnum_3'];
                echo $newarr[$kk]['rate_1'] . '-';
                $newarr[$kk]['rate_1'] = round($newarr[$kk]['rate_1'], 2);
                $newarr[$kk]['rate_2'] = round($newarr[$kk]['rate_2'], 2);
                $newarr[$kk]['rate_3'] = round($newarr[$kk]['rate_3'], 2);
                echo $newarr[$kk]['rate_1'] . '<br />';
                $b['rate_1'] .= $newarr[$kk]['rate_1'] . ",";
                $b['rate_2'] .= $newarr[$kk]['rate_2'] . ",";
                $b['rate_3'] .= $newarr[$kk]['rate_3'] . ",";
                echo $b['rate_1'] . '<br />';
                //计算3个单位对应各个检查项进度并保存
                $newarr[$kk]['jindu_1'] = $newarr[$kk]['totalnum_1'] * 100 / $point_arr[$kk];
                $newarr[$kk]['jindu_2'] = $newarr[$kk]['totalnum_2'] * 100 / $point_arr[$kk];
                $newarr[$kk]['jindu_3'] = $newarr[$kk]['totalnum_3'] * 100 / $point_arr[$kk];
                //echo $newarr[$kk]['jindu_1'].'-'.$newarr[$kk]['rate_1'];
                $newarr[$kk]['jindu_1'] = round($newarr[$kk]['jindu_1'], 2);
                $newarr[$kk]['jindu_2'] = round($newarr[$kk]['jindu_2'], 2);
                $newarr[$kk]['jindu_3'] = round($newarr[$kk]['jindu_3'], 2);
                $b['jindu_1'] .= $newarr[$kk]['jindu_1'] . ",";
                $b['jindu_2'] .= $newarr[$kk]['jindu_2'] . ",";
                $b['jindu_3'] .= $newarr[$kk]['jindu_3'] . ",";

            }

            //去除最后一个，号
            $b['rate_1'] = substr($b['rate_1'], 0, strlen($b['rate_1']) - 1);
            $b['rate_2'] = substr($b['rate_2'], 0, strlen($b['rate_2']) - 1);
            $b['rate_3'] = substr($b['rate_3'], 0, strlen($b['rate_3']) - 1);
            $b['jindu_1'] = substr($b['jindu_1'], 0, strlen($b['jindu_1']) - 1);
            $b['jindu_2'] = substr($b['jindu_2'], 0, strlen($b['jindu_2']) - 1);
            $b['jindu_3'] = substr($b['jindu_3'], 0, strlen($b['jindu_3']) - 1);
            if ($measuret) {//更改时加上新上传的测量点数
                $b['rectification_num'] = (int)$measuret['rectification_num'] + (int)$b['rectification_num'];
                $b['surveyor_num'] = (int)$measuret['surveyor_num'] + (int)$b['surveyor_num'];
                $b['admin_num'] = (int)$measuret['admin_num'] + (int)$b['admin_num'];
                $totalnum = getSumPoint($b['pointnum']);
                $x = $b['surveyor_num'] * 100 / $totalnum;
                $y = $b['admin_num'] * 100 / $totalnum;
                $z = $b['rectification_num'] * 100 / $totalnum;
                //         			$b['surveyor_num'] = $b['surveyor_num']*100/$totalnum;
                //         			$b['admin_num'] = $b['admin_num']*100/$totalnum;
                //         			$b['rectification_num'] = $b['rectification_num']*100/$totalnum;
                //        			$b['status'] = $totalnum;
                //
                if ($x >= 70 && $y >= 30 && $z >= 100) {//整改单位100，监理单位70，
                    $b['status'] = 2;
                }


                $r = M('jk_measuring_tasks')->where("tid='$measuretid'")->save($b);

            } else {//新增
                $r = M('jk_measuring_tasks')->add($b);
            }

        }
    }

    /***
     * 上传分户验收离线数据
     *  $jupData json格式
     *   status=0：更新成功  ，info=更新成功
     *   status=-1：数据未完全更新，info=更新出错数据下标
     *   status=-2：参数错误，info=参数错误
     *  2018年6月20日
     *  yxch
     * */
    public function check_updataRT($jupData)
    {
        $timestamp = microtimeStr();
        $adata = json_decode($jupData, true);
        $rep['status'] = -1;
        $rep['info'] = 0;

        $check_tasks = $adata['check_tasks'];//任务信息
        $acprogramPoints = $adata['programPoints'];//问题点信息
        $check_rooms = $adata['check_rooms'];//任务信息房间检查状态
        file_put_contents('appRequst.log', $jupData . "\n", FILE_APPEND);
//        exit;
        //echo json_encode($adata);exit;

        //任务信息
        if ($check_tasks && is_array($check_tasks)) {
            $reTasks = '';
            $hasNew = 0;
            $check_taskslen = count($check_tasks);

            foreach ($check_tasks as $k => $c) {//循环上传的task数据

                //获取数据，查看要插入的数据是否已经存在

                $check_stage = $c['stage'];//阶段
                $position_id = $c['position_id'];//楼栋id
                $check_proid = $c['pro_id'];//项目id

                $taskdata = M('jk_task')->where("stage = '$check_stage' AND pro_id = '$check_proid' AND position_id = '$position_id'")->select();
                if ($taskdata) {
                    //存在数据
                    $reTasks .= $c['position_name'] . ' ';
                } else {//不存在数据，直接添加task
                    $hasNew = 1;
                    $result = M('jk_task')->add($c);//每条任务都是自己建的，直接进行提交,我们不进行操作
                    if ($result == false) {
                        $rep['sql2'] = M()->getLastsql();

                        $rep['info'] = '部分任务创建失败，请联系运维人员';
                        file_put_contents('err_log.log', $rep['info'] . "\n", FILE_APPEND);
                        file_put_contents('err_log.log', json_encode($check_tasks) . "\n", FILE_APPEND);
                        echo json_encode($rep);
                        exit;
                    }

                    //创建任务对应的房间检查信息
                    $rooms = getBuildsRooms($position_id, $check_proid);
                    if ($rooms) {
                        $roomCheckInfo = array();
                        $roomCheckInfo['project_id'] = $check_proid;
                        $roomCheckInfo['building_id'] = $position_id;
                        $roomCheckInfo['create_time'] = $timestamp;
                        $roomCheckInfo['last_modify_time'] = $timestamp;
                        $roomCheckInfo['task_id'] = $c['id'];

                        foreach ($rooms as $r) {
                            $roomCheckInfo['room_id'] = $r['id'];
                            M('jk_room_check_info')->add($roomCheckInfo);
                        }

                    }

                }
            }

            if ($hasNew) {//有新增数据
                $rep['sql'] = M()->getLastsql();
                $rep['status'] = 0;
                $rep['info'] = '任务创建成功';
                if ($reTasks) {
                    $rep['info'] .= '，但' . $reTasks . '的任务有重复';
                }
                echo json_encode($rep);
                exit;
            } else {
                $rep['info'] = '任务创建失败，任务重复';
                echo json_encode($rep);
                exit;
            }

            $rep['crsql'] = M()->getLastsql();
            //插入完毕,对比数据库和上传的数据，是否存在上传的数据有，但是数据库中没有的数据，获取这些数据的id
            $intasksdata = M('jk_task')->where("pro_id = '$check_proid'")->select();
            $intasksdatalen = count($intasksdata);
            for ($r = 0; $r < $check_taskslen; $r++) {
                $idinlocal = 0;//id在数据库存在
                $uptaskid = $check_tasks[$r]['id'];//上传的id
                for ($d = 0; $d < $intasksdatalen; $d++) {
                    $localtaskid = $intasksdata[$d]['id'];//数据库的id
                    if ($localtaskid == $uptaskid) {
                        $idinlocal = 1;
                        break;
                    }
                }
                if (!$idinlocal) {
                    $idtemp[] = $uptaskid;//存放不在数据库的id
                }
            }
            if (!$idtemp) {

            } else {
                $idtempids = implode(",", $idtemp);
                $rep['noindatabaseid'] = $idtempids;
            }
        }
        if ($acprogramPoints && is_array($acprogramPoints)) {
            foreach ($acprogramPoints as $k => $a) {

                $result = M('jk_acprogram_point')->add($a);

            }

        }
        if ($check_rooms && is_array($check_rooms)) {
            $taskId = '';
            $taskIds = array();
            $tmpTaskId = '';
            $check_proid = '';//项目id
            foreach ($check_rooms as $k => $a) {
                $check_proid = $a['project_id'];//项目id
                $taskId = $a['task_id'];
                if ($tmpTaskId != $taskId) {
                    $tmpTaskId = $taskId;
                    $taskIds[] = $tmpTaskId;
                }
                $result = M('jk_room_check_info')->where("id=" . $a['id'])->save($a);

            }
            foreach ($taskIds as $t) {
                $countStatusAll = M('jk_room_check_info')->where("task_id='$t'")->count();
                $countStatus2 = M('jk_room_check_info')->where("status=2 AND task_id='$t'")->count();
                if ($countStatus2 > 0)
                    $status = 1;
                if ($countStatusAll == $countStatus2) {
                    $status = 2;
                }

                $rate = round($countStatus2 * 100 / $countStatusAll);
                M('jk_task')->where("pro_id = '$check_proid' AND id ='$t'")->save(array('rate' => $rate, 'status' => $status));
            }
        }

        $rep['sql'] = M()->getLastsql();
        $rep['status'] = 0;
        $rep['info'] = $timestamp;
        echo json_encode($rep);
        exit;
    }


    /***
     * 上传分户验收离线数据
     *  $jupData json格式
     *   status=0：更新成功  ，info=更新成功
     *   status=-1：数据未完全更新，info=更新出错数据下标
     *   status=-2：参数错误，info=参数错误
     *  2017年6月8日17:43:39
     *  yxch
     * */
    public function check_updata($jupData)
    {
        $updateTime = date("Y-m-d H:i:s", time());//yyyy-mm-dd hh:mm:ss
        $timestamp = microtimeStr();
        $adata = json_decode($jupData, true);
        $rep['status'] = -1;
        $rep['info'] = 0;

        $acprogram = $adata['program'];//问题信息
        $acprogram_image = $adata['program_image'];//问题照片信息
        $check_tasks = $adata['check_tasks'];//任务信息
        $check_taskinfo = $adata['check_taskinfo'];//任务范围信息
        $check_comment = $adata['check_comment'];//任务回复
        //echo json_encode($adata);exit;
        //问题信息
        if ($acprogram && is_array($acprogram)) {
            $rep['program'] = $acprogram;
            foreach ($acprogram as $k => $a) {
                $acprogramid = $a['problem_id'];//id
                //$a['last_modify_time'] = $updateTime;
                $acprogramt = M("jk_acprogram")->where("problem_id = '$acprogramid'")->find();
                if ($acprogramt) {//若是存在，进行更新
                    $save['problem_status'] = $a['problem_status'];

                    if ($a['problem_status'] == 8) {
                        $save['remark'] = 1;
                        if ($acprogramt['remark'] == 1) {
                            $save['problem_status'] = 5;//工程部第二次不整改时，进行作废
                        }
                    }
                    $save['last_modify_time'] = $a['last_modify_time'];
                    $result = M('jk_acprogram')->where("problem_id = '$acprogramid'")->save($save);
                } else {//不存在进行添加
                    //$a['create_time'] = $updateTime;
                    $result = M('jk_acprogram')->add($a);

                }
                $rep['sql'] = M()->getLastsql();
//                 if($result==false){
//                     $rep['info'] = $k;
//                     echo json_encode($rep);
//                     exit;
//                 }
            }
        }
        //任务回复
        if ($check_comment && is_array($check_comment)) {
            foreach ($check_comment as $k => $e) {
                $id = $e['id'];//id
                //$b['last_modify_time'] = $updateTime;
                $check_commenthd = M("jk_comment")->where("id = '$id'")->find();
                if ($check_commenthd) {//若是存在，进行更新
                    //$result=M('jk_comment')->where("id = '$id'")->save($e);
                } else {//不存在进行添加
                    //$b['create_time'] = $updateTime;
                    $result = M('jk_comment')->add($e);
                }
                $rep['sql'] = M()->getLastsql();
                if ($result == false) {
                    $rep['info'] = $k;
                    echo json_encode($rep);
                    exit;
                }
            }
        }
        //问题照片信息
        if ($acprogram_image && is_array($acprogram_image)) {
            foreach ($acprogram_image as $k => $b) {
                $photoid = $b['photo_id'];//id
                //$b['last_modify_time'] = $updateTime;
                $acprogram_imaget = M("jk_acprogram_image")->where("photo_id = '$photoid'")->find();
                if ($acprogram_imaget) {//若是存在，进行更新
                    //$result=M('jk_acprogram_image')->where("photo_id = '$photoid'")->save($b);
                } else {//不存在进行添加
                    //$b['create_time'] = $updateTime;

                    $result = M('jk_acprogram_image')->add($b);
                }
                if ($result == false) {
                    $rep['info'] = $k;
                    echo json_encode($rep);
                    exit;
                }
            }
        }
        //任务信息
        if ($check_tasks && is_array($check_tasks) && $check_taskinfo && is_array($check_taskinfo)) {
            $check_taskinfolen = count($check_taskinfo);
            $check_taskslen = count($check_tasks);
            //获取项目id
            $check_proid = $check_tasks[0]['pro_id'];

            foreach ($check_tasks as $k => $c) {//循环上传的task数据
                $check_tasksid = $c['id'];
                $check_taskshd = M('jk_task')->where("id = '$check_tasksid'")->find();
                if (!$check_taskshd) {//没有插入过数据库
                    //获取数据，查看要插入的数据是否已经存在
                    $addtaskinfo = 0;
                    $check_stage = $c['stage'];//阶段
                    $position_id = $c['position_id'];//楼栋id
                    //$check_proid = $c['pro_id'];//项目id
                    $taskdata = M('jk_task')->where("stage = '$check_stage' AND pro_id = '$check_proid' AND position_id = '$position_id'")->select();
                    if ($taskdata) {
                        //存在数据
                        $taskdatalen = count($taskdata);
                        for ($q = 0; $q < $taskdatalen; $q++) {//循环数据库task数据
                            $taskid = $taskdata[$q]['id'];
                            $taskinfodata = M('jk_taskinfo')->where("task_id = '$taskid'")->select();//查找taskinfo的数据
                            $taskinfodatalen = count($taskinfodata);
                            for ($e = 0; $e < $check_taskinfolen; $e++) {//循环上传taskinfo数据
                                if ($check_taskinfo[$e]['task_id'] == $check_tasksid) {//找到上传的是本次循环用到的数据
                                    $isexist = 0;
                                    $hadeq = 1;
                                    $check_taskinfopositionid = $check_taskinfo[$e]['position_id'];
                                    //循环数据库taskinfo数据，查看position是否存在相同的
                                    for ($w = 0; $w < $taskinfodatalen; $w++) {
                                        $taskinfo_positionid = $taskinfodata[$w]['position_id'];
                                        //和传递过来的值进行对比，存在一样的跳出本循环
                                        if ($check_taskinfopositionid == $taskinfo_positionid) {
                                            $isexist = 1;
                                            break;
                                        }
                                    }
                                    if (!$isexist) {//该条taskinfo数据没有创建过，进行添加
                                        $tinfo['id'] = $check_taskinfo[$e]['id'];
                                        $tinfo['task_id'] = $check_taskinfo[$e]['task_id'];
                                        $tinfo['position_type'] = $check_taskinfo[$e]['position_type'];
                                        $tinfo['position_id'] = $check_taskinfo[$e]['position_id'];
                                        $tinfo['state'] = 'normal';
                                        $tinfo['create_time'] = $check_taskinfo[$e]['create_time'];
                                        $tinfo['update_time'] = $check_taskinfo[$e]['update_time'];
                                        $tinfo['position_name'] = $check_taskinfo[$e]['position_name'];
                                        $result1 = M('jk_taskinfo')->add($tinfo);
                                        if ($result1) {
                                            $addtaskinfo = 1;
                                        }
                                    }

                                } else {
                                }
                            }
                            if ($addtaskinfo > 0) {
                                //不存在进行插入
                                $result = M('jk_task')->add($c);//每条任务都是自己建的，直接进行提交,我们不进行操作
                                if ($result == false) {
                                    $rep['sql'] = M()->getLastsql();
                                    $rep['info'] = $k;
                                    echo json_encode($rep);
                                    exit;
                                }
                            }
                        }


                    } else {//不存在数据，直接添加task和taskinfo数据
                        $result = M('jk_task')->add($c);//每条任务都是自己建的，直接进行提交,我们不进行操作
                        if ($result == false) {
                            $rep['sql2'] = M()->getLastsql();
                            $rep['info'] = $k;
                            echo json_encode($rep);
                            exit;
                        } else {//添加成功，添加taskinfo数据
                            for ($e = 0; $e < $check_taskinfolen; $e++) {//循环上传taskinfo数据
                                if ($check_taskinfo[$e]['task_id'] == $check_tasksid) {//找到上传的是本次循环用到的数据
                                    //该条taskinfo数据没有创建过，进行添加
                                    $tinfo['id'] = $check_taskinfo[$e]['id'];
                                    $tinfo['task_id'] = $check_taskinfo[$e]['task_id'];
                                    $tinfo['position_type'] = $check_taskinfo[$e]['position_type'];
                                    $tinfo['position_id'] = $check_taskinfo[$e]['position_id'];
                                    $tinfo['state'] = 'normal';
                                    $tinfo['create_time'] = $check_taskinfo[$e]['create_time'];
                                    $tinfo['update_time'] = $check_taskinfo[$e]['update_time'];
                                    $tinfo['position_name'] = $check_taskinfo[$e]['position_name'];
                                    $result1 = M('jk_taskinfo')->add($tinfo);
                                }
                            }
                        }
                    }

                }

            }
            $rep['crsql'] = M()->getLastsql();
            //插入完毕,对比数据库和上传的数据，是否存在上传的数据有，但是数据库中没有的数据，获取这些数据的id
            $intasksdata = M('jk_task')->where("pro_id = '$check_proid'")->select();
            $intasksdatalen = count($intasksdata);
            for ($r = 0; $r < $check_taskslen; $r++) {
                $idinlocal = 0;//id在数据库存在
                $uptaskid = $check_tasks[$r]['id'];//上传的id
                for ($d = 0; $d < $intasksdatalen; $d++) {
                    $localtaskid = $intasksdata[$d]['id'];//数据库的id
                    if ($localtaskid == $uptaskid) {
                        $idinlocal = 1;
                        break;
                    }
                }
                if (!$idinlocal) {
                    $idtemp[] = $uptaskid;//存放不在数据库的id
                }
            }
            if (!$idtemp) {

            } else {
                $idtempids = implode(",", $idtemp);
                $rep['noindatabaseid'] = $idtempids;
            }
        }
        //任务范围信息
//         if($check_taskinfo && is_array($check_taskinfo)){
//             $rep['check_taskinfo'] = $check_taskinfo;
//             foreach ($check_taskinfo as $k=>$d){
//                 $check_taskinfoid = $d['id'];
//                 $d['state'] = 'normal';
//                 //$d['update_time'] = $updateTime;
//                 $check_taskinfohd = M('jk_taskinfo')->where("id = '$check_taskinfoid'")->find();
//                 if(!$check_taskinfohd)
//                     $result=M('jk_taskinfo')->add($d);//每条任务范围都是自己建的，直接进行提交,我们不进行操作
//                 if($result==false){
//                     $rep['sql'] = M()->getLastsql();
//                     $rep['info'] = $k;
//                     echo json_encode($rep);
//                     exit;
//                 }
//             }
//         }
        $rep['sql'] = M()->getLastsql();
        $rep['status'] = 0;
        $rep['info'] = $timestamp;
        echo json_encode($rep);
        exit;
    }

    public function testPush()
    {
        dump('tsetPush');
        $push = new JpushController();
        $registrationids = array('141fe1da9e8c752bc4c');
        $ret = $push->send_pub($registrationids, "您有新的整改任务，请更新确认");
        dump($ret);
    }

    /**
     *函数用途描述：获取巡查问题统计数据详情
     * @date：2018年6月13日 上午9:59:16
     * @author：luoj
     * @param： $proID 统计项目id，$type 数据类型
     * @return：status 操作结果；info 结果说明。
     *        status=0：更新成功  ，info=更新成功
     *        status=-1：数据未完全更新，info=更新出错数据下标
     *        status=-2：参数错误，info=参数错误
     **/
    public function getRCDetail($proID, $type = '', $page = 0, $count = 10, $filtr = '')
    {
        if ($proID <= 0 || !is_numeric($proID)) {
            $rep['status'] = -2;
            $rep['info'] = '参数错误';
            echo json_encode($rep);
            exit;
        }
        $db = M('jk_program');

//        当前时间戳
        $today = time() . '000';

        if ($type > '') {
            $where = "1=1 ";

            switch ($type) {
                case 'all':
                    break;
                case 'status0':
                    $where .= " AND status=0 ";
                    break;
                case 'status1':
                    $where .= " AND (status=1 OR status=3 ) ";
                    break;
                case 'status2':
                    $where .= " AND (status=2 OR status=4 ) ";
                    break;
                case 'new':
                    $today = strtotime(date("Y-m-d"), time());
                    $today = $today . '000';
                    $where .= " AND create_time>$today ";
                    break;
                case 'no_wait'://超期未完成
                    $where .= " AND type=0 AND (status=0 OR status=2) AND (limit_time*1000*24*3600+create_time)<$today ";
                    break;
                case 'yes_wait'://未超期未完成
                    $where .= " AND type=0 AND (status=0 OR status=2) AND (limit_time*1000*24*3600+create_time)>$today ";
                    break;
                case 'yes_over'://按时完成
                    $where .= " AND type=0 AND (status=1 OR status=3) AND (limit_time*1000*24*3600+create_time)>update_time ";
                    break;
                case 'no_over'://超期完成
                    $where .= " AND type=0 AND (status=1 OR status=3) AND (limit_time*1000*24*3600+create_time)<update_time ";
                    break;
                default:
                    break;

            }
            $flitr = json_decode($filtr, true);
            if (3 == $flitr['level']) {
                $uid = $flitr['uid'];
                if ($flitr['tids'] > '') {
                    $tids = $flitr['tids'];
                    $where .= " AND (authid=$uid OR target_id IN ($tids))";
                } else {
                    $where .= " AND authid=$uid ";
                }
            }

            $aproblem = $db->where("$where AND ownid=$proID")->limit($page * $count, $count)->order('create_time DESC')->select();
            $totelCount = $db->where("$where AND ownid=$proID")->count();
//            构造需要下载的其他数据
            $target_id = "";
            $where = "proID = $proID";
            foreach ($aproblem as $v) {
                $target_id .= $v['init_id'] . ",";
            }
            $target_id = substr($target_id, 0, strlen($target_id) - 1);
            if ($target_id != "" && $target_id != null) {
                $where .= " AND problem_id in(" . $target_id . ")";
                //          同时构造需要下载的问题回复数据
                $boardlist = M('jk_problm_board')->where($where)->select();
//                问题图片和问题回复图片

                foreach ($boardlist as $vv) {
                    $target_id .= ",";
                    $target_id .= $vv['boardid'];
                }
                $where = "";
                $target_id = substr($target_id, 0, strlen($target_id) - 1);
                if ($target_id != "" && $target_id != null)
                    $where = " AND target_id in(" . $target_id . ")";

                $aImagesdata = M('picture')->where("path !='' AND type!='detail' AND projectid = $proID" . $where)->select();
                $sql = M('picture')->_sql();
                $rep['boarddata'] = $boardlist;
                $rep['imagedata'] = $aImagesdata;
            }
            $rep['status'] = 0;
            $rep['sql'] = $sql;
            $rep['data'] = $aproblem;
            $rep['count'] = $totelCount;
            echo json_encode($rep);
            exit;
        } else {

//        超期未完成
            $num1 = $db->where("ownid=$proID AND type=0 AND (status=0 OR status=2) AND (limit_time*1000*24*3600+create_time)<$today")
                ->getField('COUNT(1) AS num');
            $sql = $db->_sql();

//        未超期未完成
            $num2 = $db->where("ownid=$proID AND type=0 AND (status=0 OR status=2) AND (limit_time*1000*24*3600+create_time)>$today")
                ->getField('COUNT(1) AS num');
            $sql = $db->_sql();

//        按时完成
            $num3 = $db->where("ownid=$proID AND type=0 AND (status=1 OR status=3) AND (limit_time*1000*24*3600+create_time)>update_time")
                ->getField('COUNT(1) AS num');

//        超期完成
            $num4 = $db->where("ownid=$proID AND type=0 AND (status=1 OR status=3) AND (limit_time*1000*24*3600+create_time)<update_time")
                ->getField('COUNT(1) AS num');

            $adata['count1'] = $num1;
            $adata['count2'] = $num2;
            $adata['count3'] = $num3;
            $adata['count4'] = $num4;
            $adata['total'] = $num1 + $num2 + $num3 + $num4;
            $rep['status'] = 0;
            $rep['sql'] = $sql;
            $rep['info'] = $adata;
            echo json_encode($rep);
            exit;
        }

    }

    /**
     *函数用途描述：获取实测检查项统计数据详情
     * @date：2018年6月13日 上午9:59:16
     * @author：luoj
     * @param： $inspeID 实测检查项id，$proID 统计项目id
     * @return：status 操作结果；info 结果说明。
     *        status=0：更新成功  ，info=更新成功
     *        status=-1：数据未完全更新，info=更新出错数据下标
     *        status=-2：参数错误，info=参数错误
     **/
    public function getSCDetail($proID, $inspeID)
    {
        if ($proID <= 0 || !is_numeric($proID) || $inspeID <= 0 || !is_numeric($inspeID)) {
            $rep['status'] = -2;
            $rep['info'] = '参数错误';
            echo json_encode($rep);
            exit;
        }
        $db = M('jk_rate');
        $adata = $db->where("projectid=$proID AND inspect=$inspeID")->field('rate,level')->select();
        $sql = $db->_sql();
        $rep['status'] = 0;
        $rep['sql'] = $sql;
        $rep['info'] = $adata;
        echo json_encode($rep);
        exit;
    }

    /**
     *函数用途描述：获取统计数据详情
     * @date：2018年6月13日 上午9:59:16
     * @author：luoj
     * @param： $type 统计类型，$proID 统计项目id
     * @return：status 操作结果；info 结果说明。
     *        status=0：更新成功  ，info=更新成功
     *        status=-1：数据未完全更新，info=更新出错数据下标
     *        status=-2：参数错误，info=参数错误
     **/
    public function getStatisticsDetail($type = 'sc', $proID = '')
    {
        if ($type == 'rc') {
            $db = M('jk_option');
            $pids = $db->where("pid=0 AND status=1")->getField('id', true);
            foreach ($pids as $pid) {
                $ids[$pid] = get_stemmaEX($pid, $db);
//                dump(count($ids[$pid]));
                if (!count($ids[$pid])) {
                    $ids[$pid][] = $pid;
//                    dump(($ids[$pid]));
                } else {
                    $ids[$pid] = array_column($ids[$pid], 'id');
                }
            }
//            dump($ids);
            $db = M('jk_program');
            $where['ownid'] = $proID;
            $where['type'] = 0;
            foreach ($ids as $k => $v) {
                $where['option_id'] = array('IN', $v);
                $inum = $db->where($where)->getField('COUNT(1) AS num');
                $adata[$k]['count'] = $inum;
                $adata[$k]['title'] = M('jk_option')->where("id=$k")->getField('title');
            }

            $sql = $db->_sql();
        } else if ($type == 'sc') {
            $db = M('jk_survey_option');
            $ppids = $db->where("pid=0 AND status=1")->getField('id', true);
            foreach ($ppids as $ppid) {
                $ids = array();
                $pids = $db->where("pid=$ppid AND status=1")->getField('id', true);
                foreach ($pids as $pid) {
                    $ids[$pid] = get_stemmaEX($pid, $db);
//                dump(count($ids[$pid]));
                    if (!count($ids[$pid])) {
                        $ids[$pid][] = $pid;
//                    dump(($ids[$pid]));
                    } else {
                        $ids[$pid] = array_column($ids[$pid], 'id');
                    }
                }
                $tids[$ppid] = $ids;
            }
//            dump($tids);
//            exit;
            $db = M('jk_program');
            $where['type'] = 1;
            $where['ownid'] = $proID;
            foreach ($tids as $kk => $vv) {
                $alasttree = array();
                $itotal = 0;
                foreach ($vv as $k => $v) {
                    $where['option_id'] = array('IN', $v);
                    $inum = $db->where($where)->getField('COUNT(1) AS num');
                    $atree['count'] = $inum;
                    $atree['title'] = M('jk_survey_option')->where("id=$k")->getField('title');
//                    dump($alasttree[$k]['title']);
                    $itotal += $inum;
                    $alasttree[] = $atree;
                }
                $adata[$kk]['count'] = $itotal;
                $adata[$kk]['lasttree'] = $alasttree;
                $adata[$kk]['title'] = M('jk_survey_option')->where("id=$kk")->getField('title');
            }


            $sql = $db->_sql();
        }
//        $adata['sql'] = $sql;
        $rep['status'] = 0;
        $rep['info'] = $adata;
        echo json_encode($rep);
        exit;
    }

    /**
     *函数用途描述：获取统计数据
     * @date：2018年6月13日 上午9:59:16
     * @author：luoj
     * @param： $type 统计类型，$proID 统计项目id
     * @return：status 操作结果；info 结果说明。
     *        status=0：更新成功  ，info=更新成功
     *        status=-1：数据未完全更新，info=更新出错数据下标
     *        status=-2：参数错误，info=参数错误
     **/
    public function getStatistics($type = '', $proID = '')
    {
        if ($proID == '' || $proID <= 0 || !is_numeric($proID)) {
            $rep['status'] = -2;
            $rep['info'] = '参数错误';
            echo json_encode($rep);
            exit;
        }
        if ($type == '' || $type == 'general') {
            $today = strtotime(date("Y-m-d"), time());
            $today = $today . '000';
            $db = M('jk_program');

//            日常巡查问题
            $ainfo = $db->where("ownid=$proID AND type=0")->group('status')->getField('status,COUNT(1) AS NUM');
            $sql = $db->_sql();
//            dump($ainfo);

//            待整改日常巡查问题
            $adata['rcwaitNum'] = $ainfo[0];
            $adata['rcwaitNum'] = $adata['rcwaitNum'] > 0 ? $adata['rcwaitNum'] : 0;
//            审核中日常巡查问题
            $adata['rcreviewNum'] = $ainfo[2];
            $adata['rcreviewNum'] = $adata['rcreviewNum'] > 0 ? $adata['rcreviewNum'] : 0;
//            已销项日常巡查问题
            $adata['rccompleteNum'] = $ainfo[1] + $ainfo[3];
            $adata['rccompleteNum'] = $adata['rccompleteNum'] > 0 ? $adata['rccompleteNum'] : 0;

//            实测问题
            $ascinfo = $db->where("ownid=$proID AND type=1")->group('status')->getField('status,COUNT(1) AS NUM');
//            待整改实测问题
            $adata['scwaitNum'] = $ascinfo[0];
            $adata['scwaitNum'] = $adata['scwaitNum'] > 0 ? $adata['scwaitNum'] : 0;
//            审核中实测问题
            $adata['screviewNum'] = $ascinfo[2];
            $adata['screviewNum'] = $adata['screviewNum'] > 0 ? $adata['screviewNum'] : 0;
//            已销项实测问题
            $adata['sccompleteNum'] = $ascinfo[1] + $ascinfo[3];
            $adata['sccompleteNum'] = $adata['sccompleteNum'] > 0 ? $adata['sccompleteNum'] : 0;

//            新增问题
            $newProblemNum = $db->where("ownid=$proID AND create_time>$today")->getField('COUNT(1) AS NUM');
            $adata['newProblemNum'] = $newProblemNum > 0 ? $newProblemNum : 0;
//            待整改问题
            $adata['waitNum'] = $adata['rcwaitNum'] + $adata['scwaitNum'];
//            待审核问题
            $adata['reviewNum'] = $adata['rcreviewNum'] + $adata['screviewNum'];
//            已销项问题
            $adata['completeNum'] = $adata['rccompleteNum'] + $adata['sccompleteNum'];
//            所有问题总数
            $adata['totalProblemNum'] = $adata['waitNum'] + $adata['reviewNum'] + $adata['completeNum'];
            $adata['sql'] = $sql;
            $rep['status'] = 0;
            $rep['info'] = $adata;
            echo json_encode($rep);
            exit;
        }
    }

    /**
     *函数用途描述：上传离线数据
     * @date：2016年10月13日 上午8:59:16
     * @author：luoj
     * @param： $jupData json格式，$jupData.program本次上传的数据中的问题数据,最多1000条
     * @return：status 操作结果；info 结果说明。
     *        status=0：更新成功  ，info=更新成功
     *        status=-1：数据未完全更新，info=更新出错数据下标
     *        status=-2：参数错误，info=参数错误
     **/
    public function updata($jupData)
    {
        $push = new JpushController();
        $rep['status'] = -1;
        $rep['info'] = 0;
        $updateTime = microtimeStr();

        $adata = json_decode($jupData, true);
        $aprogram = $adata['program'];
        $aboard = $adata['board'];
        $ainspect = $adata['inspect'];
        $ameasure = $adata['measure'];
        //实测实量任务表
        if ($ameasure && is_array($ameasure)) {
            foreach ($ameasure as $k => $b) {
                $measuretid = $b['tid'];
                $measuret = M('jk_measuring_tasks')->where("tid='$measuretid'")->find();
                if ($measuret['updatetime'] == $b['updatetime']) {
                    continue;
                }
                $b['updatetime'] = $updateTime;
                //重置进度和合格率
                $b['rate_1'] = $b['rate_2'] = $b['rate_3'] = "";
                $b['jindu_1'] = $b['jindu_2'] = $b['jindu_3'] = "";
                //计算各个单位的每个检查项对应的合格率和进度
                //将该任务的检查项分割为数组
                $option_arr = explode(",", $b['optionid']);
                $point_arr = explode(",", $b['pointnum']);
                //查询出所有属于该任务的测量数据
                $info = M("jk_check_point")->where("tid='$measuretid' and type=0")->select();
                //遍历该任务的检查项，分别计算对应合格率和进度
                $newarr = array();//用该数组来存储合格率和进度
                $total_arr = array();
                foreach ($option_arr as $kk => $option) {
                    foreach ($info as $vv) {
                        if ($vv['level'] == 1) {//集团工程部
                            if ($vv['inspect'] == $option) {
                                //得到不合格点数和总测量点数
                                $newarr[$kk]['nonum_1'] += $vv['nonum'];
                                $newarr[$kk]['totalnum_1'] += $vv['totalnum'];
                                $total_arr['totalnum_1'] += $vv['totalnum'];
                            }
                        } elseif ($vv['level'] == 2) {//监理单位
                            if ($vv['inspect'] == $option) {
                                //得到不合格点数和总测量点数
                                $newarr[$kk]['nonum_2'] += $vv['nonum'];
                                $newarr[$kk]['totalnum_2'] += $vv['totalnum'];
                                $total_arr['totalnum_2'] += $vv['totalnum'];
                            }
                        } elseif ($vv['level'] == 3) {//施工单位
                            if ($vv['inspect'] == $option) {
                                //得到不合格点数和总测量点数
                                $newarr[$kk]['nonum_3'] += $vv['nonum'];
                                $newarr[$kk]['totalnum_3'] += $vv['totalnum'];
                                $total_arr['totalnum_3'] += $vv['totalnum'];
                            }
                        }
                    }
                    //计算3个单位对应各个检查项合格率并保存
                    $newarr[$kk]['rate_1'] = ($newarr[$kk]['totalnum_1'] - $newarr[$kk]['nonum_1']) * 100 / $newarr[$kk]['totalnum_1'];
                    $newarr[$kk]['rate_2'] = ($newarr[$kk]['totalnum_2'] - $newarr[$kk]['nonum_2']) * 100 / $newarr[$kk]['totalnum_2'];
                    $newarr[$kk]['rate_3'] = ($newarr[$kk]['totalnum_3'] - $newarr[$kk]['nonum_3']) * 100 / $newarr[$kk]['totalnum_3'];
                    $newarr[$kk]['rate_1'] = round($newarr[$kk]['rate_1'], 2);
                    $newarr[$kk]['rate_2'] = round($newarr[$kk]['rate_2'], 2);
                    $newarr[$kk]['rate_3'] = round($newarr[$kk]['rate_3'], 2);
                    $b['rate_1'] .= $newarr[$kk]['rate_1'] . ",";
                    $b['rate_2'] .= $newarr[$kk]['rate_2'] . ",";
                    $b['rate_3'] .= $newarr[$kk]['rate_3'] . ",";
                    //计算3个单位对应各个检查项进度并保存
                    $newarr[$kk]['jindu_1'] = $newarr[$kk]['totalnum_1'] * 100 / $point_arr[$kk];
                    $newarr[$kk]['jindu_2'] = $newarr[$kk]['totalnum_2'] * 100 / $point_arr[$kk];
                    $newarr[$kk]['jindu_3'] = $newarr[$kk]['totalnum_3'] * 100 / $point_arr[$kk];
                    $newarr[$kk]['jindu_1'] = round($newarr[$kk]['jindu_1'], 2);
                    $newarr[$kk]['jindu_2'] = round($newarr[$kk]['jindu_2'], 2);
                    $newarr[$kk]['jindu_3'] = round($newarr[$kk]['jindu_3'], 2);
                    $b['jindu_1'] .= $newarr[$kk]['jindu_1'] . ",";
                    $b['jindu_2'] .= $newarr[$kk]['jindu_2'] . ",";
                    $b['jindu_3'] .= $newarr[$kk]['jindu_3'] . ",";
                }
                //去除最后一个，号
                $b['rate_1'] = substr($b['rate_1'], 0, strlen($b['rate_1']) - 1);
                $b['rate_2'] = substr($b['rate_2'], 0, strlen($b['rate_2']) - 1);
                $b['rate_3'] = substr($b['rate_3'], 0, strlen($b['rate_3']) - 1);
                $b['jindu_1'] = substr($b['jindu_1'], 0, strlen($b['jindu_1']) - 1);
                $b['jindu_2'] = substr($b['jindu_2'], 0, strlen($b['jindu_2']) - 1);
                $b['jindu_3'] = substr($b['jindu_3'], 0, strlen($b['jindu_3']) - 1);
                if ($b['imgid'] == null) {
                    $arr = get_measure_id_url($b['projectid'], $b['pointid'], $b['optionid']);
                    $b['imgid'] = $arr['imgid'];
                    $b['imgurl'] = $arr['imgurl'];
                }
                if ($measuret) {//更改时加上新上传的测量点数
                    $b['rectification_num'] = (int)$measuret['rectification_num'] + (int)$b['rectification_num'];
                    $b['surveyor_num'] = (int)$measuret['surveyor_num'] + (int)$b['surveyor_num'];
                    $b['admin_num'] = (int)$measuret['admin_num'] + (int)$b['admin_num'];
                    $totalnum = getSumPoint($b['pointnum']);
                    //$x = $b['surveyor_num']*100/$totalnum;
                    //$y = $b['admin_num']*100/$totalnum;
                    //$z = $b['rectification_num']*100/$totalnum;
//         			$b['surveyor_num'] = $b['surveyor_num']*100/$totalnum;
//         			$b['admin_num'] = $b['admin_num']*100/$totalnum;
//         			$b['rectification_num'] = $b['rectification_num']*100/$totalnum;
//        			$b['status'] = $totalnum;
                    $x = $total_arr['totalnum_2'] * 100 / $totalnum;
                    $y = $total_arr['totalnum_1'] * 100 / $totalnum;
                    $z = $total_arr['totalnum_3'] * 100 / $totalnum;
//
                    if ($x >= 70 && $y >= 30 && $z >= 100) {//整改单位100，监理单位70，
                        $b['status'] = 2;
                    }

                    $r = M('jk_measuring_tasks')->where("tid='$measuretid'")->save($b);
                } else {//新增

                    $r = M('jk_measuring_tasks')->add($b);
                }
                $r = true;
                if ($r == false) {
                    $rep['info'] = $k;
                    echo json_encode($rep);
                    exit;
                }

            }
            $rep['status'] = 0;
            $rep['info'] = $updateTime;
            echo json_encode($rep);
            exit;
        }
        //实测实量
        if ($ainspect && is_array($ainspect)) {
            foreach ($ainspect as $k => $b) {
                $inspectid = $b['id'];
                $inspect = M('jk_check_point')->where("id='$inspectid'")->find();
                $b['update_time'] = $updateTime;
                //更新合格率表
                $where['projectid'] = $b['projectid'];
                $where['inspect'] = $b['inspect'];
                $where['level'] = $b['level'];
                $rate = M('jk_rate')->where($where)->find();
                if ($inspect) {
                    if ($rate) {
                        $rate1['update_time'] = time();//更新时间
                        $rate1['nonum'] = $rate['nonum'] + $b['nonum'] - $inspect['nonum'];//不合格点数和
                        $rate1['totalnum'] = $rate['totalnum'] + $b['totalnum'] - $inspect['totalnum'];//总测量点数和
                        $rate1['rate'] = $rate1['nonum'] / $rate1['totalnum'] * 100;//合格率
                        $rate1['rate'] = round($rate1['rate'], 2);
                        M('jk_rate')->where("id=" . $rate['id'] . "")->save($rate1);
                    } else {
                        $rate1['inspect'] = $b['inspect'];//检查项id
                        $rate1['projectid'] = $b['projectid'];//项目id
                        $rate1['name'] = M('jk_survey_option')->where("id=" . $b['inspect'])->getField('title');
                        $rate1['level'] = $b['level'];//用户权限
                        $rate1['create_time'] = time();//更新时间
                        $rate1['update_time'] = time();//更新时间
                        $rate1['nonum'] = $b['nonum'];//不合格点数和
                        $rate1['totalnum'] = $b['totalnum'];//总测量点数和
                        $rate1['rate'] = $b['nonum'] / $b['totalnum'] * 100;//合格率
                        $rate1['rate'] = round($rate1['rate'], 2);
                        M('jk_rate')->add($rate1);
                    }
                    $r = M('jk_check_point')->where("id='$inspectid'")->save($b);

                } else {
                    if ($rate) {
                        $rate1['update_time'] = time();//更新时间
                        $rate1['nonum'] = $rate['nonum'] + $b['nonum'];//不合格点数和
                        $rate1['totalnum'] = $rate['totalnum'] + $b['totalnum'];//总测量点数和
                        $rate1['rate'] = $rate1['nonum'] / $rate1['totalnum'] * 100;//合格率
                        $rate1['rate'] = round($rate1['rate'], 2);
                        M('jk_rate')->where("id=" . $rate['id'] . "")->save($rate1);
                    } else {
                        $rate1['inspect'] = $b['inspect'];//检查项id
                        $rate1['projectid'] = $b['projectid'];//项目id
                        $rate1['name'] = M('jk_survey_option')->where("id=" . $b['inspect'])->getField('title');
                        $rate1['level'] = $b['level'];//用户权限
                        $rate1['create_time'] = time();//更新时间
                        $rate1['update_time'] = time();//更新时间
                        $rate1['nonum'] = $b['nonum'];//不合格点数和
                        $rate1['totalnum'] = $b['totalnum'];//总测量点数和
                        $rate1['rate'] = $b['nonum'] / $b['totalnum'] * 100;//合格率
                        $rate1['rate'] = round($rate1['rate'], 2);
                        M('jk_rate')->add($rate1);
                    }
                    $r = M('jk_check_point')->add($b);
                    //更新合格率表
                    $rep['sql'] = M()->getLastSql();
                }
                if ($r == false) {
                    $rep['info'] = $k;
                    $rep['sql'] = "false:" . M()->getLastSql();
                    echo json_encode($rep);
                    exit;
                }

            }
            $rep['sql'] = "notaarray:" . M()->getLastSql();
            $rep['status'] = 123;
            $rep['info'] = $updateTime;
            echo json_encode($rep);
            exit;
        }
        if ($aboard && is_array($aboard)) {
            foreach ($aboard as $k => $b) {
                $boardid = $b['id'];

                $board = M('jk_problm_board')->where("boardid='$boardid'")->find();
                if ($board) {
                    if ($board['update_time'] == $b['create_time']) {
                        continue;
                    }
                    $b['update_time'] = $updateTime;
                    $b['boardid'] = $b['id'];
                    $b['images'] = "";
                    $r = M('jk_problm_board')->where("boardid='$boardid'")->save($b);
                    //dump("改变：".M('jk_problm_board')->getLastSql());
                } else {
                    $b['update_time'] = $b['create_time'] = $updateTime;
                    $b['boardid'] = $b['id'];
                    $b['images'] = "";
                    $r = M('jk_problm_board')->add($b);
                    //dump("新增：".M('jk_problm_board')->getLastSql());
                }
                if ($r == false) {
                    $rep['info'] = $k;
                    // file_put_contents('updata.txt','upsql:'.M('jk_program')->_sql()."\r\n", FILE_APPEND);
                    echo json_encode($rep);
                    exit;
                }

            }
            $rep['status'] = 0;
            $rep['info'] = $updateTime;
            echo json_encode($rep);
            exit;
        }

        if ($aprogram && is_array($aprogram)) {
            foreach ($aprogram as $k => $v) {
                $find = M('jk_program')->where("init_id='" . $v['init_id'] . "'")->find();

                if ($find) {
                    if ($find['update_time'] >= $v['update_time']) {
                        continue;
                    }
                    //如果状态更改才更新问题更新时间
                    if ($v['status'] != $find['status']) {
                        //如果是实测实量问题且更新状态为待整改
                        if ($v['type'] == 1) {
                            //如果是生成重复问题则不进行操作且不改变状态
                            if ($v['from_type'] && $v['from_type'] == '生成') {
                                $save = array();
                                $save['info'] = $v['info'];
                                if ($v['status'] == 1 || $v['status'] == 3) {
                                    $save['status'] = $v['status'];
                                    $save['update_time'] = $updateTime;
                                } elseif ($v['status'] == 2) {//如果修改状态为待审核
                                    if ($find['status'] != 0) {//如果为待复核或者已关闭状态

                                    } else {
                                        $save['status'] = $v['status'];
                                        $save['update_time'] = $updateTime;
                                    }
                                } elseif ($v['status'] == 4) {//如果为待复核
                                    if ($find['status'] == 1 || $find['status'] == 3) {//如果是已关闭的任务就不修改

                                    } else {
                                        $save['status'] = $v['status'];
                                        $save['update_time'] = $updateTime;
                                        $r = M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($v);
                                    }
                                }
                                $save['update_time'] = $updateTime;
                                M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($save);
                                $r = true;
                            } else {
                                if ($v['status'] == 0) {//如果状态为待整改
                                    if ($find['status'] == 1 || $find['status'] == 3) {//如果是已关闭的任务就不修改

                                    } else {
                                        $v['update_time'] = $updateTime;
                                        $r = M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($v);
                                    }
                                } elseif ($v['status'] == 2) {//如果修改状态为待审核
                                    if ($find['status'] != 0) {//如果为待复核或者已关闭状态

                                    } else {
                                        $v['update_time'] = $updateTime;
                                        $r = M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($v);
                                    }

                                } elseif ($v['status'] == 4) {//如果为待复核
                                    if ($find['status'] == 1 || $find['status'] == 3) {//如果是已关闭的任务就不修改

                                    } else {
                                        $v['update_time'] = $updateTime;
                                        $r = M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($v);
                                    }
                                } else {
                                    $v['update_time'] = $updateTime;
                                    $r = M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($v);
                                }

                            }
                        } else {
                            if ($find['status'] == 1 || $find['status'] == 3) {//如果是已关闭的任务就不修改

                            } else {
                                $v['update_time'] = $updateTime;
                                //如果是完成状态判断是否超期
                                if ($v['status'] == 1 || $v['status'] == 3) {
                                    $endtime = $find['create_time'] + 60 * 60 * 24 * 1000 * (int)$find['limit_time'];
                                    if ($v['update_time'] > $endtime) {
                                        $v['is_over'] = 1;
                                    }
                                }
                                $r = M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($v);
                            }

                        }

                    } else {
                        //去除问题更新时间
                        $r = true;
                        //unset($v['update_time']);
                    }

                } else {
                    //判断是否相同位置相同测量项已有测量问题
                    $v['update_time'] = $updateTime;
                    $v['create_time'] = $updateTime;
                    $r = M('jk_program')->add($v);
//                    $registrationids = getRidsFromGroupId($v['target_id']);

//                    file_put_contents('push.log', time() . '=================>170976fa8aa6345ae2f' . "\n", FILE_APPEND);
//                    $registrationids = array('170976fa8aa6345ae2f');
//                    $content = "您有新的整改任务，请更新确认";
//                    $ret = $push->send_pub($registrationids, $content, 'program', $v['type']);
//                    pushLog($registrationids, $content, 'program', $v['type'], $ret['code'], $ret['message'], $v['init_id']);
                }
                $r = true;
                if ($r == false) {
                    $rep['info'] = $k;
                    // file_put_contents('updata.txt','upsql:'.M('jk_program')->_sql()."\r\n", FILE_APPEND);
                    echo json_encode($rep);
                    exit;
                }

            }
            $rep['status'] = 0;
            $rep['info'] = $updateTime;
            echo json_encode($rep);
            exit;
        }

        $rep['status'] = -2;
        $rep['info'] = '参数错误';
        echo json_encode($rep);
        exit;
    }

    /**
     * 2016-11-08
     * 李国军
     * POST提交
     * old：原密码，new：新密码 uid：用户ID
     * 返回信息：
     * $res['status']：0 修改失败 1 修改成功
     * $res['info']：失败时带的信息，失败原因
     */
    public function updataPwd()
    {
        $res['status'] = 0;
        $oldpassword = I('post.old');//原密码用于验证
        $data['password'] = I('post.new');
        $uid = I('post.uid'); //用户ID
        $Api = new UserApi();
        $ret = $Api->updateInfo($uid, $oldpassword, $data);
        if ($ret['status']) {
            $res['status'] = 1;
        } else {
            $res['info'] = $ret['info'];
        }
        echo json_encode($res);
    }

    /**
     *
     * @param $uid 用户ID
     *        $phone 修改后的手机号
     *
     */
    public function updatePhone($uid)
    {
        $res['status'] = 0;
        $phone = I('post.phone');
        $ret = M('member')->where("uid = $uid")->setField('mobile', $phone);
        if ($ret !== false) {
            $res['status'] = 1;
            $res['info'] = "更改成功";
        } else {
            $res['info'] = "更成失败";
        }
        echo json_encode($res);
    }

    /**
     *
     * @param 用户ID ：$uid
     * @param 极光推送设备ID ：$registrationId
     * 绑定设备说明：
     * 该表用于向用户推送消息，
     * 1、设备号唯一，不能重复：
     * 2、一个用户可绑定多个设备号，对应情况，多个使用者登录同一账号;
     * 3、获取到统一设备中登录多个帐号，以最新UID为准
     */
    public function addJpushID($uid, $registrationId)
    {
        $res['status'] = 0;
        //先查询是已经存在该ID
        $where = "ename ='" . $registrationId . "'";
        $eid = M('jk_equipment')->where($where)->getField('id');
        $data['uid'] = $uid;
        if (0 < $eid) {//如果有就绑定为现在的用户
            $res['status'] = 1;
            M('jk_equipment')->where($where)->save($data);//做修改操作
            $res['info'] = "修改了绑定用户";
        } else {//之前不存在，新增设备
            $data['ename'] = $registrationId;
            $data['createtime'] = time();
            $add = M('jk_equipment')->data($data)->add();//做修改操作
            if ($add) {
                $res['status'] = 1;
                $res['info'] = "绑定成功";
            } else {
                $res['info'] = "绑定出错";
            }

        }
        echo json_encode($res);
    }

    /**
     * 函数用途描述：下载分户验收服务器数据
     * @date: 2018年7月9日 下午2:44:44
     * @author: luojun
     * @param: $time 上次更新时间，$offset 数据偏移，$count 获取数据条数，$type 要下载的数据类别 program=问题列表
     * @return: json格式数据 data 返回的数据，该类数据总共数量
     */
    public function check_loaddateRT($filtr = '', $count = 10, $type = 'check_images', $page = 10)
    {

        //获取前两个月的时间错
        $where = "state='normal' ";
        $flitr = json_decode($filtr, true);
        $page = $page < 0 ? 0 : $page;
        $count = $count < 1 ? 10 : $count;
        $count = $count > 100 ? 100 : $count;
//        file_put_contents('apptest.log', $flitr."\n");
        $proid = $flitr['proid'];
        if ($type == 'check_program') {//问题信息

            //            施工单位只查看对应施工单位绑定的楼栋的数据
            if (3 == $flitr['level']) {
                $uid = $flitr['uid'];
                if ($flitr['tids'] > '') {
                    $tids = $flitr['tids'];
                    $where .= " AND (contractor_id=$uid OR contractor_id IN ($tids))";
                } else {
                    $where .= " AND contractor_id=$uid ";
                }
            }

            $build_id = $flitr['building_id'];
            $option_pid = $flitr['check_item_id'];
            $status = $flitr['status'];

            $where .= " AND project_id=$proid ";
            if ($build_id > "") {
                $where .= " AND building_id IN ($build_id) ";
            }
            if ($option_pid > "") {
                $where .= " AND regional_id IN ($option_pid) ";
            }
            if (!($status === "")) {
                $where .= " AND problem_status IN ($status) ";
            }

            file_put_contents('apptest.log', $where);
            $data = M('jk_acprogram')->where($where)->order('create_time DESC')->limit($page * $count, $count)->select();
            $sql = M('jk_acprogram')->_sql();
            $totelCount = M('jk_acprogram')->where($where)->count();

//            构造需要下载的问题点、问题回复和图片数据
            $target_id = "";
            $where = "state='normal' ";
            foreach ($data as $v) {
                $target_id .= $v['problem_id'] . ",";
            }
            $target_id = substr($target_id, 0, strlen($target_id) - 1);
            if ($target_id != "" && $target_id != null) {
                $where2 = $where . " AND problem_id in(" . $target_id . ")";
                $where .= "AND project_id = $proid ";
                $where1 = $where . " AND problem_id in(" . $target_id . ")";
                $where .= " AND program_id in(" . $target_id . ")";
//                问题点数据
                $point_data = M('jk_acprogram_point')->where($where2)->select();
                $adownData['point_data'] = $point_data;
                //          问题回复数据
                $boardlist = M('jk_comment')->where($where)->select();

//                问题图片
                $problemImage = M('jk_acprogram_image')->where($where1)->select();
                $adownData['problemImage'] = $problemImage;
//                问题回复图片
                $target_id = "";
                foreach ($boardlist as $vv) {

                    $target_id .= $vv['id'] . ',';
                }
                $where = "state='normal' AND project_id = $proid ";
                $target_id = substr($target_id, 0, strlen($target_id) - 1);
                if ($target_id != "" && $target_id != null)
                    $where .= " AND reply_id in(" . $target_id . ")";

                $aImagesdata = M('jk_comment_etx')->where($where)->select();
                $sql = M('jk_comment_etx')->_sql();
                $adownData['commentData'] = $boardlist;
                $adownData['imageData'] = $aImagesdata;
            }

        }
        elseif ($type == 'check_tasks') {//任务信息
            $where="pro_id = $proid AND state='normal'";
            $position_id = $flitr['position_id'] ;
            if($position_id>""){
                $where .=" AND  position_id in ($position_id) " ;
            }

            $stage = $flitr['stage'] ;
            if($stage>""){
                $where .= " AND stage in ($stage)";
            }
            //获取task的数据
//            file_put_contents('apptest.log', time()."\n",FILE_APPEND);
            $taskdata = M('jk_task')->where("$where")->order('create_time DESC')->limit($page * $count, $count)->select();

            $totelCount = count($taskdata);
            //获取满足条件的jk_taskinfo数据
            $data = $taskdata;
            $target_id = "";
            $build_ids = '';
//            $where = "state='normal' ";
            foreach ($data as $v) {
                $target_id .= $v['id'] . ",";
                $build_ids .= $v['position_id'] . ',';
            }
            $target_id = substr($target_id, 0, strlen($target_id) - 1);
            $build_ids = substr($build_ids, 0, strlen($build_ids) - 1);
//            $where .= " AND task_id in(" . $target_id . ")";
//
//            $adownData['taskInfo'] = M('jk_taskinfo')->where($where)->select();

//              获取任务房间检查信息数据
            if ($target_id > '') {
                $roomsCheckInfo = M('jk_room_check_info')->where("project_id = $proid AND task_id in(" . $target_id . ")")->select();
                $adownData['roomsCheckData'] = $roomsCheckInfo;
//            file_put_contents('apptest.log', time()."\n",FILE_APPEND);
                $rooms = getBuildsRooms($build_ids, $proid);
                //获取任务对应房间的户型图数据
                $target_id = "";
                $tmpArr = array();
                foreach ($rooms as $v) {
                    $tmpArr[] = $v['house_img_id'];
                }
                $tmpArr = array_unique($tmpArr);//去重
                foreach ($tmpArr as $v) {
                    if ($v > '') {
                        $target_id .= $v . ",";
                    }
                }
                if ($target_id > '') {
                    $target_id = substr($target_id, 0, strlen($target_id) - 1);
                    $houseImages = M('picture')->where("id IN ($target_id)")->field('id,projectid,type,path,target_id,create_time')->select();
//                $adownData['houseImages'] = $houseImages;

//                获取对应房间的户型配置数据
                    $target_id = "";

                    foreach ($houseImages as $v) {
                        $target_id .= $v['id'] . ",";
                    }
                    $target_id = substr($target_id, 0, strlen($target_id) - 1);
                    $houseData = M('jk_housedata')->where("house_imageid IN ($target_id)")->field('id,title,points,house_imageid,pro_id,house_typeid')->select();
                    $adownData['houseData'] = $houseData;
                }
            }


            $sql = M()->getLastSql();
        }
//        elseif ($type == 'check_tasksInfo') {//任务信息
//            $taskId = $flitr['taskId'];
//            //获取task的数据
////            file_put_contents('apptest.log', time()."\n",FILE_APPEND);
//            $taskdata = M('jk_task')->where("pro_id = $proid AND state='normal' AND id='$taskId'")->order('create_time DESC')->find();
//
//            //获取满足条件的jk_taskinfo数据
//            $data = $taskdata;
//            $target_id = "";
//            $build_ids = $taskdata['position_id'];
//            $where = "state='normal' ";
//            foreach ($data as $v) {
//                $target_id .= $v['id'] . ",";
//                $build_ids .= $v['position_id'] . ',';
//            }
//            $target_id = substr($target_id, 0, strlen($target_id) - 1);
////            $build_ids = substr($build_ids, 0, strlen($build_ids) - 1);
////            $where .= " AND task_id in(" . $target_id . ")";
////
////            $adownData['taskInfo'] = M('jk_taskinfo')->where($where)->select();
//
////              获取任务房间检查信息数据
//            $roomsCheckInfo = M('jk_room_check_info')->where("project_id = $proid AND task_id ='$taskId''")->select();
//            $adownData['roomsCheckData'] = $roomsCheckInfo;
////            file_put_contents('apptest.log', time()."\n",FILE_APPEND);
//            $rooms = getBuildsRooms($build_ids, $proid);
//            //获取任务对应房间的户型图数据
//            $target_id = "";
//            $tmpArr = array();
//            foreach ($rooms as $v) {
//                $tmpArr[] = $v['house_img_id'];
//            }
//            $tmpArr = array_unique($tmpArr);//去重
//            foreach ($tmpArr as $v) {
//                if ($v > '') {
//                    $target_id .= $v . ",";
//                }
//            }
//            if ($target_id > '') {
//                $target_id = substr($target_id, 0, strlen($target_id) - 1);
//                $houseImages = M('picture')->where("id IN ($target_id)")->field('id,projectid,type,path,target_id,create_time')->select();
//                $adownData['houseImages'] = $houseImages;
//
////            获取对应房间的户型配置数据
//                $target_id = "";
//
//                foreach ($houseImages as $v) {
//                    $target_id .= $v['id'] . ",";
//                }
//                $target_id = substr($target_id, 0, strlen($target_id) - 1);
//                $houseData = M('jk_housedata')->where("house_imageid IN ($target_id)")->field('id,title,points,house_imageid,pro_id,house_typeid')->select();
//                $adownData['houseData'] = $houseData;
//            }
//
//            $sql = M()->getLastSql();
//        }
        $adownData['data'] = $data;
        $adownData['count'] = $totelCount;
        $adownData['sql'] = $sql;
        echo json_encode($adownData);
    }


    /**
     * 函数用途描述：根据获取的项目ID下载相应服务器数据
     * @date: 2016年10月15日 下午2:44:44
     * @author: luojun
     * @param: $time 上次更新时间，$offset 数据偏移，$count 获取数据条数，$type 要下载的数据类别 program=问题列表
     * @return: json格式数据 data 返回的数据，该类数据总共数量
     */
    public function check_loaddate($time = 0, $offset = 0, $count = 1000, $type = 'check_images', $uid = 320, $deviceId = '', $proID = 21)
    {
        $adownData['type'] = $type;
        $adownData['time'] = $time;
        if ($time != 0) {//时间为前一天的数据，防止未能获取完全数据
//             $time = strtotime($time) - 3600*24;
//             $time = date('Y-m-d H:i:s',$time);
            $time = date('Y-m-d H:i:s', strtotime("$time -1 day"));
        }
        //获取当前15天前的时间
        $halfmoomdata = date('Y-m-d H:i:s', strtotime("-15 day"));
        $tempids = '';
        if ($type == 'check_program') {//问题信息
            $data = M('jk_acprogram')->where("state='normal' AND last_modify_time>='" . $time . "' AND project_id = $proID")->limit($offset, $count)->select();
            $sql = M()->getLastSql();
            $totelCount = M('jk_acprogram')->where("state='normal' AND last_modify_time>='" . $time . "' AND project_id = $proID")->count();
            //问题回复信息下载
//             $commentdata = M("jk_comment")->where("state='normal' AND update_time>='".$time."' AND project_id = $proID")->limit($offset,$count)->select();
//             $commentcont = M("jk_comment")->where("state='normal' AND update_time>='".$time."' AND project_id = $proID")->count();
//             $adownData['commentdata'] = $commentdata;
//             $adownData['commentcont'] = $commentcont;
            //在这里同时更新问题点信息表
            $point_data = array();

            foreach ($data as $k => $v) {
                $temp = M('jk_acprogram_point')->where("state='normal' AND last_modify_time>='" . $time . "' AND problem_id = '{$v['problem_id']}'")->find();
                if ($temp != '') {
                    $point_data[] = $temp;
                }
                //获取问题的id，并且已合格和已作废的id不放入(不下载已合格和已作废的图片)
                if ($v['problem_status'] != 4 && $v['problem_status'] != 5) {
                    $tempid = $v['problem_id'];
                    $tempids .= $tempid . ",";
                    $acpdata[] = $v;////已合格和已作废的获取整个的data
                } else {//已合格和已作废的获取15天前的data
                    if ($v['create_time'] >= $halfmoomdata) {

                        //获取15天前的id数据===》改为要下载已合格和已作废的图片
                        $tempid = $v['problem_id'];
                        $tempids .= $tempid . ",";

                        $acpdata[] = $v;

                    }
                }
            }
            $data = $acpdata;//改data去除了已合格和已作废的15天的数据
            $totelCount = count($data);
            $tempids = substr($tempids, 0, strlen($tempids) - 1); //去除最后一个，
            $acprogramids = "(" . $tempids . ")";//以（）框起来的id

            $adownData['point_data'] = $point_data;
            //更新问题图片下载
            $adownData['acprogram_img'] = M('jk_acprogram_image')->where("state='normal' AND last_modify_time>='" . $time . "' AND problem_id in $acprogramids ")->limit($offset, $count)->select();;
            $sql = M()->getLastSql();
            $adownData['acprogram_img_count'] = M('jk_acprogram_image')->where("state='normal' AND last_modify_time>='" . $time . "'  AND problem_id in $acprogramids ")->count();;

            // echo M()->getLastSql();die;
        } elseif ($type == 'check_comment') {

            //获取没超过15天的已合格和已作废的数据和其他状态全部数据
            $acdata = M('jk_acprogram')->where("state='normal' AND project_id = $proID")->select();
            $temids = '';
            foreach ($acdata as $v) {
                if ($v['problem_status'] != 4 && $v['problem_status'] != 5) {
                    $temid = $v['problem_id'];
                } else {//已合格和已作废的获取15天前的data
                    if ($v['create_time'] >= $halfmoomdata) {
                        $temid = $v['problem_id'];
                    }
                }
                $temids .= $temid . ",";
            }
            $temids = substr($temids, 0, strlen($temids) - 1); //去除最后一个，
            $acprogids = "(" . $temids . ")";//以（）框起来的id

            $data = M('jk_comment')->where("state='normal' AND update_time>='" . $time . "' AND project_id = '$proID' AND program_id in $acprogids")->limit($offset, $count)->select();
            $sql = M()->getLastSql();
            $totelCount = M('jk_comment')->where("state='normal' AND update_time>='" . $time . "' AND project_id = '$proID' AND program_id in $acprogids")->count();
        } elseif ($type == 'check_images') {//回复照片信息
            //不获取已合格和作废的信息
            $acprodata = M('jk_acprogram')->where("state='normal' AND last_modify_time>='" . $time . "' AND project_id = $proID AND problem_status != 4 AND problem_status != 5 ")->select();
            //echo M()->getLastsql();
            //====》修改为获取15天内的已合格和已作废的图片
            $halfmoonacprodata = M('jk_acprogram')->where("state='normal' AND last_modify_time>='" . $time . "' AND last_modify_time >= '$halfmoomdata' AND project_id = $proID AND ( problem_status = 4 OR problem_status = 5 ) ")->select();
            //数组合并
            $acprodata = array_merge($acprodata, $halfmoonacprodata);
            //echo M()->getLastsql();

            foreach ($acprodata as $k => $v) {//获取所有jk_acprogram的problem_id
                $tempid = $v['problem_id'];
                $tempids .= $tempid . ",";
            }
            $tempids = substr($tempids, 0, strlen($tempids) - 1); //去除最后一个，
            $acprogramids = "(" . $tempids . ")";//以（）框起来的id

            $data = M('jk_comment_etx')->where("update_time>='" . $time . "' AND project_id = '$proID' AND program_id in $acprogramids ")->limit($offset, $count)->select();
            $sql = M()->getLastSql();
            $totelCount = M('jk_comment_etx')->where("update_time>='" . $time . "' AND project_id = '$proID' AND program_id in $acprogramids ")->count();

        } elseif ($type == 'check_tasks') {//任务信息
            /**
             * 找到最后更新后的数据，算出进度和改变状态
             * 目前是选择楼栋，所以是仅仅是根据楼栋来进行的操作
             * 先改变状态，再把值传回去
             * */
            //更新的数据并且状态>2的数据
            $roomtempdata = M('jk_room_accept')->where("last_modify_time>='" . $time . "' AND project_id = '$proID' AND accept_status > '2'")->order('last_modify_time desc')->group('building_id')->select();
            $roomtempdatalen = count($roomtempdata);
            //获取task的数据
            $taskdata = M('jk_task')->where("pro_id = '$proID' AND update_time>='" . $time . "'")->select();
            $taskdatalen = count($taskdata);

            for ($q = 0; $q < $roomtempdatalen; $q++) {
                $roombuildid = $roomtempdata[$q]['building_id'];
                $roomstage = $roomtempdata[$q]['stage'];
                $roomupdata_time = $roomtempdata[$q]['last_modify_time'];
                for ($w = 0; $w < $taskdatalen; $w++) {
                    $needchange = 0;
                    $taskstage = $taskdata[$w]['stage'];
                    if ($taskstage == $roomstage) {//阶段相等
                        $taskid = $taskdata[$w]['id'];
                        //获取taskinfo的数据
                        $taskinfodata = M('jk_taskinfo')->where("task_id = '$taskid' AND position_type = 'building'")->select();
                        $taskinfodatalen = count($taskinfodata);
                        for ($e = 0; $e < $taskinfodatalen; $e++) {
                            if ($taskinfodata[$e]['position_id'] == $roombuildid) {
                                $needchange = 1;
                                break; //相等后，后面可以不进行循环了，跳出该循环
                            }
                        }
                    }
                    if ($needchange) {//需要改变task的数据
                        $cg['status'] = 1;
                        $countroom = M('jk_room_tmp')->where("build_id = '$roombuildid'")->count();
                        $countroomaccept = M('jk_room_accept')->where("project_id = '$proID' AND building_id = '$roombuildid' AND accept_status > '2' AND stage='$taskstage'")->count();
                        $ratetemp = $countroomaccept / $countroom;
                        $ratetemp = number_format($ratetemp, 2, '.', '');//保留2为小数
                        $cg['rate'] = $ratetemp;
                        $cg['update_time'] = $roomupdata_time;
                        //进行保存数据，保存后跳出该循环
                        M('jk_task')->where("id = '$taskid'")->save($cg);
                        break;
                    }
                }

            }
            //获取满足条件的jk_task数据
            $data = M('jk_task')->where("update_time>='" . $time . "' AND pro_id = '$proID'")->select();
            $sql = M()->getLastSql();
            $totelCount = M('jk_task')->where("update_time>='" . $time . "' AND pro_id = '$proID'")->count();
        } elseif ($type == 'changetaskstatus') {//更新任务状态

        } elseif ($type == 'check_taskinfo') {//任务范围信息
            $data = M('jk_taskinfo')->where("update_time>='" . $time . "'")->select();
            $sql = M()->getLastSql();
            $totelCount = M('jk_taskinfo')->where("update_time>='" . $time . "'")->count();
        } elseif ($type == 'jk_house_image') { //户型图表更新
            $data = M('jk_house_image')->where("state='normal' AND last_modify_time>='" . $time . "' AND project_id='$proID'")->select();
            $sql = M()->getLastSql();
            $totelCount = M('jk_house_image')->where("state='normal' AND last_modify_time>='" . $time . "' AND project_id='$proID'")->count();
        } elseif ($type == 'acprogram_recheck') {//问题验收信息
            //先获取项目所有问题的id
            $acprogramdata = M('jk_acprogram')->where("project_id = '$proID' AND last_modify_time>='" . $time . "'")->field('problem_id')->select();
            //数组转换为字符串
            foreach ($acprogramdata as $v) {
                $tempid = $v['problem_id'];
                $tempids .= $tempid . ",";
            }
            $tempids = substr($tempids, 0, strlen($tempids) - 1); //去除最后一个，
            $adownData['acprogramdata'] = $acprogramdata;
            $acprogramids = "(" . $tempids . ")";
            $adownData['ids'] = $acprogramids;
            $data = M('jk_acprogram_recheck')->where("problem_id in $acprogramids")->select();
            $sql = M()->getLastSql();
            $totelCount = M('jk_acprogram_recheck')->where("problem_id in $acprogramids")->count();
        } elseif ($type == 'acprogram_recheck_etx') {//问题验收附件

            //先获取项目所有问题的id
            $acprogramdata = M('jk_acprogram')->where("project_id = '$proID' AND last_modify_time>='" . $time . "'")->field('problem_id')->select();
            //数组转换为字符串
            foreach ($acprogramdata as $v) {
                $tempid = $v['problem_id'];
                $tempids .= $tempid . ",";
            }
            $tempids = substr($tempids, 0, strlen($tempids) - 1); //去除最后一个，
            $adownData['acprogramdata'] = $acprogramdata;
            $acprogramids = "(" . $tempids . ")";//以（）框起来
            $adownData['ids'] = $acprogramids;

            $data = M('jk_acprogram_recheck_etx')->where("problem_id in $acprogramids AND last_modify_time>='" . $time . "'")->select();
            $sql = M()->getLastSql();
            $totelCount = M('jk_acprogram_recheck_etx')->where("problem_id in $acprogramids AND last_modify_time>='" . $time . "'")->count();
        }
//         elseif($type == 'jk_acprogram_point'){  //互信图区域信息表更新
//             $data = M('jk_acprogram_point')->where("state='normal' AND last_modify_time>='".$time."' AND problem_id = '$proID'")->select();
//             $sql = M()->getLastSql();
//             $totelCount = M('jk_acprogram_point')->where("state='normal' AND last_modify_time>='".$time."' AND problem_id = '$proID'")->count();
//         }


        $adownData['data'] = $data;
        $adownData['count'] = $totelCount;
        $adownData['sql'] = $sql;
        echo json_encode($adownData);
    }

    /**
     * 函数用途描述：实时下载服务器数据
     * @date: 2016年10月15日 下午2:44:44
     * @author: luojun
     * @param: $time 上次更新时间，$offset 数据偏移，$count 获取数据条数，$type 要下载的数据类别 program=问题列表
     * @return: json格式数据 data 返回的数据，该类数据总共数量
     */

    /**
     * 函数用途描述：根据获取的项目ID下载相应服务器数据
     * @date: 2016年10月15日 下午2:44:44
     * @author: luojun
     * @param: $time 上次更新时间，$page 数据偏移(页码)，$count 获取数据条数，$type 要下载的数据类别 program=问题列表,$flitr=筛选条件
     * @return: json格式数据 data 返回的数据，该类数据总共数量
     */
    public function loaddateRT($page = 0, $count = 10, $type = 'images', $filtr = '')
    {

        file_put_contents('apptest.log', $_POST);
        //获取前两个月的时间错
        $where = "type=0";
        $flitr = json_decode($filtr, true);
        $page = $page < 0 ? 0 : $page;
        $count = $count < 1 ? 10 : $count;
        $count = $count > 100 ? 100 : $count;
        file_put_contents('apptest.log', $flitr);
        if ($type == 'program') {
//            施工单位只查看对应施工单位绑定的楼栋的数据
            if (3 == $flitr['level']) {
                $uid = $flitr['uid'];
                if ($flitr['tids'] > '') {
                    $tids = $flitr['tids'];
                    $where .= " AND (authid=$uid OR target_id IN ($tids))";
                } else {
                    $where .= " AND authid=$uid ";
                }
            }
            $proid = $flitr['proid'];
            $build_id = $flitr['build_id'];
            $option_pid = $flitr['option_pid'];
            $status = $flitr['status'];

            $where .= " AND ownid=$proid ";
            if ($build_id > "") {
                $where .= " AND build_id IN ($build_id) ";
            }
            if ($option_pid > "") {
                $where .= " AND option_pid IN ($option_pid) ";
            }
            if (!($status === "")) {
                $where .= " AND status IN ($status) ";
            }

            file_put_contents('apptest.log', $where);
            $adata = M('jk_program')->where($where)->order('create_time DESC')->limit($page * $count, $count)->select();
            $sql = M('jk_program')->_sql();
            $totelCount = M('jk_program')->where($where)->count();

//            构造需要下载的图片数据
            $target_id = "";
            $where = "proID = $proid";
            foreach ($adata as $v) {
                $target_id .= $v['init_id'] . ",";
            }
            $target_id = substr($target_id, 0, strlen($target_id) - 1);
            if ($target_id != "" && $target_id != null) {
                $where .= " AND problem_id in(" . $target_id . ")";
                //          同时构造需要下载的问题回复数据
                $boardlist = M('jk_problm_board')->where($where)->select();
//                问题图片和问题回复图片

                foreach ($boardlist as $vv) {
                    $target_id .= ",";
                    $target_id .= $vv['boardid'];
                }
                $where = "";
//                $target_id = substr($target_id, 0, strlen($target_id) - 1);
                if ($target_id != "" && $target_id != null)
                    $where = " AND target_id in(" . $target_id . ")";

                $aImagesdata = M('picture')->where("path !='' AND type!='detail' AND projectid = $proid" . $where)->select();
                $sql = M('picture')->_sql();
                $adownData['boarddata'] = $boardlist;
                $adownData['imagedata'] = $aImagesdata;
            }

            // echo M()->getLastSql();die;
        }
//        elseif($type=='board'){
//        	//获取前两个月的时间错
//        	$month_time=strtotime("-2 month");
//        	//如果更新时间小于两个月前的时间戳则只传递两个月前的数据
//        	if($time<$month_time*1000){
//        		$time=$month_time*1000;
//        	}
//        	$adata=M('jk_problm_board')->where("update_time>='".$time."' AND proID = $proID")->order('create_time DESC')->limit($offset,$count)->select();
//        	$totelCount=M('jk_problm_board')->where("update_time>='".$time."' AND proID = $proID")->count();
//        }
//        elseif($type=='images'){//不更新自己上传的图片 AND devicename = ".$deviceId."(uid != $uid OR devicename != ".$deviceId.") AND
//            //添加筛选条件不下载已经整改完成的问题对应的图片
//            $target_id="";
//            //获取已关闭的问题表的id放入target_id中
//            $where="";
//            $day_time = strtotime('-7 days')*1000;
//            $problemlist=M('jk_program')->where("ownid = $proID AND (status = 1 OR status = 3) and update_time<'".$day_time."'")->limit($offset,$count)->select();
//            foreach ($problemlist as $v){
//                $target_id.=$v['init_id'].",";
//
//            }
//            $target_id = substr($target_id,0,strlen($target_id)-1);
//            if($target_id!="" && $target_id!=null)
//                $where=" AND problem_id in(".$target_id.")";
//            else
//            	$where="1=2";
//            //将回复表中对应已关闭的问题表的id放入target_id中
//            $boardlist=M('jk_problm_board')->where("proID = $proID".$where)->limit($offset,$count)->select();
//
//            foreach ($boardlist as $vv){
//                $target_id.=$vv['boardid'].",";
//
//            }
//            $where="";
//
//            $target_id = substr($target_id,0,strlen($target_id)-1);
//            if($target_id!="" && $target_id!=null)
//                $where=" AND target_id not in(".$target_id.")";
//
//           //$where="";//合格图片也下载下来
//            //获取前两个月的时间错
//            $month_time=strtotime("-2 month");
//            //如果更新时间小于两个月前的时间戳则只传递两个月前的数据
//            if($time<$month_time*1000){
//            	$time=$month_time*1000;
//            }
//        	$adata=M('picture')->where("path !='' AND type!='detail' AND update_time>='".$time."' AND projectid = $proID".$where)->order('update_time DESC')->limit($offset,$count)->select();
//        	$totelCount=M('picture')->where("path !='' AND type!='detail' AND update_time>='".$time."' AND projectid = $proID".$where)->count();
//
//        }
//        elseif($type=='local_images'){//下载平面图
//        	$adata=M('picture')->where("path !='' AND type='local' AND projectid = $proID")->order('update_time DESC')->limit($offset,$count)->select();
//        	$totelCount=M('picture')->where("path !='' AND type='local' AND projectid = $proID")->count();
//
//        }
        elseif ($type == 'measure') {//更新实测实量任务
            //            施工单位只查看对应施工单位绑定的楼栋的数据
            $where = '1=1 ';

            $proid = $flitr['proid'];
            $build_id = $flitr['build_id'];
            $option_id = $flitr['option_id'];
            $status = $flitr['status'];

            if ($build_id > "") {
                $where .= " AND t.build_id IN ($build_id) ";
            }
            if ($option_id > "") {
                $where .= " AND t.optionid IN ($option_id) ";
            }
            if ($status > "") {
                $where .= " AND t.status IN ($status) ";
            }
            if (3 == $flitr['level']) {
                $uid = $flitr['uid'];
                if ($flitr['tids'] > '') {
                    $tids = $flitr['tids'];
                    $where .= " AND (t.authorid=$uid OR f.cid IN ($tids))";
                } else {
                    $where .= " AND t.authorid=$uid ";
                }
            }
            $adata = M()->table('irosn_jk_measuring_tasks t, irosn_jk_floor f')
                ->where("$where AND t.build_id = f.id AND t.projectid = $proid AND f.projectid=$proid AND f.status=1 ")
                ->limit($page * $count, $count)->order('t.createtime DESC')->field('t.*')->select();
            $sql = M()->_sql();
            $totelCount = M()->table('irosn_jk_measuring_tasks t, irosn_jk_floor f')
                ->where("$where AND t.build_id = f.id AND t.projectid = $proid AND f.projectid=$proid AND f.status=1 ")->count();
//        	获取实测任务对应实测数据
            $tid = '';
            foreach ($adata as $adatum) {
                $tid .= $adatum['tid'] . ',';
            }
            $tid = substr($tid, 0, strlen($tid) - 1);
            $tid = explode(',', $tid);
            $map['tid'] = array('in', $tid);
            $point_data = M('jk_check_point')->where($map)->select();
//            $sql= M('jk_check_point')->_sql();
            $adownData['point_data'] = $point_data;

        } elseif ($type == 'measuredProgram') {
            $where = "type=1";
//            施工单位只查看对应施工单位绑定的楼栋的数据
            if (3 == $flitr['level']) {
                $uid = $flitr['uid'];
                if ($flitr['tids'] > '') {
                    $tids = $flitr['tids'];
                    $where .= " AND (authid=$uid OR target_id IN ($tids))";
                } else {
                    $where .= " AND authid=$uid ";
                }
            }
            $proid = $flitr['proid'];
            $build_id = $flitr['build_id'];
            $option_pid = $flitr['option_pid'];
            $status = $flitr['status'];
            $where .= " AND ownid=$proid ";
            if ($build_id > "") {
                $where .= " AND build_id IN ($build_id) ";
            }
            if ($option_pid > "") {
                $where .= " AND option_pid IN ($option_pid) ";
            }
            if (!($status === "")) {
                $where .= " AND status IN ($status) ";
            }

            file_put_contents('apptest.log', $where);
            $adata = M('jk_program')->where($where)->order('create_time DESC')->limit($page * $count, $count)->select();
            $sql = M('jk_program')->_sql();
            $totelCount = M('jk_program')->where($where)->count();

//            构造需要下载的图片数据
            $target_id = "";
            $where = "proID = $proid";
            foreach ($adata as $v) {
                $target_id .= $v['init_id'] . ",";
            }
            $target_id = substr($target_id, 0, strlen($target_id) - 1);
            if ($target_id != "" && $target_id != null) {
                $where .= " AND problem_id in(" . $target_id . ")";
                //          同时构造需要下载的问题回复数据
                $boardlist = M('jk_problm_board')->where($where)->select();
//                问题图片和问题回复图片

                foreach ($boardlist as $vv) {
                    $target_id .= ",";
                    $target_id .= $vv['boardid'];
                }
                $where = "";
                $target_id = substr($target_id, 0, strlen($target_id) - 1);
                if ($target_id != "" && $target_id != null)
                    $where = " AND target_id in(" . $target_id . ")";

                $aImagesdata = M('picture')->where("path !='' AND type!='detail' AND projectid = $proid" . $where)->select();
//                $sql=M('picture')->_sql();
                $adownData['boarddata'] = $boardlist;
                $adownData['imagedata'] = $aImagesdata;
            }

        }
//        elseif($type=='inspect'){//实测实量数据表
//        	//获取前两个月的时间错
//        	$month_time=strtotime("-2 month");
//        	//如果更新时间小于两个月前的时间戳则只传递两个月前的数据
//        	if($time<$month_time*1000){
//        		$time=$month_time*1000;
//        	}
//        	$adata=M('jk_rate')->where("update_time>'".$ratetime."' AND projectid = $proID")->order('create_time DESC')->limit($offset,$count)->select();
//        	$totelCount=M('jk_rate')->where("projectid = $proID")->limit($offset,$count)->count();
//        	//只下载整改后的且输入同一级的检查记录
//        	$adownData['data1'] = null;
//        	//$adownData['data1']=M('jk_check_point')->where("type=1 and update_time>='".$time."' AND projectid = $proID AND level=$level")->order('create_time DESC')->limit($offset,$count)->select();
//        }
        $adownData['data'] = $adata;
        $adownData['count'] = $totelCount;
        $adownData['sql'] = $sql;
        echo json_encode($adownData);
    }


    /**
     * 函数用途描述：根据获取的项目ID下载相应服务器数据
     * @date: 2016年10月15日 下午2:44:44
     * @author: luojun
     * @param: $time 上次更新时间，$offset 数据偏移，$count 获取数据条数，$type 要下载的数据类别 program=问题列表
     * @return: json格式数据 data 返回的数据，该类数据总共数量
     */
    public function loaddate($time = 1490268108983, $offset = 0, $count = 2000, $type = 'images', $uid = 0, $deviceId = '', $proID = 21, $ratetime = 0, $level = 1)
    {
        $time = $time - 300000;//防止并发，时间到5分钟以前
        $sql = '';
        if ($type == 'program') {
            //获取前两个月的时间错
            $month_time = strtotime("-2 month");
            //如果更新时间小于两个月前的时间戳则只传递两个月前的数据
            if ($time < $month_time * 1000) {
                $time = $month_time * 1000;
            }
            $adata = M('jk_program')->where("update_time>='" . $time . "' AND ownid = $proID")->order('create_time DESC')->limit($offset, $count)->select();
            $totelCount = M('jk_program')->where("update_time>='" . $time . "' AND ownid = $proID")->count();
            // echo M()->getLastSql();die;
        } elseif ($type == 'board') {
            //获取前两个月的时间错
            $month_time = strtotime("-2 month");
            //如果更新时间小于两个月前的时间戳则只传递两个月前的数据
            if ($time < $month_time * 1000) {
                $time = $month_time * 1000;
            }
            $adata = M('jk_problm_board')->where("update_time>='" . $time . "' AND proID = $proID")->order('create_time DESC')->limit($offset, $count)->select();
            $totelCount = M('jk_problm_board')->where("update_time>='" . $time . "' AND proID = $proID")->count();
        } elseif ($type == 'images') {//不更新自己上传的图片 AND devicename = ".$deviceId."(uid != $uid OR devicename != ".$deviceId.") AND
            //添加筛选条件不下载已经整改完成的问题对应的图片
            $target_id = "";
            //获取已关闭的问题表的id放入target_id中
            $where = "";
            $day_time = strtotime('-7 days') * 1000;
            $problemlist = M('jk_program')->where("ownid = $proID AND (status = 1 OR status = 3) and update_time<'" . $day_time . "'")->limit($offset, $count)->select();
            foreach ($problemlist as $v) {
                $target_id .= $v['init_id'] . ",";

            }
            $target_id = substr($target_id, 0, strlen($target_id) - 1);
            if ($target_id != "" && $target_id != null)
                $where = " AND problem_id in(" . $target_id . ")";
            else
                $where = "1=2";
            //将回复表中对应已关闭的问题表的id放入target_id中
            $boardlist = M('jk_problm_board')->where("proID = $proID" . $where)->limit($offset, $count)->select();

            foreach ($boardlist as $vv) {
                $target_id .= $vv['boardid'] . ",";

            }
            $where = "";

            $target_id = substr($target_id, 0, strlen($target_id) - 1);
            if ($target_id != "" && $target_id != null)
                $where = " AND target_id not in(" . $target_id . ")";

            //$where="";//合格图片也下载下来
            //获取前两个月的时间错
            $month_time = strtotime("-2 month");
            //如果更新时间小于两个月前的时间戳则只传递两个月前的数据
            if ($time < $month_time * 1000) {
                $time = $month_time * 1000;
            }
            $adata = M('picture')->where("path !='' AND type!='detail' AND update_time>='" . $time . "' AND projectid = $proID" . $where)->order('update_time DESC')->limit($offset, $count)->select();
            $totelCount = M('picture')->where("path !='' AND type!='detail' AND update_time>='" . $time . "' AND projectid = $proID" . $where)->count();

        } elseif ($type == 'local_images') {//下载平面图
            $adata = M('picture')->where("path !='' AND type='local' AND projectid = $proID")->order('update_time DESC')->limit($offset, $count)->select();
            $sql = M('picture')->_sql();
            $totelCount = M('picture')->where("path !='' AND type='local' AND projectid = $proID")->count();

        } elseif ($type == 'measure') {//更新实测实量任务
            //获取前两个月的时间错
            $month_time = strtotime("-2 month");
            //如果更新时间小于两个月前的时间戳则只传递两个月前的数据
            if ($time < $month_time * 1000) {
                $time = $month_time * 1000;

            }
            $adata = M('jk_measuring_tasks')->where("status!=-1 and updatetime>='" . $time . "' AND projectid = $proID ")->order('createtime DESC')->select();
            $totelCount = M('jk_measuring_tasks')->where("status!=-1 and updatetime>='" . $time . "' AND projectid = $proID ")->count();
        } elseif ($type == 'inspect') {//实测实量数据表
            //获取前两个月的时间错
            $month_time = strtotime("-2 month");
            //如果更新时间小于两个月前的时间戳则只传递两个月前的数据
            if ($time < $month_time * 1000) {
                $time = $month_time * 1000;
            }
            $adata = M('jk_rate')->where("update_time>'" . $ratetime . "' AND projectid = $proID")->order('create_time DESC')->limit($offset, $count)->select();
            $totelCount = M('jk_rate')->where("projectid = $proID")->limit($offset, $count)->count();
            //只下载整改后的且输入同一级的检查记录
            $adownData['data1'] = null;
            //$adownData['data1']=M('jk_check_point')->where("type=1 and update_time>='".$time."' AND projectid = $proID AND level=$level")->order('create_time DESC')->limit($offset,$count)->select();
        }
        $adownData['data'] = $adata;
        $adownData['count'] = $totelCount;
        $adownData['sql'] = $sql;
        echo json_encode($adownData);
    }

    /**
     * 分户验收保存问题图片
     */
    public function check_saveImage()
    {
        file_put_contents('appRequst.log', "check_saveImage\n", FILE_APPEND);
        $photo_id = $_GET['photo_id'];
        $localname = "http://" . $_SERVER['HTTP_HOST'] . "/";
//        $localname = '';
        $project_id = $_GET['project_id'];

        $problem_id = $_GET['problem_id'];

        $update_time = $_GET['last_modify_time'];
        $create_time = $_GET['create_time'];
        $res['status'] = 0;
        $exname = strtolower(substr($_FILES['upfile']['name'], (strrpos($_FILES['upfile']['name'], '.') + 1)));
        $uploadfile = $this->getname($exname);
        $res['uploadfile'] = $localname . $uploadfile;
        if (move_uploaded_file($_FILES['upfile']['tmp_name'], $uploadfile)) {
            $res['state'] = 'normal';
            $data['photo_id'] = $photo_id;
            $data['project_id'] = $project_id;

            $data['problem_id'] = $problem_id;
//            $uploadfile=str_replace('./','',$uploadfile);
            $data['photo_url'] = $localname . $uploadfile;

            $data['last_modify_time'] = $update_time;
            $data['create_time'] = $create_time;

            $resid = M('jk_acprogram_image')->data($data)->add();

            $res['sql'] = M()->getLastSql();
            if ($resid) {
                $res['msg'] = $photo_id;
            }
        } else {
            $res['msg'] = "上传失败";
        }

        echo json_encode($res);
    }

    /**
     * 分户验收保存问题回复图片
     * */
    public function check_savePicture()
    {
        $id = $_GET['id'];
        $localname = "http://" . $_SERVER['HTTP_HOST'] . "/";
//        $localname = '';
        $project_id = $_GET['project_id'];
        $reply_id = $_GET['reply_id'];
        $program_id = $_GET['program_id'];
        $path = $_GET['path'];
        $type = $_GET['type'];
        $update_time = $_GET['update_time'];
        $create_time = $_GET['create_time'];
        $res['status'] = 0;
        $exname = strtolower(substr($_FILES['upfile']['name'], (strrpos($_FILES['upfile']['name'], '.') + 1)));
        $uploadfile = $this->getname($exname);
        $res['uploadfile'] = $localname . $uploadfile;
        if (move_uploaded_file($_FILES['upfile']['tmp_name'], $uploadfile)) {
            $res['status'] = 1;
            $data['id'] = $id;
            $data['project_id'] = $project_id;
            $data['reply_id'] = $reply_id;
            $data['program_id'] = $program_id;
            $data['path'] = $localname . $uploadfile;
            $data['type'] = $type;
            $data['update_time'] = $update_time;
            $data['create_time'] = $create_time;

            $resid = M('jk_comment_etx')->data($data)->add();

            $res['sql'] = M()->getLastSql();
            if ($resid) {
                $res['msg'] = $id;
            }
        } else {
            $res['msg'] = "上传失败";
        }

        echo json_encode($res);

    }

    /**
     * 保存图片
     */
    public function savePicture()
    {
        $inid = $_GET['init_id'];
        $type = $_GET['type'];
        $createtime = $_GET['init_id'];
        $createtime = microtimeStr() . "" . $_GET['uid'];
        $uid = $_GET['uid'];
        $localid = $_GET['localId'];
        $deviceId = $_GET['deviceId'];
        $projectid = $_GET['proID'];
        //$did=$_GET['did'];
        $res['status'] = 0;
        $exname = strtolower(substr($_FILES['upfile']['name'], (strrpos($_FILES['upfile']['name'], '.') + 1)));
        $uploadfile = $this->getname($exname);
        if (move_uploaded_file($_FILES['upfile']['tmp_name'], $uploadfile)) {
            $res['status'] = 1;
            $data['id'] = $createtime;
            $data['target_id'] = $inid;
            $data['create_time'] = microtimeStr();
            $data['update_time'] = microtimeStr();
            $data['path'] = $uploadfile;
            $data['type'] = $type;
            $data['status'] = 1;
            $data['uid'] = $uid;
            $data['devicename'] = $deviceId;
            $data['projectid'] = $projectid;

            $resid = M('picture')->data($data)->add();

            //$res['sql'] = M()->getLastSql();
            if ($resid) {
                if ($type == "problem") {
                    $mapid = M('jk_program')->where("init_id='$inid'")->getField('mapid');
                    $value = $data['id'] . "," . $mapid;
                    M('jk_program')->where("init_id='$inid'")->setField('mapid', $value);
                } elseif ($type == "board") {
                    $mapid = M('jk_problm_board')->where("boardid='$inid'")->getField('images');
                    $value = $data['id'] . "," . $mapid;
                    M('jk_problm_board')->where("boardid='$inid'")->setField('images', $value);
                }

                $res['msg'] = $localid;
            }
        } else {
            $res['msg'] = "上传失败";
        }
        echo json_encode($res);
    }

    public function getname($exname = '.jpg')
    {

        $name = uniqid();//唯一时间戳
        $dir = "./Uploads/Picture/";

        if (!is_dir($dir)) {
            mkdir($dir, 0777);
        }
        while (true) {
            if (!is_file($dir . $name . "." . $exname)) {
                $name = uniqid();
                break;
            }
        }
        return $dir . $name . "." . $exname;
    }

    public function selectimg($projectid = 21, $floorid = 150341, $measureid = 40, $offset = 0, $count = 1000)
    {
        $where['project_id'] = $projectid;
        $where['floor_id'] = $floorid;
        $where['measure_id'] = $measureid;

        $adata = M('jk_measure_image')->where($where)->limit($offset, $count)->select();
        $totelCount = M('jk_measure_image')->where($where)->count();
        $adownData['data'] = $adata;
        $adownData['count'] = $totelCount;

        echo json_encode($adownData);
    }

    //test
    public function infotest($uid = 0, $proid = 0)
    {
        if ($uid > 0 && UID == 0) {
            define('UID', $uid);
        }
        $map['status'] = 1;
        $projects = get_my_projects();
        if (!is_administrator($uid)) {
            $map['id'] = array('in', get_my_projects());
        }
        $aProjectInfo = M('jk_project')->field('id,name,other_name,mapid,pid')->where($map)->select();

        foreach ($aProjectInfo as &$aInfo) {
            $aInfo['mappath'] = coverIds2Path($aInfo['mapid']);
            $aPids[] = $aInfo['pid'];
//             dump($aInfo['mappath']);
        }
        $aProjectTree = D('JKProject/JKProjectCategory')->getTree(0, 'id,title,sort,pid');

        $aProjects = tree_to_listwx($aProjectTree, $child = '_');
        $map = array();
        $map['status'] = 1;
// 		$map['projectid']=array('in',$projects);
        $map['projectid'] = $proid;

        $aOptionTree = D('JKProgram/JKProjectCategory')->getTree(0, 'id,title,sort,pid,cid,imgid,house_img_id', $map, 'sort');
        //dump($aOptionTree);
        $aOptions = tree_to_listwx($aOptionTree, $child = '_');
        //获取同组用户列表
        $gpid = M('jk_project')->where("id=$proid")->getField('pid');

        //$aUsersList = get_groups_users($gpid);
        $aUsersList = get_all_groups_users();

        //用户单位
        $aUsers = M('auth_group_access')->where("uid=$uid")->field('group_id')->select();
        foreach ($aUsers as $v) {

            $aUserGroup[] = $v['group_id'];//当前用户对应用户组
        }

        $list = M('jk_survey_option')->where("STATUS > 0")->select();

        //实测项
        $surveyInfo = list_to_tree($list, 'id', 'pid', '_');
        $surveyList = tree_to_listwx($surveyInfo, $child = '_');
        //获取项目级别信息
// 		$aProjectindex = M('jk_project')->where("pid = 0 AND STATUS > 0")->order('create_time DESC')->select();

// 		组织架构
        $map = array(
            'status' => array(
                'gt',
                0
            ),
// 		    'cate'=>1,
// 		    'id' => array('in', $aPids),
        );
        $list = M('AuthGroup')->field('id,title,pid')
            ->where($map)
            ->select();

        $group_ids = M('auth_group_access')->where("uid=" . $uid)->field('group_id')->order('group_id')->select();
        $alist = array();
        $oldlist = array();
        $iflag = 0;
        foreach ($group_ids as $v) {
            $map = array('status' => array('gt', 0), 'id' => $v['group_id']);
            $glist = M('AuthGroup')->field('id,title,pid,cate')->where($map)->find();

            foreach ($oldlist as $value) {
                if ($value[$glist['id']]) {
                    $iflag = 1;
                }
            }
            if ($iflag) {
                $iflag = 0;
                continue;
            }
            $glistTem = list_to_tree($list, 'id', 'pid', '_', $v['group_id']);
            if ($glistTem) {
                $glist['_'] = $glistTem;
                // dump($glist);
            }
            $alist[] = $glist;
            $temlist = tree_to_listwx($alist, '_');
            $oldlist[] = $temlist;
        }


        //dump($list);
        $ret['projectInfos'] = $aProjectInfo;
        $ret['projectTree'] = $aProjectTree;
        $ret['projectList'] = $aProjects;

        $ret['optionTree'] = $aOptionTree;
        $ret['optionList'] = $aOptions;

        $ret['surveyTree'] = $surveyInfo;
        $ret['surveyList'] = $surveyList;
        $ret['userGroup'] = $aUserGroup;
        $ret['userList'] = $aUsersList;
        $ret['nodeTree'] = $alist;
//         $ret['projectindex'] = $aProjectindex;
//         dump($ret);
        $path = 'Uploads/' . $uid . '_' . $proid . '.log';
        file_put_contents($path, json_encode($ret));
        //$path = 'Uploads/'.$uid.'_'.$proid.'.zip';
        //$this->zip('Uploads/'.$uid.'_'.$proid, './Uploads');
        //echo preg_replace('/[\x00-\x1f]|\x7f/i','','http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].'/'.$path);
        echo 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] . '/' . $path;
    }

    /**
     * 函数用途描述：info接口zip版本
     * @date: 2017年6月24日 下午12:09:05
     * @author: luojun
     * @param:
     * @return:
     */
    public function infozip($uid = 0, $proid = 0)
    {
        if ($uid > 0 && UID == 0) {
            define('UID', $uid);
        }
        //保存该用户的切换项目id
        $save['now_proid'] = $proid;
        M("member")->where("uid=$uid")->save($save);
        $map['status'] = 1;
        //  $projects = get_my_projects();
        if (!is_administrator($uid)) {
            $map['id'] = array('in', get_my_projects());
        }
        $aProjectInfo = M('jk_project')->field('id,name,other_name,mapid,pid')->where($map)->select();

        foreach ($aProjectInfo as &$aInfo) {
            $aInfo['mappath'] = coverIds2Path($aInfo['mapid']);
            $aPids[] = $aInfo['pid'];
            //             dump($aInfo['mappath']);
        }
        $aProjectTree = D('JKProject/JKProjectCategory')->getTree(0, 'id,title,sort,pid');

        $aProjects = tree_to_listwx($aProjectTree, $child = '_');

        $map = array();
        $map['status'] = 1;
        // 		$map['projectid']=array('in',$projects);
        $map['projectid'] = $proid;
        // file_put_contents('time.log',json_encode( $aProjectTree), FILE_APPEND);
        M('jk_floor')->where($map)->select();
        $aOptionTree = D('JKProgram/JKProjectCategory')->getTree(0, 'id,title,sort,pid,cid,imgid,house_img_id,StagesCode', $map, 'title');
        //dump($aOptionTree);

        $aOptions = tree_to_listwx($aOptionTree, $child = '_');
        //获取同组用户列表
        $gpid = M('jk_project')->where("id=$proid")->getField('pid');

        //$aUsersList = get_groups_users($gpid);
        $aUsersList = get_all_groups_users();
        //用户单位
        $aUsers = M('auth_group_access')->where("uid=$uid")->field('group_id')->select();
        foreach ($aUsers as $v) {

            $aUserGroup[] = $v['group_id'];//当前用户对应用户组
        }

        $list = M('jk_survey_option')->where("STATUS > 0")->select();

        //实测项
        $surveyInfo = list_to_tree($list, 'id', 'pid', '_');
        $surveyList = tree_to_listwx($surveyInfo, $child = '_');

        //获取项目级别信息
        // 		$aProjectindex = M('jk_project')->where("pid = 0 AND STATUS > 0")->order('create_time DESC')->select();

        // 		组织架构
        $map = array(
            'status' => array(
                'gt',
                0
            ),
            // 		    'cate'=>1,
            // 		    'id' => array('in', $aPids),
        );

        $list = M('AuthGroup')->field('id,title,pid')
            ->where($map)
            ->select();

        $group_ids = M('auth_group_access')->where("uid=" . $uid)->field('group_id')->order('group_id')->select();
        file_put_contents('time.log', '213', FILE_APPEND);
        $alist = array();
        $oldlist = array();
        $iflag = 0;
        foreach ($group_ids as $v) {
            $map = array('status' => array('gt', 0), 'id' => $v['group_id']);
            $glist = M('AuthGroup')->field('id,title,pid,cate')->where($map)->find();

            foreach ($oldlist as $value) {
                if ($value[$glist['id']]) {
                    $iflag = 1;
                }
            }
            if ($iflag) {
                $iflag = 0;
                continue;
            }
            $glistTem = list_to_tree($list, 'id', 'pid', '_', $v['group_id']);
            if ($glistTem) {
                $glist['_'] = $glistTem;
                // dump($glist);
            }
            $alist[] = $glist;
            $temlist = tree_to_listwx($alist, '_');
            $oldlist[] = $temlist;
        }

        //获取项目编码
        $pro_code = M('jk_project')->where("id=" . $proid)->getField('ProjectNumber');
        $ret['stageInfos'] = M('jk_stage')->field("distinct(StagesCode),StagesName")->group("StagesCode")->where("status = 1 and ParentCode!='' and  ParentCode='" . $pro_code . "'")->select();
        //dump($list);
        $ret['projectInfos'] = $aProjectInfo;
        $ret['projectTree'] = $aProjectTree;
        $ret['projectList'] = $aProjects;

        $ret['optionTree'] = $aOptionTree;
        $ret['optionList'] = $aOptions;

        $ret['surveyTree'] = $surveyInfo;
        $ret['surveyList'] = $surveyList;
        $ret['userGroup'] = $aUserGroup;
        $ret['userList'] = $aUsersList;
        $ret['nodeTree'] = $alist;
        //         $ret['projectindex'] = $aProjectindex;
        //         dump($ret);
        $path = 'Uploads/' . $uid . '_' . $proid . '.log';

        // file_put_contents('time.log','23');
        file_put_contents($path, json_encode($ret));

        $this->zip($path, './Uploads');
        $path = $path . '.zip';
        chmod($path, 0777);
        // file_put_contents('time.log','2333');
        file_put_contents('time.log', 'fi', FILE_APPEND);
        //echo preg_replace('/[\x00-\x1f]|\x7f/i','','http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].'/'.$path);
        echo 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] . '/' . $path;

        file_put_contents('time.log', 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] . '/' . $path, FILE_APPEND);
        file_put_contents('time.log', 'fi', FILE_APPEND);
    }

    /**
     * 函数用途描述：更改之前的问题表中日常巡查加上最上级检查项id
     * @date: 2017年05月16日 下午3:16:44
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function add_option_pid()
    {
        $data = M('jk_program')->where('status!=-1 and type=0')->select();
        foreach ($data as $v) {
            if ($v['option_pid'] == NULL || $v['option_pid'] == '') {
                $new['option_pid'] = get_option_pid($v['option_id']);
                $res = M('jk_program')->where("init_id=" . $v['init_id'])->save($new);
                if ($res)
                    echo 'success';
                else
                    echo M()->getLastSql();
            }

        }
    }

    /**
     * 函数用途描述：更改之前的问题表中实测适量问题加上最上级检查项id
     * @date: 2017年05月17日 下午1:41:44
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function add_survey_pid()
    {
        $data = M('jk_program')->where('status!=-1 and type=1')->select();
        foreach ($data as $v) {
            if ($v['option_pid'] == NULL || $v['option_pid'] == '') {
                $new['option_pid'] = get_survey_pid($v['option_id']);
                $res = M('jk_program')->where("init_id=" . $v['init_id'])->save($new);
                if ($res)
                    echo 'success';
                else
                    echo M()->getLastSql();
            }

        }
    }

    /**
     * 函数用途描述：更改之前的测量表中根据uid查询到level以及计算合格率
     * @date: 2017年05月17日 下午4:04:44
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function change_check_point()
    {
        $data = M('jk_check_point')->where("status!=-1 and `level` IS NULL")->select();

        foreach ($data as $v) {
            //获取用户所属权限等级
            $new['level'] = get_my_auth($v['userid']);
            //计算合格率,测量点数,不合格点数
            $arr = getrate($v);
            $new['rate'] = $arr['rate'];
            $new['nonum'] = $arr['nonum'];
            $new['totalnum'] = $arr['totalnum'];
            $res = M('jk_check_point')->where("id=" . $v['id'])->save($new);
            if ($res)
                echo 'success';
            else
                echo M()->getLastSql();
        }

    }

    /**
     * 函数用途描述：更改之前的测量任务表加上imgid和imgurl
     * @date: 2017年07月03日 下午6:19:44
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function change_measure_tasks()
    {
        $data = M('jk_measuring_tasks')->where("`imgid` IS NULL")->select();
        foreach ($data as $v) {
            //根据项目、检查项、位置找出对应图纸的id和url
            $arr = get_measure_id_url($v['projectid'], $v['pointid'], $v['optionid']);
            $new['imgid'] = $arr['imgid'];
            $new['imgurl'] = $arr['imgurl'];
            $res = M('jk_measuring_tasks')->where("tid=" . $v['tid'])->save($new);
            if ($res)
                echo 'success';
            else
                echo M()->getLastSql();
        }

    }

    /**
     * 函数用途描述：计算各个项目的合格率
     * @date: 2017年05月18日 上午10:14:44
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function change_rate()
    {
        //先找出最底层的检查项
        $inspects = M('jk_survey_option')->where("maxqualified!=0 and status=1")->select();
        //遍历底层检查项
        foreach ($inspects as $inspect) {
            //遍历所有项目
            $projects = M('jk_project')->where("status=1")->field('id')->select();
            foreach ($projects as $project) {
                //生成3个单位的对应记录
                for ($i = 1; $i <= 3; $i++) {
                    //查询check_point表中对应数据
                    $where['projectid'] = $project['id'];
                    $where['inspect'] = $inspect['id'];
                    $where['level'] = $i;
                    $points = M('jk_check_point')->where($where)->field('nonum,totalnum')->select();
                    //构造合格率表中的数据
                    $add['inspect'] = $inspect['id'];
                    $add['name'] = $inspect['title'];
                    $add['projectid'] = $project['id'];
                    $add['create_time'] = time();
                    $add['update_time'] = time();
                    $nonum = 0;//不合格点数
                    $totalnum = 0;//总测量点数
                    foreach ($points as $point) {
                        $nonum = $nonum + $point['nonum'];
                        $totalnum = $totalnum + $point['totalnum'];
                    }
                    $rate = $nonum / $totalnum * 100;//合格率
                    $rate = round($rate, 2);
                    $add['nonum'] = $nonum;
                    $add['totalnum'] = $totalnum;
                    $add['rate'] = $rate;
                    $add['level'] = $i;
                    $res = M('jk_rate')->where($where)->find();
                    if ($res) {
                        echo 'save';
                    } else {
                        echo 'add';
                        $res = M('jk_rate')->add($add);
                    }
                }
            }
        }
    }

    /**
     * 函数用途描述: 获取用户反馈列表
     * @date: 2017-5-31 14:41:37
     * @author: yushichuan
     * @param:
     * @return: json: $advices(二维数组)
     */
    public function get_advice()
    {
        $advices = M('JkAdvice')->select();
        foreach ($advices as &$v) {
            $v['time'] = date("Y-m-d H:i:s", $v['time']);
        }
// 	   print_r($advices);exit;
        echo json_encode($advices);
    }

    /**
     * 函数用途描述: 添加用户反馈
     * @date: 2017-5-31 14:54:21
     * @author: yushichuan
     * @param:
     * @return: 1 写入成功     2 写入失败      3 该反馈已存在
     */
    public function add_advice()
    {
        if (IS_POST) {
            //获取用户名
            $map = ['uid' => I('post.user_id'), 'status' => 1];
            $username = M('Member')->where($map)->getField('username');
            //获取项目名
            $map = ['id' => I('post.project_id'), 'status' => 1];
            $project = M('JkProject')->where($map)->getField('name');

            //判断该反馈是否已存在
            $map = [
                'user_id' => I('post.user_id'),
                'type' => I('post.type'),
                'content' => I('post.content')
            ];
            if (M('JkAdvice')->where($map)->count()) {
                $ret['status'] = 3;
            } else {
                $data = I('post.');
                $data['username'] = $username;
                $data['project'] = $project;
                $data['time'] = time();
                $res = M('JkAdvice')->add($data);
                if ($res)
                    $ret['status'] = 1;
                else
                    $ret['status'] = 2;
            }
            echo json_encode($ret);
        }

    }

    /**
     * 函数用途描述: 判断是否存在该条测量任务
     * @date: 2017-6-16 09:18:21
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function is_exit_task($projectid = 21, $pointid = 150341, $optionid = 40, $pointnum = 0)
    {
        $map['projectid'] = $projectid;
        $map['pointid'] = $pointid;
        $map['optionid'] = $optionid;
        $map['pointnum'] = $pointnum;
        $res = M('jk_measuring_tasks')->where($map)->find();
        if ($res)
            $ret['status'] = 1;
        else
            $ret['status'] = 0;
        echo json_encode($ret);
    }

    /**
     * 函数用途描述:返回最上级的分户验收检查项ID及名称
     * @date: 2017-6-16 09:18:21
     * @author: tanjiewen
     * @param:
     * @return:最上级检查项及对应的下级检查项ids
     */
    public function get_check_option()
    {
//        $res = M("jk_acoption")->field("id AS item_id,title AS item_name")->where("pid=0")->select();
//        foreach ($res as &$v) {
//            $v['cids'] = $v['item_id'] . "," . get_check_ids($v['item_id']);
//        }
        $table = M('jk_acoption');

        //根据父类id获取对应子级检查项
        $list = $table->field('id AS item_id,pid AS parent_id,sort AS sort_number,title AS item_name,option_type,minqualified,maxqualified,mindestroy,maxdestroy,submitdata')->select();
        //实测项
        $surveyInfo = list_to_tree($list, 'item_id', 'parent_id', '_', 0);
//        $surveyLists=tree_to_list($surveyInfo,"_","item_id");
        $surveyLists = tree_to_listwx($surveyInfo, $child = '_', 'item_id');
        $ret['data'] = $surveyInfo;
        $ret['listData'] = $surveyLists;
        echo json_encode($ret);
    }


    /**
     * 函数用途描述：zip压缩
     * @date: 2017年6月24日 上午11:14:13
     * @author: luojun
     * @param:
     * @return:
     */
    function zip($path, $savedir)
    {
        $path = preg_replace('/\/$/', '', $path);
        preg_match('/\/([\d\D][^\/]*)$/', $path, $matches, PREG_OFFSET_CAPTURE);
        $filename = $matches[1][0] . ".zip";
        set_time_limit(0);
        $zip = new \ZipArchive();
        $zip->open($savedir . '/' . $filename, \ZIPARCHIVE::OVERWRITE);
        if (is_file($path)) {
            $path = preg_replace('/\/\//', '/', $path);
            $base_dir = preg_replace('/\/[\d\D][^\/]*$/', '/', $path);
            $base_dir = addcslashes($base_dir, '/:');
            $localname = preg_replace('/' . $base_dir . '/', '', $path);
            $zip->addFile($path, $localname);
            $zip->close();
            return $filename;
        } elseif (is_dir($path)) {
            $path = preg_replace('/\/[\d\D][^\/]*$/', '', $path);
            $base_dir = $path . '/';//基目录
            $base_dir = addcslashes($base_dir, '/:');
        }
        $path = preg_replace('/\/\//', '/', $path);
        function addItem($path, &$zip, &$base_dir)
        {
            $handle = opendir($path);
            while (false !== ($file = readdir($handle))) {
                if (($file != '.') && ($file != '..')) {
                    $ipath = $path . '/' . $file;
                    if (is_file($ipath)) {//条目是文件
                        $localname = preg_replace('/' . $base_dir . '/', '', $ipath);
                        var_dump($localname);
                        $zip->addFile($ipath, $localname);
                    } else if (is_dir($ipath)) {
                        addItem($ipath, $zip, $base_dir);
                        $localname = preg_replace('/' . $base_dir . '/', '', $ipath);
                        var_dump($localname);
                        $zip->addEmptyDir($localname);
                    }
                }
            }
        }

        addItem($path, $zip, $base_dir);
        $zip->close();
        return $filename;
    }

    /**
     * 函数用途描述：分享下载
     * @date: 2017年7月4日
     * @author: yushichuan
     * @param:
     * @return:
     */
    public function downloadUrl()
    {
        //获取设备类型
        $type = $this->getDeviceType();
        if ($type === 1) {     //ios设备
            header('Location: https://itunes.apple.com/cn/app/%E9%87%91%E5%93%81%E8%B4%A8/id1188724253?mt=8');
        } elseif ($type === 2) {       //安卓设备
            header('Location: http://218.70.38.40:3380/jkqc.apk');
        } else {
            echo "<h1 style='margin:1%;'>请用安卓或IOS设备访问</h1>";
        }
    }

    /**
     * 函数用途描述：判断来源设备类型
     * @date: 2017年7月4日
     * @author:
     * @param:
     * @return:
     */
    private function getDeviceType()
    {
        //全部变成小写字母
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $type = 'other';
        //分别进行判断
        if (strpos($agent, 'iphone') || strpos($agent, 'ipad')) {
            return 1;
        }

        if (strpos($agent, 'android')) {
            return 2;
        }
    }

    /**
     * 函数用途描述：将最近一个月的已完成的测量任务的更新时间重置为当前时间
     * @date: 2017年7月27日
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function update_measure_time()
    {
        //获取前一个月的时间错
        $update_time = strtotime("-1 month");
        //查询更新时间在一个月以内的测量任务
        $save['updatetime'] = time() * 1000;


        $datas = M('jk_measuring_tasks')->where("updatetime>" . $update_time * 1000)->save($save);

        if ($datas) {
            $len = M('jk_measuring_tasks')->where("updatetime>" . $update_time * 1000)->count();
            echo 'success' . $len;
        } else {
            echo 'shibai';

        }
    }

    /**
     * 函数用途描述：将已完成的测量任务更改状态
     * @date: 2018年1月29日
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function update_measure_status($projectid = '21')
    {
        //获取前一个月的时间错
        $update_time = strtotime("-1 month");
        //查询更新时间在一个月以内的测量任务
        //$save['updatetime']=time()*1000;
        if ($projectid) {
            $map['projectid'] = $projectid;
        }
        $map['_string'] = "status !=2 and updatetime>" . $update_time * 1000;
        $datas = M('jk_measuring_tasks')->order('createtime DESC')->where($map)->select();
        $count = 0;
        if ($datas) {
            foreach ($datas as $v) {
                //查询出所有属于该任务的测量数据
                $info = M("jk_check_point")->where("tid='" . $v['tid'] . "' and type=0")->select();

                //遍历该任务的检查项，分别计算对应合格率和进度
                $option_arr = explode(",", $v['optionid']);
                $point_arr = explode(",", $v['pointnum']);
                $newarr = array();//用该数组来存储合格率和进度
                $total_arr = array();
                foreach ($option_arr as $kk => $option) {
                    foreach ($info as $vv) {
                        if ($vv['level'] == 1) {//集团工程部
                            if ($vv['inspect'] == $option) {
                                $total_arr['totalnum_1'] += $vv['totalnum'];
                            }
                        } elseif ($vv['level'] == 2) {//监理单位
                            if ($vv['inspect'] == $option) {
                                $total_arr['totalnum_2'] += $vv['totalnum'];
                            }
                        } elseif ($vv['level'] == 3) {//施工单位
                            if ($vv['inspect'] == $option) {
                                $total_arr['totalnum_3'] += $vv['totalnum'];
                            }
                        }
                    }
                }
                //计算总进度
                $totalnum = getSumPoint($v['pointnum']);
                $x = $total_arr['totalnum_2'] * 100 / $totalnum;
                $y = $total_arr['totalnum_1'] * 100 / $totalnum;
                $z = $total_arr['totalnum_3'] * 100 / $totalnum;

                if ($x >= 70 && $y >= 30 && $z >= 100) {//整改单位100，监理单位70，
                    $save['status'] = 2;
                    $save['updatetime'] = time() * 1000;

                    $r = M('jk_measuring_tasks')->where("tid='" . $v['tid'] . "'")->save($save);
                    if ($r) {
                        $count++;
                    }
                }
            }
            echo 'success' . $count;
        }
    }

    /**
     * 函数用途描述：将最近两个月的回复数据加上的更新时间
     * @date: 2017年7月27日
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function update_board_time()
    {
        //获取前一个月的时间错
        $update_time = strtotime("-2 month");
        //查询更新时间在一个月以内的测量任务
        $save['update_time'] = time() * 1000;


        $datas = M('jk_problm_board')->where("create_time>" . $update_time * 1000)->save($save);

        if ($datas) {
            $len = M('jk_problm_board')->where("create_time>" . $update_time * 1000)->count();
            echo 'success' . $len;
        } else {
            echo 'shibai';

        }
    }

    /**
     * 函数用途描述：将最近一个月的更新时间重置为当前时间
     * @date: 2017年7月27日
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function update_problem_time()
    {
        //获取前一个月的时间错
        $update_time = strtotime("-1 month");
        //查询更新时间在一个月以内的测量任务
        $save['update_time'] = time() * 1000;


        $datas = M('jk_program')->where("update_time>" . $update_time * 1000)->save($save);

        if ($datas) {
            $len = M('jk_program')->where(" update_time>" . $update_time * 1000)->count();
            echo 'success' . $len;
        } else {
            echo 'shibai';

        }
    }

    /**
     * 函数用途描述：判断该项目是否开启分户验收
     * @date: 2017年7月27日
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function is_check($proID = 21, $etx = 0)
    {
        //查看该项目是否开启分户验收
        $ret['is_check'] = M('jk_project')->where("id=" . $proID)->getField('ischeck');
        if ($ret['is_check'] == 1 && $etx == 1) {

            $houseImages = M('picture')->where("projectid=$proID AND type='house'")->field('id,projectid,type,path,target_id,create_time')->select();
            $ret['houseImages'] = $houseImages;

            //检查批次数据
        }

        echo json_encode($ret);
    }

    /**
     * 函数用途描述：创建测试问题数据
     * @date: 2017年9月11日
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function add_test_problem($proID = 21)
    {
        //查看该项目是否开启分户验收
        $newArr = M('jk_acprogram')->find();

        unset($newArr['problem_id']);
        for ($i = 0; $i <= 20; $i++) {
            M('jk_acprogram')->add($newArr);

        }
        echo M()->getLastSql();
        echo M('jk_acprogram')->count();
    }

    /**
     * 函数用途描述：创建测试问题数据
     * @date: 2017年10月20日
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function update_measure()
    {
        //查询不合格点数为0，但状态不为合格的记录
        $map['nonum'] = 0;
        $map['is_out_range'] = array('neq', 0);
        $save['is_out_range'] = 0;
        $data = M('jk_check_point')->where($map)->save($save);
        dump($data);

    }

    /**
     * 函数用途描述：将挂错的楼栋返回原样
     * @date: 2017年10月20日
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function update_build_code()
    {
        $where['pid'] = 0;
        $where['status'] = 1;
        $where['masterCode'] = array('neq', '');
        $where['projectid'] = 21;
        $result = M('jk_floor')->where($where)->select();
        //遍历楼栋数据
        foreach ($result as $v) {
            //如果有两个相同编码的则不管
            $map = array();
            $map['masterCode'] = $v['masterCode'];
            $bulid = M('jk_floor')->where($map)->select();
            if (count($bulid) == 1) {
                //找出楼栋所属项目并绑定
                $ParentCode = M('jk_stage')->where("StagesCode='" . $bulid[0]['StagesCode'] . "'")->getField("ParentCode");
                $projectid = M('jk_project')->where("ProjectNumber='" . $ParentCode . "'")->getField('id');

                if ($projectid) {
                    //dump($data);
                    $data['projectid'] = $projectid;
                    $res = M('jk_floor')->where("id=" . $bulid[0]['id'])->save($data);
                    //echo $res;
                } else {
                    echo $v['title'] . "、";
                }

            } else {
                //删除测试项目下的该编码
                $save['masterCode'] = '';
                $save['status'] = -1;
                $res = M('jk_floor')->where("id=" . $v['id'])->save($save);

            }
        }
    }

    /**
     * 函数用途描述：将历史测量任务对应的楼栋名改为现在的楼栋名并更新时间
     * @date: 2017年10月20日
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function update_measure_build()
    {
        //搜索所有未完成的测量任务
        $map['status'] = array('neq', 2);
        $data = M('jk_measuring_tasks')->where($map)->select();
        $count = 0;
        foreach ($data as $v) {
            //分割每一条的point
            $points = explode('、', $v['point']);
            //查看pointid对应的最上级楼栋名并比较
            $new_point = get_build_name($v['pointid']);

            if (($new_point != $points[0]) && $new_point != null) {//如果不相等，更新楼栋名
                $count++;
                $points[0] = $new_point;
                //dump($points);
                $save['point'] = implode($points, '、');
                M('jk_measuring_tasks')->where("tid='" . $v['tid'] . "'")->save($save);
                //echo $save['point'];
                //$count++;die;
            }

        }
        echo $count;
    }

    /**
     * 函数用途描述：将历史测量任务添加ID并更新时间
     * @date: 2017年10月20日
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function update_measure_buildID()
    {
        //搜索所有未完成的测量任务
        //$map['status']   = array('neq',2);
        $map['_string'] = "build_id is null or build_id=''";
        $data = M('jk_measuring_tasks')->where($map)->select();
        $count = 0;
        foreach ($data as $v) {
            //查看pointid对应的最上级楼栋名并比较
            $build_id = get_build_id($v['pointid']);
            $save['build_id'] = $build_id;

            $save['updatetime'] = time() * 1000;
            M('jk_measuring_tasks')->where("tid='" . $v['tid'] . "'")->save($save);

            $count++;


        }
        echo $count;
    }

    /**
     * 函数用途描述：将历史巡查任务绑定的施工单位更改为现在的楼栋施工单位
     * @date: 2017年10月20日
     * @author: tanjiewen
     * @param:ownid:项目ID
     * @return:
     */
    public function update_problem_build($ownid = '')
    {
        if ($ownid) {
            $map['ownid'] = $ownid;
        }
        $count1 = 0;
        $count2 = 0;
        //$map['status'] = array('neq',1);
        $data = M('jk_program')->where($map)->field('status,init_id,target_id,project_ids')->select();
        foreach ($data as $v) {
            //获取楼栋对应的施工单位ID
            $save = array();
            $floors = explode(',', $v['project_ids']);
            $cid = M('jk_floor')->where("id='" . $floors[0] . "'")->getField('cid');
            if ($cid != $v['target_id']) {
                $count1++;
                $save['target_id'] = $cid;
                //已闭合的任务不修改更新时间，免得影响闭合率
                if ($v['status'] == 1 || $v['status'] == 3) {

                } else {
                    $save['update_time'] = time() * 1000;
                }
                M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($save);
            } else {
                $count2++;
            }
        }
        echo $count1 . "  " . $count2;
    }

    /**
     * 函数用途描述：将历史问题添加更新时间和楼栋ID
     * @date: 2017年11月2日
     * @author: tanjiewen
     * @param:ownid:项目ID
     * @return:
     */
    public function update_problem_buildID($ownid = '')
    {
        if ($ownid) {
            $map['ownid'] = $ownid;
        }
        $count = 0;
        $map['_string'] = "build_id is null";
        $data = M('jk_program')->where($map)->field('init_id,target_id,project_ids')->select();
        foreach ($data as $v) {
            //获取楼栋对应的施工单位ID
            $floors = explode(',', $v['project_ids']);
            $save['build_id'] = $floors[0];
            $save['update_time'] = time() * 1000;
            M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($save);
            $count++;
        }
        echo $count;
    }

    /**
     * 函数用途描述：将授权错误绑定的施工单位、监理单位取消掉项目
     * @date: 2017年10月20日
     * @author: tanjiewen
     * @param:ownid:项目ID
     * @return:
     */
    public function update_member_auth()
    {
        $uids = M('member')->where('status=1')->getField('uid', true);
        foreach ($uids as $v) {
            //查看是否有多个授权
            $group_ids = M('auth_group_access')->where('uid=' . $v)->select();
            if (count($group_ids) == 2) {
                //查看是否同时有施工单位/监理单位->工程部
                $cate1 = 0;//工程部
                $cate2 = 0;//施工单位，监理单位
                $del_ids = array();//记录删除的id
                foreach ($group_ids as $vv) {
                    $cate = M("auth_group")->where("id=" . $vv['group_id'])->getField('cate');
                    if ($cate > 10) {
                        $cate = M("auth_group")->where("id=" . $cate)->getField('cate');
                    }

                    if ($cate == 1) {
                        $cate1 = 1;
                        $del_ids[] = $vv['group_id'];
                    } elseif ($cate == 2 || $cate == 3) {
                        $cate2 = 1;
                    }
                }
                //如果都满足,则删除项目权限
                if ($cate1 == 1 && $cate2 == 1) {
                    $name = M('member')->where('uid=' . $v)->getField('nickname');
                    echo $name . "  ";
                    $map['uid'] = $v;
                    $map['group_id'] = array('in', $del_ids);
                    M('auth_group_access')->where($map)->delete();

                }
            }
        }
    }

    /**
     * 函数用途描述：将授权错误绑定的施工单位、监理单位取消掉项目
     * @date: 2017年11月13日
     * @author: tanjiewen
     * @param:ownid:项目ID
     * @return:
     */
    public function update_floor_pic($projectid = '123')
    {
        $floors = M('jk_floor')->where("status=1 and projectid='" . $projectid . "'")->select();
        foreach ($floors as $floor) {
            //如果有图纸
            if ($floor['imgpath'] && $floor['imgid']) {
                //判断picture表是否有对应记录
                $has = M('picture')->where("id='" . $floor['imgid'] . "'")->find();
                if (!$has) {//如果没有就恢复
                    $data['id'] = $floor['imgid'];
                    $data['projectid'] = $projectid;
                    $data['type'] = 'local';
                    $data['path'] = $floor['imgpath'];
                    $data['status'] = 1;
                    $data['create_time'] = time() * 1000;
                    $data['update_time'] = time() * 1000;
                    $data['target_id'] = $floor['imgid'];
                    $res = M('picture')->add($data);
                    if ($res)
                        echo 'add';
                } else {
                    echo 'has';
                }
            }
        }
    }

    /**
     * 函数用途描述：将授权错误绑定的施工单位、监理单位取消掉项目
     * @date: 2017年11月13日
     * @author: tanjiewen
     * @param:ownid:项目ID
     * @return:
     */
    public function update_problem_status($ownid = '')
    {
        if ($ownid) {
            $map['ownid'] = $ownid;
        }
        $count = 0;
        $map['type'] = 1;
        $map['status'] = 0;

        $data = M('jk_program')->where($map)->select();
        //echo M()->getLastSql();
        foreach ($data as $v) {
            //获取楼栋对应的施工单位ID
            if (strpos($v['info'], '已超过') !== false) {
                //echo '包含';
            } else {
                //echo $v['info']."-";
                $save['status'] = 3;
                $save['update_time'] = time() * 1000;
                M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($save);
                $count++;
            }

        }
        echo $count;
    }

    /**
     * 函数用途描述：更新实测实量审核中->已合格
     * @date: 2017年11月13日
     * @author: tanjiewen
     * @param:ownid:项目ID
     * @return:
     */
    public function update_measure_problem_status($ownid = '')
    {
        if ($ownid) {
            $map['ownid'] = $ownid;
        }
        $count = 0;
        $map['type'] = 1;
        $map['status'] = 2;

        $data = M('jk_program')->where($map)->select();
        //echo M()->getLastSql();
        foreach ($data as $v) {
            //查看当前问题有多少回复
            $num = M('jk_problm_board')->where("problem_id='" . $v['init_id'] . "'")->count();
            //echo M()->getLastSql();

            if ($num >= 3) {
                $save['status'] = 1;

                $save['update_time'] = time() * 1000;
                M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($save);
                $count++;
            }


        }
        echo $count;
    }

    /**
     * 函数用途描述：更新实测实量待整改->各状态
     * @date: 2017年11月13日
     * @author: tanjiewen
     * @param:ownid:项目ID
     * @return:
     */
    public function update_measure_problem_status1($ownid = '')
    {
        if ($ownid) {
            $map['ownid'] = $ownid;
        }
        $count = 0;
        $map['type'] = 1;
        //$map['status'] = 0;

        $data = M('jk_program')->where($map)->select();
        //echo M()->getLastSql();
        foreach ($data as $v) {
            //查看当前问题有多少回复
            $num = M('jk_problm_board')->where("problem_id='" . $v['init_id'] . "'")->count();
            //echo M()->getLastSql();

            if ($num == 1 && $v['status'] != 2) {
                $save['status'] = 2;

                $save['update_time'] = time() * 1000;
                M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($save);
                $count++;
            } elseif ($num == 2 && $v['status'] != 4) {
                $save['status'] = 4;

                $save['update_time'] = time() * 1000;
                M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($save);
                $count++;
            } elseif ($num >= 3 && $v['status'] != 1) {
                $save['status'] = 1;

                $save['update_time'] = time() * 1000;
                M('jk_program')->where("init_id='" . $v['init_id'] . "'")->save($save);
                $count++;
            }
        }
        echo $count;
    }

    /**
     * 函数用途描述：处理历史数据，增加超时标志
     * @date: 2018年1月23日
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function update_over()
    {
        $map['_string'] = 'is_over IS  NULL and (status=1 OR status=3)';
        $map['type'] = 0;
        $datas = M('jk_program')->where($map)->select();
        $count1 = $count2 = 0;
        foreach ($datas as $data) {
            $endtime = $data['create_time'] + 60 * 60 * 24 * 1000 * (int)$data['limit_time'];
            if ($data['update_time'] < $endtime) {
                $save['is_over'] = 0;
                $count1++;
            } else {
                $save['is_over'] = 1;
                $count2++;
            }
            M('jk_program')->where("init_id=" . $data['init_id'])->save($save);

        }
        echo "未超时:" . $count1 . "  已超时：" . $count2;
    }
}/* class end */


?>