<?php
/**
* 订单插件
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: member.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_member extends apibusiness_response_order_plugin_abstract
{
    const _APP_NAME = 'ome';
    /**
     * 订单保存前，会员信息操作
     *
     * @return void
     * @author 
     **/
    public function preCreate()
    {
        // 保存前端店铺会员信息
        unset($this->_platform->_newOrder['member_id']);
        
        $member_id = kernel::single('ome_member_func')->save($this->_platform->_ordersdf['member_info'],$this->_platform->_shop['shop_id']);
        if ($member_id) {
            $this->_platform->_newOrder['member_id'] = $member_id;
            
            // 订单批次索引号
            if ($service = kernel::servicelist('service.order')){
                foreach ($service as $object => $instance){
                    if (method_exists($instance, 'order_job_no')){
                        $this->_platform->_newOrder['order_job_no'] = $instance->order_job_no($this->_platform->_newOrder, 'get');
                    }
                }
            }

            $member = $this->_platform->_ordersdf['member_info'];
            $member['member_id'] = $member_id;
            $this->_platform->_apiLog['info'][] = '会员标准$sdf结构：'.var_export($member,true);
        }
    }

    /**
     * 订单更新前操作
     *
     * @return void
     * @author 
     **/
    public function preUpdate()
    {
        $member_id = kernel::single('ome_member_func')->save(
            $this->_platform->_ordersdf['member_info'],
            $this->_platform->_shop['shop_id'],
            $this->_platform->_tgOrder['member_id'],
            $old_member
            );

        if ($member_id != $this->_platform->_tgOrder['member_id']) {
            $this->_platform->_newOrder['member_id'] = $member_id;

            $logModel = app::get(self::_APP_NAME)->model('operation_log');
            $logModel->write_log('order_edit@ome',$this->_platform->_tgOrder['order_id'],'修改订单会员信息');
        }

        // 更新前的会员信息
        $this->_platform->_tgOrder['mem_info']      = $old_member;
        $this->_platform->_tgOrder['mem_uname']     = $old_member['uname'];
        $this->_platform->_tgOrder['mem_name']      = $old_member['name'];
        $this->_platform->_tgOrder['mem_telephone'] = $old_member['tel'];
        $this->_platform->_tgOrder['mem_mobile']    = $old_member['mobile'];
        $this->_platform->_tgOrder['mem_email']     = $old_member['email'];
        $this->_platform->_tgOrder['mem_zipcode']   = $old_member['zip'];
        $this->_platform->_tgOrder['mem_area']      = $old_member['addr'];

    }
}