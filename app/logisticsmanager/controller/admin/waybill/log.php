<?php
class logisticsmanager_ctl_admin_waybill_log extends desktop_controller{
    public function index() {
        $base_filter = array('status|noequal'=>'success');
        $params = array(
            'title'=>'电子面单异常请求',
            'actions'=>array(
                array('label' => '批量重试', 'submit' => 'index.php?app=logisticsmanager&ctl=admin_waybill_log&act=batch_retry','target' => 'dialog::{width:550,height:170,title:\'重试获取快递面单请求\'}'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'base_filter' => $base_filter,
        );
        $this->finder('logisticsmanager_mdl_waybill_log', $params);
    }

    public function batch_retry(){
        $this->pagedata['log_id'] = $_POST['log_id'];
        $this->pagedata['logCount'] = count($_POST['log_id']);
        $this->pagedata['jsonLogs'] = json_encode($_POST['log_id']);
        $this->display("admin/waybill/retryLog.html");
    }

    public function ajaxRetry(){
        $tmp = explode('||', $_POST['ajaxParams']);
        $logs = explode(';', $tmp[1]);

        if(is_array($logs) && !empty($logs)){
            $channelObj = &app::get("logisticsmanager")->model("channel");
            $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');
            $router = kernel::single('apibusiness_router_request');
            $emsRpcObj = kernel::single('logisticsmanager_rpc_request_ems');
            $jdRpcObj = kernel::single('logisticsmanager_rpc_request_ems');

            //获取面单来源信息
            $channels = array();
            $rows = $channelObj->getList('*',array('status'=>'true'));
            foreach($rows as $val) {
                $channels[$val['channel_id']] = $val;
                unset($val);
            }
            unset($rows);

            //获取请求的日志信息
            $rows = $waybillLogObj->getList('*',array('log_id'=>$logs));
            $succ = $fail = 0;
            foreach($rows as $val){
                if($val['params']) {
                    if ($channels[$val['channel_id']]['channel_type']=='wlb' && $channels[$val['channel_id']]['shop_id']) {
                        $router->setShopId($channels[$val['channel_id']]['shop_id'])->get_waybill_number($val['params']);
                    } elseif($channels[$val['channel_id']]['channel_type']=='ems') {
                        $emsRpcObj->get_waybill_number($val['params']);
                    } elseif ($channels[$val['channel_id']]['channel_type']=='360buy') {
                        $jdRpcObj->get_waybill_number($val['params']);
                    }
                    $retry = $val['retry']+1;
                    $waybillLogObj->update(array('status'=>'running','retry'=>$retry),array('log_id'=>$val['log_id']));
                    $succ++;
                } else {
                    $fail++;
                }
                unset($val);
                usleep(200000);
            }
            unset($rows);
            echo json_encode(array('total' => count($logs), 'succ' => $succ, 'fail' => $fail));
        }else{
            echo json_encode(array('total' => 0, 'succ' => 0, 'fail' => 0));
        }
    }
}