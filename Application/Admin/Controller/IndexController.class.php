<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;

use User\Api\UserApi as UserApi;

/**
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class IndexController extends AdminController
{

    /**
     * 后台首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index_bak()
    {

        if (UID) {

            if(IS_POST){
                $count_day=I('post.count_day', C('COUNT_DAY'),'intval',7);
                if(M('Config')->where(array('name'=>'COUNT_DAY'))->setField('value',$count_day)===false){
                    $this->error(L('_ERROR_SETTING_').L('_PERIOD_'));
                }else{
                   S('DB_CONFIG_DATA',null);
                    $this->success(L('_SUCCESS_SETTING_').L('_PERIOD_'),'refresh');
                }

            }else{
                $this->meta_title = L('_INDEX_MANAGE_');
                $today = date('Y-m-d', time());
                $today = strtotime($today);
                $count_day = C('COUNT_DAY',null,7);
                $count['count_day']=$count_day;
                for ($i = $count_day; $i--; $i >= 0) {
                    $day = $today - $i * 86400;
                    $day_after = $today - ($i - 1) * 86400;
                    $week_map=array('Mon'=>L('_MON_'),'Tue'=>L('_TUES_'),'Wed'=>L('_WEDNES_'),'Thu'=>L('_THURS_'),'Fri'=>L('_FRI_'),'Sat'=>'<strong>'.L('_SATUR_').'</strong>','Sun'=>'<strong>'.L('_SUN_').'</strong>');
                    $week[] = date('m月d日 ', $day). $week_map[date('D',$day)];
                    $user = UCenterMember()->where('status=1 and reg_time >=' . $day . ' and reg_time < ' . $day_after)->count() * 1;
                    $registeredMemeberCount[] = $user;
                    if ($i == 0) {
                        $count['today_user'] = $user;
                    }
                }
                $week = json_encode($week);
                $this->assign('week', $week);
                $count['total_user'] = $userCount = UCenterMember()->where(array('status' => 1))->count();
                $count['today_action_log'] = M('ActionLog')->where('status=1 and create_time>=' . $today)->count();
                $count['last_day']['days'] = $week;
                $count['last_day']['data'] = json_encode($registeredMemeberCount);
                // dump($count);exit;

                $this->assign('count', $count);
                $this->display();
            }


        } else {
            $this->redirect('Public/login');
        }
    }

    
    /**
    *函数用途描述：后台项目首页
    *@date：2016年10月19日 下午3:28:42
    *@author：luoj
    *@param：
    *@return：
    **/
    public function index()
    {
    
        if (UID) {
            $this->meta_title = L('项目列表');     
            $aUid=I('get.uid',0,'intval');	     
            $this->meta_title = L('选择默认项目');
           // dump($this->pro_list);
            //对项目进行排序并构建
            $projects = sort_project($this->pro_list);
            //dump($projects['error_arr']);
            $this->assign('no_pros',$projects['no_arr']);
            $this->assign('error_pros',$projects['error_arr']);
            $this->assign('or_pros',$projects['or_arr']);  
           // echo  count($projects['or_arr']);die;
   
            if(count($projects['or_arr'])>=2 || count($this->pro_list)>10){
            	$this->display('new_index');
            }else{
            	$this->display('base_new_index');
            }
                    
        } else {
            $this->redirect('Public/login');
        }
    }
}
