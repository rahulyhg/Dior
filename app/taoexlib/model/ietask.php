<?php
class taoexlib_mdl_ietask extends dbeav_model{

    var $exec_time = 0;

    /*function modifier_finish_count($row){
        return $row*100;
    }*/

    /**
     * 执行导出任务，生成文件
     * @param int $task_id 任务编号
     */
    public function export_id($task_id){
        if(!$task_id) return false;

        @ini_set('memory_limit','1024M');
        $exec_time = time();
        $offset = 0;
        while(true) {
            //超过并且等于30秒执行时间，重新链接数据库
            $cur_time = time();
            if($cur_time >= $exec_time + 30 ){
                $this->db->dbclose();
                $exec_time = $cur_time;
            }

            if($this->export($task_id,$offset)==false){
                return true;
            }
            $offset++;
        }
    }

    /**
     * 执行导出任务，生成文件
     */
    public function export($task_id,$offset = 0){
        // 查询数据库中等待执行的任务
        if($task_id) {
            $sql = ("SELECT task_id,model,file_name,filter_data,finish_count,app FROM sdb_taoexlib_ietask WHERE task_id=$task_id");
        }
        $task = $this->db->selectrow($sql);
        if (!$task) return false;

        //变为运行中
        $apiObj = kernel :: single('base_httpclient');
        if($offset == 0){
            $apiObj->post('http://' . $_SERVER['SERVER_NAME'] . '/crontab/api/api.php', array('api'=>'ietask_running','task_id'=>$task_id));
        }

        //删除旧有文件
        if(!empty($task['file_name'])){
            $ident_ret = kernel::single('taoexlib_storager')->parse($task['file_name']);
            kernel::single('taoexlib_storager')->remove($ident_ret['id']);
        }

        $task_id = $task['task_id'];
        $filter_arr = unserialize($task['filter_data']);
        if(!$filter_arr)return false;

        $oIo = kernel::servicelist('desktop_io');
        foreach( (array)$oIo as $aIo ){
            if( $aIo->io_type_name == ($_POST['_io_type']?$_POST['_io_type']:'csv') ){
                $oImportType = $aIo;
                break;
            }
        }
        unset($oIo);

        $_export_type = 'csv';
        $multi_type = true;
        $data = array('name'=> $task['model'] );
        //$offset = $task['finish_count'];
        $offset = isset($offset) ? $offset : 0;
        $model = app::get($task['app'])->model($task['model']);

        //后台导出时启用than between等高级过滤方式 fix by danny
        $model->filter_use_like = true;
        $model->is_queue_export = true;

        $oImportType->init($model);

        if( method_exists($model,'fgetlist_'.$_export_type) ){
            //双层结构
            switch($task['model']){
                case 'orders':
                    $main = 'order';
                    $pair = 'obj';
                    break;
                case 'sales':
                    $main = 'sales';
                    $pair = 'sales_items';
                    break;
                case 'inventory':
                    $main = 'branch';
                    $pair = 'export';
                    break;
                case 'shop_frame':
                    $main = 'inventorydepth';
                    break;
                case 'ome_products':
                    $main = 'products';
                    break;
                case 'ome_goodsale':
                    $main = 'goodsale';
                    break;
                case 'ome_storeStatus':
                    $main = 'storestatus';
                    break;
                case 'ome_goodsrank':
                    $main = 'goodsrank';
                    break;
                case 'aftersale':
                    $main = 'aftersale';
                    $pair = 'aftersale_items';
                    break;
                case 'ome_shop':
                    $main = 'shop';
                    break;
                case 'ome_branchdelivery':
                    $main = 'branchdelivery';
                    break;
                case 'ome_aftersale':
                    $main = 'aftersale';
                    break;
                case 'goods':
                    $main = 'goods';
                    break;
                case 'bill_order':
                    $main = 'bill';
                    break;
                case 'ar_statistics':
                    $main = 'ar';
                    break;
                case 'iostocksearch':
                    $main = 'main';
                    break;
                case 'distributor_product_sku':
                    $main = 'main';
                    break;
                case 'analysis_bills':
                    $main = 'bill';
                    break;
                case 'analysis_book_bills':
                    $main = 'bill';
                    break;
                case 'branch_product':
                    $main = 'branch_product';
                    break;
                case 'reship':
                    $main = 'reship';
                    break;
            }
            // 导出头部
            $listFlag = $model->fgetlist_csv($data,$filter_arr,$offset,1,1000);
            if($task['model']=='inventory' || ($task['model']=='branch_product' && in_array($task['app'],array('console','wms')))){
                $total_count = isset($data['content'][$pair]) ? count($data['content'][$pair]) : count($data['content'][$main]);
                $apiObj->post('http://' . $_SERVER['SERVER_NAME'] . '/crontab/api/api.php', array('api'=>'ietask_finish_count','total_count'=>$total_count,'task_id'=>$task_id));
            }else{
                $total_count = isset($data['content'][$main]) ? count($data['content'][$main]) : count($data['content']);
            }
        }else{
            $multi_type = false;
            // 导出头部
            $listFlag = $oImportType->fgetlist($data,$model,$filter_arr,$offset,$_POST['_export_type'],1000);
            $csv_content = $this -> sep_export($data,$offset,$model,$exportType=1);
            $total_count = count($data['contents']);
        }

        $day_ttl = time() + 86400;//缓存失效时间 默认一天
        if ($multi_type == true) {
            if($offset == 0) {
                base_kvstore::instance('taoexlib_ietask')->append('file_header_'.$task_id,$data['title'][$main]."\n",$day_ttl);
            }

            if(isset($data['content'][$main]) && is_array($data['content'][$main])){
                $content_order = '';
                foreach($data['content'][$main] as $v) {
                    $content_order .= $v."\n";
                }

                base_kvstore::instance('taoexlib_ietask')->append('file_header_'.$task_id,$content_order,$day_ttl);
            }

            if($offset == 0) {
                base_kvstore::instance('taoexlib_ietask')->append('file_body_'.$task_id,$data['title'][$pair]."\n",$day_ttl);
            }
            if(isset($data['content'][$pair]) && is_array($data['content'][$pair])){
                $content_obj = '';
                foreach($data['content'][$pair] as $v) {
                    $content_obj .= $v."\n";
                }
                base_kvstore::instance('taoexlib_ietask')->append('file_body_'.$task_id,$content_obj,$day_ttl);
            }
        }else{
            base_kvstore::instance('taoexlib_ietask')->append('file_body_'.$task_id,$csv_content,$day_ttl);
        }

        unset($data);

        if($listFlag == false) {
            //合并文件：对于单头和单体类型的数据需要
            base_kvstore::instance('taoexlib_ietask')->fetch('file_header_'.$task_id,$file_header);
            base_kvstore::instance('taoexlib_ietask')->fetch('file_body_'.$task_id,$file_body);
            if(!$file_header)$file_header = '';
            if(!$file_body)$file_body = '';

            //合并头跟尾
            $file_data = $file_header . $file_body;
            $ident_data = kernel::single('taoexlib_storager')->save($file_data,$_export_type);
            $expire_time = time() + 259200;//默认三天有效期

            $apiObj->post('http://' . $_SERVER['SERVER_NAME'] . '/crontab/api/api.php', array('api'=>'ietask_finish','file_name'=>$ident_data,'expire_time'=>$expire_time,'task_id'=>$task_id));

            //清除缓存
            base_kvstore::instance('taoexlib_ietask')->real_delete('file_header_'.$task_id);
            base_kvstore::instance('taoexlib_ietask')->real_delete('file_body_'.$task_id);

            return false;
        }else{
            $apiObj->post('http://' . $_SERVER['SERVER_NAME'] . '/crontab/api/api.php', array('api'=>'ietask_finish_count','total_count'=>$total_count,'task_id'=>$task_id));

            return true;
        }
    }

