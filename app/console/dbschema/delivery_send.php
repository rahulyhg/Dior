<?php
$db['delivery_send']=array (
 'columns' =>
  array (
     'delivery_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
   ),
   'sync' =>
    array (
      'type' => array(
          'none' => '未发起',
          'running' => '运行中',
          'success' => '成功',
          'fail' => '失败',
          'sending' => '发起中',
      ),
      'default' => 'sending',
      'label' => '回写状态',
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'msg' =>
    array (
      'type' => 'text',
      'editable' => false,
    ), 
),
    
'engine' => 'innodb',
'version' => '$Rev: 41996 $',
);
?>