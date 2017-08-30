<?php
/**
 * 淘宝订单数据处理
 * @author ome 
 * @copyright 2013.4.8
 *
 */
class ome_service_c2c_taobao_order{

    // 为下列用户开放分销订单编辑按钮
    private $_editorder_host = array(
        'ziyouqishi.erp.shopexdrp.cn',
    );

    /**
     * 分销类型
     * 经销 tbjx , 代销 tbdx
     **/
    private $fenxiao_type = array('tbjx','tbdx');

    /**
     * 订单列表上编辑项是否可显示
     *
     * @return void
     * @author 
     **/
    public function is_edit_view($order_sdf,&$flag){
        
        $host = kernel::single('base_request',1)->get_host();
        if(in_array($order_sdf['order_source'], $this->fenxiao_type) && !in_array($host,$this->_editorder_host) ){
            $flag = 'false';
        }
    }

    /**
     * sdf结构预处理
     *
     * @return void
     * @author 
     **/
    public function pre_tbfx_order(&$order_sdf,$shop_info)
    {
        $trade_type = $order_sdf['t_type'];

        $msg = array('rsp'=>'success','msg'=>'');

        if($trade_type == 'fenxiao'){
            if(trim($order_sdf['member_info']['uname']) == trim($shop_info['nickname'])){
                $msg['rsp'] = 'fail';
                $msg['msg'] = '订单号已存在';
                return $msg;
            }
            $tmp_fx_order_id = $order_sdf['fx_order_id'];
            $order_sdf['fx_order_id'] = $order_sdf['order_bn'];
            $order_sdf['order_bn'] = $tmp_fx_order_id;            
        }

        return $msg;
    }

    /**
     * 保存淘宝分销信息(用于rpc订单添加时)
     *
     * @return void
     * @author 
     **/
    public function save_tbfx_order($order_sdf)
    {
        if('taobao' != $order_sdf['shop_type'] && !in_array($order_sdf['order_source'], $this->fenxiao_type) ) return false;

        $tborder = app::get('ome')->model('tbfx_orders');
        $tbobj = app::get('ome')->model('tbfx_order_objects');
        $tbitem = app::get('ome')->model('tbfx_order_items');

        $tbfx_sdf = $tbfx_obj = $tbfx_item = array();

        $tbfx_sdf['order_id'] = $order_sdf['order_id'];
        $tbfx_sdf['fenxiao_order_id'] = $order_sdf['fx_order_id'];
        $tbfx_sdf['tc_order_id'] = $order_sdf['tc_order_id'];

        foreach($order_sdf['order_objects'] as $key=>$object){
            $tbfx_obj[] = array(
                'order_id' => $order_sdf['order_id'],
                'obj_id' => $object['obj_id'],
                'fx_oid' => $object['fx_oid'],
                'tc_order_id' => $object['tc_order_id'],
                'cost_tax' => $object['cost_tax'],
                'buyer_payment' => $object['buyer_payment'],
            );
            foreach($object['order_items'] as $k => $item){
                $tbfx_item[] = array(
                    'order_id' => $order_sdf['order_id'],
                    'item_id' => $item['item_id'],
                    'obj_id' => $item['obj_id'],
                    'buyer_payment' => $item['buyer_payment'],
                    'cost_tax' => $item['cost_tax'],
                );
            }
        }

        if($tborder->insert($tbfx_sdf)){
            foreach ($tbfx_obj as $objs) {
                $tbobj->insert($objs);
            }
            foreach ($tbfx_item as $items) {
                $tbitem->insert($items);
            }            
        }
    }


    /**
     * 保存淘宝分销信息(用于rpc订单更新时)
     *
     * @return void
     * @author 
     **/
    public function update_tbfx_order($order_sdf,$must_add_tbfxsdf = array(),$must_update_tbfxsdf = array())
    {
        if('taobao' != $order_sdf['shop_type'] && !in_array($order_sdf['order_source'], $this->fenxiao_type) ) return false;

        $tbobj = app::get('ome')->model('tbfx_order_objects');
        $tbitem = app::get('ome')->model('tbfx_order_items');

        if($must_update_tbfxsdf){
            foreach((array)$must_update_tbfxsdf['order_objects'] as $updateobj){
                $tbobj->update($updateobj,array('order_id'=>$updateobj['order_id'],'obj_id'=>$updateobj['obj_id']));
            }
            foreach((array)$must_update_tbfxsdf['order_items'] as $updateitem){
                $tbitem->update($updateitem,array('item_id'=>$updateitem['item_id'],'obj_id'=>$updateitem['obj_id']));
            }
        }

        if($must_add_tbfxsdf){
            foreach((array)$must_add_tbfxsdf['order_objects'] as $addobj){
                $tbobj->save($addobj);
            }
            foreach((array)$must_add_tbfxsdf['order_items'] as $additem){
                $tbitem->insert($additem);
            }
        }

    }

    /**
     * 扩展sdf字段
     *
     * @return void
     * @author 
     **/
    public function order_sdf_extend(&$order_sdf){

        $Torderitem = app::get('ome')->model('tbfx_order_items');

        if ($order_sdf){
            foreach ($order_sdf as $obj_type=>$objects){
                if (is_array($objects)){
                    foreach ($objects as $obj_id=>$items){
                        if ($items['order_items']){
                            foreach ($items['order_items'] as $item_id=>$item_val){
                                 $tbfx_data = $Torderitem->getList('buyer_payment',array('item_id'=>$item_val['item_id']));
                                $order_sdf[$obj_type][$obj_id]['order_items'][$item_id]['buyer_payment'] = $tbfx_data[0]['buyer_payment'];
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 处理捆绑商品order_item上的价格分摊
     *
     * @return void
     * @author 
     **/
    function apportion_pkg_price(&$order_sdf)
    {
        if('taobao' != $order_sdf['shop_type'] && !in_array($order_sdf['order_source'], $this->fenxiao_type) ) return false;

        $total_nums = array();
        $tmp_cost_tax = $tmp_buyer_payment = 0;
        foreach ($order_sdf['order_objects'] as $key => $objs) {
            foreach($objs['order_items'] as $k=>$item){
                $total_nums[$key] += $item['quantity'];
            }
        }

        foreach($order_sdf['order_objects'] as $key=>$objs){
            $item_count = count($objs['order_items']);
            foreach($objs['order_items'] as $k=>$item){
                if($item_count > 1){
                    if($item_count == $k){
                        $order_sdf['order_objects'][$key]['order_items'][$k]['cost_tax'] = round(($objs['cost_tax']-$tmp_cost_tax),3);
                        $order_sdf['order_objects'][$key]['order_items'][$k]['buyer_payment'] = round(($objs['buyer_payment']-$tmp_buyer_payment),3);
                    }else{
                        $order_sdf['order_objects'][$key]['order_items'][$k]['cost_tax'] = round((($item['quantity']/$total_nums[$key])*$objs['cost_tax']),3);
                        $order_sdf['order_objects'][$key]['order_items'][$k]['buyer_payment'] = round((($item['quantity']/$total_nums[$key])*$objs['buyer_payment']),3);
                        $tmp_cost_tax += $order_sdf['order_objects'][$key]['order_items'][$k]['cost_tax'];
                        $tmp_buyer_payment += $order_sdf['order_objects'][$key]['order_items'][$k]['buyer_payment'];
                    }
                }
            }
        }

    }
}