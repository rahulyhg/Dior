<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 * @version osc---hanbingshu sanow@126.com
 * @date 2012-08-02
 */
class tgstockcost_costschema
{
  function get_schema()
  {
      $db['branch_product']=array (
        'columns' =>
        array (
			'id' => 
				array (
				'type' => 'number',
				'required' => true,
				'pkey' => true,
				'extra' => 'auto_increment',
				'editable' => false,
			),
          'product_bn' =>
          array (
            'label' => '货号',
            'editable' => false,
            'in_list' => true,
            'default_in_list'=>true,
			'searchtype' => 'has',
			'filterdefault' => true,
            'filtertype' => 'normal',
            'filterdefault' => true,
          ),
          'product_name' =>
          array (
            'label' => '名称',
            'editable' => false,
            'in_list' => true,
            'default_in_list'=>true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'orderby'=>false,
          ),

          'store' =>
          array (
            'label' => '库存数',
            'editable' => false,
            'in_list' => true,
            'default_in_list'=>true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'orderby'=>false,
          ),
          'unit_cost' =>
          array (
            'label' => '单位平均成本',
		   'type'=>'money',
            'editable' => false,
            'in_list' => true,
            'default_in_list'=>true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'orderby'=>false,
          ),
          'inventory_cost' =>
          array (
            'label' => '库存成本',
            'editable' => false,
            'in_list' => true,
            'default_in_list'=>true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'orderby'=>false,
			'type'=>'money',
          ),
        ),
       // 'engine' => 'innodb',
        //'version' => '$Rev:  $',
      );
      return $db['branch_product'];
  }
}