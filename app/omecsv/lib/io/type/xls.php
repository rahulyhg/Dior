<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class omecsv_io_type_xls extends omecsv_io_io{

    var $io_type_name = 'xls';



    function data2local( $data ){
        $title = array();
        foreach( $data as $aTitle ){
            $title[] = $this->charset->utf2local($aTitle);
        }
        return $title;
    }

    function fgetlist( &$data,&$model,$filter,$offset,$exportType =1 ){}

    function turn_to_sdf( $data ){}

    function import(&$contents,$app,$mdl ){}

    function csv2sdf($data,$title,$csvSchema,$key = null){
        $rs = array();
        $subSdf = array();
        foreach( $csvSchema as $schema => $sdf ){
            $sdf = (array)$sdf;
            if( ( !$key && !$sdf[1] ) || ( $key && $sdf[1] == $key ) ){
                eval('$rs["'.implode('"]["',explode('/',$sdf[0])).'"] = $data[$title[$schema]];');
                unset($data[$title[$schema]]);
            }else{
                $subSdf[$sdf[1]] = $sdf[1];
            }
        }
        if(!$key){
            foreach( $subSdf as $k ){
                foreach( $data[$k] as $v ){
                    $rs[$k][] = $this->csv2sdf($v,$title,$csvSchema,$k);
                }
            }
        }
        foreach( $data as $orderk => $orderv ){
            if( substr($orderk,0,4 ) == 'col:' ){
                $rs[ltrim($orderk,'col:')] = $orderv;
            }
        }
        return $rs;
    }


    public function export_header($filename){
        header('Content-Type: application/vnd.ms-excel');
        $file_name = $filename.'.xls';
        $encoded_filename = urlencode($file_name);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);

        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/MSIE/', $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match('/Firefox$/', $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $file_name . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
        }
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
    }

    public function export($data,$offset,$model,$exportType=1){
        foreach($data as $pColumn=>$pValue)
        {
             $this->objPHPExcel->setActiveSheetIndex(0)
            ->setCellValueExplicitByColumnAndRow($pColumn, $offset, $pValue);
        }
    }

    public function finish_export(){
        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel , 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

}
