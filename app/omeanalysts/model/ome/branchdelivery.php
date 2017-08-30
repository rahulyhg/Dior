<?php
class omeanalysts_mdl_ome_branchdelivery extends dbeav_model{

    var $has_export_cnf = true;

    var $export_name = '仓库发货情况汇总';

    public function get_count($filter=null){
        
        #$sales_sql = 'select sum(SI.nums) as total_sales from sdb_ome_sales_items SI left join sdb_ome_sales S on SI.sale_id = S.sale_id where '.$this->_sfilter($filter);
        $sales_sql ='
         select
              sum(items.number) as total_sales
         from sdb_ome_delivery delivery
         left join sdb_ome_delivery_items items  on delivery.delivery_id = items.delivery_id
         left join sdb_ome_products p on p.product_id = items.product_id
         left join sdb_ome_goods g on g.goods_id = p.goods_id
         where '.$this->_newFilter($filter);
        $salesdata = $this->db->select($sales_sql);

        $aftersale_sql = 'select sum(AI.num) as total_aftersales from sdb_sales_aftersale_items AI left join sdb_sales_aftersale A on AI.aftersale_id = A.aftersale_id where '.$this->_rfilter($filter);

        $aftersaledata = $this->db->select($aftersale_sql);

        return array(
            'total_sales' => $salesdata[0]['total_sales']?$salesdata[0]['total_sales']:0,
            'total_aftersales' => $aftersaledata[0]['total_aftersales']?$aftersaledata[0]['total_aftersales']:0,
        );
    }

