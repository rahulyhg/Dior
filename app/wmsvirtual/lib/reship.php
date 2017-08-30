<?php

class wmsvirtual_reship
{
    
    /**
     * 退货结果.
     * @param   
     * @return  array
     * @access  public
     * @author  sunjing@shopex.cn
     */
    function result($result,$node_id)
    {
        $data = $this->format_data($result);
        $method='wms.reship.status_update';
        kernel::single('wmsvirtual_response')->dispatch('wms',$method,&$data,$node_id);
    }

    
    /**
     * 格式化数据
     * @param 
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function format_data($result)
    {
         $data = array(
            'reship_bn'=>'201405141613000643',
            'logistics'=>'',
            'logi_no'=>'',
            'warehouse'=>'ILC-SH',
            'status'=>'FINISH',
            'remark'=>'',
            'operate_time'=>'2014-05-13 00:00:00',
            'item'=>'[{"product_bn":"6957133865867","normal_num":"0","defective_num":"1"}]',
        );
        return $data;
    }
} 

?>