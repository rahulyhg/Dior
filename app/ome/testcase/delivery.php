<?php
class delivery extends PHPUnit_Framework_TestCase
{
    function setUp() {
        $this->db = kernel::database();
        $this->order_bn = '201306041410006600';
        $this->branch_bn = 'stockhouse';
        $this->logi_code = 'STO';
    }

    public function testdelivery(){
        //更新订单分派及确认状态
        $orders = $this->db->selectrow('SELECT order_id FROM sdb_ome_orders WHERE order_bn='.$this->order_bn);
        $order_id = $orders['order_id'];

        $sql = sprintf('UPDATE sdb_ome_orders SET group_id=\'%s\',op_id=\'%s\',confirm=\'%s\' WHERE order_id=\'%s\'','1','1','Y',$order_id);
        $this->db->exec($sql);

        $branchs = $this->db->selectrow('SELECT branch_id FROM sdb_ome_branch WHERE branch_bn=\''.$this->branch_bn.'\'');
        $branch_id = $branchs['branch_id'];

        $logis = $this->db->selectrow('SELECT corp_id FROM sdb_ome_dly_corp WHERE `type`=\''.$this->logi_code.'\'');
        $logi_id = $logis['corp_id'];

        $combineObj = kernel::single('omeauto_auto_combine');
        $order_ids = array($order_id);
        $consignee = array(
            'name' => '可可',
            'area' => 'mainland:北京/北京市/东城区:3',
            'addr' => '东城区1234号',
            'telephone' => '',
            'mobile' => '18752566525',
            'r_time' => '任意日期 任意时间段',
            'zip' => '123123',
            'email' => '',
            'memo' => '',
            'time' => '任意日期 任意时间段',
            'branch_id' => $branch_id
        );
        $logiId = array($logi_id);

        $result = $combineObj->mkDelivery($order_ids, $consignee, $logiId);

        #获取订单ID
        $dly = $this->db->selectrow('SELECT delivery_id FROM sdb_ome_delivery_order WHERE order_id='.$order_id);
        $delivery_id = $dly['delivery_id'];

        //人工审单合并发货单触发通知wms创建发货单
        $original_data = kernel::single('ome_event_data_delivery')->generate($delivery_id);
        $wms_id = kernel::single('ome_branch')->getWmsIdById($branch_id);
        $rs = kernel::single('ome_event_trigger_delivery')->create($wms_id, $original_data);
        //error_log(print_r($original_data,1),3,DATA_DIR.'/original_data.txt');

        #ocs发货单请求日志参数
        $method = 'store.wms.saleorder.create';
        $contents = file_get_contents('http://ocs2.test.vmod.cn/ocs1.3.0encode/data/'.$method);
        $ocs_params = @unserialize($contents);

        #讨管发货单请求日志参数
        $tg_contents = file_get_contents('http://localhost/oms/data/'.$method);
        $tg_params = @unserialize($tg_contents);

        $diff_arr_item = middleware_func::compare_params($ocs_params,$tg_params);
        print_r($diff_arr_item);
        error_log(print_r($diff_arr_item,1),3,DATA_DIR.'/diff_'.$method);
    }
}
