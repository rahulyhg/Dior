<?php
class resaftersale extends PHPUnit_Framework_TestCase
{
    function setUp() {
        
        
    }
    function reship(){
        $params = array (
  'to_node_id' => '1436313032',
  'refund_id' => '16728531766763',
  'servername' => '192.168.41.15',
  'app_id' => 'ecos.ome',
  'sign' => 'F855B8272260C942345494A68D9D5D46',
  'from_node_id' => '1039313632',
  'refund_phase' => 'onsale',
  'refund_version' => '1380005717152',
  'aim_node' => '1634313532',
  'company_name' => '',
  'sid' => '0',
  'tid' => '428013004486367',
  'method' => 'ome.refund.add',
  'status' => 'wait_buyer_return_goods',
  'oid' => '428013004486367',
  'reason' => '收到商品破损',
  'node_id' => '1039313632',
  'date' => '2013-09-25 13:51:04',
  'refund_item_list' => '{"return_item": [{"outer_id": "test003", "price": 1, "num": 2, "num_iid": 20154206285}]}',
  'modified' => '2013-09-24 16:23:48',
  'refund_type' => 'reship',
  'aim_url' => 'http://192.168.41.15/taoguan/branches/aftersaletrunk/index.php/api',
            
);
       // base_rpc_service::$node_id = '1132393932';1533313232
            base_rpc_service::$node_id = '1039313632';
        return $params;
    }
    
