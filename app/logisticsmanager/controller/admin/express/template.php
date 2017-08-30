<?php
class logisticsmanager_ctl_admin_express_template extends desktop_controller{
    var $name = "快递面单管理";
    var $workground = "setting_tools";
    
    function index(){
        $base_filter = array('template_type' => array('normal', 'electron'));
        $params = array(
            'title'=>'快递面单管理',
            'actions' => array(
                array(
                    'label' => '新增普通面单',
                    'href' => 'index.php?app=logisticsmanager&ctl=admin_express_template&act=addTmpl&type=normal&finder_id='.$_GET['finder_id'],
                    'target' => "_blank",
                ),
                array(
                    'label' => '新增电子面单',
                    'href' => 'index.php?app=logisticsmanager&ctl=admin_express_template&act=addTmpl&type=electron&finder_id='.$_GET['finder_id'],
                    'target' => "_blank",
                ),
                array(
                            'label' => '导入面单模板',
                            'href' => 'index.php?app=logisticsmanager&ctl=admin_express_template&act=importnormalTmpl',
                            'target' => "dialog::{width:400,height:300,title:'导入面单模板'}",
                        ),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'base_filter' => $base_filter
        );
        $this->finder('logisticsmanager_mdl_express_template', $params);
    }

    /* 新增模板 */
    public function addTmpl() {
        $this->_edit();
    }

    /* 编辑模板 */
    public function editTmpl($template_id){
        $this->_edit($template_id);
    }

    private function _edit($template_id=NULL){
        $templateObj = &$this->app->model('express_template');
        $elements = $templateObj->getElements();
        if($template_id){
            $template = $templateObj->dump($template_id);
            $this->pagedata['title'] = '编辑模板';

			#普通快递面单,放出自定义打印项
			if($template['template_type'] == 'normal'){
            //获取用户自定义的打印项
            $_arr_self_elments = $this->app->getConf('logisticsmanager.delivery.express.template.selfElments');
            $arr_self_elments = $_arr_self_elments['element_'.$template_id]; //获取每个快递单对应的自定义打印项
            if(!empty($arr_self_elments)){
                $_value =  array_values($arr_self_elments['element']);
                $_key = array_keys($arr_self_elments['element']);
                $new_key = str_replace('+', '_', $_key[0]); //把原来键中的+号替换掉
                $new_self_elments[$new_key] = $_value[0];

                $elements = array_merge($elements,$new_self_elments); //合并系统打印项和用户自定义打印项
            }
			}
        } else {
            $template = array(
                'template_width' => ($_GET['type']=='normal') ? 240 :100,
                'template_height' => ($_GET['type']=='normal') ? 160: 150,
                'template_type' => $_GET['type'] ? $_GET['type'] : 'normal',
            );
            $this->pagedata['title'] = '新增模板';
        }

        if($template['file_id']){
            $bgUrl = $this->getImgUrl($template['file_id']);
            $this->pagedata['tmpl_bg'] = $bgUrl;
        }

        //$this->pagedata['tmpl_bg'] = 'http://img.tg.taoex.com/7ff271a2f9071043b751f18a08139ad6.jpg';
        $this->pagedata['tmpl'] = $template;
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['dpi'] = 96;
        $this->pagedata['elements'] = $elements;
        $this->pagedata['uniqid'] = uniqid();
        $this->pagedata['userAgent'] = $this->getUserAgent();
        //$this->pagedata['controllerTemplateObj'] = $this->_makeControllTemplateObj();
        $this->singlepage('admin/express/template.html');
    }

    /**
     * 保存模板
     *
     */
    function saveTmpl(){
        $data = array(
            'template_name' => $_POST['template_name'],
            'template_type' => $_POST['template_type'],
            'status' => $_POST['status'],
            'template_width' => $_POST['template_width'],
            'template_height' => $_POST['template_height'],
            'file_id' => $_POST['file_id'] ? $_POST['file_id'] : 0,
            'template_data' => $_POST['template_data'],
            'is_default' => isset($_POST['is_default']) ? $_POST['is_default'] : 'false',
            'page_type'=>isset($_POST['page_type']) ? $_POST['page_type'] : '1',
            'aloneBtn'=>isset($_POST['aloneBtn']) ?  $_POST['aloneBtn'] : 'false',
            'btnName'=>$_POST['btnName'],
        );
        if ($data['template_name'] == ''){
            switch ($data['template_type']) {
                case 'delivery':
                    $title = '请输入发货单名称';
                    break;
                case 'stock':
                    $titel = '请输入备货单名称';
                    break;
                default :
                    $titel = '请输入快递单名称';
                    break;
            }
            echo $titel;
            exit;
        }
        if (!in_array($data['template_type'],array('normal', 'electron', 'delivery', 'stock'))) {
            echo '面单类型不符合规则！';
            exit;
        }
        if((!$data['template_width'] || !$data['template_height']) && $data['file_id']>0){
            $bgUrl = $this->getImgUrl($data['file_id']);
            list($width, $height) = getimagesize($bgUrl);
            if($width && $height){
                $data['template_width'] = intval($width*25.4/96);
                $data['template_height'] = intval($height*25.4/96);
            }
        }

        $templateObj = &$this->app->model('express_template');
        if ($_POST['template_id']){
            $filter = array('template_id' => $_POST['template_id']);
            $re = $templateObj->update($data,$filter );
        }else {
            $re = $templateObj->insert($data);
        }
        //设置默认类型
        if (in_array($data['template_type'], array('delivery', 'stock')) && $data['is_default'] == 'true') {
            if ($_POST['template_id']) {
                $updateId = $_POST['template_id'];
            }
            else {
                $updateId = $re;
            }
            if ($updateId) {
                $filter = array('template_type' => $data['template_type'], 'template_id|noequal' => $updateId);
                $updateMark = array('is_default' => 'false');
                $re = $templateObj->update($updateMark, $filter );
            }
        }
        if ($re){
            echo "SUCC";
        }else {
            echo "保存失败";
        }
    }

    /**
     * 上传背景图片页面
     *
     * @param int $print_id
     */
    function uploadBg($print_id=0){
        $this->pagedata['dly_printer_id'] = $print_id;
        $this->display('admin/express/upload_bg.html');
    }

    function extName($file){
        return substr($file,strrpos($file,'.'));
    }

    /**
     * 处理上传的图片
     *
     */
    function doUploadBg(){
        $ss = kernel::single('base_storager');
        $extname = strtolower($this->extName($_FILES['background']['name']));
        if($extname=='.jpg' || $extname=='.jpeg'){
            $id = $ss->save_upload($_FILES['background'],"file","",$msg);//返回file_id;
        }else{
            echo "<script>parent.MessageBox.error('请上传JPG格式的图片');</script>";
            return false;
        }
        $this->doneUploadBg(basename($id));
        echo "<script>parent.MessageBox.success('上传完成');</script>";
    }

    /**
     * 更新背景图片的显示
     *
     * @param string $file
     */
    function doneUploadBg($file){
        if($file){
            $bgUrl = $this->getImgUrl($file);
            list($width, $height) = getimagesize($bgUrl);
            $pager_width = intval($width*25.4/96);
            $pager_height = intval($height*25.4/96);
            echo '<script>
                parent.$("bg_file_id").value = "'.$file.'";
                parent.$("template_width").value = "'.$pager_width.'";
                parent.$("template_height").value = "'.$pager_height.'";
                parent.embed1.setStyles({width:'.($width+30) .',height:'.($height+30).',});
                parent.embed1.setbackground("'.$bgUrl.'");
            </script>';
        }else{
            var_dump(__LINE__,$file);
        }
    }

    /**
     * 清除背景图片
     *
     * @param string $file
     */
    function deleteBg($file_id){
        if($file_id>0){
            $fileObj = app::get("base")->model("files");
            $file = $fileObj->dump(array('file_id'=>$file_id));
            if(is_array($file) && !empty($file)) {
                $storager = kernel::single('base_storager');
                $storager->remove($file['file_path'],'file');
            }
        }
        return true;
    }

    /**
     * 获取背景图片url
     *
     * @param string $file
     */
    function getImgUrl($file){
        $ss = kernel::single('base_storager');
        $url = $ss->getUrl($file,"file");

        return $url;
    }

    /**
     * 显示背景图片
     *
     * @param string $file
     */
    function showPicture($file){
        header('Content-Type: image/jpeg');
        $ss = kernel::single('base_storager');
        $a = $ss->getUrl($file,"file");
        
        // 当背景图片加载失败时，显示失败提示
        $file = readfile($a);
        if($file) {
            return $file;
        }else{
            $a = $this->app->res_dir.'/express_bg_error.jpg';
        return readfile($a);
        }
        //$dlyObj = &$this->app->model('delivery');
        //$dlyObj->sfile($a);//ROOT_DIR.'/app/ome/upload/tmp/'.$file);
    }

    function printTest(){
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['dpi'] = 96;

        if($_POST['tmp_bg']){
            $this->pagedata['bg_id'] = $_POST['tmp_bg'];
        }else if($_POST['prt_tmpl_id']){
            $printTmpl = $this->app->model('print_tmpl');
             
            $printTmpl = $printTmpl->dump($_POST['prt_tmpl_id'],'file_id');
            
            $this->pagedata['bg_id'] = $printTmpl['file_id'];
        }

        $this->display('admin/delivery/express_print_test.html');
    }

    #自定义模板
    function selfTmpl(){
        #模板id
        $prt_tmpl_id = $_GET['prt_tmpl_id'];
        $this->pagedata['prt_tmpl_id'] = $prt_tmpl_id;
        #获取用户自定义的打印项
        $_arr_self_elment = $this->app->getConf('logisticsmanager.delivery.express.template.selfElments');
        $arr_self_elment = $_arr_self_elment['element_'.$prt_tmpl_id];
        #自定义打印项的权重
        if(isset($arr_self_elment)){
            $key = array_keys($arr_self_elment['element']);
            $elments = explode('+', $key[0]);
            foreach($elments as $v){
                #获取打印项的权重
                if($v == 'n'){
                    $this->pagedata['n'] = 'true';
                }else{
                    $this->pagedata[$v] = $arr_self_elment['weight'][$v]['weight'];
                }
            }
        }
		$this->display('admin/express/dly_print_selftmp.html');
    }
    #保存自定义的打印方式
    function doSelfTmlp(){
        header("Content-type: text/html; charset=utf-8");
        #模板id
        $tmlp_id = 'element_'.$_POST['prt_tmpl_id'];
        $_weight = $_POST['dly'];
        $elments = $_POST['delivery'];
        $_elments = $elments;
        #自定义的打印项键
        unset($_elments['n']);
        #去除换行后，打印项数量在2-5个之间
        $_count = count($_elments);
        if($_count > 5){
            echo "<script>alert('最多不超过 5 个');</script>";exit;
        }
        if( $_count < 2){
            echo "<script>alert('至少选择 2 个');</script>";exit;
        }
        $self_elments = $this->selfPrintElments();
        $_elments = array_keys($_elments);
        $elment_key = array();
        $_i = 0;
        foreach($_elments as $v){
            if(strlen($_weight[$v]['weight'])<=0){
                echo "<script>alert('请为选中的打印项设置权重');</script>";exit;
            }
            #检测权重$_weight[$v]['weight']，是否重复
            if($_i > 0){
                if(array_key_exists($_weight[$v]['weight'], $elment_key)){
                    echo "<script>alert('请不要设置相同权重!');</script>";exit;
                }
            }
            $elment_key[$_weight[$v]['weight']] = $v;
            $elment_name[$_weight[$v]['weight']] = $self_elments[$v];
            $_i++;
        }
        #按键名倒序排序
        krsort($elment_key);
        krsort($elment_name);
        #检测前端原始提交的数据中，是否包含换行
        if($elments['n'] == 'true'){
            $elment_key['n'] = 'n';
            $elment_name['n'] = $self_elments['n'];
        }
        $new_elment_key =  implode('+',$elment_key);
        $new_elment_name =  implode('+',$elment_name);
        #本次设置的自定义打印项
        $arr_new_elments[$new_elment_key] = $new_elment_name;
        
        #获取用户所有自定义的打印项
        $selfElments=  $this->app->getConf('logisticsmanager.delivery.express.template.selfElments');
        $selfElments[$tmlp_id]['element'] = $arr_new_elments;
        $selfElments[$tmlp_id]['weight'] = $_weight;
        #保存自定义打印项
		
        $this->app->setConf('logisticsmanager.delivery.express.template.selfElments',$selfElments);
        echo "<script> parent.embed1.elments.close(); parent.history.go(0);</script>";
    }
    #自定义打印项键值对
    function selfPrintElments(){
        return array(
            'bn' => '货号',
            'pos' => '货位',
            'name' => '货品名称',
            'spec' => '规格',
            'amount' => '数量',
            'new_bn_name' => '商品名称',
            'goods_bn' => '商家编码',
            'goods_bn2' => '商品编号',//历史遗留问题,商家编码就是商品编号
            'n' => '换行'
        );
    }

    /**
     * 发货单
     */
    public function delivery() {
        $base_filter = array('template_type' => 'delivery');
        $params = array(
            'title'=>'发货面单管理',
            'actions' => array(
                array(
                    'label' => '新增发货面单',
                    'href' => 'index.php?app=logisticsmanager&ctl=admin_express_template&act=addDeliveryTmpl&type=delivery&finder_id='.$_GET['finder_id'],
                    'target' => "_blank",
                ),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'base_filter' => $base_filter
        );
        $this->finder('logisticsmanager_mdl_express_template', $params);
    }
    
    /**
     * 添加发货单面单
     * Enter description here ...
     */
    public function addDeliveryTmpl() {
        $this->_editTmplView(null, 'delivery');
    }

    /* 编辑发货单模板 */
    public function editDeliveryTmpl($template_id){
        $this->_editTmplView($template_id, 'delivery');
    }

    /**
     * 备货单
     * Enter description here ...
     */
    public function stock() {
        $base_filter = array('template_type' => 'stock');
        $params = array(
            'title'=>'备货面单管理',
            'actions' => array(
                array(
                    'label' => '备货发货面单',
                    'href' => 'index.php?app=logisticsmanager&ctl=admin_express_template&act=addStockTmpl&type=stock&finder_id='.$_GET['finder_id'],
                    'target' => "_blank",
                ),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'base_filter' => $base_filter
        );
        $this->finder('logisticsmanager_mdl_express_template', $params);
    }

    /**
     * 添加发货面单模板
     */
    public function addStockTmpl() {
        $this->_editTmplView(null, 'stock');
    }

    /**
     * 编辑备货面单模板
     * @param Int $template_id 模板ID
     */
    public function editStockTmpl($template_id) {
        $this->_editTmplView($template_id, 'stock');
    }
    
    /**
     * 编辑模板
     * 
     * @param Int $template_id 模板ID
     */
    private function _editTmplView($template_id = null, $type = '') {
        $templateObj = &$this->app->model('express_template');
        $type = isset($_GET['type']) ? trim($_GET['type']) : $type;
        $elements = $templateObj->getElementsItem($type);
        if ($template_id) {
            $template = $templateObj->dump($template_id);
            $this->pagedata['title'] = '编辑模板';
            if (in_array($type,array('copydelivery','copystock'))) {
                if ($type == 'copydelivery') {
                    
                    $this->pagedata['title'] = '复制发货单模板';
                }else if($type == 'copystock'){
                    $this->pagedata['title'] = '复制备货单模板';
                }
                
                $template['template_name'] = '复制'.$template['template_name'];
                unset($template['template_id']);
            }
            

            //获取用户自定义的打印项
            $_arr_self_elments = $this->app->getConf('ome.delivery.print.'.$type.'.selfElments');
            $arr_self_elments = $_arr_self_elments['element_'.$template_id]; //获取每个快递单对应的自定义打印项
            if (!empty($arr_self_elments)) {
                $_value =  array_values($arr_self_elments['element']);
                $_key = array_keys($arr_self_elments['element']);
                $new_key = str_replace('+', '_', $_key[0]); //把原来键中的+号替换掉
                $new_self_elments[$new_key] = $_value[0];
                $elements = array_merge($elements, $new_self_elments); //合并系统打印项和用户自定义打印项
            }
        }
        else {
            switch ($type) {
                case 'stock':
                    $templateWidth = 240;
                    $templateHeight = 160;
                    $templateType = 'stock';
                    $title = '新增备货面单模板';
                    break;
                case 'delivery':
                    $templateWidth = 240;
                    $templateHeight = 160;
                    $templateType = 'delivery';
                    $title = '新增发货面单模板';
                    break;
                
                default :
                    $templateWidth = 240;
                    $templateHeight = 160;
                    $templateType = 'delivery';
                    $title = '新增发货面单模板';
                    break;
            }
            $template = array(
                'template_width' => $templateWidth,
                'template_height' => $templateHeight,
                'template_type' => $templateType
            );
            $this->pagedata['title'] = $title;
        }
        
        $html = 'admin/' . $type . '/template.html';
        //如果存在背景图
        if ($template['file_id']) {
            $bgUrl = $this->getImgUrl($template['file_id']);
            $this->pagedata['tmpl_bg'] = $bgUrl;
        }
        $this->pagedata['tmpl'] = $template;
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['dpi'] = 96;
        $this->pagedata['elements'] = $elements;
        $this->pagedata['uniqid'] = uniqid();
        $this->pagedata['userAgent'] = $this->getUserAgent();
        $this->singlepage($html);
    }

    /**
     * 获得模板字段类型
     * @param String $type 模板类型
     */
    public function getTemplateFields($type = '', $display = true) {
        if (empty($type)) {
            $type = $_POST['type'];
        }
        $data = array();
        switch ($type) {
            case 'delivery':
                $data = array(
                    'grid_name' => 'delivery_items',
                    'field_array' => array(
                        'name' => '商品名称',
                        'bn' => '货号',
                        'number' => '数量',
                        'price' => '单价',
                        'sale_price' => '实收金额',
                        'spec_info'=>'规格',
                        'pmt_price'=>'优惠价',
                        'goods_bn'=>'商品货号',
                        'product_weight'=>'货品重量',
                        'unit'=>'单位',
                        'brand_name'=>'品牌',
                        'type_name'=>'商品类型',
                        'store_position'=>'货位',
                        'print_number'=>'序号',  
                     ),
                     'countDeliveryMsg' => array(
                         'total' => '总计',
                         'empty' => '空',
                     ),

                );
                break;
            case 'stock':
                $data = array(
                    'grid_name' => 'stock_items',
                    'field_array' => array(
                        'bn' => '货号',
                        'store_position' => '货位',
                        'name' => '商品名称',
                        'spec_info' => '商品规格',
                        'num' => '商品数量',
                        'box_price' => '合计金额',
                        'box' => '盒子号',
                        'product_weight' => '货品重量',
                        'barcode' => '条形码号',
                    ),
                    'countDeliveryMsg' => array(
                         'num_total' => '数量总计',
                         'num_money_total' => '数量金额总计',
                         'empty' => '空',
                     ),
                );
                break;
        }
        if ($display) {
            echo json_encode($data);
            exit;
        }
        else {
            return $data;
        }
    }
    /**
     * 获得浏览器版本
     */
    public function getUserAgent() {
        $agent = $_SERVER["HTTP_USER_AGENT"];
        $brower = array('brower' => 'Other', 'ver' => '0', 'type' => 2);
    
        if (strpos($agent, "MSIE 10.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '10.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 9.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '9.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 8.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '8.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 7.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '7.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 6.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '6.0', 'type' => 1);
        }
        elseif (strpos($agent, "Trident")) {
            //IE11以后的版本
            $str = substr($agent, strpos($agent, 'rv:') + strlen('rv:'));
            $ver = substr($str, 0, strpos($str, ')'));
            $brower = array('brower' => 'Ie', 'ver' => $ver, 'type' => 1);
        }
        elseif (strpos($agent, "Chrome")) {
            $str = substr($agent, strpos($agent, 'Chrome/') + strlen('Chrome/'));
            $verInfo = explode(" ", $str);
            $brower = array('brower' => 'Chrome', 'ver' => $verInfo[0], 'type' => 2);
        }
        elseif (strpos($agent, "Firefox")) {
            $str = substr($agent, strpos($agent, 'Firefox/') + strlen('Firefox/'));
            $brower = array('brower' => 'Firefox', 'ver' => $str, 'type' => 2);
        }
        return $brower;
    }
    
    protected function _makeControllTemplateObj() {
      $style = 'style="border:3px #ccc solid;';
      if ($this->pagedata['tmpl']['template_width'] && $this->pagedata['tmpl']['template_height']) {
          $style .= 'width:' . ($this->pagedata['tmpl']['template_width'] * 96/25.4+30) . 'px;';
          $style .= 'height:' . ($this->pagedata['tmpl']['template_height']*96/25.4+30) .'px;';
      }
      else {
          $style .= 'width:910px;height:510px;';
      }

      $brower = $this->getUserAgent();
      $templateObj = '';
      if ($brower['type'] == 2) {
          $templateObj .= '<embed id="embed1' . $this->pagedata['uniqid'] . '" type="application/ShopexReport-plugin" ' . $style . '">';
      }
      else {
          $templateObj .= '<OBJECT id="embed1' . $this->pagedata['uniqid'] . '" CLASSID="CLSID:54B240AC-6979-42BE-8D80-8672CFDC0E8A" ' . $style . '"></OBJECT>';
      }
      return $templateObj;
    }
    
    
    /**
     * 复制发货单模板
     * @param
     * @return
     * @access  public
     * @author sunjing@shopex.cn
     */
    function copyDeliveryTmpl($template_id)
    {
        $this->_copyTmplView($template_id, 'copydelivery');
    }

    /**
     * 复制备货单模板
     * @param
     * @return
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function copyStockTmpl($template_id) {
        $this->_copyTmplView($template_id, 'copystock');
    }

    /**
     * 复制快递单模板
     * @param
     * @return
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function copyTmpl($template_id){
        $templateObj = &$this->app->model('express_template');
        $elements = $templateObj->getElements();
        if($template_id){
            $template = $templateObj->dump($template_id);
            $template['template_name'] = '复制'.$template['template_name'];
            $this->pagedata['title'] = '复制模板';

            //获取用户自定义的打印项
            $_arr_self_elments = $this->app->getConf('ome.delivery.print.selfElments');
            $arr_self_elments = $_arr_self_elments['element_'.$template_id]; //获取每个快递单对应的自定义打印项
            if(!empty($arr_self_elments)){
                $_value =  array_values($arr_self_elments['element']);
                $_key = array_keys($arr_self_elments['element']);
                $new_key = str_replace('+', '_', $_key[0]); //把原来键中的+号替换掉
                $new_self_elments[$new_key] = $_value[0];

                $elements = array_merge($elements,$new_self_elments); //合并系统打印项和用户自定义打印项
            }
        }

        if($template['file_id']){
            $bgUrl = $this->getImgUrl($template['file_id']);
            $this->pagedata['tmpl_bg'] = $bgUrl;
        }

        //$this->pagedata['tmpl_bg'] = 'http://img.tg.taoex.com/7ff271a2f9071043b751f18a08139ad6.jpg';
        unset($template['template_id']);
        $this->pagedata['tmpl'] = $template;
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['dpi'] = 96;
        $this->pagedata['elements'] = $elements;
        $this->pagedata['uniqid'] = uniqid();
        $this->pagedata['userAgent'] = $this->getUserAgent();
        //$this->pagedata['controllerTemplateObj'] = $this->_makeControllTemplateObj();
        $this->singlepage('admin/express/template.html');
    }

    private function _copyTmplView($template_id = null, $type = '') {
        $templateObj = &$this->app->model('express_template');
        $type = $type == 'copyStockTmpl' ? 'stock' : 'delivery' ;
        
        $elements = $templateObj->getElementsItem($type);
        if ($template_id) {
            $template = $templateObj->dump($template_id);
            $this->pagedata['title'] = '编辑模板';
            if (in_array($type,array('stock','delivery'))) {
                if ($type == 'delivery') {
                    
                    $this->pagedata['title'] = '复制发货单模板';
                }else if($type == 'stock'){
                    $this->pagedata['title'] = '复制备货单模板';
                }
                
                $template['template_name'] = '复制'.$template['template_name'];
                unset($template['template_id']);
            }
            

            //获取用户自定义的打印项
            $_arr_self_elments = $this->app->getConf('ome.delivery.print.'.$type.'.selfElments');
            $arr_self_elments = $_arr_self_elments['element_'.$template_id]; //获取每个快递单对应的自定义打印项
            if (!empty($arr_self_elments)) {
                $_value =  array_values($arr_self_elments['element']);
                $_key = array_keys($arr_self_elments['element']);
                $new_key = str_replace('+', '_', $_key[0]); //把原来键中的+号替换掉
                $new_self_elments[$new_key] = $_value[0];
                $elements = array_merge($elements, $new_self_elments); //合并系统打印项和用户自定义打印项
            }
        }
        $html = 'admin/' . $type . '/template.html';
        //如果存在背景图
        if ($template['file_id']) {
            $bgUrl = $this->getImgUrl($template['file_id']);
            $this->pagedata['tmpl_bg'] = $bgUrl;
        }
        
        $this->pagedata['tmpl'] = $template;
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['dpi'] = 96;
        $this->pagedata['elements'] = $elements;
        $this->pagedata['uniqid'] = uniqid();
        $this->pagedata['userAgent'] = $this->getUserAgent();
        $this->singlepage($html);
    }

    
    /**
     * 导入普通面单模板
     * @param 
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function importnormalTmpl()
    {
        $this->display('admin/delivery/dly_print_import.html');
    }

    
    /**
     * 上传普通面单.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function doUploadnormal()
    {
        header("content-type:text/html; charset=utf-8");
        $file = $_FILES['package'];
        
        $msg = kernel::single('logisticsmanager_print_tmpl')->upload_tmpl($file);
        
        if ($msg=='success'){
            $result = true;
            $msg = "上传完成";
        }
        else{
            $result = false;
        }
        echo "<script>";
        if ($result){
            echo "parent.MessageBox.success('".$msg."');";
            echo "var fg = parent.finderGroup;";
            echo "for(fid in fg){";
            echo "if(fid){";
            echo "fg[fid].refresh(); ";
            echo "}";
            echo "}";
            echo "parent.$$('.dialog').getLast().retrieve('instance').close();";
                     
        }else{
            echo "parent.MessageBox.error('".$msg."');";
        }
        echo "</script>";
        exit;
    }

    function downloadTmpl($tmpl_id){
        $printObj = &$this->app->model('express_template');
        $tmpl = $printObj->dump($tmpl_id,'template_name,template_type,template_width,template_height,file_id,template_data');
        //$tar = &$this->app->model('utility_tar');//修改加载方式
        $tar = kernel::single('ome_utility_tar');
        $tar->addFile('info',serialize($tmpl));
        if($tmpl['file_id']){
            $ss = kernel::single('base_storager');
            $a = $ss->getUrl($tmpl['file_id'],"file");//echo file_get_contents($a);die;
            $tar->addFile('background.jpg',file_get_contents($a));
        }
        //$this->system->session->close();
        //$charset = &$this->app->model('utility_charset');//修改加载方式
        //$charset = kernel::single('ome_utility_charset');
        $charset = kernel::single('base_charset');
        $name = $charset->utf2local($tmpl['template_name'],'zh');
        @set_time_limit(0);
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header('Content-type: application/octet-stream');
        header('Content-type: application/force-download');
        header('Content-Disposition: attachment; filename="'.$name.'.dtp"');
        $tar->getTar('output');
    }
}
?>
