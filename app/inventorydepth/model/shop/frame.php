<?php
/**
*
*/
class inventorydepth_mdl_shop_frame extends dbeav_model
{

    //var $has_export_cnf = true;

    public $appendCols = 'iid,shop_id';

    public $defaultOrder = 'id';

    public $export_name = '前端店铺商品';

    function __construct($app)
    {
        parent::__construct($app);

        $this->app = $app;
        $this->export_name = '前端店铺【'.$_SESSION['shop_name'].'】商品';
    }

    public function table_name($real=false)
    {
        $table_name = 'shop_items';
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }

    public function getFinderList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {  
        $expired = kernel::single('inventorydepth_batchframe')->is_expired();
        $part = true;
        if ($filter['range'] === 'all' && $expired) {
            $part = false; unset($filter['range']);
            $this->appendCols .= ',approve_status';
            $this->updateApproveStatus($filter['shop_id']);
        }
        $list = parent::getList($cols, $filter, $offset, $limit, $orderType);
        
        if(!$list) return $list;

        return $list;
    }

    public function updateApproveStatus($shop_id) 
    {
        $shop = $this->app->model('shop')->getList('shop_id,shop_type,business_type',array('shop_id'=>$shop_id),0,1);
        $shopfactory = inventorydepth_service_shop_factory::createFactory($shop[0]['shop_type'],$shop[0]['business_type']);
        if ($shopfactory === false) {
            $errormsg  ='店铺类型有误！'; return false;
        }

        $offset = 1; $limit = 50; $count = 0;
        do {
            # 请求商品
            $result = $shopfactory->downloadListNOSku(array('approve_status'=>'onsale'),$shop_id,$offset,$limit,$errormsg);

            $totalResults = $shopfactory->getTotalResults();
            if (empty($result)) {break;}

            $count += count($result);

            foreach ($result as $value) {
                if ($value['approve_status'] == 'onsale') {
                    $iid[] = $value['iid'] ? $value['iid'] : $value['num_iid'];
                }
            }

            if ($count>=$totalResults) {
                 break;
            }

            $offset++;
        } while ( true );

        if ($iid && $shop_id) {
            $this->update(array('approve_status'=>'onsale'),array('iid'=>$iid,'shop_id'=>$shop_id));
            $this->update(array('approve_status'=>'instock'),array('iid|notin'=>$iid,'shop_id'=>$shop_id));
        }
    }

    public function modifier_detail_url($row) 
    {
        return <<<EOF
        <a target='_blank' href='{$row}'>{$row}</a>
EOF;
    }

