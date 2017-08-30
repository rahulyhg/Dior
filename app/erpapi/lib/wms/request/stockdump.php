<?php
/**
 * 转储单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_request_stockdump extends erpapi_wms_request_abstract
{

    /**
     * 转储单创建
     *
     * @return void
     * @author 
     **/
    public function stockdump_create($sdf){
        $stockdump_bn = $sdf['stockdump_bn'];

        $iscancel = kernel::single('console_service_commonstock')->iscancel($stockdump_bn);
        if ($iscancel) {
            return $this->succ('转储单已取消,终止同步');
        }

        $title = $this->__channelObj->wms['channel_name'] . '转储单添加';

        // 分页请求
        $items = $sdf['items']; sort($items);
        $total = count($items); $page_no = 1; $page_size =  150;
         $total_page = ceil($total/$page_size);
        do {

            $offset = ($page_no - 1) * $page_size;

            $sdf['items'] = array_slice($items, $offset, $page_size, true);

            $params = $this->_format_stockdump_create_params($sdf);

            $params['is_finished']      = ($page_no >= $total_page) ? 'true' : 'false';
            $params['current_page']     = $page_no;
            $params['total_page']       = $total_page;
            $params['line_total_count'] = $total;


            $callback = array(
                'class'  => get_class($this),
                'method' => 'stockdump_create_callback',
            );
            $this->__caller->call(WMS_TRANSFERORDER_CREATE, $params, $callback, $title, 10, $stockdump_bn);

            if ($params['is_finished'] == 'true') break;

            $page_no++;
        } while (true);
    } 

    public function stockdump_create_callback($response, $callback_params)
    {
        return $this->callback($response,$callback_params);
    }

    protected function _format_stockdump_create_params($sdf)
    {

        $items = array('item'=>array());
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k => $v){
                $items['item'][] = array(
                    'item_code'     => $v['bn'],
                    'item_name'     => $v['name'],
                    'item_quantity' => $v['num'],
                    'item_price'    => $v['price'] ? $v['price'] : '0',// TODO: 商品价格
                    'item_line_num' => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'item_remark'   => '',// TODO: 商品备注
                );
            }
        }
        
        $create_time = preg_match('/-|\//',$sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s",$sdf['create_time']);
        $params = array(
            'uniqid'           => self::uniqid(),
            'original_id'      => '',
            'out_order_code'   => $sdf['stockdump_bn'],
            'created'          => $create_time,
            // 'is_finished'      => $cur_page >= $total_page ? 'true' : 'false',
            // 'current_page'     => $cur_page,// 当前批次,用于分批同步
            // 'total_page'       => $total_page,// 总批次,用于分批同步
            'remark'           => $sdf['memo'],
            'src_storage'      => $sdf['src_storage'],//来源存放点编号
            'dest_storage'     => $sdf['dest_storage'],//目的存放点编号 
            'line_total_count' => $sdf['line_total_count'],// TODO: 订单行项目数量
            'items'            => json_encode($items),
        );

        return $params;   
    }


    /**
     * 转储单取消
     *
     * @return void
     * @author 
     **/
    public function stockdump_cancel($sdf){
        $stockdump_bn = $sdf['stockdump_bn'];

        $title = $this->__channelObj->wms['channel_name'] . '转储单取消'.$stockdump_bn;

        $params = $this->_format_stockdump_cancel_params($sdf);

        return $this->__caller->call(WMS_TRANSFERORDER_CANCEL, $params, null, $title, 10, $stockdump_bn);
    } 

    protected function _format_stockdump_cancel_params($sdf)
    {
        $params = array(
            'out_order_code' => $sdf['stockdump_bn'],
        );
        return $params;
    }
}