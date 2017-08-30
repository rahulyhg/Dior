<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class wms_event_receive_otherinstorage extends wms_event_response{

    /**
     * 其他入库操作后变更其他入单状态
     */
    public function setStatus(){

    }

    public function create($data){
        #error_log('other:'.var_export($data,1),3,__FILE__.".log");
        return $this->send_succ();
    }

    public function updateStatus($data){
        return $this->send_succ();
    }
}

?>
