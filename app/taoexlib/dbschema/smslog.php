<?php
$db['smslog'] = array (
	'columns' => array(
		'id' => array(
			'type' => 'int unsigned',
			'required' => true,
			'pkey' => true,
			'extra' => 'auto_increment',
			'editable' => false,
		),
		'batchno' => array(
			'type' => 'int(11)',
			'required' => true,
		),
		'deliverybn' => array(
			'type' => 'varchar(50)',
			'required' => true,
			'in_list'=>true,
			'label' => '发货单号',
			'default_in_list'=>true,
            'filtertype'=>true,
            'searchtype'=>true,
            'searchtype' => 'has',
		),
		'logino' => array(
			'type' => 'varchar(50)',
			'required' => true,
			'in_list'=>true,
			'label' => '快递单号',
			'default_in_list'=>true,
            'filtertype'=>true,
            'searchtype'=>true,
            'searchtype' => 'has',
		),
		'mobile' => array(
			'type' => 'varchar(50)',
			'label' => '手机号码',
			'required' => true,
			'in_list'=>true,
			'default_in_list'=>true,
            'filtertype'=>true,
            'searchtype'=>true,
            'searchtype' => 'has',
		),
		'content' => array(
			'type' => 'varchar(250)',
			'required' => true,
		),
		'status' => array(
			'default' => '1',			
			'type' => array(
				'0' => '发送失败',
				'1' => '发送成功'
			),
			'required' => true,
			'label' => '发送状态',
			'in_list'=>true,
			'default_in_list'=>true,
            'filtertype'=>true,
            'searchtype'=>true,
            'searchtype' => 'has',
		),
		'msg' => array(
			'type' => 'text',
			'label' => '原因',
			'in_list'=>true,
			'default_in_list'=>true,
            'filtertype'=>true,
		),
		'sendtime' => array(
			'type' => 'time',
			'required' => true,
			'label' => '发送时间',
			'in_list'=>true,
			'default_in_list'=>true,
            'filtertype'=>true,
            'searchtype'=>true,
            'searchtype' => 'has',
		)
	),
	'version' => '$Rev: 44513 $',
);
