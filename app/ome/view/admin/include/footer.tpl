<{script src="coms/pager.js"  app='desktop'}>
<{script src="coms/modedialog.js" app="desktop"}>
<{script src="coms/autocompleter.js" app="desktop"}>

<script>
(function(){
<{if $newLogo}>
    $$('#header .logo a')[0].set('html', '<{$newLogo}>');
<{/if}>
<{if $env.conf.desktop.sale_id == 'shopex.erp'}>
<{if $verLogo}>
var e2 = new Element('span');
e2.set('title', "<{$logoMesaage.ver}>");
e2.set('html', "&nbsp;&nbsp;<img src=\"<{$verLogo}>\" />");
e2.inject($$('#header .head-license')[0]);
<{/if}>
<{if $warningLogo}>
var e3 = new Element('span');
e3.set('title', "<{$logoMesaage.warning}>");
e3.set('html', "&nbsp;&nbsp;<img src=\"<{$warningLogo}>\" />");
e3.inject($$('#header .head-license')[0]);
<{/if}>
var el = new Element('span');
var qq_url = "http://b.qq.com/webc.htm?new=0&sid=800094789&eid=2188z8p8p8p808Q8y8z80&o=www.shopex.cn&q=7&ref=+";
var header_qq='&nbsp&nbsp&nbsp&nbsp&nbsp<img  style="CURSOR: pointer" onclick="javascript:window.open(\'http://b.qq.com/webc.htm?new=0&sid=800094789&eid=2188z8p8p8p808Q8y8z80&o=www.shopex.cn&q=7&ref=\'+document.location,\'_blank\', \'height=544, width=644,toolbar=no,scrollbars=no,menubar=no,status=no\')"  border="0" SRC="http://im.bizapp.qq.com:8000/zx_qq.gif">';

el.set('html', header_qq);el.inject($$('#header .head-license')[0]);
<{/if}>
})();

    window.addEvent('domready', setTimeout(function(){
        new Request({url:'index.php?app=ome&ctl=admin_service_info&act=validity',method:'get',
            onComplete:function(json){
                if(!json) {
                    return;
                }
                
                json = JSON.decode(json);
                days = json.days;
                msg = json.msg;
                ensureDay = json.ensureDay
                ensureMsg = json.ensureMsg
                warnDays = 31;
                popDays = 7;
                //用户服务等级
                var userDegree = parseInt("<{$userDegree}>");
                //用户版本
                var verCode = parseInt("<{$verCode}>");
                if(days >0){
                    $('validity_date').addEvent('click', function(){
                        new Dialog('index.php?app=ome&ctl=admin_service_info&act=index',{width:700,height:360, 'title':'服务信息'});
                    });
                }

                if(days < warnDays) {
                    $('validity_date').setStyle('color', '#ffffff');
                    $('validity_date').setStyle('background', '#dc0532');
                    $('validity_date').setStyle('border-radius', '5px');
                    $('validity_date').setStyle('box-shadow', '0 1px 2px #999999');
                    $('validity_date').setHTML(msg);
                }
                else if ((userDegree > 0 && ensureDay < popDays) && verCode < 2 ) {
                    $('validity_date').setStyle('color', '#ffffff');
                    $('validity_date').setStyle('background', '#dc0532');
                    $('validity_date').setStyle('border-radius', '5px');
                    $('validity_date').setStyle('box-shadow', '0 1px 2px #999999');
                    $('validity_date').setHTML(ensureMsg);
                }
                
                if(verCode < 2 && (days < popDays || (userDegree > 0 && ensureDay < popDays)) ) {
                    new Dialog('index.php?app=ome&ctl=admin_service_info&act=alert', {
                        width: 400,
                        height: 200,
                        modal: true,
                        title: '服务提醒'
                    });
                }
            }
        }).send();


    }, 5000));
    window.addEvent('domready', setTimeout(function(){

            new Request({url:'index.php?app=ome&ctl=admin_service_taobao&act=validity',method:'get',
                onComplete:function(json){
                    if(!json) {
                        return;
                    }
                   
                    json = JSON.decode(json);
                    has_expire = json.has_expire;
                    if(has_expire) {
                        new Dialog('index.php?app=ome&ctl=admin_service_taobao&act=alert', {
                            width: 800,
                            height: 200,
                            modal: true,
                            title: '淘宝SESSION过期提醒'
                        });
                    }
                }
            }).send();


    }, 6000));
    window.addEvent('domready', setTimeout(function(){
        if ('<{$session_warning}>' == 'false') {
            alert("<{$logoMesaage.warning_alert}>");
        }
    }, 7000));
</script>
