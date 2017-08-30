<?php
class apibusiness_response_remark_v1 extends apibusiness_response_remark_abstract{
    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($remark= array()){
        return parent::canAccept($remark);
    }
}