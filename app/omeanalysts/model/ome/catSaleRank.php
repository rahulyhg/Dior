<?php
class omeanalysts_mdl_ome_catSaleRank extends dbeav_model{

    var $table_name = 'cat_sale_statis';
    var $defaultOrder = 'sales_amount desc';

    public function table_name($real=false)
    {
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$this->table_name;
        }else{
            return $this->table_name;
        }
    }
	public function _filter($filter,$tableAlias=null,$baseWhere=null){

        $where = array(1);
        if($filter['time_from'] && $filter['time_to'])
        {
        	$where[] = ' g.sales_time >='.$filter['time_from'];
        	$where[] = ' g.sales_time <='.$filter['time_to'];
        }

        return parent::_filter($filter,'g',$baseWhere)." AND ".implode($where,' AND ');

    }
    
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
    	$filter['time_from']=$filter['_params']['time_from'];
    	$filter['time_to']=$filter['_params']['time_to'];

    	$sql = 'SELECT b.name as shop_name,c.name as type_name,sum(g.sales_num) AS sales_num FROM `sdb_omeanalysts_cat_sale_statis` as g,`sdb_ome_shop` as b,`sdb_ome_goods_type` as c WHERE '.$this->_filter($filter).' AND g.shop_id=b.shop_id and g.type_id=c.type_id GROUP BY g.type_id,b.name ORDER BY g.sales_num desc';
    	$rows = $this->db->selectLimit($sql,$limit,$offset);
    	return $rows;
    }
    
	public function get_schema()
    {
    	$schema = array (
			'columns' => 
			  array (
			  /*
			    'id' => 
			    array (
			      'type' => 'int unsigned',
			      'required' => true,
			      'pkey' => true,
			      'extra' => 'auto_increment',
			      'label' => 'ID',
			    ),*/
			    'shop_name' => 
			    array (
				  'type' => 'table:shop@ome',
			      'required' => false,
			      'editable' => false,
				  'label' => '来源店铺',
			      'in_list' => true,
			      'default_in_list' => true,
			      'order' => 1,
			      'width' => 130,
			    ),
			    'type_name' =>  
			    array (
			      'type' => 'table:goods_type@ome',
			      'label' => '商品类目',
			      'width' => 75,
			      'editable' => false,
			      'in_list' => true,
			      'default_in_list' => true,
			      'order' => 2,
			      'width' => 130,
			    ),
				'sales_num' =>
			    array (
			      'type' => 'number',
			      'editable' => false,
				  'label' => '销售量',
				  'filtertype' => 'normal',
				  'filterdefault' => true,
			      'in_list' => true,
			      'default_in_list' => true,
				  'default' => 0,
			      'order' => 3,
			      'width' => 70,
			    ),
			    /*
				'sales_amount' =>
			    array (
			      'type' => 'money',
			      'editable' => false,
				  'label' => '销售额',
				  'filtertype' => 'normal',
				  'filterdefault' => true,
			      'in_list' => true,
			      'default_in_list' => true,
				  'default' => 0,
			      'order' => 4,
			      'width' => 80,
			    ),
			    'brand_id' =>  
			    array (
			      'type' => 'table:brand@ome',
			      'label' => '品牌',
			      'width' => 75,
			      'editable' => false,
			    ),
				'sales_time' =>
			    array (
			      'type' => 'time',
			      'label' => '销售时间',
			      'width' => 130,
			      'editable' => false,
			    ),*/
			  ),
			  
			  'idColumn' => 'id',
                'in_list' => array (
                    0 => 'shop_name',
                    1 => 'type_name',
                    2 => 'sales_num',
                ),
                'default_in_list' => array (
                    0 => 'shop_name',
                    1 => 'type_name',
                    2 => 'sales_num',
                ),
        );
        return $schema;
    }

    public function export_params(){

        //获取框架filter信息
        $filter = $this->export_filter;
        
        //处理filter
        if($filter['isSelectedAll'] == '_ALL_'){
            $filter = array();
        }
        
        $params = unserialize( $_POST['params'] );
        $filter['time_from'] = $params['time_from'];
        $filter['time_to'] = $params['time_to'];



        $params = array(
            'filter' => $filter,
            'single'=> array(
                '1'=> array(
                    'method' => 'get_export_main',
                    'offset' => 0,
                    'limit' => 4000,
                    'filename' => '商品类目销售排行榜',
                ),
                
            ),
            
        );
        return $params;
    }
    
    public function get_export_main_title(){
        $title = array(
            '*:来源店铺',
            '*:排行',
            '*:商品类目',
            '*:销售量',
            '*:销售额',
            '*:品牌',
        );
        return $title;
    }


    public function get_export_main($filter,$offset,$limit,&$data){
        $shopModel = &app::get('ome')->model('shop');
        $goods_typeModel = &app::get('ome')->model('goods_type');
        $brandModel = &app::get('ome')->model('brand');

        $shop_list = $shopModel->getList('name,shop_id');
        if ($shop_list){
            foreach ( $shop_list as $shop ){
                $sql = sprintf(' SELECT type_id,brand_id,sum(sales_num) AS sales_num,sum(sales_amount) AS sales_amount FROM `sdb_omeanalysts_cat_sale_statis` WHERE sales_time>=\'%s\' AND sales_time<=\'%s\' AND shop_id=\'%s\' GROUP BY type_id,brand_id ORDER BY sales_num desc,sales_amount desc LIMIT %s,%s',$filter['time_from'],$filter['time_to'],$shop['shop_id'],$offset,$limit);
                $tmp = kernel::database()->select($sql);
                if ($tmp){
                    $rank_value = array();
                    $rank = 0;
                    foreach ( $tmp as $sort=>$val ){
                        $type_detail = $goods_typeModel->dump($val['type_id'],'name');
                        $brand_detail = $brandModel->dump($val['brand_id'],'brand_name');
                        if (!in_array($val['sales_num'],$rank_value)){
                            $rank += 1;
                            $rank_value[] = $val['sales_num'];
                        }
                        $data[] = array(
                            '*:来源店铺' => $shop['name'],
                            '*:排行' => $rank,
                            '*:商品类目' => $type_detail['name'],
                            '*:销售量' => $val['sales_num'],
                            '*:销售额' => $val['sales_amount'],
                            '*:品牌' => $brand_detail['brand_name'],
                        );
                        
                    }
                }
            }
        }


    }

}