<?php
/**
* 规则控制器
*
* @author chenping<chenping@shopex.cn>
*/
class inventorydepth_ctl_regulation extends desktop_controller {

    var $workground = 'resource_center';

    /**
     * 生成URL
     *
     * @return void
     * @author
     **/
    private function gen_url($params=array(),$full=false)
    {
        $params['app'] = isset($params['app']) ? $params['app'] : $this->app->app_id;
        $params['ctl'] = isset($params['ctl']) ? $params['ctl'] : 'regulation';
        $params['act'] = isset($params['act']) ? $params['act'] : 'index';

        return kernel::single('desktop_router')->gen_url($params,$full);
    }

    public function index()
    {
        $params = array(
                'title' => $this->app->_('规则列表'),
                'actions' => array(
                        /*
                        array(
                            'label' => $this->app->_('新建规则'),
                            'href' => $this->gen_url(array('act'=>'add')),
                        ),*/
                        array(
                            'label'  => $this->app->_('新增库存更新规则'),
                            'href'   => $this->gen_url(array('act'=>'add','p[0]'=>'stock')),
                            'target' => '_blank',
                        ),
                        /*
                        array(
                            'label'  => $this->app->_('新增上架规则'),
                            'href'   => $this->gen_url(array('act'=>'add','p[0]'=>'frame')),
                            'target' => '_blank',
                        ),*/
                        array(
                            'label' => $this->app->_('启用'),
                            'submit' => $this->gen_url(array('act'=>'using')),
                            'confirm' => $this->app->_('确定启用选中项？'),
                            'target' => 'refresh',
                        ),
                        array(
                            'label' => $this->app->_('停用'),
                            'submit' => $this->gen_url(array('act'=>'unusing')),
                            'confirm' => $this->app->_('确定停用选中项？'),
                            'target' => 'refresh',
                        )
                    ),
                'use_buildin_filter' => true,
                'use_buildin_recycle' => true
            );
        $this->finder('inventorydepth_mdl_regulation',$params);
    }

    /**
     * 库存回写规则
     *
     * @return void
     * @author
     **/
    public function stockIndex()
    {
        $params = array(
                'title' => $this->app->_('库存回写规则'),
                'actions' => array(
                        array(
                            'label'  => $this->app->_('新建'),
                            'href'   => $this->gen_url(array('act'=>'add','p[0]'=>'stock')),
                            'target' => '_blank',
                        ),
                        array(
                            'label' => $this->app->_('启用'),
                            'submit' => $this->gen_url(array('act'=>'using','p[0]'=>'stockIndex')),
                            'confirm' => $this->app->_('确定启用选中项？'),
                            'target' => 'refresh',
                        ),
                        array(
                            'label' => $this->app->_('停用'),
                            'submit' => $this->gen_url(array('act'=>'unusing','p[0]'=>'stockIndex')),
                            'confirm' => $this->app->_('确定停用选中项？'),
                            'target' => 'refresh',
                        )
                    ),
                'use_buildin_filter' => true,
                'use_buildin_recycle' => true,
                'base_filter' => array('condition'=>'stock','type' => '2'),
            );
        $this->finder('inventorydepth_mdl_regulation',$params);
    }

    /**
     * 上下架规则
     *
     * @return void
     * @author
     **/
    public function frameIndex()
    {
        $params = array(
                'title' => $this->app->_('上下架规则'),
                'actions' => array(
                        array(
                            'label'  => $this->app->_('新建'),
                            'href'   => $this->gen_url(array('act'=>'add','p[0]'=>'frame')),
                            'target' => '_blank',
                        ),
                        array(
                            'label' => $this->app->_('启用'),
                            'submit' => $this->gen_url(array('act'=>'using','p[0]'=>'frameIndex')),
                            'confirm' => $this->app->_('确定启用选中项？'),
                            'target' => 'refresh',
                        ),
                        array(
                            'label' => $this->app->_('停用'),
                            'submit' => $this->gen_url(array('act'=>'unusing','p[0]'=>'frameIndex')),
                            'confirm' => $this->app->_('确定停用选中项？'),
                            'target' => 'refresh',
                        )
                    ),
                'use_buildin_filter' => true,
                'use_buildin_recycle' => true,
                'base_filter' => array('condition'=>'frame'),
            );
        $this->finder('inventorydepth_mdl_regulation',$params);
    }

