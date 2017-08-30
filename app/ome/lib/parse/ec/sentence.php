<?php

class ome_parse_ec_sentence {

    private $lexicon;
    private $kwList = array();
    
    public function __construct(& $lexicon)
    {
        $this->lexicon = $lexicon;
    }
    
    public function insert($info)
    {
        $this->kwList[$this->getPos($info)] = $info;
    }
    
    public function get($pos)
    {
        if(isset($this->kwList[$pos]))
        {
            return $this->kwList[$pos];
        }
        
        return array();
    }
    
    public function hasPos($pos)
    {
        return isset($this->kwList[$pos]);
    }
    
    public function addWeight($pos, $value)
    {
        if(!isset($this->kwList[$pos]['weight']))
        {
            $this->kwList[$pos]['weight'] =  $this->lexicon->getWeight($this->kwList[$pos]['keyWord']);
        }
        else
        {
            $this->kwList[$pos]['weight'] = $this->kwList[$pos]['weight'];
        }
        
        //echo '<BR>'. $pos . '=>'. $value . ' 加 '. $this->kwList[$pos]['weight'] . '等于<BR><BR>';
        $this->kwList[$pos]['weight'] += $value;
    }
    
    public function getList()
    {
        $this->sort();
        
        return $this->kwList;
    }
    
    public function sort()
    {
        ksort($this->kwList);
    }
    
    public function filter($puncatuationInfoList)
    {
        foreach ($this->kwList as $p=>$info)
        {
            if(isset($puncatuationInfoList[$info['keyWord']]))
            {
                unset($this->kwList[$p]);
            }
            else 
            {
                break;
            }
        }
        
        $this->kwList = array_reverse($this->kwList, true);
        
        foreach ($this->kwList as $p=>$info)
        {
            if(isset($puncatuationInfoList[$info['keyWord']]))
            {
                unset($this->kwList[$p]);
            }
            else 
            {
                break;
            }
        }
        
        return array_reverse($this->kwList, true);
    }
    
    public function revert()
    {
        $this->kwList = array();
    }
    
    private function getPos($info)
    {
        $p = array_pop($info['pos']);
        
        return $p['index'];
    }
    
}

?>