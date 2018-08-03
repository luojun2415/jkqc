<?php 

/**
 * 通过用户组ID获取该组所有用户的设备集合
 * @param 用户组ID $groupid
 * @return 该组所有用户设备ID:
 */
function  getRidsFromGroupId($groupid){
	$regids = array();
	$uinfos = M("auth_group_access")->where("group_id = $groupid")->select();
	foreach ($uinfos as $uinfo){
		$uid = $uinfo['uid'];
		$regid = M('jk_equipment')->where("uid = $uid")->getField("ename");
		if($regid){
			array_push($regids, $regid);
		}		
	}
	return $regids;
}
/**
 * 获取测量总点数
 * @param 不同测量项的对应点数集合 $numarray
 * @return 总点数
 */
function getSumPoint($numarray){
	$dtails = explode ( ',', $numarray);//转换为数组
	$totalnum = 0;
	for ($i=0;$i<count($dtails);$i++){
		$totalnum += $dtails[$i];
	}
	return $totalnum;
}

/**
 * 添加推送日志
 * @param $receives
 * @param string $content
 * @param string $m_type
 * @param string $m_txt
 * @param string $message
 * @param int $code
 * @param $problem_id
 */
function pushLog($receives, $content='', $m_type='', $m_txt='',$message='',$code=0,$problem_id){
	$data['targets'] = json_encode($receives);
	$data['content'] = $content;
    $data['m_type'] = $m_type;
    $data['m_txt'] = $m_txt;
    $data['send_rep'] = $message;
    $data['send_status'] = $code;
    $data['send_time'] = time();
    $data['problem_id'] = $problem_id;
    M('jk_pushlog')->add($data);
}

/**
 * 获取楼栋的房间
 * @param string $build_ids
 * @param int $proid
 * @return string
 */
function getBuildsRooms($build_ids='',$proid=0){
	if($build_ids>''&&$proid>0){
		$db = M('jk_floor');
        $units=$db->where("pid IN ($build_ids) AND status=1 AND projectid=$proid")->select();
        if($units){
            $target_id="";//单元id
            foreach ($units as $v) {
                $target_id .= $v['id'] . ",";
            }
            $target_id = substr($target_id, 0, strlen($target_id) - 1);
            $floors = $db->where("pid IN ($target_id) AND status=1 AND projectid=$proid")->select();
            if($floors){
                $target_id="";//楼层id
                foreach ($floors as $v) {
                    $target_id .= $v['id'] . ",";
                }
                $target_id = substr($target_id, 0, strlen($target_id) - 1);
                $rooms = $db->where("pid IN ($target_id) AND status=1 AND projectid=$proid")->select();
                if($rooms){
                	return $rooms;
				}
				else{
                	return '';
				}
            }
		}

	}
    else{
        return '';
    }
}
?>
 