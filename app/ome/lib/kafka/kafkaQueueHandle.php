<?php
define('PHPEXCEL_ROOT', ROOT_DIR . '/app/omecsv/lib/static');   // 定义PHP_excel处理类路经
/**
 * kafka_queue处理类
 * Created by PhpStorm.
 * User: august.yao
 * Date: 2018/08/02
 * Time: 14:46
 */
class ome_kafka_kafkaQueueHandle{

    private $_limit = 1000; // 默认处理数据量

    /**
     * 表ome_kafka_queue 处理
     */
    public function worker(){

        // 查看当前脚本是否在执行
        $kafkaQueueIsTrue = app::get('ome')->getConf('kafkaQueueIsTrue');
        if($kafkaQueueIsTrue && $kafkaQueueIsTrue == 'isTrue'){
            return true;
        }else{
            // 设置执行标志
            app::get('ome')->setConf('kafkaQueueIsTrue', 'isTrue');
        }
        // 设置脚本执行时间
        set_time_limit(0);
        $orderModel = app::get('ome')->model('orders');
        // 获取需要执行的任务
        $kafkaQueue = app::get('ome')->model('kafka_queue');
        $taskList   = $kafkaQueue->getList('*', array('status'=>'hibernate'), 0, $this->_limit);

        if($taskList){
            foreach ($taskList as $key=>$val){
                // 数据处理
                list($worker, $method) = explode('.', $val['worker']);
                $obj_work = kernel::single($worker);
                $params   = $val['params'];
                // 获取订单创建时间
                $createTime = $orderModel->dump(array('order_bn'=>$params['order_bn']),'order_id,createtime');
                $params['createtime'] = $createTime['createtime'];
                // 状态发生改变时间
                $params['statusTime'] = $val['start_time'];

                $response = call_user_func_array(array($obj_work, $method), array($params['order_bn'], $params['status'], $params, $params['shop_id']));
                
                if($response['success']){
                    $kafkaQueue->db->exec("delete from sdb_ome_kafka_queue where queue_id='{$val['queue_id']}'");
                }else{
                    $kafkaQueue->db->exec("update sdb_ome_kafka_queue set status='failure',errmsg='{$response['msg']}' where queue_id='{$val['queue_id']}'");    // todo:如果有错误信息
                }
                // sleep(2);   // 延迟2秒
            }
        }
        // 设置执行标志
        app::get('ome')->setConf('kafkaQueueIsTrue', 'isFalse');
    }

    /**
     * 生成从开始月份到结束月份的月份数组
     * @param int $start 开始时间戳
     * @param int $end 结束时间戳
     */
    public function monthList($start, $end){

        // 转为时间戳
        $start = strtotime(date('Y-m', $start) . '-01');
        $end   = strtotime(date('Y-m', $end) . '-01');
        $i = 0;
        $d = array();
        while($start <= $end){
            // 这里累加每个月的的总秒数 计算公式：上一月1号的时间戳秒数减去当前月的时间戳秒数
            $month = trim(date('Y-m', $start), ' ');
            $d[$month] = array(
                'start' => $start,
                'end'   => strtotime('+1 month', $start),
            );
            $start += strtotime('+1 month', $start) - $start;
            $i++;
        }
        unset($start,$end,$i,$month); // 销毁临时变量
        return $d;
    }

    /**
     * 获取发货单时间
     * @param $orderId
     * @param $type 类型 synced审核 shipped发货
     * @return string
     */
    public function getDeliveryTime($orderId, $type = 'synced'){

        // 根据订单获取发货单
        $delivery = app::get('ome')->model('delivery_order')->dump(array('order_id'=>$orderId),'delivery_id');

        if(!$delivery){
            return '';
        }

        $res = app::get('ome')->model('delivery')->dump(array('delivery_id'=>$delivery['delivery_id']),'create_time,delivery_time');

        if(!$res){
            return '';
        }

        $time = $type == 'synced' ? $res['create_time'] : $res['delivery_time'];

        unset($res,$delivery); // 销毁变量

        return $time;
    }

    /**
     * 获取订单商品
     * @param $orderId
     * @return array()
     */
    public function getOrderItem($orderId){

        // 查询订单表
        $orderGoods = app::get('ome')->model('order_items')->getList('bn,nums',array('order_id'=>$orderId));

        return $orderGoods;
    }

    /**
     * 获取订单申请退款时间
     * @param $orderId
     * @return string
     */
    public function getRefundingTime($orderId){
        // 查询订单表
        $refundingTime = app::get('ome')->model('refund_apply')->dump(array('order_id'=>$orderId),'create_time');

        if(!$refundingTime){
            return '';
        }
        return $refundingTime['create_time'];
    }

    /**
     * 获取已退款时间
     * @param $orderId
     * @return string
     */
    public function getRefunded($orderId){
        // 查询退款表
        $refundingRes = app::get('ome')->model('refunds')->dump(array('order_id'=>$orderId),'t_sent,refund_bn,money');

        if(!$refundingRes){
            return false;
        }
        return $refundingRes;
    }

    /**
     * 获取订单退货时间
     * @param $orderId
     * @param string $type
     * @return string
     */
    public function getReshipTime($orderId, $type = 'reshipping'){
        // 查询退货表
        $reshipTime = app::get('ome')->model('reship')->dump(array('order_id'=>$orderId),'t_begin,t_end');

        if(!$reshipTime){
            return '';
        }

        $time = $type == 'reshipping' ? $reshipTime['t_begin'] : $reshipTime['t_end'];

        unset($reshipTime); // 销毁变量

        return $time;
    }

    /**
     * 获取退货单商品
     * @param $orderId
     * @return array()
     */
    public function getReshipItem($orderId){
        // 退货表
        $reshipId = app::get('ome')->model('reship')->dump(array('order_id'=>$orderId),'reship_id');
        if(!$reshipId){
            return false;
        }
        // 退货商品表
        $reshipItem = app::get('ome')->model('reship_items')->getList('bn,num,price,product_name,product_id',array('reship_id'=>$reshipId['reship_id']));

        if(!$reshipItem){
            return false;
        }
        unset($reshipId); // 销毁变量
        return $reshipItem;
    }

