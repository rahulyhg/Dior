<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class base_misc_task{

    function week(){

    }

    function minute(){

    }

    function hour(){

    }

    function day(){
        base_kvstore::delete_expire_data();
    }

    function month(){

    }

}
