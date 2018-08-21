<?php
/**
 * Created by PhpStorm.
 * User: D1M
 * Date: 2018/03/13
 * Time: 14:20
 */
class creditorderapi_ctl_service{
    public function index(){
        kernel::single('creditorderapi_service')->process();
    }
}