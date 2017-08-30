<?php
class omeanalysts_func{

    /*
    * 获取店铺列表
    */
    public function shop(){
        $shopModel = &app::get('ome')->model('shop');
        return $shopModel->getList('shop_id as type_id,name');
    }

    /*
    * 获取仓库列表
    */
    public function branch_list(){
        $branchModel = &app::get('ome')->model('branch');
        return $branchModel->getList('branch_id as type_id,name',array('attr'=>'true'));
    }

    /*
    * 获取报表搜索区域的日期时间按钮值
    */
    static function timeBtn(){
        $timeBtn = array(
            'today' => date("Y-m-d"),
            'yesterday' => date("Y-m-d", time()-86400),
            'this_month_from' => date("Y-m-" . 01),
            'this_month_to' => date("Y-m-d"),
            'this_week_from' => date("Y-m-d", time()-(date('w')?date('w')-1:6)*86400),
            'this_week_to' => date("Y-m-d"),
            'sevenday_from' => date("Y-m-d", time()-6*86400),
            'sevenday_to' => date("Y-m-d"),
        );
        return $timeBtn;
    }

    /*
    * 高级搜索查询条件过滤
    * @param $operator 搜索条件:大于、小于、等于
    * @param $field_name 搜索字段名
    * @param $field_value 搜索字段值 
    * @return 返回查询条件
    */
    static public function search_filter($operator,$field_name,$field_value){
        $where = '';
        switch($operator){
            case 'than':#大于
                $where = ' and '.$field_name.' > '.$field_value;
            break;
            case 'lthan':#小于
                $where = ' and '.$field_name.' < '.$field_value;
            break;
            case 'nequal':#等于
                $where = ' and '.$field_name.' = '.$field_value;
            break;
            case 'sthan':#小于等于
                $where = ' and '.$field_name.' <= '.$field_value;
            break;
            case 'bthan':#大于等于
                $where = ' and '.$field_name.' >= '.$field_value;
            break;
            case 'between':#介于
                $where = ' and '.$field_name.' >= '.$field_value.' and '.$field_name.' >= '.$field_value;
            break;
        }
        return $where;
    }
    
    /**
     * 保存筛选器的信息,用于做导出条件
     *
     * @return void
     * @author 
     **/
    public function save_search_filter($params = array()){
        $html = '';

        foreach($params as $k=>$v){
            if(!in_array($k,array('_DTYPE_TIME','_finder','_DTYPE_DATE','time_to','time_from','goods_type_id','brand_id','branch_id','shop_id','order_status'))){
                if(is_array($params[$k])){
                    $this->_deal_multiarray($params[$k],$ret,$k);
                    foreach($ret as $kk=>$vv){
                        $html .= "<input type='hidden' name='".$kk."' value='".$vv."'>";
                    }                        
                }else{
                   $html .= "<input type='hidden' name='".$k."' value='".$v."'>";
                }

            }
            
        }
        
        echo '<script>
            if($("tohidden")){
                var html = "'.$html.'";
                $("tohidden").set("html",html);
            }
            </script>
        '; 
    }

    private function _deal_multiarray($data,&$ret,$path=null){
        foreach($data as $k=>$v){
            $d = $path?$path.'['.$k.']':$k;
            if(is_array($v)){
                $this->_deal_multiarray($v,$ret,$d);
            }else{
                $ret[$d]=$v;
            }
        }
    }

    /**
    * 对二维数组进行排序
    *
    * sysSortArray($Array,"Key1","SORT_ASC","SORT_RETULAR","Key2"……)
    * @param array   $ArrayData  需要排序的数组.
    * @param string $KeyName1    排序字段.
    * @param string $SortOrder1  顺序("SORT_ASC"|"SORT_DESC")
    * @param string $SortType1   排序类型("SORT_REGULAR"|"SORT_NUMERIC"|"SORT_STRING")
    * @return array              排序后的数组.
    */
    function sysSortArray($ArrayData,$KeyName1,$SortOrder1 = "SORT_ASC",$SortType1 = "SORT_REGULAR")
    {
        if(!is_array($ArrayData))
        {
              return $ArrayData;
        }
        // Get args number.
        $ArgCount = func_num_args();
        // Get keys to sort by and put them to SortRule array.
        for($I = 1;$I < $ArgCount;$I ++)
        {
              $Arg = func_get_arg($I);
              if(!eregi("SORT",$Arg))
              {
                  $KeyNameList[] = $Arg;
                  $SortRule[]    = '$'.$Arg;
              }
              else
              {
                  $SortRule[]    = $Arg;
              }
        }
        // Get the values according to the keys and put them to array.
        foreach($ArrayData AS $Key => $Info)
        {
              foreach($KeyNameList AS $KeyName)
              {
                  ${$KeyName}[$Key] = $Info[$KeyName];
              }
        }
        // Create the eval string and eval it.
        $EvalString = 'array_multisort('.join(",",$SortRule).',$ArrayData);';

        eval ($EvalString);
        return $ArrayData;
    }

}