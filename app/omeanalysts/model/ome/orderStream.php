<?php
class omeanalysts_mdl_ome_orderStream extends dbeav_model{

    var $has_export_cnf = true;

    var $export_name = '订单流水';

    public function count($filter=null){
       
        $orders_sql ='select count(order_bn) as count from sdb_ome_orders where 1 and '.$this->_newFilter($filter);
		$count = $this->db->select($orders_sql);

       
        return $count[0]['count'];
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
		
		//echo "<pre>";print_r($filter);

        $orders_sql ='select  ax_order_bn,order_id,process_status,order_bn,total_amount,routetime,tax_no,is_tax,itemnum,ship_name,ship_zip,ship_area,is_w_card,pay_bn,message1,message2,message3,message4,message5,message6 from sdb_ome_orders where 1 and '.$this->_newFilter($filter).' ORDER BY createtime DESC';
		//echo "<pre>";print_r($orders_sql);exit;
      
        $sale_datas = $this->db->selectlimit($orders_sql,$limit,$offset); 
		$i=0;
//echo "<pre>";print_r($sale_datas);exit;
		$objLog = app::get('ome')->model('operation_log');

		foreach($sale_datas as $v){
			
			$operation_time = $objLog->getList('operation,operate_time',array('obj_id'=>$v['order_id'],'operation'=>array('order_create@ome','order_confirm@ome')));
			$oms_receiv_time='';
			$ax_receiv_time='';
			foreach($operation_time as $otime){
				if($otime['operation']=='order_create@ome'){
					$oms_receiv_time = $otime['operate_time'];
				}else{
					$ax_receiv_time = $otime['operate_time'];
				}
			}

			$rows[$i]['brand'] = 'DIOR';
			$rows[$i]['ax_order_bn'] = $v['ax_order_bn'];
			$rows[$i]['order_status'] = $v['process_status'];
			$rows[$i]['customer'] = 'C4010P1';
			$rows[$i]['order_no'] = $v['order_bn'];
			$rows[$i]['sales_price'] = $v['total_amount'];
			$rows[$i]['invoice_no'] = $v['tax_no'];
			$rows[$i]['invoice'] = $v['is_tax']=='true'?'YES':'NO';
			
			$rows[$i]['oms_receive_data'] = date('Y-m-d',$oms_receiv_time);
			$rows[$i]['oms_receive_time'] = date('H:i:s',$oms_receiv_time);

			$rows[$i]['ax_receiv_data'] = $ax_receiv_time?date('Y-m-d',$ax_receiv_time):'';
			$rows[$i]['ax_receiving_time'] = $ax_receiv_time?date('H:i:s',$ax_receiv_time):'';
			$rows[$i]['wms_receiv_data'] = '';
			$rows[$i]['wms_receiving_time'] = '';

			$rows[$i]['receiv_data'] = $v['routetime']?date('Y-m-d',$v['routetime']):'';
			$rows[$i]['receiving_time'] = $v['routetime']?date('H:i:s',$v['routetime']):'';
			$rows[$i]['pick_data'] = '';
			$rows[$i]['pick_time'] = '';
			$rows[$i]['delivery_date'] = '';
			$rows[$i]['delivery_time'] = '';
			$rows[$i]['itemNums'] = $v['itemnum'];
			if($v['message1']||$v['message2']||$v['message3']||$v['message4']||$v['message5']||$v['message6']){
				$rows[$i]['itemNums'] =$rows[$i]['itemNums']-1;
			}
			if($v['is_w_card']=='true'){
				$rows[$i]['itemNums'] = $rows[$i]['itemNums']-1;
			}

			$rows[$i]['comments'] = '';
			$rows[$i]['customer_name'] = $v['ship_name'];
			$rows[$i]['coiporate'] = 'Dior Official Website';
			$rows[$i]['zip_code'] = $v['ship_zip'];
			$area = explode(':',$v['ship_area']);
			$area = explode('/',$area[1]);
			$rows[$i]['city'] = $area[1];
			$rows[$i]['addr'] = $area[2];
			$rows[$i]['id'] = '';
			$rows[$i]['so_type'] = 'ECOMM';
			$rows[$i]['order_source'] = 'WEB';
			if($v['message1']||$v['message2']||$v['message3']||$v['message4']||$v['message5']||$v['message6']){
				$rows[$i]['gift'] = 'YES';
			}else{
				$rows[$i]['gift'] = 'NO';
			}
			if($v['pay_bn']=='alipay'){
				$rows[$i]['pay_bn'] = 'ALIPAY';
			}else if($v['pay_bn']=='wxpayjsapi'){
				$rows[$i]['pay_bn'] = 'WECHAT';
			}else{
				$rows[$i]['pay_bn'] = 'COD';
			}
			$rows[$i]['round_no'] = '';

			$i++;
		}
        return $rows;
    }
	
