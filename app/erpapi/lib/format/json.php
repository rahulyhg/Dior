<?php

class erpapi_format_json extends erpapi_format_abstract{
    
     public function data_encode($data){
        return json_encode($data);
     }

     public function data_decode($data){
        return json_decode($data,true);
     }
}