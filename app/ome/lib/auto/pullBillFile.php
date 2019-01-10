<?php

// 设置时区
date_default_timezone_set('PRC');

/**
 * Class ome_auto_pullBillFile
 * 拉取支付宝、微信对账单
 * @data 2018-05-11 17:48
 * @user august.yao
 */
class ome_auto_pullBillFile{

    /**
     * 传参数版拉取微信、支付宝账单
     * @param $bill_date 账单日期
     * @param $type 账单类型 aliPay：支付宝, WeChat：微信
     */
    public function pull_bill_handle($bill_date = '', $type = ''){
        $type == 'aliPay' ? $this->do_pull_bill_file_ali($bill_date) : $this->do_pull_bill_file_WeChat($bill_date);
    }

    /**
     * 拉取支付宝账单并进行处理
     * @param $bill_date 账单日期
     * @throws Exception
     */
    public function do_pull_bill_file_ali($bill_date = ''){

        @set_time_limit(600);
        @ini_set('memory_limit','1024M');

        // 引入SDK
        require_once APP_DIR . '/ome/lib/alipay_pull_bill_sdk/aop/AopClient.php';
        require_once APP_DIR . '/ome/lib/alipay_pull_bill_sdk/aop/request/AlipayDataDataserviceBillDownloadurlQueryRequest.php';
        $AopClient = new AopClient ();
        // 引入文件
        $fileHandle = kernel::single('ome_auto_fileHandle');
        // 基本信息配置
        $AopClient->appId 	           = '2016011201085238';
        $AopClient->apiVersion         = '1.0';
        $AopClient->signType           = 'RSA2';
        $AopClient->postCharset        = 'UTF-8';
        $AopClient->format             = 'json';
        $AopClient->gatewayUrl         = 'https://openapi.alipay.com/gateway.do';
        $AopClient->rsaPrivateKey      = 'MIIEpAIBAAKCAQEAvmq+J1XoIpGyvzHn96yT67qg12Y5Hw2ZPfxBEtAbJESBH+8044MBgqP6P3TKNVEl6/vLMPKcisZGbL+d1u+SqpjKmYBSkeeAONWJ42OGzDBTExIpZhWtLb/Z0Tcy+i+xZrmLzNUDO2KXJo/VSXqJY3+ZqMN7ZPD4YnlsUGX/aneWtdd5ix3iaL9tlfs+32ameEDEBdzQCHiaRT9Q68v+mkQJqZGFfK53e4H49xPDJl3NKpQk3rmSWBtozoxX2UxIHMjZk5NjAu2dlIXRrUepdgIzk5F70UGYg4zDPgqlgubc6B1rJ8TJJwh5mAgurLsR55DTIB5zoL+qhXDzSxZ94wIDAQABAoIBAE29PozIodEbMqKkOVQAfROMSgYmkcN/xcRsrBSGKc6QuTbUbf9EiOVRFOW7RKKn0peZYMdHBWoSsMXNJR+PB43mezBYF13B6bNKWtUe2PWb9Q6gJ46p4t3+qWqk4ocJ2Z9auRyTCpLAGX2A008AW/7nJVClHkeDqaRqdjIMsk5AeBy+wbNDLxiKIA0vE07dFKwYFomlhUohj9nPtmFOibi3Ndq6NKDixsOvEcVsJvAmidhrTNH93Cp1Iu4kvL7xHSpy8l8aC6ExWjdPqOjjh4j+5D+xAJ0DVcOSPEwNLJLWC+JUjkUoPKETHuqUvGtNpbB3L21PCMuFSf5aQyMlA6ECgYEA6KJTNxvgdbGQ7cgGfTFonwTHHzRy/QXTnLCOSLvMI09WLLqD9+bjIVdRN4AFBcNZe+0SHc3/zrxkBljHNmmNpJ3q5nE5iIU4bGPriuTxxlv+xUvCoJ4PwN/E79eC1x4PicZ4u2+vCfdkjCaJpBn92foZCePF3EHI1HkiZUCYs48CgYEA0YrlrRQlBeHENnt9HT7Y6O/tgXAz1TvByk14zxOENbKjBF+cFVgHgMp2vavke/o27owbcoUpOqs0NsKzE7MzNqegGDz78ld3AZLKRGeKvITEusyjL2q6QTQuFtoPF00zzTb+qFrBHF2H1gsBeol1JKgcbq4Q9vMOW9mEisZAVm0CgYBBKidRSpfHoCPO17cpnouF79qvXQvQ4K70N2jD6wc15OVoxh5tdNmu3zy65TuC78PDORdYDfpYOOFz/a5gCZVJxYqlXpYVLewuQJccYyFuxRpKvfwY7e3Pw17xKFdYVu1XVKdBKfnIOuqJb8oik71OMAaPhKRKHItbKUt/PWcI9QKBgQCSyE1jFH1qxV+BGrflyKSBZ7Bjv4lPkXCKmgJKbE1soBn5clAP035OBYVwD/0Zq4jXSWXgrs7Wvh7xTSnMXvc9Debjk7SZahN/EqiFilJxBEAa6kZVbNnT2w1+DHoNxJLI6gExRYGIsSv6D/OVNrzMVMa18VEI6/He/VwPKhjfcQKBgQDCfjpp9S6rMHLq352a/H7DkPDtoe1WMpKqxmPTlPIPOcM3YWy0PmXkw8Jz+7I1fU5MEXfTfSO41y0enlzwt5ri/8pnSauUQt5hCfhFBMJ0VZATRTUn84swx9MzKpA9Gm/f/dBXPm6uHCVfskX85bJ62L3YVfT9VaQiLIx2/l4bww==';
        $AopClient->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlJ/uEP5uGb0gHLa3PBGKsjblEew+cCoucu/VF/R7fYuJPJ8OMxPLJn4PJNbFY4tjRSohWVLOs9h0eoLvoCDx/BmSzN0kCowpYQIrbvjw6RRgNzK6Tf1TIOtoEi+PXzNz/hJwMTbisouWv51j1piSBDSfY/vS5d5b2ZI5WEFS2w47oRdaRNd/+4mxjbogvLMoJMzpmtchna7dahKVm7DISQlqG9S7UoOT3Dpobna0PO5g89S6rLIXZPjoMn5ep6o72kS+u+rAr07YHQtmhx7c+ZkZkpRnXOddaNogr/GDmv/xH5y3AAsZLYSkNVmBrT8Cymwk4V/XdN+Vhdqyh+VKCwIDAQAB';
        // 判断传入的时间是否存在
        if(empty($bill_date)){
            $bill_date = date("Y-m-d", strtotime("-1 day"));
        }
        // bill_type 账单类型trade、signcustomer；trade指商户基于支付宝交易收单的业务账单；signcustomer是指基于商户支付宝余额收入及支出等资金变动的帐务账单；
        $arr     = array('bill_type' => 'signcustomer', 'bill_date' => $bill_date);
        $request = new AlipayDataDataserviceBillDownloadurlQueryRequest();
        $request->setBizContent(json_encode($arr));
        $result  = $AopClient->execute($request);  // 发起请求获取下载地址

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode   = $result->$responseNode->code;

        $dir     = ROOT_DIR . '/data/bill_data/ali_data/'; // 解压文件所保存的目录
        $fileLog = ROOT_DIR . '/data/bill_data/pull_bill_file_log.txt'; // log日志文件
        // 判断目录是否存在
        if (!file_exists($dir)) {
            $u_mask = umask(0);	// 处理umask情况
            mkdir($dir,0777,true);   // 创建解压目录 recursive参数表示是否创建多重目录 true/false
            umask($u_mask);
        }
        // 判断文件是否存在
        if(!file_exists($fileLog)){
            $u_mask = umask(0);	// 处理umask情况
            fopen($fileLog, "a+");  // 创建log日志
            umask($u_mask);
        }

        if(!empty($resultCode) && $resultCode == 10000){
            // 下载文件地址
            $url     = $result->$responseNode->bill_download_url;
            // 生成的zip文件
            $zipName =  $dir . date('Y-m-d',time() - 24 * 60 * 60) . '.zip';
            // 进行下载
            $res = $fileHandle->downFile($url,$zipName);
            if($res != 'true'){
                $fileHandle->write_log($fileLog, '支付宝账单下载失败：' . $res);return;
            }
            // 解压文件
            $fileHandle->get_zip($zipName, $dir, $fileLog);
            // 记录log
            $fileHandle->write_log($fileLog, '支付宝账单拉取成功');
            // 处理csv文件
            $fileHandle->save_csv_data($dir, $fileLog, 'alipay', $bill_date);
        }else{
            // 获取错误信息
            $errMsg = $request->$responseNode->sub_msg;
            // 记录日志
            $fileHandle->write_log($fileLog, '支付宝账单拉取失败-' . $errMsg);
        }
    }

