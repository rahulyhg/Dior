<?php
/**
* 规则应用类
*
* @author chenping
* @version 2012-6-7 14:22
*/
class inventorydepth_ctl_regulation_apply extends desktop_controller
{
    var $workground = 'resource_center';

    public function __construct($app)
    {
        parent::__construct($app);
        $this->_request = kernel::single('base_component_request');
    }

    /**
     * 生成URL
     *
     * @return void
     * @author
     **/
    private function gen_url($params=array(),$full=false)
    {
        $params['app'] = isset($params['app']) ? $params['app'] : $this->app->app_id;
        $params['ctl'] = isset($params['ctl']) ? $params['ctl'] : 'regulation_apply';
        $params['act'] = isset($params['act']) ? $params['act'] : 'index';

        return kernel::single('desktop_router')->gen_url($params,$full);
    }

    public function index()
    {
        $actions = array(
                'title' => $this->app->_('规则应用列表'),
                'actions' => array(
                        /*
                        array(
                            'label' => $this->app->_('新建应用'),
                            'href' => $this->gen_url(array('act'=>'add')),
                            'target' => '_blank',
                        ),*/
                        array(
                            'label'=>$this->app->_('新建上下架应用'),
                            'href' => $this->gen_url(array('act'=>'add','p[0]'=>'frame')),
                            'target' => '_blank',
                        ),
                        array(
                            'label' => $this->app->_('新建库存回写应用'),
                            'href' => $this->gen_url(array('act'=>'add','p[0]'=>'stock')),
                            'target' => '_blank',
                        ),
                        array(
                            'label' => $this->app->_('启用'),
                            'submit' => $this->gen_url(array('act'=>'using')),
                            'confirm' => $this->app->_('确定启用选中项？'),
                            'target' => 'refresh'
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
        $this->finder(
                'inventorydepth_mdl_regulation_apply',
                $actions
            );
    }

    public function stockIndex()
    {
        $actions = array(
                'title' => $this->app->_('回写库存规则应用列表'),
                'actions' => array(
                        array(
                            'label' => $this->app->_('新建库存回写应用'),
                            'href' => $this->gen_url(array('act'=>'add','p[0]'=>'stock')),
                            'target' => '_blank',
                        ),
                        array(
                            'label' => $this->app->_('启用'),
                            'submit' => $this->gen_url(array('act'=>'using')),
                            'confirm' => $this->app->_('确定启用选中项？'),
                            'target' => 'refresh'
                        ),
                        array(
                            'label' => $this->app->_('停用'),
                            'submit' => $this->gen_url(array('act'=>'unusing')),
                            'confirm' => $this->app->_('确定停用选中项？'),
                            'target' => 'refresh',
                        )
                    ),
                'use_buildin_filter' => true,
                'use_buildin_recycle' => true,
                'base_filter' => array('condition'=>'stock','type'=>'2'),
            );
        $this->finder(
                'inventorydepth_mdl_regulation_apply',
                $actions
            );
    }

    public function frameIndex()
    {
        $actions = array(
                'title' => $this->app->_('回写上下架规则应用列表'),
                'actions' => array(
                        array(
                            'label'=>$this->app->_('新建上下架应用'),
                            'href' => $this->gen_url(array('act'=>'add','p[0]'=>'frame')),
                            'target' => '_blank',
                        ),
                        array(
                            'label' => $this->app->_('启用'),
                            'submit' => $this->gen_url(array('act'=>'using')),
                            'confirm' => $this->app->_('确定启用选中项？'),
                            'target' => 'refresh'
                        ),
                        array(
                            'label' => $this->app->_('停用'),
                            'submit' => $this->gen_url(array('act'=>'unusing')),
                            'confirm' => $this->app->_('确定停用选中项？'),
                            'target' => 'refresh',
                        )
                    ),
                'use_buildin_filter' => true,
                'use_buildin_recycle' => true,
                'base_filter' => array('condition' => 'frame'),
            );
        $this->finder(
                'inventorydepth_mdl_regulation_apply',
                $actions
            );
    }

    /**
     * 添加规则应用
     *
     * @return void
     * @author
     **/
    public function add($condition = 'stock')
    {
        $this->title = $this->app->_('添加规则应用');

        $this->pagedata['options'] = $this->options();

        # 在没有应用编号的情况下，临时编号
        $this->pagedata['init_bn'] = uniqid();

        # 获取所有已经联通的店铺
        $filter = array('filter_sql'=>'({table}node_id is not null AND {table}node_id!="" )');
        if (app::get('drm')->is_installed()) {
            $channelShopObj = &app::get('drm')->model('channel_shop');
            $rows = $channelShopObj->getList('shop_id');
            foreach($rows as $val){
                $shopIds[] = $val['shop_id'];
                $filter['shop_id|notin'] = $shopIds;
            }
        }
        $data['shops'] = $this->app->model('shop')->getList('shop_id,name',$filter);

        $data['condition'] = $condition;

        $this->pagedata['data'] = $data;
        $this->pagedata['title'] = $this->title;

        if ($condition == 'stock') {
            $this->singlepage('regulation/stock_apply.html');
        } else {
            $this->singlepage('regulation/frame_apply.html');
        }
        //$this->pagedata['condition'] = $condition;
        //$this->singlepage('regulation/apply.html');
    }

    /**
     * @description 编辑应用
     * @access public
     * @param void
     * @return void
     */
    public function edit($id)
    {
        $this->title=  $this->app->_('编辑规则应用');

        $applyModel = $this->app->model('regulation_apply');
        $data = $applyModel->select()->columns('*')->where('id=?',$id)->instance()->fetch_row();

        # 获取ID范围
        if ($data['apply_goods'] && $data['apply_goods'] != '_ALL_') {
            /*
            $type = ($data['condition'] == 'stock') ? 'products' : 'goods';
            $rmapping = $this->app->model('regulation_mapping');
            $pgid = $rmapping->getList('pgid',array('type'=>$type,'apply_id'=>$data['id']));
            */
            $data['pgid'] = explode(',',$data['apply_goods']);

            $sign = ($data['condition'] == 'stock') ? '货品' : '商品';
            $func = ($data['condition'] == 'stock') ? 'product_selected_show' : 'goods_selected_show';
            $domid = ($data['condition'] == 'stock') ? 'hand-selected-product' : 'hand-selected-goods';
            $count = count($data['pgid']);
            $this->pagedata['replacehtml'] = <<<EOF
<div id='{$domid}'>已选择了{$count}{$sign},<a href='javascript:void(0);' onclick='{$func}();'>查看选中{$sign}.</a></div>
EOF;
        }

        if ($data['apply_pkg'] && $data['apply_goods'] != '_ALL_') {
            $data['pkg_id'] = explode(',',$data['apply_pkg']);

            $sign = '捆绑商品';
            $func = 'pkg_selected_show';
            $domid = 'hand-selected-pkg';
            $count = count($data['pkg_id']);
            $this->pagedata['replacehtml_pkg'] = <<<EOF
<div id='{$domid}'>已选择了{$count}{$sign},<a href='javascript:void(0);' onclick='{$func}();'>查看选中{$sign}.</a></div>
EOF;
        }

        //$selShops = $this->app->model('regulation_shop')->getList('shop_id',array('apply_id'=>$data['id']));
        //$data['shop_id'] = array_map('current',$selShops);
        $data['shop_id'] = explode(',',$data['shop_id']);


        # 获取所有已经联通的店铺
        $filter = array('filter_sql'=>'({table}node_id is not null AND {table}node_id!="" )');
        if (app::get('drm')->is_installed()) {
            $channelShopObj = &app::get('drm')->model('channel_shop');
            $rows = $channelShopObj->getList('shop_id');
            foreach($rows as $val){
                $shopIds[] = $val['shop_id'];
                $filter['shop_id|notin'] = $shopIds;
            }
        }
        $data['shops'] = $this->app->model('shop')->getList('shop_id,name',$filter);


        $this->pagedata['data'] = $data;
        $this->pagedata['options'] = $this->options();

        $this->pagedata['init_bn'] = $data['bn'];
        $this->pagedata['apply_goods_query'] = http_build_query($data['apply_goods']);
        $this->pagedata['title'] = $this->title;
        //$this->singlepage('regulation/apply.html');
        if ($data['condition'] == 'stock') {
            $this->singlepage('regulation/stock_apply.html');
        } else {
            $this->singlepage('regulation/frame_apply.html');
        }
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    private function options()
    {
        $reguObj = kernel::single('inventorydepth_regulation');
        $options['style'] = $reguObj->get_style();
        $options['model'] = $reguObj->get_condition_model();
        return $options;
    }

    /**
     * 启用应用
     *
     * @return void
     * @author
     **/
    public function using()
    {
        $bool = $this->app->model('regulation_apply')->update(array('using'=>'true'),$_POST);
        $this->splash($bool ? 'success' : 'error', 'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();', $this->app->_('启用成功'));
    }

    /**
     * 停用应用
     *
     * @return void
     * @author
     **/
    public function unusing()
    {
        $bool = $this->app->model('regulation_apply')->update(array('using'=>'false'),$_POST);
        $this->splash($bool ? 'success' : 'error', 'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();', $this->app->_('停用成功'));
    }

    /**
     * 前端商品绑定列表
     *
     * @return void
     * @author
     **/
    public function merchandise_finder()
    {
        $params = array(
            'title' => $this->app->_('店铺商品关系列表'),
            'use_buildin_filter' => true,
            'use_buildin_recycle' => false,
            'alertpage_finder' => true,
            'use_view_tab' => false,
        );

        $condition = $this->_request->get_get('condition');

        $model = ($condition == 'stock') ? 'inventorydepth_mdl_shop_skus' : 'inventorydepth_mdl_shop_items';

        $shop_id = $this->_request->get_get('shop_id');
        if ($shop_id) {
            $params['base_filter']['shop_id'] = $shop_id;
        }

        $this->finder($model,$params);
    }

    public function merchandise_dialog_filter(){
        $condition = $this->_request->get_get('condition');
        $init_bn = $this->_request->get_get('init_bn');
        $model = kernel::single('inventorydepth_regulation')->get_condition_model($condition);

        $get = $this->_request->get_get();
        if ($init_bn) {
            $g = kernel::single('inventorydepth_regulation_apply')->fetch_merchandise_filter($init_bn);
            $get = $g ? $g : $get;
            $get['init_bn'] = $init_bn;
        }


        $this->main($model,$this->app,$get,$this);
    }

    private function main($object_name,$app,$filter=null,$controller=null,$cusrender=null){
        if(strpos($_GET['object'],'@')!==false){
            $tmp = explode('@',$object_name);
            $app = app::get($tmp[1]);
            $object_name = $tmp[0];
        }
        $object = $app->model($object_name);
        $ui = new base_component_ui($controller,$app);
        require(APP_DIR.'/base/datatypes.php');
        $this->dbschema = $object->get_schema();

        foreach(kernel::servicelist('extend_filter_'.get_class($object)) as $extend_filter){
            $colums = $extend_filter->get_extend_colums();
            if($colums[$object_name]){
                $this->dbschema['columns'] = array_merge((array)$this->dbschema['columns'],(array)$colums[$object_name]['columns']);
            }
        }

        foreach($this->dbschema['columns'] as $c=>$v){
            if(!$v['filtertype']) continue;

            /*
            if( isset($filter[$c]) ) {
                continue;
            }*/

            if (isset($filter['init_bn'])) {
                if($filter[$c]) $v['filterdefault'] = true;

                if(!$filter[$c]) $v['filterdefault'] = false;
            }


            if(!is_array($v['type']))
                if(strpos($v['type'],'decimal')!==false&&$v['filtertype']=='number'){
                    $v['type'] = 'number';
                }
            $columns[$c] = $v;
            if(!is_array($v['type']) && $v['type']!='bool' && isset($datatypes[$v['type']]) && isset($datatypes[$v['type']]['searchparams'])){
                $addon='<select search="1" name="_'.$c.'_search" class="x-input-select  inputstyle">';
                foreach($datatypes[$v['type']]['searchparams'] as $n=>$t){
                    $addon.="<option value='{$n}'>{$t}</option>";
                }
                $addon.='</select>';
            }elseif($v['type'] == 'skunum'){
                $addon='<select search="1" name="_'.$c.'_search" class="x-input-select  inputstyle">';
                $__select = array('nequal'=>app::get('base')->_('='),'than'=>app::get('base')->_('>'),'lthan'=>app::get('base')->_('<'));
                foreach($__select as $n=>$t){
                    $addon.="<option value='{$n}'>{$t}</option>";
                }
                $addon.='</select>';
            }else{
                if($v['type']!='bool')
                    $addon = app::get('desktop')->_('是');
                else $addon = '';
            }
            $columns[$c]['addon'] = $addon;
            if($v['type']=='last_modify'){
                $v['type'] = 'time';
            }
             $params = array(
                    'type'=>$v['type'],
                    'name'=>$c,
                );

            if ($filter[$c]) {
                $params['value'] = $filter[$c];
            }
            if($v['type']=='bool'&&$v['default']){
                $params = array_merge(array('value'=>$v['default']),$params);
            }
            if($this->name_prefix){
                $params['name'] = $this->name_prefix.'['.$params['name'].']';
            }
            if($v['type']=='region'){
                $params['app'] = 'eccommon';
            }



            $inputer = $ui->input($params);
            $columns[$c]['inputer'] = $inputer;
        }

        if($cusrender){
          return array('filter_cols'=>$columns,'filter_datatypes'=>$datatypes);
        }

        if($object->has_tag){
            $this->pagedata['app_id'] = $app->app_id;
            $this->pagedata['tag_type'] = $object_name;
            $tag_inputer = $this->fetch('finder/tag_inputer.html');
            $columns['tag'] = array('filtertype'=>true,'filterdefault'=>true,'label'=>app::get('desktop')->_('标签'),'inputer'=>$tag_inputer);
        }

        $this->pagedata['columns'] = $columns;
        $this->pagedata['datatypes'] = $datatypes;
        $this->pagedata['finder_id'] = uniqid();

        $this->display('finder/finder_filter.html');
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function _views()
    {
        $view = array(
                0 => array('label'=>$this->app->_('全部'),'href'=>''),
            );

        return $view;
    }

    /**
     * 前端商品条件集
     *
     * @return void
     * @author
     **/
    public function merchandise_filter()
    {
        $condition = $this->_request->get_post('condition');
        if (!$condition) {
            $this->splash('error', '', $this->app->_('规则类型错误!'));
        }

        $shop_id = $this->_request->get_post('shop_id');
        $init_bn = $this->_request->get_post('init_bn');
        $id['id'] = $this->_request->get_post('id');
        $merchandise_filter = $this->_request->get_post('filter');

        if ($shop_id) {
            $shop_id = http_build_query(array('shop_id'=>$shop_id));
            $shop_id = str_replace('&', ',', $shop_id);
            $merchandise_filter['advance'] = $merchandise_filter['advance'] ? $merchandise_filter['advance'].','.$shop_id : $shop_id;
        }

        $msg = kernel::single('inventorydepth_regulation_apply')->choice_callback($condition,$init_bn,$id,$merchandise_filter);

        $this->splash('success', '', $msg);
    }

    public function merchandise_filter_array(){
        $condition = $this->_request->get_post('condition');
        $init_bn = $this->_request->get_post('init_bn');

        if (!$init_bn) {
            $this->splash('error', '', $this->app->_('规则类型错误!'));
        }
        $merchandise_filter = $this->_request->get_post('filter');

        $msg = kernel::single('inventorydepth_regulation_apply')->choice_callback_array($condition,$init_bn,$merchandise_filter);
        $this->splash('success','',$msg);
    }

    /**
     * 已选择的商品和货品
     *
     * @return void
     * @author
     **/
    public function finder_choice() {
        $init_bn = $this->_request->get_get('init_bn');
        $condition = $this->_request->get_get('condition');
        if(!$init_bn) {
            echo "参数错误";exit;
        }
        $filter = array(
            'init_bn' => $init_bn,
            'condition' => $condition,
        );
        $tt = ($condition == 'stock') ? '货品' : '商品';
        $title = $this->app->_("已选择的{$tt}列表");
        $params = array(
            'title'               => $tt,
            'use_buildin_filter'  => true,
            'use_buildin_recycle' => false,
            'base_filter'         => $filter,
            'use_buildin_setcol'  => false,
        );

        $model = ($condition == 'stock') ? 'inventorydepth_mdl_regulation_productselect' : 'inventorydepth_mdl_regulation_goodselect';

        $this->finder( $model, $params);

    }

    /**
     * 删除已选择的商品/货品
     *
     * @param Int $merchandise_id 映射关系ID
     * @param String $init_bn 应用编号
     * @parma String $condition 规则类型
     *
     * @return void
     * @author
     **/
    public function removeFilter($id,$init_bn,$condition) {
        $this->begin();
        if(!$id || !$init_bn || !$condition) {
            $this->end(false,$this->app->_('错误参数'));
        }

        $model = ($condition == 'stock') ? 'regulation_productselect' : 'regulation_goodselect';
        $result = $this->app->model($model)->doRemove($init_bn,$id);
        $this->end($result);
    }

    /**
     * 保存规则应用
     *
     * @return void
     * @author
     **/
    public function save()
    {
        $this->begin();
        $post = $this->_request->get_post();
        $data = $this->check_params($post,$msg);
        if ($data === false) {
            $this->end(false,$msg);
        }

        $applyModel = $this->app->model('regulation_apply');

        $result = $applyModel->save($data);
        /*
        if ($result) {
            //kernel::single('inventorydepth_regulation_apply')->destory_merchandise_filter($post['init_bn']);
            # 如果是针对ID 保存对应关系
            if ($data['id']) {
                # 先删除
                $type = ($data['condition'] == 'stock') ? 'products' : 'goods';
                $rmapping = $this->app->model('regulation_mapping');
                $rmapping->delete(array('apply_id'=>$data['id']));
                if ($data['pgid']) {
                    $rmapping->batchSave($data['id'],$data['pgid'],$type);
                }

                $rshop = $this->app->model('regulation_shop');
                $rshop->delete(array('apply_id'=>$data['id']));
                if($data['shop_id']) {
                    $rshop->batchSave($data['id'],$data['shop_id']);
                }
            }
        }*/
        $url = $this->gen_url(array('act'=>'index'));
        $msg = $result ? $this->app->_('保存成功') : $this->app->_('保存失败');
        $this->end($result,$msg);
    }

    /**
     * @description 检查提交参数是否合法
     * @access public
     * @param void
     * @return void
     */
    public function check_params($post,&$msg)
    {
        if (empty($post['bn'])) {
            $msg = $this->app->_('应用规则不能为空!');
            return false;
        }

        $applyModel = $this->app->model('regulation_apply');
        $count = $applyModel->count(array('bn'=>$post['bn'],'condition'=>$post['condition']));
        if ((int)$count>0 && empty($post['id'])) {
            $msg = $this->app->_('应用规则已经存在!');
            return false;
        }

        if (empty($post['heading'])) {
            $msg = $this->app->_('应用名称不能为空!');
            return false;
        }

        if (!kernel::single('inventorydepth_regulation')->get_condition($post['condition'])) {
            $msg = $this->app->_('规则类型不存在!');
            return false;
        }

        if (!kernel::single('inventorydepth_regulation')->get_style($post['style'])) {
            $msg = $this->app->_('触发类型不存在!');
            return false;
        }

        /*
        if ($post['rangetype'] == 'all') {
            unset($post['shop_id']);
            $post['shop_id'][0] = '_ALL_';
        }else{
            if ($post['rangetype'] != 'some'){
                $msg = $this->app->_('店铺不能为空!');
                return false;
            }
        }*/
        if(!$post['shop_id']) {
            $msg = $this->app->_('店铺不能为空!');
            return false;
        }
        $post['shop_id'] = implode(',',$post['shop_id']);

        /*
        if ($post['apply-goods-all'] == 'true') {
            $apply_goods['id'] = '_ALL_';
            $post['apply_goods'] = $apply_goods;
        }else{}*/
            /*
            $apply_goods = kernel::single('inventorydepth_regulation_apply')->fetch_merchandise_filter($post['init_bn']);
            if (empty($apply_goods)) {
                $msg = $this->app->_('应用对象不能空!');
                return false;
            }*/

            if ($post['condition'] == 'stock') {
                if ((!is_array($post['product_id']) || empty($post['product_id'])) && (!is_array($post['pkg_id']) || empty($post['pkg_id'])) && $post['apply-goods-all'] != 'true') {
                    $msg = $this->app->_('应用对象不能空!');
                    return false;
                }
                if ($post['product_id'] && is_array($post['product_id'])) {
                    $post['apply_goods'] = implode(',',$post['product_id']);
                } else {
                    $post['apply_goods'] = '';
                }

                if ($post['pkg_id'] && is_array($post['pkg_id'])) {
                    $post['apply_pkg'] = implode(',',$post['pkg_id']);
                } else {
                    $post['apply_pkg'] = '';
                }

                if ($post['apply-goods-all'] == 'true') {
                    $post['apply_goods'] = '_ALL_';
                    $post['apply_pkg'] = '_ALL_';
                }

            }elseif($post['condition'] == 'frame'){
                if ($post['apply-goods-all'] == 'true') {
                    $post['apply_goods'] = '_ALL_';
                } else {
                    if (!is_array($post['goods_id']) || empty($post['goods_id'])) {
                        $msg = $this->app->_('应用对象不能空!');
                        return false;
                    }
                    $post['apply_goods'] = implode(',',$post['goods_id']);
                }
            } else {
                $msg = $this->app->_('应用对象不能空!');
                return false;
            }

        if (empty($post['regulation_id'])) {
            $msg = $this->app->_('规则不能为空!');
            return false;
        }

        $regulation = $this->app->model('regulation')->select()->columns('`condition`,`type`')
                                            ->where('regulation_id=?',$post['regulation_id'])->instance()->fetch_row();
        if ($regulation['condition'] != $post['condition']) {
            $msg = $this->app->_('请选择符合类型的规则!');
            return false;
        }
        $post['type'] = $regulation['type'];

        $start_time = strtotime($post['start_time'].' '.$post['_DTIME_']['H']['start_time'].':'.$post['_DTIME_']['M']['start_time']);
        $end_time = strtotime($post['end_time'].' '.$post['_DTIME_']['H']['end_time'].':'.$post['_DTIME_']['M']['end_time']);
        if ($end_time<time()) {
            $msg = $this->app->_('当前时间大于结束时间!');
            return false;
        }
        if ($end_time && $start_time>$end_time) {
            $msg = $this->app->_('开始时间大于结束时间');
            return false;
        }

        $post['start_time'] = $start_time;
        $post['end_time'] = $end_time;
        $post['operator'] = $this->user->user_id;
        $post['operator_ip'] = $this->_request->get_remote_ip();
        $post['using'] = 'false';
        $post['al_exec'] = 'false';
        return $post;
    }

    public function singlepage($view, $app_id='')
    {

        $service = kernel::service(sprintf('desktop_controller_display.%s.%s.%s', $_GET['app'],$_GET['ctl'],$_GET['act']));
        if($service){
            if(method_exists($service, 'get_file'))  $view = $service->get_file();
            if(method_exists($service, 'get_app_id'))   $app_id = $service->get_app_id();
        }

        $page = $this->fetch($view, $app_id);

        $this->pagedata['_PAGE_PAGEDATA_'] = $this->_vars;

        $re = '/<script([^>]*)>(.*?)<\/script>/is';
        $this->__scripts = '';

        preg_match_all($re, $page, $match);
        if (is_array($match[0])) {
            foreach ($match[0] as $key => $one) {
                if ($match[2][$key] && !strpos($match[1][$key], 'src') && !strpos($match[1][$key], 'hold')) {
                    $this->__scripts.="\n" . $match[2][$key];

                    $page = str_replace($one, '&nbsp' , $page);

                }
            }
        }

        $page = $page . '<script type="text/plain" id="__eval_scripts__" >' . $this->__scripts . '</script>';

        $this->pagedata['statusId'] = $this->app->getConf('b2c.wss.enable');
        $this->pagedata['session_id'] = kernel::single('base_session')->sess_id();
        $this->pagedata['desktop_path'] = app::get('desktop')->res_url;
        $this->pagedata['shopadmin_dir'] = dirname($_SERVER['PHP_SELF']).'/';
        $this->pagedata['shop_base'] = $this->app->base_url();
        $this->pagedata['desktopresurl'] = app::get('desktop')->res_url;
        $this->pagedata['desktopresfullurl'] = app::get('desktop')->res_full_url;


        $this->pagedata['_PAGE_'] = &$page;
        $this->display('singlepage.html','desktop');
    }

    /**
     * @description 显示选用的货品
     * @access public
     * @param void
     * @return void
     */
    public function showProducts()
    {
        $product_id = kernel::single('base_component_request')->get_post('product_id');

        if ($product_id) {
            $this->pagedata['_input'] = array(
                'name' => 'product_id',
                'idcol' => 'product_id',
                '_textcol' => 'name',
            );

            $productModel = $this->app->model('products');
            $list = $productModel->getList('product_id,name',array('product_id'=>$product_id),0,-1,'product_id asc');
            $this->pagedata['_input']['items'] = $list;
        }

        $this->display('regulation/show_products.html');
    }

    /**
     * @description 显示已经选中的捆绑商品
     * @access public
     * @param void
     * @return void
     */
    public function showPkg()
    {
        $pkg_id = kernel::single('base_component_request')->get_post('pkg_id');

        if ($pkg_id) {
            $this->pagedata['_input'] = array(
                'name' => 'pkg_id',
                'idcol' => 'pkg_id',
                '_textcol' => 'name',
            );

            $pkgModel = app::get('omepkg')->model('pkg_goods');
            $list = $pkgModel->getList('goods_id as pkg_id,name',array('goods_id'=>$pkg_id),0,-1,'goods_id asc');
            $this->pagedata['_input']['items'] = $list;
        }

        $this->display('regulation/show_pkg.html');
    }

    /**
     * @description 显示选用的商品
     * @access public
     * @param void
     * @return void
     */
    public function showGoods()
    {
        $goods_id = kernel::single('base_component_request')->get_post('goods_id');

        if ($goods_id) {
            $this->pagedata['_input'] = array(
                'name' => 'id',
                'idcol' => 'id',
                '_textcol' => 'title',
            );

            $goodsModel = $this->app->model('shop_items');
            $list = $goodsModel->getList('id,title',array('id'=>$goods_id),0,-1,'id asc');
            $this->pagedata['_input']['items'] = $list;
        }

        $this->display('regulation/show_goods.html');
    }

    function finder_common(){
        $params = array(
                        'title'=>app::get('desktop')->_('列表'),
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'use_buildin_filter'=>true,
                        'use_buildin_setcol'=>true,
                        'use_buildin_refresh'=>true,
                        'finder_aliasname'=>'finder_common',
                        'alertpage_finder'=>true,
                        'use_buildin_tagedit'=>false,
                    );
        if ($_GET['findercount']) {
            $params['object_method']['count'] = $_GET['findercount'];
        }
        if ($_GET['findergetlist']) {
            $params['object_method']['getlist'] = $_GET['findergetlist'];
        }
        if(substr($_GET['name'],0,7) == 'adjunct') $params['orderBy'] = 'goods_id desc';
        $this->finder($_GET['app_id'].'_mdl_'.$_GET['object'],$params);
    }
}