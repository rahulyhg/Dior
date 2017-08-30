<?php
/**
 * 更新库存逻辑
 *
 * @author chenping<chenping@shopex.cn>
 * @version $2012-8-6 17:22Z
 */
class inventorydepth_logic_stock extends inventorydepth_logic_abstract
{
    /* 当前的执行时间 */
    public static $now;

    /* 执行的间隔时间 */
    const intervalTime = 300;

    function __construct($app)
    {
        $this->app = $app;
        self::$now = time();
    }

    public function start()
    {
        @set_time_limit(0);
        @ini_set('memory_limit','128M');
        base_kvstore::instance('inventorydepth/apply/stock')->fetch('apply-lastexectime',$lastExecTime);
        if($lastExecTime && ($lastExecTime+self::intervalTime)>self::$now) {
            return false;
        }
        base_kvstore::instance('inventorydepth/apply/stock')->store('apply-lastexectime',self::$now);

        $applyModel = $this->app->model('regulation_apply');

        $products = $this->getChgProducts();

        base_kvstore::instance('inventorydepth/apply')->store('read_store_lastmodify',self::$now);
        if(empty($products)) return false;
        # 获取已经连接的店铺
        $filter = array(
            'filter_sql' =>'{table}node_id is not null and {table}node_id !=""',
        );
        $shops = $this->app->model('shop')->getList('shop_id,shop_bn,node_type',$filter);

        # 捆绑商品写内存
        kernel::single('inventorydepth_stock_pkg')->writeMemory($products);
        kernel::single('inventorydepth_stock_products')->writeMemory($products);

        $skuModel = $this->app->model('shop_skus');
        $stockCalLib = kernel::single('inventorydepth_stock_calculation');
        foreach($shops as $shop){
            //是否安装drm模块
            if (app::get('drm')->is_installed()) {
                //获取淘管店铺信息
                $channelShopObj = &app::get('drm')->model('channel_shop');
                $binds = array();
                $binds = $channelShopObj->getList('channel_id',array('shop_id'=>$shop['shop_id']),0,1);
                if(is_array($binds) && !empty($binds)) {
                    continue;
                }
            }

            # 店铺未开启回写
            $request = kernel::single('inventorydepth_shop')->getStockConf($shop['shop_id']);
            if($request != 'true') { continue; }

            # 仓库为该店铺供货
            $bra = kernel::single('inventorydepth_shop')->getBranchByshop($shop['shop_bn']);
            if (!$bra) { continue; }

            # 读取已经匹配，但不需要回写的货品
            $skuList = $skuModel->select()->columns('shop_product_bn')
                            ->where('mapping=?','1')
                            ->where('request=?','false')
                            ->where('shop_id=?',$shop['shop_id'])
                            ->instance()->fetch_all();
            $unRequest = array();
            if ($skuList) { $unRequest = array_map('current',$skuList); }
            

            $stocks = array(); unset($this->regu);/*清除上一个店铺的规则*/
            foreach($products as $product){
                if ($unRequest && in_array($product['bn'],$unRequest)) { continue; }

                $st = $this->getStock($product['bn'],$shop['shop_id'],$shop['shop_bn'],$shop['node_type']);
                if ($st === false) { continue; }

                $stocks[] = $st;
            }

            # 捆绑商品
            if (is_array(inventorydepth_stock_pkg::$pkg)) { 
                foreach (inventorydepth_stock_pkg::$pkg as $pkgValue) {
                    if ($unRequest && in_array($pkgValue['pkg_bn'],$unRequest)) { continue; }

                    $st = kernel::single('inventorydepth_logic_pkgstock')->getStock($pkgValue['pkg_bn'],$shop['shop_id'],$shop['shop_bn'],$shop['node_type']);
                    if($st === false) { continue; }

                    $stocks[] = $st;
                }
            }
            # END

            if ($stocks) {
                $new_stocks = array_chunk($stocks,50);
                foreach ($new_stocks as $stock) {
                    kernel::single('inventorydepth_shop')->doStockRequest($stock,$shop['shop_id']);
                }
            }
        }

        if ($this->reguUpdateFilter) {
            $this->app->model('regulation_apply')->update(array('al_exec'=>'true','exec_time'=>self::$now), $this->reguUpdateFilter);
        }

    }

    public function getStock($product_bn,$shop_id,$shop_bn,$node_type='') 
    {
        # 读取商品要执行的规则
        $quantity = $this->dealWithRegu($product_bn,$shop_id,$shop_bn);
        if ($quantity === false) { return false; }

        $params = array(
            'shop_product_bn' => $product_bn,
            'shop_bn'         => $shop_bn,
            'shop_id'         => $shop_id,
        );
        # 店铺冻结
        $stockCalLib = kernel::single('inventorydepth_stock_calculation');
        $store_freeze = call_user_func_array(array($stockCalLib,'get_shop_freeze'), $params);
        $tmp_product = kernel::single('inventorydepth_stock_products')->fetch_products($product_bn);
        $memo = array(
                'store_freeze' => $store_freeze,
                'last_modified' => $tmp_product['last_modified'],
        );
        
        // 受1号店回写库存限制
        if($node_type == 'yihaodian' && $quantity >= 3000){
            $quantity = 2999;
        }

        $stock = array(
            'bn' => $product_bn,
            'quantity' => $quantity,
            'memo' => json_encode($memo),
        );

        return $stock;
    }

