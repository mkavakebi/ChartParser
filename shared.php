<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of shared
 *
 * @author Keva
 */
class shared {

    private static $varnum = 0;

    public static function getNewVar() {
        self::$varnum++;
        return 'var' . self::$varnum;
    }

    static $featureTitles = array(
        'SEM', 'TYPE', 'AGR', 'SUBCAT', 'VFORM',
        'VAR', 'WH-VAR', 'IRREG-PAST', 'EN-PASTPRT',
        'PRED', 'GAP', 'WH', 'INV', 'ROOT', 'PFORM'
    );

    static function FeaturesFromString($str) {
        $str = ' TYPE ' . $str;
        $FAr = self::GetElements($str);
        $ret = array();
        $ret['AGR'] = '?myagr'; //array('1s', '2s', '3s', '1p', '2p', '3p');
        $ret['GAP'] = '-';
        $turn = true;
        foreach ($FAr as $f) {
            if ($turn) {
                $k = $f;
            } else {
                $ret[$k] = self::readFeatureValue($f);
            }
            $turn = !$turn;
        }
        if ($ret['AGR'] == '-')
            $ret['AGR'] = array('1s', '2s', '3s', '1p', '2p', '3p');
        //if ($ret['GAP'] == '-')
        //    unset($ret['GAP']);
        $ret['TYPE'] = strtoupper($ret['TYPE']);
        return $ret;
    }

    static function ValueIntersect($a, $b) {
        if (is_array($a) AND is_array($b)) {
            $a2 = $a;
            $b2 = $b;
        }
        if (is_array($a) AND !is_array($b)) {
            $a2 = $a;
            $b2 = array($b);
        }
        if (!is_array($a) AND is_array($b)) {
            $a2 = array($a);
            $b2 = $b;
        }
        if (!is_array($a) AND !is_array($b)) {
            $a2 = array($a);
            $b2 = array($b);
        }
        $union = array_intersect($a2, $b2);
        $ret = array();
        foreach ($union as $u)
            $ret[] = $u;
        return $ret;
    }

    static function isVar($v) {
        return (is_string($v) AND $v[0] == '?');
    }

    static function arrayEqualityORnot($a, $b) {
        return (count(self::ValueIntersect($a, $b)) > 0);
    }

    static function readFeatureValue($v) {
        if ($v[0] == '{') {
            $v = substr($v, 1, strlen($v) - 2);
            return explode(' ', $v);
        } return $v;
    }

    static function splitByFirstChar($str, $char) {
        $name = explode($char, $str)[0];
        $remLen = strlen($str) - strlen($name) - strlen($char);
        $from =
                strlen($name) + strlen($char);
        if ($char == '(')
            $remLen--;
        $str = substr($str
                , $from, $remLen);
        return array($name, $str);
    }

    static function getLines($file) {
        //get line arrays
        $str = file_get_contents($file);
        $str = str_replace("\n", ';', $str);
        $str = str_replace("\r", ';', $str);
        $str = str_replace(';;', ';', $str);
        $lineAr = explode(';', $str);
        $ret = array();
        foreach
        ($lineAr as $l) {
            if ($l != '' AND $l[0] != '#')
                $ret[] = $l;
        }
        return $ret;
    }

    public static $braceOpen = array('(', '<', '[', '{');
    public static $braceClose = array(')', '>', ']', '}');

    static function findElementBoundInString($str, $from) {
        $braceC = 0;
        for ($to = $from; $to < strlen($str); $to++) {
            $ch = $str[$to];
            if (in_array($ch, self::$braceOpen)) {
                $braceC++;
                continue;
            }
            if (in_array($ch, self::$braceClose)) {
                $braceC--;
                if ($braceC == 0)
                    break;
                if ($braceC < 0){
                    $to--;
                    break;
                }
                continue;
            }
            if ($ch == ' ' AND $braceC == 0) {
                $to--;
                break;
            }
        }
        $len = $to - $from + 1;
        $ret = substr($str, $from, $len);
        return $ret;
    }

    static function TrimBraces($str) {
        $str = trim($str);
        if (in_array($str[0], self::$braceOpen)) {
            $str = self::removeHeadTail($str, 1, 1);
        }
        return $str;
    }

    static function findNextElementBoundInString($str, $from) {
        $r = self::findElementBoundInString($str, $from);
        $from2 = $from + strlen($r) + 1;
        $b = self::findElementBoundInString($str, $from2);
        return $b;
    }

    static function removeHeadTail($str, $headCut, $tailCut) {
        return substr($str, $headCut, strlen($str) - $headCut - $tailCut);
    }

    static function PopWord($str) {
        $r = explode(' ', $str);
        $w = $r[0];
        $r = array_slice($r, 1, count($r) - 1);
        return array($w, implode(' ', $r));
    }

    static function GetElements($str) {
        $str = trim($str);
        if ($str == '')
            return array();
        $first = trim(self::findElementBoundInString($str, 0));
        $str2 = self::removeHeadTail($str, strlen($first), 0);
        $rest = self::GetElements($str2);
        $ret = array_merge(array($first), $rest);
        return $ret;
    }

    static function contains($source, $search) {
        return (strpos($source, $search) !== FALSE);
    }

}

?>
