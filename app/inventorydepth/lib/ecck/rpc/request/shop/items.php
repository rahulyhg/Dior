<?php
/**
* RPC接口实现类
*
* @author chenping<chenping@shopex.cn>
* @version 2012-6-25            
*/
class inventorydepth_ecck_rpc_request_shop_items extends ome_rpc_request
{
    
    function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * 实时下载店铺商品
     *
     * @param Array $filter 筛选条件(approve_status)
     * @param String $shop_id 店铺ID 
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array $items
     **/
    public function items_all_get($filter,$shop_id,$offset=0,$limit=100)
    {
        return ;
    }

    /**
     * 根据IID，实时下载店铺商品
     *
     * @param Array $iids 商品ID(不要超过限度20个)
     * @param String $shop_id 店铺ID 
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array $items
     **/
    public function items_list_get($iids,$shop_id)
    {
        return ;
    }
}