<?php
class logisticsaccounts_ctl_admin_actualdialog extends desktop_controller{
   function findActual($sid=null){
        $filter['join'] = '0';
        $filter['status'] = '2';
        $params = array(
                        'title'=>'实际账单列表',
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'base_filter' => $filter,
                    );
        $this->finder('logisticsaccounts_mdl_actual', $params);
   }

}

?>