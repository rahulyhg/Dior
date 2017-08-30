<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 * @version osc---hanbingshu sanow@126.com
 * @date 2012-08-02
 */
class tgstockcost_schema_stockcost
{
  function get_schema()
    {
    $db['branch_product'] = array(
      'columns' =>
      array (
      'product_bn' =>
        array (
          'label'           => '货号',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filterdefault'   => true,
          'filtertype'      => 'normal',
          'order'           =>5,
          'filterdefault'   => true,
          'panel_id'        =>'stocksummary_finder_top',
        ),
      'product_name' =>
        array (
          'label'           => '货品名称',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'order'           =>6,
          'panel_id'        =>'stocksummary_finder_top',
        ),
      'type_name' =>
        array (
          'label'           => '商品类型',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'order'           =>2,
          'orderby'         =>false,
          'type'            => 'table:goods_type@ome',
          'panel_id'        =>'stocksummary_finder_top',
        ),
      'brand_name' =>
        array (
          'label'           => '品牌',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'order'           =>3,
          'orderby'         =>false,
          'type'            => 'table:brand@ome',
          'panel_id'        =>'stocksummary_finder_top',
        ),
      'spec_info' =>
        array (
          'label'           => '规格',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'order'           =>7,
        ),
      'start_nums' =>
        array (
          'label'           => '期初数量',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'order'           =>8,
        ),
      'start_unit_cost' =>
        array (
          'label'           => '期初单位成本',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'type'            =>'money',
          'order'           =>9,
        ),
      'start_inventory_cost' =>
        array (
          'label'           => '期初商品成本',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'type'            =>'money',
          'order'           =>10,
        ),
      'in_nums' =>
        array (
          'label'           => '入库数量',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'order'           =>11,
        ),
      'in_unit_cost' =>
        array (
          'default'         => '0',
          'label'           => '入库平均成本',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'type'            =>'money',
          'order'           =>12,
        ),
      'in_inventory_cost' =>
        array (
          'label'           => '入库商品成本',
          'editable'        => false,
          'in_list'         => true,
          'default'         => '0',
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'type'            =>'money',
          'order'           =>13,
        ),
      'out_nums' =>
        array (
          'label'           => '出库数量',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'order'           =>14,
        ),
        'sale_out_nums' => array(
          'label'           => '销售出库数量',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => false,
          'filterdefault'   => false,
          'orderby'         =>false,
          'order'           =>15,
        ),
      'out_unit_cost' =>
        array (
          'label'           => '出库单位成本',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'type'            =>'money',
          'order'           =>15,
        ),
      'out_inventory_cost' =>
        array (
          'label'           => '出库商品成本',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'type'            =>'money',
          'order'           =>16,
        ),
      'store' =>
        array (
          'label'           => '结存数量',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'order'           =>17,
        ),
      'unit_cost' =>
        array (
          'label'           => '结存单位成本',
          'type'            =>'money',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'type'            =>'money',
          'order'           =>18,
        ),
      'inventory_cost' =>
        array (
          'label'           => '结存商品成本',
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' =>true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'orderby'         =>false,
          'type'            =>'money',
          'order'           =>19,
        ),
      'branch_id' => 
        array (
          'type'            => 'table:branch@ome',
          'pkey'            => true,
          'label'           => '仓库',
          'width'           => 110,
          'editable'        => false,
          'in_list'         => true,
          'default_in_list' => true,
          'filtertype'      => 'normal',
          'filterdefault'   => true,
          'order'           => 1,
          'orderby'         =>false,
          'panel_id'        =>'stocksummary_finder_top',
        ),
      'goods_bn' => 
        array (
          'type'            => 'varchar(50)',
          'required'        => true,
          'default'         => 0,
          'label'           => '商品编号',
          'width'           => 120,
          'editable'        => true,
          'filtertype'      => 'normal',
          'filterdefault'   => 'true',
          'in_list'         => true,
          'default_in_list' => true,
          'order'           => 4,
          'orderby'         =>false,
          'realtype'        => 'varchar(50)',
          'panel_id'        =>'stocksummary_finder_top',
        ),
      'time_from' => 
        array (
          'type'          => 'time',
          'default'       => 0,
          'label'         => '时间段',
          'width'         => 120,
          'editable'      => true,
          'filtertype'    => 'normal',
          'filterdefault' => 'true',
          'panel_id'      =>'stocksummary_finder_top',
        ),
        'unit' => 
        array (
            'type' => 'products@ome',
            'label' => '单位',
            'width' => 110,
            'filtertype' => 'normal',
            'editable' => false,
            'in_list' => true,
        ),
      ),
    );
    
    // 验证是否有成本查看权限
    // $permission = kernel::single('desktop_user')->has_permission('tgstockcost_stocksummary_cost');
    if ($_GET['act'] == 'sellstorage' && $_GET['ctl'] == 'stocksummary' && $_GET['app'] == 'tgstockcost') {
      $cost_cols = array('start_unit_cost','start_inventory_cost','in_unit_cost','in_inventory_cost','out_unit_cost','out_inventory_cost','unit_cost','inventory_cost');
      foreach ($cost_cols as $value) {
        unset($db['branch_product']['columns'][$value]);
      }
    }

    return $db['branch_product'];
  }
}