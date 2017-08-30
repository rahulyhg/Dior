<?php
class ome_ctl_admin_api_orders extends desktop_controller{
    var $workground = 'admin_api_orders';

    function index($status = 'all'){
        $base_filter = '';
		//$orderby = ' log_id ';

        switch($status){
            case 'running':
                $this->title = '同步中';
                $base_filter = array('status'=>'running');
                break;
            case 'success':
                $this->title = '同步成功';
                $base_filter = array('status'=>'success');
                break;
            case 'fail':
                $this->title = '同步失败';
                $base_filter = array('status'=>'fail');
                break;
			default:
                $this->title = '前端订单列表同步管理';
                break;
        }

        $params = array(
            'title'=>$this->title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            //'orderBy' => $orderby,
        );

        if($base_filter){
            $params['base_filter'] = $base_filter;
        }

        $this->finder('ome_mdl_api_order_log',$params);
    }


    function _views(){
		$oApiStock = $this->app->model('api_order_log');
        $base_filter = array('disabled'=>'false');
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('前端订单列表同步管理'),'filter'=>$base_filter,'optional'=>false),
            2 => array('label'=>app::get('base')->_('同步中'),'filter'=>array('status' => 'running'),'optional'=>false),
            3 => array('label'=>app::get('base')->_('同步成功'),'filter'=>array('status' => 'success'),'optional'=>false),
            4 => array('label'=>app::get('base')->_('同步失败'),'filter'=>array('status' => 'fail'),'optional'=>false),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $oApiStock->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&p[0]='.$v['filter']['status'].'&view='.$i++;
        }
        return $sub_menu;
	}

}