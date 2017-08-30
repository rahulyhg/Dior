<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class eccommon_view_helper{

    function __construct($app){
        $this->app = $app;
    }
    function modifier_barcode($data){
        return kernel::single('eccommon_barcode')->get($data);
    }
}
