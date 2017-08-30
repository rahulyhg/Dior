<?php
class apibusiness_response_aftersalev2_360buy_v1 extends apibusiness_response_aftersalev2_v1{
    
    
    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($tgOrder=array())
    {
        return parent::canAccept($tgOrder);
    }

    /**
     * 添加售后单
     *
     * @return void
     * @author 
     **/
    public function add(){
        parent::add();
        if (in_array($this->_refundsdf['refund_type'],array('refund','apply'))) {
            $this->refund_add();
        }else{
            $this->aftersale_add();
        }
        
    }

    protected function aftersale_additional($returninfo){

    }
    protected function format_data($sdf){
        if ($sdf['refund_type'] == 'refund') {//新增京东售前退款格式转化
        //0、未审核 1、审核通过2、审核不通过 3、京东财务审核通过 4、京东财务审核不通过 5、人工审核通过 6、拦截并退款 7、青龙拦截成功 8、青龙拦截失败 9、强制关单并退款
            $status = $sdf['status'];
            if (in_array( $status,array('0')) ) {
                $sdf['status'] = 'WAIT_SELLER_AGREE';
                $sdf['refund_type'] = 'apply';
            }else if( in_array($status,array('1','3','5')) ){//申请通过
                $sdf['status'] = 'WAIT_BUYER_RETURN_GOODS';
                $sdf['refund_type'] = 'apply';
            }else if( in_array($status,array('2','4')) ){ //拒绝
                $sdf['status'] = 'SELLER_REFUSE_BUYER';
                $sdf['refund_type'] = 'apply';
            }else if( in_array($status,array('6','9')) ){ //成功
                $sdf['status'] = 'SUCCESS';
            }
            
        }else{
            $item_list = json_decode($sdf['refund_item_list'],true);
            $item_list = $item_list['return_item'];
            foreach ($item_list as $k=>$item ) {
                $item_list[$k]['bn'] = $item['sku_id'];
                $order_bn = $item['oid'];
            }
            $sdf['refund_item_list'] = $item_list;
            $sdf['tid'] = $order_bn;
        }
        
        $sdf['modified'] = $sdf['modified'] ? kernel::single('ome_func')->date2time($sdf['modified']): '';
        return $sdf;
    }

 
}

?>