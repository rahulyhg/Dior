<?php
class omeanalysts_mdl_ome_orderSteamItems extends dbeav_model{

    var $has_export_cnf = true;

    var $export_name = '订单明细';

    public function count($filter=null){
		$orders_sql ='SELECT count(o.order_id) as count from  sdb_ome_order_items as i LEFT JOIN sdb_ome_orders as o ON i.order_id=o.order_id WHERE 1 and '.$this->_newFilter($filter);
		$count = $this->db->select($orders_sql);

        return $count[0]['count'];
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
		
        $orders_sql ='SELECT o.order_id,o.ax_order_bn,o.order_bn,i.bn,i.name,i.nums,o.ship_name,o.ship_area,o.logi_no from  sdb_ome_order_items as i LEFT JOIN sdb_ome_orders as o ON i.order_id=o.order_id WHERE 1 and '.$this->_newFilter($filter).' ORDER BY o.createtime DESC';
      
        $sale_datas = $this->db->selectlimit($orders_sql,$limit,$offset); 
		$i=0;

		foreach($sale_datas as $v){
			$rows[$i]['brand'] = 'DIOR';
			$rows[$i]['ax_order_no'] = $v['ax_order_bn'];
			$rows[$i]['order_no'] = $v['order_bn'];
			$rows[$i]['so_type'] = 'ECOMM';

			$coun[$v['order_id']][] = $v['bn'];

			$rows[$i]['line'] = '';

			$rows[$i]['sku'] = $v['bn'];
			$rows[$i]['name'] = $v['name'];

			$rows[$i]['nums'] = $v['nums'];
			$rows[$i]['item_group'] = '';
			$rows[$i]['customer'] = 'C4010P1';
			$rows[$i]['ship_name'] = $v['ship_name'];
			$area = explode(':',$v['ship_area']);
			$area = explode('/',$area[1]);

			$rows[$i]['city'] = $area[1];
			$rows[$i]['logi_no'] = $v['logi_no'];

			$i++;
		}
        return $rows;
    }
	
	public function _newFilter($filter){
		if(isset($filter['time_from']) && $filter['time_from']){
            $time_from = ' o.createtime >='.strtotime($filter['time_from']);
            $where[] = $time_from;
        }

        if(isset($filter['time_to']) && $filter['time_to']){
            $time_to = ' o.createtime <='.strtotime($filter['time_to'].' 23:59:59');
            $where[] = $time_to;
        }

		
        if(isset($filter['order_no']) && $filter['order_no']){

            $order_bn = ' o.order_bn = "'.$filter['order_no'].'"';
            $where[] = $order_bn;
        }

		return implode(' AND ',$where);
	}