    /**
     * 订单历史状态数据-通过浏览器下载
     * 下载的文件通常很大, 所以先设置csv相关的Header头, 然后打开
     * PHP output流, 渐进式的往output流中写入数据, 写到一定量后将系统缓冲冲刷到响应中
     * 避免缓冲溢出
     */
    public function orderStatusExcel($timeStart, $timeEnd){

        // 设置配置项
        set_time_limit(0); // 程序执行时间
        ignore_user_abort(true); // 客户端断开后,仍然继续运行
        ini_set('memory_limit', '2048M'); // 运行内存
        // 时间文本转成时间戳
        $startTime = strtotime($timeStart);
        $endTime   = strtotime("$timeEnd +1 day");
        // 参数判断
        if(!is_numeric($startTime) || !is_numeric($endTime) || ($endTime <= $startTime)){
            return false;
        }
        // 引入model
        $orderModel = app::get('ome')->model('orders');
        $kafkaDir= ROOT_DIR . '/data/kafka_status_history/'; // 文件保存目录
        $fileLog = ROOT_DIR . '/data/kafka_status_history/kafka_file_log'.$timeStart.'_'.$timeEnd.'.txt'; // log日志文件
        // 判断目录是否存在
        if (!file_exists($kafkaDir)) {
            $u_mask = umask(0);	            // 处理umask情况
            mkdir($kafkaDir, 0777, true);   // 创建解压目录 recursive参数表示是否创建多重目录 true/false
            umask($u_mask);
        }
        // 判断log文件是否存在
        if(!file_exists($fileLog)){
            $u_mask = umask(0);	    // 处理umask情况
            fopen($fileLog, "a+");  // 创建log日志
            umask($u_mask);
        }
        // excel字段信息
        $columns = array(
            'order_bn','brand','Channel','status','createtime','Status_change_time','logi_bn(物流单号)','sku(商品sku)','num(商品数量)','bn(退款单号)','money(退款金额)'
        );
        // 文件民
        $fileName =  $timeStart . '_' . $timeEnd . '.csv';

        // 设置好告诉浏览器要下载excel文件的headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="'. $fileName .'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $fp = fopen('php://output', 'a'); // 打开output流
        mb_convert_variables('GBK', 'UTF-8', $columns);
        fputcsv($fp, $columns); // 将数据格式化为CSV格式并写入到output流中

        // 获取订单数据量
        $sql = "SELECT count(order_id) num FROM sdb_ome_orders 
                where createtime >= '$startTime' and createtime < '$endTime' and pay_status not in ('0','8')";

        $numRes = $orderModel->db->select($sql);

        $limit = 1000;  // 每次查询的条数
        $pages = ceil($numRes[0]['num'] / $limit); // 总页码

        for($i = 0; $i < $pages; $i++) {

            $offset = $i * $limit;  // 偏移量

            $sql = "SELECT order_bn,order_id,createtime,logi_no,paytime,last_modified,status,pay_status,ship_status,route_status,
                    routetime,order_confirm_time,process_status 
                    FROM sdb_ome_orders 
                    where createtime >= '$startTime' and createtime < '$endTime' and pay_status not in ('0','8') 
                    limit $offset, $limit";

            ##记录日志##
            $myFile = fopen($fileLog, 'a+');
            $res = $sql . "\n";
            fwrite($myFile, $res);
            fclose($myFile);
            ##记录日志##

            // 查询数据
            $orderList = $orderModel->db->select($sql);

            // 订单状态处理
            foreach ($orderList as $k=>$v){
                // 已支付状态
                ## 推送已支付 paid ##
                $rowData = array(
                    "\t" . $v['order_bn'] . "\t",'Dior','DMALL','paid',$v['createtime'],$v['paytime'],'','','','',''
                );
                mb_convert_variables('GBK', 'UTF-8', $rowData);
                fputcsv($fp, $rowData);

                // 判断订单是否发货--推送已审核、已发货、退货申请中、已退货
                if($v['ship_status'] == '0'){
                    if($v['process_status'] == 'splited'){
                        ## 推送已审核 synced ##
                        $syncedTime = $this->getDeliveryTime($v['order_id'], 'synced'); // 获取已审核时间
                        $rowData = array(
                            "\t" . $v['order_bn'] . "\t",'Dior','DMALL','synced',$v['createtime'],$syncedTime,'','','','',''
                        );
                        mb_convert_variables('GBK', 'UTF-8', $rowData);
                        fputcsv($fp, $rowData);
                    }
                }else{
                    // 判断订单是否发货 推送已审核、已发货
                    if(in_array($v['ship_status'], array(1,2))){
                        ## 推送已审核 synced ##
                        $syncedTime = $this->getDeliveryTime($v['order_id'], 'synced'); // 获取已审核时间
                        $rowData = array(
                            "\t" . $v['order_bn'] . "\t",'Dior','DMALL','synced',$v['createtime'],$syncedTime,'','','','',''
                        );
                        mb_convert_variables('GBK', 'UTF-8', $rowData);
                        fputcsv($fp, $rowData);

                        ## 推送已发货 shipped ##
                        $shippedTime = $this->getDeliveryTime($v['order_id'], 'shipped'); // 获取发货时间
//                        $orderItem   = $this->getOrderItem($v['order_id']); // 获取订单商品信息
//                        foreach ($orderItem as $itemKey=>$itemValue){
//                            $rowData = array(
//                                "\t" . $v['order_bn'] . "\t",'Dior','DMALL','shipped',$v['createtime'],$shippedTime,"\t" . $v['logi_no'] . "\t","\t" . $itemValue['bn'] . "\t",$itemValue['nums'],'',''
//                            );
//                            mb_convert_variables('GBK', 'UTF-8', $rowData);
//                            fputcsv($fp, $rowData);
//                        }
                        $rowData = array(
                            "\t" . $v['order_bn'] . "\t",'Dior','DMALL','shipped',$v['createtime'],$shippedTime,"\t" . $v['logi_no'] . "\t",'','','',''
                        );
                        mb_convert_variables('GBK', 'UTF-8', $rowData);
                        fputcsv($fp, $rowData);
                    }
                    // 判断订单是否发生退货 推送已审核、已发货、退货申请中、已退货
                    if(in_array($v['ship_status'], array(3,4))){
                        ## 推送已审核 synced ##
                        $syncedTime = $this->getDeliveryTime($v['order_id'], 'synced'); // 获取已审核时间
                        $rowData = array(
                            "\t" . $v['order_bn'] . "\t",'Dior','DMALL','synced',$v['createtime'],$syncedTime,'','','','',''
                        );
                        mb_convert_variables('GBK', 'UTF-8', $rowData);
                        fputcsv($fp, $rowData);

                        ## 推送已发货 shipped ##
                        $shippedTime = $this->getDeliveryTime($v['order_id'], 'shipped'); // 获取发货时间
//                        $orderItem   = $this->getOrderItem($v['order_id']); // 获取订单商品信息
//                        foreach ($orderItem as $itemKey=>$itemValue){
//                            $rowData = array(
//                                "\t" . $v['order_bn'] . "\t",'Dior','DMALL','shipped',$v['createtime'],$shippedTime,"\t" . $v['logi_no'] . "\t","\t" . $itemValue['bn'] . "\t",$itemValue['nums'],'',''
//                            );
//                            mb_convert_variables('GBK', 'UTF-8', $rowData);
//                            fputcsv($fp, $rowData);
//                        }
                        $rowData = array(
                            "\t" . $v['order_bn'] . "\t",'Dior','DMALL','shipped',$v['createtime'],$shippedTime,"\t" . $v['logi_no'] . "\t",'','','',''
                        );
                        mb_convert_variables('GBK', 'UTF-8', $rowData);
                        fputcsv($fp, $rowData);

                        ## 推送退货申请中 reshipping ##
                        $reshippingTime = $this->getReshipTime($v['order_id'], 'reshipping'); // 获取退货申请中时间
                        $rowData = array(
                            "\t" . $v['order_bn'] . "\t",'Dior','DMALL','reshipping',$v['createtime'],$reshippingTime,'','','','',''
                        );
                        mb_convert_variables('GBK', 'UTF-8', $rowData);
                        fputcsv($fp, $rowData);

                        ## 推送已退货 reshipped ##
                        $reshippedTime = $this->getReshipTime($v['order_id'], 'reshipped'); // 获取已退货时间
                        $reshipItem    = $this->getReshipItem($v['order_id']); // 获取退货商品
                        foreach ($reshipItem as $reshipKey=>$reshipValue){
                            $rowData = array(
                                "\t" . $v['order_bn'] . "\t",'Dior','DMALL','reshipped',$v['createtime'],$reshippedTime,'',"\t" . $reshipValue['bn'] . "\t",$reshipValue['num'],'',''
                            );
                            mb_convert_variables('GBK', 'UTF-8', $rowData);
                            fputcsv($fp, $rowData);
                        }
                    }
                }

                // 退款单--推送已退款、退款申请中
                if(in_array($v['pay_status'], array(4,5))){
                    ## 推送退款申请中 refunding ##
                    $refundingTime = $this->getRefundingTime($v['order_id']);
                    $rowData = array(
                        "\t" . $v['order_bn'] . "\t",'Dior','DMALL','refunding',$v['createtime'],$refundingTime,'','','','',''
                    );
                    mb_convert_variables('GBK', 'UTF-8', $rowData);
                    fputcsv($fp, $rowData);

                    ## 推送已退款 refunded ##
                    $refunded = $this->getRefunded($v['order_id']); // 获取退款单信息
                    $rowData = array(
                        "\t" . $v['order_bn'] . "\t",'Dior','DMALL','refunded',$v['createtime'],$refunded['t_sent'],'','','',"\t" . $refunded['refund_bn'] . "\t",$refunded['money']
                    );
                    mb_convert_variables('GBK', 'UTF-8', $rowData);
                    fputcsv($fp, $rowData);

                }else{
                    // 退款申请中---在sdb_ome_refund_apply表中存在数据并且状态不为0、1
                    $sql = "select apply_id from sdb_ome_refund_apply where `status` not in ('0','1') and order_id='{$v['order_id']}'";
                    $refunding = $orderModel->db->select($sql);
                    if($refunding){
                        ## 推送退款申请中 refunding ##
                        $refundingTime = $this->getRefundingTime($v['order_id']);
                        $rowData = array(
                            "\t" . $v['order_bn'] . "\t",'Dior','DMALL','refunding',$v['createtime'],$refundingTime,'','','','',''
                        );
                        mb_convert_variables('GBK', 'UTF-8', $rowData);
                        fputcsv($fp, $rowData);
                    }
                }
                // 已完成
                if($v['route_status'] == '1' && $v['ship_status'] == '1'){
                    ## 推送已完成 completed ##
                    $rowData = array(
                        "\t" . $v['order_bn'] . "\t",'Dior','DMALL','completed',$v['createtime'],$v['last_modified'],'','','','',''
                    );
                    mb_convert_variables('GBK', 'UTF-8', $rowData);
                    fputcsv($fp, $rowData);
                }
                // 已取消订单
                if($v['status'] == 'dead' && $v['process_status'] == 'cancel'){
                    ## 推送已取消 cancel ##
                    $rowData = array(
                        "\t" . $v['order_bn'] . "\t",'Dior','DMALL','cancel',$v['createtime'],$v['last_modified'],'','','','',''
                    );
                    mb_convert_variables('GBK', 'UTF-8', $rowData);
                    fputcsv($fp, $rowData);
                }
                // 销毁临时变量
                unset($sql,$syncedTime,$shippedTime,$orderItem,$reshippingTime,$reshippedTime,$reshipItem,$refundingTime,$refunded,$refunding);
            }

            unset($orderList,$sql); // 释放变量的内存

            ob_flush(); // 刷新输出缓冲到浏览器
            flush();    // 必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
        }
        fclose($fp);
    }

