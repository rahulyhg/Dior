<?php
/**
* 代销人插件
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: sellingagent.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_sellingagent extends apibusiness_response_order_plugin_abstract
{
    const _APP_NAME = 'ome';

    /**
     * 订单完成后处理
     *
     * @return void
     * @author 
     **/
    public function postCreate()
    {
        if ($this->_platform->_ordersdf['selling_agent']['member_info']['uname']) {
            $selling_agent = $this->_platform->_ordersdf['selling_agent'];
            #代销商发货人和发货地址都必须存在
            if($this->_platform->_ordersdf['seller_address'] && $this->_platform->_ordersdf['seller_name']){
                $seller['seller_name'] = $this->_platform->_ordersdf['seller_name'];#卖家姓名
                $seller['seller_mobile'] = $this->_platform->_ordersdf['seller_mobile'];#卖家电话号码
                $seller['seller_phone'] = $this->_platform->_ordersdf['seller_phone'];#卖家电话号码
                $seller['seller_state'] = $this->_platform->_ordersdf['seller_state'];#卖家的所在省份
                $seller['seller_city'] = $this->_platform->_ordersdf['seller_city'];#卖家的所在城市
                $seller['seller_district'] = $this->_platform->_ordersdf['seller_district'];#卖家的所在地区
                $seller['seller_zip'] = $this->_platform->_ordersdf['seller_zip'];#卖家的邮编
                $seller['seller_address'] = $this->_platform->_ordersdf['seller_address'];#发货人的详细地址
                $selling_agent['seller'] = $seller;
            }
            kernel::single('ome_order_func')->update_sellagent($this->_platform->_newOrder['order_id'],$selling_agent,'create');
            

            $this->_platform->_apiLog['info'][] = '代销人标准$sdf结构：'.var_export($this->_platform->_ordersdf['selling_agent'],true);
        }
    }

    /**
     * 更新代销人
     *
     * @return void
     * @author 
     **/
    public function postUpdate()
    {
        $sellagent_update = kernel::single('ome_order_func')->update_sellagent(
            $this->_platform->_tgOrder['order_id'],
            $this->_platform->_ordersdf['selling_agent']
            );
        if ($sellagent_update) {
            $this->_platform->_apiLog['info'][] = '代销人信息发生变化，$sdf结构：'.var_export($this->_platform->_ordersdf['selling_agent'],true);

            $logModel = app::get(self::_APP_NAME)->model('operation_log');
            $logModel->write_log('order_edit@ome',$this->_platform->_tgOrder['order_id'],'修改代销人信息');
        }
        
        $agentModel = app::get(self::_APP_NAME)->model('order_selling_agent');
        $this->_platform->_tgOrder['agent'] = $agentModel->dump(array('order_id'=>$this->_platform->_tgOrder['order_id']));
    }
}