    public function io_title($filter=null,$ioType='csv'){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['inventorydepth'] = array(
                    '*:商品编码' => 'bn',
                    '*:商品名称' => 'title',
                    '*:在架状态' => 'approve_status',
                    '*:店铺数量' => 'shop_stock',
                    '*:淘管数量' => 'actual_stock',
                    '*:SKU数' => 'sku_num',
                    '*:SKU列表' => 'sku_list',
                    '*:店铺名称' => 'shop_name',
                );
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType]['inventorydepth'] );
        return $this->ioTitle[$ioType][$filter];
    }

    public function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ,$maxOffset = 1000){
        if(!empty($filter['ids'])){
            $filter['id'] = $ids;
        }
        @set_time_limit(0);
        if ($offset>$maxOffset) return false;
        $shop_id = $filter['shop_id'];
        
        $shop = $this->app->model('shop')->getList('name,shop_bn,shop_type,shop_id,business_type',$filter,0,1);
        
        $shop_bn = $shop[0]['shop_bn'];
        $shop_type = $shop[0]['shop_type'];
        $shop_name = $shop[0]['name'];
        $business_type = $shop[0]['business_type'];
        $shopfactory = inventorydepth_service_shop_factory::createFactory($shop_type,$business_type);
        if ($shopfactory === false) {
            $errormsg = $this->app->_('店铺类型有误！'); return false;
        }

        if( !$data['title']['inventorydepth'] && $offset == 0){
            if( !$data['title']['inventorydepth'] ){
                $title = array();
                foreach( $this->io_title('inventorydepth') as $k => $v ){
                    $title[] = $this->charset->utf2local($v);
                }
                $data['title']['inventorydepth'] = '"'.implode('","',$title).'"';
            }
            
            /*
            $approve_status = $shopfactory->get_approve_status();
            foreach ($approve_status as $key=>$value) {
                if ($value['filter']) {
                    $this->export_status[] = $value['filter'];
                }
            }*/

            //$this->offset = 1;
        }

        //$offset++;
        unset($data['name']);
        /*
        if (empty($this->export_status)) {
            unset($data['name']);
            return false;
        }
        
        $items = $this->export_next($shop[0],$shopfactory);
        if($items === false ) {
            unset($data['name']);
            return false;
        }

        $data['contents'] = $items;
        $this->offset++;

        return true;*/
        $limit = 100;
        $pageno = $offset*$limit;

        $itemList = $this->getList('iid,title,approve_status,bn as outer_id,price,shop_store as num',$filter,$pageno,$limit);
        if (empty($itemList)) {
            return false;
        }
        
        $result = array(); $itemIIds = array();
        foreach ($itemList as $key=>$value) {
            $iid = strval($value['iid']);
            
            $itemIIds[] = $iid;
            $result[$iid] = $value;
        }
        
        // 取SKUS
        $skuModel = app::get('inventorydepth')->model('shop_skus');
        $skuList = $skuModel->getList('shop_iid,shop_product_bn as outer_id',array('shop_id'=>$shop_id,'shop_iid'=>$itemIIds));
        foreach ($skuList as $key=>$value) {
            $shop_iid = strval($value['shop_iid']);

            $result[$shop_iid]['skus']['sku'][] = $value;
        }
        
        $rs = $this->formatCsv($items,$result,$shop);
        foreach($items as $v){
           $new_items[] = $this->charset->utf2local($v);
        }
        $items = $new_items;
        if($data['content']['inventorydepth']){
            $data['content']['inventorydepth'] = array_merge($data['content']['inventorydepth'],$items);
        }else{
            $data['content']['inventorydepth'] = $items;
        }
        //$data['content']= $items;

        return $rs;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function formatCsv(&$data,$result,$shop = array()) 
    {
        if(empty($result)) return false;
        $shop_name = $shop[0]['name'];
        $shop_id = $shop[0]['shop_id'];
        $shop_bn = $shop[0]['shop_bn'];

        $iid = array(); $content = array();

        $spbn = array();
        foreach($result as $key => $item){
            $iid = $item['iid'] ? $item['iid'] : $item['num_iid'];

            if ($item['skus']['sku']) {
                foreach ($item['skus']['sku'] as $sku) {
                    $spbn[] = $sku['outer_id'];
                }
            } else {
                $spbn[] = $item['outer_id'];
            }

            $content[strval($iid)] = array(
                'bn' => $item['outer_id'],
                'title' => $item['title'],
                'approve_status' => ($item['approve_status']=='onsale' ? '在售' : '下架'),
                'shop_stock' => intval($item['num']),
                'actual_stock' => '-',
                'sku_num' => '-',
                'sku_list' => '-',
                'shop_name' => $shop_name,
                'download_time' => $item['download_time'],
                'price' => $item['price'],
                'detail_url' => $item['detail_url'],
            );
        }
        $spbn = array_filter($spbn); $skuMapping = array();
        if ($spbn) {
            $products = $this->app->model('products')->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('bn'=>$spbn));
            $products = $products ? $products : array();
            if ($products) {
                kernel::single('inventorydepth_stock_products')->resetVar()->writeMemory($products);
                kernel::single('inventorydepth_stock_pkg')->resetVar()->writeMemory($products);

                $list = $this->app->model('shop_adjustment')->getList('shop_product_bn,bind',array('shop_product_bn'=>$spbn,'mapping'=>'1','shop_id'=>$shop_id));
                foreach ($list as $key => $value) {
                    $skuMapping[$value['shop_product_bn']] = $value['bind'];
                }
            }

            unset($spbn,$products,$list);
        }
        # END

        foreach ($result as $key => $item) {
            $iid = $item['iid'] ? strval($item['iid']) : strval($item['num_iid']);
            $shop_product_bn = array(); $pkgFlag = $productFlag = array();

            if ($item['skus']['sku']) {
                $content[$iid]['sku_num'] = count($item['skus']['sku'])/1;
                
                $content[$iid]['sku_list'] = '';
                foreach ($item['skus']['sku'] as $sku) {
                    $content[$iid]['sku_list'] .= $sku['outer_id'].'||';
                    $shop_product_bn[] = $sku['outer_id'];
                    if (isset($skuMapping[$sku['outer_id']])) {
                        $productFlag[] = $sku['outer_id'];
                    }
                    if ($skuMapping[$sku['outer_id']]) {
                        $pkgFlag[] = $sku['outer_id'];
                    }
                }
            } else {
                $shop_product_bn[] = $item['outer_id'];
                if (isset($skuMapping[$item['outer_id']])) {
                    $productFlag[] = $item['outer_id'];
                }
                if ($skuMapping[$item['outer_id']]) {
                    $pkgFlag[] = $item['outer_id'];
                }
            }

            $shop_product_bn = array_filter($shop_product_bn);
            if ( $shop_product_bn ) {
                if ($pkgFlag) {
                    $actual_stock = kernel::single('inventorydepth_stock_calculation')->get_pkg_actual_stock($pkgFlag[0],$shop_bn,$shop_id);
                } elseif ($productFlag){
                    $actual_stock = kernel::single('inventorydepth_stock_calculation')->get_goods_actual_stock($shop_product_bn,$shop_bn,$shop_id);
                } else {
                    $actual_stock = '-';
                }
                
                $content[$iid]['actual_stock'] =$actual_stock/1;
            }
            //$data[] = implode( "\t,", $content[$iid] );
        }
        
        $data = $content;
        return true;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function export_next($shop,$shopfactory) 
    {
        if (empty($this->export_status)) {
            return false;
        }

        $approve_status = $this->export_status[0];
        $result = $this->sdf_export($items,$shop,$approve_status,$this->offset,50,$shopfactory);
        if ($result === false) {
            array_shift($this->export_status);
            $this->offset = 1;
            return $this->export_next($shop,$shopfactory);
        }

        return $items;
    }
    
    
    function export_csv($data,$exportType = 1 ){
        $output = array();
        $output[] = $data['title']['inventorydepth'];
        foreach( $data['content']['inventorydepth'] as $k => $val ){
            $output[] = implode("\n",(array)$val);
        }
        echo implode("\n",$output);
    }

    public function sdf_export(&$data,$shop,$approve_status,$offset,$limit=90,$shopfactory) 
    {
        $shop_id = $shop['shop_id']; $shop_bn = $shop['shop_bn'];$shop_name = $shop['name'];

        $result = $shopfactory->downloadList($approve_status,$shop_id,$offset,$limit,$errormsg);

        if(empty($result)) return false;
        
        $iid = array(); $content = array();

        $spbn = array();
        foreach($result as $key => $item){
            $iid = $item['iid'] ? $item['iid'] : $item['num_iid'];

            if ($item['skus']['sku']) {
                foreach ($item['skus']['sku'] as $sku) {
                    $spbn[] = $sku['outer_id'];
                }
            } else {
                $spbn[] = $item['outer_id'];
            }

            $content[strval($iid)] = array(
                'bn' => $item['outer_id'],
                'title' => $item['title'],
                'approve_status' => ($item['approve_status']=='onsale' ? '在售' : '下架'),
                'shop_stock' =>$item['num']/1,
                'actual_stock' => '-',
                'sku_num' => '-',
                'sku_list' => '-',
                'shop_name' => $shop_name,
            );
        }
        $spbn = array_filter($spbn); $skuMapping = array();
        if ($spbn) {
            $products = $this->app->model('products')->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('bn'=>$spbn));
            $products = $products ? $products : array();
            if ($products) {
                kernel::single('inventorydepth_stock_products')->resetVar()->writeMemory($products);
                kernel::single('inventorydepth_stock_pkg')->resetVar()->writeMemory($products);

                $list = $this->app->model('shop_adjustment')->getList('shop_product_bn,bind',array('shop_product_bn'=>$spbn,'mapping'=>'1','shop_id'=>$shop_id));
                foreach ($list as $key => $value) {
                    $skuMapping[$value['shop_product_bn']] = $value['bind'];
                }
            }

            unset($spbn,$products,$list);
        }
        # END

        foreach ($result as $key => $item) {
            $iid = $item['iid'] ? strval($item['iid']) : strval($item['num_iid']);
            $shop_product_bn = array(); $pkgFlag = $productFlag = array();

            if ($item['skus']['sku']) {
                $content[$iid]['sku_num'] = count($item['skus']['sku'])/1;
                
                $content[$iid]['sku_list'] = '';
                foreach ($item['skus']['sku'] as $sku) {
                    $content[$iid]['sku_list'] .= $sku['outer_id'].'||';
                    $shop_product_bn[] = $sku['outer_id'];
                    if (isset($skuMapping[$sku['outer_id']])) {
                        $productFlag[] = $sku['outer_id'];
                    }
                    if ($skuMapping[$sku['outer_id']]) {
                        $pkgFlag[] = $sku['outer_id'];
                    }
                }
            } else {
                $shop_product_bn[] = $item['outer_id'];
                if (isset($skuMapping[$item['outer_id']])) {
                    $productFlag[] = $item['outer_id'];
                }
                if ($skuMapping[$item['outer_id']]) {
                    $pkgFlag[] = $item['outer_id'];
                }
            }

            $shop_product_bn = array_filter($shop_product_bn);
            if ( $shop_product_bn ) {
                if ($pkgFlag) {
                    $actual_stock = kernel::single('inventorydepth_stock_calculation')->get_pkg_actual_stock($pkgFlag[0],$shop_bn,$shop_id);
                } elseif ($productFlag){
                    $actual_stock = kernel::single('inventorydepth_stock_calculation')->get_goods_actual_stock($shop_product_bn,$shop_bn,$shop_id);
                } else {
                    $actual_stock = '-';
                }
                
                $content[$iid]['actual_stock'] = $actual_stock/1;
            }
            
            $data[] = implode( "\t,", $content[$iid] );
        }

        return true;
    }


    public function export_test() 
    {
            $content = array(
                'bn' => 'testbn',
                'title' => '测试商品',
                'approve_status' =>  '在售' ,
                'shop_stock' => '200' ,
                'actual_stock' => '200',
                'sku_num' => '1',
                'sku_list' => 'pbn1',
                'shop_name' => '测试店铺',
            );

            foreach ($content as $kk=>$vv) {
                $content[$kk] = $this->charset->utf2local($vv);
            }
            
            $data['inventorydepth'][] = '="'.implode( '","', $content ).'"';

            return $data;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function get_schema()
    {
        $schema = parent::get_schema();
        if (isset($schema['columns']['taog_store'])) {
            unset($schema['columns']['taog_store']);
            $k = array_search('taog_store', $schema['default_in_list']);
            if ($k) {
                unset($schema['default_in_list'][$k]);
            }
            $k = array_search('taog_store', $schema['in_list']);
            if ($k) {
                unset($schema['in_list'][$k]);
            }
        }
        if (isset($schema['columns']['shop_store'])) {
            unset($schema['columns']['shop_store']);
            $k = array_search('shop_store', $schema['default_in_list']);
            if ($k) {
                unset($schema['default_in_list'][$k]);
            }
            $k = array_search('shop_store', $schema['in_list']);
            if ($k) {
                unset($schema['in_list'][$k]);
            }
        }
        return $schema;
    }

    //根据查询条件获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end){
        
        //根据选择的字段定义导出的第一行标题
        if($curr_sheet == 1){
            $data['content']['main'][] = $this->getExportTitle($fields);
        }

        if(!empty($filter['ids'])){
            $filter['id'] = $ids;
        }

        $shop_id = $filter['shop_id'];
        $shop = $this->app->model('shop')->getList('name,shop_bn,shop_type,shop_id,business_type',$filter,0,1);
        
        $shop_bn = $shop[0]['shop_bn'];
        $shop_type = $shop[0]['shop_type'];
        $shop_name = $shop[0]['name'];
        $business_type = $shop[0]['business_type'];
        $shopfactory = inventorydepth_service_shop_factory::createFactory($shop_type,$business_type);
        if ($shopfactory === false) {
            $errormsg = $this->app->_('店铺类型有误！'); return false;
        }

        unset($data['name']);

        $itemList = $this->getList('iid,title,approve_status,bn as outer_id,price,shop_store as num,download_time,detail_url,price', $filter, $start, $end);
        if (empty($itemList)) {
            return false;
        }
        
        $result = array(); $itemIIds = array();
        foreach ($itemList as $key=>$value) {
            $iid = strval($value['iid']);
            
            $itemIIds[] = $iid;
            $result[$iid] = $value;
        }
        
        // 取SKUS
        $skuModel = app::get('inventorydepth')->model('shop_skus');
        $skuList = $skuModel->getList('shop_iid,shop_product_bn as outer_id',array('shop_id'=>$shop_id,'shop_iid'=>$itemIIds));
        foreach ($skuList as $key=>$value) {
            $shop_iid = strval($value['shop_iid']);
            $result[$shop_iid]['skus']['sku'][] = $value;
        }
        
        $rs = $this->formatCsv($items,$result,$shop);

        foreach($items as $k => $v){
            $newItem['bn'] = $v['bn'];
            $newItem['title'] = $v['title'];
            $newItem['column_approve_status'] = $v['approve_status'];
            $newItem['shop_stock'] = $v['shop_stock'];
            $newItem['actual_stock'] = $v['actual_stock'];
            $newItem['column_sku_num'] = $v['sku_num'];
            $newItem['sku_list'] = $v['sku_list'];
            //$newItem['column_regulation'] = '';
            $newItem['download_time'] = date('Y-m-d H:i:s',$v['download_time']);
            $newItem['detail_url'] = $v['detail_url'];
            $newItem['price'] = $v['price'];
            $newItem['shop_name'] = $v['shop_name'];
            //$newItem['column_store_statistics'] = '*:前端/总',

            $exptmp_data = array();
            foreach ($newItem as $key => $col) {
                $newItem[$key] = mb_convert_encoding($newItem[$key], 'GBK', 'UTF-8');
                $exptmp_data[] = $newItem[$key];
            }

            $data['content']['main'][] = implode(',', $exptmp_data);
        }

        return $data;
    }

    public function getExportTitle($fields){
        $export_columns = array(
            'bn' => '*: 店铺商品编码',
            'title' => '*:店铺商品名称',
            'column_approve_status' => '*: 商品在架状态',
            'shop_stock' => '*:店铺数量',
            'actual_stock' =>'*:淘管数量',
            'column_sku_num' => '*:SKU数',
            'sku_list'=> '*:SKU列表',
            //'column_regulation' => '*: 应用上下架规则',
            'download_time' => '*: 同步时间',
            'detail_url' => '*:访问URL',
            'price' => '*:销售价',
            'shop_name' => '*:店铺名称',
            //'column_store_statistics' => '*:前端/总',
        );

        $title = array();
        foreach( $export_columns as $k => $col ){
                $title[] = $export_columns[$k];
        }
        
        return mb_convert_encoding(implode(',',$title), 'GBK', 'UTF-8');
    }

    public function disabled_export_cols(&$cols){
        unset($cols['column_regulation'], $cols['column_store_statistics'], $cols['column_operation']);
    }
}