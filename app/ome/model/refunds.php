<?php

class ome_mdl_refunds extends dbeav_model{

    var $defaultOrder = array('t_ready DESC');

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        if (isset($filter['order_bn'])){
            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id',array('order_bn|has'=>$filter['order_bn']));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $archiveObj = &app::get('archive')->model('orders');
            $archives = $archiveObj->getList('order_id',array('order_bn|has'=>$filter['order_bn']));
            foreach ($archives  as $archive ) {
                $orderId[] = $archive['order_id'];
            }
            $where .= '  AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['order_bn']);
        }
        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }

    /* create_refunds 添加退款单
     * @param sdf $sdf
     * @return sdf
     */
    function create_refunds(&$sdf){
        $this->save($sdf);
    }

    /**
     * 发起前端店铺退款请求
     * @param mixed $sdf 请求数据
     * @return boolean
     */
    function refund_request($sdf=NULL){
        if (IS_NULL($sdf)) return false;
        //退款请求
        if ($service_refund = kernel::servicelist('service.refund')){
            foreach($service_refund as $object=>$instance){
                if(method_exists($instance,'refund_request')){
                    $instance->refund_request($sdf);
                }
            }
        }
        //订单支付状态:退款中
        $objOrder = &$this->app->model('orders');
        if ($sdf['is_archive'] && $sdf['is_archive']=='1') {
            $objOrder = &app::get('archive')->model('orders');
        }
        $orderdata['pay_status'] = '7';
        $filter = array('order_id'=>$sdf['order_id']);
        $objOrder->update($orderdata,$filter);
        //更新退款申请单的状态为退款中
        $oRefaccept = &$this->app->model('refund_apply');
        $data = array('status' => '5');
        $filter = array('apply_id'=>$sdf['apply_id']);
        $oRefaccept->update($data, $filter);
        return true;
    }


    function refund_detail($refund_id){
        $refund_detail = $this->dump($refund_id);
        if ($refund_detail['payment']){
            $sql = "SELECT custom_name FROM sdb_ome_payment_cfg WHERE id=".$refund_detail['payment'];
            $payment_cfg = $this->db->selectrow($sql);
            $refund_detail['payment_name'] = $payment_cfg['custom_name'];
        }else {
            $refund_detail['payment_name'] = '';
        }
        $refund_detail['status_name'] = ome_refund_func::refund_status_name($refund_detail['status']);
        return $refund_detail;
    }

    function save(&$refund_data,$mustUpdate=NULL){
        return parent::save($refund_data,$mustUpdate,true);
    }
    /*
     * 生成退款单号
     *
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
            $refund_bn = date("YmdH").'14'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('select refund_bn from sdb_ome_refunds where refund_bn =\''.$refund_bn.'\'');
        }while($row);
        return $refund_bn;
    }

    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'order_bn'=>app::get('base')->_('订单号'),
        );
        return $Options = array_merge($parentOptions,$childOptions);
    }
    /*
     * 单据>>订单单据>>退款单增加导出。
     */
    function io_title( $filter=null,$ioType='csv' ){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['payments'] = array(
                    'bn:退款单号' => 'refund_bn',
                    'col:订单号'=>'order_id',
                    'col:退款账号' => 'account',
                    'col:退款银行' => 'bank',
                    'col:收款账户' => 'pay_account',
                    'col:货币' => 'currency',
                    'col:支付金额' => 'money',
                    'col:支付网关费用' => 'paycost',
                    'col:支付类型' => 'pay_type',
                    'col:操作员' => 'op_id',
                    'col:支付开始时间' => 't_ready',
                    'col:发款时间' => 't_sent',
                    'col:用户确认收款时间' => 't_received',
                    'col:支付状态' => 'status',
                    'col:备注' => 'memo',
                    'col:来源店铺' => 'shop_id',
                );
                break;
        }
        $this->ioTitle[$ioType]['payments'] = array_keys( $this->oSchema[$ioType]['payments'] );
        return $this->ioTitle[$ioType][$filter];
     }

     //csv导出
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        if( !$data['title']['payments'] ){
            $title = array();
            foreach( $this->io_title('payments') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['payments'] = '"'.implode('","',$title).'"';
        }
        $limit = 100;
        //if( $filter[''] )
        if( !$list=$this->getList('refund_id',$filter,$offset*$limit,$limit) )return false;
        foreach( $list as $aFilter ){
            $aOrder = $this->dump($aFilter['refund_id'],'*');
            $aOrder['refund_bn'] = "=\"\"".$aOrder['refund_bn']."\"\"";

            //处理时间
            $aOrder['t_ready'] = $aOrder['t_ready']?date('Y-m-d H:i:s',$aOrder['t_ready']):'';
            $aOrder['t_sent'] = $aOrder['t_sent']?date('Y-m-d H:i:s',$aOrder['t_sent']):'';
            $aOrder['t_received'] = $aOrder['t_received']?date('Y-m-d H:i:s',$aOrder['t_received']):'';

            //处理支付类型
            switch ($aOrder['pay_type']){
                case 'online':
                    $aOrder['pay_type'] = '在线支付';
                    break;
                case 'offline':
                    $aOrder['pay_type'] = '线下支付';
                    break;
                case 'deposit':
                    $aOrder['pay_type'] = '预存款支付';
                    break;
            }

            //处理支付状态
            switch ($aOrder['status']){
                case 'succ':
                    $aOrder['status'] = '支付成功';
                    break;
                case 'failed':
                    $aOrder['status'] = '支付失败';
                    break;
                case 'cancel':
                    $aOrder['status'] = '未支付';
                    break;
               case 'error':
                    $aOrder['status'] = '处理异常';
                    break;
              case 'invalid':
                    $aOrder['status'] = '非法参数';
                    break;
              case 'progress':
                    $aOrder['status'] = '处理中';
                    break;
              case 'timeout':
                    $aOrder['status'] = '超时';
                    break;
              case 'ready':
                    $aOrder['status'] = '准备中';
                    break;
            }

            //处理操作员
            $po = app::get('pam')->model('account')->dump($aOrder['op_id']);
            $aOrder['op_id'] = $po['login_name'];

            //处理订单号
            $po = app::get('ome')->model('orders')->dump($aOrder['order_id']);
            $aOrder['order_id'] = "=\"\"".$po['order_bn']."\"\"";            //处理备注

//              $aOrder['memo'] = kernel::single('ome_func')->format_memo($aOrder['memo']);
//              if(!empty($aOrder['memo'])){
//                foreach($aOrder['memo'] as $k => $v){
//                    $arr[]= $v['op_content']." BY ".$v['op_name']." ".$v['op_time'];
//                  }
//                  $aOrder['memo'] = implode(',',$arr);
//              }
            $shop = app::get('ome')->model('shop')->dump($aOrder['shop_id'],'name');
            $aOrder['shop_id'] = $shop['name'];
            foreach( $this->oSchema['csv']['payments'] as $k => $v ){
                $orderRow[$k] = $this->charset->utf2local(utils::apath( $aOrder,explode('/',$v) ));
            }
            $data['content']['payments'][] = '"'.implode('","',$orderRow).'"';
        }
        $data['name'] = '退款单'.date("Ymd");
        return true;
    }

    function export_csv($data,$exportType = 1 ){
        $output = array();
            foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
        echo implode("\n",$output);
    }

    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams) {
        $type = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        }
        elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams) {
        $params = $logParams['params'];
        $type = 'bill';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_refund') {
            $type .= '_salesBill_refund';
        }
        $type .= '_export';
        return $type;
    }
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'bill';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_refund') {
            $type .= '_salesBill_refund';
        }
        $type .= '_import';
        return $type;
    }

    /**
     * 单据来源.
     * @param   archive
     * @return  
     * @access  public
     * @author sunjng@shopex.cn
     */
    function modifier_archive($row)
    {
        
        if($row == '1'){
           $row = sprintf("<div style='background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", 'red', '归档', '归档', '归档');
        }else{
            $row = '-';
        }
        return $row;
    }
}
?>