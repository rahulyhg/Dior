<?php

/**
 * 操作日志dbschema扩展
 *
 */
class ome_finder_extend_filter_operation_log {
    function get_extend_colums(){
        $usersModel = app::get('desktop')->model('users');
        $result = $usersModel->getList('user_id,name');
        $userList = array();
        foreach ($result as $v) {
            $userList[$v['user_id']] = $v['name'];
        }
        $operationType = ome_operation_log::getType();
        $db['operation_log'] = array(
            'columns' => array(
                'user' => array(
                    'type' => $userList,
                    'editable' => false,
                    'label' => '操作者',
                    'width' => 110,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'panel_id' => 'ome_operation_log_finder_top',
                ),
                'operation_type' => array(
                    'type' => $operationType,
                    'editable' => false,
                    'label' => '操作类型',
                    'width' => 110,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'panel_id' => 'ome_operation_log_finder_top',
                ),
                'st_time' => array(
                    'type' => 'date',
                    'label' => '起始日期',
                    'required' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filterdefault' => true,
                    'filtertype' => 'time',
                    'width' => 140,
                    'panel_id' => 'ome_operation_log_finder_top',
                ),
                'et_time' => array(
                    'type' => 'date',
                    'label' => '结束日期',
                    'required' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filterdefault' => true,
                    'filtertype' => 'time',
                    'width' => 140,
                    'panel_id' => 'ome_operation_log_finder_top',
                ),
            )
        );
        return $db;
     }
}