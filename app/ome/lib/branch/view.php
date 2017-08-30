<?php
class ome_branch_view extends desktop_controller{
    
    public function getBranchView($branch_id, $url, $title='查看', $method='GET'){
        //$render = app::get('ome')->render();
        if ($branch_id){
            $this->pagedata['branch_id'] = $branch_id;
            return $branch_id;
        }else {
            $oBranch = &app::get('ome')->model('branch');
            $is_super = kernel::single('desktop_user')->is_super();
            $branch_ids = $oBranch->getBranchByUser(true);
            if (!$is_super){
                $branch_ids = $oBranch->getBranchByUser(true);
                if ($branch_ids){
                    if (count($branch_ids) > 1){
                        $this->pagedata['branch_list'] = $branch_ids;
                        $this->pagedata['name'] = $title;
                        $this->pagedata['url'] = $url;
                        $this->pagedata['method'] = $method;
                        $this->page("admin/branch/exist_branch.html");
                        exit;
                    }else {
                        $this->pagedata['branch_id'] = $branch_ids[0]['branch_id'];
                        return $branch_ids[0]['branch_id'];
                    }
                }else{
                    $this->pagedata['name'] = $title;
                    $this->pagedata['url'] = $url;
                    $this->pagedata['method'] = $method;
                    $this->page("admin/branch/exist_branch.html");
                    exit;
                }
            }else {
                $branch_ids = $oBranch->getList('branch_id,name,uname,phone,mobile','',0,-1);
                if ($branch_ids){
                    if (count($branch_ids) > 1){
                        $this->pagedata['branch_list'] = $branch_ids;
                        $this->pagedata['name'] = $title;
                        $this->pagedata['url'] = $url;
                        $this->pagedata['method'] = $method;
                        $this->page("admin/branch/exist_branch.html");
                        exit;
                    }else {
                        $this->pagedata['branch_id'] = $branch_ids[0]['branch_id'];
                        return $branch_ids[0]['branch_id'];
                    }
                }else{
                    $this->pagedata['name'] = $title;
                    $this->pagedata['url'] = $url;
                    $this->pagedata['method'] = $method;
                    $this->page("admin/branch/exist_branch.html");
                    exit;
                }
            }
        }
    }
}