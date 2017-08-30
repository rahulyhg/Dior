<?php
/**
* weixin(微信小店)订单处理 抽象类
*/
abstract class apibusiness_response_order_wx_abstract extends apibusiness_response_order_abstractbase
{
    /**
     * 是否接收订单
     *
     * @return void
     * @author 
     **/
    /*  protected function canAccept()
    {
        $result = parent::canAccept();
        if ($result === false) {
            return false;
        }

        # 未支付的款到发货订单拒收
        if ($this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['pay_status'] == '0') {
            $this->_apiLog['info']['msg'] = '未支付订单不接收';
            return false;
        }

        return true;
    }   */
    /**
     * 订单转换淘管格式
     *
     * @return void
     * @author
     **/
    public function component_convert()
    {
    
        parent::component_convert();
    
        $this->_newOrder['pmt_goods'] = abs($this->_newOrder['pmt_goods']);
        $this->_newOrder['pmt_order'] = abs($this->_newOrder['pmt_order']);
    }   
    /**
     * 需要更新的组件
     *
     * @return void
     * @author
     **/
    protected function get_update_components()
    {
        $components = array('markmemo','custommemo','marktype');
    
        return $components;
    }     
    /**
     * 纠正微信小店的货号(微信前端打的不对)
     *
     * @return void
     * @author
     **/
    protected function reTransSdf(){
        parent::reTransSdf();
        #修复微信小店支付费用bug
        if(!empty($this->_ordersdf['payinfo']['cost_payment'])){
            if($this->_ordersdf['payinfo']['cost_payment'] == $this->_ordersdf['payed']){
                $this->_ordersdf['payinfo']['cost_payment'] = 0;
            }
        }
    
        if(!$this->_ordersdf['lastmodify']){
            $this->_ordersdf['lastmodify'] = date('Y-m-d H:i:s',time());
        }
        #获取货号
        foreach ($this->_ordersdf['order_objects'] as $objkey => &$object) {
            $product_info   = array();
            $goods_id = $object['shop_goods_id'];
            $product_info  = $this->item_get($goods_id);
            foreach ($object['order_items'] as $k => &$v) {
                #重新获取货号
                if ($product_info && $product_info['skus']) {
                    $v['bn']      = $product_info['skus'][$v['shop_product_id']]['bn'];
                    $object['bn'] = $product_info['skus'][$v['shop_product_id']]['bn'];
                }
            }
        }
    }
    protected function item_get($num_iid){
        static $goods;
        if ($goods[$num_iid]) {
            return $goods[$num_iid];
        }
        $rs = kernel::single('apibusiness_router_request')->setShopId($this->_shop['shop_id'])->item_get($num_iid,$this->_shop['shop_id']);
        if ($rs->rsp == 'fail' || !$rs->data ){
            $this->_apiLog['info'][] = '获取商品详细('.$rs->msg_id.')失败：' . $num_iid;
            return array();
        }
    
        $data = json_decode($rs->data,true);
        $this->_apiLog['info'][] = '获取商品详细('.$rs->msg_id.')成功：' . $num_iid;
    
        if ($rs->rsp == 'succ' && $data) {
            $item = $data['item'];
            unset($data);
    
            if ($item['skus']['sku']) {
                foreach ($item['skus']['sku'] as $key => $value) {
                    $goods[$num_iid]['skus'][$value['sku_id']]['sku_id'] = $value['sku_id'];
                    $goods[$num_iid]['skus'][$value['sku_id']]['bn'] = $value['bn'];
                }
            }
        }
        return $goods[$num_iid];
    }        
}