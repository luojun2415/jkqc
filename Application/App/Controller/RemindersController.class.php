<?php

/**
 * 所属项目 金科质量管理系统.
 * 开发者: 李国军
 * 创建日期: 2016年11月25日
 * 版权所有 重庆艾锐森科技有限责任公司(www.irosn.com)
 * 用于新任务推送以及任务时间提醒
 */
namespace App\Controller;

use Think\Controller;

class RemindersController extends Controller {
	/**
	 * 获取需要推送的信息
	 * 推送条件：
	 * 1、任务还有一天要过期了；
	 * 2、任务已过期；
	 */
	public function getAndSend() {
		//header ( "Content-type:text/html;charset=utf-8" );
		$push = new JpushController();
		$db_promblem = M ( 'jk_program' );
		$db_task = M ( 'jk_measuring_tasks' );
		$now = microtimeStr (); // 当前毫秒级时间戳
		$overtime = 0;
		$registrationids = array();
		// 3600000*6 一天的毫秒数：86400000 //43200000
		$sql = "SELECT init_id,target_id,create_time,limit_time,create_time+limit_time*86400000 AS endtime 
				FROM `irosn_jk_program` WHERE status=0 OR status=2";
		$infos = M ( 'jk_measuring_tasks' )->query ( $sql );
		foreach ( $infos as $info ) {
			// 到期时间减掉现在的时间
			$overtime = $info ['endtime'] - $now;
			//获取改组用户下所有用户对应的设备ID
			$registrationids = getRidsFromGroupId($info['target_id']);
			if ($overtime < 0) {//已过期				
				$push->send_pub($registrationids, "您有任务已过期，请更新确认");
			}
			if ($overtime > 0 && $overtime <= 43200000) {//12个小时之内到期，给出提醒
				$push->send_pub($registrationids, "您有任务将在12小时内过期，请更新确认");
			}
			unset($registrationids);
		}
	}
}