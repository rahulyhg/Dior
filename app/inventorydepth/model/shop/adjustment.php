<?php
/**
*
*/
class inventorydepth_mdl_shop_adjustment extends dbeav_model
{
    public $filter_use_like = false;

    public $defaultOrder = 'shop_product_bn_crc32';

    function __construct($app)
    {
        parent::__construct($app);

        $this->app = $app;
    }

    public function table_name($real=false){
        $table_name = 'shop_skus';
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }

    /**
     * 覆盖店铺库存
     *
     * @return void
     * @author
     **/
    public function convert_shop_stock($filter)
    {
        $sql = 'UPDATE '.$this->table_name(true).' SET shop_stock=release_stock WHERE '.$this->_filter($filter);
        $this->db->exec($sql);
    }


    public function io_title( $filter, $ioType='csv' ){
        switch( $ioType ){
             case 'csv':
                 $this->oSchema['csv']['title'] = array(
                     '*:店铺编码' => 'shop_bn',
                     '*:货品编号' => 'shop_product_bn',
                     '*:货品名称' => 'shop_title',
                     '*:发布库存' => 'release_stock',
                 );
             break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
    }
    
    public function fgetlist_csv(&$data,$filter,$offset,$exportType = 1 ) 
    {
        @ini_set('memory_limit','64M');

        if( !$data['title']){
            $title = array();
            foreach( $this->io_title('title') as $k => $v ){
                $title[] = $v;
            }
            $data['title'] = '"'.implode('","',$title).'"';
        }

        $limit = 100;
        if( !$list=$this->getList(implode(',',$this->oSchema['csv']['title']),$filter,$offset*$limit,$limit) )return false;
        foreach( $list as $aFilter ){
            $pRow = array();
            /*
            $detail['shop_bn'] = $aFilter['shop_bn'];
            $detail['shop_product_bn'] = $aFilter['shop_product_bn'];
            $detail['shop_title'] = $aFilter['shop_title'];
            $detail['release_stock'] = '';*/
            foreach( $this->oSchema['csv']['title'] as $k => $v ){
                $pRow[$k] =  $aFilter[$v];
            }
            $data['contents'][] = '"'.implode('","',$pRow).'"';
        }
        return true;
    }

    public function exportName(&$data) 
    {
        $shop_name = $this->app->model('shop')->select()->columns('name')->where('shop_id=?',$_SESSION['shop_id'])->instance()->fetch_one();
        $data['name'] = $shop_name.'发布库存模版'.date('Ymd');
    }

    public function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }


    /**
     * 导入前的单纯数据验证
     *
     * @return void
     * @author 
     **/
    public function  prepared_import_csv_row($row,$title,&$goodsTmpl,&$mark,&$newObjFlag,&$msg)
    {
        if(empty($row)) return false;

        $shop_bn = $row[$title['*:店铺编码']];
        $shop_product_bn = $row[$title['*:货品编号']];
        $release_stock = $row[$title['*:发布库存']];
        //$barcode = $row[$title['*:条形码']];

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);
            $mark = 'title';

