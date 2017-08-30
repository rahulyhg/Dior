<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_event_response{

    private $_response = '';

    function __construct(){
        // $this->_response = kernel::single('middleware_message');
    }

    public function send_succ($msg=''){
        // return $this->_response->output('succ',$msg);
        $rs = array(
            'rsp'      => 'succ',
            'msg'      => $msg,
            'msg_code' => null,
            'data'     => null,
        );
        return $rs;
    }

    public function send_error($msg, $msg_code=null, $data=null){
        // return $this->_response->output($rsp='fail', $msg, $msg_code, $data);

        $rs = array(
            'rsp'      => 'fail',
            'msg'      => $msg,
            'msg_code' => $msg_code,
            'data'     => $data,
        );
        return $rs;
    }
}
