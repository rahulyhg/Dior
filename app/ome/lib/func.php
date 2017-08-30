<?php

/**
 * OME公共函数库
 * @copyright Copyright (c) 2010, shopex. inc
 * @author dongdong
 *
 */
class ome_func
{
    /**
     * 地区字符串格式验证
     * 正则匹配地区是否为本系统的标准地区格式，标准格式原样返回，非标准格式试图转换标准格式返回，否则
     * @access static
     * @param string $area 待验证地区字符串
     * @return string 转换后的本系统标准格式地区
     */
    public function region_validate(&$area){
        $is_correct_area = $this->is_correct_region($area);
        if (!$is_correct_area){
            //非标准格式进行转换
            $this->local_region($area);
        }
    }

    /**
     * ECOS本地标准地区格式判断
     * @access public
     * @param string $area 地区字符串，如：malind:上海/徐汇区:22
     * @return boolean
     */
    public function is_correct_region($area){
        $pattrn = "/^([a-zA-Z]+)\:(\S+)\:(\d+)$/";
        if (preg_match($pattrn, $area)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 本系统标准地区格式转换
     * 正则匹配地区是否为本系统的标准地区格式，转换成功返回标准地区格式，转换失败原地区字符串返回
     * @access static
     * @param string $area 待转换地区字符串
     * @return string  转换后的本系统标准格式地区
    */
    public function local_region(&$area){

        $tmp_area = explode("/",$area);
        //地区初始值临时存储
        $ini_first_name = trim($tmp_area[0]);
        $ini_second_name = trim($tmp_area[1]);
        $ini_third_name = trim($tmp_area[2]);

        $tmp_area2 = preg_replace("/省|市|县|区/","",$tmp_area);
        $first_name = trim($tmp_area2[0]);
        //自治区兼容
        $tmp_first_name = $this->area_format($first_name);
        if ($tmp_first_name) $first_name = $tmp_first_name;
        $second_name = trim($tmp_area2[1]);
        $third_name = trim($tmp_area2[2]);
        $regionObj = &app::get('eccommon')->model('regions');

        $region_first = $region_second = $region_third = "";
        if ($first_name){
            //获取省------region_id
            //TODO：针对北京省份数据存在BOM头进行兼容
            if (strstr($first_name, '北京')){
                $bom_first_name = chr(239).chr(187).chr(191).$first_name;
                $region_first = $regionObj->dump(array('local_name|head'=>$bom_first_name,'region_grade'=>'1'), 'package,region_id,local_name');
                if (empty($region_first)){
                  $region_first = $regionObj->dump(array('local_name|head'=>$first_name,'region_grade'=>'1'), 'package,region_id,local_name');
                }
            }else{
                $region_first = $regionObj->dump(array('local_name|head'=>$first_name,'region_grade'=>'1'), 'package,region_id,local_name');
            }
            $first_name = $region_first['local_name'];
            
            if (!$first_name){
                $region_first = array(
                    'local_name' =>$ini_first_name,
                    'package' =>'mainland',
                    'region_grade' =>'1',
                );
                $regionObj->save($region_first);
                $first_name = $region_first['local_name'];
                $region_path = ",".$region_first['region_id'].",";
                //更新region_path字段
                $regionObj->update(array('region_path'=>$region_path), array('region_id'=>$region_first['region_id']));
            }
            
        }
        
        if ($second_name){//获取市------region_id
            //精确查找
            $second_filter = array('local_name'=>trim($tmp_area[1]),'region_grade'=>'2','p_region_id'=>$region_first['region_id']);
            $region_second = $regionObj->dump($second_filter, 'package,region_id,p_region_id,local_name');
            
            if (empty($region_second['local_name'])){
                //模糊查找
                $second_filter = array('local_name|head'=>$second_name,'region_grade'=>'2','p_region_id'=>$region_first['region_id']);
                $region_second = $regionObj->dump($second_filter, 'package,region_id,p_region_id,local_name');
            }
            $second_name = $region_second['local_name'];
            if (!$second_name){
                $region_second = array(
                    'local_name' =>$ini_second_name,
                    'p_region_id' =>$region_first['region_id'],
                    'package' =>'mainland',
                    'region_grade' =>'2',
                );
                $regionObj->save($region_second);
                $second_name = $region_second['local_name'];
                $region_path = ",".$region_first['region_id'].",".$region_second['region_id'].",";
                //更新region_path字段
                $regionObj->update(array('region_path'=>$region_path), array('region_id'=>$region_second['region_id']));

                //添加二级地区后更新一级地区的
                $regionObj->update(array('haschild'=>1), array('region_id'=>$region_first['region_id']));
            }
        }
        if ($third_name){
            //获取县region_id
            if (!$region_second['region_id']){
                
                //先根据第三级查出所有第二级
                $filter = array('local_name|head'=>$third_name);
                
                $regions = $regionObj->getList('p_region_id', $filter, 0, -1);
                
                if ($regions){
                    foreach ($regions as $k=>$v){
                        $region_second_tmp = $regionObj->dump(array('region_id'=>$v['p_region_id'],'region_grade'=>'2'), 'region_path,package,region_id,p_region_id,local_name');
                        
                        $tmp = explode(",",$region_second_tmp['region_path']);
                        if (in_array($region_first['region_id'],$tmp)){
                            $region_second = $region_second_tmp;
                            $second_name = $region_second['local_name'];
                            break;
                        }
                    }
                }
            }
            
            //精确查找
            $third_filter = array('local_name'=>trim($tmp_area[2]),'region_grade'=>'3','p_region_id'=>$region_second['region_id']);
            $region_third = $regionObj->dump($third_filter, 'package,region_id,p_region_id,local_name');
            if (empty($region_third['local_name'])){
                //模糊查找
                $third_filter = array('local_name|head'=>$third_name,'region_grade'=>'3','p_region_id'=>$region_second['region_id']);
                
                $region_third = $regionObj->dump($third_filter, 'package,region_id,p_region_id,local_name');
            }
            $third_name = $region_third['local_name'];
            if (!$third_name){
                if ($region_second['region_id']) {
                    
                
                    $region_third = array(
                        'local_name' =>$ini_third_name,
                        'p_region_id' =>$region_second['region_id'],
                        'package' =>'mainland',
                        'region_grade' =>'3',
                    );

                    $regionObj->save($region_third);
                
                    $third_name = $region_third['local_name'];
                    $region_path = ",".$region_first['region_id'].",".$region_second['region_id'].",".$region_third['region_id'].",";
                    //更新region_path字段
                    $regionObj->update(array('region_path'=>$region_path), array('region_id'=>$region_third['region_id']));

                    //添加三级地区后更新二级地区的
                    $regionObj->update(array('haschild'=>1), array('region_id'=>$region_second['region_id']));
                }else{
                    
                    $region_third = $regionObj->dump(array('local_name|head'=>$tmp_area[2],'p_region_id'=>$region_first['region_id']), 'package,region_id,p_region_id,local_name');
                    if ($region_third) {
                        $third_name = $tmp_area[2];
                    }
                    
                }
            }
        }
        $return = false;
        
        if ($region_third['region_id']){
            $region_id = $region_third['region_id'];
            $package = $region_third['package'];
        }elseif ($region_second['region_id']){
            $region_id = $region_second['region_id'];
            $package = $region_second['package'];
        }
        $region_area = array_filter(array($first_name,$second_name,$third_name));
        $region_area = implode("/", $region_area);
      
        if ($region_area || $region_id){
            $area = $package.":".$region_area.":".$region_id;
            $return = true;
        }

        //去除多余分隔符“/”
        if ($return==false){
            $area = implode("/", array_filter($tmp_area));
        }

    }

    /**
     * 前端店铺三级地区本地临时转换
     * @param $area
     */
    public function area_format($area){
        $area_format = array(
            '内蒙古自治' => '内蒙古',
            '广西壮族自治' => '广西',
            '西藏自治' => '西藏',
            '宁夏回族自治' => '宁夏',
            '新疆维吾尔自治' => '新疆',
            '香港特别行政' => '香港',
            '澳门特别行政' => '澳门',
        );
        if ($area_format[$area]){
            return $area_format[$area];
        }else{
            return false;
        }
    }

    /**
     * 拆分标准格式为：省市县
     * @param string $area
     * @return array 下标从0开始，依次代表：省、市、县
     */
    public function split_area(&$area){
       preg_match("/:(.*):/", $area,$tmp_area);
       if($tmp_area[1]){
           $tmp_area = explode('/', $tmp_area[1]);
           $area = $tmp_area;
       }
    }   /**
     * 数组转换字符串
     * 支持多维数组
     * @access public
     * @param array $data
     * @return string
     */
    static function array2string($data){
        if (!is_array($data)) return null;
        ksort($data, SORT_REGULAR);
        $string = '';
        if ($data)
        foreach ((array)$data as $k=>$v){
            $string .= $k . (is_array($v) ? self::array2string($v) : $v);
        }
        return $string;
    }

    /**
     * 日期型转换时间戳
     * @access public
     * @param $string $date_time 日期字符串或时间戳
     * @return 时间戳
     */
    public function date2time($date_time){
        if (strstr($date_time,'-')){
            return strtotime($date_time);
        }else{
            return $date_time;
        }
    }

    /**
     * 输出订单备注与留言
     * @param string $memo 备注与留言内容：序列化数组
     * serail(array(0=>array('op_name'=>'2','op_time'=>'12342'),1=>array);
     * @return array 标准可直接读取的数组
     */
    public function format_memo($memo){
        if (empty($memo)) return NULL;
        $mark = array();
        if ( !is_array($memo) ){
            $mark = unserialize($memo);
        }
        foreach ((array)$mark as $k=>$v){
            if (!strstr($v['op_time'], "-")){
                $v['op_time'] = date('Y-m-d H:i:s',$v['op_time']);
                $mark[$k]['op_time'] = $v['op_time'];
            }
        }
        return $mark ? $mark : $memo;
    }

    /**
     * 追加订单备注与留言
     * $new_memo待追加的订单备注类型可为数组或字符串:
     * 数组：array('op_name'=>'操作员姓名','op_content'=>'备注内容','op_time'=>'操作时间')
     * 字符串：则op_content为字符串内容,op_name为当前登录用户的姓名,op_time为当前系统操作时间
     * @access public
     * @param mixed $new_memo 待追加的订单备注(类型可为数组或字符串)
     * @param mixed $old_memo 订单备注/留言原始数据
     * @return Serialize 数组
     */
    public function append_memo($new_memo,$old_memo=''){
        if ( empty($new_memo) ) return NULL;
        $append_memo = array();
        $memo = array();
        $op_name = kernel::single('desktop_user')->get_name();
        $time = time();
        //待追加的内容
        if ( is_array($new_memo) ){
            $op_name = $new_memo['op_name'] ? $new_memo['op_name'] : $op_name;
            $op_content = $new_memo['op_content'];
            $op_time = $this->date2time($new_memo['op_time']);
        }else{
            $op_content = $new_memo;
            $op_time = $time;
        }
        $append_memo = array(
            'op_name' => $op_name,
            'op_time' => $op_time,
            'op_content' => $op_content,
        );
        //订单备注/留言原始数据
        if ($old_memo){
            if ( !is_array($old_memo) ){
                $old_memo = unserialize($old_memo);
            }
            foreach($old_memo as $k=>$v){
                $memo[] = $v;
            }
        }
        $memo[] = $append_memo;
        return $memo;
    }

    /**
     * 计算两个时间的差值转换到日期
     *
     * @param $time1
     * @param $time2
     * @return array
     */
    public function toTimeDiff($time1,$time2){
        $arr_time_diff = array('d'=>0,'h'=>0,'m'=>0,'i'=>0);
        $time_diff = $time1 - $time2;
        $k = 86400;
        $arr_time_diff['d'] = intval($time_diff / $k);
        $time_diff = $time_diff % $k;
        $k = $k/24;
        $arr_time_diff['h'] = intval($time_diff/$k);
        $time_diff = $time_diff % $k;
        $k = $k/60;
        $arr_time_diff['m'] = intval($time_diff/$k);
        $arr_time_diff['i'] = intval($time_diff%$k);

        return $arr_time_diff;
    }

    /**
     * 得到后台登录的管理员信息
     *
     * return array
     */
    public function getDesktopUser(){
        $opInfo['op_id'] = kernel::single('desktop_user')->get_id();
        $opInfo['op_name'] = kernel::single('desktop_user')->get_name();

        if(empty($opInfo['op_id'])){
            $opInfo = $this->get_system();
        }
        return $opInfo;
    }
    /**
     * 获取system账号信息，写死。
     */
    public function get_system(){
        $opInfo = array(
            'op_id' => 16777215,
            'op_name' => 'system'
        );
        return $opInfo;
    }

    /**
     * 去除字符BOM头
     * @param array or string $data 字符或数组
     * @return array or string 非BOM头字符串
     */
    static public function strip_bom($data=NULL){
        if (empty($data)) return NULL;
        if(is_array($data)){
            foreach($data as $k=>$v){
                $charset[1] = substr($v, 0, 1);
                $charset[2] = substr($v, 1, 1);
                $charset[3] = substr($v, 2, 1);
                if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
                    $data[$k] = substr($v, 3);
                }
            }
        }
        else{
            $charset[1] = substr($data, 0, 1);
            $charset[2] = substr($data, 1, 1);
            $charset[3] = substr($data, 2, 1);
            if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
                $data = substr($data, 3);
            }
        }
        return $data;
    }