    public function get_safe_str(&$str,$iconv) {
        $str = str_replace("'","",$str);
        if($iconv == true) {
            $str = iconv('GB2312','UTF-8//IGNORE', $str);
        }
        $str = "'".$str."\r\n'";
        return $str;
    }

    /**
     * 单独处理报表导出
     */
    private function sep_export(&$data,$offset,&$model,$exportType=1){

        if(method_exists($model,'export_csv')){
            $rs = $model->export_csv($data,$exportType);
        }else{
            $rs = '';
            if( is_array( $data ) ){
                $data = (array)$data;
                if( empty( $data['title'] ) && empty( $data['contents'] ) ){
                    $rs = implode( "\n", $data );
                }else{
                    if ($offset==0)
                        $rs = $data['title']."\n".implode("\n",(array)$data['contents']);
                    else
                        $rs = implode("\n",(array)$data['contents']);
                }
            }else{
                $rs = (string)$data;
            }
        }

        $rs .= "\n";

        if(function_exists('mb_convert_encoding')){
            //excel 2007 读取utf8乱码bug。
            return mb_convert_encoding($rs, 'GBK', 'UTF-8');
        }else{
            return $this->charset->utf2local( $rs );
        }
    }

    /**
     * 自动创建文件目录
     */
    public function DoCreateNewDir($sUploadDir) {
        $strTmpPath = "";
        $arrPath = explode("/",$sUploadDir);
        for ($intRow = 0;$intRow< (sizeof($arrPath)-1);$intRow++) {
            $strTmpPath .= $arrPath[$intRow] . "/";
            if (is_dir($strTmpPath)==false) {
                if(!@mkdir($strTmpPath)) {
                    echo('Folder created failed!');
                }
            }
        }
        return true;
    }

