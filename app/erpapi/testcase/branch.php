<?php
// define('BASE_URL','http://localhost/erp.trunk.localhost');
class branch extends PHPUnit_Framework_TestCase
{
    function setUp() {}
    

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function testbranch()
    {


        // $_SERVER['HTTP_X_FORWARDED_HOST'] = 'erp.trunk.localhost';
        // $this->push();
        //$this->callback();
        $result = kernel::single('erpapi_router_request')->set('wms',8)->branch_getlist(null);
    }
}
