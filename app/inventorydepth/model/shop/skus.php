<?php
/**
*
*/
class inventorydepth_mdl_shop_skus extends dbeav_model
{
    public $additional = array();

    public $filter_use_like = true;

    public $appendCols = 'shop_iid,shop_id';

    public static $taog_products = array();

    public static $taog_pkg = array();

    function __construct($app)
    {
        parent::__construct($app);

        $this->app = $app;
    }

    public function initTaogBn($bnList) 
    {
        if ($bnList) {
            self::$taog_products = self::$taog_pkg = array();

            $productModel = $this->app->model('products');
            $list = $productModel->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('bn'=>$bnList));
            foreach ($list as $key=>$value) {
                self::$taog_products[$value['bn']] = $value;
            }

            $products = $list ? $list : array();
            kernel::single('inventorydepth_stock_products')->resetVar()->writeMemory($products);

            # 看是不是捆绑商品
            if (app::get('omepkg')->is_installed()) {
                $pkgModel = app::get('omepkg')->model('pkg_goods');
                $list = $pkgModel->getList('pkg_bn',array('pkg_bn'=>$bnList));
                foreach ($list as $key=>$value) {
                    self::$taog_pkg[$value['pkg_bn']] = $value;
                }

                kernel::single('inventorydepth_stock_pkg')->resetVar()->writeMemory($products);
            }
            
            unset($list,$products);

            kernel::single('inventorydepth_stock_calculation')->set_recal_shop_freeze(true);
        }
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
            if ($filter['shop_product_bn'] == 'nobn') {
                $where[] = ' (shop_product_bn is NULL OR shop_product_bn="") ';
                unset($filter['shop_product_bn']);
            }

