<?php
/**
* 淘宝赠品插件
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: tbgift.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_cod extends apibusiness_response_order_plugin_abstract
{
    /**
     * 订单完成后处理
     *
     * @return void
     * @author 
     **/
    public function postCreate()
    {
        if('true' == $this->_platform->_ordersdf['shipping']['is_cod']){
            $orderExtendObj = app::get('ome')->model('order_extend');
            $extendInfo = array();

            $shop_type = $this->_platform->_ordersdf['shop_type'];

            if ( in_array($shop_type, array('vjia','360buy','dangdang','yihaodian')) ) {
                $otherList = json_decode($this->_platform->_ordersdf['other_list'],true);
                foreach($otherList as $val){
                    if($val['type']=='unpaid'){
                        $unpaidprice = $val['unpaidprice'];
                        break;
                    }
                }

                $extendInfo['receivable'] = (isset($unpaidprice)) ? $unpaidprice : ($this->_platform->_ordersdf['total_amount'] - $this->_platform->_ordersdf['payed']);
            }else{
                $extendInfo['receivable'] = $this->_platform->_ordersdf['total_amount'];
            }
            $extendInfo['order_id'] = $this->_platform->_newOrder['order_id'];
            $orderExtendObj->insert($extendInfo);
            $this->_platform->_apiLog['info'][] = '货到付款应收款$sdf结构：'.var_export($extendInfo,true);
        }
    }
   #货到付款订单，更新应收金额
   public function postUpdate(){
       if('true' == $this->_platform->_ordersdf['shipping']['is_cod']){
           $shopex_list = ome_shop_type::shopex_shop_type();
           #目前只处理易开店订单
           if (in_array($this->_platform->_ordersdf['shop_type'],$shopex_list)) {
               $extendInfo = array();
               $orderExtendObj = &app::get('ome')->model('order_extend');
               $extendInfo['receivable'] = $this->_platform->_ordersdf['total_amount'];
               $filter['order_id'] = $this->_platform->_tgOrder['order_id'];
               #检查是否存在
               $count = $orderExtendObj->count(array('order_id'=>$this->_platform->_tgOrder['order_id']));
               if($count > 0){
                   $orderExtendObj->update($extendInfo,$filter);
               }else{
                   $_data['receivable'] =  $extendInfo['receivable'];
                   $_data['order_id'] =  $this->_platform->_tgOrder['order_id'];
                   $orderExtendObj->insert($_data);
               }
               $extendInfo['order_id'] = $this->_platform->_tgOrder['order_id'];
               $this->_platform->_apiLog['info'][] = '货到付款应收款$sdf结构：'.var_export($extendInfo,true);
           }
           #货到付款订单,处理销售单上的付款时间
           $paytime = $this->_platform->_newOrder['paytime'];
           $ship_status = $this->_platform->_tgOrder['ship_status'];
           if((!empty($paytime)) && ($ship_status == '1')){
               $objsales = &app::get('ome')->model('sales');
               #检查销售单是否存在
               $sale_id = $objsales->getList('sale_id',array('order_id'=>$this->_platform->_tgOrder['order_id']));
               if(!empty($sale_id)){
                   $objsales->update(array('paytime'=>$paytime),array('order_id'=>$this->_platform->_tgOrder['order_id']));
               }
           }   
       }   
   }
}