    /**
     * 修正菜单用
     *
     */
    public function disable_menu($type=''){
        if(empty($type)){
            $type = 'all';
        }

        switch($type){
            case 'ectools':
                $this->_disabe_menu_ectools();
                break;
            case 'image':
                $this->_disable_menu_image();
                break;
            case 'desktop':
                $this->_disable_menu_desktop();
                break;
            case 'all':
                $this->_disabe_menu_ectools();
                $this->_disable_menu_image();
                $this->_disable_menu_desktop();
                break;
        }
    }

    private function _disabe_menu_ectools(){
        kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='true',display='false' WHERE workground='ectools.wrokground.order' AND app_id='ectools'");
        kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='true',display='false' WHERE menu_type='permission' AND app_id='ectools' AND permission<>'regions'");
        kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='true',display='false' WHERE menu_type='panelgroup' AND menu_title='支付与货币'");
        kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='true',display='false' WHERE menu_type='adminpanel' AND menu_path IN ('app=ectools&ctl=currency&act=index','app=ectools&ctl=payment_cfgs&act=index','app=ectools&ctl=setting&act=index','app=ectools&ctl=admin_payment_notice&act=index')");
    }

    private function _disable_menu_image(){
        kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='true',display='false' WHERE menu_type='workground' AND app_id='image'");
        kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='true',display='false' WHERE menu_type='permission' AND app_id='image'");
        kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='true',display='false' WHERE menu_type='panelgroup' AND menu_title='图片管理'");
        kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='true',display='false' WHERE menu_type='adminpanel' AND menu_path IN ('app=image&ctl=admin_manage&act=index','app=image&ctl=admin_manage&act=imageset')");
    }

