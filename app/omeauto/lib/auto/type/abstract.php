<?php
/**
 *
 */

abstract class omeauto_auto_type_abstract{
    
    /**
     * 保存规则内容
     * 
     * @var Array
     */
    protected $content;
    
    /**
     * 获取输入UI
     * 
     * @param mixed $val
     * @return String
     */
    public function getUI($val) {
  
        $tpl = kernel::single('base_render');
        $role = array_shift($val);
        $tpl->pagedata['role'] = $role;
        $tpl->pagedata['init'] = json_decode(base64_decode($role), true);
        if (method_exists($this, '_prepareUI')) {
            
            $this->_prepareUI($tpl);
        }
        return $tpl->fetch($this->getTemplateName(), 'omeauto');
    }
    
    /**
     * 获取用于输出的模板名
     * 
     * @param void
     * @return String
     */
    public function getTemplateName() {
        
        $className = strtolower(get_class($this));
        $className = str_replace('omeauto_auto_', '', $className);
        $fileName = sprintf('order/%s.html', str_replace('_', '/', $className));

        return $fileName;
    }
    
    /**
     * 创建规则
     * 
     */
    public function createRole($params) {
    
        $result = $this->checkParams($params);
        
        if ($result === true) {
            
            return json_encode(array('code' => 'SUCC', 'msg' => $this->roleToString($params)));
        } else {
            
            return json_encode(array('code' => 'FAIL', 'msg' => $result));
        }
    }
    
    /**
     * 设置已经创建好的配置内容
     * 
     */
   public function setRole($params) {
       
       $this->content = $params;
   }
}