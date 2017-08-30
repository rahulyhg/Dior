<?php

class ome_parse_ec_parseEC {
    
    private $lexicon = null;
    private $content = '';
    
    private $sureInfoList = array ();
    private $denyInfoList = array ();
    private $expressInfoList = array ();
    private $puncatuationInfoList = array ();
    private $sentenceHeap;
    
    public function __construct($content = null) {
        $this->lexicon = new ome_parse_ec_lexicon ();
        $this->sentenceHeap = new ome_parse_ec_sentence ( $this->lexicon );
        
        $this->setContent ( $content );
    }
    
    public function setContent($content) {
        $this->content = preg_replace('/(\s)/', '', $content);
    }
    
    public function parse() {
        $this->init();
        
        $surePtn = '/(' . implode ( '|', array_map ( 'preg_quote', array_keys ( $this->lexicon->getSureLib () ) ) ) . ')/i';
        $i = preg_replace_callback ( $surePtn, array (& $this, 'parseSureMatch' ), $this->content );
        
        $denyPtn = '/(' . implode ( '|', array_map ( 'preg_quote', array_keys ( $this->lexicon->getDenyLib () ) ) ) . ')/i';
        $i = preg_replace_callback ( $denyPtn, array (& $this, 'parseDenyMatch' ), $this->content );
        
        $expressPtn = '/(' . implode ( '|', array_map ( 'preg_quote', array_keys ( $this->lexicon->getExpressLib () ) ) ) . ')/i';
        $i = preg_replace_callback ( $expressPtn, array (& $this, 'parseExpressMatch' ), $this->content );
        
        $puncatuationPtn = '/(' . implode ( '|', array_map ( 'preg_quote', array_keys ( $this->lexicon->getPunctuationLib () ) ) ) . ')/';
        $pi = preg_replace_callback ( $puncatuationPtn, array (& $this, 'parsePuncatuationMatch' ), $this->content );
        
        return $this->parseSentence ( $this->sentenceHeap->getList () );
    }
    
    public function parseSentence($kwList) {
        $this->sentenceHeap->filter($this->puncatuationInfoList);
        
        //就近原则处理是非
        foreach ( $this->expressInfoList as $ew => $info ) {
            $this->setFrontExpressWeight( $info );
            $this->setAfterExpressWeight ( $info );
        }
        //var_dump($this->sentenceHeap->getList());
        //exit;
        
        //处理分句
        $rl = array();
        $subWeight = 0;
        $subNum = 0;
        $sl = $this->sentenceHeap->getList();

        foreach ($sl as $pos => $info)
        {
            if(isset($this->puncatuationInfoList[$info['keyWord']]))
            {
                $rl[$subNum++]['weight'] = $subWeight;
                $subWeight = 0;
            }
            
            $subWeight += $this->lexicon->getWeight($info['keyWord']);
            
            if(isset($info['weight']))
            {
                $subWeight += $info['weight'];
            }
            
            if(isset($this->expressInfoList[$info['keyWord']]))
            {
                $rl[$subNum]['express'][] = $info;
            }
        }
        $rl[$subNum++]['weight'] = $subWeight;
        //var_dump($rl);
        //exit;
        
        //进行对比
        $matchResult = array();
        
        foreach ($rl as $result)
        {
            //有肯定项
            if($result['weight'] >= 0 )
            {
                $matchResult['yes'][] = $result['express'];
                continue;
            }
            
            //有排除项
            if($result['weight'] < 0 )
            {
                $matchResult['no'][] = $result['express'];
            }
        }
        //var_dump($matchResult);
        //exit;
        
        if(empty($matchResult))
        {
            foreach ($rl as $result)
            {
                //有肯定项
                if($result['weight'] >= 0 )
                {
                    $matchResult['yes'][] = $result['express'];
                    continue;
                }
            }
        }
        //var_dump($matchResult);exit;
        
        //修正子句
        foreach ($matchResult as $key => $result)
        {
            foreach ($result as $i => $r)
            {
                if($key == 'yes')
                {
                    foreach ($r as $j => $rj)
                    {
                        if(isset($rj['weight']) && $rj['weight'] <= 0)
                        {
                            unset($matchResult[$key][$i][$j]);
                        }
                    }
                }
                
                if($key == 'no')
                {
                    foreach ($r as $j => $rj)
                    {
                        if(isset($rj['weight']) && $rj['weight'] >= 0)
                        {
                            unset($matchResult[$key][$i][$j]);
                        }
                    }
                }
            }
        }
        //var_dump($matchResult);
        //exit;
        
        // 再次修正
        foreach ($matchResult as $k => $v)
        {
            foreach ($v as $ki => $vi)
            {
                if(empty($vi))
                {
                    unset($matchResult[$k][$ki]);
                }
            }
        }
        
        return $matchResult;
    }
    
