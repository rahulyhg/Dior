<?php

/**
 * 订单获取
 */
class ome_ctl_admin_order_auto extends desktop_controller {

    /**
     * 订单获取模块名称
     * @var String
     */
    const __ORDER_APP = 'ome';

    /**
     * 获取订单，其中隐藏自动确认规则
     *
     * @param void
     * @return void
     */
    function index() {
        if(app::get('replacesku')->is_installed()){
            $oOrders = app::get('ome')->model('orders');
            $order_list = $oOrders->getlist('order_id',array('is_fail'=>'true'));
            $order_list_count = count($order_list);
            $sku_tran = new replacesku_order;
            echo '共有符合条件的待转换订单数:'.count($order_list).'条记录<br>';
            $tran_mess=$sku_tran->transform_sku($order_list);
            echo '失败订单:'.$tran_mess['fail'].' 成功:'.$tran_mess['succ'].' 其它:'.$tran_mess['other'];
        }
        $orderAuto = new omeauto_auto_combine();
        $orderGroup = $orderAuto->getBufferGroup();

        $orderCnt = 0;
        $orderGroupCnt = 0;
        $orderGroupOrdCnt = 0;

        //计数
        foreach ($orderGroup as $group) {
            if ($group['cnt'] > 1) {
                $orderGroupCnt++;
                $orderGroupOrdCnt += $group['cnt'];
            }
            $orderCnt += $group['cnt'];
        }

        $bufferOrderCnt = app::get('ome')->model('orders')->count(array('order_confirm_filter' => '(op_id IS NULL AND group_id IS NULL AND (is_cod=\'true\' or pay_status in (\'1\',\'4\',\'5\')))', 'status' => 'active', 'ship_status' => '0', 'f_ship_status' => '0', 'confirm' => 'N', 'abnormal' => 'false', 'refund_status' => 0, 'is_auto' => 'false', 'is_fail' => 'false'));

        $this->pagedata['bufferTime'] = omeauto_auto_combine::getCnf('bufferTime');
        $this->pagedata['bufferOrderCnt'] = $bufferOrderCnt;
        $this->pagedata['orderCnt'] = $orderCnt;
        $this->pagedata['orderGroup'] = json_encode($orderGroup);
        $this->pagedata['orderGroupOrdCnt'] = $orderGroupOrdCnt;
        $this->pagedata['orderGroupCnt'] = $orderGroupCnt;
        #全境判断
        $all_dlycorp = kernel::single('logistics_dly_corp')->fetchDefaultRoles();
        #是否提醒
        $allDlycorpnotify = app::get('ome')->getConf('allDlycorp.status');

        /* 操作时间间隔 start */
        $lastGetOrder = app::get('ome')->getConf('lastGetOrder'); //上次获取订单信息(key为execTime表示执行时间，key为pageBn表示页面编号),
        $getOrderIntervalTime = app::get('ome')->getConf('ome.getOrder.intervalTime'); //每次操作的时间间隔

        if(($lastGetOrder['execTime']+60*$getOrderIntervalTime)<time()){
            $this->pagedata['is_allow'] = true;
        }else{
            $this->pagedata['is_allow'] = false;
        }
        $this->pagedata['lastGetOrderTime'] = !empty($lastGetOrder['execTime']) ? date('Y-m-d H:i:s',$lastGetOrder['execTime']) : '';
        $this->pagedata['getOrderIntervalTime'] = $getOrderIntervalTime;
        $this->pagedata['currentTime'] = time();
        /* 操作时间间隔 end */

        $shopList = app::get('ome')->model('shop')->getList('*');
        $this->pagedata['shopList'] = $shopList;

        $this->pagedata['allDlycorpnotify'] = $allDlycorpnotify;

        $this->pagedata['all_dlycorp'] = $all_dlycorp;

        //增加所需配置检查
        $config = $this->chkConfig();
        if(count($config)>0){
            $this->pagedata['config'] = $config;
        }
        $this->display('admin/order/auto.html');
    }

