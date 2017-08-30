<?php
/**
 * 短信模板类
 *
 * @package taoexlib
 * @author   zhangxuehui
 **/
class taoexlib_ctl_admin_sms_items extends desktop_controller {
    var $workground = 'rolescfg';

    function __construct(&$app)
    {
        parent::__construct($app);
        
        $this->itemsMdl = $this->app->model('sms_sample_items');
    }
   /**
     * 模板列表.
     * @param  
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function list_sample($id)
    {
        $base_filter = array('id'=>$id);
        $params = array(
            'title'=>'短信模板修改记录',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>false,
            'base_filter' => $base_filter,
            
        );
       
        $this->finder('taoexlib_mdl_sms_sample_items', $params);
    }
    

    public function setStatus($iid,$id,$status)
    {   
        
        if($status =='1'){
            $now_status = '0';
            $items = $this->itemsMdl->getList('*',array('id'=>$id,'status'=>'1'));
            
            if ( count($items)<=1 ) {
                 echo "<script>parent.MessageBox.error('请确认至少有一个模板开启中,以保证短信可以发送！！');</script>";
                 exit;
            }
        }else{
            $now_status = '1';
            
        }
        
        $data = array('iid'=>$iid,"status"=>$now_status);
        $this->itemsMdl->save($data);
        echo "<script>parent.MessageBox.success('命令已经被成功发送！！');parent.finderGroup['{$_GET[finder_id]}'].refresh();</script>";
        exit;
    }
   
}