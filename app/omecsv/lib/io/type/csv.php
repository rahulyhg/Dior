<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class omecsv_io_type_csv extends omecsv_io_io{

    var $io_type_name = 'csv';
    var $limitRow = 5000;


    function str2Array( &$content,$char = "\n" ){
        $content = str_replace("\r",'\r',str_replace("\n",'\n',str_replace('"','""',$v)));
    }

    function fgethandle($inputFileName,&$contents){
        $handle = fopen( $inputFileName,"r" );

        $line = 0; $contents = array();
        while ($row = fgetcsv($handle) ) {
            if($line==0){
                $this->encoding=mb_detect_encoding($row[0],array('ASCII','GB2312','GBK','UTF-8'));
            }
            kernel::log('fdassdf='.$this->encoding);
            if($this->encoding=="UTF-8"){
                $contents[$line] = $row;
            }else{
                 foreach( $row as $num => $col ){
                    $value = (string)$col;            
                    $contents[$line][$num] = iconv("GBK","UTF-8",$value);
                 }
            }
              $line++;
        }

        fclose($handle);
    }

    function data2local( $data ){
        $title = array();
        foreach( $data as $aTitle ){
            $title[] = $this->charset->utf2local($aTitle);
        }
        return $title;
    }

    function fgetlist( &$data,&$model,$filter,$offset,$exportType =1 ){
        $limit = 100;

        $cols = $model->_columns();
        if(!$data['title']){
            $this->title = array();
            foreach( $this->getTitle($cols) as $titlek => $aTitle ){
                $this->title[$titlek] = $aTitle;
            }
            $data['title'] = '"'.implode('","',$this->title).'"';
        }

        if(!$list = $model->getList(implode(',',array_keys($cols)),$filter,$offset*$limit,$limit))return false;

        foreach( $list as $line => $row ){
            $rowVal = array();
            foreach( $row as $col => $val ){

                if( in_array( $cols[$col]['type'],array('time','last_modify') ) && $val ){
                   $val = date('Y-m-d H:i',$val);
                }
                if ($cols[$col]['type'] == 'longtext'){
                    if (strpos($val, "\n") !== false){
                        $val = str_replace("\n", " ", $val);
                    }
                }

                if( strpos( (string)$cols[$col]['type'], 'table:')===0 ){
                    $subobj = explode( '@',substr($cols[$col]['type'],6) );
                    if( !$subobj[1] )
                        $subobj[1] = $model->app->app_id;
                    $subobj = &app::get($subobj[1])->model( $subobj[0] );
                    $subVal = $subobj->dump( array( $subobj->schema['idColumn']=> $val ),$subobj->schema['textColumn'] );
                    $val = $subVal[$subobj->schema['textColumn']]?$subVal[$subobj->schema['textColumn']]:$val;
                }

                if( array_key_exists( $col, $this->title ) )
                    $rowVal[] = addslashes(  (is_array($cols[$col]['type'])?$cols[$col]['type'][$val]:$val ) );
            }
            $data['contents'][] = '"'.implode('","',$rowVal).'"';
        }
        return true;

    }

    function turn_to_sdf( $data ){

    }

    function import(&$contents,$app,$mdl ){
        $model = &$this->model;
        if(!is_array($contents))
            $this->str2Array($contents);
        if( !$this->data['title'] )
            $this->data = array('title'=>array(),'contents'=>array());
        $msg = array();

        $oQueue = app::get('base')->model('queue');


        while( true ){
            //
        }
        return array('success', $msg);

        while( true ){
            $row = current($contents);
            if( !is_array($row) )
                $this->str2Array($row,',');
            if( $row ){
                foreach( $row as $num => $col )
                    $row[$num] = trim($col,'"');
            }
            $newObjFlag = false;
            $rowData = $model->prepared_import_csv_row( $row,$this->data['title'],$tmpl,$mark,$newObjFlag,$msg );
            if( $rowData === false ){
                return array('failure',$msg);
            }

            if( !current($contents) || $newObjFlag ){
                if( $mark != 'title' ){

                    $saveData = $model->prepared_import_csv_obj( $this->data,$mark,$tmpl,$msg);
                    if( $saveData === false ){
                        return array('failure',$msg);
                    }

                    if( $saveData ){
                        $queueData = array(
                            'queue_title'=>$mdl.app::get('desktop')->_('导入'),
                            'start_time'=>time(),
                            'params'=>array(
                                'sdfdata'=>$saveData,
                                'app' => $app,
                                'mdl' => $mdl
                            ),
                            'worker'=>'desktop_finder_builder_to_run_import.run',
                        );
                        $oQueue->save($queueData);
                    }
                    if( $mark )
                        eval('$this->data["'.implode('"]["',explode('/',$mark)).'"] = array();');
                }
            }
            next( $contents );
            if( $mark ){
                if( $mark == 'title' )
                    eval('$this->data["'.implode('"]["',explode('/',$mark)).'"] = $rowData;');
                else
                    eval('$this->data["'.implode('"]["',explode('/',$mark)).'"][] = $rowData;');
            }
            if( !$row )break;
        }

        return array('success', $msg);
    }

    function prepared_import( $appId,$mdl ){
        $this->model = &app::get($appId)->model($mdl);
        $this->model->ioObj = $this;
        if( method_exists( $this->model,'prepared_import_csv' ) ){
            $this->model->prepared_import_csv();
        }
        return;
    }

    function finish_import(){
        if( method_exists( $this->model,'finish_import_csv' ) ){
            $this->model->finish_import_csv();
        }
    }

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

    public function export_header($filename)
    {
        header("Content-Type: text/csv");
        $file_name = $filename.".csv";
        $encoded_filename = urlencode($file_name);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);

        $ua = $_SERVER["HTTP_USER_AGENT"];
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox$/", $ua)) {
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
        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel , 'CSV')->setUseBOM(true);
        $objWriter->save('php://output');
    }

}
