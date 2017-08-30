<?php
/**
 *  队列抽象类
 * 
 * @author chenping<chenping@shopex.cn>
 */

abstract class inventorydepth_abstract_queue {

    /**
     * 保存发布队列
     *
     * @param $params Array 货品的过滤条件，沿用FINDER中的FILTER
     * @return void
     * @author 
     **/
    public function insert_release_queue($title,$params)
    {
        $queueData = array(
            'queue_title'=>$title,
            'start_time'=>time(),
            'params'=>$params,
            'worker'=>'inventorydepth_queue.exec_release_queue',
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }

    /**
     * 执行发布队列
     *
     * @param $params Array 货品的过滤条件，沿用FINDER中的FILTER
     * @return void
     * @author 
     **/
    public function exec_release_queue($cursor_id,$params,$errormsg)
    {
        $offset  = $params['offset']; unset($params['offset']);
        $limit   = $params['limit']; unset($params['limit']);
        $operInfo = $params['operInfo'];unset($params['operInfo']);
        $shop_id = $params['shop_id'];

        $optLogModel = app::get('inventorydepth')->model('operation_log');
        $adjustmentModel = app::get('inventorydepth')->model('shop_adjustment');

        $skus = $adjustmentModel->getList('id,shop_product_bn,release_stock',$params,$offset,$limit);
        $memo = array('last_modified'=>time());
        foreach ($skus as $key => $sku) {
            $stocks[$sku['id']] = array(
                'bn' => $sku['shop_product_bn'],
                'quantity' => $sku['release_stock'],
                'memo' => json_encode($memo),
            );

            $optLogModel->write_log('sku',$sku['id'],'stockup','批量发布库存：'.$sku['release_stock'],$operInfo);
        }

        if($stocks){
             kernel::single('inventorydepth_shop')->doStockRequest($stocks,$shop_id,true);
        }
        return false;
    }


    /**
     * 保存发布队列
     *
     * @param $params Array 商品的过滤条件，沿用FINDER中的FILTER
     * @return void
     * @author 
     **/
    public function insert_shop_item_queue($title,$params)
    {
        $queueData = array(
            'queue_title'=>$title,
            'start_time'=>time(),
            'params'=>$params,
            'worker'=>'inventorydepth_queue.exec_shop_item_queue',
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }

    /**
     * @description 执行店铺商品插队列
     * @access public
     * @param $params Array 商品的过滤条件，沿用FINDER中的FILTER
     * @return void
     */
    public function exec_shop_item_queue($cursor_id,$params,$errormsg) 
    {
        $itemsModel = app::get('inventorydepth')->model('shop_items');
        if (is_array($params)) {
            foreach ($params as $param) {
                $itemsModel->saveItem($param['items'],$param['shop']);
            }
        }
        return false;
    }

    /**
     * 保存SKU信息,文件导入发布库存
     *
     * @return void
     * @author 
     **/
    public function insert_shop_skus_queue($title,$params)
    {
        $queueData = array(
            'queue_title'=>$title,
            'start_time'=>time(),
            'params'=>$params,
            'worker'=>'inventorydepth_queue.exec_shop_skus_queue',
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }

    /**
     * @description 执行保存SKU队列
     * @access public
     * @param void
     * @return void
     */
    public function exec_shop_skus_queue($cursor_id,$params,$errormsg) 
    {
        $skuModel = app::get('inventorydepth')->model('shop_skus');
        if (is_array($params)) {
            $stocks = array(); $i = 0;
            foreach ($params as $param) {
                //$skuModel->save($param);
                $stocks[$param['shop_id']][] = array(
                    'bn' => $param['shop_product_bn'],
                    'quantity' => $param['release_stock'],
                );
            }

            foreach($stocks as $shop_id => $stock){
                $new_stock = array_chunk($stock,50);
                foreach ($new_stock as $value) {
                    kernel::single('inventorydepth_shop')->doStockRequest($value,$shop_id);
                }
            }
            
        }
        return false;
    }

    /**
     * 批量上下架，放入队列
     *
     * @param $params Array 商品的过滤条件，沿用FINDER中的FILTER
     * @return void
     * @author 
     **/
    public function insert_approve_queue($title,$params)
    {
        $queueData = array(
            'queue_title'=>$title,
            'start_time'=>time(),
            'params'=>$params,
            'worker'=>'inventorydepth_queue.exec_approve_queue',
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }

    /**
     * 上下架执行队列
     *
     * @return void
     * @author 
     **/
    public function exec_approve_queue($cursor_id,$params,$errormsg)
    {
        # 上下架处理
        $approve_status = $params['do_approve']; unset($params['do_approve']);
        $offset = $params['offset']; unset($params['offset']);
        $limit = $params['limit']; unset($params['limit']);
        $operInfo = $params['operInfo'];unset($params['operInfo']);

        kernel::single('inventorydepth_shop')->doApproveBatch($params,$approve_status,$offset,$limit,$operInfo);
        
        return false; 
    }

    /**
     * @description 批量更新库存进队列,店铺级操作，更新所有货品库存
     * @access public
     * @param void
     * @return void
     */
    public function insert_stock_update_queue($title,$params) 
    {
        $queueData = array(
            'queue_title'=>$title,
            'start_time'=>time(),
            'params'=>$params,
            'worker'=>'inventorydepth_queue.exec_stock_update_queue',
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }

    /**
     * @description 执行更新库存进队列
     * @access public
     * @param $params Array 货品的过滤条件
     * @return void
     */
    public function exec_stock_update_queue($cursor_id,$params,$errormsg) 
    {
        $offset  = $params['offset']; unset($params['offset']);
        $limit   = $params['limit']; unset($params['limit']);
        $shop_id = $params['shop_id'];

        $productModel = app::get('inventorydepth')->model('products');
        $products = $productModel->getList('*',array(),$offset,$limit);
        
        kernel::single('inventorydepth_stock_pkg')->writeMemory($products);
        kernel::single('inventorydepth_stock_products')->writeMemory($products);

        $shopModel = app::get('inventorydepth')->model('shop');
        $shop = $shopModel->select()->columns('*')->where('shop_id=?',$shop_id)->instance()->fetch_row();
        
        foreach ($products as $product) {
            
            $st = kernel::single('inventorydepth_logic_stock')->getStock($product['bn'],$shop['shop_id'],$shop['shop_bn']);
            if ($st === false) { continue; }

            $stocks[] = $st;
        }

        # 捆绑商品
        if (is_array(inventorydepth_stock_pkg::$pkg)) { 
            foreach (inventorydepth_stock_pkg::$pkg as $pkgValue) {
                $st = kernel::single('inventorydepth_logic_pkgstock')->getStock($pkgValue['pkg_bn'],$shop['shop_id'],$shop['shop_bn']);
                if($st === false) { continue; }

                $stocks[] = $st;
            }
        }
        # END

        if($stocks){
            kernel::single('inventorydepth_shop')->doStockRequest($stocks,$shop_id);
        }
        return false;
    }

    /**
     * @description 插入队列共用方法
     * @access public
     * @param void
     * @return void
     */
    public function insert_queue($title,$params,$worker) 
    {
        $queueData = array(
            'queue_title'=>$title,
            'start_time'=>time(),
            'params'=>$params,
            'worker'=>$worker,
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }

}
