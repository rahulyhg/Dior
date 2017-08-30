<?php
/**
* qqbuy(QQ网购)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
* @author chenping<chenping@shopex.cn>
* @version $Id: qqbuy.php 2013-13-12 14:44Z
*/
class apibusiness_request_v1_qqbuy extends apibusiness_request_partyabstract
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
            'tid'          => $delivery['order']['order_bn'],
            'company_code' => $delivery['dly_corp']['type'],
            'company_name' => $delivery['logi_name'] ? $delivery['logi_name'] : '',
            'logistics_no' => $delivery['logi_no'] ? $delivery['logi_no'] : '',
            'send_type'    => $delivery['dly_corp']['type'] == 'EMS' ? 3 : 1,
        );

        return $param;
    }

    /**
     * 售后请求
     * @param   array    $returninfo    售后信息
     * @return  
     * @access  protected
     * @author 
     */
    protected function update_aftersale_request($returninfo)
    {

    }

    public function update_order_shippinginfo($order)
    {
        
    }
}