            return $titleRs;
        }

        if(isset($this->nums)){
                $this->nums++;
                if($this->nums > 5000){
                    $msg['error'] = "导入的商品数据量过大，请减少到5000单以下！";
                    return false;
                }
        }else{
            $this->nums = 0;
        }

        $mark = 'contents';

        if (empty($shop_bn)) {
            $msg['warning'][] = '存在店铺编码为空的记录!';
            return false;
        }

        if (empty($shop_product_bn)) {
            $msg['warning'][] = '存在货品编码为空的记录!';
            return false;
        }
        
        if ((int)$release_stock<1) {
            $msg['warning'][] = '存在发布库存为零的记录!';
            return false;
        }
        return $row;
    }

    /**
     * 导入前的数据库数据验证
     *
     * @return void
     * @author 
     **/
    public function prepared_import_csv_obj($data,$mark,$goodsTmpl,&$msg = '')
    {
        $title = $data['title'];
        $contents = &$data['contents'];
        
        // $operInfo = kernel::single('inventorydepth_func')->getDesktopUser();

        $productModel = $this->app->model('products');
        $shopModel = $this->app->model('shop');
        foreach($contents as $content){
            $shop_bn = $content[$title['*:店铺编码']];
            $shop_product_bn = $content[$title['*:货品编号']];
            $release_stock = $content[$title['*:发布库存']];

            
            $filter['shop_bn'] = $shop_bn;
            $filter['shop_product_bn'] = $shop_product_bn;

            $sku = $this->getList('id,shop_product_bn,shop_id',$filter,0,1);

            if (!$sku) {
                $msg['error'] = "店铺【{$shop_bn}】不存在货品【{$shop_product_bn}】!";
                return false;
            }

            $shop = $shopModel->getList('shop_id,shop_bn,name,shop_type',array('shop_bn'=>$shop_bn),0,1);
            if (!$shop) {
                $msg['error'] = "店铺【编号是{$shop_bn}】不存在!";
                return false;
            }
            
            $sdf[] = array('release_stock'=>$release_stock,'id'=>$sku[0]['id'],'shop_id'=>$sku[0]['shop_id'],'shop_product_bn'=>$sku[0]['shop_product_bn']);

            // 记录操作日志
            $optLogModel = app::get('inventorydepth')->model('operation_log');
            $optLogModel->write_log('sku',$sku[0]['id'],'stockup','导入发布库存：'.$release_stock);

        }
        base_kvstore::instance('inventorydepth_shop_goods')->store('shop-goods-'.$this->ioObj->cacheTime,serialize($sdf));

        return null;
    }

    public function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }

    public function finish_import_csv(){
        base_kvstore::instance('inventorydepth_shop_goods')->fetch('shop-goods-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('inventorydepth_shop_goods')->store('shop-goods-'.$this->ioObj->cacheTime,'');
        
        $data = unserialize($data);

        $title = '导入店铺发布库存';
        kernel::single('inventorydepth_queue')->insert_shop_skus_queue($title,$data);
        return null;
    }

    /**
     * 
     *
     * @return void
     * @author 
     **/
    public function update_shop_stock($ids)
    {
        $sql = 'UPDATE '.$this->table_name(true).' SET shop_stock=release_stock WHERE id in('.implode(',', $ids).')';

        return $this->db->exec($sql);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        $where = array(1);
        if (isset($filter['shop_product_bn'])) {

            if ($filter['shop_product_bn'] == 'repeat') {
                unset($filter['shop_product_bn']);

                $pbn = $this->get_repeat_product_bn($filter);
                if ($pbn) {
                    $filter['shop_product_bn'] = $pbn;
                } else {
                    # 没有重复的，则结果为空
                    $filter['shop_product_bn'][] = 'norepeat';
                }
            }

            if ($filter['shop_product_bn'] == 'exceptrepeat') {
                unset($filter['shop_product_bn']);

                $pbn = $this->get_repeat_product_bn($filter);
                if ($pbn) {
                    $filter['shop_product_bn|notin'] = $pbn;
                }
            }
        }
        return parent::_filter($filter,$tableAlias,$baseWhere).' AND '.implode(' AND ', $where);
    }
    
    /**
     * @description 获取重复货号
     */
    public function get_repeat_product_bn($filter) 
    {
        $sql = 'SELECT id,shop_product_bn,shop_id FROM '.$this->table_name(true).' WHERE shop_id="'.$filter['shop_id'].'" AND shop_product_bn!="" AND shop_product_bn is not null GROUP BY shop_product_bn,shop_id  Having count(1)>1 ';
        $list = $this->db->select($sql);
        $pbn = array();
        if ($list) {
            foreach ($list as $key=>$value) {
                $pbn[] = $value['shop_product_bn'];
            }
        }
        return $pbn;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function modifier_mapping($row) 
    {
        if ($row == '1') {
            $row = '<div style="color:green;">SKU已匹配</div>';
        } else {
            $row = '<div style="color:red;">SKU未匹配</div>';
        }
        return $row;
    }

    public function getFinderList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null) 
    {

        $this->appendCols = 'shop_iid,shop_sku_id';

        $list = parent::getList($cols, $filter, $offset, $limit, $orderType);

        return $list;
    }

}