    /**
     * 拉取微信账单并进行处理
     * @throws Exception
     */
    public function do_pull_bill_file_WeChat($bill_date = ''){

        @set_time_limit(600);
        @ini_set('memory_limit','1024M');

        // 引入文件
        $fileHandle = kernel::single('ome_auto_fileHandle');
        require_once APP_DIR . '/ome/lib/wxpay/lib/WxPay.Api.php';
        require_once APP_DIR . '/ome/lib/wxpay/log.php';
        
        // 实列化下载对账单对象
        $input = new WxPayDownloadBill();
        // 判断传入的时间是否存在
        if(empty($bill_date)){
            $bill_date = date("Ymd", strtotime("-1 day"));
        }
        // 对账单日期
        $input->SetBill_date($bill_date);
        // 对账单类型
        $input->SetBill_type('ALL');
        // 获取账单信息
        $downloadBillResult = WxPayApi::downloadBill($input);

        $dir     = ROOT_DIR . '/data/bill_data/WeChat_data/'; // 解压文件所保存的目录
        $fileLog = ROOT_DIR . '/data/bill_data/pull_bill_file_log.txt'; // log日志文件
        // 判断目录是否存在
        if (!file_exists($dir)) {
            $u_mask = umask(0);	// 处理umask情况
            mkdir($dir,0777,true);   // 创建解压目录 recursive参数表示是否创建多重目录 true/false
            umask($u_mask);
        }
        // 判断文件是否存在
        if(!file_exists($fileLog)){
            $u_mask = umask(0);	// 处理umask情况
            fopen($fileLog, "a+");  // 创建log日志
            umask($u_mask);
        }
        // 失败记录
        if (empty($downloadBillResult)) {
            // 记录log
            $fileHandle->write_log($fileLog, '微信账单拉取失败-接口问题');return;
        }
        // 返回数据
        $data = $this->deal_WeChat_result($downloadBillResult);
        // 将文件备份
        $backupDir = ROOT_DIR . '/data/bill_data/backup/WeChat_data/'; // 备份目录
        $this->put_to_file($backupDir, $bill_date, $downloadBillResult, false);
        // 记录log
        $fileHandle->write_log($fileLog, '微信账单拉取成功');
        // 处理csv文件
        $fileHandle->save_data_WeChat($data, $fileLog, 'weixin', $bill_date, false);

        ### 开始小程序账单处理 ###
        // 启用小程序配置
        WxPayConfig::$enable = true;
        // 对账单日期
        $input->SetBill_date($bill_date);
        // 对账单类型
        $input->SetBill_type('ALL');
        // 获取账单信息
        $downloadBillResult = WxPayApi::downloadBill($input);
        // 失败记录
        if (empty($downloadBillResult)) {
            $fileHandle->write_log($fileLog, '微信小程序账单拉取失败-接口问题');return;
        }
        // 返回数据
        $data = $this->deal_WeChat_result($downloadBillResult);
        // 将文件备份
        $backupDir = ROOT_DIR . '/data/bill_data/backup/WeChat_data/'; // 备份目录
        $this->put_to_file($backupDir, $bill_date, $downloadBillResult, true);
        // 记录log
        $fileHandle->write_log($fileLog, '微信小程序账单拉取成功');
        // 处理csv文件
        $fileHandle->save_data_WeChat($data, $fileLog, 'weixin', $bill_date, true);
        ### 小程序账单处理结束 ###
    }

