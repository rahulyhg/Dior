<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class eccommon_misc_task{

    function week(){

    }

    function minute(){
    }

    function hour(){
        kernel::single('eccommon_analysis_task')->analysis_hour();
        kernel::single('eccommon_analysis_task')->analysis_day();
    }

    function day(){

    }

    function month(){

    }
}
