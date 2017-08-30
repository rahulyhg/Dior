<?php
class omeanalysts_ome_graph_month{
    public function fetch_graph_data($params){
        $analysis_info = app::get('eccommon')->model('analysis')->select()->columns('*')->where('service = ?', $params['service'])->instance()->fetch_row();
        if(empty($analysis_info))   return array('categories'=>array(), 'data'=>array());
        if(isset($params['report']) && $params['report'] == 'month'){
            $params['time_from'] = strtotime($params['time_from']);
            $params['time_to'] = strtotime($params['time_to']);
            for($i=$params['time_from']; $i<=$params['time_to'];){
                $time_range[] = date("Y-m", $i);
                $i = mktime(0, 0, 0, date('m',$i)+1, date('d',$i), date('Y',$i));
            }

            $logs_options = kernel::single($params['service'])->logs_options;
            $target = $logs_options[$params['target']];
            if(is_array($target['flag']) && count($target['flag'])){
                foreach($target['flag'] AS $k=>$v){
                    foreach($time_range AS $date){
                        $data[$v][$date] = 0;
                    }
                }
            }else{
                foreach($time_range AS $date){
                    $data['全部'][$date] = 0;
                }
            }

            foreach($time_range AS $date){
                $time_from = strtotime($date.'-01');
                $time_to = mktime(0, 0, 0, date('m',$time_from)+1, date('d',$time_from), date('Y',$time_from));

                $obj = app::get('eccommon')->model('analysis_logs')->select()->columns('target, flag, sum(value) AS value,time')->where('analysis_id = ?', $analysis_info['id']);
                $obj->where('time >= ?', $time_from);
                $obj->where('time < ?', $time_to);
                if(isset($this->_params['type'])) $obj->where('type = ?', $params['type']);
                $rows = $obj->where('target = ?', $params['target'])->group(array('flag'))->instance()->fetch_all();

                foreach($rows AS $row){
                    $flag_name = $target['flag'][$row['flag']];
                    if($flag_name){
                        $data[$flag_name][$date] = $row['value'];
                    }else{
                        $data['全部'][$date] = $row['value'];
                    }
                }
            }
        }else{
            $obj = app::get('eccommon')->model('analysis_logs')->select()->columns('target, flag, value, time')->where('analysis_id = ?', $analysis_info['id']);
            $obj->where('target = ?', $params['target']);
            $obj->where('time >= ?', strtotime(sprintf('%s 00:00:00', $params['time_from'])));
            $obj->where('time <= ?', strtotime(sprintf('%s 23:59:59', $params['time_to'])));
            if(isset($this->_params['type']))   $obj->where('type = ?', $params['type']);
            $rows = $obj->instance()->fetch_all();

            for($i=strtotime($params['time_from']); $i<=strtotime($params['time_to']); $i+=($analysis_info['interval'] == 'day')?86400:3600){
                $time_range[] = ($analysis_info['interval'] == 'day') ? date("Y-m-d", $i) : date("Y-m-d H", $i);
            }

            $logs_options = kernel::single($params['service'])->logs_options;
            $target = $logs_options[$params['target']];
            if(is_array($target['flag']) && count($target['flag'])){
                foreach($target['flag'] AS $k=>$v){
                    foreach($time_range AS $date){
                        $data[$v][$date] = 0;
                    }
                }
            }else{
                foreach($time_range AS $date){
                    $data['全部'][$date] = 0;
                }
            }

            foreach($rows AS $row){
                $date = ($analysis_info['interval'] == 'day') ? date("Y-m-d", $row['time']) : date("Y-m-d H", $row['time']);
                $flag_name = $target['flag'][$row['flag']];
                if($flag_name){
                    $data[$flag_name][$date] = $row['value'];
                }else{
                    $data['全部'][$date] = $row['value'];
                }
            }
        }

        return array('categories'=>$time_range, 'data'=>$data);
    }
}