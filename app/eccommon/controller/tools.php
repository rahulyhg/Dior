<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class eccommon_ctl_tools extends desktop_controller{

    function __construct($app) {
        parent::__construct($app);
		header("cache-control: no-store, no-cache, must-revalidate");
        $this->app = $app;
    }

    //该方法desktop框架js会调用掉
    function selRegion()
    {
        $path = $_GET['path'];
        $depth = $_GET['depth'];

        $local = kernel::single('eccommon_regions_select');
        $ret = $local->get_area_select($path,array('depth'=>$depth));
        if($ret){
            echo '&nbsp;-&nbsp;'.$ret;exit;
        }else{
            echo '';exit;
        }
    }
}