    /**
     * 导出订单历史状态数据-csv文件
     * @param $timeStart
     * @param $timeEnd
     * @return bool
     */
    public function getExcel($timeStart, $timeEnd){
        // 设置配置项
        set_time_limit(0); // 程序执行时间
        ignore_user_abort(true); // 客户端断开后,仍然继续运行
        ini_set('memory_limit', '2048M'); // 运行内存
        // 时间文本转成时间戳
        $startTime = strtotime($timeStart);
        $endTime   = strtotime($timeEnd);
        // 参数判断
        if(!is_numeric($startTime) || !is_numeric($endTime) || ($endTime <= $startTime)){
            return false;
        }
        // 引入model
        $orderModel = app::get('ome')->model('orders');
        $kafkaDir= ROOT_DIR . '/data/kafka_order_history/'; // 文件保存目录
        $fileLog = ROOT_DIR . '/data/kafka_order_history/kafka_file_log'.$timeStart.'_'.$timeEnd.'.txt'; // log日志文件
        // 判断目录是否存在
        if (!file_exists($kafkaDir)) {
            $u_mask = umask(0);	            // 处理umask情况
            mkdir($kafkaDir, 0777, true);   // 创建解压目录 recursive参数表示是否创建多重目录 true/false
            umask($u_mask);
        }
        // 判断log文件是否存在
        if(!file_exists($fileLog)){
            $u_mask = umask(0);	    // 处理umask情况
            fopen($fileLog, "a+");  // 创建log日志
            umask($u_mask);
        }

        // 时间处理
        $monthList = $this->monthList($startTime, $endTime);

        foreach ($monthList as $mKey=>$mValue){

            // 打开文件
            $fOpen = fopen($kafkaDir . $mKey . '.csv', 'wb');
            // excel字段信息
            $columns = array(
                'order_bn','brand','Channel','status','createtime','Status_change_time','logi_bn(物流单号)','sku(商品sku)','num(商品数量)','bn(退款单号)','money(退款金额)'
            );
            mb_convert_variables('GBK', 'UTF-8', $columns);
            fputcsv($fOpen, $columns);

            // 获取订单数据量
            $sql = "SELECT count(order_id) num FROM sdb_ome_orders 
                    where createtime >= '{$mValue['start']}' and createtime < '{$mValue['end']}' and pay_status not in ('0','8')";

            $numRes = $orderModel->db->select($sql);

            $limit = 600;  // 每次查询的条数
            $pages = ceil($numRes[0]['num'] / $limit); // 总页码

            for($i = 0; $i < $pages; $i++) {

                $offset = $i * $limit;  // 偏移量

                $sql = "SELECT order_bn,order_id,createtime,logi_no,paytime,last_modified,status,pay_status,ship_status,route_status,
                        routetime,order_confirm_time,process_status 
                        FROM sdb_ome_orders 
                        where createtime >= '{$mValue['start']}' and createtime < '{$mValue['end']}' and pay_status not in ('0','8') 
                        limit $offset, $limit";

                ##记录日志##
                $myFile = fopen($fileLog, 'a+');
                $res = $sql . "\n";
                fwrite($myFile, $res);
                fclose($myFile);
                ##记录日志##

                // 查询数据
                $orderList = $orderModel->db->select($sql);

                // 订单状态处理
                foreach ($orderList as $k=>$v){
                    // 已支付状态
                    ## 推送已支付 paid ##
                    $rowData = array(
                        "\t" . $v['order_bn'] . "\t",'Dior','DMALL','paid',$v['createtime'],$v['paytime'],'','','','',''
                    );
                    mb_convert_variables('GBK', 'UTF-8', $rowData);
                    fputcsv($fOpen, $rowData);

                    // 判断订单是否发货--推送已审核、已发货、退货申请中、已退货
                    if($v['ship_status'] == '0'){
                        if($v['process_status'] == 'splited'){
                            ## 推送已审核 synced ##
                            $syncedTime = $this->getDeliveryTime($v['order_id'], 'synced'); // 获取已审核时间
                            $rowData = array(
                                "\t" . $v['order_bn'] . "\t",'Dior','DMALL','synced',$v['createtime'],$syncedTime,'','','','',''
                            );
                            mb_convert_variables('GBK', 'UTF-8', $rowData);
                            fputcsv($fOpen, $rowData);
                        }
                    }else{
                        // 判断订单是否发货 推送已审核、已发货
                        if(in_array($v['ship_status'], array(1,2))){
                            ## 推送已审核 synced ##
                            $syncedTime = $this->getDeliveryTime($v['order_id'], 'synced'); // 获取已审核时间
                            $rowData = array(
                                "\t" . $v['order_bn'] . "\t",'Dior','DMALL','synced',$v['createtime'],$syncedTime,'','','','',''
                            );
                            mb_convert_variables('GBK', 'UTF-8', $rowData);
                            fputcsv($fOpen, $rowData);

                            ## 推送已发货 shipped ##
                            $shippedTime = $this->getDeliveryTime($v['order_id'], 'shipped'); // 获取发货时间
//                            $orderItem   = $this->getOrderItem($v['order_id']); // 获取订单商品信息
//                            foreach ($orderItem as $itemKey=>$itemValue){
//                                $rowData = array(
//                                    "\t" . $v['order_bn'] . "\t",'Dior','DMALL','shipped',$v['createtime'],$shippedTime,"\t" . $v['logi_no'] . "\t","\t" . $itemValue['bn'] . "\t",$itemValue['nums'],'',''
//                                );
//                                mb_convert_variables('GBK', 'UTF-8', $rowData);
//                                fputcsv($fOpen, $rowData);
//                            }
                            $rowData = array(
                                "\t" . $v['order_bn'] . "\t",'Dior','DMALL','shipped',$v['createtime'],$shippedTime,"\t" . $v['logi_no'] . "\t",'','','',''
                            );
                            mb_convert_variables('GBK', 'UTF-8', $rowData);
                            fputcsv($fOpen, $rowData);
                        }
                        // 判断订单是否发生退货 推送已审核、已发货、退货申请中、已退货
                        if(in_array($v['ship_status'], array(3,4))){
                            ## 推送已审核 synced ##
                            $syncedTime = $this->getDeliveryTime($v['order_id'], 'synced'); // 获取已审核时间
                            $rowData = array(
                                "\t" . $v['order_bn'] . "\t",'Dior','DMALL','synced',$v['createtime'],$syncedTime,'','','','',''
                            );
                            mb_convert_variables('GBK', 'UTF-8', $rowData);
                            fputcsv($fOpen, $rowData);

                            ## 推送已发货 shipped ##
                            $shippedTime = $this->getDeliveryTime($v['order_id'], 'shipped'); // 获取发货时间
//                            $orderItem   = $this->getOrderItem($v['order_id']); // 获取订单商品信息
//                            foreach ($orderItem as $itemKey=>$itemValue){
//                                $rowData = array(
//                                    "\t" . $v['order_bn'] . "\t",'Dior','DMALL','shipped',$v['createtime'],$shippedTime,"\t" . $v['logi_no'] . "\t","\t" . $itemValue['bn'] . "\t",$itemValue['nums'],'',''
//                                );
//                                mb_convert_variables('GBK', 'UTF-8', $rowData);
//                                fputcsv($fOpen, $rowData);
//                            }
                            $rowData = array(
                                "\t" . $v['order_bn'] . "\t",'Dior','DMALL','shipped',$v['createtime'],$shippedTime,"\t" . $v['logi_no'] . "\t",'','','',''
                            );
                            mb_convert_variables('GBK', 'UTF-8', $rowData);
                            fputcsv($fOpen, $rowData);

                            ## 推送退货申请中 reshipping ##
                            $reshippingTime = $this->getReshipTime($v['order_id'], 'reshipping'); // 获取退货申请中时间
                            $rowData = array(
                                "\t" . $v['order_bn'] . "\t",'Dior','DMALL','reshipping',$v['createtime'],$reshippingTime,'','','','',''
                            );
                            mb_convert_variables('GBK', 'UTF-8', $rowData);
                            fputcsv($fOpen, $rowData);

                            ## 推送已退货 reshipped ##
                            $reshippedTime = $this->getReshipTime($v['order_id'], 'reshipped'); // 获取已退货时间
                            $reshipItem    = $this->getReshipItem($v['order_id']); // 获取退货商品
                            foreach ($reshipItem as $reshipKey=>$reshipValue){
                                $rowData = array(
                                    "\t" . $v['order_bn'] . "\t",'Dior','DMALL','reshipped',$v['createtime'],$reshippedTime,'',"\t" . $reshipValue['bn'] . "\t",$reshipValue['num'],'',''
                                );
                                mb_convert_variables('GBK', 'UTF-8', $rowData);
                                fputcsv($fOpen, $rowData);
                            }
                        }
                    }

                    // 退款单--推送已退款、退款申请中
                    if(in_array($v['pay_status'], array(4,5))){
                        ## 推送退款申请中 refunding ##
                        $refundingTime = $this->getRefundingTime($v['order_id']);
                        $rowData = array(
                            "\t" . $v['order_bn'] . "\t",'Dior','DMALL','refunding',$v['createtime'],$refundingTime,'','','','',''
                        );
                        mb_convert_variables('GBK', 'UTF-8', $rowData);
                        fputcsv($fOpen, $rowData);

                        ## 推送已退款 refunded ##
                        $refunded = $this->getRefunded($v['order_id']); // 获取退款单信息
                        $rowData = array(
                            "\t" . $v['order_bn'] . "\t",'Dior','DMALL','refunded',$v['createtime'],$refunded['t_sent'],'','','',"\t" . $refunded['refund_bn'] . "\t",$refunded['money']
                        );
                        mb_convert_variables('GBK', 'UTF-8', $rowData);
                        fputcsv($fOpen, $rowData);

                    }else{
                        // 退款申请中---在sdb_ome_refund_apply表中存在数据并且状态不为0、1
                        $sql = "select apply_id from sdb_ome_refund_apply where `status` not in ('0','1') and order_id='{$v['order_id']}'";
                        $refunding = $orderModel->db->select($sql);
                        if($refunding){
                            ## 推送退款申请中 refunding ##
                            $refundingTime = $this->getRefundingTime($v['order_id']);
                            $rowData = array(
                                "\t" . $v['order_bn'] . "\t",'Dior','DMALL','refunding',$v['createtime'],$refundingTime,'','','','',''
                            );
                            mb_convert_variables('GBK', 'UTF-8', $rowData);
                            fputcsv($fOpen, $rowData);
                        }
                    }
                    // 已完成
                    if($v['route_status'] == '1' && $v['ship_status'] == '1'){
                        ## 推送已完成 completed ##
                        $rowData = array(
                            "\t" . $v['order_bn'] . "\t",'Dior','DMALL','completed',$v['createtime'],$v['last_modified'],'','','','',''
                        );
                        mb_convert_variables('GBK', 'UTF-8', $rowData);
                        fputcsv($fOpen, $rowData);
                    }
                    // 已取消订单
                    if($v['status'] == 'dead' && $v['process_status'] == 'cancel'){
                        ## 推送已取消 cancel ##
                        $rowData = array(
                            "\t" . $v['order_bn'] . "\t",'Dior','DMALL','cancel',$v['createtime'],$v['last_modified'],'','','','',''
                        );
                        mb_convert_variables('GBK', 'UTF-8', $rowData);
                        fputcsv($fOpen, $rowData);
                    }
                    // 销毁临时变量
                    unset($sql,$syncedTime,$shippedTime,$orderItem,$reshippingTime,$reshippedTime,$reshipItem,$refundingTime,$refunded,$refunding);
                }
                unset($orderList,$sql); // 释放变量的内存
            }
            fclose($fOpen);
        }
    }

