<?php

class ome_aftersale_service
{
    const _APP_NAME = 'ome';
    
    public function __construct()
    {
        $this->_router = kernel::single('ome_aftersale_request');
    }
    /**
     * 售后申请编辑页面扩展
     * @param   
     * @return 
     * @access  public
     * @author 
     */
    function pre_return_product_edit($returninfo)
    {
        $shop_id = $returninfo['shop_id'];
        $return_id = $returninfo['return_id'];
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        if ($returninfo['source'] == 'matrix' && $shop && $shop['node_id']) {
           $plugin_html = $this->_router->setShopId($shop_id)->pre_return_product_edit($returninfo);
           
            if ($plugin_html && $plugin_html['rsp']!='fail') {
                return $plugin_html;
            }
            
        }        
        
    }

    
    /**
     * 售后申请编辑后扩展
     * @param   
     * @return  
     * @access  public
     * @author 
     */
    function return_product_edit_after($data)
    {
        
        $return_id = $data['return_id'];
        $oReturn_product = &app::get('ome')->model ( 'return_product' );
        $return_product = $oReturn_product->dump($return_id,'shop_id,source');
        $shop_id = $return_product['shop_id'];
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        $source = $return_product['source'];
        $data['shop_id'] = $shop_id;
        if ($source == 'matrix' && $shop && $shop['node_id']) {
            #查询店铺是否绑定
            $result = $this->_router->setShopId($shop_id)->return_product_edit_after($data);
        }
        
          
        
        
     }

    /**
    * 售后申请详情页面扩展
    *
    */
    function return_product_detail($data){
        
        $return_id = $data['return_id'];
        $oReturn_product = &app::get('ome')->model ( 'return_product' );
        $return_product = $oReturn_product->dump($return_id,'shop_id,source');
        $shop_id = $return_product['shop_id'];
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        if ($return_product['source'] == 'matrix' && $shop && $shop['node_id']) {
            
            $result = $this->_router->setShopId($shop_id)->return_product_detail($data);
            if ($result && $result['rsp']!='fail') {
                return $result;
            }
            
        }  
        
        
    }

    /**
     * 保存售后申请状态之前的扩展
     * @param   array    $data
     * @return  array
     * @access  public
     * @author 
     */
    function pre_save_return($data)
    {
        $return_id = $data['return_id'];
        $oProduct = &app::get('ome')->model ( 'return_product' );
        $oPro_detail  = $oProduct->dump ( $return_id, 'shop_id,source' );
        $shop_id = $oPro_detail['shop_id'];
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        if ($oPro_detail['source'] == 'matrix' && $shop && $shop['node_id']) {
            $result = $this->_router->setShopId($shop_id)->pre_save_return($data);

            return $result;
        }
        
       
    }

    
    

    
    /**
     * 保存退款后扩展
     * @param   array    $data
     * @return  
     * @access  public
     * @author 
     */
    function after_save_return($data)
    {
        $apply_id = $data['apply_id'];
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        $shop_id = $refunddata['shop_id'];
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        if ($refunddata['source'] == 'matrix' && $shop && $shop['node_id']) {
            
            $result = $this->_router->setShopId($shop_id)->after_save_return($data);
        }
        
    }

    /**
     * 退款详情页面扩展
     * @param   
     * @return  
     * @access  public
     * @author 
     */
    function refund_detail($data)
    {
        $shop_id = $data['shop_id'];
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        $result = $this->_router->setShopId($shop_id)->refund_detail($data);
        if ($result && $shop && $shop['node_id'] && $result['rsp']!='fail') {
            return $result;
        }
        
    }

    
    /**
     * 保存退款申请前扩展
     * @param   array    $data
     * @return  array
     * @access  public
     * @author 
     */
    function pre_save_refund($apply_id,$data)
    {
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        $shop_id = $refunddata['shop_id'];
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        if ($refunddata['source'] == 'matrix' && $shop && $shop['node_id']) {
            
            $result = $this->_router->setShopId($shop_id)->pre_save_refund($apply_id,$data);
            return $result;
        }
        
    }

    /**
     * 保存退款申请单后的扩展
     * @param   array data
     * @return  
     * @access  public
     * @author 
     */
    function after_save_refund($data)
    {
        $apply_id = $data['apply_id'];
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        $shop_id = $refunddata['shop_id'];
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        if ($refunddata['source'] == 'matrix' && $shop && $shop['node_id']) {
            
            $result = $this->_router->setShopId($shop_id)->after_save_refund($data);
        }
        
    }

    
    /**
     * Short description.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function save_return($status,$data)
    {
        $return_id = $data['return_id'];
        $oProduct = &app::get('ome')->model ( 'return_product' );
        $oPro_detail  = $oProduct->dump ( $return_id, 'shop_id,source' );
        $shop_id = $oPro_detail['shop_id'];
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        if ($oPro_detail['source']== 'matrix' && $shop && $shop['node_id']) {
            
            $result = $this->_router->setShopId($shop_id)->save_return($status,$data);
            return $result;
        }
        
        
    }

    
    /**
     * 保存售后状态时是否发起更新
     * @param   array data
     * @return  bool
     * @access  public
     * @author cyyr24@sina.cn
     */
    function return_api($data){
        $shop_id = $data['shop_id'];
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        
        if ($shop && $shop['node_id']) {
            $result = $this->_router->setShopId($shop_id)->return_api();
            return $result;
        }
        
    }

    
    /**
     * Short description.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function refund_button($apply_id,$status)
    {
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $refund_apply = $oRefund_apply->dump($apply_id,'shop_id,source');
        $shop_id = $refund_apply['shop_id'];
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        if ($refund_apply['source']=='matrix' && $shop && $shop['node_id']) {
            $result = $this->_router->setShopId($shop_id)->refund_button($apply_id,$status);
            return $result;
        }
        
    }
    
    
    /**
     * 售后拒绝按钮
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function return_button($return_id,$status)
    {
        $oReturn = &app::get('ome')->model('return_product');
        $return = $oReturn->dump($return_id,'shop_id,source');
        $shop_id = $return['shop_id'];
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        if ($return['source']=='matrix' && $shop && $shop['node_id']) {
            
            $result = $this->_router->setShopId($shop_id)->return_button($return_id,$status);
            return $result;
        }
        
    }

    /**
    * 质检页面
    */
    function reship_edit($returninfo){
        
        $shop_id = $returninfo['shop_id'];
        $result = $this->_router->setShopId($shop_id)->reship_edit($returninfo);
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->dump($shop_id);
        if ($result && $result['rsp']!='fail' && $shop && $shop['node_id']) {
            return $result;
        }
        
        
    }
    
} 

?>