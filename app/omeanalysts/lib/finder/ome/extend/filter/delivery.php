<?php
class omeanalysts_finder_ome_extend_filter_delivery{
    function get_extend_colums(){
        $dlyCorpObj = &app::get('ome')->model('dly_corp');
        $dlyCorps = $dlyCorpObj->getList('corp_id,branch_id,all_branch,name');
        $logi_ids = array();
        foreach($dlyCorps as $dlyCorp){
            if($dlyCorp['all_branch']=='true' || $dlyCorp['branch_id']==$_POST['type_id']){
                $logi_ids[$dlyCorp['corp_id']] = $dlyCorp['name'];
            }
        }

        $db['ome_delivery']=array (
            'columns' => array (
                'order_bn' => array (
                    'type' => 'varchar(30)',
                    'label' => '订单号',
                    'width' => 130,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
            )
        );
        return $db;
    }
}