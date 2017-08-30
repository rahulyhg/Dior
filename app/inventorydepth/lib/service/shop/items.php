<?php
/**
 * 店铺ITEM,RPC调用类
 * 
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_service_shop_items {

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 实时下载店铺商品
     *
     * @param Array $filter 搜索条件
     * @param String $shop_id 店铺ID
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array 
     */
    public function items_all_get($filter,$shop_id,&$errormsg,$offset=0,$limit=100){

         $result = kernel::single('inventorydepth_rpc_request_shop_items')->items_all_get($filter,$shop_id,$offset,$limit);

        if($result === false){
            $errormsg = $this->app->_('请求失败!');
            return false;
        }elseif ($result->rsp !== 'succ') {
            $errormsg = $result->err_msg;
            return false;
        }

         return json_decode($result->data,true);
    }

    /**
     * 根据IID，实时下载店铺商品
     *
     * @param Array $iids 商品ID(不要超过限度20个)
     * @param String $shop_id 店铺ID 
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array 
     **/
    public function items_list_get($iids,$shop_id,&$errormsg)
    {
        $result = kernel::single('inventorydepth_rpc_request_shop_items')->items_list_get($iids,$shop_id);

        if($result === false){
            $errormsg = $this->app->_('请求失败!');
            return false;
         }elseif ($result->rsp !== 'succ') {
            $errormsg = $this->app->_('请求失败：'.$result->err_msg);
            return false;
         }

         return json_decode($result->data,true);
    }

    /**
     * 获取单个商品明细
     *
     * @param Int $iid商品ID
     * @param String $shop_id 店铺ID 
     * @return void
     * @author 
     **/
    public function item_get($iid,$shop_id,&$errormsg)
    {
        $result = kernel::single('inventorydepth_rpc_request_shop_items')->item_get($iid,$shop_id);

        if ($result === false) {
            $errormsg = $this->app->_('请求失败');
            return false;
        } elseif ($result->rsp !== 'succ') {
            $errormsg = $this->app->_('请求失败：'.$result->err_msg);
            return false;
        }

        return json_decode($result->data,true);
    }
    
}