	public function _newFilter($filter){
		if(isset($filter['time_from']) && $filter['time_from']){
            $time_from = ' createtime >='.strtotime($filter['time_from']);
            $where[] = $time_from;
        }

        if(isset($filter['time_to']) && $filter['time_to']){
            $time_to = ' createtime <='.strtotime($filter['time_to'].' 23:59:59');
            $where[] = $time_to;
        }

		
        if(isset($filter['order_no']) && $filter['order_no']){

            $order_bn = ' order_bn = "'.$filter['order_no'].'"';
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
                    'Order status'     => 'order_status',
                    'Customer N.' => 'customer',
                    'OMS Order No.'     => 'order_no',
                    'Sales Price' => 'sales_price',
                    'Invoice No.' => 'invoice_no',
					'Invoice' => 'invoice',
                    'OMS RECEIVE DATE'=>'oms_receive_data',
					'OMS RECEIVE TIME'=>'oms_receive_time',
					'AX RECEIVE DATE'=>'ax_receiv_data',
					'AX RECEIVE TIME'=>'ax_receiving_time',
					'WMS RECEIVE DATE'=>'wms_receiv_data',
					'WMS RECEIVE TIME'=>'wms_receiving_time',
				    'PICKING DATE'=>'pick_data',
					'PICKING TIME'=>'pick_time',
					'WH DISPATCH DATE'=>'delivery_date',
					'WH DISPATCH TIME'=>'delivery_time',
					'SF DELIVERY DATE'=>'receiv_data',
					'SF DELIVERY TIME'=>'receiving_time',
					'Nb of lines' => 'itemNums',
					'Comments' => 'comments',
					'Customer name' => 'customer_name',
					'Corporate name' => 'coiporate',
					'ZIP Code' => 'zip_code',
					'City' => 'city',
					'Addr. 1' => 'addr',
					'Id' => 'id',
					'SO Type' => 'so_type',
					'Order Orig.' => 'order_source',
					'Gift' => 'gift',
					'COD' => 'pay_bn',
					'Round No.' => 'round_no',
                );
            break;
        }
        $this->ioTitle[$ioType] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType];
    }
    
    public function export_csv($data){
        $output = array();
        $output[] = $data['title']['orderstream']."\n".implode("\n",(array)$data['content']['orderstream']);
        echo implode("\n",$output);
    }

    public function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){

        @ini_set('memory_limit','64M');

        if( !$data['title']['orderstream']){
            $title = array();
            foreach( $this->io_title('csv') as $k => $v ){
                $title[] = $v;
            }
            $data['title']['orderstream'] = mb_convert_encoding('"'.implode('","',$title).'"', 'GBK', 'UTF-8');
        }

        $limit = 100;
        
        if( !$list=$this->getlist('*',$filter,$offset*$limit,$limit) ) return false;
		$order_status = array(
				'unconfirmed'=>'unconfirmed',
				'splited'=>'Sent To AX',
				'cancel'=>'Canceled',
				'confirmed'=>'confirmed',
			);

        foreach( $list as $aFilter ){

            $branchdeliveryRow['Brand'] = $aFilter['brand'];
            $branchdeliveryRow['Order No.'] = '';
            $branchdeliveryRow['Order status']     = $order_status[$aFilter['order_status']];

            $branchdeliveryRow['Customer N.'] = $aFilter['customer'];
            $branchdeliveryRow['OMS Order No.']     = $aFilter['order_no'];
            $branchdeliveryRow['Sales Price'] = $aFilter['sales_price'];
            $branchdeliveryRow['Invoice No.'] = $aFilter['invoice_no'];
			$branchdeliveryRow['Invoice'] = $aFilter['invoice'];

            $branchdeliveryRow['OMS RECEIVE DATE'] = $aFilter['oms_receive_data'];
            $branchdeliveryRow['OMS RECEIVE TIME'] = $aFilter['oms_receive_time'];
            $branchdeliveryRow['AX RECEIVE DATE'] = $aFilter['ax_receiv_data'];
            $branchdeliveryRow['AX RECEIVE TIME'] = $aFilter['ax_receiving_time'];

			$branchdeliveryRow['WMS RECEIVE DATE'] = '';
			$branchdeliveryRow['WMS RECEIVE TIME'] = '';
			$branchdeliveryRow['PICKING DATE'] = $aFilter['pick_data'];
            $branchdeliveryRow['PICKING TIME'] = $aFilter['pick_time'];
            $branchdeliveryRow['WH DISPATCH DATE'] = '';
            $branchdeliveryRow['WH DISPATCH TIME'] = '';

			$branchdeliveryRow['SF DELIVERY DATE'] = $aFilter['receiv_data'];
			$branchdeliveryRow['SF DELIVERY TIME'] = $aFilter['receiving_time'];

			$branchdeliveryRow['Nb of lines'] = $aFilter['itemNums'];
			$branchdeliveryRow['Comments'] = $aFilter['comments'];
			$branchdeliveryRow['Customer name'] = $aFilter['customer_name'];
			$branchdeliveryRow['Corporate name'] = $aFilter['coiporate'];
			$branchdeliveryRow['ZIP Code'] = '';
			$branchdeliveryRow['City'] = $aFilter['city'];
			$branchdeliveryRow['Addr. 1'] = $aFilter['addr'];
			$branchdeliveryRow['Id'] = $aFilter['id'];
			$branchdeliveryRow['SO Type'] = $aFilter['so_type'];
			$branchdeliveryRow['Order Orig.'] = $aFilter['order_source'];
			$branchdeliveryRow['Gift'] = $aFilter['gift'];
			$branchdeliveryRow['COD'] = $aFilter['pay_bn'];
			$branchdeliveryRow['Round No.'] = $aFilter['round_no'];


            $data['content']['orderstream'][] = mb_convert_encoding('"'.implode('","',$branchdeliveryRow).'"', 'GBK', 'UTF-8');
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
                'order_status' =>
                array(
                    'type' => array(
						'unconfirmed'=>'unconfirmed',
						'splited'=>'Sent To AX',
						'cancel'=>'Canceled',
						'confirmed'=>'confirmed',
					),
                    'label' => 'Order status',
                    'width' => 120,
                    'order' => 3,
                    'orderby' => false,
                ),
                'customer' =>
                array(
                    'type' => 'varchar(30)',
                    'label' => 'Customer N.',
                    'width' => 100,
                    'order' => 4,
					'orderby' => false,
                ),
                'order_no' =>
                array(
                    'type' => 'varchar(32)',
                    'label' => 'OMS Order No.',
                    'width' => 120,
                    'order' => 5,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'searchtype' => 'has',
            '		orderby' => false,
                ),
                'sales_price' =>
                array(
                    'type' => 'money',
                    'label' => 'Sales Price',
                    'width' => 90,
                    'order' => 6,
					'orderby' => false,
                ),
                'invoice_no'=>
                array(
                    'type' => 'varchar(50)',
                    'label' => 'Invoice No.',
                    'width' => 120,
                    'order' => 7,
					'orderby' => false,
                ),
				'invoice'=>
                array(
                    'type' => 'varchar(10)',
                    'label' => 'Invoice',
                    'width' => 120,
                    'order' => 7,
					'orderby' => false,
                ),
                'oms_receive_data' =>
                array(
                  'type' => 'varchar(30)',
                  'label' => 'OMS RECEIVE DATE',
                  'width' => 130,
                  'order' => 8,   
                ),                               
                'oms_receive_time' =>
                array(
                  'type' => 'number',
                  'label' => 'OMS RECEIVE TIME',
                  'width' => 130,
                  'order' => 9,    
				  'orderby' => false,
                ),
				
				'ax_receiv_data' =>
                array(
                  'type' => 'varchar(30)',
                  'label' => 'AX RECEIVE DATE',
                  'width' => 130,
                  'order' => 10,   
                ),                               
                'ax_receiving_time' =>
                array(
                  'type' => 'number',
                  'label' => 'AX RECEIVE TIME',
                  'width' => 130,
                  'order' => 10,    
				  'orderby' => false,
                ),

				'wms_receiv_data' =>
                array(
                  'type' => 'varchar(30)',
                  'label' => 'WMS RECEIVE DATE',
                  'width' => 130,
                  'order' => 11,   
                ),                               
                'wms_receiving_time' =>
                array(
                  'type' => 'number',
                  'label' => 'WMS RECEIVE TIME',
                  'width' => 130,
                  'order' => 11,    
				  'orderby' => false,
                ),
				
				'pick_data' =>
                array(
                  'type' => 'varchar(30)',
                  'label' => 'PICKING DATE',
                  'width' => 130,
                  'order' => 12,   
                ),                               
                'pick_time' =>
                array(
                  'type' => 'number',
                  'label' => 'PICKING TIME',
                  'width' => 130,
                  'order' => 12,    
				  'orderby' => false,
                ),

				'delivery_date' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'WH DISPATCH DATE',
                  'width' => 130,
                  'order' => 13,
				  'orderby' => false,
                ),
				'delivery_time' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'WH DISPATCH TIME',
                  'width' => 130,
                  'order' => 13,
				  'orderby' => false,
                ),

				'receiv_data' =>
                array(
                  'type' => 'varchar(30)',
                  'label' => 'SF DELIVERY DATE',
                  'width' => 130,
                  'order' => 14,   
                ),                               
                'receiving_time' =>
                array(
                  'type' => 'number',
                  'label' => 'SF DELIVERY TIME',
                  'width' => 130,
                  'order' => 14,    
				  'orderby' => false,
                ),
				
				
				
				'itemNums' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Nb of lines',
                  'width' => 40,
                  'order' => 14,
				  'orderby' => false,
                ),
				'comments' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Comments',
                  'width' => 80,
                  'order' => 15,
				  'orderby' => false,
                ),
				'customer_name' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Customer name',
                  'width' => 100,
                  'order' => 16,
				  'orderby' => false,
                ),
				'coiporate' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Corporate name',
                  'width' => 120,
                  'order' => 17,
				  'orderby' => false,
                ),
				'zip_code' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'ZIP Code',
                  'width' => 120,
                  'order' => 18,
				  'orderby' => false,
                ),
				'city' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'City',
                  'width' => 80,
                  'order' => 19,
				  'orderby' => false,
                ),
				'addr' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Addr. 1',
                  'width' => 150,
                  'order' => 20,
				  'orderby' => false,
                ),
				'id' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Id',
                  'width' => 40,
                  'order' => 21,
				  'orderby' => false,
                ),
				'so_type' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'SO Type',
                  'width' => 80,
                  'order' => 22,
				  'orderby' => false,
                ),

				'order_source' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Order Orig.',
                  'width' => 40,
                  'order' => 23,
				  'orderby' => false,
                ),
				'gift' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Gift',
                  'width' => 40,
                  'order' => 24,
				  'orderby' => false,
                ),
				'pay_bn' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'COD',
                  'width' => 80,
                  'order' => 25,
				  'orderby' => false,
                ),
				'round_no' =>
                array(
                  'type'  => 'varchar(50)',
                  'label' => 'Round No.',
                  'width' => 40,
                  'order' => 26,
				  'orderby' => false,
                ),
            ),
            'in_list' => array(
                0 => 'brand',
                1 => 'ax_order_no',
                2 => 'order_status',
                3 => 'customer',
                4 => 'order_no',
                5 => 'sales_price',
                6 => 'invoice_no',        
                7 => 'receiv_data',
                8 => 'receiving_time',
                9 => 'pick_data',
                10 => 'pick_time',
				11=>'delivery_date',
				12=>'delivery_time',
				13=>'itemNums',
				14=>'comments',
				15=>'customer_name',
				16=>'coiporate',
				17=>'zip_code',
				18=>'city',
				19=>'addr',
				20=>'id',
				21=>'so_type',
				22=>'order_source',
				23=>'gift',
				24=>'pay_bn',
				25=>'round_no',
				26=>'invoice',
				27 => 'oms_receive_data',
                28 => 'oms_receive_time',
				29 => 'ax_receiv_data',
                30 => 'ax_receiving_time',
				31 => 'wms_receiv_data',
                32 => 'wms_receiving_time',

            ),
            'default_in_list' => array(
                0 => 'brand',
                1 => 'ax_order_no',
                2 => 'order_status',
                3 => 'customer',
                4 => 'order_no',
                5 => 'sales_price',
                6 => 'invoice_no',        
                7 => 'receiv_data',
                8 => 'receiving_time',
                9 => 'pick_data',
                10 => 'pick_time',
				11=>'delivery_date',
				12=>'delivery_time',
				13=>'itemNums',
				14=>'comments',
				15=>'customer_name',
				16=>'coiporate',
				17=>'zip_code',
				18=>'city',
				19=>'addr',
				20=>'id',
				21=>'so_type',
				22=>'order_source',
				23=>'gift',
				24=>'pay_bn',
				25=>'round_no',
				26=>'invoice',
				27 => 'oms_receive_data',
                28 => 'oms_receive_time',
				29 => 'ax_receiv_data',
                30 => 'ax_receiving_time',
				31 => 'wms_receiv_data',
                32 => 'wms_receiving_time',
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