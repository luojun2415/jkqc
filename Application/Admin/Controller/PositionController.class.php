<?php
namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminSortBuilder;
use Common\Model\MemberModel;
use User\Api\UserApi;
use Admin\Builder\AdminTreeListBuilder;


//库位管理控制器
class PositionController extends AdminController {
    
    //展示所有仓库
    public function index($page = 1, $r = 20){
        
        //查出记所有的数据和记录数
        $depot_info = M('jk_program')->where("is_del=0 AND pid=0")->select();
        $totalCount = M('jk_program')->where("is_del=0 AND pid=0")->count();
        
        $builder = new AdminListBuilder();
        $builder->title('仓库详情页');
        $builder->meta_title = '仓库详情列表';
        $title=I('get.title','');
        
        //查询条件     根据状态查询
        $status = array(
                array('id'=>0,'value'=>L('空置')),
                array('id'=>1,'value'=>L('非空')),
        );
        $builder->select(L('状态：'), 'status', 'select', L('选择状态'), '', '', $status);
        
        
        //列表
        $builder->keyId('id','仓库编号')
        ->keyText('deport_name', '仓库名称')
        ->keyText('pid', '所属区域')
        ->keyText('is_del', '是否被删')
        ->keyText('status', '状态')
        ->keyUpdateTime('ctime', '创建时间')
        ->keyUpdateTime('utime', '修改时间')
        ->keyDoActionEdit('Position/edit?init_id=###', '详情');
        
        $builder->data($depot_info);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
}