    public function count($filter=null){
       
        return count($this->getList('*',$filter));
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){

        #$sale_sql = 'select SI.bn,SI.product_id,SI.name,sum(SI.nums) as nums,SI.branch_id,S.shop_id from sdb_ome_sales_items SI left join sdb_ome_sales S on SI.sale_id = S.sale_id where '.$this->_sfilter($filter).' group by SI.bn,SI.branch_id ';
        $sale_sql ='
         select
              items.bn,p.name name,delivery.branch_id,delivery.shop_id,sum(items.number) nums
         from sdb_ome_delivery delivery
         left join sdb_ome_delivery_items items  on delivery.delivery_id = items.delivery_id
         left join sdb_ome_products p on p.product_id = items.product_id
         left join sdb_ome_goods g on g.goods_id = p.goods_id
         where '.$this->_newFilter($filter).' group by items.bn, delivery.shop_id order by null';
        $orderType = preg_replace('/branch_name/', 'SI.branch_id', $orderType);
        $orderType = preg_replace('/sale_num/', 'SI.nums', $orderType);
        if($orderType) $sale_sql .= ' order by '.(is_array($orderType)?implode($orderType,' '):$orderType);
      
        $sale_datas = $this->db->selectlimit($sale_sql,$limit,$offset);
        
        $rows = $rowdatas = array();
        
        $bns = array();

        foreach($sale_datas as $v){
            $bns[] = $v['bn'];
        }

        $sql = 'select p.bn,p.spec_info,p.barcode as goods_bn,gt.name as goods_type,b.brand_name from sdb_ome_products p left join sdb_ome_goods g on p.goods_id = g.goods_id left join sdb_ome_goods_type gt on g.type_id = gt.type_id left join sdb_ome_brand b on g.brand_id = b.brand_id where p.bn in ("'.implode('","',$bns).'")';
  
        $get_bns = $this->db->select($sql);
   
        foreach($get_bns as $v){
            $product_info[$v['bn']] = $v;
            $all_bns[] = $v['bn'];
        }
        
        #goods_type brand_name goods_bn goods_specinfo
        foreach ($sale_datas as $k => $v) {

            $md_key =  md5($v['shop_id'].'-'.$v['branch_id'].'-'.$v['bn']).'sale';

            $this->get_productinfo($v['bn'],$all_bns,$product_info,$rowdatas[$md_key]);

            $rowdatas[$md_key]['branch_name']  = $v['branch_id'];
            $rowdatas[$md_key]['product_bn']   = $v['bn'];
            $rowdatas[$md_key]['product_name'] = $v['name'];
            $rowdatas[$md_key]['sale_num']     = $v['nums'];
            $rowdatas[$md_key]['shop_id']      = $v['shop_id'];
        }

        unset($sale_datas);


        $aftersale_sql = 'select AI.bn,AI.product_id,AI.product_name,sum(AI.num) as num,AI.branch_id,A.shop_id from sdb_sales_aftersale_items AI left join sdb_sales_aftersale A on AI.aftersale_id =  A.aftersale_id where '.$this->_rfilter($filter).' group by AI.bn,AI.branch_id order by null ';
        
        if($orderType) $aftersale_sql .= ' order by '.(is_array($orderType)?implode($orderType,' '):$orderType);

        $aftersale_datas = $this->db->selectlimit($aftersale_sql,$limit,$offset);

        $bns = array();

        foreach($aftersale_datas as $v){
            $bns[] = $v['bn'];
        }

        $sql = 'select p.bn,p.spec_info,p.barcode as goods_bn,gt.name as goods_type,b.brand_name from sdb_ome_products p left join sdb_ome_goods g on p.goods_id = g.goods_id left join sdb_ome_goods_type gt on g.type_id = gt.type_id left join sdb_ome_brand b on g.brand_id = b.brand_id where p.bn in ("'.implode('","',$bns).'")';
  
        $get_bns = $this->db->select($sql);
   
        foreach($get_bns as $v){
            $product_info[$v['bn']] = $v;
            $all_bns[] = $v['bn'];
        }

        foreach ($aftersale_datas as $k => $v) {

            $md_key =  md5($v['shop_id'].'-'.$v['branch_id'].'-'.$v['bn']).'aftersale';
            
            $this->get_productinfo($v['bn'],$all_bns,$product_info,$rowdatas[$md_key]);

            $rowdatas[$md_key]['branch_name']   = $v['branch_id'];
            $rowdatas[$md_key]['product_bn']    = $v['bn'];
            $rowdatas[$md_key]['product_name']  = $v['product_name'];
            $rowdatas[$md_key]['aftersale_num'] = $v['num'];
            $rowdatas[$md_key]['shop_id']       = $v['shop_id'];        
        }

        unset($aftersale_datas);
        
        $i = 0;

        foreach ($rowdatas as $v) {

            $rows[$i]['branch_name']     = $v['branch_name']?$v['branch_name']:'-';
            $rows[$i]['goods_type']      = $v['goods_type']?$v['goods_type']:'-';
            $rows[$i]['brand_name']      = $v['brand_name']?$v['brand_name']:'-';
            $rows[$i]['goods_bn']        = $v['goods_bn']?$v['goods_bn']:'-';
            $rows[$i]['goods_specinfo']  = $v['goods_specinfo']?$v['goods_specinfo']:'-';
            $rows[$i]['product_bn']      = $v['product_bn']?$v['product_bn']:'-';
            $rows[$i]['product_name']    = $v['product_name']?$v['product_name']:'-';
            $rows[$i]['sale_num']        = $v['sale_num']?$v['sale_num']:0;
            $rows[$i]['aftersale_num']   = $v['aftersale_num']?$v['aftersale_num']:0;
            $rows[$i]['shop_id']         = $v['shop_id']?$v['shop_id']:'-'; 
            $rows[$i]['total_nums']      = $v['sale_num'] - $v['aftersale_num'];
            $i++;
        }
        
        return $rows;
    }
    public function _newFilter($filter){ 
        #$where = array();
        #已发货的基础过滤条件
        $where[] = ' delivery.status=\'succ\'';
        $where[] = 'delivery.type=\'normal\'';
        $where[] = 'delivery.pause=\'FALSE\'';
        $where[] = 'delivery.parent_id=\'0\'';
        $where[] =' delivery.disabled=\'false\''; 
        
        #店铺
        if(isset($filter['shop_id']) && $filter['shop_id']){
            $where[] = ' delivery.shop_id =\''.addslashes($filter['shop_id']).'\'';
        }

        if(isset($filter['own_branches']) && $filter['own_branches']){
            $where[]= ' delivery.branch_id in ('.implode(',',$filter['own_branches']).')';
        }
        unset($filter['own_branches']);

        #仓库
        if(isset($filter['branch_id']) && $filter['branch_id']){
            $where[] = ' delivery.branch_id = '.addslashes($filter['branch_id']);
        }
        #货号       
        if(isset($filter['product_bn']) && $filter['product_bn']){
            $where[] = ' items.bn =\''.addslashes($filter['product_bn']).'\'';
        } 
        #时间
        if(isset($filter['time_from']) && $filter['time_from']){
            $time_from = ' delivery.delivery_time >='.strtotime($filter['time_from']);
            $where[] = $time_from;
        }
        if(isset($filter['time_to']) && $filter['time_to']){
            $time_to = ' delivery.delivery_time <='.strtotime($filter['time_to'].' 23:59:59');
            $where[] = $time_to;
        }
        #品牌
        if(isset($filter['brand_id']) && $filter['brand_id']){
            $where[]= '  g.brand_id = '.$filter['brand_id'];
        }
        #类型
        if(isset($filter['goods_type_id']) && $filter['goods_type_id']){
            $where[]= '  g.type_id = '.$filter['goods_type_id'];
        } 
        return  implode($where,' AND ');
    }