    function tb()
    {

       
        $params = array (
  't_received' => '',
  'to_node_id' => '1436313032',
  'buyer_name' => '',
  'refund_id' => '16747905476763',
  'aim_url' => 'http://192.168.41.14/taofenxiao_working/index.php/api',
  'servername' => '192.168.41.14',
  'app_id' => 'ecos.ome',
  'sign' => 'C393A275A33FBF247F602A592704EC31',
  'currency' => 'CNY',
  'shipping_type' => '',
  'receiver_address' => '',
  'good_status' => 'BUYER_RETURNED_GOODS',
  'from_node_id' => '1132393932',
  'split_fee' => '',
  'pay_type' => '',
  'advance_status' => '',
  'buyer_bank' => '',
  'outer_no' => '',
  'aim_node' => '1533373832',
  'tid' => '156101006476367',
  'has_good_return' => 'True',
  't_sent' => '',
  'memo' => '测试退款呀。。。退呀退。。退货单哦。。',
  'status' => 'WAIT_SELLER_CONFIRM_GOODS',
  'logistics_company' => '4444',
  'buyer_id' => '',
  'company' => '',
  'oid' => '156101006476367',
  'cs_status' => '',
  'buyer_account' => '',
  'reason' => '其他',
  'node_id' => '1132393932',
  'date' => '2013-09-26 15:45:13',
  'refund_fee' => '0.01',
  'desc' => '测试退款呀。。。退呀退。。退货单哦。。',
  'payment_id' => '',
  'paycost' => '',
  'refund_item_list' => '{"return_item": [{"item_id": "", "price": "", "num": 1, "modified": "2013-09-25 16:33:55", "oid": 156101006476367}]}',
  'buyer_nick' => 'sunfree11',
  'seller_nick' => 'tao840105',
  'created' => '2013-09-25 14:06:04',
  'good_return_time' => '',
  'modified' => '2013-09-25 16:33:55',
  'method' => 'ome.refund.add',
  't_begin' => '2013-09-25 14:06:04',
  'payment_type' => '支付宝',
  'total_fee' => '0.03',
  'split_seller_fee' => '',
  'refund_type' => 'apply',
  'currency_fee' => '0.01',
);
        base_rpc_service::$node_id = '1132393932';
            //base_rpc_service::$node_id = '1533313232';
        return $params;
    }
    
    
    /**
     * Short description.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function tmallrefund()
    {
        $params = array ( 'status' => 'wait_seller_agree', 'to_node_id' => '1436313032', 'refund_id' => '8001309260016857', 'from_node_id' => '1039313632', 'attribute' => '', 'servername' => '192.168.41.15', 'bill_type' => 'refund_bill', 'cs_status' => '1', 'modified' => 1380260393, 'reason' => '收到商品破损', 'current_phase_timeout' => '2013-10-02 13:39:53', 'trade_status' => 'finished', 'refund_fee' => '1', 'payment_id' => '2013092611001001450048068831',  'created' => 1380189638, 'seller_nick' => '商家测试帐号37', 'buyer_nick' => '木之本涟', 'refund_version' => '1380260393040', 'operation_constraint' => 'null', 'aim_node' => '1634313532', 'aim_url' => 'http://192.168.41.15/taoguan/branches/aftersaletrunk/index.php/api', 'tid' => '430105809411217', 'tag_list' => '""', 'refund_type' => 'refund', 'actual_refund_fee' => '', 'good_return_time' => false, 'refund_phase' => 'aftersale', 'oid' => '430105809411217', );
        base_rpc_service::$node_id = '1039313632';
        return $params;
    }
    function tmall()
    {
        $params =array (
  'status' => 'wait_seller_agree',
  'to_node_id' => '1436313032',
  'refund_id' => '16843949536763',
  'from_node_id' => '1039313632',
  'attribute' => '',
  'servername' => '192.168.41.15',
  'bill_type' => 'refund_bill',
  'sign' => '882563118F31EF339A3D2E1A380B3B4A',
  'app_id' => 'ecos.ome',
  'cs_status' => '1',
  'modified' => '2013-09-30 14:53:19',
  'reason' => '退运费',
  'node_id' => '1039313632',
  'current_phase_timeout' => '2013-10-05 14:53:19',
  'date' => '2013-09-30 14:54:10',
  'trade_status' => 'wait_confirm_good',
  'refund_fee' => '1',
  'payment_id' => '2013092611001001690050420579',
  'refund_item_list' => '{"return_item": [{"price": 1, "oid": "429852822116367", "num_iid": 35068190671, "num": 1, "outer_id": "test003", "refund_phase": "onsale"}]}',
  'created' => '2013-09-30 14:53:19',
  'seller_nick' => '商家测试帐号37',
  'buyer_nick' => 'sunfree11',
  'refund_version' => '1380523999440',
  'operation_constraint' => 'null',
  'aim_node' => '1634313532',
  'aim_url' => 'http://192.168.41.15/taoguan/branches/aftersaletrunk/index.php/api',
  'tid' => '429852822116367',
  'tag_list' => '""',
  'method' => 'ome.aftersalev2.add',
  'refund_type' => 'return',
  'actual_refund_fee' => '',
);
       // base_rpc_service::$node_id = '1132393932';1533313232
            base_rpc_service::$node_id = '1039313632';
        return $params;
    }
    /**
     * Short description.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function gd()
    {
        $params = array ( 
           
            'refund_id' => '923135514033277298',
            'shipping_type'=>'free',
            
            'created'   =>'2013-9-11',
            
            'status'=>'WAIT_SELLER_AGREE',
            
            
            'good_status'=>'BUYER_NOT_RECEIVED',
           
            
            'receiver_address'=>'上海市徐汇区',
            'title'=>'测试售后呀',
            'item_list'=>
            array(
            0=>array(
                'num'=>'1','price'=>'1.00','outer_id'=>'test001','title'=>'test001','oid'=>'21122','refund_memo'=>'退就是退','oid'=>'155693082006367'),
            ) );
        base_rpc_service::$node_id = '123456';
        return $params;
    }

    
    /**
     * Short description.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function yhd()
    {
        $params = array ( 
           
            'refund_id' => '95235514033277298',
            'tid'=>'201309031710001966',
             'created'   =>'2013-9-11',
           
           'status'=>'WAIT_SELLER_AGREE',
            'refund_fee'=>'10',
            'reason'=>'申请原因',
            'desc'=>'申请说明',
          
           'good_return_time'=>'2013-9-1',
            'logistics_company'=>'韵达',
            'logistics_no'=>'1023233',
            'receiver_address'=>'上海市徐汇区',
            'title'=>'测试售后呀',
            'item_list'=>
            array(
            0=>array(
                'num'=>'1','price'=>'1.00','outer_id'=>'test002','title'=>'test002','oid'=>'21122','refund_memo'=>'退就是退'),
            ) );
        //7555755
        base_rpc_service::$node_id = '7555755';
        return $params;
    }

    
   
    public function testResaftersale(){
        //$params = $this->tb();
        //$params = $this->reship();
        $params = $this->tmall();
        //$params = $this->tmallrefund();
        //$params = $this->gd();
        //$params = $this->yhd();
        $result = kernel::single('apibusiness_router_response')->dispatch('aftersalev2','add',$params);
      
    }
}

?>