<?php

include_once 'shared.php';

class grammar extends shared {

// '#' is prohibited in the string
    public $left;
    public $right;
    public $nxtLexID;
    public $VarArray;
    public $position;

    function __construct($str) {
        list($left, $right) = self::splitByFirstChar($str, '->');
        $this->left = new lexicon($left);
//$right = substr($right, 1, strlen($right) - 2);
        $right = str_replace(')(', ')#(', $right);
        $right = explode('#', $right);
        foreach ($right as $r) {
            $this->right[] = new lexicon($r);
        }
        $this->nxtLexID = 0;
    }

    function __clone() {
        $this->left = clone($this->left);
        foreach ($this->right as $k => $v)
            $this->right[$k] = clone($v);
    }

    /**
     * 
     * @return lexicon
     */
    private function getCurrentLecxicon($type = 'fixed') {
        if ($type == 'fixed')
            return $this->getRight($this->nxtLexID);
        else
            return $this->right[$this->nxtLexID];
    }

    /**
     * 
     * @param lexicon $lex
     */
    function getFixedLexicon($lex) {
        $lex->DoVariables($this->VarArray);
    }

    /**
     * 
     * @param type $i
     * @return lexicon
     */
    function getRight($i) {
        $a = clone $this->right[$i];
        $this->getFixedLexicon($a);
        return $a;
    }

    /**
     * 
     * @return lexicon
     */
    function getLeft() {
        $a = clone $this->left;
        $this->getFixedLexicon($a);
        return $a;
    }

    /**
     * 
     * @param lexicon $lex
     */
    public function machLexiconWithCurrent($lex) {
        $mine = $this->getCurrentLecxicon();
        if ($this->position != null AND $this->position[1] != $lex->position[0])
            return false;

        return lexicon::matchLexicons($mine, $lex);
    }

    /**
     * 
     * @param lexicon $lex
     */
    public function Proceed($lex) {
        $mine = $this->getCurrentLecxicon('pure');
        $varAr = lexicon::Get_Vars_AndMergeLexicons($mine, $lex);
        $this->AddVariables($varAr);
        $this->position[1] = $lex->position[1];
        $this->right[$this->nxtLexID] = $lex;
        $this->nxtLexID++;
        if ($this->nxtLexID == count($this->right)) {
            $this->ResolveUnusedVariables();
            $this->left->position = array($this->right[0]->position[0], $lex->position[1]);
            $a = $this->getLeft();
            $a->LambdaResolve();
            $a->children = $this->right;
            return $a;
        } else {
            return null;
        }
    }

    function ProcessGap() {
        $forwardLex = $this->getCurrentLecxicon();
        if (!isset($forwardLex->features['GAP']))
            return 'non';


        if (self::isVar($forwardLex->features['GAP'])) {
            $lex = clone $forwardLex;
            $lex->position[1] = $this->position[1];
            $varAr = array($forwardLex->features['GAP'] => $lex);
            $this->AddVariables($varAr);
            return $this->Proceed($lex);
        } else {
            return 'non';
        }
    }

    function ReplaceVariables($varAr) {
        $this->left->DoVariables($varAr);
        foreach ($this->right as $r) {
            $r->DoVariables($varAr);
        }
    }

    function AddVariables($varAr) {
        foreach ($varAr as $key => $value) {
            if (!isset($this->VarArray[$key])) {
                $this->VarArray[$key] = $value;
            } elseif ($value instanceof lexicon) {
                $this->VarArray[$key] = $value;
            } else {
                $this->VarArray[$key] = self::ValueIntersect($this->VarArray[$key], $value);
            }
        }
    }

    function ResolveUnusedVariables() {
        $varAr = null;
        foreach ($this->getLeft()->features as $f)
            if
            (self::isVar($f))
                $varAr[$f] = 'var' . rand(10, 40);
        if ($varAr) {
            $this->AddVariables($varAr);
        }
    }

    /**
     *
     * @var grammar
     */
    public static $list;

    static function initGrammars() {
        $gramAr = self::getLines('grammar.txt');
        foreach ($gramAr as $g) {
            self::$list[] = new grammar($g);
        }
    }

}

?>
