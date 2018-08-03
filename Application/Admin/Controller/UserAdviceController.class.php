<?php

namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminSortBuilder;
use Common\Model\MemberModel;
use User\Api\UserApi;
use Admin\Builder\AdminTreeListBuilder;

//用户反馈控制器
class UserAdviceController extends AdminController {
    
    //用户反馈列表
    public function advice_list($page=1,$r=10) {
        //查询反馈表
        $advices = M('JkAdvice')->order('time desc')->page($page,$r)->select();
        $totalcount = M('JkAdvice')->count();
        
        //转换时间
        foreach($advices as &$v){
            $v['time'] = date("Y-m-d H:i",$v['time']);
        }

        //建立模板
        $builder = new AdminListBuilder();
        $builder->title('反馈列表');
        $builder->meta_title = '反馈列表';
         
        $builder->keyText('project',L('项目名称'))
        ->keyText('content',L('用户反馈内容'))
        ->keyText('username',L('实测提交人'))
        ->keyText('time',L('提交时间'))
        ->keyDoAction('advice_info?id=###','查看')
        ->keyDoAction('advice_del?id=###','删除','',array('class'=>'confirm ajax-get'));
        
        //循环数据分页输出
        $builder->data($advices);
        $builder->pagination($totalcount,$r);
        $builder->display();
    }
    
    //用户反馈详情页
    public function advice_info($id) {
        $map['id'] = $id;
        $info = M('JkAdvice')->where($map)->find();
        $info['time'] = date("Y-m-d H:i",$info['time']);
        $this->assign('info',$info);
        $this->display();
    }
    
    //反馈删除
    public function advice_del($id) {
        $map = ['id'=>$id];
        $res = M('JkAdvice')->where($map)->delete();
        if($res)
            $this->success(L('删除成功'));
        else
            $this->error(L('删除失败'));
    }
}//class end