    function _views() {
        return;
        $sub_menu[0] = array('label' => $this->app->_('全部'),'filter'=>array());
        $sub_menu[1] = array('label' => $this->app->_('更新库存规则列表'), 'filter' => array('condition'=>'stock'), 'optional' => false);
        $sub_menu[2] = array('label' => $this->app->_('商品上下架规则列表'), 'filter' => array('condition'=>'frame'), 'optional' => false);

        $regulationModel = $this->app->model('regulation');
        foreach ($sub_menu as $key=>$value) {
            $sub_menu[$key]['addon'] = $regulationModel->count($value['filter']);
            $sub_menu[$key]['href'] = $this->gen_url(array('view'=>$key));
        }
        return $sub_menu;
    }

    /**
     * 规则页面初始参数
     *
     * @return void
     * @author
     **/
    private function options()
    {
        # 规则类型:frame:上下架、stock:更新库存
        $options['condition']   = kernel::single('inventorydepth_regulation')->get_condition();

        # 上下架/库存更新对应的模型
        $options['model']       = kernel::single('inventorydepth_regulation')->get_condition_model();

        # 逻辑比较符:大于、小于等等
        $options['comparison']  = kernel::single('inventorydepth_math')->get_show_comparison();

        # 算数比较符: + 、- 等等
        $options['calculation'] = kernel::single('inventorydepth_math')->get_calculation();

        # 上下架对应的参数:upper:上架、lower:下架
        $options['frame']       = kernel::single('inventorydepth_frame')->get_benchmark();

        # 几种库存状态:actual_stock:可售库存、release_stock:发布库存等等
        $options['stock']       = kernel::single('inventorydepth_stock')->get_benchmark();

        # 条件对应的几中库存状态
        $options['obj']         = kernel::single('inventorydepth_stock')->get_benchobj();

        # 条件针对货品数
        $options['forsku']        = kernel::single('inventorydepth_frame')->sku_option();

        return $options;
    }

    public function add($condition='stock')
    {
        $this->title = ($condition == 'stock') ? $this->app->_('添加更新库存规则') : $this->app->_('添加更新商品上下架规则');

        $PG = $this->options();

        # 伪造一条数据给循环用
        $PG['data']['content']['filters'] = array(1);

        # 规则类型
        $PG['data']['condition'] = $condition;

        $this->pagedata = $PG;
        $this->pagedata['title'] = $this->title;
        $this->singlepage('regulation.html');
    }

    public function save()
    {
        $this->begin();
        $data = kernel::single('inventorydepth_regulation')->check_and_build($_POST,$msg);
        if ($data === false) {
            $this->end(false,$msg);
        }

        $mark = $this->app->model('regulation')->save($data);
        if ($mark && $_POST['regulation_shop_level'] == 'true') {
            $remote_ip = kernel::single('base_component_request')->get_remote_ip();
            $apply = array(
                'bn' => $_POST['regulation_shop_bn'],
                'heading' => $_POST['regulation_shop_bn'].'规则应用',
                'condition' => $data['condition'],
                'style' => 'stock_change',
                'start_time' => time(),
                'end_time' => strtotime('2030-12-12'),
                'shop_id' => $_POST['regulation_shop_id'],
                'using' => 'true',
                'al_exec' => 'false',
                'operator' => 16777215,
                'update_time' => time(),
                'operator_ip' => $remote_ip,
                'regulation_id' => $data['regulation_id'],
                'apply_goods' => '_ALL_',
                'apply_pkg' => '_ALL_',
                'priority' => 10,
                'type' => '1',
            );
            $this->app->model('regulation_apply')->save($apply);
        }

        $url = $this->gen_url(array('act'=>'index'));
        $msg = $mark ? $this->app->_('保存成功!') : $this->app->_('保存失败!');
        //$js  = $mark ? 'javascript:location.href="'.$url.'";' : '';
        $this->end($mark ? true : false,$msg,$js);
    }

