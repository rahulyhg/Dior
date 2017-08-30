<?php
class omeanalysts_analysis_shop_shop{

    /**
     * 用于统计实时的店铺销售数据
     *
     * @return void
     * @author
     **/
    public function analysis_data($filter = array()){

        $analysis_info = app::get('eccommon')->model('analysis')->select()->columns('*')->where('service = ?', 'omeanalysts_ome_shop')->instance()->fetch_row();

        if(empty($_POST['time_from'])){
            $params['sdfdata']['time_from'] = $params['sdfdata']['time_to'] = time();
            $params['sdfdata']['analysis_id'] = $analysis_info['id'];
            $this->analysis($params);
        }else{
            $params['time_from'] = strtotime($_POST['time_from']);
            $params['time_to'] = strtotime($_POST['time_to']);
            $params['analysis_id'] = $analysis_info['id'];

            $queueData = array(
                'queue_title'=>$_POST['time_from'].'-'.$_POST['time_to'].'店铺每日汇总',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$params,
                ),
                'worker'=>'omeanalysts_analysis_shop_shop.run_queue',
            );

            $queue_id = app::get('base')->model('queue')->insert($queueData);
            app::get('base')->model('queue')->runtask($queue_id);

            echo 'succ';exit;
        }

    }
    public function regenerate(){

        $time_from = date("Y-m-d", time()-(date('w')?date('w')-1:6)*86400);

        $time_to = date("Y-m-d", strtotime($time_from)+86400*7-1);
        $render = kernel::single('desktop_controller');
        $render->pagedata['type'] = 'shop';
        $render->pagedata['action'] = 'analysis_data';
        $render->pagedata['time_from'] = $time_from;
        $render->pagedata['time_to'] = $time_to;
        echo $render->fetch('ome/regenerate_report.html','omeanalysts');
    }


    function run_queue(&$cursor_id,$params,&$errmsg){

        return $this->analysis($params);
    }

    private function analysis($params){

        $analyLogMdl = app::get('eccommon')->model('analysis_logs');

        $ft = $params['sdfdata']['time_from'];

        $time_from = mktime(0, 0, 0, date('m',$ft), date('d',$ft), date('Y',$ft));

        $tt = $params['sdfdata']['time_to'];

        $time_to = mktime(23, 59, 59, date('m',$tt), date('d',$tt), date('Y',$tt));

        $no_run_days = intval(ceil(($time_to-$time_from)/(24*3600)));

        for($i=1;$i<=$no_run_days;$i++){
            $end_time = date("Y-m-d",$time_from+($i*24*3600));
            $last_end_time = strtotime($end_time);
            $rows = kernel::single('omeanalysts_ome_shop')->get_logs($last_end_time);
            if($rows){

                foreach($rows AS $row){
                    $logs = array();
                    $logs['analysis_id'] = $params['sdfdata']['analysis_id'];
                    $logs['type'] = $row['type'];
                    $logs['target'] = $row['target'];
                    $logs['flag'] = $row['flag'];
                    $logs['value'] = $row['value'];
                    $logs['time'] = $last_end_time;

					$analydata = $analyLogMdl->dump(array('analysis_id'=>$logs['analysis_id'],'flag'=>$logs['flag'],'time'=>$logs['time']),'id');

					$logs['id'] = $analydata['id'];

                    $analyLogMdl->save($logs);
                }
            }

        }
        return false;
    }
}