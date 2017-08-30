<?php
// define('BASE_URL','http://localhost/erp.trunk.localhost');
class reship extends PHPUnit_Framework_TestCase
{
    function setUp() {}
    

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function testreship()
    {


        // $_SERVER['HTTP_X_FORWARDED_HOST'] = 'erp.trunk.localhost';
        $this->push();
        // $this->callback();
    }

    public function push()
    {
        kernel::single('console_reship')->notify_reship('cancel',1);
    }


}
