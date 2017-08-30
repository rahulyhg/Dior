<?php
/**
* yihaodian(1号店平台)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
* @author chenping<chenping@shopex.cn>
* @version $Id: yihaodian.php 2013-13-12 14:44Z
*/
class apibusiness_request_v1_yihaodian extends apibusiness_request_partyabstract
{
    /**
     * 获取发货参数
     *
     * @param Array $delivery 发货单信息
     * @return Array
     * @author 
     **/
    protected function getDeliveryParam($delivery)
    {
        $param = array(
            'tid'               => $delivery['order']['order_bn'],
            'company_code'      => $delivery['dly_corp']['type'],
            'logistics_company' => $delivery['logi_name'] ? $delivery['logi_name'] : '',
            'logistics_no'      => $delivery['logi_no'] ? $delivery['logi_no'] : '',
        );

        return $param;
    }// TODO TEST

    
    
    protected function aftersale_api($status){
        $api_method = '';
        switch( $status ){
            case '3':
                $api_method = AGREE_RETURN_GOOD;
            break;
            case '4':
                $api_method = CHECK_REFUND_GOOD;
            break;
            case '5':
                $api_method = REFUSE_RETURN_GOOD;
            break;
        }
        return $api_method;
    }

    protected function format_aftersale_params($aftersale,$status){
        $oReturn_yhd = app::get(self::_APP_NAME)->model('return_product_yihaodian');
        $oReturn_items = app::get(self::_APP_NAME)->model('return_product_items');
        $return_yhd = $oReturn_yhd->dump(array('return_bn'=>$aftersale['return_bn']));
        $params = array(
            'refund_id'=>$aftersale['return_bn'],
            
         );
        $return_id = $aftersale['return_id'];
        switch ($status) {
            case '3':
                $items = $oReturn_items->getList('*',array('return_id'=>$return_id),0,-1);
                $return_num = 0;
                $amount = 0;
                foreach($items as $item){
                    $return_num+=$item['num'];
                    $amount+=$item['num'] * $item['price'];
                }
                $params['return_num'] = $return_num;
                $params['amount'] = $amount;
                $params['is_postfee'] = $return_yhd['isdeliveryfee'];
                $params['is_sendtype'] = $return_yhd['sendbacktype'];
                $params['seller_logistics_address_id'] = $return_yhd['isdefaultcontactname'];
                $params['memo'] = '同意退货';
                
                if ($return_yhd['isdefaultcontactname'] == '0') {
                    $params['receiver_name'] = $return_yhd['contactname'];
                    $params['receiver_phone'] = $return_yhd['contactphone'];
                    $params['receiver_address'] = $return_yhd['sendbackaddress'];
                }
                break;
            case '4':
                 
                 break;
            case '5':
                $params['message'] = $aftersale['refuse_message'];
                
                break;
        }
        return $params;
    
    }

    public function update_order_shippinginfo($order)
    {
        
    }

    

}