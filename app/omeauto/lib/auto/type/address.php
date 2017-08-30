<?php

/**
 * 收货地区
 */
class omeauto_auto_type_address extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {

    /**
     * 在显示前为模板做一些数据准备工作
     *
     * @param object $tpl
     * @return void
     */
    public function _prepareUI(& $tpl) {

        $regionLists = kernel::single('eccommon_regions')->getList('region_id,local_name,p_region_id,region_grade,region_path');
        $tpl->pagedata['regions'] = $this->_getRegions($regionLists);
    }

    /**
     * 检查输入的参数
     *
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params) {

        if (empty($params['address']) && !is_array($params['address'])) {

            return "你还没有选择收货地址所匹配的区域\n\n请勾选以后再试！！";
        }

        return true;
    }

    /**
     * 生成规则字串
     *
     * @param Array $params
     * @return String
     */
    public function roleToString($params) {

        $rows = kernel::single('eccommon_regions')->getList('local_name', array('region_id' => $params['address']));

        $caption = '';
        foreach ($rows as $row) {

            $caption .= ", " . $row['local_name'];
        }
        $caption = sprintf('收货区域在 %s ', preg_replace('/^,/is', '', $caption));

        $role = array('role' => 'address', 'caption' => $caption, 'content' => $params['address']);

        return json_encode($role);
    }

    /**
     * 设置已经创建好的配置内容
     *
     * @param array $params
     * @return void
     */
    public function setRole($params) {
        $this->content = array();
        //转换为字符名称
        if (!empty($params) && is_array($params)) {
            $rows = kernel::single('eccommon_regions')->getListByIds($params);
            foreach ($rows as $row) {
                $this->content[] = $row['local_name'];
            }
        }
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
                $area = $order['ship_area'];
                kernel::single('eccommon_regions')->split_area($area);
                if (!in_array($area[0], $this->content)) {
                    return false;
                }
            }
            return true;
        } else {

            return false;
        }
    }

    /**
     * 获取地区列表
     *
     * @param Array $regionLists
     * @param Integer $parent
     * @return Array
     */
    private function _getRegions($regionLists, $parent= 0) {

        $ret = array();

        foreach ($regionLists as $row) {

            $path = ereg_replace('^,|,$', '', $row['region_path']);
            $depth = substr_count($path, ',');
            if ($depth == 0) {

                $ret[$path]['caption'] = $row['local_name'];
            } elseif ($depth == 1) {

                $path = split(',', $path);
                $ret[$path[0]]['items'][$path[1]]['caption'] = $row['local_name'];
            } else {

                $path = split(',', $path);
                $ret[$path[0]]['items'][$path[1]]['items'][$path[2]] = $row['local_name'];
            }
        }

        return $ret;
    }

}