    public function dealWithRegu($pbn,$shop_id,$shop_bn,&$apply_regulation=array()) {
        $product = kernel::single('inventorydepth_stock_products')->fetch_products($pbn);
        $product_id = $product['product_id'];

        $regu = $this->getRegu($shop_id);
        foreach($regu as $r){
            if(empty($r['regulation'])) continue;

            if((in_array($product_id,$r['apply_goods']) || $r['apply_goods'][0]=='_ALL_') && (in_array($shop_id,$r['shop_id']) || $r['shop_id'][0]=='_ALL_')) {

                if ($r['style'] == 'fix') {
                    $this->reguUpdateFilter['id'][] = $r['id'];
                }
                
                /*
                if (isset($this->stock_quantity[$r['id']][$product_id]) && $this->stock_quantity[$r['id']][$product_id] >= 0) {
                    $quantity = (int)$this->stock_quantity[$r['id']][$product_id];
                    break;
                }*/

                # 判断是否满足规则
                $params = array(
                    'shop_product_bn' => $pbn,
                    'shop_bn'             => $shop_bn,
                    'shop_id'              => $shop_id,
                );
                foreach ($r['regulation']['content']['filters'] as $filter) {
                    $allow_update = $this->check_condition($filter,$params);

                    if(!$allow_update){ continue 2;}
                }

                if ($r['regulation']['content']['stockupdate'] != 1) { return false;}

                $quantity = kernel::single('inventorydepth_stock')->formulaRun($r['regulation']['content']['result'],$params,$msg);

                if ($quantity === false){ continue; }

                //$this->stock_quantity[$r['id']][$product_id] = $quantity;
                
                $apply_regulation = $r['regulation'];
                break;
            }
        }

        return is_null($quantity) ? false : $quantity;
    }

    public function set_stock_quantity($shop_id,$key,$data) {
        $this->stock_quantity[$shop_id][$data['product_id']] = $data['quantity'];
    }

    /**
     * @description 获取指定店铺的所有规则
     * @access public
     * @param void
     * @return void
     */
    public function getRegu($shop_id) {
        if(!$this->regu) {
            $filter = array(
                'start_time|sthan' =>self::$now,
                'end_time|bthan' =>self::$now,
                'using' =>'true',
                'al_exec' => 'false',
                'condition' => 'stock',
                'filter_sql' => "(shop_id='_ALL_' || FIND_IN_SET('{$shop_id}',shop_id) )",
            );
            $this->regu = $this->app->model('regulation_apply')->getList('*',$filter,0,-1,'type desc,priority desc');

            foreach($this->regu as $key=>$value){
                $this->regu[$key]['shop_id'] = explode(',',$value['shop_id']);
                $this->regu[$key]['apply_goods'] = explode(',',$value['apply_goods']);
                $this->regu[$key]['apply_pkg'] = explode(',',$value['apply_pkg']);
                $this->regu[$key]['regulation'] = &$regulation[$value['regulation_id']];
            }

            if($regulation){
                $rr = $this->app->model('regulation')->getList('*',array('regulation_id'=>array_keys($regulation),'using'=>'true'));
                foreach($rr as $r){
                    $regulation[$r['regulation_id']] = $r;
                }
            }
        }

        return $this->regu;
    }

    /**
     * @description 获取真正执行的规则
     * @access public
     * @param void
     * @return void
     */
    public function getExecRegu($pbn,$shop_id,$shop_bn) 
    {
        $product = kernel::single('inventorydepth_stock_products')->fetch_products($pbn);
        if(!$product) return '';
        $product_id = $product['product_id'];

        $regu = $this->getRegu($shop_id);
        foreach($regu as $r){
            if(empty($r['regulation'])) continue;

            if((in_array($product_id,$r['apply_goods']) || $r['apply_goods'][0]=='_ALL_') && (in_array($shop_id,$r['shop_id']) || $r['shop_id'][0]=='_ALL_')) {

                # 判断是否满足规则
                //$valid = $this->valid();
                $params = array(
                    'shop_product_bn' => $pbn,
                    'shop_bn'         => $shop_bn,
                    'shop_id'         => $shop_id,
                );
                foreach ($r['regulation']['content']['filters'] as $filter) {
                    $allow_update = $this->check_condition($filter,$params);

                    if(!$allow_update){ continue 2;}
                }
                
                $exec_regu = array(
                    'regulation_id' => $r['regulation']['regulation_id'],
                    'heading' => $r['regulation']['heading'],
                );

                break;
            }
        }

        return $exec_regu;
    }

    /**
     * @description 获取5分钟内库存变更的货品
     * @access public
     * @param void
     * @return void
     */
    public function getChgProducts()
    {
        base_kvstore::instance('inventorydepth/apply')->fetch('read_store_lastmodify',$read_store_lastmodify);
        if (!$read_store_lastmodify || $read_store_lastmodify>self::$now) {
            $read_store_lastmodify = self::$now-self::intervalTime;
            base_kvstore::instance('inventorydepth/apply')->store('read_store_lastmodify',$read_store_lastmodify);
        }

        $filter = array(
            'max_store_lastmodify|between' => array(
                0 => $read_store_lastmodify,
                1 => self::$now,
            )
        );
        $products = $this->app->model('products')->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',$filter);

        return $products;
    }

}