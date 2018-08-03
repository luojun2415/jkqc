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
        
        //联合jk_taskinfo表查询任务位置
        'JkTaskinfo' => [
            'mapping_type' => self::HAS_ONE,
            'mapping_fields' => 'position_name',
            'foreign_key' => 'task_id',
            'as_fields' => 'position_name'
        ],
    ];
}   //class end