            if ($filter['shop_product_bn'] == 'repeat') {
                unset($filter['shop_product_bn']);

                $sql = 'SELECT id,shop_product_bn,shop_id FROM '.$this->table_name(true).' WHERE shop_product_bn!="" AND shop_product_bn is not null GROUP BY shop_product_bn,shop_id  Having count(1)>1';
                
                $list = $this->db->select($sql);
                if ($list) {
                    foreach ($list as $value) {
                        $filter['shop_product_bn'][] = $value['shop_product_bn'];
                    }
                } else {
                    # 没有重复的，则结果为空
                    $filter['shop_product_bn'][] = 'norepeat';
                }
            }
        }

        return parent::_filter($filter,$tableAlias,$baseWhere).' AND '.implode(' AND ', $where);
    }

    public function io_title($filter=null,$ioType='csv'){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['main'] = array(
                    '*:店铺编号' => 'shop_bn',
                    '*:店铺名称' => 'shop_name',
                    '*:店铺货号' => 'shop_product_bn',
                    '*:销售价'  => 'shop_price',
                    '*:条形码'  => 'shop_barcode',
                    //'*:规格'   => 'shop_properties',
                    '*:商品名称' => 'title',
                    '*:上架状态' => 'approve_status', 
                );
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType][$filter];
    }

    public function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        @ini_set('memory_limit','64M');

        if( !$data['title'] ){
            $title = array();
            foreach( $this->io_title('main') as $k => $v ){
                //$title[] = $this->charset->local2utf($v);
                $title[] = $v;
            }
            $data['title'] = '"'.implode('","',$title).'"';
        }

        $limit = 100;

        if( !$list=$this->getList('*',$filter,$offset*$limit,$limit) )return false;

        $itemModel = $this->app->model('shop_items');
        foreach( $list as $l ){
            $item = $itemModel->select()->columns('*')->where('iid=?',$l['shop_iid'])->instance()->fetch_row();
            $l['approve_status'] = $item['approve_status'];
            $l['title'] = $item['title'];

            foreach( $this->oSchema['csv']['main'] as $k => $v ){
                //$row[$k] = $this->charset->local2utf($l[$v]);
                $row[$k] = $l[$v];
            }
            $data['contents'][] = '"'.implode('","',$row).'"';
        }

        return true;
    }

    public function get_schema()
    {
        $schema = parent::get_schema();

        # 过滤掉不显示的字段
        $none = array('release_status','release_stock','shop_stock');
        foreach ($none as $value) {
            $key = array_search($value, $schema['in_list']);

            if($key !== false) unset($schema['in_list'][$key]);

            $key = array_search($value, $schema['default_in_list']);

            if($key !== false) unset($schema['default_in_list'][$key]);
        }

        return $schema;
    }

    /**
     * 删除货品
     *
     * @return void
     * @author 
     **/
    public function deleteSkus($filter)
    {
        $sql = 'DELETE FROM `'.$this->table_name(1).'` where '.$this->_filter($filter);

        return $this->db->exec($sql);
    }

    /**
     * 保存SKU
     *
     * @return void
     * @author 
     **/
    public function updateSku($sku,$id)
    {
        # 
        $bnList[] = $sku['outer_id'];
        $this->initTaogBn($bnList);
        unset($bnList);

        $data = array(
            'shop_product_bn'       => $sku['outer_id'],
            'shop_product_bn_crc32' => sprintf('%u',crc32($sku['outer_id'])),
            'shop_properties'       => $sku['properties'],
            'shop_price'            => $sku['price'],
            'download_time'         => time(),
            'shop_stock'            => $sku['quantity'],
        );
        
        $productsModel = $this->app->model('products');
        # 映射到本地商品
        $data['mapping'] = $this->getMapping($sku['outer_id'],$data['bind']);

        $this->update($data,array('id'=>$id));
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function isave($skus,$shop,$item=array(),&$stores)
    {
        $bnList = array();
        foreach ($skus['sku'] as $sku) {
            $bnList[] = $sku['outer_id'];
        }
        $this->initTaogBn($bnList); $shop_id = $shop['shop_id']; $shop_bn = $shop['shop_bn'];
        unset($bnList);

       $productsModel = $this->app->model('products');
       $spbn = array();
        # 保存SKU
        foreach ($skus['sku'] as $sku) {
            $iid = $sku['iid'] ? $sku['iid'] : $item['iid']; $spbn[] = $sku['outer_id'];

            if ($sku['sku_id']) {
                $sku_id = md5($shop['shop_id'].$iid.$sku['sku_id']);
            } else {
                $sku_id = md5($shop['shop_id'].$iid);
            }

            # 映射到本地商品
            $product_bn = $sku['outer_id'];

            $sku = array(
                'id' => $sku_id,
                'shop_id'               => $shop['shop_id'],
                'shop_bn'               => $shop['shop_bn'],
                'shop_bn_crc32'         => sprintf('%u',crc32($shop['shop_bn'])),
                'shop_name'             => $shop['name'],
                'shop_type'             => $shop['shop_type'],
                'shop_sku_id'           => $sku['sku_id'],
                'shop_iid'              => $iid,
                'shop_product_bn'       => $sku['outer_id'],
                'shop_product_bn_crc32' => sprintf('%u',crc32($sku['outer_id'])),
                'shop_properties'       => $sku['properties'],
                'shop_price'            => $sku['price'],
                'shop_barcode'          => $sku['barcode'],
                'simple'                => ($item['simple'] == 'true') ? 'true' : 'false',
                'download_time'         => time(),
                'shop_stock'            => $sku['quantity'],
            );

            if ($item['title']) {
                $sku['shop_title'] = $item['title'];
            }
            
            $sku['mapping'] = $this->getMapping($product_bn,$sku['bind']);

            $this->save($sku);

            $delete_filter['shop_sku_id|notin'][] = $sku['shop_sku_id'];

        }
        
        if ($sku['bind']) {
            $item_actual_stock = kernel::single('inventorydepth_stock_calculation')->get_pkg_actual_stock($spbn[0],$shop_bn,$shop_id);
        } else {
            $item_actual_stock = kernel::single('inventorydepth_stock_calculation')->get_goods_actual_stock($spbn,$shop_bn,$shop_id);
        }
        $stores[strval($item['iid'])]['taog_store'] = $item_actual_stock;

        # 删除多余的
        $delete_filter['shop_iid'] = $item['iid'];
        $delete_filter['shop_id'] = $shop['shop_id'];

        $this->deleteSkus($delete_filter);
    }

    /**
     * 清空表数据
     *
     * @return void
     * @author 
     **/
    public function truncate()
    {
        $sql = 'TRUNCATE TABLE '.$this->table_name(true);

        $this->db->exec($sql);
    }

    /**
     * 批量加入货品
     *
     * @return void
     * @author 
     **/
    public function batchInsert($items,$shop,&$stores)
    {
        if (empty($items)) return false;

        $bnList = $taog_id = array();
        foreach ($items as $key => $item) {
            $iid = $item['iid'] ? $item['iid'] : $item['num_iid'];

            if (isset($item['skus']['sku'])) {
                foreach ($item['skus']['sku'] as $k => $sku) {
                    $bnList[] = $sku['outer_id'];

                    $id = md5($shop['shop_id'].$iid.$sku['sku_id']);
                    $items[$key]['skus']['sku'][$k]['taog_id'] = $id;
                    $taog_id[] = $id;
                }
            } else {
                $bnList[] = $item['outer_id'];

                $id = md5($shop['shop_id'].$iid);
                $items[$key]['taog_id'] = $id;
                $taog_id[] = $id;
            }
        }
        $this->initTaogBn($bnList); $shop_id = $shop['shop_id']; $shop_bn = $shop['shop_bn'];
        unset($bnList);

        $shopSkuLib = kernel::single('inventorydepth_shop_skus');

        $shop_bn_crc32         = $shopSkuLib->crc32($shop['shop_bn']);

        $productModel = $this->app->model('products');
        
        $request = array();
        $rows = $this->getList('request,id',array('id' => $taog_id));
        foreach ($rows as $key=>$row) {
            $request[$row['id']] = $row['request'];
        }
        unset($rows,$taog_id);

        $VALUES = array();  $delSku = array(); $data = array();
        foreach ($items as $key => $item) {
            $spbn = array();
            $iid = $item['iid'] ? $item['iid'] : $item['num_iid'];

            if (isset($item['skus']['sku'])) {
             # 多规格   
                foreach ($item['skus']['sku'] as $sku) {
                    $shop_product_bn = $sku['outer_id']; $spbn[] = $shop_product_bn;
                    $shop_product_bn_crc32 = $shopSkuLib->crc32($shop_product_bn);

                    $mapping = $this->getMapping($shop_product_bn,$bind);
                    $download_time = time();
                    
                    /*
                    if ($sku['properties_name']) {
                        $shop_properties_name = '';
                        $properties = explode(';', $sku['properties_name']);
                        foreach ($properties as $key => $value) {
                            list($pid,$vid,$pid_name,$vid_name) = explode(':', $value);
                            $shop_properties_name .= $pid_name.':'.$vid_name.';';
                        }
                    }else{
                        $shop_properties_name = '';
                    }*/
                    #  判断是否存在发布库存
                    $release_stock = 0;
                    
                    #$id = md5($shop['shop_id'].$iid.$sku['sku_id']); $delSku[] = $id;
                    
                    $data[] = array(
                        'shop_id' => $shop['shop_id'],
                        'shop_bn' => $shop['shop_bn'],
                        'shop_bn_crc32' => $shop_bn_crc32,
                        'shop_name' => $shop['name'],
                        'shop_type' => $shop['shop_type'],
                        'shop_sku_id' => $sku['sku_id'],
                        'shop_iid' => $iid,
                        'shop_product_bn' => $shop_product_bn,
                        'shop_product_bn_crc32' => $shop_product_bn_crc32,
                        'shop_properties' => $sku['properties'],
                        'shop_price' => $sku['price'],
                        'simple' => $item['simple'],
                        'download_time' => $download_time,
                        'shop_title' => $item['title'],
                        'mapping' => $mapping,
                        'shop_stock' => isset($sku['quantity']) ? $sku['quantity'] : $sku['num'],
                        'shop_properties_name' => $sku['properties_name'] ? $sku['properties_name'] : '',
                        'release_stock' => $release_stock,
                        'bind' => $bind,
                        'id' => $sku['taog_id'],
                        'request' => $request[$sku['taog_id']] == 'false' ? 'false' : 'true',
                    );

                }
            }else{
             # 单商品
                $shop_product_bn       = $item['outer_id']; $spbn[] = $shop_product_bn;
                $shop_product_bn_crc32 = $shopSkuLib->crc32($shop_product_bn);

                $mapping = $this->getMapping($shop_product_bn,$bind);
                $download_time = time();
                $shop_properties_name = '';
                
                $release_stock = 0;

                #$id = md5($shop['shop_id'].$iid); $delSku[] = $id;

                $data[] = array(
                    'shop_id' => $shop['shop_id'],
                    'shop_bn' => $shop['shop_bn'],
                    'shop_bn_crc32' => $shop_bn_crc32,
                    'shop_name' => $shop['name'],
                    'shop_type' => $shop['shop_type'],
                    'shop_sku_id' => '',
                    'shop_iid' => $iid,
                    'shop_product_bn' => $shop_product_bn,
                    'shop_product_bn_crc32' => $shop_product_bn_crc32,
                    'shop_properties' => $item['props'],
                    'shop_price' => $item['price'],
                    'simple' => $item['simple'],
                    'download_time' => $download_time,
                    'shop_title' => $item['title'],
                    'mapping' => $mapping,
                    'shop_stock' => $item['num'],
                    'shop_properties_name' => '',
                    'release_stock' => $release_stock,
                    'bind' => $bind,
                    'id' => $item['taog_id'],
                    'request' => $request[$item['taog_id']]=='false' ? 'false' : 'true',
                );
            }

            # 商品的实际库存
            if ($bind) {
                $item_actual_stock = kernel::single('inventorydepth_stock_calculation')->get_pkg_actual_stock($spbn[0],$shop['shop_bn'],$shop['shop_id']);
            } else {
                $item_actual_stock = kernel::single('inventorydepth_stock_calculation')->get_goods_actual_stock($spbn,$shop['shop_bn'],$shop['shop_id']);
            }

            $stores[strval($iid)]['taog_store'] = $item_actual_stock;
        }

        $sql = inventorydepth_func::get_replace_sql($this,$data);
        $this->db->exec($sql); 
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function getMapping($bn,&$bind) 
    {  
        $bind = '0';
        # $productModel = $this->app->model('products');
        # $product = $productModel->getList('bn',array('bn'=>$bn),0,1);

        if(isset(self::$taog_products[$bn])){ 
            return '1';
        }
        
        if (isset(self::$taog_pkg[$bn])) {
            $bind = '1';
            return '1';
        }

        return '0';
        # 看是不是捆绑商品
        /*
        if (app::get('omepkg')->is_installed()) {
            $pkgModel = app::get('omepkg')->model('pkg_goods');
            $pkg = $pkgModel->getPkgBn($bn);
            
            if($pkg){ 
                $bind = '1';
                return '1';
            }
        }

        return '0';*/
    }

    /**
     * @description 删除过时数据
     * @access public
     * @param void
     * @return void
     */
    public function deletePassData($shop_id,$time) 
    {
        $sql = ' DELETE FROM `'.$this->table_name(1).'` WHERE shop_id = "'.$shop_id.'" AND download_time < '.$time;
        $this->db->exec($sql);
    }

    /**
     * 通过CRC32查询
     *
     * @return void
     * @author 
     **/
    public function getListByCrc32($shop_product_bn,$shop_id)
    {
        $shop_product_bn = (array)$shop_product_bn;
        foreach ($shop_product_bn as &$value) {
            $value = kernel::single('inventorydepth_shop_skus')->crc32($value);
        }

        $filter = array(
            'shop_product_bn_crc32' => $shop_product_bn,
            'shop_id' => $shop_id,
        );

        $skus = $this->getList('*',$filter);

        return $skus;
    }
}