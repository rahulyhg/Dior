var omeShopExGoodsEditor = new Class({
    Implements:[Options],
    options: {
        periodical: false,
        delay: 500,
        postvar:'finderItems',
        varname:'items',
        width:500,
        height:400
    },
    initialize: function(el, options){
        this.el = $(el);
        this.setOptions(options);
        this.cat_id = $('gEditor-GCat-input').getValue();
        this.type_id = $('gEditor-GType-input').getValue();
        this.goods_id = $('gEditor-GId-input').getValue();
        this.initEditorBody.call(this);
    },
    initEditorBody:function(){         
        var _this=this;
        var gcatSelect=$('gEditor-GCat-input');
        var gtypeSelect=$('gEditor-GType-input');
        
        gcatSelect.addEvent('change',function(e){
            var selectedOption=$(this.options[this.selectedIndex]);
            var typeid=selectedOption.get('type_id')||1;
            var goods_name = $('id_gname').get('value');
            if(typeid!=gtypeSelect.getValue() && goods_name!=''){
				if(confirm('\t重设分类将会丢失当前所输入的相关数据，确定吗？')||this.getValue()<0){
						gtypeSelect.getElement('option[value='+typeid+']').set('selected',true);
						_this.updateEditorBody.call(_this);
				}
            }
            _this.cat_id = this.getValue();
        });
        gtypeSelect.addEvent('click',function(){
           this.store('tempvalue',this.getValue());
        });
        gtypeSelect.addEvent('change',function(e){
            var tmpTypeValue = this.retrieve('tempvalue');
            var goods_name = $('id_gname').get('value');
            //if (goods_name!=''){
				if(this.getValue()){
						_this.updateEditorBody.call(_this);
						_this.type_id=this.getValue();
				}else{
					this.getElement('option[value='+tmpTypeValue+']').set('selected',true);
	            }
           //}
        });
    },
    updateEditorBody:function(options){
		if($('productNode')&&$('productNode').retrieve('specOBJ')){
			$('productNode').appendChild($('productNode').retrieve('specOBJ').toHideInput($('productNode').getElement('tr')));		
		}
	   var parma={
		   update:'gEditor-Body',
		data:$('gEditor').toQueryString(),
		method:'post',
		onComplete:function(callHtml){
			   goodsEditFrame();
       }};
       W.page('index.php?app=ome&ctl=admin_goods_editor&act=update',parma);
    },
    mprice:function(e){
        for(var dom=e.parentNode; dom.tagName!='TR';dom=dom.parentNode){;}
        var info = {};
        $ES('input',dom).each(function(el){
            if(el.name == 'price[]')
                info['price']=el.value;
            else if(el.name == 'goods[product][0][price]')
                info['price']=el.value;
            else if(el.getAttribute('level'))
                info['level['+el.getAttribute('level')+']']=el.value;
        });
        window.fbox = new Dialog('index.php?app=ome&ctl=admin_goods_editor&act=set_mprice',{title:'编辑会员价', ajaxoptions:{data:info,method:'post'},modal:true});
        window.fbox.onSelect = goodsEditor.setMprice.bind({base:goodsEditor,'el':dom});
    },
    setMprice:function(arr){
        var parr={};
        arr.each(function(p){
            parr[p.name] = p.value;
        });
        $ES('input',this.el).each(function(d){
            var level = d.getAttribute('level');
            if(level && parr[level]!=undefined){
                d.value = parr[level];
            }
        });
    },
    spec:{
        addCol:function(s,typeid){	
            this.dialog = new Dialog('index.php?app=ome&ctl=admin_goods_editor&act=set_spec&_form='+(s?s:'goods-spec')+'&p[0]='+typeid,{ajaxoptions:{data:$('goods-spec').toQueryString()+($('nospec_body')?'&'+$('nospec_body').toQueryString():''),method:'post'},title:'规格'});
        },
        addRow:function(){
            this.dialog = new Dialog('index.php?app=ome&ctl=admin_goods_editor/spec&act=addRow',{ajaxoptions:{data:$('goods-spec'),method:'post'}});
        }
    },
    adj:{
        addGrp:function(s){
            this.dialog = new Dialog('index.php?app=ome&ctl=admin_goods_editor&act=addGrp&_form='+(s?s:'goods-adj'));
        }
    },
 
    rateGoods:{
        add:function(){
            window.fbox = new Dialog('index.php?ctl=goods/product&act=select',{modal:true,ajaxoptions:{data:{onfinish:'goodsEditor.rateGoods.insert(data)'},method:'post'}});
        },
        del:function(){
        },
        insert:function(data){
            $ES('div.rate-goods').each(function(e){
                data['has['+e.getAttribute('goods_id')+']'] = 1;
            });
            new Ajax('index.php?ctl=goods/product&act=ratelist',{data:data,onComplete:function(s){$('x-rate-goods').innerHTML+=s}}).request();
        }
    }
});
