<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class promotion_finder_orders{
    var $column_control = '操作';
    
    public function __construct($app) {
        $this->app = $app;
    }
    
    
    function column_control($row){
        return '<a href="index.php?app=promotion&ctl=admin_orders&act=edit&p[0]='.$row['rule_id'].'&finder_id='.$_GET['_finder']['finder_id'].'" target="_blank">'.app::get('b2c')->_('编辑').'</a>';
    }
    
}
?>
