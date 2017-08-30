<?php
class inventorydepth_batchframe {
    const EXPIRED_TIME = 1800;
    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function is_expired(&$downloadTime='') 
    {  
        base_kvstore::instance('inventorydepth/batchframe')->fetch('downloadTime'.$_SESSION['shop_id'],$downloadTime);
        if ($downloadTime) {
            $result = $downloadTime > (time()-self::EXPIRED_TIME) ? false : true;

            return $result;
        } else {
            return true;
        }
    }
}
