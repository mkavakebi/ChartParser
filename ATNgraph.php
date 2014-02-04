<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ATNgraph
 *
 * @author Keva
 */
class ATNgraph extends shared {

    public $name;
    public $HeadState;
    public $resultString;
    public $Features;

    /**
     *
     * @var ATNResult 
     */
    private $Hold;

    /**
     *
     * @var ATNResult 
     */
    public $parentHold;

    /**
     *
     * @var Arc 
     */
    public $Arcs;

    function __construct($lineAr) {
        $this->HeadState = $this->name = self::removeHeadTail($lineAr[0], 0, 1);

        for ($i = 1; $i < count($lineAr); $i++) {
            $this->Arcs[] = new Arc($lineAr[$i]);
        }
        $this->Features['AGR'] = array('1s', '2s', '3s', '1p', '2p', '3p');
        $this->parentHold = array();
        $this->Hold = array();
    }

    function __clone() {
        $p = $this->Arcs;
        foreach ($p as $k => $v) {
            $this->Arcs[$k] = clone $v;
        }
    }
/**
 * 
 * @param type $str
 * @return ATNResult
 */
    function matchString($str) {
        var_dump($this->HeadState);
//        var_dump($this->HeadState);
        if ($this->HeadState == 'VP') {
            echo 123;
        }
        $TotalRet = array();
        $arcs = $this->getAvailableArcs();
        if ($arcs)
            foreach ($arcs as $a) {
                if ($a->isPOP()) {
                    if (!count($this->Hold)) {
                        $p = clone $this;
                        $p->ActionArcOverResult($a, array());
                        $partret = new ATNResult($this->WrappedResultString(), $this->Features, $str, $this->parentHold);
                        $TotalRet[] = $partret;
                    }
                } elseif ($a->isVir()) {
                    $name = $a->getArcType();
                    if (isset($this->Hold[$name]))
                        $holdRes = $this->Hold[$name];
                    elseif (isset($this->parentHold[$name]))
                        $holdRes = $this->parentHold[$name];
                    else
                        continue;
                    $holdRes->restStr = $str;
                    $partret = $this->makeProceed($a, array($holdRes));
                    if ($partret)
                        $TotalRet = array_merge($TotalRet, $partret);
                } elseif ($ret = $a->ConsumeString($str, $this->MakeAllHoldList())) {
                    $partret = $this->makeProceed($a, $ret);
                    if ($partret)
                        $TotalRet = array_merge($TotalRet, $partret);
                }
            }
        return $TotalRet;
    }

    /**
     * 
     * @param ATNResult $results
     * @param Arc $arc
     * @return ATNResult
     */
    function makeProceed($arc, $results) {
        $TotalRet = array();
        foreach ($results as $r) {
            if ($this->testArcOverResult($arc, $r)) {
                $p = clone $this;
                $p->ActionArcOverResult($arc, $r);
                $p->resultString.=$r->resultString;
                $p->HeadState = $arc->To;
                if ($arc->isVir()) {
                    $name = $arc->getArcType();
                    if (isset($this->Hold[$name]))
                        unset($p->Hold[$name]);
                    elseif (isset($this->parentHold[$name]))
                        unset($this->parentHold[$name]);
                }
                $partret = $p->matchString($r->restStr);
                if ($partret)
                    $TotalRet = array_merge($TotalRet, $partret);
            }
        }
        return $TotalRet;
    }

    function MakeAllHoldList() {
        return array_merge($this->Hold, $this->parentHold);
    }

    function WrappedResultString() {
        return ' '.$this->name . '(' . $this->resultString . ')';
    }

    /**
     * 
     * @param Arc $arc
     * @param ATNResult $res
     * @return boolean
     */
    function testArcOverResult($arc, $res) {
        $tests = $arc->Tests;
        if (!$tests OR !count($tests))
            return true;
//        var_dump($tests, $this->Features, '-------');
        foreach ($tests as $tst) {
            $r1 = $this->EvaluateString($tst, $res->Featuers);
            if ($r1 == 'ignore')
                continue;
            if ($r1 == 'fail')
                return false;
            if (!count($r1))
                return false;
        }
        return true;
    }

