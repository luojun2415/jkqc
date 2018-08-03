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
//use Admin\Builder\AdminTreeListBuilder;

//use Think\Model;
use JKProgram;

//use Think\Template;

//use JKProgram\excel;
require(dirname(dirname(__FILE__)) . "/excel/PHPExcel.php");
require(dirname(dirname(__FILE__)) . "/excel/PHPExcel/Reader/Excel5.php");

require(dirname(dirname(__FILE__)) . "/excel/PHPExcel/IOFactory.php");

/**
 * Class ShopController
 *
 * @package Admin\controller
 * @luoj
 */
class JKProgramController extends AdminController
{

    public static $proId;

    protected $shopModel;

    protected $shop_configModel;

    protected $shop_categoryModel;

    private $ser_url;
    function _initialize()
    {

        if (!IS_ROOT) {
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

        $this->ser_url=$_SERVER["SERVER_ADDR"].':'.$_SERVER["SERVER_PORT"];
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
    {

        $id = I('get.id', 0, 'intval');
        if ($id) {
            $_SESSION['proId'] = $id;
            $_SESSION['proId1'] = $id;
        } else {
            if ($_SESSION['proId1'] != "") {
                $_SESSION['proId'] = $_SESSION['proId1'];
                $_SESSION['proId1'] = "";
            }
        }
        //接受分期编码
        $stageCode = I('get.stageCode');
        //echo $stageCode;die;
        if ($stageCode) {
            $_SESSION['stageCode'] = $stageCode;
            //是否加上分期条件
            if ($stageCode != 'nocode') {
                $map['StagesCode'] = array(
                    'eq',
                    $stageCode
                );
                $stageName = M('jk_stage')->where("StagesCode='" . $stageCode . "'")->getField('StagesName');

                $this->assign('StageName', $stageName);
            } else {
                $this->assign('StageName', '暂无分期');
            }
        } else {
            $stageCode = $_SESSION['stageCode'];
            //是否加上分期条件
            if ($stageCode != 'nocode') {
                if ($stageCode) {
                    $map['StagesCode'] = array(
                        'eq',
                        $stageCode
                    );
                    $stageName = M('jk_stage')->where("StagesCode='" . $stageCode . "'")->getField('StagesName');
                    $this->assign('StageName', $stageName);
                } else {
                    $this->assign('StageName', '所有分期');
                }

            } else {
                $this->assign('StageName', '暂无分期');
            }
        }

        $map['status'] = array(
            'gt',
            -1
        );
        $map['pid'] = array(
            'eq',
            0
        );

        $map['projectid'] = $_SESSION['proId'];
        $this->sysMDMData($map['projectid']);
        $floordata = M('jk_floor')->where($map)->order('title,sort ASC')->select();
        //echo M()->getLastSql();
        $this->assign('floor_count', count($floordata));
        // echo M()->getLastSql();die;
        foreach ($floordata as &$v) {

            if ($v['examine'] == 0) {
                $v['examine'] = '未审核';
            } else if ($v['examine'] == 1) {
                $v['examine'] = '审核中';
            } else if ($v['examine'] == 2) {
                $v['examine'] = '已通过';
            }
            $v['StagesName'] = M('jk_stage')->where("StagesCode='" . $v['StagesCode'] . "'")->getField('StagesName');
        }

        $this->assign('floordata', $floordata);
        $this->display('/JKProgram@JKProgram/shopCategory');
    }

    /**
     * 楼栋排序
     * 2017年6月28日11:42:02
     * yxch
     * */
    public function savesorts()
    {
        $changgeid = $_POST['changgeid'];
        $changesort = $_POST['changesort'];
        $data['sort'] = $changesort;

        $res = M('jk_floor')->where("id = '$changgeid'")->save($data);
        if ($res) {
            $result['status'] = 1;
            $result['msg'] = "修改排序成功";
        } else {
            $result['status'] = 0;
            $result['msg'] = "修改排序失败";
        }
        $this->ajaxReturn($result);
    }

    /**
     * 进入某个楼栋
     */
    public function selectfloor($id, $name)
    {

        //储存楼栋id
        $_SESSION['selectfloorid'] = $id;
        $_SESSION['is_examine'] = 1;
        if (M('jk_floor')->where('id=' . $id)->getField('examine') != 0) {
            $_SESSION['is_examine'] = 1;
        } else {
            $_SESSION['is_examine'] = 0;
        }


        //判断是否是历史楼栋同步的数据
//         $isHis = M('jk_floor_h')->where('hid='.$id)->field('hid,code')->find();
//         //历史楼栋的房间数据有三种情况： ERP有 工程有 和 ERP有 工程没有  以ERP为准；ERP没有 工程有  按正常业务流程走。
//         if($isHis){//是历史数据，但有两种情况：已做楼栋映射；未做楼栋映射
//             if($isHis['code']){//楼栋编码已经映射
//                 //MDM数据中间表是否有对应楼栋的房间信息
//                 $BN=$isHis['code'];
//                 $count = M('jk_room_m')->where("BUILDNUMBER='$BN'")->count('RoomNumber');
//                 dump($count);
//                 $sta=microtimeStr();
//                 dump(microtimeStr());
//                 if($count){//MDM有房间数据，已MDM房间信息为准

//                     $rooms  = M('jk_room_mdm')->where('build_id='.$id)->field('id,RoomNumber,BuildNumber,UnitNO,AbsolutelyFloor,RoomNO')->select();
//                     if($rooms){//处理房间编码对应
//                         //生成单元楼层房间可识别编码
//                         $data=array();
//                         foreach ($rooms as $room){
//                             $room['Mdmid']=$room['BuildNumber']."_".$room['UnitNO']."_"
//                                 .$room['AbsolutelyFloor']."_".$room['RoomNO'];
//                             $data[]['id']=$room['id'];
//                             $data[]['Mdmid']=$room['Mdmid'];

//                         }
//                         $ret=M('jk_room_mdm')->save($data);
//                         dump($ret.M('jk_room_mdm')->_sql());
//                         //处理mdm历史房间数据，
//                         $sql = "UPDATE `irosn_jk_room_m` m,`irosn_jk_room_mdm` mdm SET mdm.RoomNO=m.ROOMNO
//                         WHERE m.ROOMNO=mdm.RoomNO AND m.UNITNO=mdm.UnitNO AND m.ABSOLUTELYFLOOR=mdm.AbsolutelyFloor
//                         AND m.BUILDNUMBER=mdm.BuildNumber AND m.BUILDNUMBER='$BN'";
//                         $Model = new Model(); // 实例化一个model对象 没有对应任何数据表
//                         dump($sql);
//                         //$Model->execute($sql);

//                         dump("last".(microtimeStr()-$sta));
//                     }
//                 }
//             }
//             else{
//                 //更新楼栋历史数据处理状态，暂时将楼栋修改为已审核，防止用户继续生成房间信息
//                 M('jk_floor')->where('id='.$id)->save(array('examine'=>2));
//             }
//         }
        $sta = microtimeStr();
        js_log($sta);
        //先查询出该楼栋下的所有子级
        $proId = M('jk_floor')->where('id=' . $id)->getField('projectid');
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
            f.imgpath')->order('f.sort')->select();
//         dump("last:".(microtimeStr()-$sta));
        js_log('cost:'.(microtimeStr()-$sta));
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

        $this->assign('arr_floor', $data);
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
    public function saveflooroption()
    {
        if (IS_POST) {
            $id = $_POST['ids'];
            $ids = explode(',', $id);
            // $proId=$_SESSION['proId'];
            if ($_SESSION['proId1'] != "")
                $proId = $_SESSION['proId1'];
            else
                $proId = $_SESSION['proId'];
            //echo $proId;die;
            $oid = $_POST['oid'];
            if (!$oid) {
                $this->error('未选择实测项');
            }
            $map['project_id'] = $proId;
            $map['measure_id'] = $oid;
            $map['floor_id'] = array('in', $ids);

            $infos = M('jk_measure_image')->where($map)->field('floor_id')->select();


            if (is_array($infos)) {
                foreach ($infos as $v) {
                    $inIds[] = $v['floor_id'];
                }
                $outIds = array_diff($ids, $inIds);
//                 $this->error('in'.json_encode($outIds));
            } else
                $outIds = $ids;
            //$this->error(json_encode($outIds));
            $data['project_id'] = $proId;
            $data['measure_id'] = $oid;

            $data['imgid'] = $_POST['mapid'];;
            $data['imgurl'] = coverIds2Path($data['imgid']);
            $data['createtime'] = microtimeStr();
            $data['updatetime'] = $data['createtime'];
            foreach ($outIds as $v) {
                $data['floor_id'] = $v;
                M('jk_measure_image')->add($data);
            }
            if ($inIds) {
                $map['floor_id'] = array('in', $inIds);
                M('jk_measure_image')->where($map)->save(array('imgid' => $data['imgid'], 'imgurl' => $data['imgurl']));

            }
            //修改图片type
            M('Picture')->where("id=" . $data['imgid'])->save(array('type' => 'detail'));
            $this->success('操作完成');
        }

    }

    /**
     * 函数用途描述：新版保存实测图信息
     * @date: 2017年3月18日 下午9:25:37
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function saveflooroption1()
    {
        if (IS_POST) {
            $id = $_POST['ids'];
            if (!$id) {
                $this->error('未勾选楼层信息');
            }
            $id = substr($id, 0, -1);
            $ids = explode(',', $id);
            // $proId=$_SESSION['proId'];
            if ($_SESSION['proId1'] != "")
                $proId = $_SESSION['proId1'];
            else
                $proId = $_SESSION['proId'];
            //echo $proId;die;
            $oid = $_POST['oid'];
            if (!$oid) {
                $this->error('未选择实测项');
            }
            if (!$_POST['mapid']) {
                $this->error('未选择实测图纸');
            }

            $map['project_id'] = $proId;
            $map['measure_id'] = $oid;
            $map['floor_id'] = array('in', $ids);

            $infos = M('jk_measure_image')->where($map)->field('floor_id')->select();


            if (is_array($infos)) {
                foreach ($infos as $v) {
                    $inIds[] = $v['floor_id'];
                }
                $outIds = array_diff($ids, $inIds);
                //                 $this->error('in'.json_encode($outIds));
            } else
                $outIds = $ids;
            //$this->error(json_encode($outIds));
            $data['project_id'] = $proId;
            $data['measure_id'] = $oid;

            $data['imgid'] = $_POST['mapid'];
            $data['imgurl'] = coverIds2Path($data['imgid']);
            $data['createtime'] = microtimeStr();
            $data['updatetime'] = $data['createtime'];
            foreach ($outIds as $v) {
                $data['floor_id'] = $v;
                M('jk_measure_image')->add($data);
            }
            if ($inIds) {
                $map['floor_id'] = array('in', $inIds);
                M('jk_measure_image')->where($map)->save(array('imgid' => $data['imgid'], 'imgurl' => $data['imgurl']));

            }
            //修改图片type
            M('Picture')->where("id=" . $data['imgid'])->save(array('type' => 'detail'));
            $this->success('操作完成');
        }

    }

    /**
     * 函数用途描述：ajax新版保存实测图信息
     * @date: 2017年6月03日 晚上23:25:37
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function ajaxsaveflooroption()
    {

        $id = $_POST['ids'];
        if (!$id) {
            $this->error('未勾选楼层信息');
        }

        $id = substr($id, 0, -1);

        $ids = explode(',', $id);

        // $proId=$_SESSION['proId'];
        if ($_SESSION['proId1'] != "")
            $proId = $_SESSION['proId1'];
        else
            $proId = $_SESSION['proId'];
        //echo $proId;die;
        $oid = $_POST['oid'];
        if (!$oid) {
            $this->error('未选择实测项');
        }

        if (!$_POST['mapid']) {
            $this->error('未选择实测图纸');
        }

        $map['project_id'] = $proId;
        $map['measure_id'] = $oid;
        $map['floor_id'] = array('in', $ids);

        $infos = M('jk_measure_image')->where($map)->field('floor_id')->select();


        if (is_array($infos)) {
            foreach ($infos as $v) {
                $inIds[] = $v['floor_id'];
            }
            $outIds = array_diff($ids, $inIds);
            //$this->error('in'.json_encode($outIds));
        } else {
            $outIds = $ids;
        }
        //$this->error(json_encode($outIds));
        $data['project_id'] = $proId;
        $data['measure_id'] = $oid;

        $data['imgid'] = $_POST['mapid'];
        $data['imgurl'] = coverIds2Path($data['imgid']);
        $data['createtime'] = microtimeStr();
        $data['updatetime'] = $data['createtime'];
        foreach ($outIds as $v) {
            $data['floor_id'] = $v;
            M('jk_measure_image')->add($data);
        }
        if ($inIds) {
            $map['floor_id'] = array('in', $inIds);
            M('jk_measure_image')->where($map)->save(array('imgid' => $data['imgid'], 'imgurl' => $data['imgurl']));

        }
        //修改图片type
        M('Picture')->where("id=" . $data['imgid'])->save(array('type' => 'detail'));


        echo '1';

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

        if (empty($id)) {
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

    /**
     * 函数用途描述：楼层实测图纸
     * @date: 2017年3月16日 下午4:02:18
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function surfloor1($measureid = 0)
    {

        if ($measureid) {
            //获取当前项目id
            if ($_SESSION['proId1'] != "")
                $proId = $_SESSION['proId1'];
            else
                $proId = $_SESSION['proId'];
            $where['project_id'] = $proId;//设置项目id的条件
            $where['measure_id'] = $measureid;//设置项目id的条件
            $id = $_SESSION['selectfloorid'];
            //先查询出该楼栋下的所有子级

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
            f.imgpath')->order('f.sort')->select();
//         dump("last:".(microtimeStr()-$sta));

            $list = array_merge($ulist, $flist, $rlist);

            $floors = M('jk_measure_image')->where($where)->field('floor_id,imgurl')->select();
            $floors_ids = array_column($floors, 'floor_id');
            $floors_imgs = array_column($floors, 'imgurl', 'floor_id');
            foreach ($list as &$v) {
                //判断楼层对应检查项是否有图纸

                if (in_array($v['id'], $floors_ids)) {

                    $v['measureimg'] = $floors_imgs[$v['id']];
                    //echo $v['measureimg'];return;
                    //$v['measureimg']=1;
                }
            }
            $data = list_to_tree($list, 'id', 'pid', '_', $id);
            $this->assign('arr_floor', $data);
            $content = $this->fetch('/JKProgram@JKProgram/measureimglist');
            //             dump($content);
            echo $content;
            return;
        } else {

            $nodelist = D('JKProject/JKProjectSurvey')->getTree(0, 'id,title,sort,pid,status');
            //$this->assign('ids', $id);
            $this->assign('nodeList', $nodelist);
            $this->meta_title = L('实测实量图信息');
            $this->display('/JKProgram@JKProgram/surfloor3');
        }
    }


    /**
     * 函数用途描述：楼层实测图纸
     * @date: 2017年3月16日 下午4:02:18
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function surfloor2($measureid = 0)
    {
        //查询出对应的楼栋信息放入右边
        $id = $_SESSION['selectfloorid'];
        //先查询出该楼栋下的所有子级
        $proId = M('jk_floor')->where('id=' . $id)->getField('projectid');
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
            f.imgpath')->order('f.sort')->select();
//         dump("last:".(microtimeStr()-$sta));

        $list = array_merge($ulist, $flist, $rlist);
        $data = list_to_tree($list, 'id', 'pid', '_', $id);

        $this->assign('arr_floor', $data);

//        $nodelist = D('JKProject/JKProjectSurvey')->getTree(0, 'id,title,sort,pid,status');
        //$this->assign('ids', $id);
//        $this->assign('nodeList', $nodelist);
        $this->meta_title = L('实测实量图信息');
        $this->display('/JKProgram@JKProgram/surfloor2');

    }

    /**
     * 进入某个楼栋
     */
    public function selectfloor2($id, $name)
    {
        //储存楼栋id
        $_SESSION['selectfloorid'] = $id;
        //先查询出该楼栋下的所有子级
        $unit_ids = M('jk_unit_tmp')->where('build_id=' . $id)->field('id')->select();
        $floor_ids = M('jk_floor_tmp')->where('build_id=' . $id)->field('id')->select();
        $room_ids = M('jk_room_tmp')->where('build_id=' . $id)->field('id')->select();
        $all_ids = array_merge($unit_ids, $floor_ids, $room_ids);
        foreach ($all_ids as $v) {
            $ids[] = $v['id'];
        }
        $position_where['status'] = 1;
        $position_where['id'] = array('in', $ids);
        $list = M('jk_floor')->where($position_where)->field('id,title,sort,pid,status,imgpath')->select();
        $data = list_to_tree($list, 'id', 'pid', '_', $id);
        // du/mp($data);
        // echo '<pre>'; var_dump($data); echo '</pre>';die;
        $this->assign('arr_floor', $data);
        $this->assign('floor_pid', $id);
        $this->assign('floor_name', $name);

        $this->display('/JKProgram@JKProgram/selectfloor2');
    }

    public function surfloor3($measureid = 0)
    {

        if ($measureid) {
            //获取当前项目id
            if ($_SESSION['proId1'] != "")
                $proId = $_SESSION['proId1'];
            else
                $proId = $_SESSION['proId'];
            $where['project_id'] = $proId;//设置项目id的条件
            $where['measure_id'] = $measureid;//设置项目id的条件
            $floorid = $_SESSION['selectfloorid'];
            //先查询出该楼栋下的所有子级
            $unit_ids = M('jk_unit_tmp')->where('build_id=' . $floorid)->field('id')->select();
            $floor_ids = M('jk_floor_tmp')->where('build_id=' . $floorid)->field('id')->select();
            $room_ids = M('jk_room_tmp')->where('build_id=' . $floorid)->field('id')->select();
            $all_ids = array_merge($unit_ids, $floor_ids, $room_ids);
            foreach ($all_ids as $v) {
                $ids[] = $v['id'];
            }
            $position_where['status'] = 1;
            $position_where['id'] = array('in', $ids);
            $list = M('jk_floor')->where($position_where)->field('id,title,sort,pid,status,imgpath')->select();

            $floors = M('jk_measure_image')->where($where)->field('floor_id,imgurl')->select();
            $floors_ids = array_column($floors, 'floor_id');
            $floors_imgs = array_column($floors, 'imgurl', 'floor_id');
            //             dump($floors_imgs);
            foreach ($list as &$v) {
                //判断楼层对应检查项是否有图纸
                if (in_array($v['id'], $floors_ids)) {
                    $v['measureimg'] = $floors_imgs[$v['id']];
                    //$v['measureimg']=1;
                }
            }
            $data = list_to_tree($list, 'id', 'pid', '_', $floorid);

            $this->assign('arr_floor', $data);
            $content = $this->fetch('/JKProgram@JKProgram/measureimglist');
            //             dump($content);
            echo $content;
            return;
        } else {
            //查询出对应的楼栋信息放入右边
            $floorid = $_SESSION['selectfloorid'];
            //先查询出该楼栋下的所有子级
            $unit_ids = M('jk_unit_tmp')->where('build_id=' . $floorid)->field('id')->select();
            $floor_ids = M('jk_floor_tmp')->where('build_id=' . $floorid)->field('id')->select();
            $room_ids = M('jk_room_tmp')->where('build_id=' . $floorid)->field('id')->select();
            $all_ids = array_merge($unit_ids, $floor_ids, $room_ids);
            foreach ($all_ids as $v) {
                $ids[] = $v['id'];
            }
            $position_where['status'] = 1;
            $position_where['id'] = array('in', $ids);
            $list = M('jk_floor')->where($position_where)->field('id,title,sort,pid,status,imgpath')->select();
            $data = list_to_tree($list, 'id', 'pid', '_', $floorid);
            $this->assign('arr_floor', $data);

            $nodelist = D('JKProject/JKProjectSurvey')->getTree(0, 'id,title,sort,pid,status');
            //$this->assign('ids', $id);
            $this->assign('nodeList', $nodelist);
            $this->meta_title = L('实测实量图信息');
            $this->display('/JKProgram@JKProgram/surfloor3');
        }
    }

    public function surfloor4($measureid = 0)
    {

        if ($measureid) {
            //获取当前项目id
            if ($_SESSION['proId1'] != "")
                $proId = $_SESSION['proId1'];
            else
                $proId = $_SESSION['proId'];
            $where['project_id'] = $proId;//设置项目id的条件
            $where['measure_id'] = $measureid;//设置项目id的条件
            $floorid = $_SESSION['selectfloorid'];
            //先查询出该楼栋下的所有子级
            $unit_ids = M('jk_unit_tmp')->where('build_id=' . $floorid)->field('id')->select();
            $floor_ids = M('jk_floor_tmp')->where('build_id=' . $floorid)->field('id')->select();
            $room_ids = M('jk_room_tmp')->where('build_id=' . $floorid)->field('id')->select();
            $all_ids = array_merge($unit_ids, $floor_ids, $room_ids);
            foreach ($all_ids as $v) {
                $ids[] = $v['id'];
            }
            $position_where['status'] = 1;
            $position_where['id'] = array('in', $ids);
            $list = M('jk_floor')->where($position_where)->field('id,title,sort,pid,status,imgpath')->select();

            $floors = M('jk_measure_image')->where($where)->field('floor_id,imgurl')->select();
            $floors_ids = array_column($floors, 'floor_id');
            $floors_imgs = array_column($floors, 'imgurl', 'floor_id');
            //             dump($floors_imgs);
            foreach ($list as &$v) {
                //判断楼层对应检查项是否有图纸
                if (in_array($v['id'], $floors_ids)) {
                    $v['measureimg'] = $floors_imgs[$v['id']];
                    //$v['measureimg']=1;
                }
            }
            $data = list_to_tree($list, 'id', 'pid', '_', $floorid);

            $this->assign('arr_floor', $data);
            $content = $this->fetch('/JKProgram@JKProgram/measureimglist');
            //             dump($content);
            echo $content;
            return;
        } else {
            //查询出对应的楼栋信息放入右边
            $floorid = $_SESSION['selectfloorid'];
            //先查询出该楼栋下的所有子级
            $unit_ids = M('jk_unit_tmp')->where('build_id=' . $floorid)->field('id')->select();
            $floor_ids = M('jk_floor_tmp')->where('build_id=' . $floorid)->field('id')->select();
            $room_ids = M('jk_room_tmp')->where('build_id=' . $floorid)->field('id')->select();
            $all_ids = array_merge($unit_ids, $floor_ids, $room_ids);
            foreach ($all_ids as $v) {
                $ids[] = $v['id'];
            }
            $position_where['status'] = 1;
            $position_where['id'] = array('in', $ids);
            $list = M('jk_floor')->where($position_where)->field('id,title,sort,pid,status,imgpath')->select();
            $data = list_to_tree($list, 'id', 'pid', '_', $floorid);
            $this->assign('arr_floor', $data);

            $nodelist = D('JKProject/JKProjectSurvey')->getTree(0, 'id,title,sort,pid,status');
            //$this->assign('ids', $id);
            $this->assign('nodeList', $nodelist);
            $this->meta_title = L('实测实量图信息');
            $this->display('/JKProgram@JKProgram/surfloor4');
        }
    }


    public function createdata($pid)
    {
        $arr = array();
        $map['status'] = array(
            'gt',
            -1
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
                    -1
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
                //$this->success($result . L('_SUCCESS_') . L('_PERIOD_'), U('JKProgram/shopCategory'));
            } else {
                $this->error($title . L('_FAIL_') . L('_EXCLAMATION_') . $this->shop_categoryModel->getError());
            }
        } else {
            //             施工单位信息
            //$proid=$_SESSION['proId'];
            //获取当前项目id
            if ($_SESSION['proId1'] != "")
                $proid = $_SESSION['proId1'];
            else
                $proid = $_SESSION['proId'];
            $pid = M('jk_project')->where("id=$proid")->getField('pid');
            if (!$pid) {
                $this->error('未选择项目对应组织架构', U('jkprogram/goodsedit/', array('id' => $proid)));
            }
            $where = array('status' => array('gt', 0), 'pid' => $pid);
            $list = M('AuthGroup')->where($where)->getField('id,title,cate');
            if (!$list) {
                $this->error('未创建项目下属施工单位', U('auth_manager/index'));
            } else {
                foreach ($list as $k => $v) {
                    if (3 == $v['cate']) {//原施工单位类型
                        continue;
                    }
                    if ($v['cate'] > 10) {//新类型
                        $attr = get_cate_attr('AuthGroup', $v['id'], 3);//获取属性类别

                        if (3 == $attr['cate']) {
                            continue;
                        }
                    }

                    unset($list[$k]);
                }

                if (!$list) {
                    $this->error('未创建项目下属施工单位', U('auth_manager/index'));
                }
            }
            $list = array_column($list, 'title', 'id');
            $list['0'] = '请选择';

            if ($id != 0) {
                $category = $this->shop_categoryModel->find($id);
            }
            $builder = new AdminConfigBuilder();
            $map['id'] = $_SESSION['proId'];
            $find = M('jk_project')->where($map)
                ->field('periods,blocks,batch')
                ->find();
//             $maxPriod = $find['periods'] ? $find['periods'] : 5;
//             $maxBlocks = $find['blocks'] ? $find['blocks'] : 5;
//             $maxBatch = $find['batch'] ? $find['batch'] : 5;
//             $arrPriod = $arrBlock = $arrBatch = array();
//             for ($i = 1; $i <= $maxPriod; $i ++) {
//                 $arrPriod[$i] = $i;
//             }
//             for ($i = 1; $i <= $maxBlocks; $i ++) {
//                 $arrBlock[$i] = $i;
//             }
//             for ($i = 1; $i <= $maxBatch; $i ++) {
//                 $arrBatch[$i] = $i;
//             }

            $builder->keyHidden('id', '')->title('修改楼栋信息')
                ->keyReadOnly('title', '楼栋名称*')
                // ->keyText('sort', '楼栋排序')
                ->keySelect('cid', L('选择施工单位'), '', $list)
                ->keySingleImage('limgid', '楼层默认图片')
                ->keySingleImage('himgid', '每户默认图片')
                //->keySelect('periods', L('所属分期'), '', $arrPriod)
                //->keySelect('blocks', L('所属标段'), '', $arrBlock)
                //->keySelect('batch', L('所属批次'), '', $arrBatch)
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
    public function addMore($id = 0, $title = '', $floorNum = 1, $cid = 0, $stratnum = '', $endnum = '', $imgid = '', $pid = 0,
                            $periods = 0, $blocks = 0, $sort = 999)
    {
        if (IS_POST) {
            if ($title == '' || $title == null) {
                $this->error(L('请输楼栋名称'));
            }
            if (!$cid) {
                $this->error(L('请选择施工单位'));
            }
            if ($endnum <= 0 || intval($endnum) <= 0) {
                $this->error(L('请正确输入楼层数据'));
            }
            if ($title == '室外环境')
                $sort = 1000;
            if ($floorNum == "")
                $floorNum = 1;
            $data = M('jk_floor');
            $info = $data->create();
            $floorNum = intval($floorNum) > 20 ? 20 : intval($floorNum);
            $stratdynum = (int)$_POST['stratdynum'];
            $enddynum = (int)$_POST['enddynum'];
            if ($enddynum - $stratdynum > 10)
                $this->error('单元数不能超过10' . L('_FAIL_'));
            for ($jf = 0; $jf < $floorNum; $jf++) {
                $datainfo = $info;
//                 $datainfo['title'] = $datainfo['title'];
                // $datainfo['projectid'] = $_SESSION['proId'];
                //获取当前项目id
                if ($_SESSION['proId1'] != "")
                    $datainfo['projectid'] = $_SESSION['proId1'];
                else
                    $datainfo['projectid'] = $_SESSION['proId'];
                $datainfo['status'] = 1;
                $datainfo['create_time'] = time();
                $datainfo['update_time'] = time();
                $datainfo['sort'] = 0;
                $datainfo['up_date'] = date('Y-m-d H:i:s', time());
                $datainfo['pid'] = 0;

                if ($pid = $data->add($datainfo)) {
                    // for($i=$stratnum;$i<=$endnum;$i++){

                    // 判断是否有单元
                    $stratdynum = (int)$_POST['stratdynum'];
                    $enddynum = (int)$_POST['enddynum'];
                    if ($enddynum - $stratdynum > 10)
                        $enddynum = $stratdynum + 10;
                    if ($stratdynum > 0 && $endnum > 0) {
                        // 组建值
                        for ($d = $_POST['stratdynum']; $d <= $_POST['enddynum']; $d++) {
                            $datainfo['title'] = $d . "单元";
                            $datainfo['pid'] = $pid;
                            $datainfo['imgpath'] = '';
                            $datainfo['imgid'] = 0;
                            $datainfo['sort'] = $d;
                            if ($did = $data->add($datainfo)) {
                                for ($i = (int)$_POST['stratnum']; $i <= (int)$_POST['endnum']; $i++) {
                                    if ($i == 0) {
                                        continue;
                                    } else {
                                        $datainfo['title'] = $i . "F";
                                        $datainfo['pid'] = $did;
                                        $datainfo['imgpath'] = M('picture')->where(array(
                                            'id' => $_POST['limgid']
                                        ))->getField('path');
                                        $datainfo['sort'] = $i;
                                        $datainfo['imgid'] = $_POST['limgid'];
                                        if ($cid = $data->add($datainfo)) {
                                            for ($j = 1; $j <= (int)$_POST['hnum']; $j++) {
                                                if ($i < 0) {
                                                    $hao = $i * 100 - $j;
                                                } else {
                                                    $hao = $i * 100 + $j;
                                                }
                                                $datainfo['title'] = $hao;
                                                $datainfo['pid'] = $cid;
                                                $datainfo['imgid'] = $_POST['himgid'];
                                                $datainfo['sort'] = $j;
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
                            for ($i = (int)$_POST['stratnum']; $i <= (int)$_POST['endnum']; $i++) {
                                if ($i == 0) {
                                    continue;
                                } else {
                                    $datainfo['title'] = $i . "F";
                                    $datainfo['pid'] = $did;
                                    $datainfo['imgpath'] = M('picture')->where(array(
                                        'id' => $_POST['limgid']
                                    ))->getField('path');
                                    $datainfo['sort'] = $i;
                                    $datainfo['imgid'] = $_POST['limgid'];
                                    if ($cid = $data->add($datainfo)) {
                                        for ($j = 1; $j <= (int)$_POST['hnum']; $j++) {
                                            if ($i < 0) {
                                                $hao = $i * 100 - $j;
                                            } else {
                                                $hao = $i * 100 + $j;
                                            }
                                            $datainfo['title'] = $hao;
                                            $datainfo['pid'] = $cid;
                                            $datainfo['sort'] = $j;
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
                $this->success($title . '添加' . L('_SUCCESS_'), U('JKProgram/shopCategory'));
            } else {
                $this->error($title . '添加' . L('_FAIL_'));
            }
        } else {
//             施工单位信息
            if ($_SESSION['proId1'] != "")
                $proid = $_SESSION['proId1'];
            else
                $proid = $_SESSION['proId'];
            //echo $proid;die;
            $pid = M('jk_project')->where("id=$proid")->getField('pid');
            if (!$pid) {
                $this->error('未选择项目对应组织架构', U('jkprogram/goodsedit/', array('id' => $proid)));
            }
            $where = array('status' => array('gt', 0), 'pid' => $pid);
            $list = M('AuthGroup')->where($where)->getField('id,title,cate');
            //  var_dump($list);die;

            if (!$list) {
                $this->error('未创建项目下属施工单位', U('auth_manager/index'));
            } else {
                foreach ($list as $k => $v) {
                    if (3 == $v['cate']) {//原施工单位类型
                        continue;
                    }
                    if ($v['cate'] > 10) {//新类型
                        $attr = get_cate_attr('AuthGroup', $v['id'], 3);//获取属性类别

                        if (3 == $attr['cate']) {
                            continue;
                        }
                    }

                    unset($list[$k]);
                }

                if (!$list) {
                    $this->error('未创建项目下属施工单位', U('auth_manager/index'));
                }
            }
            $list = array_column($list, 'title', 'id');
            $list['0'] = '请选择';

            $builder = new AdminConfigBuilder();
            $map['id'] = $_SESSION['proId'];
            $find = M('jk_project')->where($map)
                ->field('periods,blocks,batch')
                ->find();
            $maxPriod = $find['periods'] ? $find['periods'] : 5;
            $maxBlocks = $find['blocks'] ? $find['blocks'] : 5;
            $maxBatch = $find['batch'] ? $find['batch'] : 5;
            $arrPriod = $arrBlock = $arrBatch = array();
            for ($i = 1; $i <= $maxPriod; $i++) {
                $arrPriod[$i] = $i;
            }
            for ($i = 1; $i <= $maxBlocks; $i++) {
                $arrBlock[$i] = $i;
            }
            for ($i = 1; $i <= $maxBatch; $i++) {
                $arrBatch[$i] = $i;
            }

            $builder->title('快速新建楼栋')
                ->keyText('title', '楼栋名称*')
                ->keyText('floorNum', '同类楼栋数量*', '默认为1,最多一次创建20栋')
                //  ->keyText('sort', '楼栋排序','默认为999')
                ->keySelect('cid', L('选择施工单位'), '', $list)
                ->keyText('stratdynum', '开始单元数(没有单元请填0,最多一次创建10栋)')
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
     * 函数用途描述: MDM数据联通 更改楼栋信息
     * date: 2017年7月31日12:19:16
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function addMore_new($id = 0, $projectid = '', $floorNum = 1, $cid = 0, $stratnum = '', $endnum = '', $imgid = '',
                                $periods = 0, $blocks = 0, $sort = 999)
    {
        if (IS_POST) {
            if (!$cid) {
                $this->error(L('请选择施工单位'));
            }
            if ($endnum <= 0 || intval($endnum) <= 0) {
                //$this->error(L('请正确输入楼层数据'));
            }

            if ($floorNum == "")
                $floorNum = 1;
            $data = M('jk_floor');
            $info = $data->create();
            $floorNum = intval($floorNum) > 20 ? 20 : intval($floorNum);
            $stratdynum = (int)$_POST['stratdynum'];
            $enddynum = (int)$_POST['enddynum'];
            if ($enddynum - $stratdynum > 20) {
                $this->error('单元数不能超过20' . L('_FAIL_'));
            }

            $datainfo = $info;
            //                 $datainfo['title'] = $datainfo['title'];
            // $datainfo['projectid'] = $_SESSION['proId'];
            //获取当前项目id
            if ($_SESSION['proId1'] != "")
                $datainfo['projectid'] = $_SESSION['proId1'];
            else
                $datainfo['projectid'] = $_SESSION['proId'];
            if ($projectid)
                $datainfo['projectid'] = $projectid;
            $projectid = $datainfo['projectid'];

            $total = M('jk_floor')->where("projectid =$projectid AND status=1")->count();
            if($total>50000){
                $this->error('该楼栋基础信息过多，请联系运维人员！');
            }
            action_log('add_more', 'JKProgram', $id, UID);
//            $this->error('该楼栋基础信息过多，请联系运维人员！'.$total);

            $datainfo['status'] = 1;
            //$datainfo['sort'] = 0;
            $datainfo['up_date'] = date('Y-m-d H:i:s', time());
            //保存当前楼栋的信息
            if ($data->save($datainfo)) {
                // for($i=$stratnum;$i<=$endnum;$i++){
                // 判断是否有单元
                unset($datainfo['id']);
                $datainfo['create_time'] = time();
                $datainfo['update_time'] = time();
                $stratdynum = (int)$_POST['stratdynum'];

                $enddynum = (int)$_POST['enddynum'];
                $startnum = (int)$_POST['stratnum'];
                $endnum = (int)$_POST['endnum'];
                if ($endnum - $startnum > 1000)
                    $endnum = $startnum + 1000;

                if ($enddynum - $stratdynum > 20)
                    $enddynum = $stratdynum + 20;
                $hnum = (int)$_POST['hnum'];
                if($hnum>10000){
                    $hnum=10000;
                }
                $total=$hnum*($endnum - $startnum)*($enddynum - $stratdynum);
                if($total>50000){
                    $this->error( '添加' . L('_FAIL_').'要创建的房间数据过多，请检查传人参数！');
                }
                //获取已有单元的sort值
                $sort = M('jk_floor')->where('status=1 and pid='.$id)->order('sort DESC')->getField('sort');
                if ($stratdynum > 0 && $endnum > 0) {
                    // 组建值
                    for ($d = $stratdynum; $d <= $enddynum; $d++) {

                        $datainfo['title'] = $d . "单元";
                        $datainfo['pid'] = $id;
                        $datainfo['imgpath'] = '';
                        $datainfo['imgid'] = 0;
                        $datainfo['sort'] = $d;
                        if($sort){
                            $datainfo['title'] = $sort+$d . "单元";
                            $datainfo['sort']  = $sort+$d;
                        }
                        if ($did = $data->add($datainfo)) {

//                            楼层一次最多999

                            for ($i = $startnum; $i <= $endnum; $i++) {
                                if ($i == 0) {
                                    continue;
                                } else {
                                    $datainfo['title'] = $i . "F";
                                    $datainfo['pid'] = $did;
                                    $datainfo['imgpath'] = M('picture')->where(array(
                                        'id' => $_POST['limgid']
                                    ))->getField('path');
                                    $datainfo['sort'] = $i;
                                    $datainfo['imgid'] = $_POST['limgid'];
                                    if ($cid = $data->add($datainfo)) {

                                        $max_num = $hnum >= 100 ? 1000 : 100;
                                        for ($j = 1; $j <= $hnum; $j++) {
                                            if ($i < 0) {
                                                $hao = $i * $max_num - $j;
                                            } else {
                                                $hao = $i * $max_num + $j;
                                            }
                                            $datainfo['title'] = $hao;
                                            $datainfo['pid'] = $cid;
                                            $datainfo['imgid'] = $_POST['himgid'];
                                            $datainfo['sort'] = $j;
                                            $datainfo['imgpath'] = M('picture')->where(array(
                                                'id' => $_POST['himgid']
                                            ))->getField('path');
                                            $res = $data->add($datainfo);
                                        }
                                    } // if
                                }
                            } // for添加层结束
                        } else { // 判断是否添加成功
                            $this->error(M()->getLastSql());
                        }
                    } // 循环添加单元结束
                } else {

                    if($sort){
                        $sort++;
                        $datainfo['sort']  = $sort;
                        $datainfo['title'] = $sort.'单元';
                    }else{
                        $datainfo['sort']  = 1;
                        $datainfo['title'] = '1单元';
                    }

                    $datainfo['pid'] = $id;
                    $datainfo['imgpath'] = '';
                    $datainfo['imgid'] = 0;
                    $did = $data->add($datainfo);
                    if ($did) {
                        for ($i = $stratnum; $i <= $endnum; $i++) {
                            if ($i == 0) {
                                continue;
                            } else {
                                $datainfo['title'] = $i . "F";
                                $datainfo['pid'] = $did;
                                $datainfo['imgpath'] = M('picture')->where(array(
                                    'id' => $_POST['limgid']
                                ))->getField('path');
                                $datainfo['sort'] = $i;
                                $datainfo['imgid'] = $_POST['limgid'];
                                if ($cid = $data->add($datainfo)) {
                                    $max_num = $hnum >= 100 ? 1000 : 100;
                                    for ($j = 1; $j <= $hnum; $j++) {
                                        if ($i < 0) {
                                            $hao = $i * $max_num - $j;
                                        } else {
                                            $hao = $i * $max_num + $j;
                                        }
                                        $datainfo['title'] = $hao;
                                        $datainfo['pid'] = $cid;
                                        $datainfo['sort'] = $j;
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

            if ($id) {
                $this->success( '添加' . L('_SUCCESS_'), U('JKProgram/shopCategory', array('id' => $projectid)));
            } else {
                $this->error( '添加' . L('_FAIL_'));
            }
        }
        else {
            if (M('jk_floor')->where('id=' . $id)->getField('examine') != 0) {
                $this->error('该状态不能进行更改房间信息,如需修改请先驳回');
            }
            //施工单位信息
            if ($_SESSION['proId1'] != "")
                $proid = $_SESSION['proId1'];
            else
                $proid = $_SESSION['proId'];
            //echo $proid;die;
            $pid = M('jk_project')->where("id=$proid")->getField('pid');
            if (!$pid) {
                $this->error('未选择项目对应组织架构', U('jkprogram/goodsedit/', array('id' => $proid)));
            }
            $where = array('status' => array('gt', 0), 'pid' => $pid);
            $list = M('AuthGroup')->where($where)->getField('id,title,short_title,cate');
            if (!$list) {
                $this->error('未创建项目下属施工单位', U('auth_manager/index'));
            } else {
                foreach ($list as $k => $v) {
                    $list[$k]['title'] = $v['title'].$v['short_title'];
                    if (3 == $v['cate']) {//原施工单位类型
                        continue;
                    }
                    if ($v['cate'] > 10) {//新类型
                        $attr = get_cate_attr('AuthGroup', $v['id'], 3);//获取属性类别

                        if (3 == $attr['cate']) {
                            continue;
                        }
                    }

                    unset($list[$k]);
                }

                if (!$list) {
                    $this->error('未创建项目下属施工单位', U('auth_manager/index'));
                }
            }
            $list = array_column($list, 'title', 'id');
            $list['0'] = '请选择';
            if ($id != 0) {
                $category = $this->shop_categoryModel->find($id);
            }
            $builder = new AdminConfigBuilder();
            $map['id'] = $proid;
            $find = M('jk_project')->where($map)
                ->field('periods,blocks,batch')
                ->find();
            $maxPriod = $find['periods'] ? $find['periods'] : 5;
            $maxBlocks = $find['blocks'] ? $find['blocks'] : 5;
            $maxBatch = $find['batch'] ? $find['batch'] : 5;
            $arrPriod = $arrBlock = $arrBatch = array();
            for ($i = 1; $i <= $maxPriod; $i++) {
                $arrPriod[$i] = $i;
            }
            for ($i = 1; $i <= $maxBlocks; $i++) {
                $arrBlock[$i] = $i;
            }
            for ($i = 1; $i <= $maxBatch; $i++) {
                $arrBatch[$i] = $i;
            }

            $builder->title('快速新建楼层及房间')
                ->keySelect('cid', L('选择施工单位'), '', $list)
                //->keyText('stratdynum', '开始单元数(没有单元请填0,最多一次创建10栋)')
                ->keyText('stratdynum', '开始单元数')
                ->keyText('enddynum', '结束单元数')
                ->keyInteger('stratnum', '开始楼层数*')
                ->keyInteger('endnum', '结束楼层数*')
                ->keyInteger('hnum', '每层每单元户数*')
                //->keySelect('periods', L('所属分期'), '', $arrPriod)
                //->keySelect('blocks', L('所属标段'), '', $arrBlock)
                //->keySelect('batch', L('所属批次'), '', $arrBatch)
                ->keyHidden('id', '')
                ->keyHidden('projectid', '')
                ->buttonSubmit(U('JKProgram/addMore_new'))
                ->data($category)
                ->buttonBack()
                ->display();
        }
    }

    /**
     * 函数用途描述:  新增房间信息
     * date: 2017年8月10日16:00:16
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function addroom($build_id = 0, $name = '', $title = '', $projectid = '', $pid = 0, $hnum = 0)
    {
        if ($_SESSION['is_examine'] == 1) {
            $this->error('该状态不能进行更改房间信息,如需修改请先驳回');
        }
        if (IS_POST) {
            if ($title == '' || $title == null) {
                $this->error(L('请输房间名称'));
            }


            //拼凑新增楼层的数组
            $data['projectid'] = $projectid;
            $data['title'] = $title;
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['up_date'] = date('Y-m-d H:i:s', time());
            $data['pid'] = $pid;
            $data['status'] = 1;
            $data['imgpath'] = '';
            $data['imgid'] = 0;
            //计算sort
            $sort = M('jk_floor')->where('status=1 and pid=' . $pid)->order('id DESC')->getField('sort');
            $data['sort'] = $sort + 1;

            if ($cid = M('jk_floor')->add($data)) {
                //查看是否已经注册过房间编码
                $is_examine = M('jk_floor')->where('id=' . $build_id)->getField('examine');
                if ($is_examine != 2) {
                    $this->success($title . '添加' . L('_SUCCESS_'), U('JKProgram/selectfloor', array('id' => $build_id, 'name' => $name)));
                } else {

                    //$ids[]=$cid;

                    //$res = $this->generate_register_code($build_id);;
                    //调用延时接口执行生成及注册编码,
                    //$this->doSend($build_id);

                    $this->success($title . '添加' . L('_SUCCESS_'), U('JKProgram/selectfloor', array('id' => $build_id, 'name' => $name)));
// 					if($res['status']==1){
// 						$this->success($title .'添加成功并注册对应房间编码', U('JKProgram/selectfloor', array('id' => $build_id,'name'=>$name )));
// 					}else{
// 						$this->error($res['reason'], U('JKProgram/selectfloor', array('id' => $build_id,'name'=>$name )));
// 					}
                }
            } else {
                $this->error($title . '添加' . L('_FAIL_'));
            }

        } else {

            $builder = new AdminConfigBuilder();
            $builder->title('新建房间');
            //所属单元列表
            $data = M('jk_floor')->where("id=" . $build_id)->field('id,projectid')->find();
            $data['build_id'] = $data['id'];
            $data['name'] = $name;
            $data['pid'] = $pid;
            unset($data['id']);
            $builder->keyText('title', '房间名称')
                ->keyHidden('projectid', '')
                ->keyHidden('build_id', '')
                ->keyHidden('name', '')
                ->keyHidden('pid', '')
                ->data($data)
                //->buttonBack()
                ->buttonSubmit(U('JKProgram/addroom'))
                ->display();
        }
    }

    /**
     * 函数用途描述：延时更新房间信息接口
     * @date: 2017年9月15日 上午9:48:20
     *
     * @author : tanjiewen
     * @param
     *            :
     * @return :
     */
    public function doSend($id = 2651728)
    {

        $url = "http://" . $this->ser_url . "/index.php?s=/admin/JKMdm/generate_register_code/id/" . $id;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);

        $result = curl_exec($curl);

        curl_close($curl);

        //echo $url;
        file_put_contents('ceshi.txt', $result);
        //dump($result);exit;
    }

    /**
     * 函数用途描述：延时审核房间接口
     * @date: 2017年9月15日 上午9:48:20
     *
     * @author : tanjiewen
     * @param
     *            :
     * @return :
     */
    public function do_room_send($id = 2651728)
    {
        file_put_contents('ceshi2333.txt', '1111');
        $url = "http://" . $this->ser_url . "/index.php?s=/admin/JKMdm/main_generate_register_code/id/" . $id;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);

        $result = curl_exec($curl);

        curl_close($curl);
        //echo $url;

        file_put_contents('ceshi2333.txt', $url,FILE_APPEND);
        //dump($result);exit;
    }

    /**
     * 函数用途描述：延时变更房间接口
     * @date: 2017年11月30日 上午11:00:20
     *
     * @author : tanjiewen
     * @param
     *            :
     * @return :
     */
    public function do_room_update($id = 2651728)
    {
        $url = "http://" . $this->ser_url . "/index.php?s=/admin/JKMdm/update_examine_floor/id/" . $id;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);

        $result = curl_exec($curl);

        curl_close($curl);
        //echo $url;
        file_put_contents('ceshi.txt', $result);
        //dump($result);exit;
    }

    /**
     * 函数用途描述: 更改房间信息
     * date: 2017年8月10日16:19:16
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function addfloor($build_id = 0, $name = '', $title = '', $projectid = '', $pid = 0, $hnum = 0, $pre = '')
    {

        if ($_SESSION['is_examine'] == 1) {
            $this->error('该状态不能进行更改房间信息,如需修改请先驳回');
        }
        if (IS_POST) {
            if ($title == '' || $title == null) {
                $this->error(L('请输入楼层名称'));
            }
            if ($hnum == 0 || $hnum == null) {
                $this->error(L('请输入每层户数'));
            }

            //拼凑新增楼层的数组
            $data['projectid'] = $projectid;
            $data['title'] = $title;
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['up_date'] = date('Y-m-d H:i:s', time());
            $data['pid'] = $pid;
            $data['status'] = 1;
            $data['imgpath'] = '';
            $data['imgid'] = 0;
            //计算sort
            $sort = M('jk_floor')->where('status=1 and pid=' . $pid)->order('id DESC')->getField('sort');
            $data['sort'] = $sort + 1;

            if ($cid = M('jk_floor')->add($data)) {
                $max_num = (int)$hnum >= 100 ? 1000 : 100;
                for ($j = 1; $j <= $hnum; $j++) {
                    if ((int)$title < 0) {
                        $hao = (int)$title * $max_num- $j;
                    } else {
                        $hao = (int)$title * $max_num + $j;
                    }
                    if ($pre) {
                        if ($j < 10) {
                            $hao = $pre . '00' . $j;
                        } elseif ($j < 100) {
                            $hao = $pre . '0' . $j;
                        } else {
                            $hao = $pre . $j;
                        }


                    }
                    $data['title'] = $hao;
                    $data['pid'] = $cid;

                    $data['sort'] = $j;
                    $res = M('jk_floor')->add($data);
                    //记录房间ID
                    $ids[] = $res;
                }
                if ($res) {
                    //查看是否已经注册过房间编码
                    $is_examine = M('jk_floor')->where('id=' . $build_id)->getField('examine');
                    if ($is_examine == 0) {
                        $this->success($title . '添加' . L('_SUCCESS_'), U('JKProgram/selectfloor', array('id' => $build_id, 'name' => $name)));
                    } else {
                        // $res = $this->generate_register_code($build_id);;
                        $save['examine'] = 0;
                        $res = M('jk_floor')->where('id=' . $build_id)->save($save);
                        //将新的数据生成编码并注册
                        //$this->doSend($build_id);
                        $this->success($title . '添加' . L('_SUCCESS_'), U('JKProgram/selectfloor', array('id' => $build_id, 'name' => $name)));
//     					if($res){
//     						$this->success($title .'添加成功并注册对应房间编码', U('JKProgram/selectfloor', array('id' => $build_id,'name'=>$name )));
//     					}else{
//     						$this->success($title .'添加成功但未注册对应房间编码', U('JKProgram/selectfloor', array('id' => $build_id,'name'=>$name )));
//     					}
                    }

                } else {
                    $this->error($title . '添加' . L('_FAIL_'));
                }

            } // if
            $this->error($title . '添加' . L('_FAIL_'));
        } else {
            $builder = new AdminConfigBuilder();
            $builder->title('新建楼层');
            //所属单元列表
            $data = M('jk_floor')->where("id=" . $build_id)->field('id,projectid')->find();
            $danyuans = M('jk_floor')->where("status=1 and pid=" . $build_id)->field('id,title,projectid')->select();

            if (count($danyuans) == 0) {
                //如果没有单元，则提示新增单元
                $this->error('请先在楼层配置中的快速新建楼层及房间中新建单元');
            }
            if (count($danyuans) > 1) {

                $list = array_column($danyuans, 'title', 'id');
                $builder->keySelect('pid', L('所属单元'), '', $list);
            } else {

                $data['pid'] = $danyuans[0]['id'];
                $builder->keyHidden('pid', '');
            }
            $data['build_id'] = $data['id'];
            $data['name'] = $name;
            unset($data['id']);
            $builder->keyText('title', '楼层名称')
                ->keyInteger('hnum', '每层户数*')
                ->keyText('pre', '自定义房间前缀（可不填写，用于车位等特殊情况，从001开始）')
                ->keyHidden('projectid', '')
                ->keyHidden('build_id', '')
                ->keyHidden('name', '')
                ->data($data)
                //->buttonBack()
                ->buttonSubmit(U('JKProgram/addfloor'))
                ->display();
        }
    }

    /**
     * 函数用途描述: 给每楼层增加一个房间信息
     * date: 2017年10月23日10:06:16
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function add_floor_room($build_id = 0, $name = '', $title = '', $projectid = '', $pid = 0, $hnum = 0)
    {
        if ($_SESSION['is_examine'] == 1) {
            $this->error('该状态不能进行更改房间信息,如需修改请先驳回');
        }
        if (IS_POST) {
            //给该单元每楼层增加一个房间
            $floors = M('jk_floor')->where('pid=' . $pid)->getField('id', true);
            foreach ($floors as $floor) {
                //拼凑新增房间的数组
                $data['projectid'] = $projectid;
                //该楼层最新的一条房间的数据
                $room = M('jk_floor')->where('status =1 and pid=' . $floor)->order('id DESC')->find();

                if ((int)$title < 0) {
                    $data['title'] = $room['title'] - 1;
                } else {
                    $data['title'] = $room['title'] + 1;
                }
                $data['sort'] = $room['sort'] + 1;

                $data['create_time'] = time();
                $data['update_time'] = time();
                $data['up_date'] = date('Y-m-d H:i:s', time());
                $data['pid'] = $floor;
                $data['status'] = 1;
                $data['imgpath'] = '';
                $data['imgid'] = 0;
                $res = M('jk_floor')->add($data);
                //记录房间ID
                $ids[] = $res;
                if (!res) {
                    $this->error($title . '添加' . L('_FAIL_'));
                }

            }
            if ($res) {
                //查看是否已经注册过房间编码
                $is_examine = M('jk_floor')->where('id=' . $build_id)->getField('examine');
                if ($is_examine == 0) {
                    $this->success($title . '添加' . L('_SUCCESS_'), U('JKProgram/selectfloor', array('id' => $build_id, 'name' => $name)));
                } else {
                    $save['examine'] = 0;
                    $res = M('jk_floor')->where('id=' . $build_id)->save($save);

                    //修改状态为待提交审核
                    //$this->doSend($build_id);
                    $this->success($title . '添加' . L('_SUCCESS_'), U('JKProgram/selectfloor', array('id' => $build_id, 'name' => $name)));
                }

            }
        } else {
            $builder = new AdminConfigBuilder();
            $builder->title('批量新建房间');
            //所属单元列表
            $data = M('jk_floor')->where("id=" . $build_id)->field('id,projectid')->find();
            $danyuans = M('jk_floor')->where("status=1 and pid=" . $build_id)->field('id,title,projectid')->select();

            if (count($danyuans) == 0) {
                //如果没有单元，则提示新增单元
                $this->error('请先在楼层配置中的快速新建楼层及房间中新建单元');
            }
            if (count($danyuans) > 1) {

                $list = array_column($danyuans, 'title', 'id');
                $builder->keySelect('pid', L('所属单元'), '', $list);
            } else {

                $data['pid'] = $danyuans[0]['id'];
                $builder->keyHidden('pid', '');
            }
            $data['build_id'] = $data['id'];
            $data['name'] = $name;
            unset($data['id']);
            $builder
                //->keyText('title', '楼层名称')
                //->keyInteger('hnum', '每层户数*')
                ->keyHidden('projectid', '')
                ->keyHidden('build_id', '')
                ->keyHidden('name', '')
                ->data($data)
                ->buttonSubmit(U('JKProgram/add_floor_room'))
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
            'status' => -1
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
        $rs = M('jk_floor')->where(array(
            'id' => array(
                'in',
                $ids
            )
        ))->save(array(
            'status' => $status
        ));
        if (is_array($ids)) {

            foreach ($ids as $id) {
                $list = M('jk_floor')->where("id>=$id AND id<" . ($id + 10000))->field('id,pid')->select();

                $list = list_to_tree($list, 'id', 'pid', '_', $id);
                $list = tree_to_listwx($list, '_');
                $list = array_column($list, 'id');
                $rs = M('jk_floor')->where(array(
                    'id' => array(
                        'in',
                        $list
                    )
                ))->save(array(
                    'status' => $status
                ));
            }
        } else {
            $list = M('jk_floor')->where("id>=$ids AND id<" . ($ids + 10000))->field('id,pid')->select();

            $list = list_to_tree($list, 'id', 'pid', '_', $ids);

            $list = tree_to_listwx($list, '_');
            $list = array_column($list, 'id');

            $rs = M('jk_floor')->where(array(
                'id' => array(
                    'in',
                    $list
                )
            ))->save(array(
                'status' => $status
            ));
        }


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
        $is_new = intval(!$this->shopModel->where(array(
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
            'status' => -1
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
        !isset($data['SHOP_SCORE_TYPE']) && $data['SHOP_SCORE_TYPE'] = '1';
        !isset($data['SHOP_HOT_SELL_NUM']) && $data['SHOP_HOT_SELL_NUM'] = '10';

        // 读取数据
        $map = array(
            'status' => array(
                'GT',
                -1
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
            if (!$face_code) {
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
        if (!$id) {
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
    public function ProjectProblemList($page = 1, $r = 20)
    {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $_SESSION['proId'] = $id;
            action_log('show_project', 'JkProjcet', $id, UID);
        }
        // $map['ownid'] = $_SESSION['proId'];
        $map['status'] = array('egt', 0);
        //接收时间条件（转换为毫秒）
        $aSearch1 = I('get.usearch1', '') * 1000;
        $aSearch2 = I('get.usearch2', '') * 1000;

        //设置搜索条件
        if (!empty($aSearch1)) {
            $aSearch1 = strtotime(date('Y-m-d', I('get.usearch1', '')).' 00:00:00') * 1000;
            //$map.=" AND o.used_time >= '$aSearch1' ";
            $map['_string'] = "create_time>='$aSearch1' ";
        }
        if (!empty($aSearch2)) {
            $aSearch2 = strtotime(date('Y-m-d', I('get.usearch2', '')).' 23:59:59') * 1000;
            $map['_string'] .= "and create_time<='$aSearch2' ";
        }
        //所属项目
        $ownid = I('get.ownid', '');
        $projectids = get_my_projects();
//      if(!empty($ownid)){
//          if($ownid!='all')
//              $map['ownid'] = array('eq', $ownid);
//          else
//          	$map['ownid'] =  array('in', $projectids);
//      }else{
//      	$map['ownid'] =  array('in', $projectids);
//      }
        //找出对应用户组所属项目
        if ($ownid) {
            $proids = get_select_projects($ownid);
            $map['ownid'] = array('in', $proids);
        } else {
            $map['ownid'] = array('in', $projectids);
        }
        //状态
        $status = I('get.status', '');
        if (!empty($status)) {
            if ($status != 5&&$status!=6)
                $map['status'] = array('eq', $status);
            else if($status == 6){
                $map['is_over'] = 1;
            }
        }
        if ($status == 0)
            $map['status'] = array('eq', $status);
        //问题类型
        $type = I('get.type', '');
        if ($type == null || $type == '')
            $type = 3;
        if (!empty($type) && $type != null) {
            if ($type != 3)
                $map['type'] = array('eq', $type);
        }
        if ($type == 0)
            $map['type'] = array('eq', $type);

        //检查项
//     $option=I('get.option','');
//     if(!empty($option)){
//     	$map['option_id'] = array('eq', $option);
//     }
        $title = I('get.title', '');
        if($title!=''){
            $map['build_id'] = $title;
        }

        //if ($title != '')
        //    $goodsList = M('jk_program')->where($map)->order('create_time desc')->select();
        //else
        $goodsList = M('jk_program')->where($map)->order('create_time desc')->page($page, $r)->select();
        // dump(M()->getLastSql());
        $totalCount = M('jk_program')->where($map)->count();
        $builder = new AdminListBuilder();
        $builder->title('项目问题列表');
        $builder->meta_title = '项目问题列表';

        $newtotalCount = 0;
        $newgoodsList = array();
        //选项问题
        foreach ($goodsList as &$val) {
            //问题选项【检查项】
            $val['id'] = $val['init_id'];
            if ($val['type'] > 0) {
                $option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
            } else
                $option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');
            $val['option_id'] = $option;
            unset($option);

            //楼栋数字截取
            $loudong_j = substr($val['project_ids'], -1);
            if ($loudong_j == ',') {
                $val['project_ids'] = substr($val['project_ids'], 0, -1);
            }

            //楼栋【问题具体位置】
            $floor = M('jk_floor')->where("id in (" . $val['project_ids'] . ")")->field('title')->select();
            $loudong = "";
            foreach ($floor as $value) {
                if ($value['title'] != '')
                    $loudong = $loudong . $value['title'] . '-';
            }

            $changdu = mb_strlen($loudong, 'utf-8');
            $hangye = mb_substr($loudong, 0, $changdu - 1, 'utf-8');
            $val['project_ids'] = $hangye;

            //问题提交人
            $val['authid'] = M('member')->where('uid=' . $val['authid'])->getField('username');

            $val['target'] = M('auth_group')->where('id=' . $val['target_id'])->getField('title');

            //时间处理
            if (strlen($val['create_time']) > 10) {
                $val['create_time'] = substr($val['create_time'], 0, 10);
            }
            if (strlen($val['update_time']) > 10) {
                $val['update_time'] = substr($val['update_time'], 0, 10);
            }

            //所属项目
            $val['other_id'] = M('jk_project')->where('id=' . $val['ownid'])->getField('other_id');
            $val['ownid'] = M('jk_project')->where('id=' . $val['ownid'])->getField('name');

            //状态
            if ($val['status'] == 0) {
                $val['status'] = "待整改";
            } else if ($val['status'] == 1) {
                $val['status'] = "正常已关闭";
            } else if ($val['status'] == 2) {
                $val['status'] = "待审核";
            } else if ($val['status'] == 3) {
                $val['status'] = "强制关闭";
            } else if ($val['status'] == 4) {
                $val['status'] = "待复核";
            }
            if($val['is_over'] == 1){
                $val['status'] = "已超期";
            }
            //if ($floor[0][title] == $title) {
            //     $newgoodsList[] = $val;
            //    $newtotalCount += 1;
            // }
        }
        unset($val);
        //确定当前项目的模板：项目对应的areaID

        $areaID = M('jk_project')->where('id=' . $_SESSION['proId'])->getField('areaID');
        //($_COOKIE['areanum'])
        setcookie('areanum', $areaID);

        $attr['target-form'] = 'ids';
        $attr['href'] = U('exWord');
        $attr['class'] = 'a_jump';
        $builder->button('导出通知单', $attr)->setStatusUrl(U('setGoodsStatus1'));
        $attr['href'] = U('exhuiWord');
        $builder->button('导出通知回复单', $attr);
        $attr['href'] = U('exstopWord');
        $builder->button('导出暂停令', $attr);
        $attr['href'] = U('excontactWord');
        $builder->button('导出联系单', $attr);
        //判断是否是四川项目
        $sichuan = M('jk_project')->where('id=' . $_SESSION['proId'])->getField('areaID');

        if ($sichuan == 2) {
            $attr['href'] = U('exstartWord');
            $builder->button('导出复工单', $attr);
        }
        $builder->setSelectPostUrl(U('JKProgram/ProjectProblemList'));
        //根据项目筛选
        //获取该角色下的所有项目
        $projectwhere['status'] = array('gt', 0);
        if (!IS_ROOT) {
            $projectwhere['id'] = array('in', get_my_projects());
        }
        $list = M('jk_project')->where($projectwhere)->field('id,name')->select();
        //构造项目数组
        $projectArr = array();
        $projectArr[0]['id'] = 'all';
        $projectArr[0]['value'] = '全部';
        $kk = 1;
        foreach ($list as $vvv) {
            $projectArr[$kk]['id'] = $vvv['id'];
            $projectArr[$kk]['value'] = $vvv['name'];
            $kk++;
        }
        // $builder->select(L('项目：'), 'ownid', 'select', L('项目'), '', '', $projectArr);
        $builder->setSelectPostUrl(U('JKProgram/ProjectProblemList') . '&ownid=' . $ownid . "&usearch1=" . ($aSearch1 / 1000));

        //项目筛选
        $builder->buttonModalPopup(U('JKProgram/selectproject'),
            '',
            '根据项目筛选',
            array('data-title' => ('选择项目')));
        //根据类型筛选
        $typeArr = array();
        $typeArr[0]['id'] = '3';
        $typeArr[0]['value'] = '全部';
        $typeArr[1]['id'] = '0';
        $typeArr[1]['value'] = '日常巡查';
        $typeArr[2]['id'] = '1';
        $typeArr[2]['value'] = '实测实量';
        $builder->select(L('问题类型：'), 'type', 'select', L('问题类型'), '', '', $typeArr);
        //根据检查项搜索1.先构建检查项数组

        //根据下拉列表筛选
        $astauts = array(array('id' => 5, 'value' => L('全部')), array('id' => 0, 'value' => L('待整改')), array('id' => 2, 'value' => L('待审核')), array('id' => 4, 'value' => L('待复核')),
            array('id' => 1, 'value' => L('正常已关闭')), array('id' => 3, 'value' => L('强制关闭')), array('id' => 6, 'value' => L('已超期')));

        $builder->select(L('状态：'), 'status', 'select', L('选择状态'), '', '', $astauts);
        //根据下拉列表筛选--楼栋
        //构建最顶级的楼栋列表
        if (!empty($ownid)) {
            if ($ownid != 'all') {
                $floorwhere['projectid'] = array('in', $proids);
                $floorlist = M('jk_floor')->where("status=1 and pid=0")->where($floorwhere)->order('title')->select();
            } else {
                $floorlist = M('jk_floor')->where("1=2")->order('title')->select();//不放置楼栋信息
            }
        } else {
            $floorlist = M('jk_floor')->where("status=1 and pid=0 and projectid='" . $_SESSION['proId'] . "'")->order('title')->select();
        }
        $a = array();
        $a[0]['id'] = '';
        $a[0]['value'] = '全部';
        $i = 1;
        foreach ($floorlist as $topfloor) {
            $a[$i]['id'] = $topfloor['id'];
            $a[$i]['value'] = $topfloor['title'];
            $i++;
        }
        // $builder->setSelectPostUrl(U('JKProgram/ProjectProblemList'))
        $builder->select(L('楼栋：'), 'title', 'select', L('选择楼栋'), '', '', $a);

        //搜索框
        $builder->setSearchPostUrl(U('JKProgram/ProjectProblemList'))
            ->search('时间从', 'usearch1', 'timer', '', '', '', '');
        $builder->search('到', 'usearch2', 'timer', '', '', '', '');
        //列表
        //$builder->keyId('other_id','项目编号')
        $builder->keyText('ownid', '项目名称')
            ->keyText('project_ids', '问题位置')
            ->keyText('option_id', '检查项')
            ->keyText('info', '问题描述')
            ->keyText('target', '整改单位')
            ->keyText('authid', '问题提交人')
            ->keyUpdateTime('create_time', '提交时间')
            ->keyUpdateTime('update_time', '更新时间')
            ->keyText('status', '状态')
            ->keyDoActionEdit('JKProgram/programsedit?init_id=###', '详情');
        // $builder->data($goodsList);
        //  $builder->pagination($totalCount, $r);
        // if ($title != '') {
        //var_dump($newgoodsList);
        //    $builder->data($newgoodsList);
        //    $builder->pagination($newtotalCount, $r);
        //} else {
        $builder->data($goodsList);
        $builder->pagination($totalCount, $r);
        //}
        $builder->display();
    }

    /**
     * 函数用途描述：选择区域
     * @date: 2017年02月09日
     *
     * @author : 谭杰文
     * @return :
     */
    public function selectarea($page = 1, $r = 20)
    {
        $builder = new AdminListBuilder();
        $builder->title('选择区域格式');
        $builder->meta_title = '区域格式列表';
        $areaList = array(
            array('id' => 1, 'name' => '重庆'),
            array('id' => 2, 'name' => '四川'),
            array('id' => 3, 'name' => '北京'),
            array('id' => 4, 'name' => '湖南'),
            array('id' => 5, 'name' => '陕西'),
            array('id' => 6, 'name' => '云南'),
            array('id' => 7, 'name' => '郑州'),
            array('id' => 8, 'name' => '贵州'),
            array('id' => 9, 'name' => '新疆'),
            array('id' => 10, 'name' => '安徽'),
            array('id' => 11, 'name' => '江苏'),
            array('id' => 12, 'name' => '山东'),
        );
        $aList = array(
            array('id' => 1, 'name' => '重庆监理用表_监理通知单'),
            array('id' => 2, 'name' => '重庆监理用表_工程暂停令'),
            array('id' => 3, 'name' => '重庆监理用表_监理通知回复单'),
            array('id' => 4, 'name' => '重庆监理用表_工作联系单'),

            array('id' => 5, 'name' => '四川监理用表_监理通知单'),
            array('id' => 6, 'name' => '四川监理用表_工程暂停令'),
            array('id' => 7, 'name' => '四川监理用表_监理通知回复单'),
            array('id' => 8, 'name' => '四川监理用表_工作联系单'),

            array('id' => 9, 'name' => '北京监理用表_监理通知单'),
            array('id' => 10, 'name' => '北京监理用表_工程暂停令'),
            array('id' => 11, 'name' => '北京监理用表_监理通知回复单'),
            array('id' => 12, 'name' => '北京监理用表_工作联系单'),

            array('id' => 13, 'name' => '湖南监理用表_监理通知单'),
            array('id' => 14, 'name' => '湖南监理用表_工程暂停令'),
            array('id' => 15, 'name' => '湖南监理用表_监理通知回复单'),
            array('id' => 16, 'name' => '湖南监理用表_工作联系单'),

            array('id' => 17, 'name' => '陕西监理用表_监理通知单'),
            array('id' => 18, 'name' => '陕西监理用表_工程暂停令'),
            array('id' => 19, 'name' => '陕西监理用表_监理通知回复单'),
            array('id' => 20, 'name' => '陕西监理用表_工作联系单'),

            array('id' => 21, 'name' => '云南监理用表_监理通知单'),
            array('id' => 22, 'name' => '云南监理用表_工程暂停令'),
            array('id' => 23, 'name' => '云南监理用表_监理通知回复单'),
            array('id' => 24, 'name' => '云南监理用表_工作联系单'),

            array('id' => 25, 'name' => '郑州监理用表_监理通知单'),
            array('id' => 26, 'name' => '郑州监理用表_工程暂停令'),
            array('id' => 27, 'name' => '郑州监理用表_监理通知回复单'),
            array('id' => 28, 'name' => '郑州监理用表_工作联系单'),

            array('id' => 29, 'name' => '贵州监理用表_监理通知单'),
            array('id' => 30, 'name' => '贵州监理用表_工程暂停令'),
            array('id' => 31, 'name' => '贵州监理用表_监理通知回复单'),
            array('id' => 32, 'name' => '贵州监理用表_工作联系单'),

            array('id' => 33, 'name' => '新疆监理用表_监理通知单'),
            array('id' => 34, 'name' => '新疆监理用表_工程暂停令'),
            array('id' => 35, 'name' => '新疆监理用表_监理通知回复单'),
            array('id' => 36, 'name' => '新疆监理用表_工作联系单'),

            array('id' => 37, 'name' => '安徽监理用表_监理通知单'),
            array('id' => 38, 'name' => '安徽监理用表_工程暂停令'),
            array('id' => 39, 'name' => '安徽监理用表_监理通知回复单'),
            array('id' => 40, 'name' => '安徽监理用表_工作联系单'),

            array('id' => 41, 'name' => '江苏监理用表_监理通知单'),
            array('id' => 42, 'name' => '江苏监理用表_工程暂停令'),
            array('id' => 43, 'name' => '江苏监理用表_监理通知回复单'),
            array('id' => 44, 'name' => '江苏监理用表_工作联系单'),

            array('id' => 45, 'name' => '山东监理用表_监理通知单'),
            array('id' => 46, 'name' => '山东监理用表_工程暂停令'),
            array('id' => 47, 'name' => '山东监理用表_监理通知回复单'),
            array('id' => 48, 'name' => '山东监理用表_工作联系单'),
        );
//     	$attr['target-form'] = 'ids';
//     	$attr['href'] = U('exALLWord');
//     	$attr['class']='a_jump1';
//     	$builder->button('导出', $attr)->setStatusUrl(U('selectarea'));
        $attr['target-form'] = 'ids';
        $attr['href'] = U('exword');
        $attr['class'] = 'a_jump1';
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
        $builder->keyId('id', '区域编号')
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
    public function measureList($page = 1, $r = 20)
    {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $_SESSION['proId'] = $id;
            action_log('show_project', 'JkProjcet', $id, UID);
        }
        //接收时间条件（转换为毫秒）
        $aSearch1 = I('get.usearch1', '') * 1000;
        $aSearch2 = I('get.usearch2', '') * 1000;

        //设置搜索条件
        if (!empty($aSearch1)) {
            $aSearch1 = strtotime(date('Y-m-d', I('get.usearch1', '')).' 00:00:00') * 1000;
            //$map.=" AND o.used_time >= '$aSearch1' ";
            $map['_string'] = "create_time>='$aSearch1' ";
        }
        if (!empty($aSearch2)) {
            $aSearch2 = strtotime(date('Y-m-d', I('get.uSearch2', '')).' 23:59:59') * 1000;
            $map['_string'] .= "and create_time<='$aSearch2' ";
        }
        // $map['projectid'] = $_SESSION['proId'];
        $map['status'] = 1;
        $map['type'] = 0;
        //接收筛选条件（状态）
        $is_out_range = I('get.is_out_range', '');
        if (!empty($is_out_range)) {
            if ($is_out_range != 4)
                $map['is_out_range'] = array('eq', $is_out_range);
        }
        if ($is_out_range == 0)
            $map['is_out_range'] = array('eq', $is_out_range);

        //检查项
        $option = I('get.option', '');
        if (!empty($option)) {
            $map['inspect'] = array('eq', $option);
        }
        //所属项目
        $ownid = I('get.ownid', '');

//         if(!empty($ownid)){
//             if($ownid!='all')
//                 $map['projectid'] = array('eq', $ownid);
//         	else
//          		$map['projectid'] =  array('in', get_my_projects());
// 	    }else{
// 	     	$map['projectid'] =  array('in', get_my_projects());
// 	    }
        if ($ownid) {
            $proids = get_select_projects($ownid);
            $map['projectid'] = array('in', $proids);

        } else {
            $map['projectid'] = array('in', get_my_projects());
        }

        //接收筛选条件（楼栋）
        $title = I('get.title', '');
        if ($title != '')
            $measureList = M('jk_check_point')->where($map)->order('create_time desc')->select();
        else
            $measureList = M('jk_check_point')->where($map)->order('create_time desc')->page($page, $r)->select();

        //echo M()->getLastSql();
        $totalCount = M('jk_check_point')->where($map)->count();
//        $totalCount = count($totalCount);
        //echo $totalCount;//die;
        $newtotalCount = 0;
        $builder = new AdminListBuilder();
        $builder->title('实测实量列表');
        $builder->meta_title = '实测实量列表';

        //选项问题
        $newmeasureList = array();

        foreach ($measureList as &$val) {
            //问题选项【检查项】
            $option = M('jk_survey_option')->where('id=' . $val['inspect'])->getField('title');
            $val['inspect'] = $option;
            unset($option);

            //楼栋数字截取
            // $floorinfo=getpointinfo($val['postion'],'');
            // echo $floorinfo;die;
            $val['project_ids'] = $val['postion'];
            $loudong_j = substr($val['project_ids'], -1);
            if ($loudong_j == ',') {
                $val['project_ids'] = substr($val['project_ids'], 0, -1);
            }

            //var_dump( $val['project_ids']);
            //楼栋【问题具体位置】
            $floor = M('jk_floor')->where("id in (" . $val['project_ids'] . ")")->field('title')->select();
            $loudong = "";

            foreach ($floor as $value) {
                if ($value['title'] != '')
                    $loudong = $loudong . $value['title'] . '-';
            }
            $changdu = mb_strlen($loudong, 'utf-8');
            $hangye = mb_substr($loudong, 0, $changdu - 1, 'utf-8');
            $val['project_ids'] = $hangye;

            //问题提交人
            $val['authid'] = M('member')->where('uid=' . $val['userid'])->getField('username');


            //时间处理
            if (strlen($val['create_time']) > 10) {
                $val['create_time'] = substr($val['create_time'], 0, 10);
            }
            if (strlen($val['update_time']) > 10) {
                $val['update_time'] = substr($val['update_time'], 0, 10);
            }

            //所属项目
            $val['other_id'] = M('jk_project')->where('id=' . $val['projectid'])->getField('other_id');
            $val['ownid'] = M('jk_project')->where('id=' . $val['projectid'])->getField('name');

            //状态
            if ($val['is_out_range'] == 0) {
                $val['is_out_range'] = "合格";
            } else if ($val['is_out_range'] == 1) {
                $val['is_out_range'] = "需整改";
            } else if ($val['is_out_range'] == 2) {
                $val['is_out_range'] = "需质量锤";
            }

            if ($floor[0][title] == $title) {
                $newmeasureList[] = $val;
                $newtotalCount += 1;
            }
        }
        unset($val);

        $attr['target-form'] = 'ids';
        $attr['href'] = U('exMeasureWord');
        $attr['class'] = 'a_jump';
        $builder->button('导出测量单', $attr)->setStatusUrl(U('setGoodsStatus'));
        //搜索框
        // $builder->setSearchPostUrl(U('JKProgram/measureList'))
        $builder->search('时间从', 'usearch1', 'timer', '', '', '', '');
        $builder->search('到', 'usearch2', 'timer', '', '', '', '');
        // $builder->setSelectPostUrl(U('JKProgram/measureList'));
        //根据项目筛选
        //获取该角色下的所有项目
        $projectwhere['status'] = array('gt', 0);
        if (!IS_ROOT) {
            $projectwhere['id'] = array('in', get_my_projects());
        }
        $list = M('jk_project')->where($projectwhere)->field('id,name')->select();
        //构造项目数组
        $projectArr = array();
        $projectArr[0]['id'] = 'all';
        $projectArr[0]['value'] = '全部';
        $kk = 1;
        foreach ($list as $vvv) {
            $projectArr[$kk]['id'] = $vvv['id'];
            $projectArr[$kk]['value'] = $vvv['name'];
            $kk++;
        }
        // $builder->select(L('项目：'), 'ownid', 'select', L('项目'), '', '', $projectArr);
        //根据检查项搜索1.先构建检查项数组
        $builder->setSelectPostUrl(U('JKProgram/measureList') . '&ownid=' . $ownid . "&usearch1=" . ($aSearch1 / 1000));

        //项目筛选
        $builder->buttonModalPopup(U('JKProgram/selectproject'),
            '',
            '根据项目筛选',
            array('data-title' => ('选择项目')));
        $allOption = M('jk_survey_option')->where('status!=-1')->field('id,title')->select();
        $optionArr = array();
        $optionArr[0]['id'] = '';
        $optionArr[0]['value'] = '全部';
        $i = 1;
        foreach ($allOption as $eachOption) {
            $optionArr[$i]['id'] = $eachOption['id'];
            $optionArr[$i]['value'] = $eachOption['title'];
            $i++;
        }

        $builder->select(L('检查项：'), 'option', 'select', L('检查项'), '', '', $optionArr);
        //根据下拉列表筛选--状态
        $astauts = array(array('id' => 4, 'value' => L('全部')), array('id' => 0, 'value' => L('合格')), array('id' => 1, 'value' => L('需整改')),
            array('id' => 2, 'value' => L('需质量锤')));

        $builder->select(L('状态：'), 'is_out_range', 'select', L('选择状态'), '', '', $astauts);
        //根据下拉列表筛选--楼栋
        //构建最顶级的楼栋列表
        if (!empty($ownid)) {
            if ($ownid != 'all') {
                $floorwhere['projectid'] = array('in', $proids);
                $floorlist = M('jk_floor')->where("status=1 and pid=0")->where($floorwhere)->order('title')->select();
            } else {
                $floorlist = M('jk_floor')->where("1=2")->order('title')->select();//不放置楼栋信息
            }
        } else {
            $floorlist = M('jk_floor')->where("status=1 and pid=0 and projectid='" . $_SESSION['proId'] . "'")->order('title')->select();
        }
        $a = array();
        $a[0]['id'] = '';
        $a[0]['value'] = '全部';
        $i = 1;
        foreach ($floorlist as $topfloor) {
            $a[$i]['id'] = $topfloor['title'];
            $a[$i]['value'] = $topfloor['title'];
            $i++;
        }
//         echo '<pre>';
//         var_dump($a);
//         echo '</pre>';die;
//          $astauts=array(array('id' => 0, 'value' => L('合格')),array('id' => 1, 'value' => L('需整改')),
//              array('id' => 2, 'value' => L('需质量锤')));
        //$builder->setSelectPostUrl(U('JKProgram/measureList'))
        $builder->select(L('楼栋：'), 'title', 'select', L('选择楼栋'), '', '', $a);

        // $builder->keyId('other_id','项目编号')
        $builder->keyText('ownid', '项目名称')
            ->keyText('project_ids', '实测位置')
            ->keyText('inspect', '检查项')
            ->keyText('authid', '实测提交人')
            ->keyUpdateTime('create_time', '提交时间')
            ->keyUpdateTime('update_time', '更新时间')
            ->keyText('is_out_range', '状态')
            ->keyDoActionEdit('JKProgram/measureedit?id=###', '详情');

        if ($title != '') {
            $builder->data($newmeasureList);
            $builder->pagination($newtotalCount, $r);
        } else {
            $builder->data($measureList);
            $builder->pagination($totalCount, $r);
        }
        $builder->display();
    }

    public function selectproject()
    {
        if (IS_POST) {

        }
        $map = array('status' => array('gt', 0));
        $pids = M('AuthGroup')->where($map)->field('pid')->select();
        $pids = array_column($pids, 'pid');
        $proList = M('AuthGroup')->field('id,title,cate,pid')->where($map)->select();
        //dump($list);
        if (IS_ROOT) {
            $proList = list_to_tree($proList, 'id', 'pid', '_', 0);

        } else {
            $start = time();
            //重新构造数组，筛选出没有项目的节点并从数组中去掉
            foreach ($proList as $kk => $vv) {
                // $result=get_my_projects($vv['id']);
                if (112 <= $vv['cate']) {
                    $hasproject = M('jk_project')->where("pid=" . $vv['id'])->select();
                    if (!$hasproject) {
                        //如果该组织架构下没有项目就筛选掉
                        unset($proList[$kk]);
                    }
                }

            }

            $group_ids = M('auth_group_access')->where("uid=" . UID)->field('group_id')->select();
            $alist = array();
            $oldlist = array();
            $iflag = 0;
            foreach ($group_ids as $v) {
                $map = array('status' => array('gt', 0), 'id' => $v['group_id']);
                $glist = M('AuthGroup')->field('id,title,pid,cate')->where($map)->find();
                //                 if($glist['cate']!=1){
                //                     $glist=getCatePath('AuthGroup',$glist['id'],1);
                //                 }
                foreach ($oldlist as $value) {
                    if ($value[$glist['id']] || $value[$glist['pid']]) {
                        $iflag = 1;
                    }
                }
                if ($iflag) {
                    $iflag = 0;
                    continue;
                }
                $glistTem = list_to_tree($proList, 'id', 'pid', '_', $v['group_id']);
                // $hasProject=get_my_projects($v['group_id']);
                if ($glistTem) {
                    $glist['_'] = $glistTem;
                    // dump($glist);
                }
                $alist[] = $glist;
                $temlist = tree_to_listwx($alist, '_');
                $oldlist[] = $temlist;
            }
            $proList = $alist;

        }

        $this->assign('nodeList', $proList);
        $this->display('/JKProgram@JKProgram/selectproject');
    }

    /**
     * 函数用途描述：实测任务列表
     * @date: 2017年3月29日
     *
     * @author : 谭杰文
     * @return :
     */
    public function measureTasksList($page = 1, $r = 20, $ownid = 0)
    {

        //接收时间条件（转换为毫秒）
        $aSearch1 = I('get.usearch1', '') * 1000;
        $aSearch2 = I('get.usearch2', '') * 1000;

        //设置搜索条件
        if (!empty($aSearch1)) {
            $aSearch1 = strtotime(date('Y-m-d', I('get.usearch1', '')).' 00:00:00') * 1000;
            //$map.=" AND o.used_time >= '$aSearch1' ";
            $map['_string'] = "createtime>='$aSearch1' ";
        }
        if (!empty($aSearch2)) {
            $aSearch2 = strtotime(date('Y-m-d', I('get.uSearch2', '')).' 23:59:59') * 1000;
            $map['_string'] .= "and createtime<='$aSearch2' ";
        }
        //接收筛选条件（状态）
        $status = I('get.status', '');
        if (!empty($status)) {
            if ($status != 4)
                $map['status'] = array('eq', $status);
        }
        if ($status == 0)
            $map['status'] = array('eq', $status);
        //所属项目
        $ownid = I('get.ownid', '');
        //找出对应用户组所属项目
        if ($ownid) {
            $proids = get_select_projects($ownid);
            $map['projectid'] = array('in', $proids);
        } else {
            $map['projectid'] = array('in', get_my_projects());
        }
//         if(!empty($ownid)){
//             if($ownid!='all')
//                 $map['projectid'] = array('eq', $ownid);
//         }

        //接收筛选条件（楼栋）
        $title = I('get.title', '');
        $measureList = M('jk_measuring_tasks')->where($map)->order('createtime desc')->page($page, $r)->select();
        $totalCount = M('jk_measuring_tasks')->where($map)->count();
        //$newtotalCount=0;
        dump(M('jk_measuring_tasks')->_sql());
        $builder = new AdminListBuilder();
        $builder->title('实测实量任务列表');
        $builder->meta_title = '实测实量任务列表';

        //选项问题
        //$newmeasureList=array();
        foreach ($measureList as &$val) {
            $val['id'] = $val['tid'];
            //问题提交人
            $val['authid'] = M('member')->where('uid=' . $val['authorid'])->getField('username');


            //时间处理
            if (strlen($val['createtime']) > 10) {
                $val['create_time'] = substr($val['create_time'], 0, 10);
            }
            if (strlen($val['updatetime']) > 10) {
                $val['update_time'] = substr($val['update_time'], 0, 10);
            }

            //所属项目
            $val['other_id'] = M('jk_project')->where('id=' . $val['projectid'])->getField('other_id');
            $val['ownid'] = M('jk_project')->where('id=' . $val['projectid'])->getField('name');

            //状态
            if ($val['status'] == 0) {
                $val['status'] = "未关闭";
            } else if ($val['status'] == 2) {
                $val['status'] = "关闭";
            }
            //时间
            $val['createtime'] = date("Y-m-d H:i", $val['createtime'] / 1000);
            //echo $val['createtime'];die;
            $val['updatetime'] = date("Y-m-d H:i", $val['updatetime'] / 1000);
        }
        unset($val);

        $attr['target-form'] = 'ids';
        $attr['href'] = U('measureTasksDels');
        $attr['class'] = ' btn ajax-post confirm btn-danger';

        $builder->button('关闭任务', $attr)->setStatusUrl(U('measureTasksList'));
        $attr['href'] = U('measureTasksDels?status=0');
        $attr['class'] = ' btn ajax-post confirm';
        $builder->button('开启任务', $attr)->setStatusUrl(U('measureTasksList'));
        //搜索框
        // $builder->setSearchPostUrl(U('JKProgram/measureTasksList'))
        $builder->search('时间从', 'usearch1', 'timer', '', '', '', '');
        $builder->search('到', 'usearch2', 'timer', '', '', '', '');
        //根据项目筛选
        //获取该角色下的所有项目
        $projectwhere['status'] = array('gt', 0);
        if (!IS_ROOT) {
            $projectwhere['id'] = array('in', get_my_projects());
        }
        $list = M('jk_project')->where($projectwhere)->field('id,name')->select();
        //构造项目数组
        $projectArr = array();
        $projectArr[0]['id'] = 'all';
        $projectArr[0]['value'] = '全部';
        $kk = 1;
        foreach ($list as $vvv) {
            $projectArr[$kk]['id'] = $vvv['id'];
            $projectArr[$kk]['value'] = $vvv['name'];
            $kk++;
        }
        // $builder->select(L('项目：'), 'ownid', 'select', L('项目'), '', '', $projectArr);


        //根据下拉列表筛选--状态
        $astauts = array(array('id' => 4, 'value' => L('全部')), array('id' => 0, 'value' => L('未关闭')), array('id' => 2, 'value' => L('已关闭')));
        $builder->select(L('状态：'), 'status', 'select', L('选择状态'), '', '', $astauts);

        $builder->setSelectPostUrl(U('JKProgram/measureTasksList') . '&ownid=' . $ownid . "&usearch1=" . ($aSearch1 / 1000));

        //项目筛选
        $builder->buttonModalPopup(U('JKProgram/selectproject'),
            '',
            '根据项目筛选',
            //array('data-title' => ('选择项目'), 'target-form' => 'ids1', 'can_null' => 'true'));
            array('data-title' => ('选择项目')));
        //$builder->keyId('other_id','项目编号')
        $builder->keyText('ownid', '项目名称')
            ->keyText('point', '实测位置')
            ->keyText('authid', '实测提交人')
            ->keyText('createtime', '提交时间')
            ->keyText('updatetime', '更新时间')
            ->keyText('status', '状态')
            ->keyDoActionEdit('JKProgram/measureTasksDel?id=###&status=2', '关闭任务')
            ->keyDoActionEdit('JKProgram/measureTasksDel?id=###&status=0', '开启任务');
        $builder->data($measureList);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }

    /**
     * 函数用途描述：关闭测量任务
     * @date: 2017年03月31日
     *
     * @author : tanjiewen
     * @return :
     */
    public function measureTasksDel($id = 0, $status = 2)
    {
        //根据id找出对应测量任务

        $update['updatetime'] = time() * 1000;

        $update['status'] = $status;
        $ret = M('jk_measuring_tasks')->where('tid=' . $id)->save($update);
        if ($ret)
            $this->success(L('修改成功'), U('JKProgram/measureTasksList'));
        else
            $this->error(L('修改失败'));
    }

    /**
     * 函数用途描述：批量关闭测量任务
     * @date: 2017年03月31日
     *
     * @author : tanjiewen
     * @return :
     */
    public function measureTasksDels($id = 0, $status = 2)
    {
        //根据id找出对应测量任务
        $arr = json_decode($_COOKIE['prids']);

        if (!$arr) {
            $this->error("未选择操作数据！");
        }
        foreach ($arr as $obj) {
            $ids[] = $obj->value;
        }
        $map['tid'] = array(
            'in',
            $ids
        );

        $list = M("jk_measuring_tasks")->where($map)->field('tid,updatetime,status')->select();
        foreach ($list as &$v) {
            $v['updatetime'] = time() * 1000;
            $v['status'] = $status;
            M('jk_measuring_tasks')->where('tid=' . $v['tid'])->save($v);
        }

        //if($ret)
        $this->success(L('修改成功'), U('JKProgram/measureTasksList'));
//     	else
//     		$this->error(L('修改失败'));
    }

    /**
     * 函数用途描述：编辑项目
     * @date: 2016年11月13日
     *
     * @author : luojun
     * @return :
     */
    public function goodsedit($id = 0, $name = '')
    {
        $isEdit = $id ? 1 : 0;
        $title = $isEdit ? '修改项目' : '添加项目';
        $meta_title = $isEdit ? '修改项目' : '添加项目';

        $this->assign('meta_title', $meta_title);
        $this->assign('title', $title);
        if (IS_POST) {
            $pid = $_POST["pid"];
            if ($pid) {
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
                        //$this->success(L('_SUCCESS_EDIT_'), U('JKProgram/shopcategory'));
                        $this->success(L('_SUCCESS_EDIT_'), U('Index/index'));
                    }
                    $this->error(L('编辑失败！'));
                } else {
                    // 商品名存在验证
                    $map['status'] = array(
                        'gt',
                        0
                    );
                    $map['name'] = $name;
                    $map['pid'] = $pid;
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
            } else {
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
                ->where($map)->order("sort DESC")
                ->select();
            if (IS_ROOT) {
                $list = list_to_tree($list, 'id', 'pid', '_', 0);
            } else {
                $initPid = $auth_group['id'];
                $group_ids = M('auth_group_access')->where("uid=" . UID)->field('group_id')->select();
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
                $list = $alist;
            }

            $this->assign('nodeList', $list);


            $goods = array();
            if ($isEdit) {
                $goods = $this->shopModel->where('id=' . $id)->find();
                $topgroup = M('auth_group')->field('pid,id,title')
                    ->where("id=" . $goods['pid'])
                    ->find();
                $this->assign('pid', $topgroup['id']);
                $this->assign('group_title', $topgroup['title']);
                $group_title = $topgroup['title'];
                $pid = $topgroup['pid'];

                while ($pid != 0) {
                    $auth = M('auth_group')->where('id=' . $pid)->field('pid,title')->find();
                    $group_title = $auth['title'] . "-" . $group_title;
                    $pid = $auth['pid'];
                }
                if ($group_title) {
                    $group_title = '(' . $group_title . ')';
                }

                $this->assign('group_title', $group_title);
            } else {
                $goods['status'] = 1;
                $goods['periods'] = 5;
                $goods['blocks'] = 5;
                $goods['batch'] = 5;
            }
            $area = M('jk_area')->select();
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
    public function exotherword()
    {
        $builder = new AdminListBuilder();
        $builder->title('导出文件模板列表');
        $builder->meta_title = '导出文件模板列表';

        $attr = array();
        $attr['target-form'] = 'ids';
        $attr['href'] = U('doexotherword');
        $attr['class'] = 'a_jump2';
        $builder->button('导出文件', $attr);

        //列表
        $builder->keyId('id', '模板编号')
            ->keyText('name', '模板名称');

        $aList = array(
            array('id' => 1, 'name' => '重庆监理用表_工程暂停令'),
            array('id' => 2, 'name' => '重庆监理用表_监理通知回复单'),
            array('id' => 3, 'name' => '重庆监理用表_工作联系单'),
            array('id' => 4, 'name' => '四川监理用表_工程暂停令'),
            array('id' => 5, 'name' => '四川监理用表_监理通知回复单'),
            array('id' => 6, 'name' => '四川监理用表_工作联系单'),
            array('id' => 7, 'name' => '北京监理用表_工程暂停令'),
            array('id' => 8, 'name' => '北京监理用表_监理通知回复单'),
            array('id' => 9, 'name' => '北京监理用表_工作联系单'),
            array('id' => 10, 'name' => '湖南监理用表_工程暂停令'),
            array('id' => 11, 'name' => '湖南监理用表_监理通知回复单'),
            array('id' => 12, 'name' => '湖南监理用表_工作联系单'),
            array('id' => 13, 'name' => '陕西监理用表_工程暂停令'),
            array('id' => 14, 'name' => '陕西监理用表_监理通知回复单'),
            array('id' => 15, 'name' => '陕西监理用表_工作联系单'),
            array('id' => 16, 'name' => '云南监理用表_工程暂停令'),
            array('id' => 17, 'name' => '云南监理用表_监理通知回复单'),
            array('id' => 18, 'name' => '云南监理用表_工作联系单'),
            array('id' => 19, 'name' => '郑州监理用表_工程暂停令'),
            array('id' => 20, 'name' => '郑州监理用表_监理通知回复单'),
            array('id' => 21, 'name' => '郑州监理用表_工作联系单'),
            array('id' => 22, 'name' => '贵州监理用表_工程暂停令'),
            array('id' => 23, 'name' => '贵州监理用表_监理通知回复单'),
            array('id' => 24, 'name' => '贵州监理用表_工作联系单'),
            array('id' => 25, 'name' => '新疆监理用表_工程暂停令'),
            array('id' => 26, 'name' => '新疆监理用表_监理通知回复单'),
            array('id' => 27, 'name' => '新疆监理用表_工作联系单'),
            array('id' => 28, 'name' => '安徽监理用表_工程暂停令'),
            array('id' => 29, 'name' => '安徽监理用表_监理通知回复单'),
            array('id' => 30, 'name' => '安徽监理用表_工作联系单'),
            array('id' => 31, 'name' => '江苏监理用表_工程暂停令'),
            array('id' => 32, 'name' => '江苏监理用表_监理通知回复单'),
            array('id' => 33, 'name' => '江苏监理用表_工作联系单'),
            array('id' => 34, 'name' => '山东监理用表_工程暂停令'),
            array('id' => 35, 'name' => '山东监理用表_监理通知回复单'),
            array('id' => 36, 'name' => '山东监理用表_工作联系单'),
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
    public function doexotherword()
    {
        $ids = array_unique((array)I('ids', 0));
        if (!$ids) {
            $this->error("未选择操作数据！");
        }
        $date = date('Y年m月d日', time());
        $area = json_decode($_COOKIE['templateid']);
        //构建区域模块变量
        foreach ($area as $a) {
            $template = $a->value;
        }
        $year = date("Y");
        $this->assign("year", $year);

        // echo $template;die;
        $content = $this->fetch('/JKProgram@JKProgram/exotherWord' . $template);

        $flieName = iconv("UTF-8", "GBK", $date . "导出文件.doc");

        $content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片
        // echo $html.$content.'</html>';
        $fp = fopen($flieName, 'w');
        fwrite($fp, $html . $content . '</html>');
        fclose($fp);


//         $this->display('/JKProgram@JKProgram/exotherWord1');
        header("location:" . $date . "导出文件.doc");
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

        if (!$arr) {
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
        //根据项目id查出区域id

        $areanum = M("jk_project")->where("id=" . $list[0]['ownid'])->getField("areaID");
        $_COOKIE['areanum'] = $areanum;
        //查询当前用户是否为监理单位
        $isjianli = 0;
        $groups = M("auth_group_access")->where("uid=" . session('user_auth.uid') . "")->select();
        //var_dump($groups);
        foreach ($groups as $g) {
            //根据$g['group_id']找到是否为监理单位
            $group = M("auth_group")->where("id=" . $g['group_id'])->field("id,cate")->find();
            if (10 > $group['cate']) {
                if ($group['cate'] == '2')
                    $isjianli = 1;
            } else {
                $cate = get_cate_attr("auth_group", $group['id'], 2);//监理
                if ($cate['cate'] == '2')
                    $isjianli = 1;
            }


        }

        if ($isjianli) {
            foreach ($list as $v) {
                if ($v['authid'] != session('user_auth.uid') && session('user_auth.uid') != 1) {
                    //echo UID;
                    $this->error("请勿导出非本人提交的报表");
                }
            }
        }
        $data = array();
        $allIamges = '';
        // 选项问题
        foreach ($list as &$val) {
            // 问题选项【检查项】
            if ($val['type'] > 0) {
                $option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
            } else
                $option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');

            $val['id'] = $val['init_id'];

            $val['option_id'] = $option;
            unset($option);

            // 楼栋数字截取
            $loudong_j = substr($val['project_ids'], -1);
            if ($loudong_j == ',') {
                $val['project_ids'] = substr($val['project_ids'], 0, -1);
            }

            // 楼栋【问题具体位置】
            $floor = M('jk_floor')->where("id in (" . $val['project_ids'] . ")")
                ->field('title')
                ->select();
            $loudong = "";
            foreach ($floor as $value) {
                if ($value['title'] != '')
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
        $year = date("Y");
        $this->assign("year", $year);
        $this->assign("data", $data);
        $this->assign("apath", $apath);
        //$_COOKIE['areanum']=$areanum['areaID'];

        $content = $this->fetch('/JKProgram@JKProgram/exWord' . $_COOKIE['areanum']);


        // $fileContent = $this->getWordDocument($content,$_SERVER['HTTP_HOST'].'/',0);
        $flieName = iconv("UTF-8", "GBK", "zhenggaitongzhidan.doc");

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
        header("location:zhenggaitongzhidan.doc");

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

        if (!$arr) {
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
        //根据项目id查出区域id
        $areanum = M("jk_project")->where("id=" . $list[0]['ownid'])->getField("areaID");
        $_COOKIE['areanum'] = $areanum;
        $isjianli = 0;
        $groups = M("auth_group_access")->where("uid=" . session('user_auth.uid') . "")->select();
        //var_dump($groups);
        foreach ($groups as $g) {
            //根据$g['group_id']找到是否为监理单位
            $group = M("auth_group")->where("id=" . $g['group_id'])->field("id,cate")->find();
            if (10 > $group['cate']) {
                if ($group['cate'] == '2')
                    $isjianli = 1;
            } else {
                $cate = get_cate_attr("auth_group", $group['id'], 2);//监理
                if ($cate['cate'] == '2')
                    $isjianli = 1;
            }
        }

        if ($isjianli) {
            foreach ($list as $v) {
                if ($v['authid'] != session('user_auth.uid') && session('user_auth.uid') != 1) {
                    //echo UID;
                    $this->error("请勿导出非本人提交的报表");
                }
            }
        }
        $data = array();
        $allIamges = '';
        // 选项问题
        foreach ($list as &$val) {
            // 问题选项【检查项】
            if ($val['type'] > 0) {
                $option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
            } else
                $option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');

            $val['id'] = $val['init_id'];

            $val['option_id'] = $option;
            unset($option);

            // 楼栋数字截取
            $loudong_j = substr($val['project_ids'], -1);
            if ($loudong_j == ',') {
                $val['project_ids'] = substr($val['project_ids'], 0, -1);
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
        $year = date("Y");
        $this->assign("year", $year);
        $this->assign("data", $data);
        $this->assign("apath", $apath);

        $content .= $this->fetch('/JKProgram@JKProgram/exhuiword' . $_COOKIE['areanum']);


        $flieName = iconv("UTF-8", "GBK", "huifutongzhidan.doc");
        $content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片

        $fp = fopen($flieName, 'w');

        fwrite($fp, $html . $content . '</html>');
        fclose($fp);
        header("location:huifutongzhidan.doc");
    }

    public function exALLWord()
    {
        $arr = json_decode($_COOKIE['prids']);
        $area = json_decode($_COOKIE['areanum']);
        if (!$arr) {
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
            if ($val['type'] > 0) {
                $option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
            } else
                $option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');

            $val['id'] = $val['init_id'];

            $val['option_id'] = $option;
            unset($option);

            // 楼栋数字截取
            $loudong_j = substr($val['project_ids'], -1);
            if ($loudong_j == ',') {
                $val['project_ids'] = substr($val['project_ids'], 0, -1);
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
        $year = date("Y");
        $this->assign("year", $year);
        $this->assign("data", $data);
        $this->assign("apath", $apath);
        //构建区域模块变量
        //var_dump($area);die;
        $date = date('Y年m月d日', time());
        foreach ($area as $a) {
            $template = $a->value;
            $content .= $this->fetch('/JKProgram@JKProgram/exALLWord' . $template);
        }

        $flieName = iconv("UTF-8", "GBK", $date . "导出文件.doc");
        $content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片

        $fp = fopen($flieName, 'w');

        fwrite($fp, $html . $content . '</html>');
        fclose($fp);
        header("location:" . $date . "导出文件.doc");
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
        if (!$arr) {
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
        //根据项目id查出区域id
        $areanum = M("jk_project")->where("id=" . $list[0]['ownid'])->getField("areaID");
        $_COOKIE['areanum'] = $areanum;
        $isjianli = 0;
        $groups = M("auth_group_access")->where("uid=" . session('user_auth.uid') . "")->select();
        //var_dump($groups);
        foreach ($groups as $g) {
            //根据$g['group_id']找到是否为监理单位
            $group = M("auth_group")->where("id=" . $g['group_id'])->field("id,cate")->find();
            if (10 > $group['cate']) {
                if ($group['cate'] == '2')
                    $isjianli = 1;
            } else {
                $cate = get_cate_attr("auth_group", $group['id'], 2);//监理
                if ($cate['cate'] == '2')
                    $isjianli = 1;
            }
        }

        if ($isjianli) {
            foreach ($list as $v) {
                if ($v['authid'] != session('user_auth.uid') && session('user_auth.uid') != 1) {
                    //echo UID;
                    $this->error("请勿导出非本人提交的报表");
                }
            }
        }
        $data = array();
        $allIamges = '';
        // 选项问题
        foreach ($list as &$val) {
            // 问题选项【检查项】
            if ($val['type'] > 0) {
                $option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
            } else
                $option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');

            $val['id'] = $val['init_id'];

            $val['option_id'] = $option;
            unset($option);

            // 楼栋数字截取
            $loudong_j = substr($val['project_ids'], -1);
            if ($loudong_j == ',') {
                $val['project_ids'] = substr($val['project_ids'], 0, -1);
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
        $year = date("Y");
        $this->assign("year", $year);
        $this->assign("data", $data);
        $this->assign("apath", $apath);
        $content .= $this->fetch('/JKProgram@JKProgram/excontactWord' . $_COOKIE['areanum']);


        $flieName = iconv("UTF-8", "GBK", "lianxidan.doc");

        $content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片
        $fp = fopen($flieName, 'w');
        fwrite($fp, $html . $content . '</html>');
        fclose($fp);
        header("location:lianxidan.doc");
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
        if (!$arr) {
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
        //根据项目id查出区域id
        $areanum = M("jk_project")->where("id=" . $list[0]['ownid'])->getField("areaID");
        $_COOKIE['areanum'] = $areanum;
        $isjianli = 0;
        $groups = M("auth_group_access")->where("uid=" . session('user_auth.uid') . "")->select();
        //var_dump($groups);
        foreach ($groups as $g) {
            //根据$g['group_id']找到是否为监理单位
            $group = M("auth_group")->where("id=" . $g['group_id'])->field("id,cate")->find();
            if (10 > $group['cate']) {
                if ($group['cate'] == '2')
                    $isjianli = 1;
            } else {
                $cate = get_cate_attr("auth_group", $group['id'], 2);//监理
                if ($cate['cate'] == '2')
                    $isjianli = 1;
            }
        }

        if ($isjianli) {
            foreach ($list as $v) {
                if ($v['authid'] != session('user_auth.uid') && session('user_auth.uid') != 1) {
                    //echo UID;
                    $this->error("请勿导出非本人提交的报表");
                }
            }
        }
        $data = array();
        $allIamges = '';
        // 选项问题
        //拼接暂停问题选项
        $stopReason = "";
        foreach ($list as &$val) {
            // 问题选项【检查项】
            if ($val['type'] > 0) {
                $option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
            } else
                $option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');

            $val['id'] = $val['init_id'];

            $val['option_id'] = $option;

            unset($option);

            // 楼栋数字截取
            $loudong_j = substr($val['project_ids'], -1);
            if ($loudong_j == ',') {
                $val['project_ids'] = substr($val['project_ids'], 0, -1);
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
            $stopReason .= $val['project_ids'] . "位置" . $val['option_id'] . ";";
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
        $year = date("Y");
        $this->assign("stopReason", $stopReason);
        $this->assign("year", $year);
        $this->assign("data", $data);
        $this->assign("apath", $apath);
        //构建区域模块变量

        $content .= $this->fetch('/JKProgram@JKProgram/exstopWord' . $_COOKIE['areanum']);


        // $fileContent = $this->getWordDocument($content,$_SERVER['HTTP_HOST'].'/',0);
        $flieName = iconv("UTF-8", "GBK", "zantingling.doc");


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
        header("location:zantingling.doc");

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
        if (!$arr) {
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
        //根据项目id查出区域id
        $areanum = M("jk_project")->where("id=" . $list[0]['ownid'])->getField("areaID");
        $_COOKIE['areanum'] = $areanum;
        $isjianli = 0;
        $groups = M("auth_group_access")->where("uid=" . session('user_auth.uid') . "")->select();
        //var_dump($groups);
        foreach ($groups as $g) {
            //根据$g['group_id']找到是否为监理单位
            $group = M("auth_group")->where("id=" . $g['group_id'])->field("id,cate")->find();
            if (10 > $group['cate']) {
                if ($group['cate'] == '2')
                    $isjianli = 1;
            } else {
                $cate = get_cate_attr("auth_group", $group['id'], 2);//监理
                if ($cate['cate'] == '2')
                    $isjianli = 1;
            }
        }
        if ($isjianli) {
            foreach ($list as $v) {
                if ($v['authid'] != session('user_auth.uid') && session('user_auth.uid') != 1) {
                    //echo UID;
                    $this->error("请勿导出非本人提交的报表");
                }
            }
        }
        $data = array();
        $allIamges = '';
        // 选项问题
        //拼接暂停问题选项
        $stopReason = "";
        foreach ($list as &$val) {
            // 问题选项【检查项】
            if ($val['type'] > 0) {
                $option = M('jk_survey_option')->where('id=' . $val['option_id'])->getField('title');
            } else
                $option = M('jk_option')->where('id=' . $val['option_id'])->getField('title');

            $val['id'] = $val['init_id'];

            $val['option_id'] = $option;

            unset($option);

            // 楼栋数字截取
            $loudong_j = substr($val['project_ids'], -1);
            if ($loudong_j == ',') {
                $val['project_ids'] = substr($val['project_ids'], 0, -1);
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
            $stopReason .= $val['project_ids'] . "位置" . $val['option_id'] . ";";
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
        $year = date("Y");
        $this->assign("stopReason", $stopReason);
        $this->assign("year", $year);
        $this->assign("data", $data);
        $this->assign("apath", $apath);
        if ($_COOKIE['areanum'] != 2)
            $this->error("所属城市没有复工令模板");
        $content .= $this->fetch('/JKProgram@JKProgram/exstartWord' . $_COOKIE['areanum']);


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
        if (!$arr) {
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
        $measurezhi = array();
        // 选项问题
        foreach ($list as &$val) {
            // 【检查项】
            $option = M('jk_survey_option')->where('id=' . $val['inspect'])->getField('title');
            $val['option_id'] = $option;
            unset($option);
            //检查项对应的合格值，整改值，质量锤值
            $measureinfo = M('jk_survey_option')->where('id=' . $val['inspect'])->find();
            $val['minqualified'] = (float)$measureinfo['minqualified'];
            $val['maxqualified'] = (float)$measureinfo['maxqualified'];
            //判断是否为特殊项：轴线偏差
            if ($val['inspect'] != 15) {
                $val['minzhenggai'] = $val['minqualified'] * 1.0;
                $val['maxzhenggai'] = $val['maxqualified'] * 1.0;

                $val['mindestroy'] = $measureinfo['mindestroy'];
                $val['maxdestroy'] = $measureinfo['maxdestroy'];
            } else {
                $val['minzhenggai'] = "";
                $val['maxzhenggai'] = "";
                $val['mindestroy'] = $val['minqualified'] * 1.0;
                $val['maxdestroy'] = $val['maxqualified'] * 1.0;
            }
            if ($val['inspect'] == 14) {
                $val['minzhenggai'] = "";
                $val['maxzhenggai'] = "";
                $val['mindestroy'] = "";
                $val['maxdestroy'] = "";
            }

            // 楼栋数字截取
            $loudong_j = substr($val['postion'], -1);
            if ($loudong_j == ',') {
                $val['project_ids'] = substr($val['postion'], 0, -1);
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
            $val['info'] = explode('|', $val['info']);
            //当为特殊项时计算差值
            $i = 0;
            foreach ($val['info'] as $vv) {

                $nums = explode(',', $vv);

                if (count($nums) > 1) {
                    $min = min($nums);
                    $max = max($nums);
                    $cha = $max - $min;
                    if ($val['inspect'] == '4')//如果为楼板厚度，则为后一个减去前一个
                    {
                        $cha = $nums[1] - $nums[0];
                    }
                    $val['info'][$i] .= '(' . $cha . ')';
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

        $flieName = iconv("UTF-8", "GBK", "celiangtongzhidan.doc");

        $content = str_replace("src=\"", "src=\"http://" . $_SERVER['HTTP_HOST'] . "/", $content); // 给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; // 这句不能少，否则不能识别图片
        // echo $html.$content.'</html>';
        $fp = fopen($flieName, 'w');
        // dump($html.$content.'</html>');
        fwrite($fp, $html . $content . '</html>');
        fclose($fp);
        header("location:celiangtongzhidan.doc");

        // $this->display('/JKProgram@JKProgram/exWord');
    }

    public function refundExcel()
    {
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
        if (!$arr) {
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
        $measurezhi = array();
        // 选项问题
        foreach ($list as &$val) {
            // 【检查项】
            $option = M('jk_survey_option')->where('id=' . $val['inspect'])->getField('title');
            $val['option_id'] = $option;
            unset($option);
            //检查项对应的合格值，整改值，质量锤值
            $measureinfo = M('jk_survey_option')->where('id=' . $val['inspect'])->find();
            $val['minqualified'] = (float)$measureinfo['minqualified'];
            $val['maxqualified'] = (float)$measureinfo['maxqualified'];
            //判断是否为特殊项：轴线偏差
            if ($val['inspect'] != 15) {
                $val['minzhenggai'] = $val['minqualified'] * 1.0;
                $val['maxzhenggai'] = $val['maxqualified'] * 1.0;

                $val['mindestroy'] = $measureinfo['mindestroy'];
                $val['maxdestroy'] = $measureinfo['maxdestroy'];
            } else {
                $val['minzhenggai'] = "";
                $val['maxzhenggai'] = "";
                $val['mindestroy'] = $val['minqualified'] * 1.0;
                $val['maxdestroy'] = $val['maxqualified'] * 1.0;
            }
            if ($val['inspect'] == 14) {
                $val['minzhenggai'] = "";
                $val['maxzhenggai'] = "";
                $val['mindestroy'] = "";
                $val['maxdestroy'] = "";
            }

            // 楼栋数字截取
            $loudong_j = substr($val['postion'], -1);
            if ($loudong_j == ',') {
                $val['project_ids'] = substr($val['postion'], 0, -1);
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
            $val['info'] = explode('|', $val['info']);
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', '工程名称' . $val['ownid'])->setCellValue('A2', '检查项:' . $val['option_id'])
                ->setCellValue('D2', '检查位置:' . $val['project_ids'])->setCellValue('G2', '检查人:' . $val['authid']);
            //$data[] = $val;

        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');
        die;
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
            for ($i = 0; $i < count($arrPath); $i++) {
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

        for ($i = 0; $i < count($images); $i++) {
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
        $this->meta_title = '问题详情';
        $find = M('jk_program')->where("init_id='$init_id'")->find();
        // 所属项目
        $find['ownid'] = M('jk_project')->where("id=" . $find['ownid'])->getField('name');
        $find['info'] = $find['info'];
        // 楼栋
        $map = array('id' => array('in', $find['project_ids']));
        $floor = M('jk_floor')->where($map)
            ->field('id,title,pid,periods,batch,blocks')
            ->select();
        //dump($floor);
        foreach ($floor as $v) {
            if ($v['title'] != "") {
                $sfloor = $sfloor . $v['title'] . '—';
            }
            //echo $sfloor."<br/>";
            if ($v['pid'] == 0) {//楼栋批次等信息
                $find['ownid'] .= ' ';
                if ($v['periods']) {
                    $find['ownid'] = $find['ownid'] . $v['periods'] . '期';
                }
                if ($v['batch']) {
                    $find['ownid'] = $find['ownid'] . $v['batch'] . '批次';
                }
                if ($v['blocks']) {
                    $find['ownid'] = $find['ownid'] . $v['blocks'] . '标段';
                }

            }
        }
        $sfloor = rtrim($sfloor, "—");
        $find['project_ids'] = $sfloor;//楼层拼接


        // 时间戳记截取13->10;
        if (strlen($find['create_time']) > 10) {
            $find['create_time'] = time_format(substr($find['create_time'], 0, 10));
        } else {
            $find['create_time'] = time_format($find['create_time']);
        }
        if (strlen($find['update_time']) > 10) {
            $find['update_time'] = time_format(substr($find['update_time'], 0, 10));
        } else {
            $find['update_time'] = time_format($find['update_time']);
        }
        // 问题提交人id->姓名
        $authInfo = M('member')->where('uid=' . $find['authid'])->field('nickname,username,department')->find();
        //dump($authInfo);

        $find['department'] = $authInfo['department'];
        $find['authid'] = $authInfo['nickname'];
        if ($authInfo['username']) {
            $find['authid'] = $find['authid'] . '[' . $authInfo['username'] . ']';
        }

        //问题选项
        $optionDB = $find['type'] > 0 ? M('jk_survey_option') : M('jk_option');
        $map = array();
        $map['id'] = $find['option_id'];
        $option = $optionDB->where($map)
            ->field('id,title,pid')->find();

        $find['option_id'] = $option['title'];
        if ($option['pid'] > 0) {
            $map['id'] = $option['pid'];
            $poption = M('jk_option')->where($map)
                ->field('id,title,pid')->find();
            $find['option_id'] = $poption['title'] . '  ' . $option['title'];
        }

        //整改单位

        $map = array();
        $map['id'] = $find['target_id'];
        $find['target_id'] = M('auth_group')->where($map)->getField('title');

//      状态：   0：待整改；1：正常关闭；2：待复查；3：强制关闭
        $starr = array('0' => '待整改', '1' => '正常关闭', '2' => '待复查', '3' => '强制关闭');
        $find['status'] = $starr[$find['status']];
        //详情图组
        //$find['imgpath'] = coverIds2Path($find['mapid'],1);
        $find['imgpath'] = get_image_by_id($find['init_id'], 1);
        //dump($find['imgpath']);
        //留言板
        $map = array();
        $map['problem_id'] = $init_id;
        $boardInfo = M('jk_problm_board')->where($map)->order('boardid ASC')->select();
        foreach ($boardInfo as &$v) {
            $userInfo = M('member')->where('uid=' . $v['userid'])->field('nickname,username,department')->find();
            $v['department'] = $userInfo['department'];
            $v['username'] = $userInfo['nickname'];
            if ($userInfo['username']) {
                $v['username'] = $v['username'] . '[' . $userInfo['username'] . ']';
            }//留言用户
            $v['imgpath'] = get_image_by_id($v['boardid'], 1);
            //$v['imgpath']=coverIds2Path($v['images'],1);
            if (strlen($v['create_time']) > 10) {
                $v['create_time'] = time_format(substr($v['create_time'], 0, 10));
            } else {
                $v['create_time'] = time_format($v['create_time']);
            }
        }
        $find['problm_board'] = $boardInfo;
        //dump($find);
        $this->assign('data', $find);

        $this->display('/JKProgram@JKProgram/programdetail');

    }

    /**
     * 实测单列表详情页
     * yxch
     * 2017年5月27日11:18:33
     * */

    //实测项预览
    public function measureedit($id = 0)
    {
        $this->meta_title = '测量详情';

        $find = M('jk_check_point')->where('id=' . $id)->find();
        // 所属项目
        $find['ownid'] = M('jk_project')->where("id=" . $find['projectid'])->getField('name');

        //查询所有属于该位置的信息
        $postion1 = $find['postion'];
        $inspect = $find['inspect'];
        //同一位置的所有检测信息按检测项来排序
        $tempdata1 = M('jk_check_point')->where("postion = '$postion1' AND inspect = '$inspect' AND type = 0")->order('level,update_time')->select();

        //获取检测位置测量几个点
        $jcnum = M("jk_survey_option")->where("id = '$inspect'")->getField(pointlength);
        //echo $jcnum;die;
        $widthpersent = 100 / $jcnum;
        $widthpersent = $widthpersent . "%";
        //echo $widthpersent;die;
        $this->assign('jcnum', $jcnum);
        $this->assign('widthpersent', $widthpersent);

        foreach ($tempdata1 as $vv1) {
            $res1['id'] = $vv1['id'];
            $res1['inspect'] = $vv1['inspect'];//检测项目
            $res1['level'] = $vv1['level'];//检测部门
            $res1['info'] = explode("|", $vv1['info']);//检测信息，转换为数组
            $res1['nonum'] = $vv1['nonum'];//不合格数
            $res1['totalnum'] = $vv1['totalnum'];//总测数
            $res1['update_time'] = $vv1['update_time'];//更新时间

            $data[] = $res1;
        }

        //同一level下同一检测项，取出最新的数据
        $i = 0;
        $inspect1 = "";
        foreach ($data as $vv2) {
            if ($inspect1 == $vv2['inspect']) {
                $update_time1 = $vv2['update_time'];
                $j = $i - 1;
                if ($vv2['level'] == $data2[$j]['level']) {
                    //echo "uptime=".$data2[$j]['update_time'];
                    if ($vv2['update_time'] >= $data2[$j]['update_time']) {
                        $res2['id'] = $vv2['id'];
                        $res2['inspect'] = $vv2['inspect'];//检测项目
                        $res2['level'] = $vv2['level'];//检测部门
                        $res2['info'] = $vv2['info'];//检测信息，转换为数组
                        $res2['nonum'] = $vv2['nonum'];//不合格数
                        $res2['totalnum'] = $vv2['totalnum'];//总测数
                        $res2['update_time'] = $vv2['update_time'];//更新时间
                        $data2[$j] = $res2;
                    }
                } else {
                    $res2['id'] = $vv2['id'];
                    $res2['inspect'] = $vv2['inspect'];//检测项目
                    $res2['level'] = $vv2['level'];//检测部门
                    $res2['info'] = $vv2['info'];//检测信息，转换为数组
                    $res2['nonum'] = $vv2['nonum'];//不合格数
                    $res2['totalnum'] = $vv2['totalnum'];//总测数
                    $res2['update_time'] = $vv2['update_time'];//更新时间
                    $data2[$i] = $res2;
                    $i++;
                }
            } else {
                $inspect1 = $vv2['inspect'];
                $res2['id'] = $vv2['id'];
                $res2['inspect'] = $vv2['inspect'];//检测项目
                $res2['level'] = $vv2['level'];//检测部门
                $res2['info'] = $vv2['info'];//检测信息，转换为数组
                $res2['nonum'] = $vv2['nonum'];//不合格数
                $res2['totalnum'] = $vv2['totalnum'];//总测数
                $res2['update_time'] = $vv2['update_time'];//更新时间
                $data2[$i] = $res2;
                $i++;
            }
        }

        //算合格率
        $totalnonum = $data2[0]['nonum'] + $data2[1]['nonum'] + $data2[2]['nonum'];//总不合格数量
        $totalnum1 = $data2[0]['totalnum'] + $data2[1]['totalnum'] + $data2[2]['totalnum'];//测量总数
        $persent = (1 - $totalnonum / $totalnum1) * 100;
        $persent = number_format($persent, 2, '.', '');
        $persent = $persent . "%";
        //dump($data2);
        $countdata2 = count($data2);
        if ($countdata2 != 3) {//判断是否每个部门都进行了提交
            //判断第一个值是哪个部门
            if ($data2[0]['level'] == 1) {
                $level1info7 = $data2[0]['info'];//工程部
                $level1persent = (1 - $data2[0]['nonum'] / $data2[0]['totalnum']) * 100;
            } elseif ($data2[0]['level'] == 2) {
                $level2info8 = $data2[0]['info'];//监理
                $level2persent = (1 - $data2[0]['nonum'] / $data2[0]['totalnum']) * 100;
            } elseif ($data2[0]['level'] == 3) {
                $level3info9 = $data2[0]['info'];//施工单位
                $level3persent = (1 - $data2[0]['nonum'] / $data2[0]['totalnum']) * 100;
            }
            //判断第二个值是哪个部门
            if ($data2[1]['level'] == 2) {//因为按照level来排序，第2个值不可能是1
                $level2info8 = $data2[1]['info'];//监理
                $level2persent = (1 - $data2[1]['nonum'] / $data2[1]['totalnum']) * 100;
            } elseif ($data2[1]['level'] == 3) {
                $level3info9 = $data2[1]['info'];//施工单位
                $level3persent = (1 - $data2[1]['nonum'] / $data2[1]['totalnum']) * 100;
            }

        } else {
            //有3个值
            $level1info7 = $data2[0]['info'];//工程部
            $level2info8 = $data2[1]['info'];//监理
            $level3info9 = $data2[2]['info'];//施工单位

            //分别算合格率
            $level1persent = (1 - $data2[0]['nonum'] / $data2[0]['totalnum']) * 100;
            $level2persent = (1 - $data2[1]['nonum'] / $data2[1]['totalnum']) * 100;
            $level3persent = (1 - $data2[2]['nonum'] / $data2[2]['totalnum']) * 100;
        }

        $level1persent = number_format($level1persent, 2, '.', '');
        $level1persent = $level1persent . "%";
        $level2persent = number_format($level2persent, 2, '.', '');
        $level2persent = $level2persent . "%";
        $level3persent = number_format($level3persent, 2, '.', '');
        $level3persent = $level3persent . "%";

        //转换成字符串
        $level1info1 = implode("|", $level1info7);
        $level2info2 = implode("|", $level2info8);
        $level3info3 = implode("|", $level3info9);

        //|替换成，
        $levelinfo4 = str_replace("|", ",", $level1info1);
        $levelinfo5 = str_replace("|", ",", $level2info2);
        $levelinfo6 = str_replace("|", ",", $level3info3);
        //转换数组
        $levelinfo4 = explode(",", $levelinfo4);
        $levelinfo5 = explode(",", $levelinfo5);
        $levelinfo6 = explode(",", $levelinfo6);

        //把|转变成-,去除“，”变成|
        $aaa1 = str_replace("|", "-", $level1info1);
        $aaa2 = str_replace("|", "-", $level2info2);
        $aaa3 = str_replace("|", "-", $level3info3);
        $ccc1 = str_replace(",", "|", $aaa1);
        $ccc2 = str_replace(",", "|", $aaa2);
        $ccc3 = str_replace(",", "|", $aaa3);

        $level1info4 = str_replace(",", " ", $level1info1);
        $level2info5 = str_replace(",", " ", $level2info2);
        $level3info6 = str_replace(",", " ", $level3info3);
        //转换回数组
        $level1info = explode("|", $level1info4);

        $level2info = explode("|", $level2info5);
        $level3info = explode("|", $level3info6);

        $bbb1 = explode("-", $ccc1);
        $bbb2 = explode("-", $ccc2);
        $bbb3 = explode("-", $ccc3);
        //根据测量项，找到测量项的合格数据
        $option1 = M('jk_survey_option')->where("id = '$inspect'")
            ->field('id,title,pid,minqualified,maxqualified,mindestroy,maxdestroy,pointlength')->find();
        if ($level1info7) {//存在施工单位的测量
            foreach ($level1info7 as $vvv1) {
                if ($vvv1 != "" && $vvv1 != "," && $vvv1 != ",," && $vvv1 != ",,," && $vvv1 != ",,,,") {//未输入的值不显示
                    if ($option1['pointlength'] == 1) {
                        if ($option1['maxdestroy'] != null || $option1['mindestroy'] != null) {
                            if ($vvv1 > $option1['maxdestroy'] || $vvv1 < $option1['mindestroy']) {
                                //存入质量锤数组
                                $zlc[] = $vvv1;
                            } else if ($vvv1 > $option1['maxqualified'] * 1.0 || $vvv1 < ($option1['minqualified'] * 1.0)) {
                                //存入整改数组
                                $zg[] = $vvv1;
                            }
                        } else {
                            if (($vvv1 > ($option1['maxqualified'] * 1.0) || $vvv1 < ($option1['minqualified'] * 1.0)) && $option1['id'] != "14") {
                                //存入整改数组
                                $zg[] = $vvv1;
                            }
                        }
                    } else {
                        $nums1 = explode(',', $vvv1);
                        $min = min($nums1);
                        $max = max($nums1);
                        $cha = $max - $min;
                        //构建特殊项数组
                        $arr1 = array('4', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51');
                        if (in_array($option1['id'], $arr1))//如果为特殊项，则为后一个减去前一个
                        {
                            $cha = $nums1[1] - $nums1[0];

                        }

                        if ($option1['maxdestroy'] != null || $option1['mindestroy'] != null) {

                            if ($cha > $option1['maxdestroy'] || $cha < $option1['mindestroy']) {
                                //存入质量锤数组
                                $zlc[] = $vvv1;


                            } else if ($cha > $option1['maxqualified'] * 1.0 || $cha < ($option1['minqualified'] * 1.0)) {
                                //存入整改数组
                                //echo $cha."<br/>";
                                $zg[] = $vvv1;
                            }

                        } else//如果未设置则只判断是否需要整改
                        {

                            if ($cha > ($option1['maxqualified'] * 1.0) || $cha < ($option1['minqualified'] * 1.0)) {
                                //存入整改数组
                                $zg[] = $vvv1;
                            }

                        }

                    }
                }
            }

        }
        if ($level2info8) {//存在施工单位的测量
            foreach ($level2info8 as $vvv2) {
                if ($vvv2 != "" && $vvv2 != "," && $vvv2 != ",," && $vvv2 != ",,," && $vvv2 != ",,,,") {//未输入的值不显示
                    if ($option1['pointlength'] == 1) {
                        if ($option1['maxdestroy'] != null || $option1['mindestroy'] != null) {
                            if ($vvv2 > $option1['maxdestroy'] || $vvv2 < $option1['mindestroy']) {
                                //存入质量锤数组
                                $zlc1[] = $vvv2;
                            } else if ($vvv2 > $option1['maxqualified'] * 1.0 || $vvv2 < ($option1['minqualified'] * 1.0)) {
                                //存入整改数组
                                $zg1[] = $vvv2;
                            }
                        } else {
                            if (($vvv2 > ($option1['maxqualified'] * 1.0) || $vvv2 < ($option1['minqualified'] * 1.0)) && $option1['id'] != "14") {
                                //存入整改数组
                                $zg1[] = $vvv2;
                            }
                        }
                    } else {
                        $nums1 = explode(',', $vvv2);
                        $min = min($nums1);
                        $max = max($nums1);
                        $cha = $max - $min;
                        //构建特殊项数组
                        $arr1 = array('4', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51');
                        if (in_array($option1['id'], $arr1))//如果为特殊项，则为后一个减去前一个
                        {
                            $cha = $nums1[1] - $nums1[0];

                        }

                        if ($option1['maxdestroy'] != null || $option1['mindestroy'] != null) {

                            if ($cha > $option1['maxdestroy'] || $cha < $option1['mindestroy']) {
                                //存入质量锤数组
                                $zlc1[] = $vvv2;


                            } else if ($cha > $option1['maxqualified'] * 1.0 || $cha < ($option1['minqualified'] * 1.0)) {
                                //存入整改数组
                                //echo $cha."<br/>";
                                $zg1[] = $vvv2;
                            }

                        } else//如果未设置则只判断是否需要整改
                        {

                            if ($cha > ($option1['maxqualified'] * 1.0) || $cha < ($option1['minqualified'] * 1.0)) {
                                //存入整改数组
                                $zg1[] = $vvv2;
                            }

                        }

                    }
                }
            }

        }

        if ($level3info9) {//存在甲方工程部的测量
            foreach ($level3info9 as $vvv3) {
                if ($vvv3 != "" && $vvv3 != "," && $vvv3 != ",," && $vvv3 != ",,," && $vvv3 != ",,,,") {//未输入的值不显示
                    if ($option1['pointlength'] == 1) {
                        if ($option1['maxdestroy'] != null || $option1['mindestroy'] != null) {
                            if ($vvv3 > $option1['maxdestroy'] || $vvv3 < $option1['mindestroy']) {
                                //存入质量锤数组
                                $zlc2[] = $vvv3;
                            } else if ($vvv3 > $option1['maxqualified'] * 1.0 || $vvv3 < ($option1['minqualified'] * 1.0)) {
                                //存入整改数组
                                $zg2[] = $vvv3;
                            }
                        } else {
                            if (($vvv3 > ($option1['maxqualified'] * 1.0) || $vvv3 < ($option1['minqualified'] * 1.0)) && $option1['id'] != "14") {
                                //存入整改数组
                                $zg2[] = $vvv3;
                            }
                        }
                    } else {
                        $nums1 = explode(',', $vvv3);

                        $min = min($nums1);
                        $max = max($nums1);
                        $cha = $max - $min;
                        //构建特殊项数组
                        $arr1 = array('4', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51');
                        if (in_array($option1['id'], $arr1))//如果为特殊项，则为后一个减去前一个
                        {
                            $cha = $nums1[1] - $nums1[0];
                        }

                        if ($option1['maxdestroy'] != null || $option1['mindestroy'] != null) {

                            if ($cha > $option1['maxdestroy'] || $cha < $option1['mindestroy']) {
                                //存入质量锤数组
                                $zlc2[] = $vvv3;


                            } else if ($cha > $option1['maxqualified'] * 1.0 || $cha < ($option1['minqualified'] * 1.0)) {
                                //存入整改数组
                                $zg2[] = $vvv3;
                            }

                        } else//如果未设置则只判断是否需要整改
                        {

                            if ($cha > ($option1['maxqualified'] * 1.0) || $cha < ($option1['minqualified'] * 1.0)) {
                                //存入整改数组
                                $zg2[] = $vvv3;
                            }

                        }
                    }
                }
            }
        }
        //去除空数组
        $zg = array_filter($zg);//整改
        $zg1 = array_filter($zg1);
        $zg2 = array_filter($zg2);
        $zlc = array_filter($zlc);//质量锤
        $zlc1 = array_filter($zlc1);
        $zlc2 = array_filter($zlc2);


        //对存在质量锤和要整改的进行颜色显示变化
        $count = count($level1info7);//施工单位有几条数据
        //dump($level1info7);
        for ($x = 0; $x < $count; $x++) {
            $infoarrs = $level1info7[$x];//该条数据转换成数组

            //去除，
            $infoarr = str_replace(",", "|", $infoarrs);

            //判断该值是否存在于整改和质量锤中
            $inzg = in_array($infoarrs, $zg);
            $inzlc = in_array($infoarrs, $zlc);
            if ($inzg && !$inzlc) {//存在于整改不存在质量锤中
                $html = $infoarr;
                $zgg = 1;
            } elseif ($inzlc) {//存在于质量锤中
                $html = $infoarr;
                $zlcc = 1;
            } else {
                $html = $infoarr;
            }
            $pp = $html;
            $pp = rtrim($pp, "|");

            //$pp转换成数组
            $pp = explode("|", $pp);

            //判断是否存在设计值
            $havedesign = 0;
            $arr1 = array('4', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51');
            if (in_array($option1['id'], $arr1))//如果为特殊项，则为后一个减去前一个
            {
                $havedesign = 1;

            }
            foreach ($pp as $levkey => $pv) {
                $isodd = $levkey / 2;
                $isodd = is_int($isodd);//偶数
                if ($isodd && $havedesign) {
                    $parr[] = "<p style='color:#2BA245;'>" . $pv . "</p>";
                } else {
                    if ($zgg) {
                        $parr[] = "<p style='color:blue;'>" . $pv . "</p>";
                    } elseif ($zlcc) {
                        $parr[] = "<p style='color:red;'>" . $pv . "</p>";
                    } else {
                        $parr[] = "<p >" . $pv . "</p>";
                    }
                }
            }

            $newinfo[$x] = $parr;
            $parr = "";
            $html = "";
            $zgg = 0;
            $zlcc = 0;
        }

        $count1 = count($level2info8);//监理单位有几条数据
        for ($x1 = 0; $x1 < $count1; $x1++) {
            $infoarrs1 = $level2info8[$x1];//该条数据转换成数组

            //去除，
            $infoarr1 = str_replace(",", "|", $infoarrs1);

            //判断该值是否存在于整改和质量锤中
            $inzg1 = in_array($infoarrs1, $zg1);
            $inzlc1 = in_array($infoarrs1, $zlc1);
            if ($inzg1 && !$inzlc1) {//存在于整改不存在质量锤中
                $html = $infoarr1;
                $zgg = 1;
            } elseif ($inzlc1) {//存在于质量锤中
                $html = $infoarr1;
                $zlcc = 1;
            } else {
                $html = $infoarr1;
            }
            $pp1 = $html;

            $pp1 = rtrim($pp1, "|");

            //$pp1转换成数组
            $pp1 = explode("|", $pp1);

            //判断是否存在设计值
            $havedesign = 0;
            $arr1 = array('4', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51');
            if (in_array($option1['id'], $arr1))//如果为特殊项，则为后一个减去前一个
            {
                $havedesign = 1;

            }

            foreach ($pp1 as $levkey1 => $pv1) {
                $isodd = $levkey1 / 2;
                $isodd = is_int($isodd);//偶数
                if ($isodd && $havedesign) {
                    $parr1[] = "<p style='color:#2BA245;'>" . $pv1 . "</p>";
                } else {
                    if ($zgg) {
                        $parr1[] = "<p style='color:blue;'>" . $pv1 . "</p>";
                    } elseif ($zlcc) {
                        $parr1[] = "<p style='color:red;'>" . $pv1 . "</p>";
                    } else {
                        $parr1[] = "<p >" . $pv1 . "</p>";
                    }
                }
            }
            $newinfo1[$x1] = $parr1;
            $parr1 = "";
            $html = "";
            $zgg = 0;
            $zlcc = 0;
        }

        $count2 = count($level3info9);//施工单位有几条数据
        //dump($level3info9);
        for ($x2 = 0; $x2 < $count2; $x2++) {
            $infoarrs2 = $level3info9[$x2];//该条数据转换成数组

            //去除，
            $infoarr2 = str_replace(",", "|", $infoarrs2);

            //判断该值是否存在于整改和质量锤中
            $inzg2 = in_array($infoarrs2, $zg2);
            $inzlc2 = in_array($infoarrs2, $zlc2);
            if ($inzg2 && !$inzlc2) {//存在于整改不存在质量锤中
                $html = $infoarr2;
                $zgg = 1;
            } elseif ($inzlc2) {//存在于质量锤中
                $html = $infoarr2;
                $zlcc = 1;
            } else {
                $html = $infoarr2;
            }
            $pp2 = $html;

            $pp2 = rtrim($pp2, "|");

            //$pp1转换成数组
            $pp2 = explode("|", $pp2);
            //dump($pp2);
            //判断是否存在设计值
            $havedesign = 0;
            $arr1 = array('4', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51');
            if (in_array($option1['id'], $arr1))//如果为特殊项，则为后一个减去前一个
            {
                $havedesign = 1;

            }

            foreach ($pp2 as $levkey2 => $pv2) {

                $isodd = $levkey2 / 2;
                $isodd = is_int($isodd);//偶数
                if ($isodd && $havedesign) {
                    $parr2[] = "<p style='color:#2BA245;'>" . $pv2 . "</p>";
                } else {
                    if ($zgg) {
                        $parr2[] = "<p style='color:blue;'>" . $pv2 . "</p>";
                    } elseif ($zlcc) {
                        $parr2[] = "<p style='color:red;'>" . $pv2 . "</p>";
                    } else {
                        $parr2[] = "<p >" . $pv2 . "</p>";
                    }
                }
            }
            $newinfo2[$x2] = $parr2;
            //dump($newinfo2);
            $parr2 = "";
            $html = "";
            $zgg = 0;
            $zlcc = 0;
        }
        //dump($newinfo2);

        //字符串
        $zzg = implode(",", $zg);
        $zzg1 = implode(",", $zg1);
        $zzg2 = implode(",", $zg2);
        $zzlc = implode(",", $zlc);
        $zzlc1 = implode(",", $zlc1);
        $zzlc2 = implode(",", $zlc2);
        //拼接字符串
        $zzzg = "<br/>施工单位：" . $zzg . "<br/>监理单位：" . $zzg1 . "<br/> 甲方工程部：" . $zzg2;
        $zzzlc = "<br/>施工单位：" . $zzlc . " <br/>监理单位：" . $zzlc1 . " <br/>甲方工程部：" . $zzlc2;

        $this->assign('newinfo2', $newinfo2);
        $this->assign('newinfo1', $newinfo1);
        $this->assign('newinfo', $newinfo);//颜色信息数组
        $this->assign('zg', $zg);//整改
        $this->assign('zlc', $zlc);//质量锤
        $this->assign('zzzg', $zzzg);//拼接后的整改
        $this->assign('zzzlc', $zzzlc);//拼接后的质量锤
        $this->assign('persent', $persent);//百分比
        //数组
        $this->assign('level1info7', $level1info7);
        $this->assign('level2info8', $level2info8);
        $this->assign('level3info9', $level3info9);
        //以空格隔开的数组
        $this->assign('level1info', $level1info);
        $this->assign('level2info', $level2info);
        $this->assign('level3info', $level3info);
        //判断每个数组的长度
        $cont1 = count($level1info);
        $cont2 = count($level2info);
        $cont3 = count($level3info);
        $maxcount = max($cont1, $cont2, $cont3);
        //根据得到的maxcount，得到那个数组最长
        if ($maxcount == $cont1)
            $maxarr = $level1info;
        elseif ($maxcount == $cont2)
            $maxarr = $level2info;
        elseif ($maxcount == $cont3)
            $maxarr = $level3info;
        $this->assign('maxcount', $maxcount);
        $this->assign('maxarr', $maxarr);
        //以|隔开的数组
        $this->assign('bbb1', $bbb1);
        $this->assign('bbb2', $bbb2);
        $this->assign('bbb3', $bbb3);
        $this->assign('data2', $data2);
        //每个部门的合格率
        $this->assign('level1persent', $level1persent);
        $this->assign('level2persent', $level2persent);
        $this->assign('level3persent', $level3persent);

        // 楼栋
        $map = array('id' => array('in', $find['postion']));
        $floor = M('jk_floor')->where($map)
            ->field('id,title,pid,periods,batch,blocks')
            ->select();
        //dump($floor);
        foreach ($floor as $v) {
            if ($v['title']) {//title不为空
                $sfloor = $sfloor . $v['title'] . '—';
            }
            if ($v['pid'] == 0) {//楼栋批次等信息
                $find['ownid'] .= ' ';
                if ($v['periods']) {
                    $find['ownid'] = $find['ownid'] . $v['periods'] . '期';
                }
                if ($v['batch']) {
                    $find['ownid'] = $find['ownid'] . $v['batch'] . '批次';
                }
                if ($v['blocks']) {
                    $find['ownid'] = $find['ownid'] . $v['blocks'] . '标段';
                }

            }
        }
        //dump($sfloor);
        //去除楼层拼接的最后一个‘一’
        $sfloor = rtrim($sfloor, '—');
        $find['project_ids'] = $sfloor;//楼层拼接


        // 时间戳记截取13->10;
        if (strlen($find['create_time']) > 10) {
            $find['create_time'] = time_format(substr($find['create_time'], 0, 10));
        } else {
            $find['create_time'] = time_format($find['create_time']);
        }
        if (strlen($find['update_time']) > 10) {
            $find['update_time'] = time_format(substr($find['update_time'], 0, 10));
        } else {
            $find['update_time'] = time_format($find['update_time']);
        }
        // 实测提交人id->姓名
        $authInfo = M('member')->where('uid=' . $find['userid'])->field('nickname,username')->find();

        $find['authid'] = $authInfo['nickname'];
        if ($authInfo['username']) {
            $find['authid'] = $find['authid'] . '[' . $authInfo['username'] . ']';
        }

        //实测选项
        $optionDB = M('jk_survey_option');
        $map = array();
        $map['id'] = $find['inspect'];
        $option = $optionDB->where($map)
            ->field('id,title,pid,minqualified,maxqualified,mindestroy,maxdestroy,pointlength')->find();
        $find['option_id'] = $option['title'];
        //状态：
        $starr = array('0' => '合格', '1' => '需整改', '2' => '需质量锤');
        $find['status'] = $starr[$find['is_out_range']];
        //构建测量信息
        $info = explode('|', $find['info']);
        $infos = "";
        $Rectification = "";
        $destroy = "";
        $i = 1;
        //计算合格率
        $notqualified = 0;//不合格数量
        $totalnum = 0;//总填写数量
        foreach ($info as $v1) {
            if ($v1 != "" && $v1 != "," && $v1 != ",," && $v1 != ",,," && $v1 != ",,,,") {//未输入的值不显示
                $totalnum += 1;
                $infos .= $i . '.' . $v1 . " ";
                //判断是否超过合格标准
                if ($option['pointlength'] == 1) {
                    if ($option['maxdestroy'] != null || $option['mindestroy'] != null) {
                        if ($v1 > $option['maxdestroy'] || $v1 < $option['mindestroy']) {
                            //存入质量锤数组
                            $notqualified += 1;
                            $destroy .= $i . '.' . $v1 . " ";
                        } else if ($v1 > $option['maxqualified'] * 1.0 || $v1 < ($option['minqualified'] * 1.0)) {
                            //存入整改数组
                            $notqualified += 1;
                            $Rectification .= $i . '.' . $v1 . " ";
                        }
                    } else//如果未设置则只判断是否需要整改
                    {

                        if (($v1 > ($option['maxqualified'] * 1.0) || $v1 < ($option['minqualified'] * 1.0)) && $option['id'] != "14") {
                            //存入整改数组
                            $notqualified += 1;
                            $Rectification .= $i . '.' . $v1 . " ";
                        }

                    }
                    //轴线偏差
//         	        if ($option['id'] == "15") {
//         	            if ($v1 > ($option['maxqualified'] * 1.0) || $v1 < ($option['minqualified'] * 1.0)) {
//         	                //存入整改数组
//         	               // $Rectification .= $i.'.'.$v1." ";
//         	            }

//         	        }

                } else {
                    $nums = explode(',', $v1);

                    $min = min($nums);
                    $max = max($nums);
                    $cha = $max - $min;
                    //构建特殊项数组
                    $arr = array('4', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51');
                    if (in_array($option['id'], $arr))//如果为特殊项，则为后一个减去前一个
                    {
                        $cha = $nums[1] - $nums[0];
                    }

                    if ($option['maxdestroy'] != null || $option['mindestroy'] != null) {

                        if ($cha > $option['maxdestroy'] || $cha < $option['mindestroy']) {
                            //存入质量锤数组
                            $notqualified += 1;
                            $destroy .= $i . '.' . $v1 . " ";


                        } else if ($cha > $option['maxqualified'] * 1.0 || $cha < ($option['minqualified'] * 1.0)) {
                            //存入整改数组
                            $notqualified += 1;
                            $Rectification .= $i . '.' . $v1 . " ";
                        }

                    } else//如果未设置则只判断是否需要整改
                    {

                        if ($cha > ($option['maxqualified'] * 1.0) || $cha < ($option['minqualified'] * 1.0)) {
                            //存入整改数组
                            $notqualified += 1;
                            $Rectification .= $i . '.' . $v1 . " ";
                        }

                    }

                }
            }
            $i++;
        }
        $find['info'] = $infos;

        $find['Rectification'] = $Rectification;
        $find['destroy'] = $destroy;
        $find['qualified'] = ($totalnum - $notqualified) / $totalnum * 100 . "%";//合格率
        //var_dump($Rectification);die;
        $this->assign('data', $find);
        $this->assign('infos', $infos);
        $this->display('/JKProgram@JKProgram/measuredetail');

    }

    /**
     * 函数用途描述：概况统计
     * @date: 2017年5月27日 上午10:38:06
     *
     * @author : 谭杰文
     * @param proID :项目ID option_id:检查项id
     *            :
     * @return :
     */
    public function detail($proID = '', $option_id = "")
    {
        $where = "type=0";
        $pro_ids = get_my_projects();
        //接收项目条件
        if ($proID != '') {
            $where .= " AND ownid='" . $proID . "'";
            $this->assign('select_projectid', $proID);
        }else{
            //获取项目条件

            $pro_str = implode(',',$pro_ids);
            $where  .= " AND ownid in(" . $pro_str . ")";
        }

        //接收检查项条件
        if ($option_id != '') {
            //获取子级检查项
            $option_ids = getids($option_id, 0);
            if ($option_ids)
                $where .= " AND option_id in" . $option_ids;
            else
                $where .= " AND option_id in" . $option_id;
            $this->assign('select_option_id', $option_id);
        }
        //计算新增问题
        //获取当天的年份、月份、当天的0点时间戳
        $y = date("Y");
        $m = date("m");
        $d = date("d");
        $todayTime = mktime(0, 0, 0, $m, $d, $y) * 1000;
        $where1 = $where . " AND create_time>'" . $todayTime . "'";
        //算出今天新增问题数
        $newProblem = M("jk_program")->where($where1)->count();
        $this->assign('newProblem', $newProblem);
        //算出总的问题数
        $totalProblem = M("jk_program")->where($where)->count();

        $this->assign('totalProblem', $totalProblem);
        //算出待整改问题
        $where1 = $where . " AND status=0";
        $status0Problem = M("jk_program")->where($where1)->count();
        $this->assign('status0Problem', $status0Problem);
        //正常关闭的问题
        $where1 = $where . " AND status=1";
        $status1Problem = M("jk_program")->where($where1)->count();
        $this->assign('status1Problem', $status1Problem);
        //待审批问题
        $where1 = $where . " AND status=2";
        $status2Problem = M("jk_program")->where($where1)->count();
        $this->assign('status2Problem', $status2Problem);
        //非正常关闭的问题
        $where1 = $where . " AND status=3";
        $status3Problem = M("jk_program")->where($where1)->count();
        $this->assign('status3Problem', $status3Problem);
        //计算各个检查大项对应的子级检查项的id(日常巡查的)
        $options = array();
        $i = 0;
        $pids = M("jk_option")->field('id,title')->where("pid=0 and status>0")->select();
        $where1 = "type=0";
        //接收项目条件
        if ($proID != '')
            $where1 .= " AND ownid='" . $proID . "'";
        $totalnum = M("jk_program")->where($where1)->count(); //总日常巡查的问题数
        //echo M()->getLastSql();
        foreach ($pids as $v) {
            $options[$i]['title'] = $v['title'];
            $options[$i]['id'] = $v['id'];

            $ids = getids($v['id'], 0);

            if ($ids != ")")
                $options[$i]['ids'] = $ids;
            else
                $options[$i]['ids'] = $v['id'];
            //计算每一个对应的问题数量
            $where1 = "type=0";//只选择日常巡查的问题
            if ($proID != '')
                $where1 .= " AND ownid='" . $proID . "'";
            $where1 .= " AND option_id in" . $options[$i]['ids'];
            $options[$i]['count'] = M("jk_program")->where($where1)->count();
            //计算每一个对应的百分比
            $options[$i]['percent'] = round($options[$i]['count'] * 100 / $totalnum, 2) . "%";
            $i++;
        }
        $this->assign('options', $options);
        //构建项目条件数组
        $pro_where['status'] = array('eq',1);
        $pro_where['id']     = array('in',$pro_ids);
        $projectList = M("jk_project")->where($pro_where)->field('id,name')->select();
        $this->assign('projectList', $projectList);
        //构建检查项条件数组
        $measureList = M("jk_option")->where("status>0")->field('id,title')->select();
        $this->assign('measureList', $pids);//修改为最上级条件
        $this->display('/JKProgram@JKProgram/detail');
//         $id=I('get.id', 0, 'intval');
//         if($id){
//             $_SESSION['proId']=$id;
//             action_log('show_project', 'JkProjcet', $id, UID);
//         }
//         //接收时间条件（转换为毫秒）
//         $aSearch1 = I('get.usearch1','')*1000;
//         $aSearch2 = I('get.usearch2','')*1000;
//         $map="ownid=".$_SESSION['proId'];
//         //设置搜索条件
//         if(!empty($aSearch1)){
//             $map.=" AND create_time >= '$aSearch1'";
//         }
//         if(!empty($aSearch2)){

//             $map.= " and create_time<='$aSearch2' ";
//         }
//        // $map['ownid'] = $_SESSION['proId'];

//         //算出整改单位个数
//         $problemlist = M('jk_program')->field("count(*) as count,target_id,ownid,create_time")->group("target_id")->having($map)->select();
//         //var_dump($problemlist);die;
//         $totalCount = M('jk_program')->field("count(*) as count,target_id,ownid,create_time")->group("target_id")->having($map)->count();
//         $builder = new AdminListBuilder();
//         $builder->title('项目问题统计');
//         $builder->meta_title = '项目问题统计';
//         //选项问题
//         foreach ($problemlist as &$val) {
//             //整改单位
//             $val['target']=M('auth_group')->where('id=' . $val['target_id'])->getField('title');
//             //分别算出接受数，总完成数，正常完成数，非正常完成数，待整改，待审核
//             $val['total']=0;//总接受数
//             $val['totalwc']=0;//总完成数
//             $val['zc']=0;//正常完成
//             $val['fzc']=0;//非正常完成
//             $val['dzg']=0;//待整改
//             $val['dsh']=0;//待审核
//             $data=M('jk_program')->field("status,target_id,ownid")->where($map)->select();
//             foreach ($data as $v)
//             {
//                 if($v['target_id']==$val['target_id']){
//                     $val['total']+=1;
//                     if($v['status']==0)
//                         $val['dzg']+=1;
//                     elseif($v['status']==1){
//                         $val['zc']+=1;
//                         $val['totalwc']+=1;
//                     }
//                     elseif($v['status']==2)
//                         $val['dsh']+=1;
//                     elseif($v['status']==3){
//                         $val['fzc']+=1;
//                         $val['totalwc']+=1;
//                     }
//                 }

//             }
//            ;
//         }
//         unset($val);
//         //搜索框
//         $builder->setSearchPostUrl(U('JKProgram/detail'))
//         ->search('时间从','usearch1','timer','','','','');
//         $builder->search('到','usearch2','timer','','','','');

//         $builder
//         ->keyText('target', '整改单位')
//         ->keyText('total', '接受数')
//         ->keyText('totalwc', '完成数')

//         ->keyText('zc', '正常完成数')
//         ->keyText('fzc', '非正常完成数')
//         ->keyText('dzg', '待整改')
//         ->keyText('dsh', '待审核');

//         $builder->data($problemlist);
//         $builder->pagination($totalCount, $r);
//         $builder->display();
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

        $id = I('get.id', 0, 'intval');
        if ($id) {
            $_SESSION['proId'] = $id;
            action_log('show_project', 'JkProjcet', $id, UID);
        }
        //接收时间条件（转换为毫秒）
        $aSearch1 = I('get.usearch1', '') * 1000;
        $aSearch2 = I('get.usearch2', '') * 1000;
        $map = "ownid=" . $_SESSION['proId'];
        $projectname = M('jk_project')->where("id=" . $_SESSION['proId'])->getField('name');
        //设置搜索条件
        if (!empty($aSearch1)) {
            $aSearch1 = strtotime(date('Y-m-d', I('get.usearch1', '')).' 00:00:00') * 1000;
            $map .= " AND create_time >= '$aSearch1'";
        }
        if (!empty($aSearch2)) {
            $aSearch2 = strtotime(date('Y-m-d', I('get.uSearch2', '')).' 23:59:59') * 1000;
            $map .= " and create_time<='$aSearch2' ";
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
            $val['projectname'] = $projectname;
            //整改人
            $val['target'] = M('member')->where('uid=' . $val['authid'])->getField('username');
            //角色
            $val['targetrole'] = M('member')->where('uid=' . $val['authid'])->getField('position');

            $val['department'] = M('member')->where('uid=' . $val['authid'])->getField('department');
            //分别算出总提报数，已整改数，正常完成数，非正常完成数，待整改，待审核
            $val['total'] = 0;//总提报数
            $val['totalwc'] = 0;//总完成数
            $val['zc'] = 0;//正常完成
            $val['fzc'] = 0;//非正常完成
            $val['dzg'] = 0;//待整改
            $val['dsh'] = 0;//待审核
            $data = M('jk_program')->field("status,target_id,ownid,authid")->where($map)->select();
            foreach ($data as $v) {
                if ($v['authid'] == $val['authid']) {
                    $val['total'] += 1;
                    if ($v['status'] == 0)
                        $val['dzg'] += 1;
                    elseif ($v['status'] == 1) {
                        $val['zc'] += 1;
                        $val['totalwc'] += 1;
                    } elseif ($v['status'] == 2)
                        $val['dsh'] += 1;
                    elseif ($v['status'] == 3) {
                        $val['fzc'] += 1;
                        $val['totalwc'] += 1;
                    }
                }

            };
        }
        unset($val);
        //搜索框
        $builder->setSearchPostUrl(U('JKProgram/problemSubmit'))
            ->search('时间从', 'usearch1', 'timer', '', '', '', '');
        $builder->search('到', 'usearch2', 'timer', '', '', '', '');
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
     * @date：2016年11月23日 下午4:04:42
     * @author：luoj
     * @param：
     * @return：
     **/
    public function floorlist()
    {
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
    public function savefloor()
    {

        if (IS_POST) {
            $id = $_POST['id'];
            $ids = explode(',', $id);
            $data = M('jk_floor')->create();
            $map['id'] = array('in', $ids);
            $data['update_time'] = time();
            unset($data['id']);
            $data['imgpath'] = coverIds2Path($data['imgid']);
            $res = M('jk_floor')->where($map)->save($data);
            $root = getRoot('jk_floor', $ids[0]);
            if ($res) {

                //后台调用变更接口传入新的数据

                $res = $this->update_examine_floor($root['id'], $ids);
                if ($res) {
                    $this->success($title . '编辑成功', U('j_k_program/selectfloor/', array('id' => $root['id'], 'name' => $root['title'])));
                } else {
                    $this->success($title . '编辑成功但未修改MDM对应房间编码', U('j_k_program/selectfloor/', array('id' => $root['id'], 'name' => $root['title'])));
                }
            } else {
                $this->error('操作失败');
            }
            return;
        }
    }

    /**
     * 函数用途描述：新版楼栋图纸url保存修改
     * @date: 2017年6月3日 下午3:58:26
     * @author: tanjiewen
     * @param:ids楼栋id，imgid，图片id
     * @return:
     */
    public function savefloor1()
    {
        $ids = $_POST['ids'];//楼栋id
        $imgid = $_POST['imgid'];//图纸id
        if ($imgid == '' || $imgid == null) {
            echo '2';
            die;
        }
        if ($ids == '' || $ids == null) {
            echo '3';
            die;
        }
        $ids = explode(',', $ids);
        $map['id'] = array('in', $ids);
        $data['imgid'] = $imgid;
        $data['imgpath'] = coverIds2Path($imgid);
        $res = M('jk_floor')->where($map)->save($data);
        //$root=getRoot('jk_floor',$ids[0]);
        if ($res) {
            echo '1';
            //$this->success('操作成功',U('j_k_program/selectfloor/',array('id'=>$root['id'],'name'=>$root['title'])));
        } else {
            //$this->error('操作失败');
            $sql = M()->getLastSql();
            echo $sql . $imgid . $ids;
        }


    }

    /**
     * 函数用途描述：编辑楼层与房间信息
     * @date: 2016年11月25日 上午9:32:38
     * @author: luojun
     * @param:
     * @return:
     */
    public function editfloor()
    {

        if ($_SESSION['is_examine'] == 1) {
            $this->error('该状态不能进行更改房间信息,如需修改请先驳回');
        }
        $id = array_unique((array)I('id', 0));
        if (is_array($id) && count($id) == 1) {
            $iflag = 1;//编辑单个数据
        }

        $id = is_array($id) ? implode(',', $id) : $id;

        if (empty($id)) {
            $this->error('未选择操作数据！');
        }

        $builder = new AdminConfigBuilder();
        if ($iflag) {
            $map['id'] = $id;
            $data = M('jk_floor')->where($map)
                ->field('id,title,imgid')
                ->find();
        } else {
            $data['id'] = $id;
        }

        $data['submit'] = 1;
        $builder->keyReadOnly('id', '编号');
        if ($iflag) {
            $builder->keyText('title', '单元/楼层/房间名称*');
        }

        $builder->keyHidden('submit', '')->title('修改单元/楼层/房间信息')
            ->keySingleImage('imgid', '平面图')
            ->data($data)
            ->buttonSubmit(U('JKProgram/savefloor'))
            ->buttonBack()
            ->display();;
    }

    public function editfloor1()
    {

        //查询出对应的楼栋信息放入右边
        $floorid = $_SESSION['selectfloorid'];
        //先查询出该楼栋下的所有子级
        $unit_ids = M('jk_unit_tmp')->where('build_id=' . $floorid)->field('id')->select();
        $floor_ids = M('jk_floor_tmp')->where('build_id=' . $floorid)->field('id')->select();
        $room_ids = M('jk_room_tmp')->where('build_id=' . $floorid)->field('id')->select();
        $all_ids = array_merge($unit_ids, $floor_ids, $room_ids);
        foreach ($all_ids as $v) {
            $ids[] = $v['id'];
        }
        $position_where['status'] = 1;
        $position_where['id'] = array('in', $ids);
        $list = M('jk_floor')->where($position_where)->field('id,title,sort,pid,status,imgpath')->select();
        $data = list_to_tree($list, 'id', 'pid', '_', $floorid);
        $this->assign('arr_floor', $data);

        $nodelist = D('JKProject/JKProjectSurvey')->getTree(0, 'id,title,sort,pid,status');
        //$this->assign('ids', $id);
        $this->assign('nodeList', $nodelist);
        $this->meta_title = L('修改楼栋与房间信息');
        $this->display('/JKProgram@JKProgram/surfloor1');

    }

    /**
     * 函数用途描述：删除楼层与房间
     * @date: 2016年11月25日 上午9:33:05
     * @author: luojun
     * @param:
     * @return:
     */
    public function delfloor($build_id = 0)
    {
        if ($_SESSION['is_examine'] == 1) {
            $this->error('该状态不能进行更改房间信息,如需修改请先驳回');
        }
        $id = array_unique((array)I('id', 0));
        $map = array('id' => array('in', $id));
        //如果是已经有编码的，则无法删除
        /*  $floors =  M('jk_floor')->where($map)->getField('masterCode',true);
        foreach ($floors as $floor){
        	if($floor){
        		$this->error('已生成编码并通过审核，无法删除');
        	}
        } */
        $save['status'] = -1;
        $save['update_time'] = time();
        M('jk_floor')->where($map)->save($save);
        $map = array('pid' => array('in', $id));
        M('jk_floor')->where($map)->save($save);


        // $res = $this->update_examine_floor($build_id, $id);
        $this->success('删除成功');
        /*   if ($res) {
              $this->success('删除成功');
          } else {
              $this->success('删除成功但上传变更到MDM失败');
          } */


    }

    /**
     * 函数用途描述：各项目使用情况统计
     * @date: 2017年07月27日 下午 17:53:05
     * @author: tanjiewen
     * @param:
     * @return:
     */
    public function useproject($page = 1, $r = 20)
    {
        $map['status'] = array('egt', 0);
        $map_str = "status>0";
        //接收时间条件（转换为毫秒）
        $aSearch1 = I('get.usearch1', '') * 1000;
        $aSearch2 = I('get.usearch2', '') * 1000;

        js_log('start:'.time());
        $start=time();
        if ($aSearch1) {
            $aSearch1 = strtotime(date('Y-m-d', I('get.usearch1', '')).' 00:00:00') * 1000;
            setcookie('start_time', $aSearch1 / 1000);
        } else {
            setcookie('start_time', 0);
        }
        if ($aSearch2) {
            $aSearch2 = strtotime(date('Y-m-d', I('get.usearch2', '')).' 23:59:59') * 1000;
            setcookie('end_time', $aSearch2 / 1000);
        } else {
            setcookie('end_time', 0);
        }
        $where['_string'] = "1=1 ";
        //设置搜索条件
        if (!empty($aSearch1)) {
            //$map.=" AND o.used_time >= '$aSearch1' ";
            $where['_string'] .= "and create_time>='$aSearch1' ";
        }
        if (!empty($aSearch2)) {
            $where['_string'] .= "and create_time<='$aSearch2' ";
        }
        //项目筛选
        $id = I('get.ownid', '');

        //找出对应用户组所属项目
        if (!$id) {
            $id = 0;
        }
        // if($id){

        $proids = new_get_select_projects($id);

        $map['id'] = array('in', $proids);
        $map['_string'] = "pid IS NOT NULL";
        //$map['id']    =   array('in', get_my_projects($id));
        //记录条件用以传递到导出excel
        $proids_str = implode($proids, ",");

        $map_str .= "  AND id in(" . $proids_str . ")";
        $_SESSION['map_str'] = $map_str;
        // }
        //设置实测表单地址及参数
        $measure_attr['href'] = U('ex_use_measure_word') . "&where=" . $where['_string'];
        //问题类型
        $type = I('get.type', '');
        if ($type == null || $type == '')
            $type = 3;
        if (!empty($type) && $type != null) {
            if ($type != 3)
                $where['type'] = $type;
        }
        if ($type == 0)
            $where['type'] = $type;

        //分期筛选
        $stage = I('get.stage', '');
        if ($stage > '0'){
            $builds=M('jk_floor')->where("StagesCode='$stage'")->getField('id',true);
            $where['build_id'] = array('in',$builds);
//            dump($builds);
        }


        $temp_str = $where['_string'];
        //先查询出项目列表
        $projects = M('jk_project')->where($map)->page($page, $r)->field('name,id,pid,ProjectNumber')->select();
        $totalCount = M('jk_project')->where($map)->count();
        js_log('cost:'.(time()-$start));

        $stage_perants=array();
        //计算各个项目的问题统计
        foreach ($projects as &$project) {
            //问题总数
            unset($where['status']);
            $where['ownid'] = $project['id'];
//            $project['total'] = M('jk_program')->where($where)->count();

            $over_count = 0;//总的正常完成数
            //所属区域
            $pid = M('auth_group')->where('id=' . $project['pid'])->getField('pid');
            $project['area'] = M('auth_group')->where('id=' . $pid)->getField('title');

            unset($where['status']);
            $count = M('jk_program')->where($where)->group('status')->getField('status,count(status) as count',true);
            $project['total'] = $count[0]+$count[1]+$count[2]+$count[3]+$count[4] ;
//            var_dump($count);
            //待整改问题数量
//            $where['status'] = 0;
            $project['status0_num'] = $count[0]?$count[0]:0;
            //待审核问题
//            $where['status'] = 2;
            $project['status2_num'] = ($count[2]?$count[2]:0)+($count[4]?$count[4]:0);
//            $where['status'] = 4;
//            $project['status2_num'] += M('jk_program')->where($where)->count();
            //正常关闭问题数量
//            $where['status'] = 1;
            $project['status1_num'] = $count[1]?$count[1]:0;
            //非正常关闭问题
//            $where['status'] = 3;
            $project['status3_num'] = $count[3]?$count[3]:0;

            //闭合率
            $total_over = $project['status1_num'] + $project['status3_num'];
            $project['zbihe'] = round($total_over /  $project['total'] * 100, 2) . "%";
            //超期数量
//            unset($where['type']);
            $where['_string'] = $temp_str." and (status=1 OR status=3) and is_over=1";
            $over_num = M('jk_program')->where($where)->count();
            $project['over'] = $over_num;
            //总日常巡查关闭数
            $where['_string'] = $temp_str." and (status=1 OR status=3)";
            $total_num = M('jk_program')->where($where)->count();
            if($type!=3){
                $where['type'] = $type;
            }

            $where['_string'] = $temp_str;
            //统计在正常时间内完成的数量
            $project['bihe'] = round(($total_num-$over_num) / $total_num * 100, 2) . "%";
            if($id>0){
                $stage_perants[]=$project['ProjectNumber'];
            }

        }
        js_log('cost:'.(time()-$start));

        unset($where['ownid']);
        unset($where['status']);
        $builder = new AdminListBuilder();
        $builder->title('项目使用情况列表');
        $builder->meta_title = '项目使用情况列表';
        $attr['target-form'] = 'ids';
        $attr['href'] = U('ex_use_word') . "&where=" . $where['_string']."&type=".$type;


        //条件存入cookie中
        $builder->button('导出问题概况', $attr);
        $builder->button('导出实测实量概况', $measure_attr);
        //根据项目筛选
        //获取该角色下的所有项目
        $builder->setSelectPostUrl(U('JKProgram/useproject') . '&ownid=' . $id . "&usearch1=" . ($aSearch1 / 1000));
        //项目筛选
        $builder->buttonModalPopup(U('JKProgram/selectproject'),
            '',
            '根据项目筛选',
            array('data-title' => ('选择项目')));
        //根据类型筛选
        $typeArr = array();
        $typeArr[0]['id'] = '3';
        $typeArr[0]['value'] = '全部';
        $typeArr[1]['id'] = '0';
        $typeArr[1]['value'] = '日常巡查';
        $typeArr[2]['id'] = '1';
        $typeArr[2]['value'] = '实测实量';

//        获取项目分期数据
        if($id>0){
            $stage_perants = implode(',', $stage_perants);
            $stage_map['ParentCode'] = array('in',$stage_perants);
            $stages[0]=array('id'=>0,'value'=>'全部分期');
            $stages_data = M('jk_stage')->where($stage_map)->group('StagesCode')->getField('StagesCode,StagesCode as id,StagesName as value');
            $stages+=$stages_data;
//            dump( $stages);
            if(count($stages)>2){
                $builder->select(L('项目分期：'), 'stage', 'select', L('项目分期'), '', '', $stages);
            }

        }


        $builder->select(L('问题类型：'), 'type', 'select', L('问题类型'), '', '', $typeArr);
        //搜索框
        $builder->setSearchPostUrl(U('JKProgram/useproject'))
            ->search('时间从', 'usearch1', 'timer', '', '', '', '');
        $builder->search('到', 'usearch2', 'timer', '', '', '', '');
        //列表
        $builder->keyText('area', '所属区域')
            ->keyText('name', '项目名称')
            ->keyText('total', '问题总数')
            ->keyText('status0_num', '待整改问题数')
            ->keyText('status2_num', '待审核问题数')
            ->keyText('status1_num', '正常关闭问题数')
            ->keyText('status3_num', '非正常关闭问题数')
            ->keyText('over', '逾期闭合问题')
            ->keyText('zbihe', '总闭合率')
            ->keyText('bihe', '整改期限内闭合率');
        $builder->data($projects);
        $builder->pagination($totalCount, $r);

        js_log('end:'.time());
        $builder->display();
    }

    /**
     * 函数用途描述：各项目使用情况统计Excel导出
     * @date: 2017年07月27日 下午 17:53:05
     * @author: tanjiewen
     * @param: where问题筛选条件 map_str项目筛选条件
     * @return:
     */
    public function ex_use_word()
    {
        $where['_string'] = I('get.where', '');
        $temp_str = $where['_string'];
        //问题类型
        $type = I('get.type', '');
        if($type!=3){
            $where['type'] = $type;
        }
        $map = $_SESSION['map_str'];

        if ($_COOKIE['start_time']) {
            $date = date("Y-m-d", $_COOKIE['start_time']);
        }
        if ($_COOKIE['end_time']) {
            $date .= "-" . date("Y-m-d", $_COOKIE['end_time']);
        }

        //先查询出项目列表
        $projects = M('jk_project')->where($map)->field('name,id,pid')->select();
        //引入phpexcel类

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
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(30);
        //合并cell
        $objPHPExcel->getActiveSheet()->mergeCells('A1:I1');
        //设置水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A:I')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '金科质检系统应用' . $date . '数据报表')
            ->setCellValue('A2', '区域')
            ->setCellValue('B2', '项目名称')
            ->setCellValue('C2', '问题总数')
            ->setCellValue('D2', '待整改问题数')
            ->setCellValue('E2', '待审核问题数')
            ->setCellValue('F2', '正常关闭问题数')
            ->setCellValue('G2', '非正常关闭问题数')
            ->setCellValue('H2', '总闭合率')
            ->setCellValue('I2', '整改期限内闭合率');;
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A2:B2')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('C2:D2')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('E2:F2')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('F2:G2')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('G2:H2')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('H2:I2')->getFont()->setBold(true);
        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel');
        $filename = "shujubaobiao" . $date;
        $filename = iconv("utf-8", "gb2312", $filename);
        header('Content-Disposition: attachment;filename=' . $filename . '.xls"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');
        //计算各个项目的问题统计
        $i = 3;
        foreach ($projects as &$project) {
            //问题总数
            unset($where['status']);
            $where['ownid'] = $project['id'];
            $project['total'] = M('jk_program')->where($where)->count();
            $over_count = 0;//总的正常完成数
            //所属区域
            $pid = M('auth_group')->where('id=' . $project['pid'])->getField('pid');
            $project['area'] = M('auth_group')->where('id=' . $pid)->getField('title');
            //待整改问题数量
            $where['status'] = 0;
            $project['status0_num'] = M('jk_program')->where($where)->count();
            //待审核问题
            $where['status'] = 2;
            $project['status2_num'] = M('jk_program')->where($where)->count();
            $where['status'] = 4;
            $project['status2_num'] += M('jk_program')->where($where)->count();
            //正常关闭问题数量
            $where['status'] = 1;
            $project['status1_num'] = M('jk_program')->where($where)->count();
            //非正常关闭问题
            $where['status'] = 3;
            $project['status3_num'] = M('jk_program')->where($where)->count();
            //闭合率
            $total_over = $project['status1_num'] + $project['status3_num'];
            $project['zbihe'] = sprintf("%0.2f",$total_over /  $project['total'] * 100)."%";
            //该项目问题完成数
            //超期数量
            unset($where['status']);
            unset($where['type']);
            $where['_string'] = $temp_str." and (status=1 OR status=3) and is_over=1 and type=0";
            $over_num = M('jk_program')->where($where)->count();
            //总日常巡查关闭数
            $where['_string'] = $temp_str." and (status=1 OR status=3) and type=0";
            $total_num = M('jk_program')->where($where)->count();

            //统计在正常时间内完成的数量
            $project['bihe'] = round(($total_num-$over_num) / $total_num * 100, 2) . "%";

            if($type!=3){
                $where['type'] = $type;
            }
            $where['_string'] = $temp_str;
            //设置Excel
            $objPHPExcel->getActiveSheet(0)->setCellValue('A' . $i, $project['area']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('B' . $i, $project['name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C' . $i, $project['total']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D' . $i, $project['status0_num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E' . $i, $project['status2_num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F' . $i, $project['status1_num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G' . $i, $project['status3_num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H' . $i, " ".$project['zbihe']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I' . $i, " ".$project['bihe']);

            $i++;
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');
        die;
    }

    /**
     * 函数用途描述：各项目实测情况统计Excel导出
     * @date: 2017年08月03日 下午 16:53:05
     * @author: tanjiewen
     * @param: where问题筛选条件 map_str项目筛选条件
     * @return:
     */
    public function ex_use_measure_word()
    {
        $where['_string'] = I('get.where', '');

        $map = $_SESSION['map_str'];
        if ($_COOKIE['start_time']) {
            $date = date("Y-m-d", $_COOKIE['start_time']);
        }
        if ($_COOKIE['end_time']) {
            $date .= "-" . date("Y-m-d", $_COOKIE['end_time']);
        }

        //先查询出项目列表
        $projects = M('jk_project')->where($map)->field('name,id,pid')->select();
        //引入phpexcel类

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
        $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');
        //设置水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A:D')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '金科质检系统实测实量' . $date . '应用数据报表')
            ->setCellValue('A2', '区域')
            ->setCellValue('B2', '项目名称')
            ->setCellValue('C2', '实测任务总数')
            ->setCellValue('D2', '触发问题总数');

        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A2:B2')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('C2:D2')->getFont()->setBold(true);
        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel');
        $filename = "shicebaobiao" . $date;
        $filename = iconv("utf-8", "gb2312", $filename);
        header('Content-Disposition: attachment;filename=' . $filename . '.xls"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');
        //计算各个项目的问题统计
        $i = 3;
        foreach ($projects as &$project) {
            //问题总数
            unset($where['status']);
            $where['ownid'] = $project['id'];
            $where['type'] = 1;
            $project['total'] = M('jk_program')->where($where)->count();
            $over_count = 0;//总的正常完成数
            //所属区域
            $pid = M('auth_group')->where('id=' . $project['pid'])->getField('pid');
            $project['area'] = M('auth_group')->where('id=' . $pid)->getField('title');
            //实测任务总数
            $project['measure_num'] = M('jk_measuring_tasks')->where('projectid=' . $project['id'])->count();

            //设置Excel
            $objPHPExcel->getActiveSheet(0)->setCellValue('A' . $i, $project['area']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('B' . $i, $project['name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C' . $i, $project['measure_num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D' . $i, $project['total']);
            //1

            $i++;
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  //excel5为xls格式，excel2007为xlsx格式
        $objWriter->save('php://output');
        die;
    }

    /**
     * 函数用途描述：获取楼栋下属房间，并生成房间编码
     * @date: 2017年08月01日 下午 14:03:05
     * @author: tanjiewen
     * @param: id：楼栋id
     * @return:
     */
    public function floor_mask_code($id = 0)
    {
        //
        set_time_limit(120);
        $masterDataJson = array();
        $masterDataJson['masterCategory'] = '001004';
        $masterDataJson['data'] = array();
        $BuildNumber = M('jk_floor')->where('id=' . $id)->getField('masterCode');
        if (!$BuildNumber) {
            $this->error('请先检查是否绑定楼栋编码');
        }
        //查询出对应的房间信息
        $rooms = M('jk_room_mdm')->where("RoomNumber IS NULL and build_id=" . $id)->select();
        if (count($rooms) == 0) {
            $this->success('已生成房间编码，请勿重复生成');
        }
        //申请人信息
        //$applicant = UID.'-'.M('member')->where("uid=".UID)->getField('username');
        $i = 0;
        //构造数组
        foreach ($rooms as $room) {
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT'] = $room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON'] = '生成编码申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOM'] = $room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER'] = $BuildNumber;
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR'] = $room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR'] = $room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT'] = $room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME'] = $room['CreateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED'] = $room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE'] = $room['IsUsedCode'];

            $i++;
        }
        //数组转json

        $masterDataJson = json_encode($masterDataJson);
        //dump($masterDataJson);
        //实例化mdm类
        $soap = new JKMdmController();
        $last_time = time();
        $result = $soap->mdmMasterDataGenCode($masterDataJson);
        //json转数组

        $new_time = time();
        //echo $new_time-$last_time.'<br />';


        $result = json_decode($result->return);
        if ($result->state != 1) {
            $this->error('编码未全部生成成功,请检查后重试');
        }

        $j = 0;
        //遍历返回数组
        foreach ($result->data as $v) {
            //该房间编码生成成功
            if ($v->state == 1) {
                $save['masterCode'] = $v->data->ROOMNUMBER;
                M('jk_floor')->where("id='" . $rooms[$j]['id'] . "'")->save($save);
            }
            $j++;
        }

        $this->success('房间编码全部生成成功');
    }

    /**
     * 函数用途描述：获取楼栋新增的下属房间，并生成房间编码->注册房间编码
     * @date: 2017年08月01日 下午 14:03:05
     * @author: tanjiewen
     * @param: id：楼栋id
     * @return:
     */
    public function generate_register_code($id = 0)
    {
        set_time_limit(120);//数据较大费时较久
        //实例化mdm类
        $soap = new JKMdmController();
        //返回值数组
        $back['status'] = 0;
        $back['reason'] = '';
        $masterDataJson = array();
        $masterDataJson['masterCategory'] = '001004';
        $masterDataJson['data'] = array();
        $BuildNumber = M('jk_floor')->where('id=' . $id)->getField('masterCode');
        if (!$BuildNumber) {
            $back['reason'] = '楼栋编码有误,请联系管理员';
            return $back;
        }
        //查询出对应的房间信息
        $rooms = M('jk_room_mdm')->where("RoomNumber IS NULL and build_id=" . $id)->select();
        if (count($rooms) == 0) {

            $back['reason'] = '已新增该房间';
            return $back;
        }

        $i = 0;
        //构造数组
        foreach ($rooms as $room) {
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT'] = $room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON'] = '生成编码申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOM'] = $room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER'] = $BuildNumber;
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR'] = $room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR'] = $room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT'] = $room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME'] = $room['CreateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED'] = $room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE'] = $room['IsUsedCode'];

            $i++;
        }
        //数组转json
        $masterDataJson = json_encode($masterDataJson);

        //dump($masterDataJson);
        $last_time = time();
        $result = $soap->mdmMasterDataGenCode($masterDataJson);
        //json转数组
        //$new_time=time();
        //echo $new_time-$last_time.'<br />';
        //echo $BuildNumber."<br />";
        //dump($result);die;
        $result = json_decode($result->return);
        if ($result->state != 1) {
            $back['reason'] = '编码未全部生成成功';
            return $back;
        }
        $ids = array();//记录生成的编码
        $j = 0;
        //遍历返回数组
        //file_put_contents('ceshi.txt', json_encode($rooms));
        //file_put_contents('ceshi1.txt',json_encode($result->data));
        foreach ($result->data as $v) {
            //该房间编码生成成功
            if ($v->state == 1) {
                $ids[] = $rooms[$j]['id'];
                $save['RoomNumber'] = $v->data->ROOMNUMBER;
                M('jk_room_mdm')->where("id='" . $rooms[$j]['id'] . "'")->save($save);
            }
            $j++;
        }
        //注册房间信息
        //查询该楼栋下的已生成编码的房间
        $masterDataJson = array();
        $masterDataJson['masterCategory'] = '001004';
        $masterDataJson['data'] = array();
        //查询出对应的房间信息
        $where['build_id'] = $id;
        $where['id'] = array('in', $ids);
        $rooms = M('jk_room_mdm')->where($where)->select();
        //申请人信息
        //$applicant = UID.'-'.M('member')->where("uid=".UID)->getField('username');
        $i = 0;
        //构造数组
        foreach ($rooms as $room) {
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT'] = $room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON'] = '注册房间信息申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNUMBER'] = $room['RoomNumber'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOM'] = $room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNO'] = $room['RoomNO'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER'] = $BuildNumber;
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR'] = $room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR'] = $room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT'] = $room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['UNITNO'] = $room['UnitNO'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME'] = $room['CreateDataTime'];
            //$masterDataJson['data'][$i]['bussinessdata']['S_UPDATETIME']=$room['UpdateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED'] = $room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE'] = $room['IsUsedCode'];

            //     		if($i==0)
            //     			break;
            $i++;
        }
        //数组转json
        $masterDataJson = json_encode($masterDataJson);
        $last_time = time();
        $result = $soap->mdmMasterDataregistration($masterDataJson);
        //json转数组
        $result = json_decode($result->return);
        //全部注册成功
        $save = array();
        if ($result->state == 1) {
            $save['examine'] = 2;
            $save['update_time'] = time();
            $ret = M('jk_floor')->where("id=" . $id)->save($save);
            if ($ret) {
                $back['status'] = 1;
            } else {
                $back['reason'] = '房间编码注册成功,楼栋状态更改失败';
            }
        } else {
            $back['reason'] = '未全部注册成功，请查看是否已生成房间编码';

        }
        dump($back);
        return $back;

    }

    /**
     * 函数用途描述：获取楼栋所有下属房间，并生成房间编码->注册房间编码
     * @date: 2017年08月01日 下午 14:03:05
     * @author: tanjiewen
     * @param: id：楼栋id
     * @return:
     */
    public function main_generate_register_code($id = 0)
    {
        set_time_limit(120);//数据较大费时较久
        //实例化mdm类
        $soap = new JKMdmController();
        $id = array_unique((array)I('id', 0));
        //判断是否都有楼栋编码且状态为1
        $id = is_array($id) ? implode(',', $id) : $id;
        if (empty($id)) {
            $this->error(L('_PLEASE_CHOOSE_TO_OPERATE_THE_DATA_'));
        }
        $map['id'] = array('in', $id);
        $id_arr = M('jk_floor')->where($map)->getField('id', true);

        foreach ($id_arr as $v) {
            $BuildNumber = M('jk_floor')->where('id=' . $v)->getField('masterCode');
            if (!$BuildNumber) {
                $this->error('楼栋编码有误,请联系管理员');
            }
            $status = M('jk_floor')->where('id=' . $v)->getField('examine');
            if ($status != 1) {
                $this->error('请勾选审核中的楼栋');
            }
        }
        //延迟执行生成及注册房间编码
        foreach ($id_arr as $vv) {

            $this->do_room_send($vv);
            $this->do_room_update($vv);
        }
        action_log('update_build', 'Floor', $id, UID);
        $this->success('时间较长，后台执行，请稍后查看更新结果');
        die;
        //返回值数组
        $back['status'] = 0;
        $back['reason'] = '';
        $masterDataJson = array();
        $masterDataJson['masterCategory'] = '001004';
        $masterDataJson['data'] = array();
        $BuildNumber = M('jk_floor')->where('id=' . $id)->getField('masterCode');
        if (!$BuildNumber) {
            $this->error('楼栋编码有误,请联系管理员');
        }


        //查询出对应的房间信息
        $rooms = M('jk_room_mdm')->where("(RoomNumber IS NULL || RoomNumber ='') and IsUsedCode=0 and build_id=" . $id)->select();
        if (count($rooms) == 0) {
            $back['reason'] = '编码已生成';
        } else {
            $i = 0;
            //构造数组
            foreach ($rooms as $room) {
                $masterDataJson['data'][$i]['applyinfo']['APPLICANT'] = $room['id'];
                $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON'] = '生成编码申请';
                $masterDataJson['data'][$i]['bussinessdata']['ROOM'] = $room['Room'];
                $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER'] = $BuildNumber;
                $masterDataJson['data'][$i]['bussinessdata']['FLOOR'] = $room['Floor'];
                $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR'] = $room['AbsolutelyFloor'];
                $masterDataJson['data'][$i]['bussinessdata']['UNIT'] = $room['Unit'];
                $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME'] = $room['CreateDataTime'];
                $masterDataJson['data'][$i]['bussinessdata']['ISUSED'] = $room['IsUsed'];
                $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE'] = $room['IsUsedCode'];
                $i++;
            }
            //数组转json
            $masterDataJson = json_encode($masterDataJson);
            // file_put_contents('soaptest.log', '222');

            $result = $soap->mdmMasterDataGenCode($masterDataJson);
            //$this->error('测试');
            //json转数组
            $result = json_decode($result->return);
            if ($result->state != 1) {
                $this->error('编码未全部生成成功');
            }
        }

        $ids = array();//记录生成的编码
        $j = 0;
        //遍历返回数组
        foreach ($result->data as $v) {
            //该房间编码生成成功
            if ($v->state == 1) {
                $ids[] = $rooms[$j]['id'];
                $save['masterCode'] = $v->data->ROOMNUMBER;
                M('jk_floor')->where("id='" . $rooms[$j]['id'] . "'")->save($save);
            }
            $j++;
        }

        //注册房间信息
        //查询该楼栋下的已生成编码的房间
        $masterDataJson = array();
        $masterDataJson['masterCategory'] = '001004';
        $masterDataJson['data'] = array();
        //查询出对应的房间信息
        $where['build_id'] = $id;
        //$where['id']       = array('in', $ids);
        $rooms = M('jk_room_mdm')->where($where)->select();
        //申请人信息
        //$applicant = UID.'-'.M('member')->where("uid=".UID)->getField('username');
        $i = 0;
        //构造数组
        foreach ($rooms as $room) {
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT'] = $room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON'] = '注册房间信息申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNUMBER'] = $room['RoomNumber'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOM'] = $room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNO'] = $room['RoomNO'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER'] = $BuildNumber;
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR'] = $room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR'] = $room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT'] = $room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['UNITNO'] = $room['UnitNO'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME'] = $room['CreateDataTime'];
            //$masterDataJson['data'][$i]['bussinessdata']['S_UPDATETIME']=$room['UpdateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED'] = $room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE'] = $room['IsUsedCode'];

            //     		if($i==0)
            //     			break;
            $i++;
        }
        //数组转json
        $masterDataJson = json_encode($masterDataJson);
        $last_time = time();
        $result = $soap->mdmMasterDataregistration($masterDataJson);
        //json转数组
        $result = json_decode($result->return);
        //全部注册成功
        $save = array();
        if ($result->state == 1) {
            $save['examine'] = 2;
            $save['update_time'] = time();
            $ret = M('jk_floor')->where("id=" . $id)->save($save);
            if ($ret) {
                $back['status'] = 1;
                $this->success('审核成功,房间编码注册成功');
            } else {
                $this->error('房间编码注册成功,楼栋状态更改失败');
            }
        } else {
            $this->error('未全部注册成功，请查看是否已生成房间编码');
        }
        //return $back;

    }

    /**
     * 函数用途描述：根据楼栋id绑定楼栋编码
     * @date: 2017年08月02日 下午 14:03:05
     * @author: tanjiewen
     * @param: id：楼栋id
     * @return:
     */
    public function bind_mask_code($id = 0)
    {
        //     	$rooms = M('jk_room_mdm')->field('id')->where("build_id=".$id)->select();
        //     	foreach ($rooms as $room){
        //     		$ids[]=$room['id'];
        //     	}
        $save['masterCode'] = 'Z1020170050101040001';
        $ret = M('jk_floor')->where("id=" . $id)->save($save);
        if ($ret) {
            $this->success($title . '绑定' . L('_SUCCESS_'), U('JKProgram/shopCategory'));
        } else {
            $this->error($title . '绑定' . L('_FAIL_'));
        }
    }


    public function testMdm()
    {
        //183310
        //    $new_ids=array()
        //$ids = M('jk_room_mdm')->where('build_id=183310')->getField('id',true);
        // dump($this->update_examine_floor('183310',$ids));die;
        $this->examine_floor1('183310');
        die;
        $masterDataJson['masterCategory'] = '001001';
        $masterDataJson['data'] = array();

        //查询出对应的房间信息
        $rooms = M('jk_room_mdm')->where("build_id=" . $id)->select();
        //申请人信息
        $applicant = UID . '-' . M('member')->where("uid=" . UID)->getField('username');
        $i = 0;

        $masterDataJson['data'][$i]['applyinfo']['APPLICANT'] = $applicant;
        $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON'] = '生成编码申请';
        $masterDataJson['data'][$i]['bussinessdata']['PROJECTNO'] = "Z";
        $masterDataJson['data'][$i]['bussinessdata']['PLATECODE'] = '10';
        $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME'] = '2017-07-25';


        //数组转json
        $masterDataJson = json_encode($masterDataJson);
        $soap = new JKMdmController();
        $result = $soap->mdmMasterDataGenCode($masterDataJson);
        print_r($result);
        die;
        $result = $soap->testMdm();
        dump($result);

//         $test=D('JKProgram/JKMdm');
//         $test->test();
//         exit;
    }

    /**
     * 函数用途描述：提交审核楼栋下的房间
     * @date: 2017年08月04日 上午 11:23:05
     * @author: tanjiewen
     * @param: id：楼栋id ， status ；楼栋审核状态
     * @return:
     */
    public function examine_floor($id = 0, $status = 1)
    {
        //先检查是否已经生成过房间编码
//   		$number = M('jk_room_mdm')->where('build_id='.$id)->order('id DESC')->getField('RoomNumber');

//     	if($number == null || $number==''){
//     		//$this->error(M()->getLastSql());
//     		$this->error("请先生成房间编码");
//     	}
        $id = array_unique((array)I('id', 0));
        $id = is_array($id) ? implode(',', $id) : $id;
        if (empty($id)) {
            $this->error(L('_PLEASE_CHOOSE_TO_OPERATE_THE_DATA_'));
        }

        $save['examine'] = $status;
        $map['id'] = array('in', $id);
        $ret = M('jk_floor')->where($map)->save($save);
        if ($_SESSION['proId1'] != "")
            $proId = $_SESSION['proId1'];
        else
            $proId = $_SESSION['proId'];
        if ($ret) {
            action_log('update_build', 'Floor', $id, UID);
            $this->success($title . '提交' . L('_SUCCESS_'), U('JKProgram/shopCategory', array('id' => $proId)));
        } else {

            $this->error('勾选项已进入待审核状态');
        }
    }

    /**
     * 函数用途描述：审核通过楼栋下的房间
     * @date: 2017年08月04日 上午 11:23:05
     * @author: tanjiewen
     * @param: id：楼栋id ， status ；楼栋审核状态
     * @return:
     */
    public function examine_floor1($id = 0, $status = 2)
    {
        //查询出对应的房间信息
        $rooms = M('jk_room_mdm')->where("RoomNumber IS NULL and build_id=" . $id)->select();
        if (count($rooms) > 0) {
            $this->error('请先生成房间编码');
        }
        //查询该楼栋下的所有房间
        $masterDataJson['masterCategory'] = '001004';
        $masterDataJson['data'] = array();
        $BuildNumber = M('jk_floor')->where('id=' . $id)->getField('masterCode');

        //查询出对应的房间信息
        $rooms = M('jk_room_mdm')->where("build_id=" . $id)->select();
        //申请人信息
        //$applicant = UID.'-'.M('member')->where("uid=".UID)->getField('username');
        $i = 0;
        //构造数组
        foreach ($rooms as $room) {
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT'] = $room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON'] = '注册房间信息申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNUMBER'] = $room['RoomNumber'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOM'] = $room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNO'] = $room['RoomNO'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER'] = $BuildNumber;
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR'] = $room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR'] = $room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT'] = $room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['UNITNO'] = $room['UnitNO'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME'] = $room['CreateDataTime'];
            //$masterDataJson['data'][$i]['bussinessdata']['S_UPDATETIME']=$room['UpdateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED'] = $room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE'] = $room['IsUsedCode'];

//     		if($i==1)
//     			break;
            $i++;
        }
        //数组转json
        $masterDataJson = json_encode($masterDataJson);
        //dump($masterDataJson);die;
        $soap = new JKMdmController();
        $last_time = time();
        $result = $soap->mdmMasterDataregistration($masterDataJson);
        //json转数组
        $new_time = time();
        //$this->error($new_time-$last_time);
        //echo $new_time-$last_time;die;
        $result = json_decode($result->return);

        //全部注册成功
        if ($_SESSION['proId1'] != "")
            $proId = $_SESSION['proId1'];
        else
            $proId = $_SESSION['proId'];
        if ($result->state == 1) {
            $save['examine'] = $status;
            $save['update_time'] = time();
            $ret = M('jk_floor')->where("id=" . $id)->save($save);
            if ($ret) {
                $this->success($title . '房间数据注册成功', U('JKProgram/shopCategory', array('id' => $proId)));
            } else {
                $this->error('房间编码未全部注册成功，请重试');
            }

        } else {
            $this->error('未全部注册成功，请查看是否已生成房间编码');
        }


    }

    /**
     * 函数用途描述：新增的注册房间（因要配置权限，所以分为两个方法）
     * @date: 2017年08月04日 上午 11:23:05
     * @author: tanjiewen
     * @param: id：楼栋id ， $ids ；新增房间ID
     * @return:
     */
    public function add_examine_floor($id = 0, $ids = array())
    {
        //查询该楼栋下的所有房间
        $masterDataJson['masterCategory'] = '001004';
        $masterDataJson['data'] = array();
        $BuildNumber = M('jk_floor')->where('id=' . $id)->getField('masterCode');

        //查询出对应的房间信息
        $where['id'] = array('in', $ids);
        $rooms = M('jk_room_mdm')->where($where)->select();
        //申请人信息
        $i = 0;
        //构造数组
        foreach ($rooms as $room) {
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT'] = $room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON'] = '生成编码申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOM'] = $room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER'] = $BuildNumber;
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR'] = $room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR'] = $room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT'] = $room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME'] = $room['CreateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED'] = $room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE'] = $room['IsUsedCode'];

            $i++;
        }
        //数组转json
        $masterDataJson = json_encode($masterDataJson);
        $soap = new JKMdmController();
        //先生成编码
        $result = $soap->mdmMasterDataGenCode($masterDataJson);
        $result = json_decode($result->return);
        if ($result->state != 1) {
            return false;
        }
        $j = 0;
        //遍历返回数组
        foreach ($result->data as $v) {
            //该房间编码生成成功
            if ($v->state == 1) {
                $save['masterCode'] = $v->data->ROOMNUMBER;
                M('jk_floor')->where("id='" . $rooms[$j]['id'] . "'")->save($save);
            }
            $j++;
        }
        //组装数据
        //查询出对应的房间信息
        $where['id'] = array('in', $ids);
        $rooms = M('jk_room_mdm')->where($where)->select();
        //申请人信息
        //$applicant = UID.'-'.M('member')->where("uid=".UID)->getField('username');
        $i = 0;
        //构造数组
        foreach ($rooms as $room) {
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT'] = $room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON'] = '注册房间编码申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNUMBER'] = $room['RoomNumber'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOM'] = $room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER'] = $BuildNumber;
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR'] = $room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR'] = $room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT'] = $room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME'] = $room['CreateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED'] = $room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE'] = $room['IsUsedCode'];
            $i++;
        }
        //注册编码
        $result = $soap->mdmMasterDataregistration($masterDataJson);
        $result = json_decode($result->return);
        //全部注册成功

        $this->error($result->state);
        if ($result->state == 1) {
            return true;
        } else {
            return false;
        }


    }

    /**
     * 函数用途描述：房间信息变更
     * @date: 2017年08月11日 上午 11:23:05
     * @author: tanjiewen
     * @param: id：楼栋id ， $ids ；变更房间ID
     * @return:
     */
    public function update_examine_floor($id = 158620, $ids = array())
    {
        //查询该楼栋下的所有房间

        $masterDataJson['masterCategory'] = '001004';
        $masterDataJson['data'] = array();
        $BuildNumber = M('jk_floor')->where('id=' . $id)->getField('masterCode');
        //查询出对应的房间信息
        $where['id'] = array('in', $ids);
        $where['RoomNumber'] = array('gt', '');
        $rooms = M('jk_room_mdm')->where($where)->select();
        if (count($rooms) == 0 || !$rooms) {
            return true;
        }
        //return M()->getLastSql();
        //申请人信息
        //$applicant = UID.'-'.M('member')->where("uid=".UID)->getField('username');
        $i = 0;
        //构造数组
        foreach ($rooms as $room) {
            //mastecode
            $masterDataJson['data'][$i]['masterCode'] = $room['RoomNumber'];
            $masterDataJson['data'][$i]['applyinfo']['APPLICANT'] = $room['id'];
            $masterDataJson['data'][$i]['applyinfo']['APPLY_REASON'] = '信息变更申请';
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNUMBER'] = $room['RoomNumber'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOM'] = $room['Room'];
            $masterDataJson['data'][$i]['bussinessdata']['ROOMNO'] = $room['RoomNO'];
            $masterDataJson['data'][$i]['bussinessdata']['BUILDNUMBER'] = $BuildNumber;
            $masterDataJson['data'][$i]['bussinessdata']['FLOOR'] = $room['Floor'];
            $masterDataJson['data'][$i]['bussinessdata']['ABSOLUTELYFLOOR'] = $room['AbsolutelyFloor'];
            $masterDataJson['data'][$i]['bussinessdata']['UNIT'] = $room['Unit'];
            $masterDataJson['data'][$i]['bussinessdata']['UNITNO'] = $room['UnitNO'];
            $masterDataJson['data'][$i]['bussinessdata']['CREATEDATATIME'] = $room['CreateDataTime'];
            //$masterDataJson['data'][$i]['bussinessdata']['S_UPDATETIME']=$room['UpdateDataTime'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSED'] = $room['IsUsed'];
            $masterDataJson['data'][$i]['bussinessdata']['ISUSEDCODE'] = $room['IsUsedCode'];
            $i++;
        }
        //数组转json

        $masterDataJson = json_encode($masterDataJson);

        $soap = new JKMdmController();

        $result = $soap->mdmMasterDataChang($masterDataJson);

        $result = json_decode($result->return);
        file_put_contents('ceshi5.txt', json_encode($result));
        //全部变更成功
        if ($result->state == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 函数用途描述；异步处理MDM与金品质历史数据
     * @date: 2017年9月19日 下午2:07:10
     * @author: luojun
     * @param: $proId=>项目id；
     * @return:
     */
    public function sysMDMData($proId)
    {
        $find = M('jk_project')->where("id=$proId")->find();
        if (($find['done'] == 0 || $find['done'] == 3) && $find['ProjectNumber']) {
            $code = $find['ProjectNumber'];
            $url = "http://" .$this->ser_url . "/index.php?s=/admin/JKMdm/addMdmData/code/$code/pid/$proId";
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

            curl_setopt($curl, CURLOPT_TIMEOUT, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);

            $result = curl_exec($curl);

            curl_close($curl);
            //echo $url;
            file_put_contents('mdmpro.txt', $result, FILE_APPEND);
        };
    }

    /**
     * 函数用途描述：供应商列表
     * @date: 2017年11月9日 下午2:29:55
     * @author: luojun
     * @param: variable
     * @return:
     */
    public function gysList($name = '',$page=1,$r=20)
    {

        //接收时间条件（转换为毫秒）
        $aSearch1 = I('get.usearch1', '') ;
        //组织节点筛选
        $aid = I('get.ownid','');

        if($aid){
            $proids=get_select_projects($aid);

            $map['id'] = array('in', $proids);

        }
        else{
            $proids =$_SESSION['proId']=15;
            $map['id'] =$proids;
        }

        //供应商编码数组
        $pids=M('jk_project')->where($map)->field('pid,name')->select();

        $list=array();
        $i=0;
        foreach ($pids as $pid){
            $map=array();
            $map['pid']=$pid['pid'];
            $cates=M('auth_group')->where("status=-2 AND (cate=2 OR cate=3)")->field('id')->select();
            $cates=array_column($cates, 'id');
            $map['cate']=array('in', $cates);
            $map['gysCode']=array('gt', '');
            $gysids=M('auth_group')->where($map)->field('gysCode')->select();

            $map=array();
            $map['Providernumber']=array('in', array_column($gysids, 'gysCode'));
            //设置搜索条件
            if (!empty($aSearch1)) {
                $map['ProviderName']=array('like',"%$aSearch1%");
            }

            $info = M('jk_provider_mdm')->field('id,ProviderName,ProviderType,Corporation,RegistrationAuthority')
                ->where($map)->page($page, $r)->select();

            //dump(M('jk_provider_mdm')->_sql());
            foreach ($info as $k=>$v) {
                $info[$k]['projectname']=$pid['name'];
                $list[$i]=$info[$k];
                $i++;
            }
        }

        $builder = new AdminListBuilder();
        $builder->title('供应商列表');
        $builder->meta_title = '供应商列表';

        //搜索框
        $builder->setSearchPostUrl(U('JKProgram/gysList'))
            ->search('供应商名称', 'usearch1', 'txt', '', '', '', '');

        $builder->buttonModalPopup(U('JKProgram/selectproject'),
            '',
            '根据节点筛选',
            array('data-title' => ('选择节点')));
        $builder->keyText('projectname', '所属项目')
            ->keyText('ProviderName', '供应商名称')
            ->keyText('Corporation', '法人')
            ->keyText('ProviderType', '企业类型')
            ->keyText('RegistrationAuthority', '登记机关')
            ->keyDoAction('gysinfo?id=###','详情');

        $builder->data($list);
        $builder->pagination($i, $r);
        $builder->display();
    }

    /**
     * 函数用途描述：供应商详情
     * @date: 2017年11月9日 下午2:29:55
     * @author: luojun
     * @param: variable
     * @return:
     */
    public function gysinfo($id=0)
    {
        $info = M('jk_provider_mdm')->where("id=$id")->find();

        $this->assign('info',$info);
        $this->display('/JKProgram@JKProgram/gysinfo');
    }


}

?>
