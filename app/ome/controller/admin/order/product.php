<?php
/**
 +----------------------------------------------------------
 * [拆单]显示各商品的仓库库存情况
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2014-10-16 $
 * [Ecos!] (C)2003-2014 Shopex Inc.
 +----------------------------------------------------------
 */
class ome_ctl_admin_order_product extends desktop_controller
{
    /*------------------------------------------------------ */
    //-- 商品购物清单[列表]
    /*------------------------------------------------------ */
    function index($order_id, $bn)
    {
        header("cache-control:no-store,no-cache,must-revalidate");
        $order_id       = intval($order_id);
        if(empty($order_id))
        {
            die('没有需要查询的订单号...');
        }
        $oOrder     = &app::get('ome')->model('orders');
        
        #订单信息
        $order      = $oOrder->dump($order_id);
        $order['consignee']['addr'] = htmlspecialchars($order['consignee']['addr']);

        #获取相关订单，并输入内容
        $combineObj = kernel::single('omeauto_auto_combine');
        $combineOrders = $combineObj->fetchCombineOrder($order);
        
        $orderIdx = $order['order_combine_idx'];
        $orderHash = $order['order_combine_hash'];

        $flag_edit = 'true';
        
        foreach ($combineOrders as $k=>$combineOrder) {
            $combineOrders[$k]['mark_text'] = strip_tags(htmlspecialchars($combineOrder['mark_text']));
            $combineOrders[$k]['custom_mark'] = strip_tags(htmlspecialchars($combineOrder['custom_mark']));            if($combineOrder['isCombine'] == true){
                $isCombinIds[] = $combineOrder['order_id'];
            }
            $combinIds[] = $combineOrder['order_id'];

            if ($order_add_service = kernel::service('service.order.'.$combineOrder['shop_type'])){
                if (method_exists($order_add_service, 'is_edit_view')){
                    $order_add_service->is_edit_view($combineOrder, $flag_edit);
                }
            }

            $combineOrders[$k]['flag_edit'] = $flag_edit;
        }
        
        #只保留查询的货号商品
        if(!empty($bn))
        {
            foreach ($combineOrders as $key => $combineOrder)
            {
                foreach ($combineOrder['items'] as $key_type => $volist)
                {
                    foreach ($volist as $obj_id => $items)
                    {
                        if($items['bn'] != $bn)
                        {
                            unset($combineOrders[$key]['items'][$key_type][$obj_id]);
                        }
                    }
                }   
            }
        }
        
        $this->pagedata['combineOrders'] = $combineOrders;
        $this->pagedata['jsOrders'] = json_encode($combineOrders);
        
        #仓库库存
        $branch_list    = $oOrder->getBranchByOrder(array($order_id));

        if ($branch_id[$orderHash]){
            $selected_branch_id = $branch_id[$orderHash];
            $branchObj = &app::get('ome')->model('branch');
            $this->pagedata['recommend_branch'] = $branchObj->dump($branch_id[$orderHash],'branch_id,name');
        }else{
            $selected_branch_id = $branch_list[0]['branch_id'];
        }

        $this->pagedata['selected_branch_id']   = $selected_branch_id;
        $this->pagedata['branch_list']          = $branch_list;
        $this->pagedata['order_id']             = $order_id;
        $this->singlepage("admin/order/confirm/show_product_inventory.html");
    }
    /*------------------------------------------------------ */
    //-- 捆绑商品清单[仓库库存详情]
    /*------------------------------------------------------ */
    function stock($order_id)
    {
        header("cache-control:no-store,no-cache,must-revalidate");
        $order_id       = intval($order_id);
        if(empty($order_id))
        {
            die('没有需要查询的订单号...');
        }
        $oOrder     = &app::get('ome')->model('orders');
        
        #订单信息
        $order      = $oOrder->dump($order_id);
        $order['consignee']['addr'] = htmlspecialchars($order['consignee']['addr']);

        #获取相关订单，并输入内容
        $combineObj = kernel::single('omeauto_auto_combine');
        $combineOrders = $combineObj->fetchCombineOrder($order);
        
        $orderIdx = $order['order_combine_idx'];
        $orderHash = $order['order_combine_hash'];

        $flag_edit = 'true';
        
        foreach ($combineOrders as $k=>$combineOrder) {
            $combineOrders[$k]['mark_text'] = strip_tags(htmlspecialchars($combineOrder['mark_text']));
            $combineOrders[$k]['custom_mark'] = strip_tags(htmlspecialchars($combineOrder['custom_mark']));            if($combineOrder['isCombine'] == true){
                $isCombinIds[] = $combineOrder['order_id'];
            }
            $combinIds[] = $combineOrder['order_id'];

            if ($order_add_service = kernel::service('service.order.'.$combineOrder['shop_type'])){
                if (method_exists($order_add_service, 'is_edit_view')){
                    $order_add_service->is_edit_view($combineOrder, $flag_edit);
                }
            }

            $combineOrders[$k]['flag_edit'] = $flag_edit;
        }
        
        #只保留查询的货号商品
        if(!empty($bn))
        {
            foreach ($combineOrders as $key => $combineOrder)
            {
                foreach ($combineOrder['items'] as $key_type => $volist)
                {
                    foreach ($volist as $obj_id => $items)
                    {
                        if($items['bn'] != $bn)
                        {
                            unset($combineOrders[$key]['items'][$key_type][$obj_id]);
                        }
                    }
                }   
            }
        }
        
        $this->pagedata['combineOrders'] = $combineOrders;
        $this->pagedata['jsOrders'] = json_encode($combineOrders);
        
        #仓库库存
        $branch_list    = $oOrder->getBranchByOrder(array($order_id));

        if ($branch_id[$orderHash]){
            $selected_branch_id = $branch_id[$orderHash];
            $branchObj = &app::get('ome')->model('branch');
            $this->pagedata['recommend_branch'] = $branchObj->dump($branch_id[$orderHash],'branch_id,name');
        }else{
            $selected_branch_id = $branch_list[0]['branch_id'];
        }
        
        $this->pagedata['selected_branch_id']   = $selected_branch_id;
        $this->pagedata['branch_list']          = $branch_list;
        $this->pagedata['order_id']             = $order_id;
        $this->singlepage("admin/order/confirm/show_product_stock.html");
    }
}