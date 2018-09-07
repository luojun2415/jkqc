<?php 

namespace Admin\Model;
use Think\Model\RelationModel;

/**
 * 任务列表模型类
 * Class JkTask
 * @author yushichuan
 */

class JkTaskModel extends RelationModel {
    protected $_link = [
        //联合jk_project表查询项目名称
        'JkProject' => [
            'mapping_type' => self::BELONGS_TO,
            'mapping_fields' => 'name',
            'foreign_key' => 'pro_id',
            'as_fields' => 'name:pro_name'
        ],

    ];
}   //class end