    /**
     * 
     * @param Arc $arc
     * @param ATNResult $res
     * @return boolean
     */
    function ActionArcOverResult($arc, $res) {
        $actions = $arc->Actions;
        if (!$actions OR !count($actions))
            return true;
        foreach ($actions as $act) {
            if (shared::contains($act, ':=')) {
                list($a, $b) = shared::splitByFirstChar($act, ':=');
                if ($a == 'SEM' AND $b[0] == '{') {
                    $b = trim(shared::removeHeadTail($b, 1, 1));
                }
                if ($res)
                    $featStar = $res->Featuers;
                else
                    $featStar = array();
                $this->SetFeature($a, $b, $featStar);
            } elseif ($act == 'HOLD*') {
                $this->Hold[$arc->ArcType] = $res;
            }
        }
    }

    function SetFeature($feat, $val, $resFeatures) {
//        var_dump($feat, $val, $resFeatures, '----------');
        if ($feat == 'SEM') {
            $val = str_replace('?#', self::getNewVar(), $val);
            if (isset($resFeatures['SEM']))
                $val = str_replace('?SEM*', $resFeatures['SEM'], $val);
            if (isset($this->Features['SEM']))
                $val = str_replace('?SEM', $this->Features['SEM'], $val);
            $res = $this->LambdaResolve($val);
            $res = $this->SEMpostProcess($res);
        } else {
            $res = $this->EvaluateString($val, $resFeatures);
        }
        if ($res != 'ignore')
            $this->Features[$feat] = $res;
    }

    function LambdaResolve($str) {
//        return $str;
        $do = true;
        while ($do AND shared::contains($str, '^')) {
            $pos = strpos($str, '^') - 1;
            $r = self::findElementBoundInString($str, $pos);
            $varName = explode(' ', $r)[1];
            $replacement = self::findNextElementBoundInString($str, $pos + 3);
            $val = self::findNextElementBoundInString($str, $pos);
            if ($val) {
                $rep = str_replace(' ' . $varName . ' ', ' ' . $val . ' ', $replacement);
                $gaplen = strlen($r . $val) + 1;
                $from2 = $pos + $gaplen;
                $str = substr($str, 0, $pos) . $rep . substr($str, $from2, strlen($str) - $from2);
            } else {
                $do = false;
            }
        }
        return $str;
    }

    public function SEMpostProcess($str) {
        $str = str_replace('()', '', $str);
        $str = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $str);
        return $str;
    }

    function EvaluateString($str, $resFeatures) {
        if ($str[0] == '"') {
            $str = trim($str);
            return shared::removeHeadTail($str, 1, 1);
        }
        if ($str == '*')
            return $resFeatures;
        if ($str[0] == '{')
            return shared::readFeatureValue($str);
        if (shared::contains($str, '^')) {
            list($a, $b) = shared::splitByFirstChar($str, '^');
            $a = $this->EvaluateString($a, $resFeatures);
            $b = $this->EvaluateString($b, $resFeatures);
            if ($a == 'ignore' or $b == 'ignore')
                return 'ignore';
            if ($a == 'fail' or $b == 'fail')
                return 'fail';
            else
                return shared::ValueIntersect($a, $b);
        } else {
            if ($str[strlen($str) - 1] == '*') {
                $name = shared::removeHeadTail($str, 0, 1);
                if (isset($resFeatures[$name]))
                    return $resFeatures[$name];
            } else {
                if (shared::contains($str, '_')) {
                    list($name, $lis) = explode('_', $str);
                    if (isset($this->Features[$lis][$name]))
                        return $this->Features[$lis][$name];
                    else
                        return 'fail';
                } else {
                    $name = $str;
                    return $this->Features[$name];
                }
            }
        }
    }

    function getAvailableArcs() {
        $ret = null;
        foreach ($this->Arcs as $a) {
            if ($a->From == $this->HeadState) {
                $ret[] = $a;
            }
        }
        return $ret;
    }

    /**
     *
     * @var ATNgraph
     */
    public static $list;

    static function initATNs() {
        $ATNAr = self::getLines('ATN.txt');
        $points = array();
        $i = 0;
        foreach ($ATNAr as $line) {
            if ($line[strlen($line) - 1] == ':')
                $points[] = $i;
            $i++;
        }
        $points[] = $i;
        for ($i = 1; $i < count($points); $i++) {
            $eachATN = array_slice($ATNAr, $points[$i - 1], $points[$i] - $points[$i - 1]);
            self::$list[] = new ATNgraph($eachATN);
        }
    }

    /**
     * 
     * @param type $name
     * @return ATNgraph
     */
    static function GetATNbyName($name) {
        foreach (self::$list as $l) {
            if ($l->name == $name) {
                $a = clone $l;
                return $a;
            }
        }
    }

}

?>
