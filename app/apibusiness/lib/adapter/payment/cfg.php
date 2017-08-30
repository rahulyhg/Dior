<?php
/**
* 支付方式配置
*
* @category apibusiness
* @package apibusiness/lib/adapter/payment
* @author chenping<chenping@shopex.cn>
* @version $Id: cfg.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_payment_cfg
{
    public $default_pay_bn = array(
        'taobao' => 'alipaytrad',
        'paipai' => 'tenpaytrad',
    );

    private $payment_cfg = array();

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $list = app::get('ome')->model('payment_cfg')->getList($cols, $filter, $offset, $limit, $orderType);

        return $list;
    }

    /**
     * 获取一行
     *
     * @return void
     * @author 
     **/
    public function getRow($filter,$cols = '*')
    {
        $payment_cfg = app::get('ome')->model('payment_cfg')->getList($cols,$filter,0,1);

        return $payment_cfg ? $payment_cfg[0] : array();
    }

    /**
    * 支付方式获取
    * @param String $pay_bn 支付方式编号
    * @param String $shop_type 店铺类型
    * @return Array 支付方式信息
    */
    public function get_payment($pay_bn,$shop_type=''){
        if (!$pay_bn) {
            $pay_bn = $this->default_pay_bn[$shop_type] ? $this->default_pay_bn[$shop_type] : 'online';
        }

        $cfgkey = sprintf('%u',crc32($pay_bn . '-' . $shop_type));
        if($this->payment_cfg[$cfgkey]) return $this->payment_cfg;

        $filter = array('pay_bn' => $pay_bn);
        $payment_cfg = $this->getRow($filter);
        if ($payment_cfg){

           $this->payment_cfg[$cfgkey] = $payment_cfg;

           return $payment_cfg;
        }else{
            $filter = array('pay_bn' => 'online');
            $online = $this->getRow($filter);
            if ($online) {
                $this->payment_cfg[$cfgkey] = $online;

                return $online;
            } else {
                $cfgObj = app::get('ome')->model('payment_cfg');
                $online = array(
                    'custom_name' => '线上支付',
                    'pay_bn'      => 'online',
                    'pay_type'    => 'online',
                );
                $cfgObj->save($online);

                $this->payment_cfg[$cfgkey] = $online;
                
                return $online;
            }
        }
    }
}