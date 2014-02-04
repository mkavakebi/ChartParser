<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of lexicon
 *
 * @author Keva
 */
include_once 'shared.php';

class lexicon extends shared {

    // '#' is prohibited in the string
    public $name;
    public $features;
    public $position;

    /**
     *
     * @var lexicon
     */
    public $children;

    function __construct($str) {
        list($name, $str) = self::splitByFirstChar($str, '(');
        $this->name = $name;
        $this->features = self::FeaturesFromString($str);
    }

    function __clone() {
        foreach ($this as $name => $value) {
            if (gettype($value) == 'object') {
                $this->$name = clone($this->$name);
            }
        }
        $a = $this->children;
        if ($a)
            foreach ($a as $name => $value) {
                $this->children[$name] = clone $value;
            }
    }

    /**
     *
     * @var lexicon 
     */
    public static $list;

    static function initLexicons() {
        $lexAr = self::getLines('lexicon.txt');
        foreach ($lexAr as $l) {
            self::$list[] = new lexicon(trim($l));
        }
    }

    /**
     * 
     * @param lexicon $A
     * @param lexicon $B
     */
    static function matchLexicons($A, $B) {
        $res = true;
        $Flist = array_keys($A->features);
        foreach ($Flist as $f) {
            if (isset($B->features[$f]))
                $res = $res && self::isFeatureMatch($A->features[$f], $B->features[$f], $f);
            else
                $res = false;
        }
        return $res;
    }

    function DoVariables($varAr) {
        if (!$varAr)
            return;

        $keys = array_keys($this->features);
        while (count($keys)) {
            $k1 = array_pop($keys);
            foreach ($varAr as $k => $v) {
                $theFeatureValue = $this->features[$k1];
                if ($theFeatureValue instanceof lexicon) {
                    ; //$theFeatureValue->DoVariables($varAr);
                } elseif (str_replace($k, '', $theFeatureValue) != $theFeatureValue) {
                    $keys[] = $k1;
                    if (is_array($v)) {
                        if ($theFeatureValue == $k) {
                            $this->features[$k1] = $v;
                        } else {
                            $v2 = '{' . implode(' ', $v) . '}';
                            $this->features[$k1] = str_replace($k, $v2, $theFeatureValue);
                        }
                    } elseif ($v instanceof lexicon) {
                        $this->features[$k1] = $v;
                    } else {
                        $this->features[$k1] = str_replace($k, $v, $theFeatureValue);
                    }
                }
            }
        }
    }

    /**
     * 
     * @param lexicon $A
     * @param lexicon $B
     * @return type
     */
    static function Get_Vars_AndMergeLexicons(&$A, $B) {
        $ret = null;
        $Flist = array_keys($A->features);
        foreach ($Flist as $f) {
            $a = $A->features[$f];
            $b = $B->features[$f];
            if (self::isVar($a)) {
                $ret[$a] = $b;
            } elseif (self::isVar($b)) {
                $ret[$b] = $a;
            } elseif (is_array($a) or is_array($b)) {
                $inter = self::ValueIntersect($a, $b);
                if (count($inter) == 1)
                    $A->features[$f] = $inter[0];
                else
                    $A->features[$f] = $inter;
            }
        }
        foreach ($ret as $k => $v) {
            if (self::isVar($v))
                unset($ret[$k]);
        }
        return $ret;
    }

    static function isFeatureMatch($a, $b, $featureName) {
        if ($featureName != 'GAP' AND self::arrayEqualityORnot($a, $b))
            return true;
        if (shared::isVar($a) OR shared::isVar($b))
            return true;
        if ($featureName == 'GAP') {
            return self::isGapMatch($a, $b);
        }
        return false;
    }

    static function isGapMatch($a, $b) {
        if ($a == '-' OR $b == '-') {
            return ($a == $b);
        }
        if (is_string($a)) {
            $s0 = str_replace('~', ' ', $a);
            $a2 = new lexicon($s0);
        } else {
            $a2 = clone $a;
        }
        if (is_string($b)) {
            $s0 = str_replace('~', ' ', $b);
            $b2 = new lexicon($s0);
        } else {
            $b2 = clone $b;
        }
        return self::matchLexicons($b2, $a2);
    }

    function LambdaResolve() {
        if (!isset($this->features['SEM']))
            return;
        $str = $this->features['SEM'];
        if (strpos($str, '^') !== FALSE) {
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
            }
        }
        $this->features['SEM'] = $str;
    }

    function makeJson() {
        $r = $this->makeJson2();
        file_put_contents('flare.json', $r);
        return $r;
    }

    private function makeJson2() {
        $name = $this->name.'['.$this->features['TYPE'].']';
        if (!$this->children)
            return '{"name": "' . $name . '","size": 3 }';
        $p = '{"name": "' . $name . '","children": [ ';
        $str2 = array();
        foreach ($this->children as $lex) {
            $str2[] = $lex->makeJson2();
        }
        $p .= implode(',', $str2);
        $p.= ']}';
        return $p;
    }

}

?>
