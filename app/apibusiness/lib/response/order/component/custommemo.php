<?php
/**
* 买家留言
*
* @category apibusiness
* @package apibusiness/response/order/component
* @author chenping<chenping@shopex.cn>
* @version $Id: custommemo.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_component_custommemo extends apibusiness_response_order_component_abstract
{
    const _APP_NAME = 'ome';

    /**
     * 订单格式转换
     *
     * @return void
     * @author 
     **/
    public function convert()
    {
        if ($this->_platform->_ordersdf['custom_mark']) {
            $custommemo[] = array(
                'op_name' => $this->_platform->_shop['name'],
                'op_time' => date("Y-m-d H:i:s"),
                'op_content' => htmlspecialchars($this->_platform->_ordersdf['custom_mark']),
            );
            $this->_platform->_newOrder['custom_mark'] = serialize($custommemo);
        }
    }

    /**
     * 更改买家留言
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        // $custom = kernel::single('ome_order_func')->update_message(
        //     $this->_platform->_tgOrder['order_id'],
        //     $this->_platform->_ordersdf['custom_mark'],
        //     $this->_platform->_shop['name'],
        //     $this->_platform->_tgOrder['custom_mark'],false);
        $old_custom_mark = array();
        if ($this->_platform->_tgOrder['custom_mark'] && is_string($this->_platform->_tgOrder['custom_mark'])) {
            $old_custom_mark = unserialize($this->_platform->_tgOrder['custom_mark']);
        }

        $last_custom_mark = array();
        foreach ((array) $old_custom_mark as $key => $value) {
            if ( strstr($value['op_time'], "-") ) $value['op_time'] = strtotime($value['op_time']);

            if ( intval($value['op_time']) > intval($last_custom_mark['op_time']) ) {
                $last_custom_mark = $value;
            }
        }

        if ($this->_platform->_ordersdf['custom_mark'] && $last_custom_mark['op_content'] != $this->_platform->_ordersdf['custom_mark']) {
            $custom = (array) $old_custom_mark;
            $custom[] = array('op_name'=>$this->_platform->_shop['name'],'op_content'=>$this->_platform->_ordersdf['custom_mark'],'op_time'=>date('Y-m-d H:i:s'));
        }

        if ($custom) {
            $this->_platform->_newOrder['custom_mark'] = serialize($custom);

            $this->_platform->_apiLog['info'][] = '订单买家留言发生变化，$sdf结构：'.var_export($custom,true);

            $logModel = app::get(self::_APP_NAME)->model('operation_log');
            $logModel->write_log('order_edit@ome',$this->_platform->_tgOrder['order_id'],'修改买家留言');
        }
    }

}