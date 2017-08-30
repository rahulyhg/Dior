<?php

/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class eccommon_task
{

    public function post_install()
    {
        kernel::log('Initial eccommon');
        kernel::single('base_initial', 'eccommon')->init();

        kernel::log('Initial Regions');
        kernel::single('eccommon_regions_mainland')->install();
    }//End Function
}//End Class
