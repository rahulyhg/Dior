<?php
class logisticsmanager_mdl_channel extends dbeav_model {
    
    /*
     * CSV导入
     */
    function prepared_import_csv(){
        
        $this->ioObj->cacheTime = time();
    }

    function finish_import_csv(){
        base_kvstore::instance('logisticsmanager')->fetch('waybill-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('logisticsmanager')->store('waybill-'.$this->ioObj->cacheTime,'');
        $channel_id = $_GET['channel_id'];
        $channelObj = app::get('logisticsmanager')->model('channel');
        $channel = $channelObj->dump($channel_id);
        $waybill_list = array_chunk($data['waybill'],5);
        $logistics_code = $channel['logistics_code'];
        foreach ($waybill_list as $waybill ) {
            $insert_sql = array();
            foreach ($waybill as $bill ) {
                if ($bill) {
                    $insert_sql[]="('$bill','$channel_id','$logistics_code')";
                }
            }
            if ($insert_sql) {
                $sql = "INSERT INTO sdb_logisticsmanager_waybill(waybill_number,channel_id,logistics_code) VALUES ".implode(',',$insert_sql);
                $channelObj->db->exec($sql);
            }
        }
        $opObj = &app::get('ome')->model('operation_log');
        $memo = "电子面单号导入";
        $opObj->write_log('waybill_import@logisticsmanager', $channel_id, $memo);
        return null;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        
        return null;
    }

    //CSV导入业务处理
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        
        $mark = false;
        
        $re = base_kvstore::instance('logisticsmanager')->fetch('waybill-'.$this->ioObj->cacheTime,$fileData);
        
        if( !$re ) $fileData = array();
        if( substr($row[0],0,1) == '*' ){
            $mark = 'title';
            $channel_id = $_GET['channel_id'];
        
            $channelObj = app::get('logisticsmanager')->model('channel');
            $channel = $channelObj->dump($channel_id,'channel_type');
            
            if (!in_array($channel['channel_type'],array('sto'))) {
                 $msg['error'] = "目前导入只支持申通类型!";
                 return false;
            }
        }else{
            if ($row[0]) {
                $fileData['waybill'][] = $row[0];
            }
            
        }
        
        base_kvstore::instance('logisticsmanager')->store('waybill-'.$this->ioObj->cacheTime,$fileData);    
        return null;
    }



}

?>