    /**
     * 检查自动审单的配置
     *
     * @param void
     * @return void
     */
    private function chkConfig() {
        $result = array();
        //检查订单分组
        $otCount = app::get('omeauto')->model('order_type')->count(array('disabled' => 'false'));
        if ($otCount == 0) {
            $result['noOrderDefined'] = true;
        }
        //检查自动确认规则
        $acCount = app::get('omeauto')->model('autoconfirm')->count(array('defaulted' => 'true','disabled' => 'false'));
        if ($acCount == 0) {
            //没有设置审单规则，自动增加默认规则
            $result['noAutoConfirmDefaultRole'] = true;
        }
        /*else if (!$this->chkDefaultAutoConfirmRole()) {
            //有设置审单规则，但没有增加默认规则，自动增加
            $result['noAutoConfirmDefaultRole'] = true;
            $result['aId'] = $this->addDefaultAutoConfirmRole();
        }*/
        //检查自动分派规则
        /*$adCount = app::get('omeauto')->model('autodispatch')->count(array('disabled' => 'false'));
        if ($adCount == 0) {
            $result['noAutoDispatchRole'] = true;
        }*/
        //检查仓库分配规则
        $abCount = app::get('ome')->model('branch')->count(array('disabled' => 'false', 'online' => 'true'));
        if ($abCount == 0) {
            //没有设置线上仓库
            //$result['noDlyConfig'] = $this->chkDlyCorp(true);
            $result['noAutoBranchRole'] = true;
        }
        /*else if ($abCount == 1){
            //一个线上仓库
            //默认发货，可不提醒
            $result['noDlyConfig'] = $this->chkDlyCorp(true);
        } else {
            //有多仓
            $result['noAutoMutiBranchRole'] = true;
            $result['noDlyConfig'] = $this->chkDlyCorp(false);
        }*/

        return $result;
    }

    /**
     * 检查物流公司
     *
     * @param boolean $singleBranch
     * @return void
     */
    private function chkDlyCorp($singleBranch) {

    }

    /**
     * 检查是否有默认审单规则
     *
     * @param void
     * @return void
     */
    private function chkDefaultAutoConfirmRole() {

        $config = omeauto_auto_group::fetchDefaultRoles();

        if (empty($config)) {

            return false;
        } else {

            return true;
        }
    }

    /**
     * 增加默认自动审单规则
     *
     * @param void
     * @return void
     */
    private function addDefaultAutoConfirmRole() {

        $config = omeauto_auto_group::getDefaultRoles();
        $sdf = array(
            'name' => '默认审单规则',
            'config' => $config,
            'memo' => '默认审单规则',
            'disabled' => 'false',
            'defaulted' => 'true'
        );
        app::get('omeauto')->model('autoconfirm')->save($sdf);

        return $sdf['oid'];
    }

