<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class eccommon_analysis_task
{
    public function analysis_day()
    {
        $data = $this->fetch_by_interval('day');
        foreach(kernel::serviceList('eccommon_analyse_day') AS $service){
            if(!$service instanceof eccommon_analysis_interface) continue;
            $run_task = false;
            $service_name = get_class($service);
            $run_day = strtotime(date("Y-m-d", time()-86400) . ' 00:00:00');
            if(!isset($data[$service_name])){
                $new_service = array(
                        'service' => $service_name,
                        'interval' => 'day',
                );
                if($analysis_id = app::get('eccommon')->model('analysis')->insert($new_service)){
                    $run_task = true;
                }
            }elseif($data[$service_name]['modify']+86400 <= $run_day){
                $run_day = $data[$service_name]['modify']+86400;
                $run_task = true;
                $analysis_id = $data[$service_name]['id'];
                unset($data[$service_name]);
            }else{
                unset($data[$service_name]);
            }
            if($run_task){
                $rows = $service->get_logs($run_day);
                if($rows){
                    foreach($rows AS $row){
                        $logs = array();
                        $logs['analysis_id'] = $analysis_id;
                        $logs['types'] = $row['type'];
                        $logs['target'] = $row['target'];
                        $logs['flag'] = $row['flag'];
                        $logs['value'] = $row['value'];
                        $logs['time'] = $run_day;
                        app::get('eccommon')->model('analysis_logs')->insert($logs);
                    }
                }
                app::get('eccommon')->model('analysis')->update(array('modify'=>$run_day), array('id'=>$analysis_id));
            }
        }
    }//End Function

    public function analysis_hour()
    {
        $data = $this->fetch_by_interval('hour');
        foreach(kernel::serviceList('eccommon_analyse_hour') AS $service){
            if(!$service instanceof eccommon_analysis_interface) continue;
            $run_task = false;
            $service_name = get_class($service);
            $run_hour = strtotime(date("Y-m-d H", time()-3600) . ':00:00');
            if(!isset($data[$service_name])){
                $new_service = array(
                        'service' => $service_name,
                        'interval' => 'hour',
                );
                if($analysis_id = app::get('eccommon')->model('analysis')->insert($new_service)){
                    $run_task = true;
                }
            }elseif($data[$service_name]['modify']+3600 <= $run_hour){
                $run_hour = $data[$service_name]['modify']+3600;
                $run_task = true;
                $analysis_id = $data[$service_name]['id'];
                unset($data[$service_name]);
            }else{
                unset($data[$service_name]);
            }
            if($run_task){
                $rows = $service->get_logs($run_hour);
                if($rows){
                    foreach($rows AS $row){
                        $logs = array();
                        $logs['analysis_id'] = $analysis_id;
                        $logs['type'] = $row['type'];
                        $logs['target'] = $row['target'];
                        $logs['flag'] = $row['flag'];
                        $logs['value'] = $row['value'];
                        $logs['time'] = $run_hour;
                        app::get('eccommon')->model('analysis_logs')->insert($row);
                    }
                }
                app::get('eccommon')->model('analysis')->update(array('modify'=>$run_hour), array('id'=>$analysis_id));
            }
        }
    }//End Function

    public function fetch_by_interval($interval)
    {
        $rows = app::get('eccommon')->model('analysis')->getList('*', array('interval'=>$interval));
        foreach($rows AS $row){
            $data[$row['service']] = $row;
        }
        return $data;
    }//End Function

}//End Class