    /**
     * 订单历史状态excel-通过浏览器下载
     * @param $startTime
     * @param $endTime
     * @return bool
     */
    public function order_history_status_xls($timeStart, $timeEnd){
        // 设置配置项
        ini_set('memory_limit', '2048M');
        set_time_limit(0);
        ignore_user_abort(true); // 客户端断开后,仍然继续运行
        // 时间文本转成时间戳
        $startTime = strtotime($timeStart);
        $endTime   = strtotime("$timeEnd +1 day");
        // 参数判断
        if(!is_numeric($startTime) || !is_numeric($endTime) || ($endTime <= $startTime)){
            return false;
        }
        // 引入model
        $orderModel = app::get('ome')->model('orders');
        // 引入excel处理类
        require_once PHPEXCEL_ROOT . '/PHPExcel.php';
        require_once PHPEXCEL_ROOT . '/PHPExcel/Writer/Excel5.php';

        $kafkaDir= ROOT_DIR . '/data/kafka_history_excel/'; // 文件保存目录
        $fileLog = ROOT_DIR . '/data/kafka_history_excel/kafka_file_log'.$timeStart.'_'.$timeEnd.'.txt'; // log日志文件
        // 判断目录是否存在
        if (!file_exists($kafkaDir)) {
            $u_mask = umask(0);	            // 处理umask情况
            mkdir($kafkaDir, 0777, true);   // 创建解压目录 recursive参数表示是否创建多重目录 true/false
            umask($u_mask);
        }
        // 判断log文件是否存在
        if(!file_exists($fileLog)){
            $u_mask = umask(0);	    // 处理umask情况
            fopen($fileLog, "a+");  // 创建log日志
            umask($u_mask);
        }

        // 实例化PHPExcel
        $objExcel  = new PHPExcel();
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);

