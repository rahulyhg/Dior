<?php

class ome_mdl_refund_apply extends dbeav_model{
    //所用户信息
    static $__USERS = null;

    var $pay_type = array (
        'online' => '在线支付',
        'offline' => '线下支付',
        'deposit' => '预存款支付',
      );

    var $defaultOrder = array('create_time DESC');

    function _filter($filter,$tableAlias=null,$baseWhere=null){
        if(isset($filter['order_bn'])){
            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id', array('order_bn|has'=>$filter['order_bn']), 0, -1);
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $archiveObj = app::get('archive')->model('orders');
            $archives = $archiveObj->getList('order_id', array('order_bn|has'=>$filter['order_bn']), 0, -1);
            foreach ($archives as $archive ) {
                $orderId[] = $archive['order_id'];
            }
            if (empty($orderId)){
                $orderId[] = '0';
            }
            $where .= ' AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['order_bn']);
        }
        if (isset($filter['member_uname'])){
            $memberObj = &$this->app->model("members");
            $rows = $memberObj->getList('member_id',array('uname|has'=>$filter['member_uname']));
            $memberId[] = 0;
            foreach($rows as $row){
                $memberId[] = $row['member_id'];
            }

            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id', array('member_id'=>$memberId));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $where .= ' AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['member_uname']);
        }
        if (isset($filter['ship_name'])){
            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id', array('ship_name|has'=>$filter['ship_name']));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $where .= ' AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['ship_name']);
        }
         #退款原因,模糊搜索
         if(isset($filter['memo'])){
            $_filter['filter_sql'] = ' memo like \''.$filter['memo'].'%\'';
            $have_memo = $this->app->model('refund_apply')->getList('apply_id',$_filter,0,-1);
            if(!empty($have_memo)){
                foreach($have_memo as $v){
                    $_apply_id[$v['apply_id']] = $v['apply_id'];
                }
                $where .= ' AND apply_id IN ('.implode(',', $_apply_id).')';
                unset($filter['memo']);
            }
        } 
        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }

    function refund_apply_detail($refapply_id){
    	$refapply_detail = $this->dump($refapply_id);
        $product_data = $refapply_detail['product_data'];
        if ($product_data) {
            $items = unserialize($product_data);
        }
        $refapply_detail['items'] = $items;
        if ($refapply_detail['payment']){
    	    $sql = "SELECT custom_name FROM sdb_ome_payment_cfg WHERE id=".$refapply_detail['payment'];
    	    $payment_cfg = $this->db->selectrow($sql);
            $refapply_detail['payment_name'] = $payment_cfg['custom_name'];
        }else {
            $refapply_detail['payment_name'] = '';
        }

    	$refapply_detail['type'] = $this->pay_type[$refapply_detail['pay_type']];
    	return $refapply_detail;
    }

    /* create_refund_apply 添加申请退款单
     * @param sdf $sdf
     * @return sdf
     */
    function create_refund_apply(&$sdf){
        $this->save($sdf);
    }

    function save(&$refund_data,$mustUpdate=NULL){
    	return parent::save($refund_data,$mustUpdate,true);
    }

    /**
     * 快捷搜索
     */
    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'order_bn' => '订单号',
            'member_uname'=>app::get('base')->_('用户名'),
            'ship_name'=>app::get('base')->_('收货人'),
        );
        return array_merge($parentOptions,$childOptions);
    }

    /**
     * 检查是否要将订单设为取消
     * 只有 全额退款并且为未发货的订单才会取消
     */
    function check_iscancel($order_id,$memo=null){
        $oShop = &$this->app->model('shop');
        $oOrder = app::get('ome')->model('orders');
        $order_detaillist = $oOrder->dump($order_id);
        $shop_detail = $oShop->dump(array('shop_id'=>$order_detaillist['shop_id']),'node_id');

          //只有未发货的才会取消订单
        if($order_detaillist['ship_status'] == 0){
          //增加订单取消的流程
          $memo = $memo?$memo:'订单全额退款后取消！';

          $mod = 'sync';
          $c2c_shop_list = ome_shop_type::shop_list();
          if(in_array($order_detaillist['shop_type'],$c2c_shop_list) || $order_detaillist['source'] == 'local' || !$shop_detail['node_id']){
            $mod = 'async';
          }
          $oOrder->cancel($order_id,$memo,true,$mod);
       }
    }

    /*
     * 退款申请单号
     *
     * @return 退款单号
     */
     function gen_id(){
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $refund_apply_bn = date("YmdH").'14'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('select refund_apply_bn from sdb_ome_refund_apply where refund_apply_bn =\''.$refund_apply_bn.'\'');
        }while($row);
        return $refund_apply_bn;
    }

    /**
     * 单据来源.
     * @param   
     * @return  string
     * @access  public
     * @author cyyr24@sina.cn
     */
    function modifier_source($row)
    {
        if ($row == 'local') {
            $source = '本地';
        }else if($row == 'matrix'){
           $source = '线上';
        }else if ($row == 'archive') {
            $source = sprintf("<div style='background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", 'red', '归档', '归档', '归档');
        }else {
            $source = '-';
        }
        return $source;
    }

    /**
     * 退款原因
     * @param   
     * @return  string
     * @access  public
     * @author cyyr24@sina.cn
     */
    function modifier_memo($row)
    {
        if ($row) {
            $reason = sprintf("<div style='background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", 'green', $row, $row, $row);
            return $reason;
        }
        
    }

    public function modifier_apply_op_id($row){
        switch ($row) {
            
            case 16777215:
                $ret = '系统';
                break;
            default:
                $ret = $this->_getUserName($row);
                break;
        }

        return $ret;
    }

    /**
     * 获取用户名
     *
     * @param Integer $gid
     * @return String;
     */
    private function _getUserName($uid) {
        if (self::$__USERS === null) {

            self::$__USERS = array();
            $rows = app::get('desktop')->model('users')->getList('*');
            foreach((array) $rows as $row) {
                self::$__USERS[$row['user_id']] = $row['name'];
            }
        }

        if (isset(self::$__USERS[$uid])) {

            return self::$__USERS[$uid];
        } else {

            return '系统';
        }
    }

    /**
     * 补偿费用显示
     * @param int
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function modifier_bcmoney($row)
    {
        if ($row>0) {
            $bcmoney = sprintf("<div style='background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", 'red', $row, $row, $row);
            return $bcmoney;
        }
    }
}
?>