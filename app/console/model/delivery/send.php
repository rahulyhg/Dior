<?php
class console_mdl_delivery_send extends dbeav_model{

    
    
    function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
       
       return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }
    
    public function count($filter=null){
        $filtersql = $this->_filter($filter);
        $filtersql = str_replace('`sdb_console_delivery_send`.sync','s.sync',$filtersql);
        $filtersql = str_replace('`sdb_console_delivery_send`','D',$filtersql);
        $sql = 'SELECT count(*) as _count FROM sdb_console_delivery_send as s left join sdb_ome_delivery as D ON s.delivery_id=D.delivery_id WHERE '.$filtersql;

        $row = $this->db->select($sql);

        return intval($row[0]['_count']);
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        $filtersql = $this->_filter($filter);
        $filtersql = str_replace('`sdb_console_delivery_send`.sync','s.sync',$filtersql);
        $filtersql = str_replace('`sdb_console_delivery_send`','D',$filtersql);
        $sql = 'SELECT s.sync,D.delivery_id,D.delivery_bn,D.member_id,D.is_cod,D.logi_name FROM sdb_console_delivery_send as s left join sdb_ome_delivery as D ON s.delivery_id=D.delivery_id WHERE '.$filtersql;

			
        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);

        $rows = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($rows, $cols);
        
        return $rows;
    }
    
    public function get_schema(){
        $schema = array (
            'columns' => array (
                'delivery_id' =>
                array (
                    'type' => 'int unsigned',
                    'required' => true,
                    'pkey' => true,
                    'editable' => false,
                    'extra' => 'auto_increment',
                ),
                'delivery_bn' =>
                array (
                    'type' => 'varchar(32)',
                    'required' => true,
                    'label' => '发货单号',
                    'comment' => '配送流水号',
                    'editable' => false,
                    'width' =>140,
                    'searchtype' => 'nequal',
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                ),
                'member_id' =>
                array (
                    'type' => 'table:members@ome',
                    'label' => '会员用户名',
                    'comment' => '订货会员ID',
                    'editable' => false,
                    'width' =>75,

                ),
                'is_cod' =>
                array (
                    'type' => 'bool',
                    'default' => 'false',
                    'required' => true,
                    'label' => '是否货到付款',
                    'editable' => false,
                    'filtertype' => 'normal',

                ),
                'logi_id' =>
                array (
                    'type' => 'table:dly_corp@ome',
                    'comment' => '物流公司ID',
                    'editable' => false,
                    'label' => '物流公司',
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'logi_name' =>
                array (
                    'type' => 'varchar(100)',
                    'label' => '物流公司',
                    'comment' => '物流公司名称',
                    'editable' => false,

                ),
                'sync' =>
                array (
                    'type' => array(
                    'none' => '未发起',
                    'running' => '运行中',
                    'success' => '成功',
                    'fail' => '失败',
                    'sending' => '发起中',
                    ),
                    'default' => 'sending',
                    'label' => '回写状态',
                    'editable' => false,
                   
                ),
                'msg' =>
                array (
                    'type' => 'text',
                    'editable' => false,
                     'label' => '错误信息',
                ), 



            ),
        'idColumn' => 'delivery_id',
        'in_list' => array (
            0 => 'delivery_bn',
            1=>'member_id',
            2=>'is_cod',
            3=>'logi_name',
            4=>'sync',
            5=>'msg',
        ),
        'default_in_list' => array (
            0 => 'delivery_bn',
            1=>'member_id',
            2=>'is_cod',
            3=>'logi_name',
            4=>'sync',
            5=>'msg',
        ),
        );
        return $schema;
    }

    
    /**
     * 更新发送仓储状态
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function update_send_status($delivery_id,$sync,$msg='')
    {
        $sync_detail = $this->db->selectrow("SELECT delivery_id FROM sdb_console_delivery_send WHERE delivery_id=".$delivery_id);
        $sync = $sync == 'succ' ? 'success' : $sync;
        if ($sync_detail) {
            $sqlstr = '';
            if ($msg) {
                $sqlstr.=",msg='".$msg."'";
            }
            $SQL = "UPDATE sdb_console_delivery_send SET sync='".$sync."'".$sqlstr." WHERE delivery_id=".$delivery_id;
        }else{
            $SQL = "INSERT INTO sdb_console_delivery_send(delivery_id,sync,msg) VALUES('$delivery_id','$sync','$msg')";
        }

        $this->db->exec($SQL);
    }

}