    private function _disable_menu_desktop(){
        kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='false',display='true' WHERE menu_type='permission' AND app_id='desktop' AND permission='performance'");
        kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='false',display='true' WHERE menu_type='permission' AND app_id='desktop' AND permission='setting'");
    }
    //---------------修正菜单结束---------------//

    /**
     * 获取insert sql语句
     * @access static public
     * @param Object $model model对象
     * @param Array $data 需插入的关联(字段)数组数据,支持多维
     * @return String insert sql语句
     */
    static public function get_insert_sql($model,$data){
        if (empty($model) || empty($data)) return NULL;

        $cols = $model->_columns();
        $strValue = $insert_data = $column_type = array();
        $strFields = '';

        $rs = $model->db->exec('select * from `'.$model->table_name(1).'` where 0=1');
        $col_count = mysql_num_fields($rs['rs']);

        $tmp_data = $data;
        if (!is_array(array_pop($tmp_data))){
            $insert_data[] = $data;
        }else{
            $insert_data = $data;
        }
        unset($tmp_data);

        foreach ($insert_data as $key=>$value){
            $insertValues = array();
            if (!empty($strFields)){
                $col_count = count($strFields);
            }
            for($i=0;$i<$col_count;$i++) {
                if (empty($strFields)){
                    $column = mysql_fetch_field($rs['rs'],$i);
                    $k = $column->name;
                    $column_type[$k] = $column->type;
                    if( !isset($value[$k]) ){
                        continue;
                    }
                }else{
                    $k = $strFields[$i];
                }
                $p = $cols[$k];

                if(!isset($p['default']) && $p['required'] && $p['extra']!='auto_increment'){
                    if(!isset($value[$k])){
                        trigger_error(($p['label']?$p['label']:$k).app::get('base')->_('不能为空！'),E_USER_ERROR);
                    }
                }

                if( $value[$k] !== false ){
                    if( $p['type'] == 'last_modify' ){
                        $insertValues[$k] = time();
                    }elseif( $p['depend_col'] ){
                        $dependColVal = explode(':',$p['depend_col']);
                        if( $value[$dependColVal[0]] == $dependColVal[1] ){
                            switch( $dependColVal[2] ){
                                case 'now':
                                    $insertValues[$k] = time();
                                    break;
                            }
                        }
                    }
                }

                if( $p['type']=='serialize' ){
                    $value[$k] = serialize($value[$k]);
                }
                if( !isset($value[$k]) && $p['required'] && isset($p['default']) ){
                    $value[$k] = $p['default'];
                }
                $insertValues[$k] = base_db_tools::quotevalue($model->db,$value[$k],$column_type[$k]);
            }
            if (empty($strFields)){
                $strFields = array_keys($insertValues);
            }
            $strValue[] = "(".implode(',',$insertValues).")";
        }

        $strFields = implode('`,`', $strFields);
        $strValue = implode(',', $strValue);
        $sql = 'INSERT INTO `'.$model->table_name(true).'` ( `'.$strFields.'` ) VALUES '.$strValue;

        return $sql;
    }