    /**
     * AJAX 调用过程，用来处理指定数量的订单组
     *
     * @author hzjsq (2011/3/24)
     * @param void
     * @return void
     */
    function ajaxDoAuto() {

        //$_POST['ajaxParams'] = '9188147adbc5f9fec60b6954b372008d||34386258||4593,4585;b6ebedcf3f4731b0e9bbf54d734b5458||2342460039||4610;25c960337889863a395e738f2bd52e7e||4120870759||4695;ab0681ff1230e99dc4a1b62cde5e4348||925419292||4597;82879d1978cc3340a1b2b00b70f5665c||22417073||4587;195fc2a36ff7d0ef0df81990a06c6bbb||1581938594||4645;e2d4b8ee6e8037b82409b5dd606ae043||4088183090||4576;ff79c7e442fdd1a9daa3cee090768f54||1397696457||4603;9f0c7e21def66d95568ee467ddce4d68||1278968609||4578;8f67082a3ac57b55a71ec3d49cabe760||4230719483||4622;0930c4c1d95181c12235f1d4f10fc775||3914270256||4588;5e42650d81ff796ebc6055d5bccbaa87||4038218342||4577;55be70fff231a78f797897f788d9c6e0||3863421435||4581;a4ab52ab1e50a396dab53d50fce5c963||1067387843||4568;d3d59469d0e42f582bf9bcf9c837b1a2||2485585540||4574;bb1a659a2c18d2d2f26b9c9aed706f99||15103333||4573;6f3af4fa5c863ad03f1b6b665a90978d||718201326||4617;3ce81972b502c69012c196e0d2bc89d0||4143205577||4559;c4b6799ddebb0b0948b82b8f70e50217||3376795406||4562;3de4ae5cd724257df3312b7cd7f2c5d9||424650450||4571;a07836e959e36ef7d820ea7547a6c20b||504591290||4564;c8b91522162d4a60c29b88c747186bdc||1234305942||4602,4600,4599,4606,4607,4601;3badc48bf20017152300df953521e116||994136834||4551;70e67aae131b0aea8b4f465ad72fd078||801243848||4544;925d501aacb5f89bee2a3837d7f78e18||1401305915||4567,4538;dce4d30daa07592d2f53dcfe9250a586||3535344562||4555;49e982bc30c75d755ddf821589a690af||1122475138||4546;18173838e1f0aac633641a82eca0ece2||778623590||4550;9340fdfd844bc074d3e51a05b4e7ab9f||2726277404||4552,4509;456c4e7205a92a32320f7cd8fdc70a3d||4061502290||4547;85328e8bcfad7a889afadb83ba15406d||875863429||4557,4539,4516,4507;20b34879a5f19f11eac6dfcc6f4686d3||2873048713||4560,4563;f4c32e9e1cc4f27f8a0a5055b4bebf34||3446277176||4525;e94c5720b8912085cf09280e5eb77844||1917032608||4569;faa40060e81f9da3cc22296874392f12||3137551739||4531;5f9a53e818ba62269083d8793b3b14d6||1216527224||4532;5cb6af337b3fa81279dcef62d74a194e||3842790849||4543;d36a57834ed06d0d2e94772113aaa952||2111126793||4530,4452,4450,4398,4379;53a52e5800a628dac4da9052b0d897a0||4193388532||4529;28598a0cc28f2cf442e3181d9524c399||893002501||4534;c43ea6c1ff825a0e8e3244c4fb21abdf||1286485585||4520;7a6930dc7c7a45d9a822f785e2589294||2714996695||4523;036998fd2c4b28a2b695fe38222e96df||356585465||4542;6475597af9da3017125bfc7ec27a2e8d||1145689084||4521,4517;04a8aff4138222ffc8ff3d3376104451||863091670||4508;be5158b035a79938a0b4087050481829||1627123680||4499;7279fb496ce512d2562fd99e0c185646||842894259||4502;9bf186f2d7170171ab030ee2f2a53ce3||1529935419||4500;bad25beddafe2bcb1663a595e0796ad0||583893921||4489;ea047adb63277507769b3ce2cbec39ec||2496618488||4503;18e47a5b165f89f59281fd004015db4a||1756363751||4498,4505,4497;39d1fae7676162dee4c95c5d32c61860||1913956696||4512,4515,4584;8259dc3c11a647a50d9c89a77a9cafe8||568321373||4773;c8f1257ff728f877cbd081bdfdcc0405||124959205||4481;fc04486cf98add0dffea3337627b7339||4008584455||4493;22a44d4fd0aff8aa36811c7fc70ac478||1790927992||4482;cb2def42553e356585cc7637f7940139||3513977750||4490;b073739517eace0c44352af77ec6c5a9||3871882482||4483;d944ed3ba0059707ea08605889f9c626||2066875433||4474;e4b0272f0aed3eb8ebcf46a7b892a6cf||3977921245||4487;ae63a5ec6735b28d99bbf2729980337c||2267199356||4484;af14afe07f3f1402daf9641729339f7a||876738119||4480;753da3ace1fb211ea33e0eb9882b1a3f||2465025565||4471;f69d1d1b4661c14b145c3d6d19487012||332621740||4473;d930aa91ea32dea003d108b68b98bb7a||434246616||4745;66ef30395843aefd6bf62a3dc3f71355||1135026330||4468;b3e1111b8453297c0bfc646727b518b4||2056329638||4469;fbea2553289208b21efd5dbc6b6f7062||131354773||4460;35be308a8d481a6fb7b91d01d669df75||1678074376||4463;afbbb82cc27e0d6e34a4d6d0c3193b10||3976580794||4461;0f78fd9989e77dde20b3cf05dad73ac7||2712997713||4462;c4b9e90a471897251299eb39afb0c08d||1931797976||4459,4453,4454;2d6e619c008a3eea6db563d0d9a55c35||322251526||4467;ab78dc4a3ad82f5b2cef014955c56d7f||1732049628||4456;69fe404b9f45658d6d4f3c9728a8c333||3370302108||4455;eb356aaaf1cf3db5efa0ab43d0ce6b3c||1863920135||4438;2934558bc262655a1ea7b582365ddabb||635669202||4444;a8610a9a58047424f5e9c4e177f352fa||4074075811||4441,4447,4435,4434,4440,4451,4439,4449,4442,4445,4433,4436,4437;0a42e76830ecdb6f772daed630f4f39e||477852499||4448;10814a4d4862d9b41d834115ed0d90c1||3963118525||4430,4337;b5045fda552617b31c4fd0a11edfa9e6||3049198145||4414;1ecf18f3d064ff7e78b7eb66078ef639||907073720||4478;3429260bc9975032cfe7e2f831a372a5||2294362643||4431;3deb9d864dfc33bc7832cc2e26a028fd||868421709||4417;ecbd098fa98b799529a9b7a9a393ce4a||2994625652||4405;286641185ec67b00070b4ccb7f7a29bb||1247619607||4408;0cba1dfa0f6417b3f31efc295f96d9e6||2564658517||4419;ec68db5cd3a3c576ab84e72db5115615||2810040799||4626,4634,4636;694f1e711a160280582126143fc6efd7||3297670645||4411;cce7d96c652910c418f5bbc24aa28c46||173199629||4404;6b96945d47c58a5aeea69bacb40ad694||1558223581||4400;89e2f53c94429e89e30ef5968f863594||2236870628||4396;83e17997f2df1abbda007b4664be6eb0||716628646||4394;a81430cc24357735983f278eb0447e0c||3848649758||4393;0b81be5c8627a59ecad3b58217af2e82||2853029438||4429;557fa600a8a9119e3127e1c54d395ccf||1954589566||4387;cf45d74e595514a057adc970c6b425b2||3902870659||4410;2c12c4a06c2f0219835054f774da3bb4||2363697399||4381;a2224a189d81815e4de89d9f67b8bc36||149258503||4380;b9c70e48a3805c2f11a487ebadf7a216||2266047797||4384,4363;8574fb384bab5a0eda04276d7134df38||4058281288||4395;dab45ef06ab0023042a483dccebe864b||1560362843||4376;6eafb6a3e7c01a3530ce4466829fdb82||818174268||4369,3163;c58ff0045d2ec4dc13b896bebefe46fe||6001045||4370;4e1656a8a427c7d8e4729e5632497a5f||2227141168||4365;1b1c7107c2bd2866756fc5fa507b92ca||637796879||4364,4368;98d1ecde2ed8d7739f3c9dce6b785b2c||662260348||4391;7ce99aca3a42c773cadcf2903c2a2bc4||347261539||4359;87a050c7d1e031ab50db6adbb6976a93||1468566977||4353;f76e5a0fc82e875b9a2334a8425457a2||26714694||4354;8ef40d535536fe6d3f9f98f17d98c44f||551343513||4361;1680d221d85364b0e6975c265bdab43e||3528263302||4346;bcce89a6db5a8a7960cbc89ea196891f||1484898479||4339;dfe583cf68056645251209f8cf4d7613||204517978||4362;712937b137d7e940c29d65e8c82169f1||61355970||4345;466488f58a65b054a10c7516946f7c7a||1992382844||4358;43270db3a2855d06a5fed04fa785e57c||1159535100||4356,4355;1cfa808a843bb548d65d06ddad0d951a||2997086808||4344;d4315f7b69800af6a017f4a4bbc41a82||1301165329||4392,4383;1717344dc5a3a7ef6d8e38f4948b6a86||3633603658||4321;7e45dbdc6e52293a8d32e66638d7541e||1960757392||4397;c3a01e07dede8d51ce31e06f85e7182b||630601108||4325;aa09c8c441dcc80cf3ba1a616a78b4b1||870228829||4322,4335;e1ad579837493f9528d9ed734f1610a5||1352479286||4324;689fb7715303a7d4ca2d4cd2142a63dc||301351562||4330;fa1669189db29d9425eaa33808c374a9||320223373||4320,2216;5041f8773d0761f2ef97f587b3036aeb||2012571091||4328,4326;985b7f0eb800232a4f8fa47a274b1303||1586646452||4327;30f9df65a7b5b21f1f54500387ac2b39||3167496238||4313;b122511c30fab124d4dd19f45da2bcdd||1011476802||4315;300b6f5f9647e1e1a1d9ea1ddf51cbb7||3752223373||4308;e595a435e80ac4c2a45cc26e00049dc9||2081445100||4348;c1f99e77ecbbf82d8b7340733753782e||1070821119||4323,3095;0f8a8b2bfca89a3d6fc7e0fcbc4d3dd5||1142209818||4351;d128706d5f3d39d8c160fa19d7e40d09||1032587163||4304;0d27ce8e59f13493e336d08f27d4e1c4||2829714360||4466;081805d0e2205667c677949c5b1863fd||715565416||4382,4386;02dc9ba95213afb05ea9375471960894||781666595||4334;da939db601ffb0f35039376aa0857142||3875958690||4307;63aebeb27768263c5c35c05f902c3874||2686697180||4350;be06d663b34cd72767d4766abd6ba6de||441510973||4298;89975b9cfd2a5ffe0f6c4ef463db2f39||1115042001||4297;7f183a1148e7ac8b9168acc7aaa1d142||212987534||4292;87a375a8b2a8c58be74f1564be6dcc0a||1936878736||4295,4272,4266,4277;9c640cb4920e52907daed596cfac782f||3617519742||4291;06a8b6eff49b3f4d84ed040225f4fb70||2829027697||4750,4156;603cc26f8bd422fa508f11a8769049e7||1104213212||4282;459176902d793fc0b694e6f091eee325||374369178||4284,4287,4270;198c02e7fded8f62535f9e61a8f82aee||3018496200||4281;57a35cd9c67a2bb47acaf6ef37f003b0||3303284752||4283;6733d7226e10bf5a17c1f002e6537c63||2327524955||4288;d18f16ba80831b4f4dd2b08e30f0ee23||2353654590||4275;ad358d957072fc2d1ef7a9128ebf453d||1039337203||4273;253eade8104b99855a3becccad80544a||3826273491||4280;54d2d43438bfe476ec2dd064d482ad6f||3177927017||4263;d56c363ab3d18e11fc51d682dae3fc87||727907351||4268;a12c814ff5e3e594f80f18a66f07eb79||2290871840||4276;6f813db8b3192035f2954fe172c328f4||3671351150||4267;76e8d9192fb7b5636fc8a8469b098f9a||1805298312||4265;34ff825c88994ad5b3488dc53c1e2b37||2737412945||4258;c3e155ea027719afa6fdcd59be2afdf8||1439775118||4259;6c13351e93a7ff31301213c54662762a||4255974881||4254;27da6cccfbfb866f29a8a6268423c5bd||900297961||4264;d68fc8a609a3e1230e26de8fa61db586||3698346413||4260;141d93995003216a00287c73dc83bfef||2748554298||4262;4ef0d61ba4bab741d904d798b21cd61f||1891007247||4252,4239;44612efff4a78d10c41485a8a883027f||3155480930||4251;0716bcbb5cad0cda0edac2be941601b0||1806323042||4285;e4615fde9db7cb73f75102f6b9e86824||1723766241||4247,4226,4219,158,157;01df59d80c93fe2712187f380e46a6ee||420617394||4245;3770f5aba678ef6d53bff073c89fdbca||254372948||4238;3cfc7de9bb7aebc0a1c0dfe3c763163d||3273855889||4240;1a45fd87937e9e7c32409adbbc5c6e5a||3494363067||4241;dd8d628b6a9db9c06b3cc559240cb90d||1096211530||4269;267134401643bddf9d1a4e0bd7af5b55||3937347023||4232;eaa0cd642a40601fba1f7024b6c41c96||4108425782||4231;02d96574e20f1a712d9c2f979034bbfe||977425282||4229;4691778f5ec6e3ecf7cd79001a14cc19||253259067||4230;448407a708c85ec9a9cecba71babb4d9||1838965610||4242,4234;8658a619584428112766f3138d794648||3941335179||4228;44f61c9c5a8528162ab24dfbf49ec3bd||88025286||4218;21769d851de18ee8f9c6395d18076776||2906634660||4286;d248b66b713e6ed14911bff369ba995e||2122620602||4212;5be76b89e192fdeb23ad5e1ad1d9d0f3||2695146856||4888,4885,4886;2c9e513ccc7efce7d981f4cd8f18bc63||3986327160||4256,4182;66279e10afe0ce6b4918d5e6e97d10a1||104070700||4589;fc4d0be826bbe7b93bdb39fe05d7ab6f||3119751500||4196;0110dfd3b37110ac2f98ba01796f9ff2||2503070462||4195;b6339dc70bcd181bcd2eedc54cfb3e9a||2433254218||4204;28bd9aeafbfdf4e5041abdc5597a5d2d||3440978694||4709;2e210a8ce5e1420c93b3773f598270ad||2763712754||4207;5801b05c2e18e4e4125c42fac8224e72||3870042352||4235,4236,4244,4248,4253,4261;91f4aac1f0a76e71ba7cdd21953102cf||922394168||4194;da669ae1be631d5959f6d1ea3bca183e||3146448681||4214;8fcfe714603de41109b674e7bcb71979||2755705134||4470;0c23e4568267bed03d079bd1c8ad0bd7||1901864627||4215;2615b1f42d78f2648bda2f991735f7cd||1989009810||4183;96dfbb7509a5b028c9d09dfd08d93f13||2935090478||4184;a591ae58dbdce22292e83ca01a4a82f7||2961321583||4191;35a5cd52ba2b83b30d7438408b70d172||1145823632||4271';
        //获取参数
        //danny_freeze_stock_log
        define('FRST_TRIGGER_OBJECT_TYPE','发货单：获取订单自动生成发货单');
        define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_order_auto：index');
        $params = $this->_parseAjaxParams($_POST['ajaxParams']);
        if (empty($params)) {
            echo $this->_ajaxRespone();
            exit;
        }

        /* 执行时间判断 start */
        $pageBn = intval($_POST['pageBn']);
        $lastGetOrder = app::get('ome')->getConf('lastGetOrder'); //上次获取订单信息(key为execTime表示执行时间，key为pageBn表示页面编号),
        $getOrderIntervalTime = app::get('ome')->getConf('ome.getOrder.intervalTime'); //每次操作的时间间隔

        if($pageBn !=$lastGetOrder['pageBn'] && ($lastGetOrder['execTime']+60*$getOrderIntervalTime)>time()){
            echo $this->_ajaxRespone();
            exit;
        }
        if($pageBn !=$lastGetOrder['pageBn'] && $pageBn<$lastGetOrder['execTime']){
            echo $this->_ajaxRespone();
            exit;
        }

        //记录本次获取订单时间
        $currentGetOrder = array(
            'execTime'=>time(),
            'pageBn'=>$pageBn,
        );
        app::get('ome')->setConf('lastGetOrder',$currentGetOrder);
        /* 执行时间判断 end */

        //订单预处理
        $preProcessLib = new ome_preprocess_entrance();
        $preProcessLib->process($params,$msg);

        //开始自动确认
        $orderAuto = new omeauto_auto_combine();
        //开始处理
        $result = $orderAuto->process($params);
        echo $this->_ajaxRespone($result);
    }

