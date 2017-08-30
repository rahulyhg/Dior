<?php
class apibusiness_response_logistics_v1 extends apibusiness_response_logistics_abstract{
    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($Logisticsdf = array()){
        return parent::canAccept($Logisticsdf);
    }
}