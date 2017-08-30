<?php
class erpapi_mdl_api_fail extends dbeav_model{

    public function modifier_obj_type($col)
    {
        switch ($col) {
            case 'purchase':
                $col = '采购单';
                break;
            case 'delivery':
                $col = '发货单';
                break;
            case 'allcoate':
                $col = '调拨出入库单';
                break;
            case 'defective':
                $col = '残损入库单';
                break;
            case 'adjustment':
                $col = '调帐入库单';
                break;
            case 'exchange':
                $col = '换货入库单';
                break;
            case 'other':
                $col = '其他入库单';
                break;
            case 'purchase_return':
                $col = '采购退货单';
                break;
            case 'defective':
                $col = '残损出库单';
                break;
            case 'adjustment':
                $col = '调帐出库单';
                break;
            case 'reship':
                $col = '退货单';
                break;
        }

        return $col;
    }

    /**
     * 发布失败日志
     *
     * @return void
     * @author 
     **/
    public function publish_api_fail($method, $callback_params, $result)
    {
        $obj_bn   = $callback_params['obj_bn'];
        $obj_type = $callback_params['obj_type'];
        $err_msg  = $result['err_msg'];
        $res      = $result['res'];
        $status   = $result['rsp'];

        if (!$obj_bn || !$obj_type || !$method) return true;

        $fail = $this->dump(array('obj_bn'=>$obj_bn,'obj_type'=>$obj_type));
        if ($status == 'fail') {
            $fail_params = array(
                'obj_bn'     => $obj_bn,
                'obj_type'   => $obj_type,
                'method'     => $method,
                'err_msg'    => $err_msg.'('.$res.')',
                'fail_times' => ((int)$fail['fail_times'] + 1),
                'status'     => 'fail',
            );
            if ($fail) $fail_params['id'] = $fail['id'];

            $this->save($fail_params);

            // 判断是不是超时
            if (in_array($res,array('e00090','ERP00090')) && $fail_params['fail_times']<3) {

                $retrytime = app::get('ome')->getConf('ome.apifail.retry');
                $retrytime = $retrytime ? $retrytime * 60 : 600;
                $push_params = array(
                    'data' => array(
                        'log_id'     => $fail_params['id'],
                        'task_type'  => 'autoretryapi',
                        'exectime'   => (time() + $retrytime),
                        'obj_bn'     => $obj_bn,
                        'obj_type'   => $obj_type,
                        'method'     => $method,
                        'id'         => $fail_params['id'],
                    ),
                    'url' => kernel::openapi_url('openapi.autotask','service')
                );

                $flag = kernel::single('taskmgr_interface_connecter')->push($push_params);

                // if ($flag) {
                //     $this->update(array('status'=>'running'),array('id'=>$fail_params['id']));
                // }
            }
        } elseif ($status=='succ' && $fail) {
            $this->delete(array('id'=>$fail['id']));
        }

        return true;
    }
}
