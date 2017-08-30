<?php
/**
* 订单备注
*
* @category apibusiness
* @package apibusiness/response/order/component
* @author chenping<chenping@shopex.cn>
* @version $Id: markmemo.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_component_markmemo extends apibusiness_response_order_component_abstract
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
        if ($this->_platform->_ordersdf['mark_text']) {
            $markmemo[] = array(
                'op_name' => $this->_platform->_ordersdf['shop']['name'],
                'op_time' => date("Y-m-d H:i:s",time()),
                'op_content' => htmlspecialchars($this->_platform->_ordersdf['mark_text']),
            );
            $this->_platform->_newOrder['mark_text'] = serialize($markmemo);
        }
    }

    /**
     * 更新订单备注
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        // $mark = kernel::single('ome_order_func')->update_mark(
        //     $this->_platform->_tgOrder['order_id'],
        //     $this->_platform->_ordersdf['mark_text'],
        //     $this->_platform->_shop['name'],$this->_platform->_tgOrder['mark_text'],false);

        $old_mark_text = array();
        if ($this->_platform->_tgOrder['mark_text'] && is_string($this->_platform->_tgOrder['mark_text'])) {
            $old_mark_text = unserialize($this->_platform->_tgOrder['mark_text']);
        }

        $last_mark_text = array();
        foreach ((array) $old_mark_text as $key => $value) {
            if ( strstr($value['op_time'], "-") ) $value['op_time'] = strtotime($value['op_time']);

            if ( intval($value['op_time']) > intval($last_mark_text['op_time']) ) {
                $last_mark_text = $value;
            }
        }

        if ($this->_platform->_ordersdf['mark_text'] && $last_mark_text['op_content'] != $this->_platform->_ordersdf['mark_text']) {
            $mark = (array) $old_mark_text;
            $mark[] = array('op_name'=>$this->_platform->_shop['name'],'op_content'=>$this->_platform->_ordersdf['mark_text'],'op_time'=>date('Y-m-d H:i:s'));
        }

        $logModel = app::get(self::_APP_NAME)->model('operation_log');
        if($mark){

            $this->_platform->_newOrder['mark_text'] = serialize($mark);

            $this->_platform->_apiLog['info'][] = '订单备注发生变化，$sdf结构：'.var_export($mark,true);

            $logModel->write_log('order_edit@ome',$this->_platform->_tgOrder['order_id'],'修改订单备注');
        }
    }
}