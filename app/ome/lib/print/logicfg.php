<?php
/**
*关联快递单号规则处理类
*
* @author sunjing<sunjing@shopex.cn>
* @version 2013-1-15 15:01
*/
class ome_print_logicfg{

    function __construct(&$app)
    {
        $this->app = $app;
    }


    function getLogiCfg(){
        $params=array(
            'POSTB'=>array(0=>'第一代',1=>'第二代(末位自增)'),
            'EMS'=>array(0=>'13年2月1号之前面单',1=>'13年2月1号之后面单'),
            'ZJS'=>array(0=>'旧版',1=>'新版'),
         );
        return $params;
        
    }
}

?>