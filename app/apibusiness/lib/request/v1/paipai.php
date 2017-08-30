<?php
/**
* paipai(拍拍平台)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
* @author chenping<chenping@shopex.cn>
* @version $Id: paipai.php 2013-13-12 14:44Z
*/
class apibusiness_request_v1_paipai extends apibusiness_request_partyabstract
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
        $params = array(
            'tid'               => $delivery['order']['order_bn'],
            'company_code' => trim($delivery['dly_corp']['type']),
            'logistics_no' => $delivery['logi_no'],
        );

        return $params;
    }// TODO TEST


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