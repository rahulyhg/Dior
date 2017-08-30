<?php
/**
 * 插件接口
 *
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */

interface ome_extracolumn_interface {

    /**
     * 定义要执行数据处理的主键ids
     *
     * @param Array $group 要处理的列表数组数据
     * @return Array
     */
    public function init($params);

    /**
     * 处理字段数据组信息
     *
     * @param null
     * @return Array
     */
    public function process($params);

    /**
     * 转换相应字段的内容
     *
     * @param null
     * @return Array
     */
    public function outPut();
}