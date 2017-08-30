<?php
// define('BASE_URL','http://localhost/erp.trunk.localhost');
class amqp extends PHPUnit_Framework_TestCase
{
    function setUp() {}
    

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function testamqp()
    {
echo date('Y-m-d H:i:s');exit;
                    $push_params = array(
                        'data' => array(
                            'log_id'     => $logi_id,
                            'task_type'  => 'autoretryapi',
                            'exectime'   => (time() + 600),
                            'obj_bn'     => $obj_bn,
                            'obj_type'   => $obj_type,
                            'method'     => $method,
                        ),
                        'url' => kernel::openapi_url('openapi.autotask','service')
                    );
                    $flag = kernel::single('taskmgr_interface_connecter')->push($push_params);
    }
}
