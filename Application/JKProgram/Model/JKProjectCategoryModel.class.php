<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <code-tech.diandian.com>
// +----------------------------------------------------------------------

namespace JKProgram\Model;
use Think\Model;

/**
 * Class Shop_categoryModel
 * @package Shop\Model
 * @郑钟良
 */
class JKProjectCategoryModel extends Model {

    protected $tableName='jk_floor';
    protected $_validate = array(
          array('url','require','url必须填写'), //默认情况下用正则进行验证
    );
    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME, self::MODEL_UPDATE),
        array('status', '1', self::MODEL_INSERT),
    );


    /**
     * 获得分类树
     * @param int $id
     * @param bool $field
     * @return array
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function getTree($id = 0, $field = true,$map='',$order=''){
		
        /* 获取当前分类信息 */
        if($id){
            $info = $this->info($id);
            $id   = $info['id'];
        }

        /* 获取所有分类 */
        if(!$map){
            $map['status']  = array('gt', -1);
        }
		
        //如果是楼栋，按title排序
       	if($order!=''){
			file_put_contents('time.log',json_encode($map), FILE_APPEND);
       		$list = $this->field($field)->where($map)->order($order)->select();
			file_put_contents('time.log',$this->_sql(), FILE_APPEND);
       	}else{
        	$list = $this->field($field)->where($map)->order('id')->select();
       	}
		
        $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_', $root = $id);

        //dump($list);
        
        /* 获取返回数据 */
        if(isset($info)){ //指定分类则返回当前分类极其子分类
            $info['_'] = $list;
        } else { //否则返回所有分类
            $info = $list;
        }

        return $info;
    }


    /**
     * 获取分类详细信息
     * @param $id
     * @param bool $field
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function info($id, $field = true){
        /* 获取分类信息 */
        $map = array();
        if(is_numeric($id)){ //通过ID查询
            $map['id'] = $id;
        } else { //通过标识查询
            $map['name'] = $id;
        }
        return $this->field($field)->where($map)->order('id')->find();
    }

    public function editData($data)
    {
        $data=$this->create();
        $data['imgpath']=M('picture')->where(array('id'=>$data['imgid']))->getField('path');
        if($data['id']){
            $res=$this->save($data);
        }else{
            $res=$this->add($data);
        }
       // return M()->getLastSql();
        return $res;
    }
    
    //快速创建
    public function addDataMore($data){
    	dump($data);
    	exit;
    	$data=$this->create();
    	$data['imgpath']=M('picture')->where(array('id'=>$data['imgid']))->getField('path');
    	
    	for($i=1;$i<=$data['createnum'];$i++){
    		
    		$data['title']=$data['title']."(".$i.")";
    		$res=$this->add($data);
    	}
    	return $res;
    }
}

?>