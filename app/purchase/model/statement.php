<?php
/*
 * 结算清单
 */

class purchase_mdl_statement extends dbeav_model{
    
    
    function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        if (!$orderType) $orderType = 'supplier_id,statement_time desc';
        $data = parent::getList($cols, $filter, $offset, $limit, $orderType);
        $oSupplier = $this->app->model('supplier');
        $return = array();
        foreach ($data as $key=>$val){
            //获取供应商编号
            $supplier = $oSupplier->supplier_detail($val['_0_supplier_id'], 'bn');
            $val['supplier_bn'] = $supplier['bn'];
            $return[] = $val;
        }
        return $return;
    }
    
    /*
     * 结算清单getList重载
     */
    function getStatementList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
    
    $ini_supplier_list = $this->statementListGroupBy($filter, $offset, $limit, $orderType);
        $supplier_list = array();
        foreach ($ini_supplier_list as $key=>$val)
        {
          $statementDetail = $this->statementDetail($val['supplier_id'], $wheresql);
            
          //计算差额合计
          if ($statementDetail){
        foreach ($statementDetail as $k=>$v)
        {
          if ($v['object_type']==3){
            if ($k>0) $val['difference'] -= $v['difference'];
          }else{
          $val['difference'] += $v['difference'];
          }
        }
          }
          $val['statement_id'] = $val['supplier_id'];#主键ID
          $supplier_list[] = $val;
        }
        return $supplier_list;

    }
    
    /*
     * 重载getlist数据总数
     */
    function getStatementCount($filter=null)
    {
    return count($this->statementListGroupBy($filter));
    }

    /*
     * 结算供应商统计列表
     * 
     */
    function statementListGroupBy($filter=null, $offset=0, $limit=-1, $orderType=null)
    {
    $orderType = $orderType ? $orderType : $this->defaultOrder;
        if($orderType){
        $ordersql = ' ORDER BY '.(is_array($orderType) ? implode($orderType,' ') : $orderType);
        }
        if ($filter) {
        $wheresql = " WHERE ".$this->_filter($filter);
        }
        if ($offset >= 0 || $limit >= 0){
            $offset = ($offset >= 0) ? $offset . "," : '';
            $limit = ($limit >= 0) ? $limit : '18446744073709551615';
            $limitsql .= ' LIMIT ' . $offset . ' ' . $limit;
        }
        //获取结算表中的供应商列表
        $sql = " SELECT a.*,b.`name`,b.`bn`
                 FROM (
                 SELECT `supplier_id`,sum(`initial_pay`) initial_pay,sum(`pay_add`) pay_add,sum(`paid`) paid,
                 sum(`final_pay`) final_pay,sum(`initial_receive`) initial_receive,sum(`receive_add`) receive_add,
                 sum(`received`) received,sum(`final_receive`) final_receive,sum(`difference`) difference
                 FROM sdb_purchase_statement $wheresql GROUP BY `supplier_id` ) a
                 LEFT JOIN `sdb_purchase_supplier` b ON a.`supplier_id`=b.`supplier_id` 
                 $ordersql $limitsql ";
                 
        $ini_supplier_list = &$this->db->select($sql);
        return $ini_supplier_list;
    }
    
    /*
     * 结算清单合计 
     * @packace statement_counter
     */
    function statement_counter($supplier_id=null)
    {
        if ($supplier_id) $wheresql = " and `supplier_id`='".$supplier_id."' ";
        $sql = " SELECT `supplier_id`,sum(`initial_pay`) initial_pay,sum(`pay_add`) pay_add,sum(`paid`) paid,
                 sum(`final_pay`) final_pay,sum(`initial_receive`) initial_receive,sum(`receive_add`) receive_add,
                 sum(`received`) received,sum(`final_receive`) final_receive
                 FROM `sdb_purchase_statement` where 1 $wheresql GROUP BY `supplier_id` ";
        $result = &$this->db->selectrow($sql);
        
        $oStatement = $this->app->model('statement');
        $statementDetail = $this->statementDetail($result['supplier_id'], $wheresql);
        
        //供应商名称
        $oSupplier = $this->app->model('supplier');
        $supplier = $oSupplier->dump($result['supplier_id'], 'name,bn');
        $result['supplier_name'] = $supplier['name'];
        $result['supplier_bn'] = $supplier['bn'];
        
        //计算差额合计
        if ($statementDetail)
        foreach ($statementDetail as $k=>$v)
        {
          if ($v['object_type']==3){
              if ($k>0) $result['difference'] -= $v['difference'];
          }
          else{
              $result['difference'] += $v['difference'];
          }
        }
        return $result;
    }
    
    /*
     * 结算清单列表 statementDetail
     * @param int($supplier_id)
     * @param string($wheresql)
     * @return ArrayIterator
     */
    function statementDetail($supplier_id='', $wheresql='')
    {
        if ($supplier_id) $sql = " and `supplier_id`='".$supplier_id."' ";
        //统计该供应商下的结算详情列表
        $sql_detail = " SELECT * FROM `sdb_purchase_statement`
                            WHERE 1 $sql $wheresql ORDER BY `supplier_id`,`object_type` ";
        $statementDetail = $this->db->select($sql_detail);
        return $statementDetail;
    }
    
    /*
     * 结算单打印
     */
    function statement_print_do($ids=null)
    {
        $ini_supplier_list = $this->statementListGroupBy($ids);
        $supplier_list = array();
        if ($ini_supplier_list)
        foreach ($ini_supplier_list as $key=>$val)
        {
          $statementDetail = $this->statementDetail($val['supplier_id'], '');
          $val['difference'] = 0;
          //计算差额合计
          if ($statementDetail){
            foreach ($statementDetail as $k=>$v)
            {
              if ($v['object_type']==3){
                $val['difference'] -= $v['difference'];
              }else{
                $val['difference'] += $v['difference'];
              }
            }
          }
          $val['statement_list'] = $statementDetail;
          $supplier_list[] = $val;
        }
        return $supplier_list;
    }
    
    /*
     * 采购结算统计表数据获取
     */
    function GetClearingTables($data=null)
    {
        //日期查询
        $begin_date = $data['begin_date'];
        $end_date = $data['end_date'];
        $supplier_id = $data['supplier'];
        if ($begin_date) $wheresql = " and FROM_UNIXTIME(`statement_time`,'%Y-%m-%d')>='$begin_date' ";
        if ($end_date) $wheresql .= " and FROM_UNIXTIME(`statement_time`,'%Y-%m-%d')<='$end_date' ";
        if ($supplier_id){
            $wheresql .= " and supplier_id='".$supplier_id."' ";
            $oSupplier = $this->app->model('supplier');
            //供应商名称
            $supplier_detail = $oSupplier->supplier_detail($supplier_id, 'name');
        }
        $sql = " SELECT * FROM `sdb_purchase_statement` where 1 $wheresql ";
        $statementList = &$this->db->select($sql);
        $clearingtables = array();
        $clearingtables['difference'] = 0;
        $clearingtables['supplier_id'] = $supplier_id;
        $clearingtables['supplier_name'] = $supplier_detail['name'];
        foreach ($statementList as $key=>$val)
        {
            //差额
            if ($val['object_type']==3){
                $clearingtables['difference'] -= $val['difference'];
            }else{
                $clearingtables['difference'] += $val['difference'];
            }
            $clearingtables['initial_pay'] += $val['initial_pay'];#期初应付
            $clearingtables['initial_receive'] += $val['initial_receive'];#期初应收
            $clearingtables['pay_add'] += $val['pay_add'];#本期增加应付
            $clearingtables['receive_add'] += $val['receive_add'];#本期增加应收
            $clearingtables['paid'] += $val['paid'];#本期已付
            $clearingtables['received'] += $val['received'];#本期已收
            $clearingtables['final_pay'] += $val['final_pay'];#期末应付
            $clearingtables['final_receive'] += $val['final_receive'];#期末应收
        }
        //开始统计日期
        $sql = " SELECT `statement_time` FROM `sdb_purchase_statement` ORDER BY statement_time asc limit 0,1 ";
        $start_statetime = &$this->db->select($sql);
        if ($start_statetime[0]['statement_time'])
        $clearingtables['start_statetime'] = date("Y-m-d",$start_statetime[0]['statement_time']);
        
        $clearingtables['begin_date'] = $begin_date;
        $clearingtables['end_date'] = $end_date;
        return $clearingtables;
    }
    
    /*
     * 获取业务类型
     * @package getStatementType
     */
    function getStatementType($type='')
    {
        $arr = array(
          '1' => '赊购入库',
          '2' => '现款结算',
          '3' => '采购退货'
        );
        if ($type) return $arr[$type];
        else return $arr;     
    }
    
    /*
     * 结算状态 
     */
    function getStatementStatus($status=null){
        $arr = array(
          '1' => '未结算',
          '2' => '已结算',
          '3' => '拒绝结算'
        );
        if ($status) return $arr[$status];
        else return $arr;
    }
    
}
?>