    public function io_title( $ioType='csv' ){
    
        switch( $ioType ){
            case 'csv':
                $this->oSchema['csv']['main'] = array(
                    'Brand' => 'brand', 
                    'Order No.' => 'ax_order_no',
                    'OMS Order No.'     => 'order_no',
                    'SO Type' => 'so_type',
                    'Line'     => 'line',
                    'SKU' => 'sku',
                    'Description' => 'name',
                    'Picking qty' => 'nums',
                    'Item Group' => 'item_group',
                    'Customer N.' => 'customer',
                    'Person' => 'ship_name',
					'City' => 'city',
					'Tracking No' => 'logi_no',

                );
            break;
        }
        $this->ioTitle[$ioType] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType];
    }
    
    public function export_csv($data){
        $output = array();
        $output[] = $data['title']['orderstreamItems']."\n".implode("\n",(array)$data['content']['orderstreamItems']);
        echo implode("\n",$output);
    }

    public function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){

        @ini_set('memory_limit','64M');

        if( !$data['title']['orderstreamItems']){
            $title = array();
            foreach( $this->io_title('csv') as $k => $v ){
                $title[] = $v;
            }
            $data['title']['orderstreamItems'] = mb_convert_encoding('"'.implode('","',$title).'"', 'GBK', 'UTF-8');
        }

        $limit = 100;
        
        if( !$list=$this->getlist('*',$filter,$offset*$limit,$limit) ) return false;


        foreach( $list as $aFilter ){

            $branchdeliveryRow['Brand'] = $aFilter['brand'];
            $branchdeliveryRow['Order No.'] = '';
            $branchdeliveryRow['OMS Order No.']     = $aFilter['order_no'];
            $branchdeliveryRow['SO Type'] = $aFilter['so_type'];
            $branchdeliveryRow['Line']     = '';
            $branchdeliveryRow['SKU'] = $aFilter['sku'];
            $branchdeliveryRow['Description'] = $aFilter['name'];
            $branchdeliveryRow['Picking qty'] = $aFilter['nums'];
            $branchdeliveryRow['Item Group'] = $aFilter['item_group'];
            $branchdeliveryRow['Customer N.'] = $aFilter['customer'];
            $branchdeliveryRow['Person'] = $aFilter['ship_name'];

			$branchdeliveryRow['City'] = $aFilter['city'];
			$branchdeliveryRow['Tracking No'] = $aFilter['logi_no'];

            $data['content']['orderstreamItems'][] = mb_convert_encoding('"'.implode('","',$branchdeliveryRow).'"', 'GBK', 'UTF-8');

        }

        $data['name'] = $this->export_name.date("YmdHis");

        return true;
    }

    public function exportName(&$data){
        $data['name'] = $_POST['time_from'].'到'.$_POST['time_to'].$this->export_name;
    }


    public function get_schema(){

        $schema = array (
            'columns' => array ( 
                'brand' =>
                array(
                  'type' => 'varchar(50)',
                  'editable' => false,
                  'label'=>'Brand',
				  'width' => 60,
                  'order' => 1,
                ),
                'ax_order_no' =>
                array(
                    'type' => 'varchar(100)',
                    'label' => 'Order No.',
                    'width' => 120,
                    'order' => 2,
                    'orderby' => false,
                ),
               
                'order_no' =>
                array(
                    'type' => 'varchar(32)',
                    'label' => 'OMS Order No.',
                    'width' => 120,
                    'order' => 3,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'searchtype' => 'has',
            '		orderby' => false,
                ),
                'so_type' =>
                array(
                    'type' => 'varchar(50)',
                    'label' => 'SO Type',
                    'width' => 90,
                    'order' => 4,
					'orderby' => false,
                ),
                'line'=>
                array(
                    'type' => 'varchar(50)',
                    'label' => 'Line',
                    'width' => 50,
                    'order' => 5,
					'orderby' => false,
                ),
                'sku' =>
                array(
                  'type' => 'varchar(30)',
                  'label' => 'SKU',
                  'width' => 100,
                  'order' => 6,   
                ),                               
                'name' =>
                array(
                  'type' => 'number',
                  'label' => 'Description',
                  'width' => 180,
                  'order' => 7,    
				  'orderby' => false,
                ),
                'nums' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Picking qty',
                  'width' => 60,
                  'order' => 8,
				  'orderby' => false,
                ),
                'item_group' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Item Group',
                  'width' => 40,
                  'order' => 9,
				  'orderby' => false,
                ),
				'customer' =>
                array(
                    'type' => 'varchar(30)',
                    'label' => 'Customer N.',
                    'width' => 100,
                    'order' => 10,
					'orderby' => false,
                ),
				'ship_name' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Person',
                  'width' => 80,
                  'order' => 11,
				  'orderby' => false,
                ),
				'city' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'city',
                  'width' => 150,
                  'order' => 12,
				  'orderby' => false,
                ),
				'logi_no' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Tracking No',
                  'width' => 100,
                  'order' => 13,
				  'orderby' => false,
                ),
            ),
            'in_list' => array(
                0 => 'brand',
                1 => 'ax_order_no',
                2 => 'order_no',
                3 => 'so_type',
                4 => 'line',
                5 => 'sku',
                6 => 'name',        
                7 => 'nums',
                8 => 'item_group',
                9 => 'customer',
                10 => 'ship_name',
				11=>'city',
				12=>'logi_no',
            ),
            'default_in_list' => array(
                0 => 'brand',
                1 => 'ax_order_no',
                2 => 'order_no',
                3 => 'so_type',
                4 => 'line',
                5 => 'sku',
                6 => 'name',        
                7 => 'nums',
                8 => 'item_group',
                9 => 'customer',
                10 => 'ship_name',
				11=>'city',
				12=>'logi_no',
            ),
        );
        return $schema;
    }

    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams) {
        $type = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        }
        elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams) {
        $params = $logParams['params'];
        $type = 'report';
        if ($logParams['app'] == 'omeanalysts' && $logParams['ctl'] == 'ome_analysis') {
            $type .= '_purchaseReport_branchDeliveryAnalysis';
        }
        $type .= '_export';
        return $type;
    }
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'report';
        if ($logParams['app'] == 'omeanalysts' && $logParams['ctl'] == 'ome_analysis') {
            $type .= '_purchaseReport_branchDeliveryAnalysis';
        }
        $type .= '_import';
        return $type;
    }

    //根据查询条件获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end){

        //根据选择的字段定义导出的第一行标题
        if($curr_sheet == 1){
            $data['content']['main'][] = $this->getExportTitle($fields);
        }

        if( !$list=$this->getlist('*',$filter,$start,$end) ) return false;
        
        $branchdeliveryRow = array();
        $Oshop = app::get('ome')->model('shop');
        $shops = $Oshop->getList('name,shop_id');
        foreach ($shops as $v) {
            $shop[$v['shop_id']] = $v['name'];
        }
        unset($shops);

        $Obranch = &app::get('ome')->model('branch');
        $branchs = $Obranch->getList('branch_id,name',array('is_deliv_branch'=>'true'));
        foreach ($branchs as $v) {
            $branch[$v['branch_id']] = $v['name'];
        }
        unset($branchs);

        foreach( $list as $aFilter ){
            $branchdeliveryRow['branch_name'] = $branch[$aFilter['branch_name']];
            $branchdeliveryRow['goods_type'] = $aFilter['goods_type'];
            $branchdeliveryRow['brand_name']     = $aFilter['brand_name'];
            $branchdeliveryRow['goods_bn'] = $aFilter['goods_bn'];
            $branchdeliveryRow['product_bn']     = $aFilter['product_bn'];
            $branchdeliveryRow['product_name'] = $aFilter['product_name'];
            $branchdeliveryRow['goods_specinfo'] = $aFilter['goods_specinfo'];
            $branchdeliveryRow['sale_num'] = $aFilter['sale_num'];
            $branchdeliveryRow['aftersale_num'] = $aFilter['aftersale_num'];
            $branchdeliveryRow['shop_id'] = $shop[$aFilter['shop_id']];
            $branchdeliveryRow['total_nums'] = $aFilter['total_nums'];

            $exptmp_data = array();
            foreach (explode(',', $fields) as $key => $col) {
                if(isset($branchdeliveryRow[$col])){
                    //过滤地址里的特殊字符
                    $branchdeliveryRow[$col] = str_replace('&nbsp;', '', $branchdeliveryRow[$col]);
                    $branchdeliveryRow[$col] = str_replace(array("\r\n","\r","\n"), '', $branchdeliveryRow[$col]);
                    $branchdeliveryRow[$col] = str_replace(',', '', $branchdeliveryRow[$col]);

                    $branchdeliveryRow[$col] = mb_convert_encoding($branchdeliveryRow[$col], 'GBK', 'UTF-8');
                    $exptmp_data[] = $branchdeliveryRow[$col];
                }
            }

            $data['content']['main'][] = implode(',', $exptmp_data);
        }

        return $data;
    }
}