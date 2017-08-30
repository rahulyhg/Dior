<?php
/**
* 规则应用处理类
*
* @author chenping
* @version 2012-6-7 19:23
*/
class inventorydepth_regulation_apply
{

    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 存储店铺商品关系条件
     *
     * @return void
     * @author
     **/
    public function store_merchandise_filter($key,$value)
    {
        base_kvstore::instance('regulation/apply/merchandise')->store($key,$value,(time()+86400));
    }

    /**
     * @description 删除店铺商品关系条件
     * @access public
     * @param void
     * @return void
     */
    public function destory_merchandise_filter($key) 
    {
        base_kvstore::instance('regulation/apply/merchandise')->store($key,'',(time()-1000));
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function fetch_merchandise_filter($key)
    {
        base_kvstore::instance('regulation/apply/merchandise')->fetch($key,$data);

        return $data;
    }

    public function choice_callback_array($condition,$init_bn,$merchandise_filter=array()){
        if (isset($merchandise_filter['advance'])) {
            $merchandise_filter = preg_replace('/(,)/','&',$merchandise_filter['advance']);
            parse_str($merchandise_filter,$merchandise_filter);          
            $this->store_merchandise_filter($init_bn,$merchandise_filter);
        }else{
            $this->store_merchandise_filter($init_bn,'');
        }

        $tt = ($condition=='stock') ? '货品' : '商品';

        $t_url = 'index.php?app=inventorydepth&ctl=regulation_apply&act=merchandise_dialog_filter&init_bn='.$init_bn.'&condition='.$condition;
        $url = 'index.php?app=desktop&act=alertpages&goto='.urlencode($t_url);

        if ($merchandise_filter) {
            $rs = <<<EOF
            已选择{$tt}条件
            <a href='javascript:void(0);' onclick='var _handle=this.getParent(".gridlist");
            Ex_Loader("modedialog",function(){
                filterMDialog = new Class({
                    Extends:ModeDialog,
                    options:{
                        onHide:function(value){
                            var params=this.options.params;
                            var data = params.postdata;
                            data += value ? "&filter[advance]="+value : "";
                            new Request({url:params.url,onSuccess:function(rs){
                                 this.fireEvent("callback",rs);
                            }.bind(this)}).send(data);
                        }
                    },
                    submit:function(win){
                        var f = this.doc.getElement("div[id^=filter-list-]");
                        var qstr = f.toQueryString(
                            function(el) {
                                var elp = $(el).getParent("dl"),m;

                                if(!elp||!elp.isDisplay()||!!!$(el).value)return;

                                if(m = el.name.match(/_([\s\S]+)_search/)){
                                    if(!!!f.getElement("*[name="+m[1]+"]").value)return;
                                }

                                if(el.name.match(/_DTYPE_TIME/)){
                                    if(!!!f.getElement("*[name="+el.value+"]").value)return;
                                }

                                if(m=el.name.match(/_DTIME_\[([^\]]+)\]\[([^\]]+)\]/)){
                                    if(!!!f.getElement("*[name="+m[2]+"]").value)return;
                                }
                                return true;
                        },true);
                        qstr = qstr.replace(/(&amp;)/g,",");
                        qstr = qstr.replace(/(&)/g,",");
                        win.returnValue=qstr;
                    }
                });
                new filterMDialog("{$url}",{
                    params: {
                        url: "index.php?app=inventorydepth&ctl=regulation_apply&act=merchandise_filter_array",
                        postdata:"condition={$condition}&init_bn={$init_bn}"
                    },
                    onCallback: function(data) {
                    
                        data = JSON.decode(data);
                        if (data.error) {
                            MessageBox.error(data.error);
                            return;
                        }
                        _handle.setHTML(data.success);
                        
                    }
                });
            });

            '>查看选中{$tt}条件</a>
EOF;
        }else{
            $rs = '请选择条件';
        }
        
        return $rs;
    }

    /**
    * 选择商品/货品callback处理
    * @access public
    * @param String $condition 规则类型:stock库存更新(针对货品) frame商品上下架(针对商品)
    * @param String $init_bn 规则编号
    * @param Array $merchandise_id 店铺商品映射关系ID
    * @param Array $merchandise_filter 高级筛选
    * @return 字符串信息展示
    */
    public function choice_callback($condition,$init_bn,$id=array(),$merchandise_filter=array()){

        if($id['id']) {
            $filter = $this->set_filter($init_bn,$id,$merchandise_filter);
        }else{
            $filter = $this->fetch_merchandise_filter($init_bn);
        }

        $tt = ($condition=='stock') ? '货品' : '商品';

        $model = kernel::single('inventorydepth_regulation')->get_condition_model($condition);

        $number = $this->app->model($model)->count($filter);
        if ($number == 0) {
            $this->store_merchandise_filter($init_bn,'');
        }

        $t_url = 'index.php?app=inventorydepth&ctl=regulation_apply&act=finder_choice&init_bn='.$init_bn.'&condition='.$condition;
        $url = 'index.php?app=desktop&act=alertpages&goto='.urlencode($t_url);
        if($number) {
            $rs = <<<EOF
            已选择了{$number}个{$tt}
            <a href='javascript:void(0);' onclick='var _handle=this.getParent(".gridlist");
            Ex_Loader("modedialog",function(){
                    bFinderDialog=new Class({
                        Extends:finderDialog,
                        options:{
                            onHide:function(value){
                                value = value ? value : [];
                                 var tmpForm=new Element("div"),fdoc=document.createDocumentFragment();
                                    var params=this.options.params;
                                    for(var i=0,l=value.length;i<l;i++){
                                     fdoc.appendChild(new Element("input",{type:"hidden",name:params.name,value:value[i]}));
                                }
                                tmpForm.appendChild(fdoc);
                                var data=(params.postdata)?tmpForm.toQueryString()+"&"+params.postdata:tmpForm.toQueryString();
                                new Request({url:params.url,onSuccess:function(rs){
                                      tmpForm.destroy();
                                      if(params.type)this.options.select(params,rs,value);	
                                      this.fireEvent("callback",rs);
                                }.bind(this)}).send(data);
                            }
                        }
                    });
                    new bFinderDialog("{$url}",{
                        params: {
                            url: "index.php?app=inventorydepth&ctl=regulation_apply&act=merchandise_filter",
                            postdata:"condition={$condition}&init_bn={$init_bn}"
                        },
                        onCallback: function(data) {
                            data = JSON.decode(data);
                            if (data.error) {
                                MessageBox.error(data.error);
                                return;
                            }
                            _handle.setHTML(data.success);
                        }
                    });
            });'
            ><span>查看选中{$tt}</span></a>
EOF;
        }else{
            $rs = "请选择{$tt}";
        }

        return $rs;
    }

    /**
    * 存储过滤条件
    * @access public
    * @param String $init_bn 规则编号
    * @param Array $merchandise_id 店铺商品映射关系ID
    * @param Array $merchandise_filter 高级筛选
    *
    * @return Array $filter 返回处理过的过滤条件
    */
    public function set_filter($init_bn,$id=array(),$merchandise_filter=array()){
        # 存储高级筛选条件
        if($id['id'][0] == '_ALL_') {
            if($merchandise_filter['advance']) {
                $merchandise_filter = preg_replace('/(,)/','&',$merchandise_filter['advance']);
                parse_str($merchandise_filter,$merchandise_filter);
            }

            $filter = array_merge($id,$merchandise_filter);
        }else{
        # 存储映射ID
            $filter = $id;
        }
        $this->store_merchandise_filter($init_bn,$filter);

        return $filter;
    }
}