    public function getApiResponse($data){
        $return = array(
                'rsp'=>'succ',
                'data'=>$data
                );

        return $return;    }

     public function getErrorApiResponse($data){
        $return = array(
                'rsp'=>'fail',
                'res'=>$data
            );

        return $return;    }

    static function class_exists($class_name)
    {
        $p = strpos($class_name,'_');

        if($p){
            $owner = substr($class_name,0,$p);
            $class_name = substr($class_name,$p+1);
            $tick = substr($class_name,0,4);
            if(defined('CUSTOM_CORE_DIR') && file_exists(CUSTOM_CORE_DIR.'/'.$owner.'/lib/'.str_replace('_','/',$class_name).'.php')){
                $path = CUSTOM_CORE_DIR.'/'.$owner.'/lib/'.str_replace('_','/',$class_name).'.php';
            }else{
                $path = APP_DIR.'/'.$owner.'/lib/'.str_replace('_','/',$class_name).'.php';
            }
            if(file_exists($path)){
                return true;
            }else{
                return false;
            }
        }
    }


    /**
     * 判断是否已到达设定的时间点
     * @param clock int 设置的时间点
     * @param msg int 错误信息
     * @return boolean
     **/
    function isRunTime($clock,&$msg = ''){
       $server_time = date('H:i');
       if($server_time == $clock){
          return true;
       }else{
          $msg = 'time is passed';
          return false;
       }
    }
    /**
     * @param params array 需要运算的数据，数组、数值等
     * @param operator string 操作类型 + , - , * , /
     * @param digit int 数值精度
     * @return float
     **/
    static function number_math($params = array(),$operator = '',$digit = 2){
       $mathObj = kernel::single('eccommon_math');
       $mathObj->goodsShowDecimals = $digit;
       $mathObj->operationDecimals = $digit;

       switch($operator){
           case '+':
               $action = 'number_plus';
           break;
           case '-':
               $action = 'number_minus';
           break;
           case '*':
               $action = 'number_multiple';
           break;
           case '/':
               $action = 'number_div';
           break;
           default:
               $action = false;
           break;
       }

       if($action === false){
          return false;
       }else{
          return $mathObj->$action($params);
       }
    }}