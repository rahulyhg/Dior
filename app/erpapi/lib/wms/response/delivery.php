<?php
/**
 * WMS 发货单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_delivery extends erpapi_wms_response_abstract
{
    /**
     * wms.delivery.status_update
     *
     **/
    public function status_update($params){
        // 参数校验
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'] . '发货单' . $params['delivery_bn'];
        $this->__apilog['original_bn'] = $params['delivery_bn'];

        $batch_logi_no = preg_replace('/\s/', '', trim($params['logi_no']));
        $batch_logi_no = array_filter(explode(';', $batch_logi_no));
        $logi_no = array_pop($batch_logi_no);

        $data = array(
            'delivery_bn'  => trim($params['delivery_bn']), 
            'logi_no'      => $logi_no,
            'logi_id'      => $params['logistics'],
            'weight'       => $params['weight'],
            'branch_bn'    => $params['warehouse'],
            'volume'       => $params['volume'],
            'memo'         => $params['remark'],
            'operate_time' => $params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s'),
            'wms_id'       => $this->__channelObj->wms['channel_id'],
        );

        if ($data['logi_id']) {
            $erModel = app::get('wmsmgr')->model('express_relation');
            $r = $erModel->dump(array('wms_id'=>$this->__channelObj->wms['channel_id'],'wms_express_bn'=>$data['logi_id']), 'sys_express_bn');
            $data['logi_id'] = $r['sys_express_bn'] ? $r['sys_express_bn'] : $data['logi_id'];
        }

        if ($params['out_delivery_bn']) $data['out_delivery_bn'] = $params['out_delivery_bn'];

        switch ($params['status']) {
            case 'CLOSE':
            case 'FAILED':
                $data['status'] = 'cancel';break;
            case 'ACCEPT':
                $data['status'] = 'accept';break;
                break;
            case 'PRINT':
                $data['status'] = 'print';break;
                break;
            case 'PICK':
                $data['status'] = 'pick';break;
                break;
            case 'CHECK':
                $data['status'] = 'check';break;
                break;
            case 'PACKAGE':
                $data['status'] = 'package';break;
                break;
            case 'DELIVERY':
                $data['status'] = 'delivery';break;
                break;
            case 'UPDATE':
                $data['status'] = 'update';break;
                break;
            default:
                $data['status'] = strtolower($params['status']);
                break;
        }

        $delivery_items = array();
        $items = $params['item'] ? json_decode($params['item'],true) : array();
        if ($items) {
            foreach ($items as $key => $val) {
                if (!$val['product_bn']) continue;

                $delivery_items[] = array(
                    'bn' => $val['product_bn'],
                    'num' => $val['num'],
                );
            }
        }

        $data['items'] = $delivery_items;
        return $data;
    }
}
