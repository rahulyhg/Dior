<?php
abstract class ome_aftersale_abstract{
    const _APP_NAME = 'ome';
    protected $_shop = array();

    /**
     * 设置店铺
     *
     * @return void
     * @author
     **/
    public function setShop($shop)
    {
        $this->_shop = $shop;

        return $this;
    }

    public function pre_return_product_edit($return_id){ return NULL; }

    public function return_product_edit_after($data){ return true; }

    public function return_product_detail($return_id){ return NULL; }

    public function pre_save_refund($apply_id,$data){ return true; }

    public function after_save_refund($data){ return true; }

    public function refund_detail($apply_id){ return NULL; }
    
    public function pre_save_return($data){ return true; }

    
    
    public function after_save_return($data){ return true; }
    /**
     * 是否继续转化单据
     * @param  
     * @return  
     * @access  public
     * @author 
     */
    function choose_type()
    {
        return true;
    }

    /**
    * 转化类型
    */
    function choose_type_value()
    {
        return;
    }
    /**
    * 售后是否向后端发起API请求
    */
    function return_api(){
        return FALSE;
    }

    function refund_button($apply_id,$status){
        $rs = array('rsp'=>'default','msg'=>'','data'=>'');
        return $rs;
    }

    function return_button($return_id,$status){
        $rs = array('rsp'=>'default','msg'=>'','data'=>'');
        return $rs;
    }
    
    
    function reship_edit()
    {
        
    }
}

?>