<?php
$db['sms_sign'] = array (
	'columns' => array(
        's_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'label' => '签名编号',
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'name' => array(
            'type' => 'varchar(45)',
            'required' => true, 
            'label' => "名称",
            'default'  =>'0',
            'in_list'=>false,
            'default_in_list'=>false,
            'editable' => false,
        ),
        'extend_no' => array(  
            'type' => 'varchar(45)',
            'label' => '返回编号',
           
            'default'  =>'0',
            'in_list'=>true,
            'default_in_list'=>true,
        ),
       
    ),
    'version' => '$Rev: 44513 $',
);