    public function dialogEdit($regulationId) {
        $PG = $this->options();

        $PG['data'] = $this->app->model('regulation')->select()->columns('*')
                        ->where('regulation_id=?',$regulationId)
                        ->instance()->fetch_row();

        $this->title = ($PG['data']['condition'] == 'stock') ? $this->app->_('修改更新库存规则') : $this->app->_('修改更新商品上下架规则');

        $PG['regulation_readonly'] = $_GET['regulation_readonly'] ? 'true' : 'false';

        $this->pagedata = $PG;

        $this->pagedata['title'] = $this->title;
        $this->display('regulation.html');
    }

    public function dialogAdd($shop_id,$shop_bn,$condition='stock')
    {
        $this->title = ($condition == 'stock') ? $this->app->_('添加更新库存规则') : $this->app->_('添加更新商品上下架规则');

        $PG = $this->options();

        # 伪造一条数据给循环用
        $PG['data']['content']['filters'] = array(1);

        # 规则类型
        $PG['data']['condition'] = $condition;

        $PG['regulation_shop_level'] = 'true';
        $PG['regulation_shop_id'] = $shop_id;
        $PG['regulation_shop_bn'] = $shop_bn;

        $this->pagedata = $PG;
        $this->pagedata['title'] = $this->title;
        $this->display('regulation.html');
    }

    public function edit($regulationId)
    {
        $PG = $this->options();

        $PG['data'] = $this->app->model('regulation')->select()->columns('*')
                        ->where('regulation_id=?',$regulationId)
                        ->instance()->fetch_row();

        $this->title = ($PG['data']['condition'] == 'stock') ? $this->app->_('修改更新库存规则') : $this->app->_('修改更新商品上下架规则');

        if (empty($PG['data']))
            $this->splash('error', '', $this->app->_('不存在的记录'));

        $this->pagedata = $PG;

        $this->pagedata['title'] = $this->title;
        $this->singlepage('regulation.html');
    }

    public function using($act='stockIndex')
    {
        $this->begin($this->gen_url(array('act'=>$act)));
        $bool = $this->app->model('regulation')->update(array('using'=>'true'),$_POST);
        $msg = $bool ? $this->app->_('启用成功！') : $this->app->_('启用失败！');
        $this->end($bool,$msg);
    }

    public function unusing($act='frameIndex')
    {
        $this->begin($this->gen_url(array('act'=>$act)));
        $regulation_id = $this->app->model('regulation')->getList('regulation_id',$_POST);
        $rid = array_map('current',$regulation_id);
        $apply = $this->app->model('regulation_apply')->getList('id',array('regulation_id'=>$rid,'using'=>'true'),0,1);
        if ($apply) {
            $this->end(false,'规则对应的应用已经开启，无法停用！');
        }

        $bool = $this->app->model('regulation')->update(array('using'=>'false'),$_POST);
        $msg = $bool ? $this->app->_('停用成功！') : $this->app->_('停用失败！');
        $this->end($bool,$msg);
    }

        /** 前台做选择时，做规则描述自动翻译
         *
         * @param string $comparison 逗号分隔的字符串 equal,between,bthan,sthan
         * @param string $increment 逗号分隔的字符串 123,456,789,321
         * @param string $increment_after 逗号分隔的字符串 111,222,333,444
         * @param string $result 执行结果 upper,lower,upper,lower|{销售库存}+10,{实际库存}+3,{销售库存}+6,{销售库存}+1,
         */
    public function ajax_checkLogic($data = NULL)
    {
        $post = kernel::single('base_component_request')->get_post();

        $data = $data ? $data : $post;

        $return = kernel::single('inventorydepth_regulation')->get_description($data);
        echo implode("\n", $return);
    }

    public function checkFormula()
    {
        $formulaCurrect = kernel::single('inventorydepth_stock')->formulaRun($_POST['result'],null,$msg);

        if ($formulaCurrect === false)
            echo 'fail';
        else
            echo 'succ';
    }
}
