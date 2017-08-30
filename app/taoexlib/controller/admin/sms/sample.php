<?php
/**
 * 短信模板类
 *
 * @package taoexlib
 * @author   zhangxuehui
 **/
class taoexlib_ctl_admin_sms_sample extends desktop_controller {
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
        $this->sampleMdl = $this->app->model('sms_sample');
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
            'title'=>'短信模板',
            'use_buildin_recycle'=>false,
            'actions'=>array(
                array(
                    'label' => '添加模板',
                    'href' => 'index.php?app=taoexlib&ctl=admin_sms_sample&act=add_sample',
                    'target' => 'dialog::{width:600,height:600,title:\'添加模板\'}'
                ),
                array(
                    'label' => '删除',
                    'submit' => 'index.php?app=taoexlib&ctl=admin_sms_sample&act=del_sample',
                    'confirm' =>"确定删除选中模板？删除后不可恢复！"
                ),
                array(
                    'label' => '同步模板状态',
                    'href' => 'index.php?app=taoexlib&ctl=admin_sms_sample&act=sync_sample',
                    'target' => 'dialog::{width:600,height:400,title:\'同步模板状态\'}'

                ),
            ),
            'base_filter' => array('disabled'=>'false'),
        );
        $this->finder('taoexlib_mdl_sms_sample', $params);
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
        $sms_sample = $this->app->model('sms_sample');
        $base_filter = array('disabled'=>'false');
        $sub_menu = array(
                0 => array('label'=>app::get('taoexlib')->_('全部'),'filter'=>$base_filter,'optional'=>false),
            );
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $sms_sample->count($v['filter']);
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
    public function add_sample()
    {
        $this->pagedata['type_list']   = $this->send_type();
        $this->pagedata['add_img']     = kernel::base_url(1).'/app/desktop/statics/bundle/btn_add.gif';
        $this->pagedata['sample_code'] = $this->sample_code();
        $this->pagedata['info']['content']     = $this->default_sample();
        $this->page("admin/sms/sample.html");
    }

    /**
     * 显示编辑模板页面
     *
     * @param  void
     * @return html
     * @author
     **/
    public function edit_sample()
    {
        $ids = $this->_request->get_get('p');
        $id = $ids[0];
        $sampleInfo = $this->sampleMdl->select()->columns()->where('id=?',$id)->instance()->fetch_row();
        if(count($sampleInfo)>0){
            $this->pagedata['type_list']   = $this->send_type();
            $this->pagedata['add_img']     = kernel::base_url(1).'/app/desktop/statics/bundle/btn_add.gif';
            $this->pagedata['sample_code'] = $this->sample_code();
            
            $this->pagedata['info']        = $sampleInfo;
        } else {
            $this->pagedata['nosample'] = 'true';
        }
        $this->page("admin/sms/sample.html");
    }

    /**
     * 保存模板信息(包括编辑和添加)
     *
     * @param  void
     * @return html
     * @author
     **/
    public function save_sample()
    {
        $this->begin("");
        $param =$this->_request->get_post();

        if(!$param['title']){
            $this->end(false,app::get('taoexlib')->_('请填写模板标题'));
        }

        if(!$param['content']){
            $this->end(false,app::get('taoexlib')->_('请填写模板内容'));
        }

        #判断内容是否有签名
         preg_match('/\{(短信签名)\}/',$param['content'],$filtcontent);
         
         preg_match('/\【(.*?)\】$/',$param['content'],$filtcontent1);

        if (!$filtcontent && !$filtcontent1) {
                $this->end(false,app::get('taoexlib')->_('请确认模板内容是否有短信签名'));
        }
        if ($filtcontent && $filtcontent1) {
            $this->end(false,app::get('taoexlib')->_('短信签名和【】一个模板只能包含其一!'));
        }
        
        $send_types = array_keys($this->send_type());
        if (!in_array($param['send_type'], $send_types) ){
            $this->end(false,app::get('taoexlib')->_('发送类型非法'));
        }

        if(!$param['sample_no']){
            $this->end(false,app::get('taoexlib')->_('请填写模板内容'));
        }

        $no = $this->sampleMdl->select()->columns()->where('sample_no=?',$param['sample_no'])->instance()->fetch_row();
        if(count($no)>0&&($param['id']!=$no['id'])){
            $this->end(false,app::get('taoexlib')->_('模板编号不能重复'));
        }

         $param['status'] = $param['status']=='true'?1:0;
        if($param['id']&&($param['status'])==0){
             $res = $this->isStop($param['id']);
             if(count($res)>0){
                $this->end(false,app::get('taoexlib')->_('模板对应的绑定关系绑定为开启，无法暂停!'));
             }
        }
        
        $result = $this->sampleMdl->save_sample($param);
        if ($result) {
            if (!$filtcontent && $filtcontent1) {
                kernel::single('taoexlib_request_sms')->newoauth_request(array('sms_sign'=>$filtcontent1[0]));
            }
            $this->end(true,app::get('taoexlib')->_('保存成功'));
        } else {
            $this->end(false,app::get('taoexlib')->_('保存失败，请重新添加'));
        }
    }

    /**
     * 逻辑删除模板
     *
     * @param  string
     * @return json
     * @author
     **/
    public function del_sample()
    {
        $this->begin("index.php?app=taoexlib&ctl=admin_sms_sample&act=index");
        $ids = $this->_request->get_post('id');
        $sampleInfo = $this->sampleMdl->getList('status,id',array('id|in'=>$ids));
        foreach ($sampleInfo as $key => $info) {
            if (!$this->_is_del($info['id'],$msg)) {
                $this->end(false,app::get('taoexlib')->_($msg));
            }else{
                 $this->sampleMdl->delete(array('id'=>$info['id']));
            }
        }
        $this->end(true,app::get('taoexlib')->_('删除成功'));
    }
    /**
     * 模板是否可以删除
     *
     * @param  $sample_id
     * @return bool
     * @author
     **/
    private function _is_del($sample_id,&$msg)
    {
        $bindInfos = app::get('taoexlib')->model('sms_bind')->getList('is_default,status', array('id'=>$sample_id));
        foreach ($bindInfos as $bindInfo) {
            if($bindInfo['is_default']=='1'){
                $msg = '此模板与默认绑定关系绑定，无法删除';
                return false;
            }
            if(count($bindInfo)>0){
                $msg = '请先删除绑定关系，再删除模板';
                return false;
            }
        }

        return true;
    }
    /**
     * 设置模板启用状态
     *
     * @param  void
     * @return void
     * @author
     **/
    public function setStatus($id,$status)
    {
        if($status =='1'){
            //暂停检查有无对应规则使用此模板
            if (!$this->isStop($id)) {
                $ruleList = '模板对应的绑定关系绑定为开启，无法暂停!';
                echo "<script>parent.MessageBox.error('$ruleList');</script>";
                exit;
            }else{
                $now_status = '0';
            }
        }else{
            $now_status = '1';
        }
        $data = array('id'=>$id,"status"=>$now_status);
        app::get('taoexlib')->model('sms_sample')->save($data);

        echo "<script>parent.MessageBox.success('命令已经被成功发送！！');parent.finderGroup['{$_GET[finder_id]}'].refresh();</script>";
        exit;
    }
    /**
     * 检查是否可以暂停模板
     *
     * @param  void
     * @return array
     * @author
     **/
    public function isStop($sample_id)
    {
        $rs = $this->sampleMdl->getOpenBindBySampleId($sample_id);
        if (isset($rs['status'])&&($rs['status']=='1')) {
            return false;
        }
        return true;
    }
    /**
     * 发送的类型
     *
     * @param  void
     * @return array
     * @author
     **/
    public function send_type()
    {
        $type_list = array(
                'delivery'=>array('type' => 'delivery','name' => "发货"),
            );
        return $type_list;
    }
    /**
     * 模板的参数
     *
     * @param  void
     * @return array
     * @author
     **/
    public function sample_code()
    {   $recovery_img =  kernel::base_url(1).'/app/desktop/statics/bundle/afresh.gif';
        $code = array(
                array('id' => 'huiyuan', 'name' => '会&nbsp;&nbsp;员&nbsp;&nbsp;名', 'value' => '{会员名}','br'=>''),
                array('id' => 'shouhuoren', 'name' => '收&nbsp;&nbsp;货&nbsp;&nbsp;人', 'value' => '{收货人}','br'=>'<br>'),
                array('id' => 'dingdanhao', 'name' => '订&nbsp;&nbsp;单&nbsp;&nbsp;号', 'value' => '{订单号}','br'=>''),
                array('id' => 'shouhuodizhi', 'name' => '收货地址', 'value' => '{收货地址}','br'=>'<br>'),
                array('id' => 'peisongfeiyong', 'name' => '配送费用', 'value' => '{配送费用}','br'=>''),
                array('id' => 'wuliugongsi', 'name' => '物流公司', 'value' => '{物流公司}','br'=>'<br>'),
                array('id' => 'wuliudanhao', 'name' => '物流单号', 'value' => '{物流单号}','br'=>''),
                array('id' => 'fukuanjine', 'name' => '付款金额', 'value' => '{付款金额}','br'=>'<br>'),
                array('id' => 'dingdanjine', 'name' => '订单金额', 'value' => '{订单金额}','br'=>''),
                array('id' => 'dingdanyouhui', 'name' => '订单优惠', 'value' => '{订单优惠}','br'=>'<br>'),
                array('id' => 'fahuoshijian', 'name' => '发货时间', 'value' => '{发货时间}','br'=>''),
                array('id' => 'dingdanshijian', 'name' => '订单时间', 'value' => '{订单时间}','br'=>'<br>'),
                array('id'=>'msgsign','name'=>'短信签名','value'=>'{短信签名}'),
                array('id' => 'recovery', 'name' => '<font style="color:#f00">恢复默认</font>', 'value' => $this->default_sample(),'br'=>'<br>','img'=>$recovery_img),

            );
        return $code;
    }

    /**
     * 默认模板
     *
     * @param  void
     * @return string;
     * @author
     **/
    public function default_sample()
    {
        $default_sample = '{收货人}，您好！您在{店铺名称}订购的商品已通过{物流公司}发出，单号：{物流单号},请当面检查后再签收，谢谢！{短信签名}';
        return $default_sample;
    }

    /**
     * 更新短信模板审核状态
     *
     * @param  void
     * @return
     * @author
     **/
    public function sync_sample(){

        $finder_id = $_GET['finder_id'];
        $this->pagedata['finder_id'] = $finder_id;
        unset($finder_id);
        $this->page("admin/sync_sample.html");
    }

    public function do_sync_sample(){
        $result = kernel::single('taoexlib_request_sms')->sms_request('list','get',$param);
        echo json_encode($result);
    }
    
    /**
     * Short description.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function send()
    {
        kernel::single('taoexlib_delivery_sms')->deliverySendMessage(4);
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
        $params = array(
            'title'=>'商品列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'base_filter' => '',
            
        );
       
        $this->finder('taoexlib_mdl_sms_sample_items', $params);
    }

}