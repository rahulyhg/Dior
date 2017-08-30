<?php
/**
 * 发货单合并条件md5值service
 * 有关发货单合并条件md5值方面的扩展功能都可以使用此服务
 * @author Chris.Zhang
 * @package ome_service_delivery_bindkey
 * @copyright www.shopex.cn 2011.03.23
 *
 */
class ome_service_delivery_bindkey{
    
    /**
     * 获取合并条件值
     * @param sdf $sdf
     * @return string md5
     */
    public function get_bindkey($sdf){
        $bindkey = md5($sdf['shop_id'].$sdf['branch_id'].$sdf['consignee']['addr'].$sdf['member_id'].$sdf['is_cod'].$sdf['is_protect']);
        return $bindkey;
    }
    
}