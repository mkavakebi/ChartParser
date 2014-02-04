<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ArcResult
 *
 * @author Keva
 */
class ATNResult {

    public $resultString;
    public $Featuers;
    public $restStr;
    public $retHold;

    function __construct($resultString, $Featuers, $restStr, $retHold) {
        $this->resultString = $resultString;
        $this->Featuers = $Featuers;
        $this->restStr = $restStr;
        $this->retHold = $retHold;
    }

    function makeJson() {
        $r = $this->makeJson2($this->resultString);
        file_put_contents('flare.json', $r);
        return $r;
    }

    private function makeJson2($str) {
        if (!shared::contains($str, '('))
            return '{"name": "' . $str . '","size": 3 }';
        list($name, $str) = shared::splitByFirstChar($str, '(');
        $elems = shared::GetElements(shared::TrimBraces($str));
        $p = '{"name": "' . $name . '","children": [ ';
        $str2 = array();
        foreach ($elems as $elem) {
            $str2[] = $this->makeJson2($elem);
        }
        $p .= implode(',', $str2);
        $p.= ']}';
        return $p;
    }

    function getRetArray() {
        $str = $this->resultString;
        return $this->getar($str);
    }

    private function getar($str) {
        if (!shared::contains($str, '('))
            return array($str);
//        var_dump($str);
        list($name, $str) = shared::splitByFirstChar($str, '(');
        $elems = shared::GetElements(shared::TrimBraces($str));
        foreach ($elems as $elem) {
            $ret[] = $this->getar($elem);
        }
        return array($name => $ret);
    }

}
?>
