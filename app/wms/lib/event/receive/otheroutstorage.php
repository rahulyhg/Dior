<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class wms_event_receive_otheroutstorage extends wms_event_response{

    /**
     * 其他出库操作后其他出库单状态变更
     */
    public function setStatus(){

    }

    public function create($data){
        #error_log(var_export($data,1),3,__FILE__.'.log');
        return $this->send_succ();
    }

    public function updateStatus($data){
        return $this->send_succ();
    }
}

?>
