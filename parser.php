<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of parser
 *
 * @author Keva
 */
class parser {

    public $string;

    function __construct($str) {
        $this->string = $str;
        $this->matchedTags = array();
        $this->Arcs = array();
    }

    /**
     *
     * @var lexicon
     */
    public $matchedTags;

    /**
     *
     * @var grammar
     */
    public $Arcs;

    function parse() {
        $words = explode(' ', $this->string);
        for ($i = 0; $i < count($words); $i++) {
            var_dump('WORD INDEX: ' . $i . '----------');
            $newTags = $this->findMatchingLexiconToWord($words[$i], array($i, $i + 1));
            $FinishedStuff = $this->findMatchingArcs($newTags);
        }
        $ret = null;
        foreach ($FinishedStuff as $lex) {
            if ($lex->position[0] == 0 AND $lex->position[1] == count($words)) {
                $ret[] = $lex;
            }
        }

        return $ret;
    }

    /**
     * 
     * @param lexicon $Lexes
     * @return lexicon
     */
    function findMatchingArcs($Lexes) {
        $_Lexes = $Lexes;
        $finishedStuff = null;
        $newArcs = array();
        $CurrentArcs = array_merge(grammar::$list, $this->Arcs);
        $i = 0;
        while ($i < count($_Lexes)) {
            $lex = $_Lexes[$i];
            $i++;

            foreach ($CurrentArcs as $g) {
                if ($g->machLexiconWithCurrent($lex)) {
                    $g2 = clone $g;
                    $lex2 = clone $lex;
                    $newlex = $g2->Proceed($lex2);
                    if ($newlex) {
                        var_dump($newlex);
                        $_Lexes[] = $newlex;
                        //$finishedStuff[] = array($g2, $newlex);
                    } else {
                        $newArcs[] = $g2;
                        $g3 = clone $g2;
                        $newlex2 = $g3->ProcessGap();
                        if ($newlex2 != 'non') {
                            if ($newlex2) {
                                var_dump($newlex2);
                                $_Lexes[] = $newlex2;
                                //$finishedStuff[] = array($g3, $newlex2);
                            } else {
                                $newArcs[] = $g3;
                            }
                        }
                    }
                }
            }
        }
        $this->Arcs = array_merge($this->Arcs, $newArcs);
        return $_Lexes;
    }

    function findMatchingLexiconToWord($word, $pos) {
        $ret = array();
        foreach (lexicon::$list as $l) {
            if ($l->name == $word) {
                $c = clone $l;
                $c->position = $pos;
                $ret[] = $c;
            }
        }
        return $ret;
    }

}

?>
