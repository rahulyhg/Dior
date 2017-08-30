<?php
/**
 * 插件接口类
 *
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */

abstract class ome_extracolumn_abstract {

    /**
     * 传入的关联主键id数组
     */
    private $__Ids = array();

    /**
     * 传入的列表页数据数组
     */
    private $__params = array();

    /**
     * 关联数据数组
     */
    private $__formatData = array();

    /**
     * 初始化相关参数
     * @param Array $params 要处理的列表数组数据
     */
    public function init($params){
        if(is_null($this->__Ids) || count($this->__Ids) <=0){
            foreach($params as $k => $param){
                $this->__Ids[] = $param[$this->__pkey];
            }
        }
        $this->__params = $params;
    }

    /**
     * 转化关联的实际数据输出
     */
    public function outPut(){
        foreach($this->__params as $k =>$_param){
            if(isset($this->__formatData[$_param[$this->__pkey]])){
                $this->__params[$k][$this->__extra_column] = $this->__formatData[$_param[$this->__pkey]];
            }else{
                $this->__params[$k][$this->__extra_column] ='';
            }
        }
        return $this->__params;
    }

    /**
     * 处理列表数组里的扩展字段
     * @param Array $params 要处理的列表数组数据
     * @return Array 转换扩展字段后的列表数据
     */
    public function process($params){
        $this->init($params);
    
        $this->__formatData = $this->associatedData($this->__Ids);

        return $this->outPut();
    }
}