<?php
/**
 * 所属项目 jkapp.
 * 开发者: luoj
 * 创建日期: 2018年6月12日
 * 创建时间: 下午4:04:09
 * 版权所有
 */

namespace Admin\Controller;
use Think\Model;
use Think\Controller;
use Admin\Builder\AdminListBuilder;

/***********************************************************************
 Class:        Mht File Maker
 Version:      1.2 beta
 Date:         02/11/2007
 Author:       Wudi <wudicgi@yahoo.de>
 Description:  The class can make .mht file.
 ***********************************************************************/

class JpushController extends AdminController
{
    public $soap;
    public $ws;

    protected function _initialize()
    {

    }

    /**
     * 推送配置
     */
    public function config(){

	}

    /**
     * 推送消息列表
     */
	public function logList($page=1,$r=20){
        $builder = new AdminListBuilder();
        $builder->title('消息推送列表');
        $builder->meta_title = '消息推送列表';

        $where = "";
        $list = M('jk_pushlog')->where($where)->order('send_time DESC')->page($page, $r)->select();
        $totalCount = M('jk_pushlog')->where($where)->count();
        $a=array(
            array('id'=>0,'value'=>'全部'),
            array('id'=>1,'value'=>'发送成功'),
            array('id'=>2,'value'=>'发送失败'),
        );
        $builder->select(L('状态：'), 'send_status', 'select', L('选择状态'), '', '', $a);

        // $builder->keyId('other_id','项目编号')
        $builder->keyText('problem_id', '问题id')
            ->keyText('send_time', '推送时间')
            ->keyText('content', '推送内容')
            ->keyText('send_rep', '推送结果');

        $builder->data($list);
        $builder->pagination($totalCount, $r);
        $builder->display();
	}
}
