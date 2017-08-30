<?php
class sales_finder_aftersale{
    
    public $return_type = array(
                            'return' => '退货',
                            'change' => '换货',
                            'refund' => '退款',
                        );
    public $pay_type = array(
                            'online' => '在线支付',
                            'offline' => '线下支付',
                            'deposit' => '预存款支付',
                        );

    function __construct($app){
       $this->app = $app;
       $this->render = app::get('sales')->render();
    }
    
    var $detail_basic = '基本信息';
    function detail_basic($item_id){
        $Oaftersale = $this->app->model('aftersale');
        $Oaftersale_items = $this->app->model('aftersale_items');
        $Oshop = app::get('ome')->model('shop');
        $Omembers = app::get('ome')->model('members');
        $Oorder = app::get('ome')->model('orders');
        $Oreturn_products = app::get('ome')->model('return_product');           
        $Oreship = app::get('ome')->model('reship');  
        $Orefund_apply = app::get('ome')->model('refund_apply');        
        $Oaccount = app::get('pam')->model('account'); 
        $Obranch = app::get('ome')->model('branch'); 

        #售后单基本信息
        $aftersales = $Oaftersale->getList('*',array('aftersale_id'=>$item_id),0,1);
        #店铺信息
        $shop = $Oshop->getList('name',array('shop_id'=>$aftersales[0]['shop_id']),0,1);
        $archive = $aftersales[0]['archive'];
        if ($archive == '1') {
            $Oorder = app::get('archive')->model('orders');
        }
        #订单信息
        $order = $Oorder->getList('order_bn,order_id',array('order_id'=>$aftersales[0]['order_id']),0,1);   
        #售后申请信息
        $return_products = $Oreturn_products->getList('return_bn',array('return_id'=>$aftersales[0]['return_id']),0,1);   
        #退换货信息
        $reship = $Oreship->getList('reship_bn',array('reship_id'=>$aftersales[0]['reship_id']),0,1);   
        #退款申请信息
        $refund_apply = $Orefund_apply->getList('refund_apply_bn',array('apply_id'=>$aftersales[0]['return_apply_id']),0,1);   
        #操作员信息
        $account = $Oaccount->getList('login_name,account_id');   
        #会员信息
        $member = $Omembers->getList('uname',array('member_id'=>$aftersales[0]['member_id']),0,1);   

        foreach ($account as $v) {
            $accounts[$v['account_id']] = $v['login_name'];
        }

        $aftersales[0]['shop_id'] = $shop[0]['name'];
        $aftersales[0]['order_id'] = $order[0]['order_bn'];
        $aftersales[0]['return_id'] = $return_products[0]['return_bn'];
        $aftersales[0]['reship_id'] = $reship[0]['reship_bn'];
        $aftersales[0]['return_apply_id'] = $refund_apply[0]['refund_apply_bn'];
        $aftersales[0]['member_id'] = $member[0]['uname'];
        $aftersales[0]['return_type'] = $this->return_type[$aftersales[0]['return_type']];

        $aftersales[0]['check_op_id'] = $accounts[$aftersales[0]['check_op_id']];
        $aftersales[0]['op_id'] = $accounts[$aftersales[0]['op_id']];
        $aftersales[0]['refund_op_id'] = $accounts[$aftersales[0]['refund_op_id']];


        if($aftersales[0]['return_type'] == '退款'){
            $payment_cfgObj = app::get('ome')->model('payment_cfg');
            $aftersale_items = $Oaftersale_items->getList('*',array('aftersale_id'=>$item_id));
            $payment_cfg = $payment_cfgObj->dump(array('id'=>$aftersale_items[0]['payment']), 'custom_name');
            $aftersale_items[0]['payment'] = $payment_cfg['custom_name'];
            $aftersales = array_merge($aftersales[0],$aftersale_items[0]);
            $aftersales['pay_type'] = $this->pay_type[$aftersales['pay_type']];

            $this->render->pagedata['aftersales'] = $aftersales;
            
            return $this->render->fetch('aftersale/detail_refunded.html');
        }else{
            $aftersale_items = $Oaftersale_items->getList('*',array('aftersale_id'=>$item_id));
            $Oorder_items = app::get('ome')->model('order_items');

            foreach ($aftersale_items as $key => $value) {

                $aftersale_items[$key]['branch_id'] = $Obranch->Get_name($aftersale_items[$key]['branch_id']);
                
                if(empty($value['saleprice']) || bccomp($value['saleprice'],'0.000',3) == 0){
                   $orderitems = $Oorder_items->getList('sale_price',array('order_id'=>$order[0]['order_id'],'bn'=>$value['bn']),0,1);
                   $aftersale_items[$key]['saleprice'] = $orderitems[0]['sale_price'];
                }
            

                if($value['return_type'] == 'return'){
                     $recover['return'][] = $aftersale_items[$key];
                }elseif($value['return_type'] == 'change'){
                     $recover['change'][] = $aftersale_items[$key];
                }else{
                     $recover['refuse'][] = $aftersale_items[$key];
                }
            }
     
            $this->render->pagedata['aftersales'] = $aftersales[0];
            $this->render->pagedata['items'] = $recover;
            return $this->render->fetch('aftersale/detail_basic.html');
        }
    }


}