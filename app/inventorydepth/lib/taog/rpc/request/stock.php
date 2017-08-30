<?php
/**
* 更新库存RPC接口实现
*
* @author chenping<chenping@shopex.cn>
* @version 2012-5-30 18:04
*/
class inventorydepth_taog_rpc_request_stock extends ome_rpc_request
{
    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function __construct() 
    {
        $this->app = $app;

        $this->router = kernel::single('apibusiness_router_request');
    }

    /**
     * 前端店铺更新库存
     *
     * @param Array $stocks 矩阵更新库存结构
     * @param String $shop_id 店铺ID
     *
     **/
    public function items_quantity_list_update($stocks,$shop_id,$dorelease = false)
    {

        if(empty($stocks)) return false;
        
        $this->router->setShopId($shop_id)->update_stock($stocks,$dorelease);
        /*
        $skuIds = array_keys($stocks);

        sort($stocks);

        //保存库存同步管理日志
         $oApiLogToStock = kernel::single('ome_api_log_to_stock');
         $oApiLogToStock->save($stocks,$shop_id);

        //待更新库存BN
        $params['list_quantity'] = json_encode($stocks);
        //附加参数
        $addon = array('all_list_quantity'=>json_encode($stocks));
        $addon['dorelease'] = $dorelease;

        $callback = array(
            'class' => 'inventorydepth_taog_rpc_request_stock',
            'method' => 'items_quantity_list_update_callback',
            'shop_id' => $shop_id,
        );

        if($shop_id){
            $shop_info = app::get('ome')->model('shop')->dump($shop_id,'name');
            $title = '批量更新店铺('.$shop_info['name'].')的库存(共'.count($stocks).'个)';
        }else{
            $title = '更新库存';
        }
        $api_name = 'store.items.quantity.list.update';
        $return = $this->request($api_name,$params,$callback,$title,$shop_id,10,false,$addon);
        if ($return !== false){
            if ($dorelease === true) {
                if ($skuIds) {
                    app::get('inventorydepth')->model('shop_adjustment')->update(array('release_status'=>'running'),array('id'=>$skuIds));
                }
            }

            app::get('ome')->model('shop')->update(array('last_store_sync_time'=>time()),array('shop_id'=>$shop_id));
        }*/
    }

    /**
     * @description 更新库存回调
     * @access public
     * @param void
     * @return void
     */
    public function items_quantity_list_update_callback($result)
    {
        $callback_params = $result->get_callback_params();
        $status = $result->get_status();
        $res = $result->get_result();
        $data = $result->get_data();
        $request_params = $result->get_request_params();
        $msg_id = $result->get_msg_id();

        $request_params['all_list_quantity'] = $request_params['list_quantity'];
        $log_params = array('store.items.quantity.list.update',$request_params,array(get_class($this),'items_quantity_list_update_callback',$callback_params));


        $log_id = $callback_params['log_id'];
        $oApi_log = &app::get('ome')->model('api_log');

        $rsp = 'succ';
        if ($status != 'succ' && $status != 'fail' ){
            $res = $status . ome_api_func::api_code2msg('re001', '', 'public');
            $rsp = 'fail';
        }

        if($status == 'succ'){
            $api_status = 'success';
        }else{
            $api_status = 'fail';
        }

        //更新失败的bn会返回，然后下次retry时，只执行失败的bn更新库存
        $err_item_bn = $data['error_response'];
        $addon['error_lv'] = $data['error_level'];//错误等级
        if (!is_array($err_item_bn)){
            $err_item_bn = json_decode($data['error_response'],true);
        }

        //$log_info = $oApi_log->dump($log_id);
        //调整通过缓存读取请求参数

        //$log_params = $request_params;
        //unserialize($log_info['params']);

        $itemsnum = json_decode($log_params[1]['list_quantity'],true);

        $adjustmentModel = app::get('inventorydepth')->model('shop_adjustment');
        $new_itemsnum = $true_itemsnum = array();
        foreach($itemsnum as $k=>$v){
            if(in_array($v['bn'],$err_item_bn)){
                $new_itemsnum[] = $v;
            } else {
                $true_itemsnum[] = $v;
                $adjustmentModel->update(array('shop_stock'=>$v['quantity']),array('shop_id'=>$callback_params['shop_id'],'shop_product_bn'=>$v['bn']));
            }
            /*
            else{
                $success_item_bn = $v['bn'];
                # 更新店铺库存
                //$adjustmentModel->update(array('shop_stock'=>$v['quantity']),array('shop_id'=>$callback_params['shop_id'],'shop_product_bn'=>$v['bn']));
            }*/
        }

        if ($err_item_bn) {
            $adjustmentModel->update(array('release_status'=>'fail'),array('shop_id'=>$callback_params['shop_id'],'shop_product_bn'=>$err_item_bn));
        }


        if ($data['true_bn']) {
            $adjustmentModel->update(array('release_status'=>'success'),array('shop_id'=>$callback_params['shop_id'],'shop_product_bn'=>$data['true_bn']));
        }



        //当返回失败且BN为空时不更新list_quantity
        if ($api_status != 'fail' || $new_itemsnum){
            $log_params[1]['list_quantity'] = json_encode($new_itemsnum);
        }else{
            $new_itemsnum = $itemsnum;
        }

        if ($data['error_bn'] || $data['no_bn']) {
            if ($data['error_bn']) {
                $msg[] = '更新失败货号【'.implode(',', $data['error_bn']).'】';
            }
            if ($data['no_bn']) {
                $msg[] = '无效货号【'.implode(',', $data['no_bn']).'】';
            }
            $msg = $res.':<br/>'.implode('<br/>',$msg);
        } elseif($status == 'succ') {
            $msg = '成功';
        } else {
            $msg = '失败';
        }

        $oApi_log->update_log($log_id,$msg,$api_status,$log_params,$addon);
        /*
        if(isset($log_params)){
            $oApi_log->update_log($log_id,$msg,$api_status,$log_params,$addon);
        }else{
            $oApi_log->update_log($log_id,$msg,$api_status,null,$addon);
        }*/
        //$log_detail = $oApi_log->dump($log_id, 'msg_id,params');
        $log_detail = array(
            'msg_id' => $msg_id,
            'params' => serialize($log_params),
        );

        //更新库存同步管理的执行状态
        $oApiLogToStock = kernel::single('ome_api_log_to_stock');
        if ($true_itemsnum) {
            $oApiLogToStock->save_callback($true_itemsnum,'success',$callback_params['shop_id'],$res,$log_detail);
        }
        if ($new_itemsnum) {
            $oApiLogToStock->save_callback($new_itemsnum,'fail',$callback_params['shop_id'],$res,$log_detail);
        }

        /*
        if ($data['true_bn']) {

            $skus = app::get('inventorydepth')->model('shop_skus')->getListByCrc32($data['true_bn'],$callback_params['shop_id']);

            $this->updateSkus($skus,$callback_params['shop_id'],$itemsnum);

            $this->updateItems($skus,$callback_params['shop_id']);
        }*/


        return array('rsp'=>$rsp,'res'=>$res,'msg_id'=>$msg_id);
    }

