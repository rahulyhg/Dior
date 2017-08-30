<?php
class resrefund extends PHPUnit_Framework_TestCase
{
    function setUp() {
        
        
    }
    public function testResrefund(){
        
        $service = 'api.ome.refund';
        $method = '';
        $params = array(
            
        );
        $type = apibusiness_router_mapping::getRspServiceMapping($service);
        $result = kernel::single('apibusiness_router_response')->setRespservice($this)->dispatch($type,$method,$params);
    }
}

?>