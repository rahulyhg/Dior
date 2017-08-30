<?php
/**
 * 店铺
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_channel_shop extends erpapi_channel_abstract 
{
    public function init($node_id,$shop_id)
    {
        $shopModel = app::get('ome')->model('shop');

        $shop = $shopModel->dump(array('shop_id'=>$shop_id));

        if (!$shop) return false;

        $this->__reqway      = 'matrix';
        
        $this->__reqplatform = $shop['node_type'];

        return true;
    }
}