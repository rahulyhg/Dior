<?php

/**
 * 来源平台
 */
class omeauto_auto_type_platform extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {

    /**
     * 在显示前为模板做一些数据准备工作
     * 
     * @param object $tpl
     * @return void
     */
    public function _prepareUI(& $tpl) {

        $shop_type = kernel::database()->select("SELECT shop_type FROM sdb_ome_shop WHERE shop_type<>'' GROUP BY shop_type");

        $shopList = array();
        foreach ($shop_type as $row) {
            $shopList[] = array('key' => $row['shop_type'], 'label' => ome_shop_type::shop_name($row['shop_type']));
        }


        /*if ($data) {
            if ($data['shop_type']) {
                $tpl->pagedata['current_shop_type'] = $data['shop_type'];
                $checked = $data['shop'];
                $shop = $this->_get_shop($data['shop_type'], $checked);
                $tpl->pagedata['shop'] = $shop;
            }
        } else {
            $shop = $this->_get_shop($shop_type[0]['key'], $checked);
            $tpl->pagedata['shop'] = $shop;
        }*/

        $tpl->pagedata['shop_type'] = $shopList;
    }

    /**
     * 检查输入的参数
     * 
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params) {

        if (empty($params['shop_type'])) {

            return "你还没有选择订单的来源平台\n\n请选择以后再试！！";
        }

        /*if (empty($params['shop']) && !is_array($params['shop'])) {

            return "你还没有选择指定来源平台下店铺\n\n请勾选以后再试！！";
        }*/

        return true;
    }
    
    /**
     * 检查订单数据是否符合要求
     * 
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item) {
        
        if (!empty($this->content)) {
            
            foreach ($item->getOrders() as $order) {
                //检查订单类型
                if (strtolower($order['shop_type']) != strtolower($this->content['type'])) {
                    return false;
                }
                /*if (!empty($this->content[shop])) {
                    if (in_array($order['shop_id'], $this->content['shop'])) {
                        return true;
                    }
                }*/
            }
            return true;
        } else {
            
            return false;
        }
    }

    /**
     * 生成规则字串
     * 
     * @param Array $params
     * @return String
     */
    public function roleToString($params) {
        
        $shoptype = ome_shop_type::shop_name($params['shop_type']);
        //$rows = app::get('ome')->model('shop')->getList('name', array('shop_id' => $params['shop']));
        
        $caption = '';
        /*foreach ($rows as $row) {
            
            $caption .= ", ".$row['name'];
        }*/
        $caption = sprintf('来自 %s 平台下的订单', $shoptype);
        
        $role = array('role' => 'platform', 'caption' => $caption, 'content'=> array('type' => $params['shop_type']));
        
        return json_encode($role);
    }

    function _get_shop($shop_type, $checked=array()) {
        $shop = array();
        if ($shop_type) {
            $rows = app::get('ome')->model('shop')->getList("shop_id,name", array("shop_type" => $shop_type), 0, -1);
            if ($rows) {
                foreach ($rows as $v) {
                    $shop[] = array(
                        'shop_id' => $v['shop_id'],
                        'shop_name' => $v['name'],
                        'checked' => ($checked && in_array($v['shop_id'], $checked)) ? 'checked' : '',
                    );
                }
            }
        }
        return $shop;
    }

    function getShopByType($params) {
        $shop_type = $params[0];
        $role = $params[1];
        $tpl = kernel::single('base_render');
        $tpl->pagedata['role'] = $role;
        $tpl->pagedata['init'] = json_decode(base64_decode($role), true);
        $shop = $this->_get_shop($shop_type);
        $tpl->pagedata['current_shop_type'] = $shop_type;
        $tpl->pagedata['shop'] = $shop;

        echo $tpl->fetch("order/type/platform/shop.html", 'omeauto');
    }

}