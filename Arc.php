<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Arc
 *
 * @author Keva
 */
class Arc extends shared {

    public $From;
    public $To;
    public $Tests;
    public $Actions;
    public $ArcType;
    public $ArcTitle;

    public function getArcType() {
        if ($this->isVir())
            return explode('-', $this->ArcType)[1];
        else
            return $this->ArcType;
    }

    function __construct($str) {
        list($title, $rest) = self::splitByFirstChar($str, ' ');
        $this->ArcTitle = $title;
        list($this->From, $this->ArcType, $this->To) = explode(':', $title);
        $testStr = self::findElementBoundInString($rest, 0);
        $actionStr = self::findNextElementBoundInString($rest, 0);
        $this->Tests = self::GetElements(self::TrimBraces($testStr));
        $this->Actions = self::GetElements(self::TrimBraces($actionStr));
    }

    /**
     * 
     * @param type $str
     * @return ATNResult
     */
    function ConsumeString($str, $HoldList) {
        if ($str == '' or !$str)
            return null;
        if (strtolower($this->ArcType) == $this->ArcType) {
            list($word, $rest) = self::PopWord($str);
            $lexes = $this->matchLexicon($word);
            if ($lexes) {
                $ret = array();
                foreach ($lexes as $lex) {
                    $ret[] = new ATNResult(' '.$this->ArcType . '(' . $word . ')', $lex->features, $rest, $HoldList);
                }
                return $ret;
            }
        } else {
            $myATN = ATNgraph::GetATNbyName($this->ArcType);
//            if (!$myATN)
//                return null;
            $myATN->parentHold = $HoldList;
            return $myATN->matchString($str);
        }
        return null;
    }

    function matchLexicon($word) {
        $ret = null;
        foreach (lexicon::$list as $lex) {
            if ($this->testLexicon($lex, $word)) {
                $ret[] = $lex;
            }
        }
        return $ret;
    }

    /**
     * 
     * @param lexicon $lex
     * @param type $word
     * @return boolean
     */
    function testLexicon($lex, $word) {
        if ($word != $lex->name)
            return false;
        if (strtolower($lex->features['TYPE']) == $this->ArcType) {
            return true;
        }
        return false;
    }

    function isVir() {
        return (substr($this->ArcType, 0, 3) == 'VIR');
    }

    function isPOP() {
        return ($this->ArcType == 'POP');
    }

}

?>