    public function _sfilter($filter){
        
        $where = array();

        #店铺
        if(isset($filter['shop_id']) && $filter['shop_id']){
            $where[] = ' S.shop_id =\''.addslashes($filter['shop_id']).'\'';
        }
        
        if(isset($filter['branch_id']) && $filter['branch_id']){
            $where[] = ' S.branch_id = '.addslashes($filter['branch_id']);
        }

        if(isset($filter['product_bn']) && $filter['product_bn']){
            $where[] = ' SI.bn =\''.addslashes($filter['product_bn']).'\'';
        }

        if(isset($filter['time_from']) && $filter['time_from']){
            $time_from = ' S.sale_time >='.strtotime($filter['time_from']);
            $where[] = $time_from;
            $ftime = $time_from;
        }

        if(isset($filter['time_to']) && $filter['time_to']){

            $time_to = ' S.sale_time <='.strtotime($filter['time_to'].' 23:59:59');
            $where[] = $time_to;
            $ftime .= ' AND '.$time_to;
        }
        

        $_where = '1';
        $filter_sql = false;

        if(isset($filter['brand_id']) && $filter['brand_id']){
            $_where .= ' and g.brand_id = '.$filter['brand_id'];
            $filter_sql = true;
        }

        if(isset($filter['goods_type_id']) && $filter['goods_type_id']){
            $_where .= ' and g.brand_id = '.$filter['brand_id'];
            $filter_sql = true;
        }
        
        if($filter_sql){
            $sql = "select si.bn from sdb_ome_sales_items si 
                left join sdb_ome_sales s on si.sale_id = s.sale_id
                left join sdb_ome_products p on si.bn = p.bn
                left join sdb_ome_goods g on p.goods_id = g.goods_id 
                where ".$_where." and s.sale_time >=".strtotime($filter['time_from'])." and s.sale_time <=".strtotime($filter['time_to'].' 23:59:59');
            $query = $this->db->select($sql);

            if($query){
                foreach($query as $qu){
                    $sale_bns[] = "'".$qu['bn']."'";
                }
                $where[] = " SI.bn IN (".implode(',',$sale_bns).")";
            }else{
                $where[] = " 1=0 ";
            }

        }


        return implode($where,' AND ');
    }

    
    public function _rfilter($filter){
        
        $where = array();

        #店铺
        if(isset($filter['shop_id']) && $filter['shop_id']){
            $where[] = ' A.shop_id =\''.addslashes($filter['shop_id']).'\'';
        }
        
        if(isset($filter['own_branches']) && $filter['own_branches']){
            $where[]= ' AI.branch_id in ('.implode(',',$filter['own_branches']).')';
        }
        unset($filter['own_branches']);

        if(isset($filter['branch_id']) && $filter['branch_id']){
            $where[] = ' AI.branch_id = '.addslashes($filter['branch_id']);
        }

        if(isset($filter['product_bn']) && $filter['product_bn']){
            $where[] = ' AI.bn =\''.addslashes($filter['product_bn']).'\'';
        }

        if(isset($filter['time_from']) && $filter['time_from']){
            $time_from = ' A.aftersale_time >='.strtotime($filter['time_from']);
            $where[] = $time_from;
            $ftime = $time_from;
        }

        if(isset($filter['time_to']) && $filter['time_to']){

            $time_to = ' A.aftersale_time <='.strtotime($filter['time_to'].' 23:59:59');
            $where[] = $time_to;
            $ftime .= ' AND '.$time_to;
        }

        $_where = '1';
        $filter_sql = false;

        if(isset($filter['brand_id']) && $filter['brand_id']){
            $_where .= ' and g.brand_id = '.$filter['brand_id'];
            $filter_sql = true;
        }

        if(isset($filter['goods_type_id']) && $filter['goods_type_id']){
            $_where .= ' and g.type_id = '.$filter['goods_type_id'];
            $filter_sql = true;
        }
        
        if($filter_sql){
            $sql = "select AI.bn from sdb_sales_aftersale_items AI 
                left join sdb_sales_aftersale A on AI.aftersale_id = A.aftersale_id
                left join sdb_ome_products p on AI.bn = p.bn
                left join sdb_ome_goods g on p.goods_id = g.goods_id 
                where ".$_where." and A.aftersale_time >=".strtotime($filter['time_from'])." and A.aftersale_time <=".strtotime($filter['time_to'].' 23:59:59');
            $query = $this->db->select($sql);

            if($query){
                foreach($query as $qu){
                    $afersale_bns[] = "'".$qu['bn']."'";
                }
                $where[] = " AI.bn IN (".implode(',',$afersale_bns).")";
            }else{
                $where[] = " 1=0 ";
            }

        }

        $where[] = 'AI.return_type = "return"';

        return implode($where,' AND ');
    } 