    /**
     * 更新店铺商品;只更新回写成功的
     *
     * @return void
     * @author
     **/
    private function updateItems($skus,$shop_id)
    {
        if(!$skus) return;

        $shop_bn = $skus[0]['shop_bn'];

        # 更新商品上下架状态，店铺库存，实际库存
        $iid = array();
        foreach ($skus as $key => $sku) {
            $id = md5($shop_id.$sku['shop_iid']);
            $iid[$id] = $sku['shop_iid'];
        }
        $filter = array(
            'shop_iid' => $iid,
            'shop_id' => $shop_id,
        );
        $skus = app::get('inventorydepth')->model('shop_skus')->getList('shop_product_bn,shop_iid,bind,shop_stock',$filter);

        $bn = array(); $items = array();
        foreach ($skus as $key => $sku) {
            $bn[] = $sku['shop_product_bn'];

            $items[$sku['shop_iid']][] = $sku;
        }
        unset($skus);

        $products = app::get('inventorydepth')->model('products')->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('bn'=>$bn));
        kernel::single('inventorydepth_stock_products')->writeMemory($products);
        if (app::get('omepkg')->is_installed()) {
            kernel::single('inventorydepth_stock_pkg')->writeMemory($products);
        }

        $stores = $approve_status = array();
        foreach ($items as $shop_iid => $item) {
            $taog_store = $shop_store = 0;
            foreach ($item as $value) {
                if ($value['bind']) {
                    $taog_store += kernel::single('inventorydepth_stock_calculation')->get_pkg_actual_stock($value['shop_product_bn'],$shop_bn,$shop_id);
                } else {
                    $taog_store += kernel::single('inventorydepth_stock_calculation')->get_actual_stock($value['shop_product_bn'],$shop_bn,$shop_id);
                }

                $shop_store += $value['shop_stock'];
            }
            $stores['taog_store'][$shop_iid] = $taog_store > 0 ? $taog_store : 0;
            $stores['shop_store'][$shop_iid] = $shop_store > 0 ? $shop_store : 0;
            $approve_status[$shop_iid] = $shop_store==0 ? 'instock' : 'onsale';
        }

        $filter = array(
            'id' => array_keys($iid),
        );

        $itemModel = app::get('inventorydepth')->model('shop_items');
        $items = $itemModel->getList('*',$filter);
        foreach ($items as &$item) {
            $item['taog_store'] = $stores['taog_store'][$item['iid']];
            $item['shop_store'] = $stores['shop_store'][$item['iid']];
            $item['approve_status'] = $approve_status[$item['iid']];
        }

        $sql = inventorydepth_func::get_replace_sql($itemModel,$items);
        $itemModel->db->exec($sql);
    }

    /**
     * 更新店铺货品;只更新回写成功的
     *
     * @return void
     * @author
     **/
    private function updateSkus($skus,$shop_id,$itemsnum)
    {
        if(!$skus) return;

        $quantity = array();
        foreach ($itemsnum as $key => $value) {
            $quantity[$value['bn']] = $value['quantity'];
        }

        foreach ($skus as &$sku) {
            $sku['shop_stock'] = $quantity[$sku['shop_product_bn']];
            $sku['release_status'] = 'success';
        }

        $skusModel = app::get('inventorydepth')->model('shop_skus');

        $sql = inventorydepth_func::get_replace_sql($skusModel,$skus);
        $skusModel->db->exec($sql);
    }
}