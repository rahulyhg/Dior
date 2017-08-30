<?php
/**
* paipai(拍拍平台)订单处理 抽象类
*
* @category apibusiness
* @package apibusiness/response/order/paipai
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_paipai_abstract extends apibusiness_response_order_abstractbase
{

    /**
     * 解决订单备注没更新，有延迟问题(拍拍平台问题，不知道什么时候解决FUCK)
     *
     * @return void
     * @author 
     **/
    protected function operationSel()
    {
        parent::operationSel();
        $lastmodify = kernel::single('ome_func')->date2time($this->_ordersdf['lastmodify']);
        if (empty($this->_operationsel) && $lastmodify == $this->_tgOrder['outer_lastmodify']) {
            $this->_operationsel = 'update';
        }
    }

    /**
     * 是否接收订单
     *
     * @return void
     * @author 
     **/
    protected function canAccept()
    {
        $result = parent::canAccept();
        if ($result === false) {
            return false;
        }

        # 未支付的款到发货订单拒收
        if ($this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['pay_status'] == '0') {
            $this->_apiLog['info']['msg'] = '未支付订单不接收';
            return false;
        }

        return true;
    }

    /**
     * 订单转换淘管格式
     *
     * @return void
     * @author 
     **/
    public function component_convert()
    {

        parent::component_convert();

        $this->_newOrder['pmt_goods'] = abs($this->_newOrder['pmt_goods']);
        $this->_newOrder['pmt_order'] = abs($this->_newOrder['pmt_order']);

        $checkems = app::get('ome')->getConf('ome.checkems');
        if ('true' == $checkems && 'ems' == strtolower($this->_newOrder['shipping']['shipping_name'])) {
            $custom_memo = $this->_newOrder['custom_mark'] ? unserialize($this->_newOrder['custom_mark']) : array();
            $custom_memo[] = array(
                'op_name'=>$this->_shop['name'], 
                'op_time'=>date("Y-m-d H:i:s",time()), 
                'op_content'=>'系统：用户选择了 EMS 的配送方式'
            );

            $this->_newOrder['custom_mark'] = serialize($custom_memo);
        }
    }

    /**
     * 需要更新的组件
     *
     * @return void
     * @author 
     **/
    protected function get_update_components()
    {
        $components = array('markmemo','custommemo','marktype');

        return $components;
    }

    /**
     * 字段转换
     *
     * @return void
     * @author 
     **/
    protected function reTransSdf()
    {
        parent::reTransSdf();

        $mark_type = array(
            'red' => 'b1',
            'yellow' => 'b3',
            'green' => 'b7',
            'blue' => 'b4',
            'pink' => 'b6',
        );
        $buyer_flag = strtolower($this->_ordersdf['buyer_flag']);
        if ($mark_type[$buyer_flag]) {
            $this->_ordersdf['mark_type'] = $mark_type[$buyer_flag];
        }
    }
}