    private function get_productinfo($bn,$all_bns,$product_info,&$data){

        if(in_array($bn,$all_bns)){
            $data['goods_type'] = $product_info[$bn]['goods_type'];
            $data['brand_name'] = $product_info[$bn]['brand_name'];
            $data['goods_specinfo'] = $product_info[$bn]['spec_info'];
            $data['goods_bn'] = $product_info[$bn]['goods_bn'];
        }else{
            $oPkg = kernel::single('omepkg_ome_product');
            $pkg_info = $oPkg->getProductByBn($bn);
            if(!empty($pkg_info)){
                $data['goods_specinfo'] = '-';
                $data['goods_type'] = '捆绑商品';
                $data['brand_name'] = '-';
                $data['goods_bn'] = '-';
            }

            if(!$pkg_info || empty($pkg_info)){
                $data['goods_specinfo'] = '-';
                $data['goods_type'] = '系统不存在此货号';
                $data['brand_name'] = '-';
                $data['goods_bn'] = '-';
            }

        }

    }

    public function io_title( $ioType='csv' ){
    
        switch( $ioType ){
            case 'csv':
                $this->oSchema['csv']['main'] = array(
                    '*:发货仓库' => 'branch_name', 
                    '*:商品类型' => 'goods_type',
                    '*:品牌'     => 'brand_name',
                    '*:商品编码' => 'goods_bn',
                    '*:货号'     => 'product_bn',
                    '*:货品名称' => 'product_name',
                    '*:商品规格' => 'goods_specinfo',
                    '*:销售数量' => 'sale_num',
                    '*:退货数量' => 'aftersale_num',
                    '*:店铺名称' => 'shop_id',
                    '*:合计数量' => 'total_nums',
                );
            break;
        }
        $this->ioTitle[$ioType] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType];
    }
    
    public function export_csv($data){
        $output = array();
        $output[] = $data['title']['branchdelivery']."\n".implode("\n",(array)$data['content']['branchdelivery']);
        echo implode("\n",$output);
    }

    public function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){

        @ini_set('memory_limit','64M');

        if( !$data['title']['branchdelivery']){
            $title = array();
            foreach( $this->io_title('csv') as $k => $v ){
                $title[] = $v;
            }
            $data['title']['branchdelivery'] = mb_convert_encoding('"'.implode('","',$title).'"', 'GBK', 'UTF-8');
        }

        $limit = 100;
        
        if( !$list=$this->getlist('*',$filter,$offset*$limit,$limit) ) return false;
        
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

            $branchdeliveryRow['*:发货仓库'] = $branch[$aFilter['branch_name']];
            $branchdeliveryRow['*:商品类型'] = $aFilter['goods_type'];
            $branchdeliveryRow['*:品牌']     = $aFilter['brand_name'];
            $branchdeliveryRow['*:商品编码'] = $aFilter['goods_bn'];
            $branchdeliveryRow['*:货号']     = $aFilter['product_bn'];
            $branchdeliveryRow['*:货品名称'] = $aFilter['product_name'];
            $branchdeliveryRow['*:商品规格'] = $aFilter['goods_specinfo'];
            $branchdeliveryRow['*:销售数量'] = $aFilter['sale_num'];
            $branchdeliveryRow['*:退货数量'] = $aFilter['aftersale_num'];
            $branchdeliveryRow['*:店铺名称'] = $shop[$aFilter['shop_id']];
            $branchdeliveryRow['*:合计数量'] = $aFilter['total_nums'];

            $data['content']['branchdelivery'][] = mb_convert_encoding('"'.implode('","',$branchdeliveryRow).'"', 'GBK', 'UTF-8');
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
                'branch_name' =>
                array(
                  'type' => 'table:branch@ome',
                  'editable' => false,
                  'label'=>'发货仓库',
                  'order' => 1,
                ),
                'goods_type' =>
                array(
                    'type' => 'table:goods_type@ome',
                    'label' => '商品类型',
                    'width' => 130,
                    'order' => 2,
                    'orderby' => false,
                ),
                'brand_name' =>
                array(
                    'type' => 'table:brand@ome',
                    'label' => '品牌',
                    'width' => 130,
                    'order' => 3,
                    'orderby' => false,
                ),
                'goods_bn' =>
                array(
                    'type' => 'varchar(30)',
                    'label' => '商品编码',
                    'width' => 130,
                    'order' => 4,
            'orderby' => false,
                ),
                'product_bn' =>
                array(
                    'type' => 'varchar(30)',
                    'label' => '货号',
                    'width' => 130,
                    'order' => 5,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'searchtype' => 'has',
            'orderby' => false,
                ),
                'product_name' =>
                array(
                    'type' => 'varchar(200)',
                    'label' => '货品名称',
                    'width' => 130,
                    'order' => 6,
            'orderby' => false,
                ),
                'goods_specinfo'=>
                array(
                    'type' => 'varchar(200)',
                    'label' => '商品规格',
                    'width' => 130,
                    'order' => 7,
            'orderby' => false,
                ),
                'sale_num' =>
                array(
                  'type' => 'number',
                  'label' => '销售数量',
                  'width' => 100,
                  'order' => 8,   
            //'orderby' => false,
                ),                               
                'aftersale_num' =>
                array(
                  'type' => 'number',
                  'label' => '退货数量',
                  'width' => 100,
                  'order' => 9,    
            'orderby' => false,
                ),
                'shop_id' =>
                array(
                  'type'  => 'table:shop@ome',
                  'label' => '店铺名称',
                  'width' => 120,
                  'order' => 10,
            'orderby' => false,
                ),
                'total_nums' =>
                array(
                  'type'  => 'number',
                  'label' => '合计数量',
                  'width' => 120,
                  'order' => 11,
            'orderby' => false,
                ),                                     
            ),
            'in_list' => array(
                0 => 'branch_name',
                1 => 'goods_type',
                2 => 'brand_name',
                3 => 'goods_bn',
                4 => 'product_bn',
                5 => 'product_name',
                6 => 'goods_specinfo',        
                7 => 'sale_num',
                8 => 'aftersale_num',
                9 => 'shop_id',
                10 => 'total_nums',
            ),
            'default_in_list' => array(
                0 => 'branch_name',
                1 => 'goods_type',
                2 => 'brand_name',
                3 => 'goods_bn',
                4 => 'product_bn',
                5 => 'product_name',
                6 => 'goods_specinfo',        
                7 => 'sale_num',
                8 => 'aftersale_num',
                9 => 'shop_id',
                10 => 'total_nums',
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