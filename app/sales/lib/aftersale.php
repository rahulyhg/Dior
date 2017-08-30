<?php
/**
 * 生成售后单
 * @package default
 * @author 
 **/
class sales_aftersale{
   
   public $aftersale_type = array(
       'refund' => 'refund',
       'return' => 'change',
       'change' => 'change',
       'refuse'=>'refuse',
   );
   public $return_product_type=array(
            'return' => 'RETURN_STORAGE',
            'change'=>'RE_STORAGE',
            'refund'=>'SALE_REFUND',
   );
   function generate_aftersale($id,$type){

      if(in_array($type,array_keys($this->aftersale_type))){

          $obj_aftersale = kernel::single('sales_aftersale_type_'.$this->aftersale_type[$type]);
         
          if( is_object($obj_aftersale) && method_exists($obj_aftersale,'generate_aftersale') ){
  
              $data = $obj_aftersale->generate_aftersale($id);
              
              if($data === false){
                  return true;
              }else{
                  $Oaftersale = &app::get('sales')->model('aftersale');
                  $data['aftersale_bn'] = $Oaftersale->get_aftersale_bn();
                  $result = $Oaftersale->save($data);
                  if ($result) {
                      //往财务对账打数据
                      if(app::get('finance')->is_installed()){
                        $this->_format_finance_data($data);
                      }
                  }
                  return $result;




              }
          }else{
              trigger_error('Type is not recognized',E_USER_ERROR);
              return false;
          }
      }else{
          trigger_error('Type is not recognized',E_USER_ERROR);
          return false;
      }

   }


    
    /**
     * 格式化财务对账数据
     * @param 
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function _format_finance_data($data)
    {
        $obj_aftersale = kernel::single('sales_aftersale_type_change');
        $Oaftersale = &app::get('sales')->model('aftersale');
        $data['sale_bn'] = $data['aftersale_bn'];
        $type = $this->return_product_type['change'];
       
        $sales_items = $data['aftersale_items'];
        if ($sales_items) {
            foreach ($sales_items as $k=>$sale ) {
                $sales_items[$k]['order_id'] = $data['order_id'];
                $sales_items[$k]['order_bn'] = $data['order_bn'];
                $sales_items[$k]['shop_id'] = $data['shop_id'];
                $sales_items[$k]['shop_name'] = $data['shop_name'];
                $sales_items[$k]['name'] = $sale['product_name'];
                $sales_items[$k]['sales_amount'] = $sale['saleprice'];
                $sales_items[$k]['nums'] = $sale['num'];
            }
            unset($data['aftersale_items']);
            $data['sale_amount'] = $data['refund_apply_money'];
            $saledata[$type]['sales'] = $data;
            
            $saledata[$type]['sales']['sales_items'] = $sales_items;
            
            $financeObj = kernel::single('finance_iostocksales');
            $result = $financeObj->do_sales_data($saledata);
        }
        
    }
}