    /**
     * 微信对账单数据处理
     * @param $response 对账单数据
     * @return array 返回结果
     */
    public function deal_WeChat_result($response){

        $result   = array();
        $response = explode(PHP_EOL, $response);

        foreach ($response as $key=>$val){
            if(stripos($val, '交易时间,公众账号ID,商户号,子商户号') !== false){
                // 去除bom头
                $val = $this->checkBOM($val);
                $result[] = explode(',', $val);
            }
            if(stripos($val, '`') !== false){
                $val  = str_replace(",","",$val);   // 将,替换掉
                $data = explode('`', $val);
                array_shift($data); // 删除第一个元素并下标从0开始
                if(count($data) == 24){ // 处理账单数据
                    $result[] = $data;
                }
                if(count($data) == 5){ // 统计数据
                    $result[] = $data;
                }
            }
            if(stripos($val, '总交易单数,总交易额,总退款金额,总企业红包退款金额,手续费总金额') !== false){
                // 去除bom头
                $val = $this->checkBOM($val);
                $result[] = explode(',', $val);
            }
        }
        return $result;
    }

    /**
     * 微信对账单数据处理
     * @param $response 对账单数据
     * @return array 返回结果
     */
    public function deal_WeChat_response($response){
        $result   = array();
        $response = str_replace(","," ",$response);
        $response = explode(PHP_EOL, $response);

        foreach ($response as $key=>$val){
            if(strpos($val, '`') !== false){
                $data = explode('`', $val);
                array_shift($data); // 删除第一个元素并下标从0开始
                if(count($data) == 24){ // 处理账单数据
                    $result['bill'][] = array(
                        'pay_time'             => $data[0], // 支付时间
                        'APP_ID'               => $data[1], // app_id
                        'MCH_ID'               => $data[2], // 商户id
                        'IMEI'                 => $data[4], // 设备号
                        'order_sn_wx'          => $data[5], // 微信订单号
                        'order_sn_sh'          => $data[6], // 商户订单号
                        'user_tag'             => $data[7], // 用户标识
                        'pay_type'             => $data[8], // 交易类型
                        'pay_status'           => $data[9], // 交易状态
                        'bank'                 => $data[10], // 付款银行
                        'money_type'           => $data[11], // 货币种类
                        'total_amount'         => $data[12], // 总金额
                        'coupon_amount'        => $data[13], // 代金券或立减优惠金额
                        'refund_number_wx'     => $data[14], // 微信退款单号
                        'refund_number_sh'     => $data[15], // 商户退款单号
                        'refund_amount'        => $data[16], // 退款金额
                        'coupon_refund_amount' => $data[17], // 代金券或立减优惠退款金额
                        'refund_type'          => $data[18], // 退款类型
                        'refund_status'        => $data[19], // 退款状态
                        'goods_name'           => $data[20], // 商品名称
                        'service_charge'       => $data[22], // 手续费
                        'rate'                 => $data[23], // 费率
                    );
                }
                if(count($data) == 5){ // 统计数据
                    $result['summary'] = array(
                        'order_num'       => $data[0],    // 总交易单数
                        'turnover'        => $data[1],    // 总交易额
                        'refund_turnover' => $data[2],    // 总退款金额
                        'coupon_turnover' => $data[3],    // 总代金券或立减优惠退款金额
                        'rate_turnover'   => $data[4],    // 手续费总金额
                    );
                }
            }
        }
        return $result;
    }

    /**
     * 将字符串写入文件
     * @param $filePath 文件路径
     * @param $content 文件内容
     * @param $isXcx 是否是小程序
     */
    public function put_to_file($filePath, $fileName, $content, $isXcx = false) {

        // 判断目录是否存在
        if (!file_exists($filePath)) {
            $u_mask = umask(0); // 处理umask情况
            mkdir($filePath, 0777 ,true);   // 创建备份目录 recursive参数表示是否创建多重目录 true/false
            umask($u_mask);
        }
        // 备份文件
        $file = $filePath . $fileName . '.csv';
        // 判断来源是否为小程序
        if($isXcx){
            $file = $filePath . $fileName . '_xcx' . '.csv';
        }
        // 打开文件
        $fOpen = fopen($file, 'wb');
        if (!$fOpen) {
            return false;
        }
        fwrite($fOpen, $content);
        fclose($fOpen);
        return true;
    }

    /**
     * 去除bom头
     * @param $contents
     * @return string
     */
    public function checkBOM($contents){

        $charset[1] = substr($contents, 0, 1);
        $charset[2] = substr($contents, 1, 1);
        $charset[3] = substr($contents, 2, 1);
        if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
            return substr($contents, 3);
        }else{
            return $contents;
        }
    }
}