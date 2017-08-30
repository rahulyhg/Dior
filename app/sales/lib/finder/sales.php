<?php
class sales_finder_sales{

    var $column_detail = 'detail';
    function detail_edit($id){        
        
        #[发货配置]是否启动拆单 ExBOY
        $dlyObj         = &app::get('ome')->model('delivery');
        $split_seting   = $dlyObj->get_delivery_seting();
        
        $render = &app::get('sales')->render();
        $Oorders = &app::get('ome')->model('orders');

        $oItem = kernel::single("ome_mdl_sales_items");
        $sales = &app::get('sales')->model('sales')->getList('*',array('sale_id'=>$id));
        $dataitems = $oItem->getList('*',array('sale_id'=>$id));
        $total_nums = $oItem->getList('sum(nums) as total_nums',array('sale_id'=>$id));
        $archive = $sales[0]['archive'];
        if ($archive == '1') {
            $sql2 = 'select ODB.logi_no from sdb_archive_delivery_bill as ODB left join sdb_archive_delivery_order as ODO on ODB.delivery_id = ODO.delivery_id left join sdb_ome_sales as OS on OS.order_id= ODO.order_id where OS.sale_id= '.$sales[0]['sale_id'];
        }else{
            $sql2 = 'select ODB.logi_no from sdb_ome_delivery_bill as ODB left join sdb_ome_delivery_order as ODO on ODB.delivery_id = ODO.delivery_id left join sdb_ome_sales as OS on OS.order_id= ODO.order_id where OS.sale_id= '.$sales[0]['sale_id'];
        }








        $delivery_bill = kernel::database()->select($sql2);

        if ($archive == '1') {
            $Oorders = &app::get('archive')->model('orders');
        }
        $orders = $Oorders->getList('order_bn',array('order_id'=>$sales[0]['order_id']));

        $sql3 = "select name from sdb_ome_shop where shop_id='".$sales[0]['shop_id']."'";
        $shopname = kernel::database()->select($sql3);

        $sql4 = "select uname from sdb_ome_members where member_id='".$sales[0]['member_id']."'";
        $uname = kernel::database()->select($sql4);
        if ($archive == '1') {
            $sql5 = "select delivery_bn,ship_name,ship_area,ship_province,ship_city,ship_district,ship_addr,ship_zip,ship_tel,ship_mobile,ship_email from sdb_archive_delivery where delivery_id='".$sales[0]['delivery_id']."'";
        }else{
            $sql5 = "select delivery_bn,ship_name,ship_area,ship_province,ship_city,ship_district,ship_addr,ship_zip,ship_tel,ship_mobile,ship_email from sdb_ome_delivery where delivery_id='".$sales[0]['delivery_id']."'";
        }
        $delivery = kernel::database()->select($sql5);
        
        /*------------------------------------------------------ */
        //-- [拆单]获取订单对应多个发货单 ExBOY
        /*------------------------------------------------------ */
        if($split_seting)
        {
            $sql    = "SELECT dord.delivery_id, d.delivery_bn, d.logi_no FROM sdb_ome_delivery_order AS dord
                        LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                        WHERE dord.order_id='".$sales[0]['order_id']."' AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false'
                        AND d.status NOT IN('failed','cancel','back','return_back')";
            $delivery_list    = kernel::database()->select($sql);
            
            #获取订单对应所有发货单
            if($delivery_list && count($delivery_list) > 1)
            {
                $delivery[0]['delivery_bn']    = '';
                $sales[0]['logi_no']           = '';
                
                foreach($delivery_list as $key => $val)
                {
                    $delivery[0]['delivery_bn']    .= ' | '.$val['delivery_bn'];
                    $sales[0]['logi_no']           .= ' | '.$val['logi_no'];
                }
        
                $delivery[0]['delivery_bn']    = substr($delivery[0]['delivery_bn'], 2);
                $sales[0]['logi_no']           = substr($sales[0]['logi_no'], 2);
            }
        }
        
        if($delivery_bill){
            foreach ($delivery_bill as $value){
                $sales[0]['logi_no'] .=' | '.$value['logi_no'];
            }
        }
        $sales[0]['order_bn'] = $orders[0]['order_bn'];
        $sales[0]['sale_time'] = date("Y-m-d H:i:s",$sales[0]['sale_time']);
        $sales[0]['delivery_bn'] = $delivery[0]['delivery_bn'];
        $sales[0]['shopname']  = $shopname[0]['name'];
        $sales[0]['uname']   = $uname[0]['uname'];
        $sales[0]['nums']   = $total_nums[0]['total_nums'];

        $render->pagedata['deliveryinfo'] = $delivery[0];
        $render->pagedata['dataitems'] = $dataitems;
        $render->pagedata['sales'] = $sales[0];
        $render->display('detail.html');
    }

    var $addon_cols = 'archive,order_id';
    var $column_order_id='订单号';
    var $column_order_id_width='100';
    function column_order_id($row)
    {
        $archive = $row[$this->col_prefix . 'archive'];
        
        $order_id = $row[$this->col_prefix . 'order_id'];
        if ($archive == '1') {
            $orderObj = app::get('archive')->model('orders');
            
        }else{
            $orderObj = app::get('ome')->model('orders');
            
        }
        $filter = array('order_id'=>$order_id);
        $order = $orderObj->dump($filter,'order_bn');

        return $order['order_bn'];
    }
}
