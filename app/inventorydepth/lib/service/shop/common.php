<?php
/**
* 商品处理抽象类
* 
* chenping<chenping@shopex.cn>
*/
abstract class inventorydepth_service_shop_common
{
    public $approve_status = array(
            //array('filter'=>array('approve_status'=>'onsale'),'name'=>'在架','flag'=>'onsale'),
            //array('filter'=>array('approve_status'=>'instock'),'name'=>'下架','flag'=>'instock'),
            array('filter'=>array('approve_status'=>'all'),'name'=>'全部','flag'=>'all'),
    );
    
    public $totalResults = 0;
    
    function __construct(&$app)
    {
        $this->app = $app;
    }
    
    /**
     * 获取上下架状态
     *
     * @return void
     * @author 
     **/
    public function get_approve_status($flag='',&$exist=false)
    {
        if (isset($this->approve_status[$flag])) {
            $exist = true;
            return $this->approve_status[$flag];
        }
        return $this->approve_status;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function getTotalResults() 
    {
        return $this->totalResults;
    }

    /**
     * 下载全部商品(不包含SKU)
     *
     * @return void
     * @author 
     **/
    public function downloadListNOSku($filter,$shop_id,$offset=0,$limit=200,&$errormsg) 
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');
        
        # 请求商品
        $result = $shopService->items_all_get($filter,$shop_id,$offset,$limit);
        if($result === false){ 
            $errormsg = $shopService->get_err_msg();
            return false;
        }

        # 数据为空
        if(empty($result['items']['item'])){
            $this->totalResults = 0;
            return array();
        }
        
        $this->totalResults = $result['totalResults'];

        return $result['items']['item'];
    }

    /**
     * 下载全部商品(包含SKU)
     *
     * @return void
     * @author 
     **/
    public function downloadList($filter,$shop_id,$offset=0,$limit=200,&$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');

        # 请求商品
        $result = $shopService->items_all_get($filter,$shop_id,$offset,$limit);
        if($result === false){ 
            $errormsg = $shopService->get_err_msg();
            return false;
        }
        
        # 数据为空
        if(empty($result['items']['item'])){
            $this->totalResults = 0;
            return array();
        }
        
        $this->totalResults = $result['totalResults'];
        
        $data = array();$time = time();
        foreach ($result['items']['item'] as $value) {
             $item = $shopService->item_get($value['iid'],$shop_id);
             if ($item === false){ 
                 $errormsg[] = $value['iid'].'：'.$shopService->get_err_msg();
                 continue;
             } 
             if(empty($item['item'])){
                $errormsg[] = $value['iid'].'不存在！';
                 continue;
             }

             $data[] = $item['item'];
        }
        unset($result,$item);
        
        return $data;
    }

    /**
     * 通过IID批量下载
     *
     * @return void
     * @author 
     **/
    public function downloadByIIds($iids,$shop_id,&$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');
        # 请求商品
        $data = array();
        foreach($iids as $iid){
             $item = $shopService->item_get($iid,$shop_id);
             if ($item === false) {
                $errormsg[] = $iid.'：'.$shopService->get_err_msg();
                continue;
             }
             
             if(empty($item['item'])){
                $errormsg[]  = $iid.'不存在！';
                continue;
             }

             $data[] = $item['item'];
        }
        unset($item);

        return $data;
    }
    
    /**
     * 通过IID下载 单个
     *
     * @return void
     * @author 
     **/
    public function downloadByIId($iid,$shop_id,&$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');
         $item = $shopService->item_get($iid,$shop_id);
         if ($item === false) {
            $errormsg = $shopService->get_err_msg();
            return false;
         }
        
        # 空数据
        if(empty($item['item'])){
            $errormsg = $iid.'不存在！';
            return array();
        }

         return $item['item'];
    }

    /**
     * 通过SKU_ID下载,单个
     *
     * @param Array $sku SKU信息
     * @param String $shop_id 店铺ID
     * @param String $errormsg 错误信息
     * @return void
     * @author
     **/
    public function dowloadBySkuId($sku,$shop_id,&$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_skus');
        $result = $shopService->item_sku_get($sku,$shop_id);

         if ($result === false) {
            $errormsg = $shopService->get_err_msg();
            return false;
         }

         if(empty($result['sku'])){
            $errormsg = 'SKU不存在！';
            return array();
         }

        return json_decode($result['sku'],true);
    }

    /**
     * 批量上下架 异步
     *
     * @return void
     * @author 
     **/
    public function doApproveBatch($approve_status,$shop_id,$check_status=true)
    {
        $request = kernel::single('inventorydepth_shop')->getFrameConf($shop_id);

        if($check_status == true && $request !== 'true'){ 
            $msg = $this->app->_('店铺上下架功能未开启');
            return false;
        }

        kernel::single('inventorydepth_rpc_request_shop_frame')->approve_status_list_update($approve_status,$shop_id);
    }

    /**
     * 单个上下架 同步
     *
     * @return void
     * @author 
     **/
    public function doApproveSync($approve,$shop_id,&$msg)
    {
        $result = kernel::single('inventorydepth_rpc_request_shop_frame')->approve_status_update($approve,$shop_id);

        if ($result === false) {
            $msg = $this->app->_('请求超时!');
            return false;
        }

        $approve_status = ($approve['approve_status'] == 'onsale') ? '上架' : '下架';

        if ($result->rsp == 'succ') {
            $msg = $approve_status.'成功';
            return true;
        }else{
            $msg = $approve_status.'失败';
            return false;
        }
    }
}