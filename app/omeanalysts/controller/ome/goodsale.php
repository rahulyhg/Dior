<?php
class omeanalysts_ctl_ome_goodsale extends desktop_controller{
    var $name = "商品销售情况";
    
    function index(){
        //修改日期范围
        $_SESSION['data'] = $_REQUEST;
        $this->mod_query_time();
        $_POST['own_branches'] = $this->getOperBranches();
        kernel::single('omeanalysts_ome_goodsale')->set_params($_POST)->display();
    }
    
    function mod_query_time(){
        
        $date_arr = $this->get_time_start();
        if(empty($_POST) && !isset($_REQUEST['time_from']) && !isset($_REQUEST['time_to'])){
            $_POST['time_from'] = $date_arr['now_zero_day'];
            $_POST['time_to'] = $date_arr['now_zero_day'];
        }
        
    }
    
    function get_time_start(){
        $t   =   time();   
        $nowday   =   date('Y-m-d',mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t)));           //当天零点的时间戳 
        $nowmonth   =   date('Y-m-d',mktime(0,0,0,date("m",$t),1,date("Y",$t)));                    //当月一号零点的时间戳
        $nextmonth =  date('Y-m-d',mktime(0,0,0,date("m",$t)+1,1,date("Y",$t)));                    //下月一号零点时间戳
        $nowyear    =   date('Y-m-d',mktime(0,0,0,1,1,date("Y",$t)));                               //当年一月一号零点时间戳
        $lastmonth =  date('Y-m-d',mktime(0,0,0,date("m",$t)-1,1,date("Y",$t)));                    //上月一号零点时间戳
        $nowendmonth = date('Y-m-d',mktime(0,0,0,date("m",$t)+1,1,date("Y",$t))-24*60*60);
        $nowendyear = date('Y-m-d',mktime(0,0,0,12,31,date("Y",$t)));
        return array('now_zero_day' => $nowday,
                     'now_zero_month'=>$nowmonth,
                     'now_zero_year'=>$nowyear,
                     'next_zero_month'=>$nextmonth,
                     'last_zero_month'=>$lastmonth,
                     'now_end_month'=>$nowendmonth,
                     'now_end_year'=>$nowendyear
        );
    }

    private function getOperBranches(){
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if (count($branch_ids)>0) {
                return $branch_ids;
            } else {
                return array(0);
            }
        }
    }

}