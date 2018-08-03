<?php 

namespace Admin\Model;
use Think\Model\RelationModel;

/**
 * 任务列表模型类
 * Class JkComment
 * @author yushichuan
 */

class JkCommentModel extends RelationModel {
    protected $_link = [
        //联合jk_comment_etx表查询回复图片
        'JkCommentEtx' => [
            'mapping_type' => self::HAS_MANY,
            'mapping_name' => 'imgs',
            'mapping_fields' => 'path',
            'foreign_key' => 'reply_id'
        ]
        
    ];
}   //class end