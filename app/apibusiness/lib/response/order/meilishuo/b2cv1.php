<?php
class apibusiness_response_order_meilishuo_b2cv1 extends  apibusiness_response_order_meilishuo_abstract{ 
    /*     
     * 是否接收订单
    * @return void
    * @author
    **/
    protected function canAccept(){
        $result = parent::canAccept();
        if ($result === false) {
            return false;
        }
        #未支付的订单拒收
        if ($this->_ordersdf['pay_status'] == '0') {
            $this->_apiLog['info']['msg'] = '未支付订单不接收';
            return false;
        }
        return true;
    }
            
    /**
     * @return void
     * @author
     **/
    public function canCreate(){
        if ($this->_ordersdf['status'] != 'active') {
            $this->_apiLog['info']['msg'] = ($this->_ordersdf['status'] == 'dead') ? '取消的订单不接收' : '完成的订单不接收';
            return false;
        }
        #美丽说创建订单的时候，未支付订单不接受
        if($this->_ordersdf['pay_status'] != '1'){
            $this->_apiLog['info']['msg'] =  '未支付美丽说订单不接收';
            return false;
        }
    }
    /**
     * 允许更新
     *
     * @return void
     * @author
     **/
    protected function canUpdate(){
        if( $this->_ordersdf['status'] != 'active'){
            $this->_apiLog['info']['msg'] = '取消的订单不接收';
            return false;
        }
        return parent::canUpdate();
    } 
}