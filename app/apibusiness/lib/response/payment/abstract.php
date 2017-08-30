<?php
/**
* 支付单 抽象类
*
* @category apibusiness
* @package apibusiness/response/payment
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_payment_abstract
{
    protected $_respservice = null;

    protected $_tgver = '';

    public $_apiLog = array();

    public $_paymentsdf = array();

    protected $_shop = array();

    const _APP_NAME = 'ome';

    public function __construct($paymentsdf)
    {
        $this->_paymentsdf = $paymentsdf;
    }

    /**
     * 添加支付单
     *
     * @return void
     * @author 
     **/
    public function add()
    {
        $this->_apiLog['title']  = '前端店铺支付业务处理接口[订单：' . $this->_paymentsdf['order_bn'].']';
        $this->_apiLog['info'][] = '接收参数 ：' . var_export($this->_paymentsdf, true);
        $this->_apiLog['info'][] = '前端店铺信息：'.var_export($this->_shop,true);
        $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver;
    }

    /**
     * 更新支付单状态
     *
     * @return void
     * @author 
     **/
    public function status_update()
    {
        $this->_apiLog['title']  = '更新付款单状态接口[订单：' . $this->_paymentsdf['order_bn'].']';
        $this->_apiLog['info'][] = '接收参数：' . var_export($this->_paymentsdf, true);
        $this->_apiLog['info'][] = '前端店铺信息：'.var_export($this->_shop,true);
        $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver;
    }

    /**
     * 响应对象设置
     *
     * @return Object
     * @author 
     **/
    public function setRespservice($respservice)
    {
        $this->_respservice = $respservice;
        return $this;
    }

    /**
     * 淘管中对应版本
     *
     * @return Object
     * @author 
     **/
    public function setTgVer($tgver)
    {
        $this->_tgver = $tgver;
        return $this;
    }

    /**
     * 店铺信息
     *
     * @return void
     * @author 
     **/
    public function setShop($shop)
    {
        $this->_shop = $shop;

        return $this;
    }

    /**
     * 异常处理
     *
     * @return void
     * @author 
     **/
    protected function exception($fun,$retry='false')
    {
        $logModel = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $logModel->gen_id();
        $logModel->write_log($log_id,
                             $this->_apiLog['title'],
                             get_class($this), 
                             $fun, 
                             '', 
                             '', 
                             'response', 
                             'fail', 
                             implode('<hr/>',$this->_apiLog['info']),
                             '',
                             'api.store.trade.payment',
                             $this->_paymentsdf['order_bn']);

        $data = array('tid'=>$this->_paymentsdf['order_bn'],'payment_id'=>$this->_paymentsdf['payment_bn'],'retry'=>$retry);

        $this->_respservice->send_user_error($this->_apiLog['info']['msg'],$data);
        exit;
    }
}