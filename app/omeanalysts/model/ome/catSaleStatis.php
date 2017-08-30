<?php
class omeanalysts_mdl_ome_catSaleStatis extends dbeav_model{

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
			    ),
			    */
			    'shop_id' => 
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
			    'type_id' =>  
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
			    /*
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
                    0 => 'shop_id',
                    1 => 'type_id',
                    2 => 'sales_num',
                    3 => 'sales_amount',
                ),
                'default_in_list' => array (
                    0 => 'shop_id',
                    1 => 'type_id',
                    2 => 'sales_num',
                    3 => 'sales_amount',
                ),
        );
        return $schema;
    }

    public function count($filter=null){
        $sql = ' SELECT count(*) FROM (SELECT shop_id,type_id,sum(sales_num) AS sales_num,sum(sales_amount) AS sales_amount FROM `'.$this->table_name(true).'` WHERE '.$this->_filter($filter).' GROUP BY shop_id,type_id) AS c ';
        $tmp = $this->db->count($sql);
        return $tmp;
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){

        $orderType = $orderType?$orderType:$this->defaultOrder;
        $sql = ' SELECT shop_id,type_id,sum(sales_num) AS sales_num,sum(sales_amount) AS sales_amount FROM `'.$this->table_name(true).'` WHERE '.$this->_filter($filter);   
        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);
        $data = $this->db->selectLimit($sql,$limit,$offset);
        return $data;
    }
    
    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = ' AND 1';
        // 默认当前月
        if ( isset($filter['time_from']) || isset($filter['time_to']) ){
            $time_from = date('Y-m-d', strtotime($filter['time_from']));
            $time_to = date('Y-m-d 23:59:59', strtotime($filter['time_to']));
            $where = ' AND FROM_UNIXTIME(`sales_time`,\'%Y-%m-%d\')>=\''.$time_from.'\' AND FROM_UNIXTIME(`sales_time`,\'%Y-%m-%d\')<=\''.$time_to.'\'';
        }

        // 店铺选择
        if ( isset($filter['type_id']) && $filter['type_id'] ){
            $filter['shop_id'] = $filter['type_id'];
        }
        unset($filter['type_id']);

        // 销售量
        if( isset($filter['sales_num']) ){
            $sales_num = $filter['sales_num'];
            $having .= omeanalysts_func::search_filter($filter['_sales_num_search'],'sales_num',$sales_num);
        }

        // 销售金额
        if( isset($filter['sales_amount']) ){
            $sales_amount = $filter['sales_amount'];
            $having .= omeanalysts_func::search_filter($filter['_sales_amount_search'],'sales_amount',$sales_amount);
        }

        // 商品类目
        if ( isset($filter['goods_type']) ){
            $where .= ' AND `type_id` = '.$filter['goods_type'];
            unset($filter['goods_type']);
        }
        if(isset($having))
        {
        	return '1'.$where.' GROUP BY shop_id,type_id having 1'.$having;
        }
        else
        {
        	return '1'.$where.' GROUP BY shop_id,type_id ';
        }
    }

    public function modifier_sales_time($sales_time)
    {
        return date('Y-m-d',$sales_time);
    }

    //omeio导出机制
    public function export_params(){
        //获取框架filter信息
        $params = unserialize($_POST['params']);
        $filter['time_from'] = $params['time_from'];
        $filter['time_to'] = $params['time_to'];
        $params = array(
            'filter' => $filter,
            //单文件 
            'single'=> array(
                '1'=> array(
                    //定义返回主体信息方法，提供自定义方法名给method，系统会分页调取主体信息
                    'method' => 'get_export_main',
                    'offset' => 0,
                    'limit' => 4000,
                    //导出文件名
                    'filename' => '类目销售对比统计',
                ),
            ),
        );
        return $params;
    }

    public function get_export_main_title(){
        $title = array(
           'col:来源店铺',
            'col:商品类目',
            'col:销售数量',
            'col:销售额',
        );
        return $title;
    }
    
     //注：$filter是在export_params()方法里获取到的filter,$offset,$limit已做处理，直接带到getList里即可
    public function get_export_main($filter,$offset,$limit,&$data){
        //$cat_sale_statis_model = app::get('omeanalysts')->model('cat_sale_statis');
        $list = $this->getList('*',$filter,$offset,$limit);
        $shopObj = &app::get('ome')->model('shop');
        $gtObj = &app::get('ome')->model('goods_type');
        foreach($list as $v){
            $shop_name = $shopObj->getList('name',array('shop_id'=>$v['shop_id']));
            $goods_type = $gtObj->getList('name',array('type_id'=>$v['type_id']));
            //无需返回值，按 模版字段 组织好一维数组 赋给 $data
            $data[] = array(
                'col:来源店铺'=>$shop_name[0]['name'],
                'col:商品类目' => $goods_type[0]['name'],
                'col:销售数量' => $v['sales_num'],
                'col:销售额' => $v['sales_amount'],
            );
        }
    }

}