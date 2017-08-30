<?php
/**
* 
*/
class inventorydepth_shop_skus
{
    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 将字符串做crc32
     *
     * @return void
     * @author 
     **/
    public function crc32($val)
    {
        return sprintf('%u',crc32($val));
    }
}