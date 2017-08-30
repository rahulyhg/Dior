<?php
$db['branch_product_stock_detail']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
    ),
	'product_id' =>  
    array (
      'type' => 'table:products@ome',
      'label' => '货号',
    ),
    'branch_id' =>  
    array (
      'type' => 'table:branch@ome',
      'label' => '仓库',
    ),
    'months' =>
    array (
      'type' => 'varchar(8)',
      'label' => '月份',
    ),
	'day1' =>
    array (
      'type' => 'int',
      'label' => '第一天库存数,以下依此类推',
    ),
    'day2' =>
    array (
      'type' => 'int',
    ),
    'day3' =>
    array (
      'type' => 'int',
    ),
    'day4' =>
    array (
      'type' => 'int',
    ),
    'day5' =>
    array (
      'type' => 'int',
    ),
    'day6' =>
    array (
      'type' => 'int',
    ),
    'day7' =>
    array (
      'type' => 'int',
    ),
    'day8' =>
    array (
      'type' => 'int',
    ),
    'day9' =>
    array (
      'type' => 'int',
    ),
    'day10' =>
    array (
      'type' => 'int',
    ),
    'day11' =>
    array (
      'type' => 'int',
    ),
    'day12' =>
    array (
      'type' => 'int',
    ),
    'day13' =>
    array (
      'type' => 'int',
    ),
    'day14' =>
    array (
      'type' => 'int',
    ),
    'day15' =>
    array (
      'type' => 'int',
    ),
    'day16' =>
    array (
      'type' => 'int',
    ),
    'day17' =>
    array (
      'type' => 'int',
    ),
    'day18' =>
    array (
      'type' => 'int',
    ),
    'day19' =>
    array (
      'type' => 'int',
    ),
    'day20' =>
    array (
      'type' => 'int',
    ),
    'day21' =>
    array (
      'type' => 'int',
    ),
    'day22' =>
    array (
      'type' => 'int',
    ),
    'day23' =>
    array (
      'type' => 'int',
    ),
    'day24' =>
    array (
      'type' => 'int',
    ),
    'day25' =>
    array (
      'type' => 'int',
    ),
    'day26' =>
    array (
      'type' => 'int',
    ),
    'day27' =>
    array (
      'type' => 'int',
    ),
    'day28' =>
    array (
      'type' => 'int',
    ),
    'day29' =>
    array (
      'type' => 'int',
    ),
    'day30' =>
    array (
      'type' => 'int',
    ),
    'day31' =>
    array (
      'type' => 'int',
    ),
  ),
  'comment' => '商品库存日报表',
  'index' => 
  array (
    'ind_product_id' => 
    array (
      'columns' => 
      array (
        0 => 'product_id',
      ),
    ),
    'ind_branch_id' => 
    array (
      'columns' => 
      array (
        0 => 'branch_id',
      ),
    ),
    'ind_months' => 
    array (
      'columns' => 
      array (
        0 => 'months',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
