<?php
class ome_finder_reship_refuse{
    var $detail_basic = "退货单详情";
    var $addon_cols = 'need_sv';

    function detail_basic($reship_id){
        $oDesktop = &app::get('desktop')->model('users');
        $render = app::get('ome')->render();
        $oReship = &app::get('ome')->model('reship');
        $detail = $oReship->getCheckinfo($reship_id);
        $desktop_detail = $oDesktop->dump(array('user_id'=>$detail['op_id']), 'name');
        $detail['op_name'] = $desktop_detail['name'];
        $cols = $oReship->_columns();

        $detail['is_check'] = $cols['is_check']['type'][$detail['is_check']];
        //$Oreason = $oReship->dump(array('reship_id'=>$reship_id),'reason');
        $reason = unserialize($detail['reason']);

        $detail['check_memo'] = $reason['check'];

        $render->pagedata['detail'] = $detail;
        $branch_product_posObj = app::get('ome')->model('branch_product_pos');
        $reship_item = $oReship->getItemList($reship_id);
        foreach ($reship_item as $key => $value) {
            $pos_string ='';
            $posLists = $branch_product_posObj->get_pos($value['product_id'], $value['branch_id']);
            if(count($posLists) > 0){
                foreach($posLists as $pos){
                    $pos_string .= $pos['store_position'].",";
                }
                $reship_item[$key]['store_position'] = substr($pos_string,0,strlen($pos_string)-1);
            }
            $recover['return'][] = $reship_item[$key];
        }
        $render->pagedata['items'] = $recover;
        return $render->fetch('admin/reship/refuse.html');
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function row_style($row)
    {
        $s = kernel::single('ome_reship')->is_precheck_reship($row['is_check'],$row[$this->col_prefix.'need_sv']);
        return $s ? 'highlight-row' : '';
    }

}

?>