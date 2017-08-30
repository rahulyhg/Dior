<?php
class ome_stock_do{
    function run($branch_id){
        $branchObj = &app::get('ome')->model('branch');
        $branch_pObj = &app::get('ome')->model('branch_product');
        
        $branch = $branchObj->dump($branch_id);
        $data['branch_id'] = $branch['branch_id'];
        $data['time'] = strtotime(date("Ymd", time()));
        if ($branch){
            $oQueue = &app::get('base')->model('queue');
            $queueData = array(
                'queue_title'=>'计算安全库存',
                'start_time'=>time(),
                'cursor_id'=>0,
                'params'=>array(
                    'sdfdata'=>$data,
                    'app' => 'ome',
                    'mdl' => 'stock_change_log',
                ),
                'worker'=>'ome_stock_to_do.run',
            );
            $oQueue->save($queueData);
            app::get('base')->model('queue')->runtask($queueData['queue_id']);
            return true;
        }else {
            return false;
        }
    }
    
    function save_stock_safe_info($data){
        if(@include(ROOT_DIR.'/config/db_exinfo.php')){
            include(ROOT_DIR."/script/db_ex.php");
            
            $db = new db_ex();
            
            $server_name = $_SERVER['SERVER_NAME'];
            $branch_id = $data['branch_id'];
            $exec_hour = $data['stock_safe_time'];
            $worker = "ome_stock_do:run";
            
            if($exec_hour > DEFAULT_TIMEZONE){
                $exec_hour = $exec_hour - DEFAULT_TIMEZONE;
            }else{
                $exec_hour = $exec_hour - DEFAULT_TIMEZONE + 24;
            }
            
            if($db->selectrow("SELECT oid FROM cron_stock_safe WHERE server_name='".$server_name."' AND branch_id=".$branch_id)){
                $db->exec("UPDATE cron_stock_safe SET exec_hour=".$exec_hour." WHERE server_name='".$server_name."' AND branch_id=".$branch_id);
            }else{
                $db->exec("INSERT INTO cron_stock_safe(server_name,branch_id,exec_hour,worker) VALUES ('".$server_name."',".$branch_id.",".$exec_hour.",'".$worker."')");
            }
        }
    }
}