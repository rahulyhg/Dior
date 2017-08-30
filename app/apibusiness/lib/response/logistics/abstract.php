<?php
abstract class apibusiness_response_logistics_abstract
{
    protected $_respservice = null;

    protected $_tgver = '';

    public $_apiLog = array();

    const _APP_NAME = 'ome';


    public function __construct($sdf){
        $this->Logisticsdf = $sdf;
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
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($Logisticsdf = array()){
         return true;
    }
    protected function exception($fun,$retry='false'){
        $logModel = app::get('ome')->model('api_log');
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
                             '',
                             $this->LogisticInfo['LogisticCode']);

        $data = array('LogisticCode'=>$this->Logisticsdf['LogisticCode'],'LogisticCode'=>$this->Logisticsdf['LogisticCode'],'retry'=>$retry);
        $this->_respservice->send_user_error($this->_apiLog['info']['msg'],$data);
        exit;
    }
}