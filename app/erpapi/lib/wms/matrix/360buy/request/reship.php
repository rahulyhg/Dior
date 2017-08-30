<?php
/**
 * 退货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_360buy_request_reship extends erpapi_wms_request_reship
{
   /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    protected function _format_reship_create_params($sdf)
    {
        $params = parent::_format_reship_create_params($sdf);

        $branch_relationObj = app::get('wmsmgr')->model('branch_relation');
        $branch_relation    = $branch_relationObj->dump(array('sys_branch_bn'=>$sdf['branch_bn']));
        $params['warehouse_code']    = $branch_relation['wms_branch_bn'];
        $params['expect_start_time'] = date('Y-m-d H:i:s');
        $params['approver']          = 'admin';

        $items = array('item'=>array());
        if ($sdf['items']){
            $oForeign_sku = app::get('console')->model('foreign_sku');

            sort($sdf['items']);
            foreach ((array)$sdf['items'] as $k => $v){
                $item_code = $oForeign_sku->get_product_outer_sku( $this->__channelObj->wms['channel_id'],$v['bn'] );
                // 获取外部商品sku
                $items['item'][] = array(
                    'item_code'      => $item_code,
                    'item_name'      => $v['name'],
                    'item_quantity'  => $v['num'],
                    'item_price'     => $v['price'] ? $v['price'] : '0',// TODO: 商品价格
                    'item_line_num'  => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'     => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'        => '',// 商品ID
                    'is_gift'        => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark'    => '',// TODO: 商品备注
                    'inventory_type' => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                );
            }
        }

        $params['items'] = json_encode($items);
        
        return $params;
    }
}