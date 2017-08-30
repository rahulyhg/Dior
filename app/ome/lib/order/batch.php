<?php
/**
 * 订单批次号处理类
 * @author Chris.Zhang
 * @access public
 * @copyright www.shopex.cn 2010.12.30
 */
class ome_order_batch{
    
    /*
     * 订单批次索引号处理
     *
     * @param sdf $orderSdf
     * @param string $process （get：返回当前的批次索引号(获取一次都占一个号，慎用)；add：如果已存在批次索引号，则不更新；update：不管有没有批次索引号，都更新）
     */
    public function order_job_no($orderSdf, $process='get'){
        if ($process == 'get'){//返回当前的批次索引号(获取一次都占一个号，慎用)
            $md5 = md5($orderSdf['consignee']['addr'].$orderSdf['consignee']['name'].$orderSdf['consignee']['mobile']);
            return $this->get_order_job_no($orderSdf['shop_id'], $orderSdf['member_id'], $md5);
        }elseif ($process == 'add'){//如果已存在批次索引号，则不更新
            if (empty($orderSdf['order_id'])) return null;
            $order = app::get('ome')->model('orders')->dump($orderSdf['order_id']);
            if ($order['order_job_no']) return null;
            $md5 = md5($orderSdf['consignee']['addr'].$orderSdf['consignee']['name'].$orderSdf['consignee']['mobile']);
            return $this->add_order_job_no($orderSdf['order_id'], $orderSdf['shop_id'], $orderSdf['member_id'], $md5);
        }elseif ($process == 'update'){//不管有没有批次索引号，都更新
            if (empty($orderSdf['order_id'])) return null;
            $md5 = md5($orderSdf['consignee']['addr'].$orderSdf['consignee']['name'].$orderSdf['consignee']['mobile']);
            return $this->add_order_job_no($orderSdf['order_id'], $orderSdf['shop_id'], $orderSdf['member_id'], $md5);
        }
        return false;
    }
    
    /*
     * 新增或更新订单批次索引号
     * 
     * @param int $order_id 订单号
     * @param int $shop_id  来源店铺ID
     * @param string $username  前台会员名
     * @param md5 $md5  md5(收货人地址+收货人姓名+收货人手机号码)
     */
    public function add_order_job_no($order_id, $shop_id, $username, $md5){
        $orderObj = &app::get('ome')->model('orders');
        $job_no = $this->get_order_job_no($shop_id, $username, $md5);
        $order = array(
            'order_id' => $order_id,
            'order_job_no' => $job_no
        );
        
        return $orderObj->save($order);
    }
    
    /*
     * 获取订单批次索引号
     * 
     * @param int $shop_id  来源店铺ID
     * @param string $username  前台会员名 (当前用member_id)
     * @param md5 $md5  md5(收货人地址+收货人姓名+收货人手机号码)
     */
    public function get_order_job_no($shop_id, $username, $md5){
        $filter = array(
            'source_shop_id' => $shop_id,
            'source_account' => $username,
            'ship_info_md5' => $md5,
        ); 
        $obObj = &app::get('ome')->model('order_batch');
        $osdcObj = &app::get('ome')->model('order_ship_daily_count');
        $order_batch = $obObj->dump($filter);
        if ($order_batch){
            $date = date('ymd',strtotime($order_batch['order_date']));
            if(strlen($order_batch['ship_running_no']) > 5) {
                $ship_no = substr($order_batch['ship_running_no'], -5);
            }else{
                $ship_no = str_pad($order_batch['ship_running_no'], 5, '0', STR_PAD_LEFT);
            }
            $increment = $order_batch['increment'] + 1;
            if(strlen($increment) > 2) {
                $increment = substr($increment, -2);
            }else{
                $increment = str_pad($increment, 2, '0', STR_PAD_LEFT);
            }
            $orderB = $filter;
            $orderB['increment'] = $order_batch['increment']+1;
            $obObj->save($orderB);
            $job_no = $date.$ship_no.$increment;
            return $job_no;
        }else {
            $date = date('ymd',time());
            $daily = $osdcObj->dump($date);
            if ($daily){
                $daily_info = array(
                    'order_date' => $daily['order_date'],
                    'squence_no' => $daily['squence_no']+1,
                );
                $osdcObj->save($daily_info);
                $squence_no = $daily['squence_no'] + 1;
                if(strlen($squence_no) > 5) {
                    $squence_no = substr($squence_no, -5);
                }else{
                    $squence_no = str_pad($squence_no, 5, '0', STR_PAD_LEFT);
                }
                $job_no = $daily['order_date'].$squence_no.'01';
            }else {
                $squence_no = '00001';
                $daily_info = array(
                    'order_date' => $date,
                    'squence_no' => $squence_no,
                );
                $osdcObj->save($daily_info);
                $job_no = $date.$squence_no.'01';
            }
            
            
            $orderB = $filter;
            $orderB['order_date'] = $date;
            $orderB['ship_running_no'] = $squence_no;
            $orderB['increment'] = 1;
            $obObj->save($orderB);
            return $job_no;
        }
    }
    
    function destroy_running_no($shop_id, $username, $md5){
        $obObj = &app::get('ome')->model('order_batch');
        $filter = array(
            'source_shop_id' => $shop_id,
            'source_account' => $username,
            'ship_info_md5' => $md5,
        );
        return $obObj->delete($filter);
    }
}