    /**
     * 解析AJAX传过来的信息
     *
     * @param String $string 原始内容
     * @return mixed
     * @author hzjsq (2011/3/25)
     */
    private function _parseAjaxParams($string) {

        $string = trim($string);
        //分解成数组
        if (strpos($string, ';')) {

            $params = explode(';', $string);
        } else {

            $params = array($string);
        }

        //继续分解成可以处理的数组内容
        $result = array();
        foreach ($params as $key => $param) {

            $tmp = explode('||', $param);

            $result[] = array('idx' => $tmp[1], 'hash' => $tmp[0], 'orders' => explode(',', $tmp[2]));
        }
        return $result;
    }

    /**
     *  对输入的内容进行格式化输出至AJAX
     *
     * @author hzjsq (2011/3/24)
     * @param Mixed $param 要转换的内容
     * @return String
     */
    private function _ajaxRespone($param) {

        if (empty($param)) {

            return json_encode(array('total' => 0, 'succ' => 0, 'fail' => 0));
        } else {

            return json_encode($param);
        }
    }

    /**
     * 匹配所有未对应的商品
     *
     * @param void
     * @return void
     */
    public function product() {

        echo "<h1>正在开发中……</h1>";
    }

    public function notify_allDlycorp(){
        $this->page('admin/order/notify_allDlycorp.html');
    }

