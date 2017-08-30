<?php
/**
* 其它入库事件
*/
class console_event_trigger_otherstockin extends console_event_trigger_stockinabstract{

    /**
    * 其他出入库数据整理
    */
    function getStockInParam($param){
        $iostockObj = kernel::single('console_iostockdata');
        $iso_id = $param['iso_id'];
        $data = $iostockObj->get_iostockData($iso_id);
        $type_id = $data['type_id'];
        switch($type_id){
            case '4'://调拔入库
            case '40'://调拔出库
                
                $data['io_type'] = 'ALLCOATE';
                break;
            case '5'://残损出库
            case '50'://残损入
                $data['io_type'] = 'DEFECTIVE';
                break;
            case '7'://直接出入库
            case '70':
                $data['io_type'] = 'DIRECT';
                break;
            default:
            $data['io_type'] = 'OTHER';
            break;
        }
       #error_log(var_export($data,1),3,__FILE__.'.log');
       return $data;
    }

    protected function update_out_bn($io_bn,$out_iso_bn)
    {
        $oIso = app::get('taoguaniostockorder')->model('iso');
        $data = array(
            'out_iso_bn'=>$out_iso_bn
        );
        $oIso->update($data,array('iso_bn'=>$io_bn));
    }
}
?>