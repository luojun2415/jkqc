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
use Admin\Builder\AdminTreeListBuilder;


use Think\Model;

/**
 * Class ShopController
 * @package Admin\controller
 * @luoj
 */
class JKSurveyOptionController extends AdminController
{

    protected $shopModel;
    protected $shop_configModel;
    protected $shop_categoryModel;

    function _initialize()
    {
        $this->shopModel = D('JKProject/JKProject');
        $this->shop_configModel = D('JKProject/JKProjectConfig');
        $this->shop_categoryModel = D('JKProject/JKProjectCategory');
        parent::_initialize();
    }

    /**商品分类
     * @author luoj
     */
    public function shopCategory()
    {
        //显示页面
        $builder = new AdminTreeListBuilder();
        $attr['class'] = 'btn ajax-post';
        $attr['target-form'] = 'ids';

        $tree = $this->shop_categoryModel->getTree(0, 'id,title,sort,pid,status');
//         dump($tree);
// 		$data[]=$tree;
        $builder->title('问题选项管理')
            ->buttonNew(U('JKProject/add'))
            ->data($tree)
            ->display();
    }

    /**分类添加
     * @param int $id
     * @param int $pid
     * @author luoj
     */
    public function add($id = 0, $pid = 0)
    {
        if (IS_POST) {
            $title=$id?L('_EDIT_'):L('_ADD_');
            if ($this->shop_categoryModel->editData()) {
                $this->success($title.L('_SUCCESS_').L('_PERIOD_'), U('JKProject/shopCategory'));
            } else {
                $this->error($title.L('_FAIL_').L('_EXCLAMATION_').$this->shop_categoryModel->getError());
            }
        } else {
            $builder = new AdminConfigBuilder();
            $categorys = $this->shop_categoryModel->select();
            $opt = array();
            foreach ($categorys as $category) {
                $opt[$category['id']] = $category['title'];
            }
            if ($id != 0) {
                $category = $this->shop_categoryModel->find($id);
            } else {
                $category = array('pid' => $pid, 'status' => 1);
                $father_category_pid=$this->shop_categoryModel->where(array('id'=>$pid))->getField('pid');
                if($father_category_pid!=0){
                    $this->error(L('_ERROR_CATEGORY_HIERARCHY_').L('_EXCLAMATION_'));
                }
            }
            $builder->title(L('_CATEGORY_ADD_'))->keyId()->keyText('title', L('_TITLE_'))
            ->keySelect('pid', L('_CATEGORY_FATHER_'), L('_CATEGORY_FATHER_SELECT_'), array('0' => L('_CATEGORY_TOP_')) + $opt)
                ->keyStatus()->keyCreateTime()->keyUpdateTime()
                ->data($category)
                ->buttonSubmit(U('JKProject/add'))->buttonBack()->display();
        }

    }

    /**分类回收站
     * @param int $page
     * @param int $r
     * @author luoj
     */
    public function categoryTrash($page = 1, $r = 20,$model='')
    {
        $builder = new AdminListBuilder();
        $builder->clearTrash($model);
        //读取微博列表
        $map = array('status' => -1);
        $list = $this->shop_categoryModel->where($map)->page($page, $r)->select();
        $totalCount = $this->shop_categoryModel->where($map)->count();

        //显示页面

        $builder->title(L('_SHOP_CATEGORY_TRASH_'))
            ->setStatusUrl(U('setStatus'))->buttonRestore()->buttonClear('ShopCategory')
            ->keyId()->keyText('title', L('_TITLE_'))->keyStatus()->keyCreateTime()
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
     * 设置商品分类状态：删除=-1，禁用=0，启用=1
     * @param $ids
     * @param $status
     * @author luoj
     */
    public function setStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        //$builder->doSetStatus('shopCategory', $ids, $status);
        $builder->doSetStatus('jk_option', $ids, $status);
    }

