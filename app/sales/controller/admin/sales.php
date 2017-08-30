<?php
class sales_ctl_admin_sales extends desktop_controller{

     var $name = '单据';
     var $workground = 'invoice_center';

     function index(){
        #增加销售单导出权限
        $is_export = kernel::single('desktop_user')->has_permission('bill_export');
        $this->title = '销售单';        
        //$filter = "sale_bn,iostock_bn,column_originalbn,shop_id,column_shopbn,branch_id,column_branchbn,member_id,sale_amount,discount,delivery_cost,additional_costs,deposit,sale_time,pay_status,operator,memo";
        #$filter = "sale_bn,shop_id,order_id,totalamount,shipping_money,pmtmoney,payed,payment,goods_cost,delivery_cost,delivery_id,order_confirm_id,order_confirm_time,order_time,paytime,ship_time,istax";

        // 发货单号 店铺编号  仓库编号 
        $params = array(
            'title'=>$this->title,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>$is_export,
            'use_buildin_filter'=>true,
            'orderBy'=>'sale_time desc',
            #'finder_cols'=>$filter,
        );
        $this->finder('sales_mdl_sales',$params);
    }

}