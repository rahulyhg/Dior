<?php
class db_ex{
        
    function &_rw_conn(){
        $this->_rw_lnk = &$this->_connect();
        return $this->_rw_lnk;
    }
    
    function &_connect(){
        $lnk = mysql_connect(PUB_DB_HOST,PUB_DB_USER,PUB_DB_PASSWORD,true);
        
        if(!$lnk){
            trigger_error(__('无法连接数据库:').mysql_error(),E_USER_ERROR);
        }
        
        mysql_select_db( PUB_DB_NAME, $lnk );
        
        mysql_query('SET NAMES \''.PUB_DB_CHARSET.'\'',$lnk);
        
        return $lnk;
    }
    
    function &exec($sql , $skipModifiedMark = false,$db_lnk=null){

        if(!is_resource($db_lnk)){
            if(isset($this->_rw_lnk)){
                $db_lnk = &$this->_rw_lnk;
            }else{
                $db_lnk = &$this->_rw_conn();
            }
        }

        if($rs = mysql_query($sql,$db_lnk)){
            $db_result = array('rs'=>&$rs,'sql'=>$sql);
            return $db_result;
        }else{
            trigger_error($sql.':'.mysql_error($db_lnk),E_USER_WARNING);
            return false;
        }
    }
    
    function &select($sql){
        if(isset($this->_rw_lnk)){
            $db_lnk = &$this->_rw_lnk;
        }else{
            $db_lnk = &$this->_rw_conn();
        }

        $rs = $this->exec($sql,$db_lnk);
        $data = array();
        while($row = mysql_fetch_assoc($rs['rs'])){
            $data[]=$row;
        }
        mysql_free_result($rs['rs']);
        return $data;
    }

    function &selectrow($sql){
        $row = &$this->selectlimit($sql,1,0);
        return $row[0];
    }

    function &selectlimit($sql,$limit=10,$offset=0){
        if ($offset >= 0 || $limit >= 0){
            $offset = ($offset >= 0) ? $offset . "," : '';
            $limit = ($limit >= 0) ? $limit : '18446744073709551615';
            $sql .= ' LIMIT ' . $offset . ' ' . $limit;
        }
        $data = &$this->select($sql);
        return $data;
    }

}

