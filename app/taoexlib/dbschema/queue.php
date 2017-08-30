<?php
$db['queue']=array (
  'columns' => 
  array (
    'queue_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'editable' => false,
      'in_list'=>true,
      'default_in_list'=>true,
      'order'=>10,
    ),
    'queue_title' => 
    array (
      'type' => 'varchar(50)',
      'label'=>app::get('base')->_('队列名称'),
      'required' => true,
      'is_title'=>true,
      'in_list'=>true,
      'width'=>200,
      'default_in_list'=>true,
      'order'=>20,
    
    ),
    'runkey' => 
    array (
      'type' => 'varchar(50)',
      'editable' => false,
    ),
    'is_resume'=>array(
      'label'=>app::get('base')->_('是否重试'),
       'type'=>array(
            'true'=>app::get('base')->_('是'),
            'false'=>app::get('base')->_('否'),
       ),
      'required' => true,
      'default' => 'true',
      'in_list'=>true,
      'width'=>100,
      'order'=>30,
    ),
    'exec_mode'=>array(
      'label'=>app::get('base')->_('执行模式'),
        'type'=>array(
            'script'=>app::get('base')->_('脚本'),
            'http'=>app::get('base')->_('web'),
        ),
      'required' => true,
      'default' => 'script',
      'in_list'=>true,
      'width'=>100,
      'order'=>40,
    ),
    'exec_timeout' => 
    array (
      'type' => 'number',
      'required' => true,
      'default' => 30,
      'label' => app::get('base')->_('超时时间(s)'),
      'editable' => false,
      'in_list'=>true,
      'order'=>50,
    ),
    'resume_nums' => 
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'label' => app::get('base')->_('执行次数'),
      'editable' => false,
      'in_list'=>true,
      'order'=>60,
    ),
    'status'=>array(
      'label'=>app::get('base')->_('状态'),
        'type'=>array(
            'sleeping'=>app::get('base')->_('请求中'),
            'paused'=>app::get('base')->_('已暂停'),
            'running'=>app::get('base')->_('运行中'),
            'failed'=>app::get('base')->_('执行失败'),
    		'succ'=>app::get('base')->_('执行成功'),
        ),
      'required' => true,
      'default' => 'sleeping',
      'in_list'=>true,
      'width'=>100,
      'default_in_list'=>true,
      'order'=>70,
    ),
    'worker'=>array(
      'type' => 'varchar(100)',
      'editable' => false,
    ),
    'host'=>array(
      'type' => 'varchar(200)',
      'editable' => false,
    ),
    'start_time'=>array(
      'type' => 'time',
      'label'=>app::get('base')->_('任务产生时间'),
      'required' => true,
      'in_list'=>true,
      'width'=>150,
      'order'=>80,
    ),
    'worker_active'=>array(
      'type' => 'time',
      'label'=>app::get('base')->_('上次运行时间'),
      'in_list'=>true,
      'width'=>150,
      'default_in_list'=>true,
      'order'=>90,
    ),
     'spend_time'=>array(
      'type' => 'time',
      'label'=>app::get('base')->_('执行花费时间'),
      'in_list'=>true,
      'width'=>150,
      'default_in_list'=>true,
      'order'=>90,
    ),
    'type' => 
    array (
      'type' => 'varchar(50)',
      'label' => '队列类型',
      'editable' => false,
    ),
    'params'=>array(
      'type' => 'serialize',
      'label'=>app::get('base')->_('参数'),
      'required' => true,
      'comment'=>app::get('base')->_('参数，通常就是filter'),
    ),
    'errmsg'=>array(
      'type' => 'text',
      'default_in_list'=>true,
      'in_list'=>true,
      'width'=>200,
      'label'=>app::get('base')->_('错误信息'),
      'order'=>100,
    ),
  ),
  'index' => 
  array (
    'ind_worker_active' => 
    array (
      'columns' => 
      array (
        0 => 'worker_active',
      ),
    ),
    'ind_status' => 
    array (
      'columns' => 
      array (
        0 => 'status',
      ),
    ),
    'ind_resume_nums' => 
    array (
      'columns' => 
      array (
        0 => 'resume_nums',
      ),
    ),
    'ind_type' => 
    array (
      'columns' => 
      array (
        0 => 'type',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 40912 $',
  'ignore_cache' => true,

);


//需要id从大到小的执行
