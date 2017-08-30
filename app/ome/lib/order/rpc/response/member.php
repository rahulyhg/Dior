<?php
/**
 * 会员数据处理
 * @access public
 * @author ome
 * @copyright www.shopex.cn 2010.12.28
 */
class ome_order_rpc_response_member{

    /**
     * 更新订单会员信息
     * @access public
     * @param array $member_info 会员信息
     * @param string $shop_id 店铺ID
     * @return int 会员ID
     */
    public function update_order_member($member_info,$shop_id){

        if (empty($member_info)) return null;
        $membersObj = &app::get('ome')->model('members');
        $smemberObj = &app::get('ome')->model('shop_members');

        $member_info = json_decode($member_info,true);
        if (empty($member_info['name'])) $member_info['name'] = $member_info['uname'];
        $member_detail = array();
        $member_id = null;
        if($member_info['uname']){
            //判断是否存在该会员
            $member_detail = $smemberObj->dump(array('shop_member_id'=>$member_info['uname'],'shop_id'=>$shop_id), 'member_id');
            $area = $member_info['area_state'].'/'.$member_info['area_city'].'/'.$member_info['area_district'];
            kernel::single('eccommon_regions')->region_validate($area);
            $area = str_replace('::','',$area);
            $members_data = array(
                'account' => array(
                    'uname' => $member_info['uname'],
                ),
                'contact' => array(
                    'name' => $member_info['name'],
                    'area' => $area,
                    'addr' => $member_info['addr'],
                    'phone' => array(
                        'mobile' => $member_info['mobile'],
                        'telephone' => $member_info['tel']
                        ),
                    'email' => $member_info['email'],
                    'zipcode' => $member_info['zip']
                ),
            );
            if (empty($member_detail['member_id'])){
                //增加会员
                $membersObj->save($members_data);
                $shop_members_data = array(
                    'shop_id' => $shop_id,
                    'shop_member_id' => $member_info['uname'],
                    'member_id' => $members_data['member_id'],
                );
                //--以下代码是解决并发量大的情况 - 开始
                if (!@$smemberObj->insert($shop_members_data)){
                    //将插入关系表失败的会员信息删除
                    $membersObj->delete(array('member_id'=>$members_data['member_id']));
                    //从关系表中查询会员ID
                    $member_detail = $smemberObj->dump(array('shop_member_id'=>$member_info['uname'],'shop_id'=>$shop_id), 'member_id');
                    $members_data['member_id'] = $member_detail['member_id'];
                }
                //--并发解决 - 结束
                $member_detail['member_id'] = $members_data['member_id'];
            }else{
                //更新会员
                $members_data = array_merge($members_data, array('member_id'=>$member_detail['member_id']));
                $membersObj->save($members_data);
            }
            $member_id = $member_detail['member_id'];
        }
        return $member_id;
    }

}