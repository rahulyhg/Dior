<?php
class ome_goods_list{
    function goods_list($cols='*',$filter=array(),$start=0,$limit=-1,$orderType=null , &$object){

        $ident=md5($cols.var_export($filter,true).$start.$limit);
        if(!$object->_dbstorage[$ident]){
            if(!$cols){
                $cols = $object->defaultCols;
            }
            if($object->appendCols){
                $cols.=','.$object->appendCols;
            }
            $sql = 'SELECT '.$cols.' FROM '.$object->table_name(true).' WHERE '.$object->_filter($filter);

            if(is_array($orderType)){
                $orderType = trim(implode(' ',$orderType))?$orderType:$object->defaultOrder;
                if($orderType){
                    $sql.=' ORDER BY '.implode(' ',$orderType);
                }
            }elseif($orderType){
                $sql .= ' ORDER BY ' . $orderType;
            }else{
                $sql.=' ORDER BY '.implode(' ', $object->defaultOrder);
            }
//            $count = $object->db->count($sql);
            $rows = $object->db->selectLimit($sql,$limit,$start);
           $object->tidy_data($rows,$cols);
            $object->_dbstorage[$ident]=$rows;
        }
        return $object->_dbstorage[$ident];
    }


}
