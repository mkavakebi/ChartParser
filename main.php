<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of main
 *
 * @author Keva
 */
include_once 'lexicon.php';
include_once 'ATNgraph.php';
include_once 'Arc.php';
include_once 'ATNResult.php';
include_once 'parser.php';
include_once 'grammar.php';

class main {

    public function run($input, $type) {
        file_put_contents('flare.json', '');
        if ($input == '')
            return;
        ob_start();
        if ($type == 'chartparser') {
            $ret = $this->runChartParser($input);
        } else {
            $ret = $this->runATN($input);
        }
        $ret2['dump'] = ob_get_clean();
        ob_clean();
        $ret2['count'] = 0;
        if ($ret) {
            $ret2['count'] = count($ret);
            $ret2['res'] = $ret[0];
//            if ($type == 'ATN')
            $ret2['res']->makeJson();
        }
        return $ret2;
    }

    public function runATN($input) {
//        $input = 'he saw the dog';
//        $input = 'which dog did he see';
//        $input = 'did he see the book';
        ob_start();
        lexicon::initLexicons();
        ATNgraph::initATNs();
        $S_ATN = ATNgraph::GetATNbyName('S');
        $ret = $S_ATN->matchString($input);
        $ret2 = null;
        foreach ($ret as $r) {
            if ($r->restStr == '') {
                $ret2[] = $r;
            }
        }

        var_dump($ret2);

        return $ret2;
    }

    public function runChartParser($input) {
//        $input = 'in which box did he put the book';
//        $input = 'jill saw the dog';
        lexicon::initLexicons();
        grammar::initGrammars();
        $p = new parser($input);
        $ret = $p->parse();

        var_dump('HHHHHHHHHHHHHHHHHHHHHHHhh');
        var_dump($ret);
        return $ret;
    }

}

?>
