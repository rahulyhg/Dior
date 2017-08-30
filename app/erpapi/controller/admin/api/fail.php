<?php
/**
 * 失败请求
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_ctl_admin_api_fail extends desktop_controller
{
    // public $workgroud = 'desktop_ctl_system';
    /**
     * 失败列表
     *
     * @return void
     * @author 
     **/
    function retry(){
        $params = array(
            'title'                  =>'失败请求',
            'actions'                => array(
                array('label'=>'批量重试','submit'=>'index.php?app=erpapi&ctl=admin_api_fail&act=retry_view','target'=>"dialog::{width:690,height:200,title:'批量重试'}"),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    =>false,
            'use_buildin_recycle'    =>false,
            'use_buildin_export'     =>false,
            'use_buildin_import'     =>false,
            'use_buildin_filter'     =>false,
            'orderBy'                => 'id desc',
        );
        $this->finder('erpapi_mdl_api_fail',$params);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function retry_view()
    {
        $filter = $_POST;
        // $filter['status'] = 'fail';

        $total = app::get('erpapi')->model('api_fail')->count($filter);
        $filter['total'] = $total;

        $this->pagedata['filter'] = http_build_query($filter);
        $this->display('admin/api/retry_view.html');
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function retry_do($page_no)
    {
        parse_str($_POST['filter'],$filter);

        $total = $filter['total']; unset($filter['total']);
        $page_size = 20;

        if ($total <= 0) {
            $rs = array('status'=>'finish');
            echo json_encode($rs);exit;
        }

        $apiModel = app::get('erpapi')->model('api_fail');

        $apilist = $apiModel->getList('*',$filter,0,$page_size);
        if (!$apilist){
            $rs = array('status'=>'finish');
            echo json_encode($rs);exit;
        }

        $data = array();
        // 发起请求
        foreach ($apilist as $key => $value) {
            // $apiModel->update(array('status'=>'fail'),array('id'=>$value['id']));

            $rs = kernel::single('erpapi_autotask_retryapi')->process($value,$err_msg);

            if ($rs !== false) {
                $data[] = $value['obj_bn'];
            }
        }

        $status = 'running';
        $finish_total = $page_no*$page_size;
        if ($finish_total >= $total) $status = 'finish';
        $rate = $finish_total >= $total ? '100' : $finish_total/$total*100;

        $rs = array('status'=>$status,'data'=>$data,'rate'=>intval($rate));
        echo json_encode($rs);exit;
    }
}