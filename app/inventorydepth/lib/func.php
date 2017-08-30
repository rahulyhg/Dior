<?php

/**
 * OME公共函数库
 * @copyright Copyright (c) 2010, shopex. inc
 * @author dongdong
 * 
 */
class inventorydepth_func
{
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
 
    /**
     * 获取REPLACE sql语句
     * @access static public
     * @param Object $model model对象
     * @param Array $data 需插入的关联(字段)数组数据,支持多维
     * @return String insert sql语句
     */
    static public function get_replace_sql($model,$data){
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
        $sql = 'REPLACE INTO `'.$model->table_name(true).'` ( `'.$strFields.'` ) VALUES '.$strValue;
        
        return $sql;
    }

    public function getDesktopUser(){
        $opInfo['op_id'] = kernel::single('desktop_user')->get_id();
        $opInfo['op_name'] = kernel::single('desktop_user')->get_name();
        $opInfo['login_name']       = kernel::single('desktop_user')->get_login_name();
        
        if(empty($opInfo['op_id'])){
            $opInfo = $this->get_system();
        }
        return $opInfo;
    }

    public function get_system(){
        $opInfo = array(
            'op_id' => 16777215,
            'op_name' => 'system',
            'login_name' => 'system',
        );
        return $opInfo;
    }
}