    public function parseSureMatch($match) {
        $keyWord = $match [0];
        
        $info = $this->setPosByKey ( $keyWord, $this->sureInfoList );
        $this->sentenceHeap->insert ( $info );
        
        return $keyWord;
    }
    
    public function parseDenyMatch($match) {
        $keyWord = $match [0];
        
        $info = $this->setPosByKey ( $keyWord, $this->denyInfoList );
        $this->sentenceHeap->insert ( $info );
        
        return $keyWord;
    }
    
    public function parseExpressMatch($match) {
        $keyWord = strtoupper($match [0]);
        
        $info = $this->setPosByKey ( $keyWord, $this->expressInfoList );
        $info['type'] = $this->lexicon->getExpressType($info['keyWord']);
        $this->sentenceHeap->insert ( $info );
        
        return $keyWord;
    }
    
    public function parsePuncatuationMatch($match) {
        $keyWord = $match [0];
        
        $info = $this->setPosByKey ( $keyWord, $this->puncatuationInfoList );
        $this->sentenceHeap->insert ( $info );
        
        return $keyWord;
    }
    
    private function setPosByKey($keyWord, &$list) {
        $times = 0;
        if (isset ( $list [$keyWord] )) {
            $times = $list [$keyWord] ['times'];
        }
        
        $splitArr = explode ( $keyWord, $this->content );
        $pos = 0;
        foreach ( $splitArr as $k => $p ) {
            $pos += strLength ( $p );
            
            if ($k == $times) {
                $pos += strLength ( $keyWord ) * $times;
                
                break;
            }
        }
        
        if (isset ( $list [$keyWord] )) {
            $times = $list [$keyWord] ['times'] = $times + 1;
        } else {
            $list [$keyWord] = array ('times' => 1, 'keyWord' => $keyWord );
        }
        
        $list [$keyWord] ['pos'] [] = array ('index' => $pos, 'keyLen' => strLength ( $keyWord ) );
        
        return $list [$keyWord];
    }
    
    private function setFrontExpressWeight($info) {
        foreach ( $info ['pos'] as $pos ) {
            $kwInfo = $this->sentenceHeap->get ( $pos['index'] );
            
            for($i = $pos['index']-1; $i >= 0; $i --) {
                if($pos['index'] - $i > 10)
                {
                    return;
                }
                
                if ($this->sentenceHeap->hasPos ( $i )) {
                    
                    $kwInSentence = $this->sentenceHeap->get ( $i );
                    
                    if(isset($this->puncatuationInfoList[$kwInSentence ['keyWord']]))
                    {
                        return;
                    }
                    
                    $weight = $this->lexicon->getWeight ( $kwInSentence ['keyWord'] );
                    
                    if ($weight > 0) {
                        return $this->sentenceHeap->addWeight ( $pos['index'], 10 );
                    } else if ($weight < 0) {
                        return $this->sentenceHeap->addWeight ( $pos['index'], -10 );
                    } elseif($weight === 0) {
                        return;
                    }
                }
            }
        }
    }
    
    private function setAfterExpressWeight($info) {
        $len = array_pop(array_keys($this->sentenceHeap->getList()));
        
        foreach ( $info ['pos'] as $pos ) {
            $kwInfo = $this->sentenceHeap->get ( $pos['index'] );
            
            for($i = $pos['index']+1; $i < $len; $i++) {
                if($i - $pos['index'] > 10)
                {
                    return;
                }
                
                if ($this->sentenceHeap->hasPos ( $i )) {
                    
                    $kwInSentence = $this->sentenceHeap->get ( $i );
                    
                    if(isset($this->puncatuationInfoList[$kwInSentence ['keyWord']]))
                    {
                        return;
                    }
                    
                    $weight = $this->lexicon->getWeight ( $kwInSentence ['keyWord'] );
                    
                    if ($weight > 0) {
                        return $this->sentenceHeap->addWeight ( $pos['index'], 10 );
                    } elseif ($weight < 0) {
                        return $this->sentenceHeap->addWeight ( $pos['index'], -10 );
                    }
                    elseif($weight === 0)
                    {
                        return;
                    }
                }
            }
        }
    }
    
    private function init()
    {
        $this->sureInfoList = array ();
        $this->denyInfoList = array ();
        $this->expressInfoList = array ();
        $this->puncatuationInfoList = array ();
        
        $this->sentenceHeap->revert();
    }
    
}

function strLength($string) {
    if (empty ( $string )) {
        return 0;
    }
    if (function_exists ( 'mb_strlen' )) {
        return mb_strlen ( $string, 'utf-8' );
    } else {
        preg_match_all ( "/./u", $string, $ar );
        return count ( $ar [0] );
    }
}

?>