        $objProps = $objExcel->getProperties();
        $objProps->setCreator('order_history'); // 设置文档属性
        $objExcel->setActiveSheetIndex(0);          // 操作第一个工作表
        $objActSheet = $objExcel->getActiveSheet();
        $objActSheet->setTitle($timeStart . '_' . $timeEnd . '订单');      // 设置标题
        // 设置字段信息
        $objActSheet->setCellValue('A1', 'order_bn');
        $objActSheet->setCellValue('B1', 'brand');
        $objActSheet->setCellValue('C1', 'Channel');
        $objActSheet->setCellValue('D1', 'status');
        $objActSheet->setCellValue('E1', 'createtime');
        $objActSheet->setCellValue('F1', 'Status_change_time');
        $objActSheet->setCellValue('G1', 'logi_bn(物流单号)');
        $objActSheet->setCellValue('H1', 'sku(商品sku)');
        $objActSheet->setCellValue('I1', 'num(商品数量)');
        $objActSheet->setCellValue('J1', 'bn(退款单号)');
        $objActSheet->setCellValue('K1', 'money(退款金额)');

        $i     = 1;     // 插入数据初始值
        $page  = 0;     // 页码
        $limit = 1000;  // 分批次处理 每次处理1000条数据
        while(1){
            // 偏移量
            $offset = $page * $limit;

            $orderSql = "SELECT order_bn,order_id,createtime,logi_no,paytime,last_modified,status,pay_status,ship_status,route_status,
                    routetime,order_confirm_time,process_status 
                    FROM sdb_ome_orders 
                    where createtime >= '$startTime' and createtime < '$endTime' and pay_status not in ('0','8') 
                    limit $offset, $limit";

            ##记录日志##
            $myFile = fopen($fileLog, 'a+');
            $res = $orderSql . "\n";
            fwrite($myFile, $res);
            fclose($myFile);
            ##记录日志##

            // 查询数据
            $orderList = $orderModel->db->select($orderSql);

//                'paid'=>'已支付',
//                'synced'=>'已审核',
//                'shipped'=>'已发货',
//                'completed'=>'已完成',
//                'reshipping'=>'退货申请中',
//                'reshipped'=>'已退货',
//                'refunding'=>'退款申请中',
//                'refunded'=>'已退款',
//                'cancel'=>'已取消'

            // 订单状态处理
            foreach ($orderList as $k=>$v){
                // 已支付状态
                //if(!in_array($v['pay_status'], array(0,8))){
                ## 推送已支付 paid ##
                $i++;
                $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                $objActSheet->setCellValue('B' . $i, 'Dior');
                $objActSheet->setCellValue('C' . $i, 'DMALL');
                $objActSheet->setCellValue('D' . $i, 'paid');
                $objActSheet->setCellValue('E' . $i, $v['createtime']);
                $objActSheet->setCellValue('F' . $i, $v['paytime']);
                $objActSheet->setCellValue('G' . $i, '');
                $objActSheet->setCellValue('H' . $i, '');
                $objActSheet->setCellValue('I' . $i, '');
                $objActSheet->setCellValue('J' . $i, '');
                $objActSheet->setCellValue('K' . $i, '');
                //}
                // 判断订单是否发货--推送已审核、已发货、退货申请中、已退货
                if($v['ship_status'] == '0'){
                    if($v['process_status'] == 'splited'){
                        ## 推送已审核 synced ##
                        $syncedTime = $this->getDeliveryTime($v['order_id'], 'synced'); // 获取已审核时间
                        $i++;
                        $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                        $objActSheet->setCellValue('B' . $i, 'Dior');
                        $objActSheet->setCellValue('C' . $i, 'DMALL');
                        $objActSheet->setCellValue('D' . $i, 'synced');
                        $objActSheet->setCellValue('E' . $i, $v['createtime']);
                        $objActSheet->setCellValue('F' . $i, $syncedTime);
                        $objActSheet->setCellValue('G' . $i, '');
                        $objActSheet->setCellValue('H' . $i, '');
                        $objActSheet->setCellValue('I' . $i, '');
                        $objActSheet->setCellValue('J' . $i, '');
                        $objActSheet->setCellValue('K' . $i, '');
                    }
                }else{
                    // 判断订单是否发货 推送已审核、已发货
                    if(in_array($v['ship_status'], array(1,2))){
                        ## 推送已审核 synced ##
                        $syncedTime = $this->getDeliveryTime($v['order_id'], 'synced'); // 获取已审核时间
                        $i++;
                        $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                        $objActSheet->setCellValue('B' . $i, 'Dior');
                        $objActSheet->setCellValue('C' . $i, 'DMALL');
                        $objActSheet->setCellValue('D' . $i, 'synced');
                        $objActSheet->setCellValue('E' . $i, $v['createtime']);
                        $objActSheet->setCellValue('F' . $i, $syncedTime);
                        $objActSheet->setCellValue('G' . $i, '');
                        $objActSheet->setCellValue('H' . $i, '');
                        $objActSheet->setCellValue('I' . $i, '');
                        $objActSheet->setCellValue('J' . $i, '');
                        $objActSheet->setCellValue('K' . $i, '');

                        ## 推送已发货 shipped ##
                        $shippedTime = $this->getDeliveryTime($v['order_id'], 'shipped'); // 获取发货时间
//                        $orderItem = $this->getOrderItem($v['order_id']); // 获取订单商品信息
//                        foreach ($orderItem as $itemKey=>$itemValue){
//                            $i++;
//                            $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
//                            $objActSheet->setCellValue('B' . $i, 'Dior');
//                            $objActSheet->setCellValue('C' . $i, 'DMALL');
//                            $objActSheet->setCellValue('D' . $i, 'shipped');
//                            $objActSheet->setCellValue('E' . $i, $v['createtime']);
//                            $objActSheet->setCellValue('F' . $i, $shippedTime);
//                            $objActSheet->setCellValue('G' . $i, "\t" . $v['logi_no'] . "\t");
//                            $objActSheet->setCellValue('H' . $i, "\t" . $itemValue['bn'] . "\t");
//                            $objActSheet->setCellValue('I' . $i, $itemValue['nums']);
//                            $objActSheet->setCellValue('J' . $i, '');
//                            $objActSheet->setCellValue('K' . $i, '');
//                        }
                        $i++;
                        $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                        $objActSheet->setCellValue('B' . $i, 'Dior');
                        $objActSheet->setCellValue('C' . $i, 'DMALL');
                        $objActSheet->setCellValue('D' . $i, 'shipped');
                        $objActSheet->setCellValue('E' . $i, $v['createtime']);
                        $objActSheet->setCellValue('F' . $i, $shippedTime);
                        $objActSheet->setCellValue('G' . $i, "\t" . $v['logi_no'] . "\t");
                        $objActSheet->setCellValue('H' . $i, '');
                        $objActSheet->setCellValue('I' . $i, '');
                        $objActSheet->setCellValue('J' . $i, '');
                        $objActSheet->setCellValue('K' . $i, '');
                    }
                    // 判断订单是否发生退货 推送已审核、已发货、退货申请中、已退货
                    if(in_array($v['ship_status'], array(3,4))){
                        ## 推送已审核 synced ##
                        $syncedTime = $this->getDeliveryTime($v['order_id'], 'synced'); // 获取已审核时间
                        $i++;
                        $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                        $objActSheet->setCellValue('B' . $i, 'Dior');
                        $objActSheet->setCellValue('C' . $i, 'DMALL');
                        $objActSheet->setCellValue('D' . $i, 'synced');
                        $objActSheet->setCellValue('E' . $i, $v['createtime']);
                        $objActSheet->setCellValue('F' . $i, $syncedTime);
                        $objActSheet->setCellValue('G' . $i, '');
                        $objActSheet->setCellValue('H' . $i, '');
                        $objActSheet->setCellValue('I' . $i, '');
                        $objActSheet->setCellValue('J' . $i, '');
                        $objActSheet->setCellValue('K' . $i, '');

                        ## 推送已发货 shipped ##
                        $shippedTime = $this->getDeliveryTime($v['order_id'], 'shipped'); // 获取发货时间
//                        $orderItem = $this->getOrderItem($v['order_id']); // 获取订单商品信息
//                        foreach ($orderItem as $itemKey=>$itemValue){
//                            $i++;
//                            $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
//                            $objActSheet->setCellValue('B' . $i, 'Dior');
//                            $objActSheet->setCellValue('C' . $i, 'DMALL');
//                            $objActSheet->setCellValue('D' . $i, 'shipped');
//                            $objActSheet->setCellValue('E' . $i, $v['createtime']);
//                            $objActSheet->setCellValue('F' . $i, $shippedTime);
//                            $objActSheet->setCellValue('G' . $i, "\t" . $v['logi_no'] . "\t");
//                            $objActSheet->setCellValue('H' . $i, "\t" . $itemValue['bn'] . "\t");
//                            $objActSheet->setCellValue('I' . $i, $itemValue['nums']);
//                            $objActSheet->setCellValue('J' . $i, '');
//                            $objActSheet->setCellValue('K' . $i, '');
//                        }
                        $i++;
                        $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                        $objActSheet->setCellValue('B' . $i, 'Dior');
                        $objActSheet->setCellValue('C' . $i, 'DMALL');
                        $objActSheet->setCellValue('D' . $i, 'shipped');
                        $objActSheet->setCellValue('E' . $i, $v['createtime']);
                        $objActSheet->setCellValue('F' . $i, $shippedTime);
                        $objActSheet->setCellValue('G' . $i, "\t" . $v['logi_no'] . "\t");
                        $objActSheet->setCellValue('H' . $i, '');
                        $objActSheet->setCellValue('I' . $i, '');
                        $objActSheet->setCellValue('J' . $i, '');
                        $objActSheet->setCellValue('K' . $i, '');

                        ## 推送退货申请中 reshipping ##
                        $reshippingTime = $this->getReshipTime($v['order_id'], 'reshipping'); // 获取退货申请中时间
                        $i++;
                        $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                        $objActSheet->setCellValue('B' . $i, 'Dior');
                        $objActSheet->setCellValue('C' . $i, 'DMALL');
                        $objActSheet->setCellValue('D' . $i, 'reshipping');
                        $objActSheet->setCellValue('E' . $i, $v['createtime']);
                        $objActSheet->setCellValue('F' . $i, $reshippingTime);
                        $objActSheet->setCellValue('G' . $i, '');
                        $objActSheet->setCellValue('H' . $i, '');
                        $objActSheet->setCellValue('I' . $i, '');
                        $objActSheet->setCellValue('J' . $i, '');
                        $objActSheet->setCellValue('K' . $i, '');

                        ## 推送已退货 reshipped ##
                        $reshippedTime = $this->getReshipTime($v['order_id'], 'reshipped'); // 获取已退货时间
                        $reshipItem = $this->getReshipItem($v['order_id']); // 获取退货商品
                        foreach ($reshipItem as $reshipKey=>$reshipValue){
                            $i++;
                            $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                            $objActSheet->setCellValue('B' . $i, 'Dior');
                            $objActSheet->setCellValue('C' . $i, 'DMALL');
                            $objActSheet->setCellValue('D' . $i, 'reshipped');
                            $objActSheet->setCellValue('E' . $i, $v['createtime']);
                            $objActSheet->setCellValue('F' . $i, $reshippedTime);
                            $objActSheet->setCellValue('G' . $i, '');
                            $objActSheet->setCellValue('H' . $i, "\t" . $reshipValue['bn'] . "\t");
                            $objActSheet->setCellValue('I' . $i, $reshipValue['num']);
                            $objActSheet->setCellValue('J' . $i, '');
                            $objActSheet->setCellValue('K' . $i, '');
                        }
                    }
                }
                // 退款单--推送已退款、退款申请中
                if(in_array($v['pay_status'], array(4,5))){
                    ## 推送退款申请中 refunding ##
                    $refundingTime = $this->getRefundingTime($v['order_id']);
                    $i++;
                    $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                    $objActSheet->setCellValue('B' . $i, 'Dior');
                    $objActSheet->setCellValue('C' . $i, 'DMALL');
                    $objActSheet->setCellValue('D' . $i, 'refunding');
                    $objActSheet->setCellValue('E' . $i, $v['createtime']);
                    $objActSheet->setCellValue('F' . $i, $refundingTime);
                    $objActSheet->setCellValue('G' . $i, '');
                    $objActSheet->setCellValue('H' . $i, '');
                    $objActSheet->setCellValue('I' . $i, '');
                    $objActSheet->setCellValue('J' . $i, '');
                    $objActSheet->setCellValue('K' . $i, '');

                    ## 推送已退款 refunded ##
                    $refunded = $this->getRefunded($v['order_id']); // 获取退款单信息
                    $i++;
                    $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                    $objActSheet->setCellValue('B' . $i, 'Dior');
                    $objActSheet->setCellValue('C' . $i, 'DMALL');
                    $objActSheet->setCellValue('D' . $i, 'refunded');
                    $objActSheet->setCellValue('E' . $i, $v['createtime']);
                    $objActSheet->setCellValue('F' . $i, $refunded['t_sent']);
                    $objActSheet->setCellValue('G' . $i, '');
                    $objActSheet->setCellValue('H' . $i, '');
                    $objActSheet->setCellValue('I' . $i, '');
                    $objActSheet->setCellValue('J' . $i, "\t" . $refunded['refund_bn'] . "\t");
                    $objActSheet->setCellValue('K' . $i, $refunded['money']);

                }else{
                    // 退款申请中---在sdb_ome_refund_apply表中存在数据并且状态不为0、1
                    $sql = "select apply_id from sdb_ome_refund_apply where `status` not in ('0','1') and order_id='{$v['order_id']}'";
                    $refunding = $orderModel->db->select($sql);
                    if($refunding){
                        ## 推送退款申请中 refunding ##
                        $refundingTime = $this->getRefundingTime($v['order_id']);
                        $i++;
                        $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                        $objActSheet->setCellValue('B' . $i, 'Dior');
                        $objActSheet->setCellValue('C' . $i, 'DMALL');
                        $objActSheet->setCellValue('D' . $i, 'refunding');
                        $objActSheet->setCellValue('E' . $i, $v['createtime']);
                        $objActSheet->setCellValue('F' . $i, $refundingTime);
                        $objActSheet->setCellValue('G' . $i, '');
                        $objActSheet->setCellValue('H' . $i, '');
                        $objActSheet->setCellValue('I' . $i, '');
                        $objActSheet->setCellValue('J' . $i, '');
                        $objActSheet->setCellValue('K' . $i, '');
                    }
                }
                // 已完成
                if($v['route_status'] == '1' && $v['ship_status'] == '1'){
                    ## 推送已完成 completed ##
                    $i++;
                    $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                    $objActSheet->setCellValue('B' . $i, 'Dior');
                    $objActSheet->setCellValue('C' . $i, 'DMALL');
                    $objActSheet->setCellValue('D' . $i, 'completed');
                    $objActSheet->setCellValue('E' . $i, $v['createtime']);
                    $objActSheet->setCellValue('F' . $i, $v['last_modified']);
                    $objActSheet->setCellValue('G' . $i, '');
                    $objActSheet->setCellValue('H' . $i, '');
                    $objActSheet->setCellValue('I' . $i, '');
                    $objActSheet->setCellValue('J' . $i, '');
                    $objActSheet->setCellValue('K' . $i, '');
                }
                // 已取消订单
                if($v['status'] == 'dead' && $v['process_status'] == 'cancel'){
                    ## 推送已取消 cancel ##
                    $i++;
                    $objActSheet->setCellValue('A' . $i, "\t" . $v['order_bn'] . "\t");
                    $objActSheet->setCellValue('B' . $i, 'Dior');
                    $objActSheet->setCellValue('C' . $i, 'DMALL');
                    $objActSheet->setCellValue('D' . $i, 'cancel');
                    $objActSheet->setCellValue('E' . $i, $v['createtime']);
                    $objActSheet->setCellValue('F' . $i, $v['last_modified']);
                    $objActSheet->setCellValue('G' . $i, '');
                    $objActSheet->setCellValue('H' . $i, '');
                    $objActSheet->setCellValue('I' . $i, '');
                    $objActSheet->setCellValue('J' . $i, '');
                    $objActSheet->setCellValue('K' . $i, '');
                }
                // 销毁临时变量
                unset($sql,$syncedTime,$shippedTime,$orderItem,$reshippingTime,$reshippedTime,$reshipItem,$refundingTime,$refunded,$refunding);
            }

            // 是否跳出循环
            if(count($orderList) < $limit){
                break;
            }
            // 销毁临时变量
            unset($orderList,$orderSql);
            $page++;
        }
        // 销毁临时变量
        unset($orderModel,$startTime,$endTime,$fileLog);
        // 保存文件
//        $filename = $kafkaDir . '/' . $timeStart . '_' . $timeEnd . '.xls';
//        $objWriter->save($filename);

        // 生成文件供浏览器下载
        $filename = $timeStart . '_' . $timeEnd . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
        exit;
    }
}

