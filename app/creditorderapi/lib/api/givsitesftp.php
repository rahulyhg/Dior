<?php
/**
 * Created by PhpStorm.
 * User: D1M_zzh
 * Date: 2018/4/25
 * Time: 11:55
 */
class creditorderapi_api_givsitesftp extends creditorderapi_api_sitesftp{

    /**
     * 价格文件更新超期提醒
     * @param string $shop_id
     * @return bool
     */
    public function crontab_update_price($shop_id = ''){

        if(empty($shop_id)){
            return false;
        }

        return true;
    }

}