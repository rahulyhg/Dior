<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_ctl_admin_service_taobao extends desktop_controller
{
    
    public  $expireDay     = 7; //session过期提醒设置
    
    /**
     * @验证session是否过期
     * @access public
     * @param void
     * @return void/json
     */
    public function validity() {
        $shopInfo = $this->getTaoBaoShop();
        if (count($shopInfo)>0) {
            echo json_encode(array('has_expire'=>true));
        }else{
            return array();
        }
    }

    /**
     * @根据session日期期限弹窗显示的提示信息页
     * @access public
     * @param void
     * @return void
     */
    public function alert() 
    {
        $shopInfo = $this->getTaoBaoShop();
        $this->pagedata['shopInfo'] = $shopInfo;
        $this->display('admin/service/taobaoalert.html');
    }

    /**
     * 获取淘宝session过期的网店 
     *
     * @param  void
     * @return void
     * @author 
     **/
    public function getTaoBaoShop()
    {
        $shopMdl  = app::get('ome')->model('shop');
        $shopInfo = $shopMdl->getList('shop_id,shop_bn,shop_type,name,node_id,addon,node_type',array('node_type'=>'taobao','node_id|nothan'=>''));
        if(count($shopInfo)<1){
            return array();
        }
        foreach ($shopInfo as $key => $shop) {
            $expireDay = $this->getSessionDays($shop);
            if($expireDay>$this->expireDay || $expireDay == 'no_expire_time' ){
                unset($shopInfo[$key]); 
            }else{
                $shopInfo[$key]['expireday'] = $this->toMsg($expireDay);
            }
        }
        if(count($shopInfo)>0){
            return $shopInfo;
        }else{
            return array();
        }
    }

    /**
     * 获取session过期时间    
     *
     * @param  array
     * @return void
     * @author 
     **/
    public function getSessionDays($shop)
    {
        $addon = $shop['addon'];
        if(isset($addon['session_expire_time']) && $addon['session_expire_time']){
            $session_expire_time = strtotime($addon['session_expire_time']);
            $expire_time = $session_expire_time - time();
            $expireDay = ceil($expire_time/86400);
        }else{
            $expireDay='no_expire_time';
        }
        return $expireDay;
    }
    /**
     * @根据服务日期期限显示相应的提示信息
     * @access public
     * @param int $days 剩余的使用天数
     * @return void
     */
    private function toMsg($days) {
        if($days === 0) {
            $msg = '最后一天，请及时续约';
        } elseif($days < 0) {
            $msg = '已过期'. abs($days) . '天';
        } else {
            $msg = '还有'. $days .'天到期';
        }
        return $msg;
    }

}