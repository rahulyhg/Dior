<?php

/**
 * 仓库分配规则
 * 
 * @version 0.1b
 * @author hzjsq
 */
class omeauto_ctl_autobranchset extends omeauto_controller {

    var $workground = "setting_tools";

    function index() {

        $params = array(
            'title' => '设置仓库分配规则',
            'actions' => array(
                array(
                    'label' => '新建',
                    'href' => 'index.php?app=omeauto&ctl=order_type&act=add&group_type=branch',
                    'target' => 'dialog::{width:700,height:480,title:\'新建分组规则\'}',
                ),
//                array(
//                    'label' => '新建',
//                    'href' => 'index.php?app=omeauto&ctl=autobranch&act=pre_add',
//                    'target' => 'dialog::{width:700,height:480,title:\'新建分组规则\'}',
//                ),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => false,
            'use_view_tab' => false,
            'base_filter' => array('group_type'=>'branch'),
            'finder_cols' => 'column_confirm,column_disabled,name,column_autoconfirm,column_autodispatch,column_autobranch,column_memo,column_order,column_content',
        );
        $this->finder('omeauto_mdl_order_type', $params);
    }

   
    /**
     * 保存仓库对应信息
     * 
     * @return void 
     */
    function save() {

        //$this->begin("index.php?app=omeauto&ctl=autoconfirm&act=index");
        $data = $_POST;
        $autobranchObj = app::get('omeauto')->model('autobranch');
        $autobranch = $autobranchObj ->getlist('bid',array('tid'=>$data['tid']));
        $old_branch = array();
        foreach ($autobranch as $branch ) {
            $old_branch[] = $branch['bid'];
        }
       $delet_branch = array_diff($old_branch,$data['bind_conf']);
       
       foreach ( $delet_branch as $dv ) {
           
           kernel::database()->exec("delete from sdb_omeauto_autobranch  where tid={$data[tid]} AND bid={$dv}");
       }
       $is_default = array_flip($data['is_default']);
       
        foreach ($data['bind_conf'] as $k=>$v ) {
            if ($is_default[$v] =='0') {
                $branch_id = $v;
                
                kernel::database()->query("update sdb_omeauto_order_type set bid=".$branch_id." where tid={$data[tid]}");
            }
            $auto_data = array(
                    'tid'=>$data['tid'],
                    'bid'=>$v,
                    'weight'=>$data['weight'][$v],
                    'is_default'=>$is_default[$v] === '0' ? '1' : '0',
                );
                
                app::get('omeauto')->model('autobranch')->save($auto_data);
        }
        
        

        echo "SUCC";
    }

   
    
    /**
     *
     * @
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function pre_add()
    {
        $this->page('autobranch/pre_add.html');
    }
    
    /**
     * 设置绑定仓库
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    
    function setBind($tid) {
        $branchObj = app::get('ome')->model('branch');
        $branchList = $branchObj->getList('*',array('disabled' => 'false','is_deliv_branch' => 'true'));
        $branch = $branchObj->dump($branch_id);
        $branch['bind_conf'] = unserialize($branch['bind_conf']);
        $autobranchObj = app::get('omeauto')->model('autobranch');
        $autobranch = $autobranchObj ->getlist('*',array('tid'=>$tid));
        $autobranch_list = array();
        foreach ($autobranch as $auto ) {
            $autobranch_list[$auto['bid']] = $auto;
        }
        $this->pagedata['autobranch_list'] = $autobranch_list;
        
        foreach ( $branchList as $k=>$v ) {
            if ($autobranch_list[$v['branch_id']]) {
                $branchList[$k]['checked'] = '1';
                $branchList[$k]['auto_weight'] = $autobranch_list[$v['branch_id']]['weight'];
            }
        }
        $this->pagedata['branch'] = $branch;
        $this->pagedata['branchList'] = $branchList;
        $order_typeObj = app::get('omeauto')->model('order_type');
        $order_type = $order_typeObj->dump($tid,'*');
        $this->pagedata['order_type'] = $order_type;
        
        
        unset($order_type);
        unset($autobranch);
        $this->page('autobranch/setBind.html');
    }
}