    /**
     * 设置商品状态：删除=-1，禁用=0，启用=1
     * @param $ids
     * @param $status
     * @author luoj
     */
    public function setGoodsStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('jk_shoplist', $ids, $status);
    }

    /**商品列表
     * @param int $page
     * @param int $r
     * @author luoj
     */
    public function goodsList($page = 1, $r = 20)
    {
        $map['status'] = array('egt', 0);
        $goodsList = $this->shopModel->where($map)->order('createtime desc')->page($page, $r)->select();
        $totalCount = $this->shopModel->where($map)->count();
        $builder = new AdminListBuilder();
        $builder->title(L('_GOODS_LIST_'));
        $builder->meta_title = L('_GOODS_LIST_');
        foreach ($goodsList as &$val) {
            $category = $this->shop_categoryModel->where('id=' . $val['cate_id'])->getField('title');
            
            $val['category'] = $category;
            unset($category);
            $val['selltype'] = ($val['selltype'] == 1) ? '现金支付' : '积分支付';
            $val['type'] = ($val['type'] == 1) ? '虚拟商品' : '实物商品';
            $val['is_hot'] = ($val['is_hot'] == 1) ? L('_YES_') : L('_NOT_');
            $val['is_recommend'] = ($val['is_recommend'] == 1) ? L('_YES_') : L('_NOT_');
        }
        unset($val);
        $builder->buttonNew(U('JKProject/goodsEdit'))->buttonDelete(U('setGoodsStatus'))->setStatusUrl(U('setGoodsStatus'));
        $builder->keyId()->keyText('name', L('_GOODS_NAME_'))->keyText('category', L('_GOODS_CATEGORY_'))
        ->keyText('adress', '店铺')
        ->keyText('selltype', '支付类型')->keyText('type', '商品类型')
        ->keyText('price', L('原价'))->keyText('new_price', L('_GOODS_PRICE_'))
        ->keyText('leftnumber', L('_GOODS_MARGIN_'))
        ->keyText('sell_num', L('_GOODS_SOLD_'))->keyText('is_hot', '热卖商品')->keyText('is_recommend', '推荐商品')
        ->keyStatus('status', L('_GOODS_STATUS_'))
        ->keyDoActionEdit('JKProject/goodsEdit?id=###');
        $builder->data($goodsList);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }

    /**设置是否为新品
     * @param int $id
     * @author luoj
     */
    public function setNew($id = 0)
    {
        if ($id == 0) {
            $this->error(L('_GOODS_SELECT_'));
        }
        $is_new = intval(!$this->shopModel->where(array('id' => $id))->getField('is_new'));
        $rs = $this->shopModel->where(array('id' => $id))->setField(array('is_new' => $is_new, 'changetime' => time()));
        if ($rs) {
            $this->success(L('_SUCCESS_SETTING_').L('_EXCLAMATION_'));
        } else {
            $this->error(L('_ERROR_SETTING_').L('_EXCLAMATION_'));
        }
    }

    /**商品回收站
     * @param int $page
     * @param int $r
     * @author luoj
     */
    public function goodsTrash($page = 1, $r = 20,$model='')
    {
        $builder = new AdminListBuilder();
        $builder->clearTrash($model);
        //读取微博列表
        $map = array('status' => -1);
        $goodsList = $this->shopModel->where($map)->order('changetime desc')->page($page, $r)->select();
        $totalCount = $this->shopModel->where($map)->count();

        //显示页面

        $builder->title(L('_GOODS_TRASH_'))
            ->setStatusUrl(U('setGoodsStatus'))->buttonRestore()->buttonClear('JKProject/Shop')
            ->keyId()->keyLink('goods_name',L('_TITLE_'), 'JKProject/goodsEdit?id=###')->keyCreateTime()->keyStatus()
            ->data($goodsList)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
     * @param int $id
     * @param $goods_name
     * @param $goods_ico
     * @param $goods_introduct
     * @param $goods_detail
     * @param $money_need
     * @param $goods_num
     * @param $status
     * @param $category_id
     * @param $is_new
     * @param $sell_num
     * @author luoj
     */
    public function goodsEdit($id = 0, $name = '', $face_code = '', $description = '', $banner_code = '',
        $goods_detail_code='', $new_price = '', $leftnumber = '', $status = '', $category_id = 0,
         $is_new = 0, $sell_num = 0,$selltype = 1,$mytype = 1, $price='')
    {
        $isEdit = $id ? 1 : 0;
        if (IS_POST) {
            if ($name == '' || $name == null) {
                $this->error(L('请输入商品名'));
            }
            if (!$face_code) {
                $this->error(L('请上传商品封面图'));
            }
            
            if ($description == '' || $description == null) {
                $this->error(L('请输入商品简介'));
            } else {
                $goods_introduct = $description;
            }
            
            if (!(is_numeric($new_price) && $new_price >= 0)) {
                $this->error(L('请正确输入商品原价'));
            }
            if (!(is_numeric($leftnumber) && $leftnumber >= 0)) {
                $this->error(L('请正确输入商品库存'));
            }
            if (!(is_numeric($sell_num) && $sell_num >= 0)) {
                $this->error(L('请正确输入商品销量'));
            }
            $goods = $this->shopModel->create();
            
            if($goods['face_code']){
                $goods['face_img']='http://'.$_SERVER['HTTP_HOST'].__ROOT__ .get_cover($goods['face_code'],'path');
            }
            if($goods['goods_detail_code']){
                $tempData=array();
                $temp=explode(',',$goods['goods_detail_code']);
                foreach ($temp as $v){
                    $tempData[]='http://'.$_SERVER['HTTP_HOST'].__ROOT__ .get_cover($v,'path');
                }
                $goods['goods_detail']=implode(',', $tempData);
                
            }
            if($goods['banner_code']){
                $tempData=array();
                $temp=explode(',',$goods['banner_code']);
                foreach ($temp as $v){
                    $tempData[]='http://'.$_SERVER['HTTP_HOST'].__ROOT__ .get_cover($v,'path');
                }
                $goods['banner']=implode(',', $tempData);
                
            }
            
            $goods['changetime'] = time();
            $goods['type'] = $mytype;
            if ($isEdit) {
                $rs = $this->shopModel->where('id=' . $id)->save($goods);
            } else {
                //商品名存在验证
                $map['status'] = array('egt', 0);
                $map['name'] = $name;
                if ($this->shopModel->where($map)->count()) {
                    $this->error(L('_ERROR_GOODS_SAME_NAME_'));
                }

                $goods['createtime'] = time();
                $rs = $this->shopModel->add($goods);
            }
            if ($rs) {
                $this->success($isEdit==0 ? L('_SUCCESS_ADD_') : L('_SUCCESS_EDIT_'), U('JKProject/goodsList'));
            } else {
                $this->error($isEdit==0 ? L('_FAIL_ADD_') : L('fail_Edit'));
            }
        } else {
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? L('_GOODS_EDIT_') : L('_GOODS_ADD_'));
            $builder->meta_title = $isEdit ? L('_GOODS_EDIT_') : L('_GOODS_ADD_');

            //获取分类列表
            $category_map['status'] = array('egt', 0);
            $goods_category_list = $this->shop_categoryModel->where($category_map)->order('pid desc')->select();
            $options = array_combine(array_column($goods_category_list, 'id'), array_column($goods_category_list, 'title'));
            
            
            $builder->keyId()->keyText('name', L('_GOODS_NAME_'))->keySingleImage('face_code', L('_GOODS_BRAND_'))
            ->keySelect('cate_id',L('_GOODS_CATEGORY_'), '', $options)
            ->keySelect('mytype','商品类型', '', array('1'=>'虚拟商品','2'=>'实物商品'))
            ->keyText('adress', '店铺')
            ->keySelect('selltype','支付类型', '', array('1'=>'现金支付','2'=>'积分支付'))
            
            ->keyText('description', L('_GOODS_SLOGAN_'))
            ->keyMultiImage('banner_code', '广告位图片','',10)->keyMultiImage('goods_detail_code', L('_GOODS_DETAIL_'),'',20)
            ->keyText('new_price', L('_GOODS_PRICE_'))
            ->keyText('price', L('商品原价'))
            ->keyText('leftnumber', L('_GOODS_MARGIN_'))->keyText('sell_num', L('_GOODS_SOLD_'));
            $builder->KeyBool('is_hot','是否热卖','');
            $builder->KeyBool('is_recommend','是否推荐','');
            if ($isEdit) {
                $goods = $this->shopModel->where('id=' . $id)->find();
                //dump($goods);
                $builder->data($goods);
                $builder->buttonSubmit(U('JKProject/goodsEdit'));
                $builder->buttonBack();
                $builder->display();
            } else {
                $goods['status'] = 1;
                $goods['mytype'] = 1;
                $goods['is_hot'] = 1;
                $goods['is_recommend'] = 1;
                $builder->buttonSubmit(U('JKProject/goodsEdit'));
                $builder->buttonBack();
                $builder->data($goods);
                $builder->display();
            }
        }
    }

    public function shopConfig()
    {
        $builder = new AdminConfigBuilder;
        $data = $builder->handleConfig();

        //初始化数据
        !isset($data['SHOP_SCORE_TYPE'])&&$data['SHOP_SCORE_TYPE']='1';
        !isset($data['SHOP_HOT_SELL_NUM'])&&$data['SHOP_HOT_SELL_NUM']='10';

        //读取数据
        $map = array('status' => array('GT', -1));
        $model = D('Ucenter/Score');
        $score_types = $model->getTypeList($map);
        $score_type_options=array();
        foreach($score_types as $val){
            $score_type_options[$val['id']]=$val['title'];
        }

        $builder->title(L('_SHOP_CONF_'))
            ->keySelect('SHOP_SCORE_TYPE', L('_SHOP_EXCHANGE_POINT_'), '',$score_type_options)
            ->keyInteger('SHOP_HOT_SELL_NUM',L('_SHOP_HOT_SELL_LEVEL_'),L('_SHOP_HOT_SELL_LEVEL_VICE_'))->keyDefault('SHOP_HOT_SELL_NUM',10)

            ->keyText('SHOP_SHOW_TITLE', L('_TITLE_NAME_'), L('_HOME_BLOCK_TITLE_'))->keyDefault('SHOP_SHOW_TITLE','热门商品')
            ->keyText('SHOP_SHOW_COUNT', '显示积分商品的个数', '只有在网站首页模块中启用了积分商城模块之后才会显示')->keyDefault('SHOP_SHOW_COUNT',4)
            ->keyRadio('SHOP_SHOW_TYPE', '推荐的范围', '', array('1' => '新品', '0' => L('_EVERYTHING_')))->keyDefault('SHOP_SHOW_TYPE',0)
            ->keyRadio('SHOP_SHOW_ORDER_FIELD', L('_SORT_VALUE_'), L('_TIP_SORT_VALUE_'), array('sell_num' => '售出数量', 'createtime' => L('_DELIVER_TIME_'), 'changetime' => L('_UPDATE_TIME_'),))->keyDefault('SHOP_SHOW_ORDER_FIELD','sell_num')
            ->keyRadio('SHOP_SHOW_ORDER_TYPE', L('_SORT_TYPE_'), L('_TIP_SORT_TYPE_'), array('desc' => L('_COUNTER_'), 'asc' => L('_DIRECT_')))->keyDefault('SHOP_SHOW_ORDER_TYPE','desc')
            ->keyText('SHOP_SHOW_CACHE_TIME', L('_CACHE_TIME_'),L('_TIP_CACHE_TIME_'))->keyDefault('SHOP_SHOW_CACHE_TIME','600')

            ->group(L('_BASIC_CONF_'),'SHOP_SCORE_TYPE,SHOP_HOT_SELL_NUM')
            ->group(L('_HOME_SHOW_CONF_'), 'SHOP_SHOW_TITLE,SHOP_SHOW_TYPE,SHOP_SHOW_COUNT,SHOP_SHOW_TITLE,SHOP_SHOW_ORDER_TYPE,SHOP_SHOW_ORDER_FIELD,SHOP_SHOW_CACHE_TIME')
            ->groupLocalComment(L('_LOCAL_COMMENT_CONF_'),'goodsDetail')
            ->data($data)
            ->buttonSubmit()
            ->buttonBack()
            ->display();
    }

    /**已完成交易列表
     * @param int $page
     * @param int $r
     * @author luoj
     */
    public function goodsBuySuccess($page = 1, $r = 20)
    {
        //读取列表
        $map['status'] = 1;
        $map['is_back'] = 0;
        $map['use_status'] = array('gt', 0);
        $model = M('jk_shoporders');
        $list = $model->where($map)->page($page, $r)->order('sendtime DESC')->select();
        $totalCount = $model->where($map)->count();

        foreach ($list as &$val) {
            $val['goods_name'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('name'));
            
            $val['phone'] = op_t(M('jk_users')->where('id=' . $val['user_id'])->getField('phone'));
            $val['address'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('adress'));
            
            $val['phone'] = op_t(M('jk_users')->where('id=' . $val['user_id'])->getField('phone'));
        }
        unset($val);
        //显示页面
        $builder = new AdminListBuilder();

        $builder->title(L('_TRADE_ACCOMPLISHED_'));
        $builder->meta_title = L('_TRADE_ACCOMPLISHED_');

       
        $builder->keyId()->keyText('goods_name', L('_GOODS_NAME_'))->keyText('phone', '用户手机')
            ->keyText('address', L('所属店铺'))
            ->keyText('paytime', L('_BUY_TIME_'))
            ->keyUpdateTime('sendtime', L('发货时间'))
            ->key('use_status',L('_STATUS_'), 'status',array(1=>L('未收货'),
                2=>L('已收货')))
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**待发货交易列表
     * @param int $page
     * @param int $r
     * @author luoj
     */
    public function verify($page = 1, $r = 20)
    {
        //读取列表
        $map = array('status' => 1,'use_status'=>0,'is_back'=>0);
        $model = M('jk_shoporders');
        $list = $model->where($map)->page($page, $r)->order('paytime DESC')->select();
        $totalCount = $model->where($map)->count();
        foreach ($list as &$val) {
            
            $val['goods_name'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('name'));
            $val['name'] = op_t(M('jk_users')->where('id=' . $val['user_id'])->getField('name'));
            $val['phone'] = op_t(M('jk_users')->where('id=' . $val['user_id'])->getField('phone'));
            $val['address'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('adress'));
            
        }
        unset($val);
        
        //显示页面
        $builder = new AdminListBuilder();

        $builder->title(L('_GOODS_WAIT_DELIVER_'));
        $builder->meta_title = L('_GOODS_WAIT_DELIVER_');

        $builder->buttonEnable(U('setGoodsBuyStatus'), L('_DELIVER_'))
            ->keyId()->keyText('goods_name', L('_GOODS_NAME_'))->keyText('name', '用户名')
            ->keyText('phone', '用户手机')
            ->keyText('address', L('所属店铺'))
            ->keyText('paytime', L('_BUY_TIME_'))
            ->key('status',L('_STATUS_'), 'status',array(0=>L('未付款'),1=>L('已付款，未发货'),
                2=>L('已收货'),3=>L('交易完毕'),4=>L('申请退款'),5=>L('退款完毕')))
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
    * 函数用途描述：虚拟商品列表
    * @date: 2016年6月14日 下午3:53:47
    * @author: jun
    * @param: variable
    * @return:
    */
    public function virtual($page = 1, $r = 20)
    {
        //读取列表
        $staut=I('get.isuse', 0, 'intval');
        $map = $where = "1=1";
        if($staut>0){
            $map= "isuse = $staut";
            $where="o.isuse = $staut";
        }
        
        $model = M('jk_goodmsg');
        $prefix=C("DB_PREFIX");
        $list=M()->table($prefix.'jk_goodmsg AS o,'.$prefix.'jk_users AS u,'.$prefix.'jk_shoplist AS s')
        ->field('o.id,o.aid,u.phone,s.name,o.no,o.isuse,o.used_time')
        ->where($where." AND o.shop_id=s.id AND o.uid=u.id")->page($page, $r)->select();
        
        //$list = $model->where($map)->page($page, $r)->select();
        $totalCount = $model->where($map)->count();
        foreach ($list as &$val) {
            if($val['isuse']==1&&$val['aid']>0){
                $info=M('jk_users')->where("id=".$val['aid'])->field('name,phone')->find();
                $val['admin'] = $info['name']."[".$info['phone']."]";
            }
            
        }
        unset($val);
        //显示页面
        $astauts=array(array('id' => 0, 'value' => L('_ALL_')),
            array('id' => 2, 'value' => L('未使用')),array('id' => 1, 'value' => L('已使用')));
        $builder = new AdminListBuilder();
        $builder->setSelectPostUrl(U('Shop/virtual'))        
        ->select(L('商品状态：'), 'isuse', 'select', L('选择状态'), '', '', $astauts);
        
        $builder->title(L('虚拟商品列表'));
        $builder->meta_title = L('虚拟商品列表');
    
        $builder->keyId()->keyText('name', L('_GOODS_NAME_'))->keyText('phone', '用户手机')
        ->keyText('no', L('商品短信码'))->keyUpdateTime('used_time','使用时间')
        ->key('isuse',L('_STATUS_'), 'status',array(2=>L('未使用'),1=>L('已使用')))
        ->keyText('admin', L('核销人'))
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    /**
    * 函数用途描述：发货
    * @date: 2016年9月8日 上午9:48:17
    * @author: luojun
    * @param: 
    * @return:
    */
    public function setGoodsBuyStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $status=1;
        if (empty($ids)) {
            $this->error('请选择要操作的数据!');
        }
        if ($status == 1) {
            $gettime = time();
            foreach ($ids as $id) {
                $data=array();
                $data['sendtime']=time();
                $data['use_status']=1;
                M('jk_shoporders')->where('id=' . $id)->save($data);                
            }
        }
        $this->success('操作成功');
    }

    /**商城日志
     * @param int $page
     * @param int $r
     * @author luoj
     */
    public function shopLog($page = 1, $r = 20)
    {
        //读取列表
        $model = M('jk_shop_log');
        $list = $model->page($page, $r)->order('create_time desc')->select();
        $totalCount = $model->count();
        //显示页面
        $builder = new AdminListBuilder();

        $builder->title(L('_SHOP_MESSAGE_RECORD_'));
        $builder->meta_title = L('_SHOP_MESSAGE_RECORD_');

        $builder->keyId()->keyText('message', L('_MESSAGE_'))->keyUid()->keyCreateTime()
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }
    
    /**
    * 函数用途描述：新增修改卡券
    * @date: 2016年7月25日 下午1:48:46
    * @author: luojun
    * @param: 
    * @return:
    */
    public function cardEdit($id = 0, $name = '', $starttime = '', $endtime = '', $status = '',
         $type=0, $price='') {
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
                //商品名存在验证
                $map['status'] = array('egt', 0);
                $map['name'] = $name;
                if (M('jk_cards')->where($map)->count()) {
                    $this->error(L('卡券名称重复'));
                }

                $goods['createtime'] = time();
                $rs = M('jk_cards')->add($goods);
            }
            if ($rs) {
                $this->success($isEdit==0 ? L('_SUCCESS_ADD_') : L('_SUCCESS_EDIT_'), U('Shop/shopCard'));
            } else {
                $this->error($isEdit==0 ? L('_FAIL_ADD_') : L('fail_Edit'));
            }
        } else {
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? L('编辑卡券') : L('新增卡券'));
            $builder->meta_title = $isEdit ? L('编辑卡券') : L('新增卡券');
            
            $builder->keyId()->keyText('name', L('卡券名称'))
            ->keyTime('starttime','开始时间')
            ->keyTime('endtime','结束时间')
            ->keySelect('price',L('卡券类型'), '', array('1'=>'抵用券','2'=>'礼品券'))            
            ->keyText('price', L('卡券面额'));
                       
            if ($isEdit) {
                $goods = M('jk_cards')->where('id='.$id)->find();
                //dump($goods);
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
    * @author: luojun
    * @param: 
    * @return:
    */   
    public function setCardsStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('jk_cards', $ids, $status);
    }
    
    /**
    * 函数用途描述：设置优惠活动状态
    * @date: 2016年7月25日 下午2:01:49
    * @author: luojun
    * @param: 
    * @return:
    */
    public function setActivityStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('jk_activity', $ids, $status);
    }
    
    /**
    * 函数用途描述：商城卡券管理
    * @date: 2016年7月21日 上午9:29:35
    * @author: luojun
    * @param: 
    * @return:
    */
    public function shopCard($page = 1, $r = 20){
        $map['status'] = array('egt', 0);
        $goodsList = M('jk_cards')->where($map)->order('createtime desc')->page($page, $r)->select();
        $totalCount = M('jk_cards')->where($map)->count();
        $builder = new AdminListBuilder();
        $builder->title(L('活动列表'));
        $builder->meta_title = L('活动列表');
        foreach ($goodsList as &$val) {           
            $val['type'] = $val['type']==1?'抵用券':'礼品券';        
        }
        unset($val);
        $builder->buttonNew(U('Shop/cardEdit'))->buttonDelete(U('setCardsStatus'))->setStatusUrl(U('setCardsStatus'));
        $builder->keyId()->keyText('name', L('卡券名称'))
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
    * @author: luojun
    * @param: 
    * @return:
    */
    public function strategyEdit($id = 0, $name = '', $face_code = '', $description = '',$imgs_code='',
         $starttime = '', $endtime = '', $status = '', $aword = 0)
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
            
            if($face_code){
                $goods['face_img']='http://'.$_SERVER['HTTP_HOST'].__ROOT__ .get_cover($goods['face_code'],'path');
            }
            if($imgs_code){
                $tempData=array();
                $temp=explode(',',$imgs_code);
                foreach ($temp as $v){
                    $tempData[]='http://'.$_SERVER['HTTP_HOST'].__ROOT__ .get_cover($v,'path');
                }
                $goods['imgs']=implode(',', $tempData);
                
            }
            
            if ($isEdit) {
                $rs = M('jk_activity')->where('id=' . $id)->save($goods);
            } else {
                //商品名存在验证
                $map['status'] = array('egt', 0);
                $map['name'] = $name;
                if (M('jk_activity')->where($map)->count()) {
                    $this->error(L('活动名称重复'));
                }

                $goods['createtime'] = time();
                $rs = M('jk_activity')->add($goods);
            }
            if ($rs) {
                $this->success($isEdit==0 ? L('_SUCCESS_ADD_') : L('_SUCCESS_EDIT_'), U('Shop/shopStrategy'));
            } else {
                $this->error($isEdit==0 ? L('_FAIL_ADD_') : L('fail_Edit'));
            }
        } else {
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? L('编辑活动') : L('新增活动'));
            $builder->meta_title = $isEdit ? L('编辑活动') : L('新增活动');

            //获取分类列表
            $category_map['status'] = array('egt', 0);
            $goods_category_list = M('jk_cards')->where($category_map)->order('id desc')->select();
            $options = array_combine(array_column($goods_category_list, 'id'), array_column($goods_category_list, 'name'));
            
            
            $builder->keyId()->keyText('name', L('活动名称'))            
            ->keyText('description', L('活动描述'))
            ->keyTime('starttime','开始时间')
            ->keyTime('endtime','结束时间')
            ->keySelect('aword',L('活动奖品'), '', array(''=>'无')+$options)            
            ->keySingleImage('face_code', L('活动封面'))
            ->keyMultiImage('imgs_code', L('活动详情'),'',20)
            ->keyText('url', L('活动外部链接'));
                       
            if ($isEdit) {
                $goods = M('jk_activity')->where('id=' . $id)->find();
                //dump($goods);
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
    * @author: luojun
    * @param: 
    * @return:
    */
    public function scareEdit($id = 0, $name = '', $description='', $starttime = '', $endtime = '',
         $status = '', $aword='',$activity_price='') {
        //keyCheckBox
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
                //商品名存在验证
                $map['status'] = array('egt', 0);
                $map['name'] = $name;
                if (M('jk_activity')->where($map)->count()) {
                    $this->error(L('活动名称重复'));
                }
                $goods['type'] = 1;
                $goods['createtime'] = time();
                $rs = M('jk_activity')->add($goods);
            }
            if ($rs) {
                $this->success($isEdit==0 ? L('_SUCCESS_ADD_') : L('_SUCCESS_EDIT_'), U('Shop/scareBuy'));
            } else {
                $this->error($isEdit==0 ? L('_FAIL_ADD_') : L('fail_Edit'));
            }
        } else {
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? L('编辑秒杀活动') : L('新增秒杀活动'));
            $builder->meta_title = $isEdit ? L('编辑秒杀活动') : L('新增秒杀活动');

            //获取分类列表
            $category_map['status'] = array('egt', 0);
            $category_map['createtime'] = array('gt', 0);
            $goods_category_list = M('jk_shoplist')->where($category_map)->order('id desc')->select();
            $options = array_combine(array_column($goods_category_list, 'id'), array_column($goods_category_list, 'name'));
            
            
            $builder->keyId()->keyText('name', L('秒杀活动名称'))            
            ->keyText('description', L('秒杀活动描述'))
            ->keyText('activity_price', L('秒杀价格'))            
            ->keyTime('starttime','开始时间')
            ->keyTime('endtime','结束时间')
            ->keySelect('aword',L('选择秒杀商品'), '', $options);            
                                   
            if ($isEdit) {
                $goods = M('jk_activity')->where('id=' . $id)->find();
                //dump($goods);
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
    * @author: luojun
    * @param: 
    * @return:
    */
    public function scareBuy($page = 1, $r = 20){
        $map['status'] = array('egt', 0);
        $map['type'] = 1;
        $goodsList = M('jk_activity')->where($map)->order('createtime desc')->page($page, $r)->select();
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
        $builder->buttonNew(U('Shop/scareEdit'))->buttonDelete(U('setActivityStatus'))->setStatusUrl(U('setActivityStatus'));
        $builder->keyId()->keyText('name', L('活动名称'))->keyText('aword', L('秒杀商品'))
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
    * @author: luojun
    * @param: 
    * @return:
    */
    public function hotelEdit($id = 0, $name = '', $tel='', $description='', $code = '', $address = '',
        $status = '', $lan='',$lat='') {
        //keyCheckBox
        $isEdit = $id ? 1 : 0;
        if (IS_POST) {
            if ($name == '' || $name == null) {
                $this->error(L('请填写店面名称'));
            }
            if ($tel == '' ) {
                $this->error(L('请填写联系电话'));
            }
            if ($address == '' ) {
                $this->error(L('请填写店面地址'));
            }
            if ($description == '' || $description == null) {
                $this->error(L('请填写店面描述简介'));
            }
            if ($code == '' || $code == null) {
                $this->error(L('请上传封面图片'));
            }
            
            $goods = M('jk_hotels')->create();
            if($goods['code']){
                $goods['img']='http://'.$_SERVER['HTTP_HOST'].__ROOT__ .get_cover($goods['code'],'path');
            }
            if ($isEdit) {
                $rs = M('jk_hotels')->where('id=' . $id)->save($goods);
            } else {
                //商品名存在验证
                $map['status'] = array('egt', 0);
                $map['name'] = $name;
                if (M('jk_activity')->where($map)->count()) {
                    $this->error(L('店面名称重复'));
                }
                
                $goods['createtime'] = time();
                $rs = M('jk_hotels')->add($goods);
            }
            if ($rs) {
                $this->success($isEdit==0 ? L('_SUCCESS_ADD_') : L('_SUCCESS_EDIT_'), U('Shop/hotelList'));
            } else {
                $this->error($isEdit==0 ? L('_FAIL_ADD_') : L('fail_Edit'));
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
    * @author: luojun
    * @param: 
    * @return:
    */
    public function setHotelStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('jk_hotels', $ids, $status);
    }
    /**
    * 函数用途描述：酒店位置信息
    * @date: 2016年7月26日 上午11:49:34
    * @author: luojun
    * @param: 
    * @return:
    */
    public function hotelList($page = 1, $r = 20) {
        $map['status'] = array('egt', 0);
        
        $goodsList = M('jk_hotels')->where($map)->order('createtime desc')->page($page, $r)->select();
        $totalCount = M('jk_hotels')->where($map)->count();
        
        $builder = new AdminListBuilder();
        
        $builder->buttonNew(U('Shop/hotelEdit'))->buttonDelete(U('setHotelStatus'))
        ->setStatusUrl(U('setHotelStatus'));
        
        $builder->title(L('店铺分布'));
        $builder->meta_title = L('店铺分布');
        
        $builder->keyId()->keyText('name', L('店铺名称'))
        ->keyText('address', L('店铺位置'))->keyStatus('status', L('状态'))
        ->keyDoActionEdit('Shop/hotelEdit?id=###');
                   
        $builder->data($goodsList);
        $builder->pagination($totalCount, $r);
        $builder->display();
    }
    
    /**
    * 函数用途描述：优惠活动管理
    * @date: 2016年7月21日 上午9:30:16
    * @author: luojun
    * @param: 
    * @return:
    */
    public function shopStrategy($page = 1, $r = 20){
        $map['status'] = array('egt', 0);
        $map['type'] = 0;
        $goodsList = M('jk_activity')->where($map)->order('createtime desc')->page($page, $r)->select();
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
        $builder->buttonNew(U('Shop/strategyEdit'))->buttonDelete(U('setActivityStatus'))->setStatusUrl(U('setActivityStatus'));
        $builder->keyId()->keyText('name', L('活动名称'))->keyText('aword', L('活动奖品'))
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
    * @author: luojun
    * @param: 
    * @return:
    */
    public function aliRefund($page = 1, $r = 20) {
        //读取列表
        $staut=I('get.is_back', 0, 'intval');
        $map['is_back']=1;
        if($staut>1){
            $map['is_back'] = $staut;
        }
    
        $map['status'] = array('egt', 0);
    
        $map['paytype'] = '支付宝';
        
        $model = M('jk_shoporders');
        $list = $model->where($map)->page($page, $r)->select();
        //         dump($model->_sql());
        $totalCount = $model->where($map)->count();
        foreach ($list as &$val) {
    
            $val['goods_name'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('name'));
            $val['phone'] = op_t(M('jk_users')->where('id=' . $val['user_id'])->getField('phone'));
            $val['name'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('adress'));
            $val['address'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('adress'));
        }
        unset($val);
    
        //显示页面
        $astauts=array(array('id' => 2, 'value' => L('已退款')),array('id' => 0, 'value' => L('未退款')));
    
        $builder = new AdminListBuilder();
        $builder->setSelectPostUrl(U('Shop/aliRefund'))
        ->select(L('退款状态：'), 'is_back', 'select', L('选择退款状态'), '', '', $astauts);
        $builder->title(L('支付宝退款申请列表'));
        //         $builder->title(L('退款申请列表'));
        $builder->meta_title = L('退款申请列表');
    
        $builder->keyId()->keyText('goods_name', L('_GOODS_NAME_'))->keyText('phone', '用户手机')
        ->keyText('name', '用户名')
        ->keyText('address', L('所属店铺'))
        ->keyText('paytime', L('_BUY_TIME_'))
        ->keyText('backreason', L('退款理由'))
        ->key('is_back',L('退款状态'), 'status',array(0=>L('未申请付款'),1=>L('未退款'),
            2=>L('已退款')))
            ->key('use_status',L('订单状态'), 'status',array(0=>L('未发货'),1=>L('已发货，未收货'),
                2=>L('已收货')));
            //             ->keyDoActionModalPopup('Shop/refundIframe?id=###','退款','退款操作');
            if($staut<=1){
                //支付宝退款
                $builder->keyDoActionModalPopup('Shop/refundIframe?id=###','支付宝退款','退款操作');                    
            }
    
    
            $builder->data($list)
            ->pagination($totalCount, $r)
            ->display();
    
    }
    
    /**
    * 函数用途描述：微信退款申请
    * @date: 2016年8月16日 下午2:09:23
    * @author: luojun
    * @param: 
    * @return:
    */
    public function Refund($page = 1, $r = 20) {
        //读取列表
        $staut=I('get.is_back', 0, 'intval');
        $map['is_back']=1;
//         dump($staut);
        if($staut>1){
            $map['is_back'] = $staut;
        }
        
        $map['status'] = array('egt', 0);
        
        $map['_string'] = "paytype='微信' OR paytype='公众号'";
        
        $model = M('jk_shoporders');
        $list = $model->where($map)->page($page, $r)->select();
//         dump($model->_sql());
        $totalCount = $model->where($map)->count();
        foreach ($list as &$val) {
            
            $val['goods_name'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('name'));         
            $val['phone'] = op_t(M('jk_users')->where('id=' . $val['user_id'])->getField('phone'));
            $val['name'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('adress'));
            $val['address'] = op_t($this->shopModel->where('id=' . $val['shop_id'])->getField('adress'));
        }
        unset($val);
        
        //显示页面
        $astauts=array(array('id' => 2, 'value' => L('已退款')),array('id' => 0, 'value' => L('未退款')));
        
        $builder = new AdminListBuilder();
        $builder->setSelectPostUrl(U('Shop/refund'))
        ->select(L('退款状态：'), 'is_back', 'select', L('选择退款状态'), '', '', $astauts);
        $builder->title(L('微信退款申请列表'));
//         $builder->title(L('退款申请列表'));
        $builder->meta_title = L('退款申请列表');

        $builder->keyId()->keyText('goods_name', L('_GOODS_NAME_'))->keyText('phone', '用户手机')
            ->keyText('name', '用户名')
            ->keyText('address', L('所属店铺'))
            ->keyText('paytime', L('_BUY_TIME_'))
            ->keyText('backreason', L('退款理由'))
            ->key('is_back',L('退款状态'), 'status',array(0=>L('未申请付款'),1=>L('未退款'),
                2=>L('已退款')))
            ->key('use_status',L('订单状态'), 'status',array(0=>L('未发货'),1=>L('已发货，未收货'),
                2=>L('已收货')));
//             ->keyDoActionModalPopup('Shop/refundIframe?id=###','退款','退款操作');
            if($staut<=1){                
                $builder->keyDoAction('Shop/refundOrder?id=###','微信退款','退款操作',
                    array('class' => 'ajax-get'));                
            }
            
            
            $builder->data($list)
            ->pagination($totalCount, $r)
            ->display();
        
    }
    
	    /**
    * 函数用途描述：还款模态框
    * @date: 2016年8月25日 上午11:18:24
    * @author: luojun
    * @param: 
    * @return:
    */
    public function refundIframe($id) {
        
        $this->assign('id', $id);
		$this->display('/Shop@shop/refundIframe');
		return;
    }
    
	
    /**
    * 函数用途描述：订单退款
    * @date: 2016年8月17日 下午3:14:56
    * @author: luojun
    * @param: 
    * @return:
    */
    public function refundOrder($id) {
        
        if(!$id){
            $this->error('退款'.L('_FAIL_').L('未指定订单！'));
        }
        $type = M('jk_shoporders')->where("id=$id")->getField('paytype');
        
        if($type=='微信'||$type=='公众号'){
            $afind = M('jk_shoporders')->where("id=$id")->field('ordernumber,price,transfee,status,
                is_back')->find();
            if($afind['status']!=1){
                $this->error('退款'.L('_FAIL_').L('未支付的订单！'));
                return;
            }
            if($afind['is_back']==2){
                $this->error('退款'.L('_FAIL_').L('已退款订单！'));
                return;
            }
            if($afind['is_back']==0){
                $this->error('退款'.L('_FAIL_').L('客户未申请退款！'));
                return;
            }
            
            include_once __APPLICATION__."Vip\Controller\JKAppWxPayController.class.php";
            $Wxpay = new \Vip\Controller\JKAppWxPayController();
            $tradeNo=$afind['ordernumber'];
            $totalMoney=floatval($afind['price'])+floatval($afind['transfee']);
//             $this->error('退款'.L('_FAIL_').$type);
            $result=$Wxpay->new_Refund($tradeNo,$totalMoney,$type);
			//dump($result);
            if($result['result_code']=="SUCCESS"&&$result['return_msg']=='OK'){
               
                $data['is_back'] = 2;
                $data['backDetail'] = $result['result_code'];
                M('jk_shoporders')->where("id=$id")->save($data);
				//echo '退款成功!';
                $this->success('退款成功!');
            }
            else {
                //dump($result);
				//echo '退款遇到问题!'.$result['err_code_des'];
                $this->error('退款遇到问题!'.$result['err_code_des']);
            }
            return;
        }
        else if($type=='支付宝'){
            $afind = M('jk_shoporders')->where("id=$id")->field('trade_no,price,transfee')->find();
        
			$refundNo=date('YmdHis', time()) . rand(111, 999);
			M('jk_shoporders')->where("id=$id")->save(array('alirefundno'=>$refundNo));
			 include_once __APPLICATION__."Vip\Controller\AlipayController.class.php";
			$Alipay = new \Vip\Controller\AlipayController();
			
			$WIDdetail_data=$afind['trade_no']."^".(floatval($afind['price'])+
				floatval($afind['transfee']))."^"."协商退款";//支付宝交易号^退款金额^备注
				
			$aliUrl=$Alipay->aliRefund($refundNo,1,$WIDdetail_data);
			
			echo $aliUrl;
        }
        else{
            $this->error('退款'.L('_FAIL_').L('未知的支付方式！'));
// 			echo '退款遇到问题!'.$result['err_code_des'];
        }
        
    }
    
}

?>