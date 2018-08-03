<?php

namespace Admin\Model;
use Think\Model\RelationModel;

/**
 * 问题列表模型类
 * Class JkAcprogram
 * @author yushichuan
 */

class JkAcprogramModel extends RelationModel {
    protected $_link = array(
        
        //联合jk_project表查询项目名称
        'JkProject' => array(
            'mapping_type' => self::BELONGS_TO,
            'mapping_fields' => 'name',
            'foreign_key' => 'project_id',
            'as_fields' => 'name'
        ),
        
        //联合jk_floor表查询building,unit,floor,room
        'JkFloor_building' => array(
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'JkFloor',
            'mapping_fields' => 'title',
            'foreign_key' => 'building_id',
            'as_fields' => 'title:building'
        ),
        'JkFloor_unit' => array(
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'JkFloor',
            'mapping_fields' => 'title',
            'foreign_key' => 'unit_id',
            'as_fields' => 'title:unit'
        ),
        'JkFloor_floor' => array(
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'JkFloor',
            'mapping_fields' => 'title',
            'foreign_key' => 'floor_id',
            'as_fields' => 'title:floor'
        ),
        'JkFloor_room' => array(
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'JkFloor',
            'mapping_fields' => 'title',
            'foreign_key' => 'room_id',
            'as_fields' => 'title:room'
        ),
        
        //联合auth_group表查询title
        'AuthGroup' => array(
            'mapping_type' => self::BELONGS_TO,
            'mapping_fields' => 'title',
            'foreign_key' => 'contractor_id',
            'as_fields' => 'title:auth_title'
        ),
        
        //联合jk_acprogram_image查询户型图
        'JkFloor_house_img_id' => [
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'JkFloor',
            'mapping_fields' => 'house_img_id',
            'foreign_key' => 'room_id',
            'as_fields' => 'house_img_id'
        ]
    );
}   //class end