    /**
    * 不再提醒
    */
    function notify(){
        $is_super = kernel::single('desktop_user')->is_super();
        if($is_super){
            echo app::get('ome')->setConf('allDlycorp.status', 2);

        }
    }

    /**
     * 获取自动处理的数据
     */
    public function ajaxGetAutoData(){
        $filter['shop_id'] = $_POST['shopId'];

        $orderAuto = new omeauto_auto_combine();
        $orderGroup = $orderAuto->getBufferGroup($filter);

        $orderCnt = 0; //本次操作订单
        $orderGroupOrdCnt = 0; //合并前的订单数
        $orderGroupCnt = 0; //合并后的发货单数
        foreach ($orderGroup as $group) {
            if ($group['cnt'] > 1) {
                $orderGroupCnt++;
                $orderGroupOrdCnt += $group['cnt'];
            }
            $orderCnt += $group['cnt'];
        }

        //缓存区可操作订单
        $bufferFilter = array('order_confirm_filter' => '(op_id IS NULL AND group_id IS NULL AND (is_cod=\'true\' or pay_status in (\'1\',\'4\',\'5\')))', 'status' => 'active', 'ship_status' => '0', 'f_ship_status' => '0', 'confirm' => 'N', 'abnormal' => 'false', 'refund_status' => 0, 'is_auto' => 'false', 'is_fail' => 'false');
        if($filter['shop_id'] && $filter['shop_id'] != 'all'){
            $bufferFilter['shop_id'] = $filter['shop_id'];
        }
        $bufferOrderCnt = app::get('ome')->model('orders')->count($bufferFilter);

        $data = array(
            'OrderGroups'=>$orderGroup,
            'currentTime'=>time(),
            'bufferOrderCnt'=>$bufferOrderCnt,
            'orderCnt'=>$orderCnt,
            'orderGroupOrdCnt'=>$orderGroupOrdCnt,
            'orderGroupCnt'=>$orderGroupCnt,
        );
        echo json_encode($data);
    }
}