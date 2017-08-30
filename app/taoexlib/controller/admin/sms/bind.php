<?php
/**
 * 短信模板类
 *
 * @package taoexlib
 * @author   zhangxuehui
 **/
class taoexlib_ctl_admin_sms_bind extends desktop_controller {
    /**
     * 列表所在组
     *
     * @var string
     **/
    var $workground = 'rolescfg';

    function __construct(&$app)
    {
        parent::__construct($app);
        $this->_request = kernel::single('base_component_request');
        $this->bindMdl = $this->app->model('sms_bind');
    }
    /**
     * 短信模板列表
     *
     * @param  void
     * @return html
     * @author 
     **/

    public function index()
    {
        $params = array(
            'title'=>'发送规则列表',
            'use_buildin_recycle'=>false,
            'actions'=>array(
                array(
                    'label' => '新建',
                    'href' => 'index.php?app=taoexlib&ctl=admin_sms_bind&act=add_bind', 
                    'target' => 'dialog::{width:600,height:500,title:\'新建发送规则\'}'
                ),
                array(
                    'label' => '删除',
                    'submit' => 'index.php?app=taoexlib&ctl=admin_sms_bind&act=del_bind', 
                    'confirm' =>"确定删除选中发送规则？"
                ),
            ),
            'base_filter' => array('disabled'=>'false'),
        );
        $this->finder('taoexlib_mdl_sms_bind', $params);
    } 
    /**
     * 显示菜单
     *
     * @param  void
     * @return html
     * @author 
     **/
    public function _views()
    {
        $sub_menu = $this->_allVeiw();
        return $sub_menu;
    }
    /**
     * 显示所有未删除的模板
     *
     * @param  void
     * @return array
     * @author 
     **/
    public function _allVeiw()
    {
        $sms_bind = $this->app->model('sms_bind');
        $base_filter = array('disabled'=>'false');
        $sub_menu = array(
                0 => array('label'=>app::get('taoexlib')->_('全部'),'filter'=>$base_filter,'optional'=>false),

            );
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $sms_bind->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=taoexlib&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$k++;
        }
        return $sub_menu;
    }
    /**
     * 显示添加模板页面
     *
     * @param  void
     * @return html
     * @author 
     **/
    public function add_bind()
    {   
        $order_typeMdl = app::get('omeauto')->model('order_type');
        //获取所有已经绑定的规则ID
        $tids = $this->bindMdl->getAllBindId();

        //获取规则的过滤条件
        $rule_filter = array('group_type'=>'sms','disabled'=>'false','tid|notin'=>$tids);
        
        $this->pagedata['bindInfo']['is_send'] = 'true';
        $this->pagedata['count'] = $order_typeMdl->count($rule_filter);
        $this->pagedata['is_add'] = 'is_add';
        $this->pagedata['rule_filter'] = $rule_filter;
        $this->pagedata['sample_filter'] = array('status'=>true,'disabled'=>'false');
        $this->page("admin/sms/bind.html");
    }

    /**
     * 显示编辑模板页面
     *
     * @param  void
     * @return html
     * @author 
     **/
    public function edit_bind()
    {   
        $ids = $this->_request->get_get('p');
        $bind_id = $ids[0];

        $bindInfo = $this->bindMdl->select()->columns()->where('bind_id=?',$bind_id)->instance()->fetch_row();
        $bindInfo['is_send'] = ($bindInfo['is_send']=='0')?'false':'true';

        $tids = $this->bindMdl->getAllBindId($bindInfo['tid']);
        $this->pagedata['rule_filter'] = array('group_type'=>'sms','disabled'=>'false','tid|notin'=>$tids);
        $this->pagedata['sample_filter'] = array('status'=>true,'disabled'=>'false');
        $this->pagedata['bindInfo']        = $bindInfo;
        $this->page("admin/sms/bind.html");
    }


    /**
     * 保存模板信息(包括编辑和添加)
     *
     * @param  void
     * @return html
     * @author 
     **/
    public function save_bind()
    {       
        $this->begin();
        $param =$this->_request->get_post();
        $sample_id = $param['sample_id'];
        $is_send   = $param['is_send'];
        if (!$sample_id) {
            $this->end(false,'请选择模板');
        }
        $bindInfo = array();
        $bind_id = $param['bind_id'];
        if ($bind_id) {
            $data['bind_id'] = $bind_id;
            $bindMdl = $this->app->model('sms_bind');
            $bindInfo = $bindMdl->select()->columns()->where('bind_id=?',$bind_id)->instance()->fetch_row();
        }
        $data['id'] = $sample_id;
        if ($bindInfo['is_default']!='1') {
            $tid = $param['tid'];
            if (!$tid) {
                $this->end(false,'请选择分组规则');
            }
            $data['tid'] = $tid;
        }
        $data['is_send'] = ($is_send=='true')?1:0;
        $res =  app::get('taoexlib')->model('sms_bind')->save($data);
        $this->end(true,'添加成功');
    }

    /**
     * 逻辑删除模板
     *
     * @param  string
     * @return json
     * @author 
     **/
    public function del_bind()
    {
        $this->begin("index.php?app=taoexlib&ctl=admin_sms_bind&act=index");
        $ids = $this->_request->get_post('bind_id');
        $bindInfo = $this->bindMdl->getList('is_default,bind_id',array('bind_id|in'=>$ids));
        foreach ($bindInfo as $key => $info) {
            if($info['is_default']=='1'){
                $this->end(false,app::get('taoexlib')->_("默认发送规则无法删除!"));
            }
            $this->bindMdl->delete(array('bind_id'=>$info['bind_id']));
        }
        $this->end(true,app::get('taoexlib')->_('删除成功'));
    }
    /**
     * 设置模板启用状态
     *
     * @param  void
     * @return void
     * @author 
     **/
    public function setDefault($bind_id)
    {   
        if (!$bind_id) {
            echo "<script>parent.MessageBox.error('$band_id');</script>";
            exit;
        }
        $bindMdl = app::get('taoexlib')->model('sms_bind');
        $data['is_default'] = '0';
        $bindMdl->update($data);
        $data['is_default'] = 1;
        $data['bind_id'] = $bind_id;
        $bindMdl->save($data);
        echo "<script>parent.MessageBox.success('命令已经被成功发送！！');parent.finderGroup['{$_GET[finder_id]}'].refresh();</script>";
        exit;
    }

    public function setStatus($id,$status)
    {   
        if($status =='1'){
            $now_status = '0';
        }else{
            $bindInfo = $this->bindMdl->getList('tid,id',array('bind_id'=>$id));
            if(!$this->ruleStatus($bindInfo[0]['tid'])){
                echo "<script>parent.MessageBox.error('发送规则对应的分组规则已经关闭，无法开启');</script>";
                exit;
            }

            if(!$this->sampleStatus($bindInfo[0]['id'])){
                echo "<script>parent.MessageBox.error('发送规则对应的模板已经关闭，无法开启');</script>";
                exit;
            }
            $now_status = '1';
        }
        $data = array('bind_id'=>$id,"status"=>$now_status);
        $this->bindMdl->save($data);
        echo "<script>parent.MessageBox.success('命令已经被成功发送！！');parent.finderGroup['{$_GET[finder_id]}'].refresh();</script>";
        exit;
    }
    /**
     * 获取规则状态
     *
     * @param $rule_id
     * @return bool
     * @author 
     **/
    public function ruleStatus($rule_id)
    {

        $ruleInfo = app::get('omeauto')->model('order_type')->getList('disabled',array('tid'=>$rule_id));
        if($ruleInfo[0]['disabled']=='true'){
            return false;
        }else{
            return true;
        }
    }
    /**
     * 获取模板状态
     *
     * @param  $sample_id
     * @return bool
     * @author 
     **/
    public function sampleStatus($sample_id)
    {
        $sampleInfo = $this->app->model('sms_sample')->getList('status',array('id'=>$sample_id));;
        if($sampleInfo[0]['status']=='0'){
            return false;
        }else{
            return true;
        }


    }
}