    /**
     * 合并csv文件
     * @param string $from 数据来源
     * @param string $to 合并后的文件
     */
    public function merge_csv_file($from,$to){
        $fw = fopen($to, 'a');// 追加模式打开
        $fp = fopen($from, 'r'); // 打开一个CSV文件资源
        $flg = true;  // 是否过滤掉每一个CSV开头一行的标题栏 false为开，true为关
        if ($fp) {
            while (!feof($fp)) {
                $buffer = fgets($fp, 4096);
                if ($flg) {
                    fwrite($fw, $buffer); // 将CSV的内容写到新的CSV文件中
                }
                $flg = true;
            }
        }
        fclose($fp);
        fclose($fw);
        @unlink($from);
    }

    function test_export($task_id){
        $this->db->exec('UPDATE sdb_taoexlib_ietask SET last_time="'.time().'",status="finished" WHERE task_id='.$task_id);
    }

    /**
     *
     *
     * 获取过期得已完成任务列表
     */
    function getExpireIetask(){
        $rows = $this->db->select('select task_id,file_name from sdb_taoexlib_ietask where status="finished" and expire_time<='.time());

        return $rows;
    }

     function remove($task_id){
        $this->db->exec('delete from sdb_taoexlib_ietask where task_id='.$task_id);
    }

    function pre_recycle($data=null){
        $ids = array();
        foreach ($data as $ietask){
            $ids[] = $ietask['task_id'];
        }
        if(!empty($ids)){
            $rows = $this->db->select('SELECT task_id,status,file_name,queue_id FROM sdb_taoexlib_ietask WHERE task_id in('.implode(',', $ids).')');
            /*foreach ($rows as $task){
                if($task['status'] == 'running'){
                    $this->recycle_msg = "不能删除运行中的任务";
                    return false;
                }
            }*/
            foreach ($rows as $task){
                //删除旧有文件
                if(!empty($task['file_name'])){
                    //$ident_ret = kernel::single('taoexlib_storager')->parse($task['file_name']);
                    //kernel::single('taoexlib_storager')->remove($ident_ret['id']);

                    //清除缓存
                    //kernel::single('base_kvstore_tokyotyrant', 'taoexlib_ietask')->delete('file_header_'.$task['task_id']);
                    //kernel::single('base_kvstore_tokyotyrant', 'taoexlib_ietask')->delete('file_body_'.$task['task_id']);
                    //base_kvstore::instance('taoexlib_ietask')->real_delete('file_header_'.$task['task_id']);
                    //base_kvstore::instance('taoexlib_ietask')->real_delete('file_body_'.$task['task_id']);
                    //看下本地在不在的话删除文件过期后
                    @rmdir(DATA_DIR.'/export/cache/'.$task['task_id']);
                    @unlink(DATA_DIR.'/export/file/'.$task['file_name']);
                }
                $this->db->exec('delete from sdb_taoexlib_queue where queue_id='.$task['queue_id']);
            }
        }

       return true;
   }

   function getValidCounts(){
        $row = $this->db->selectrow('select count(*)  as total from sdb_taoexlib_ietask where status in("running","sleeping")');

        return $row['total'];
   }


}
?>