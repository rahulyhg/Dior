<?php
class addbarcode extends PHPUnit_Framework_TestCase
{
    function setUp() {
    
    }
    
    public function testAddbarcode(){
        $path = ROOT_DIR."/app/ome/testcase/";
        $log = $path."addbarcode.log";
        error_log("Addbarcode begin.......:\n",3,$log);
        $handle = fopen($path.'zyc_barcode.csv','rb');
        if($handle){
            $db = kernel::database();
            
            $i = 0;
            
            fgets($handle); //get first line title
            while(!feof($handle)){
                $i ++;
                $buffer = fgets($handle,4096);
                if(trim($buffer)){
                    $data = explode(",",$buffer);
                    if(count($data) != 2){
                        error_log("Wrong data:\n".print_r($data,true)."\n\n\n",3,$log);
                    }else{
                        $bn = trim($data[0]);
                        $barcode = trim($data[1]);
                        
                        if($barcode){
                            $db->exec("UPDATE sdb_ome_products SET barcode='".$barcode."' WHERE bn='".$bn."'");
                        }else{
                            error_log("bn:".$bn." has no barcode\n\n\n",3,$log);
                        }
                    }
                }
            }
            
            fclose($handle);
            error_log("Addbarcode end!\n",3,$log);
        }else{
            error_log("Can not open zyc_barcode.csv!",3,$log);
        }
    }
}
