<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <script src="jquery-1.10.2.min.js"></script>
        <script src="d3.v3.min.js"></script>
        <script src="dndTree.js"></script>
        <link rel="stylesheet" type="text/css" href="treecss.css" />
    </head>
    <style>
        form{
            padding: 15px;
            height:50px;
            background-color: #F1F1F1;
            font-size: 20px;
        }
        input[type=text]{
            width: 300px;
        }
        .resultdivelement{
            display: block;
            padding: 9.5px;
            margin: 10px 10px;
            /*font-size: 13px;*/
            line-height: 20px;
            word-break: break-all;
            word-wrap: break-word;
            background-color: #f5f5f5;
            border: 1px solid #ccc;
            border: 1px solid rgba(0, 0, 0, 0.15);
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            border-radius: 4px;
        }
        #tree-container{
            height: 400px;
        }
    </style>
    <body>
        <?php
        if (isset($_REQUEST['sentence']))
            $Input = $_REQUEST['sentence'];
        else
            $Input = '';

        include_once 'main.php';

        ob_flush();
        if (isset($_REQUEST['type']))
            $dotype = $_REQUEST['type'];
        else
            $dotype = 'ChartParser';

        //ATN
        $sem = null;
        $count = 0;
        if ($dotype == 'ATN') {
            $main = new main();
            $result = $main->run($Input, 'ATN');

            if (isset($result['res']) AND $result['res'])
                $sem = htmlspecialchars($result['res']->Featuers['SEM']);
            $count = $result['count'];
//            if (!isset($ATNres['res']))
//                $ATNres['res'] = null;
        }
        //ChartParser
        if ($dotype == 'ChartParser') {
            $main = new main();
            $result = $main->run($Input, 'chartparser');
            if (isset($result['res']) AND $result['res'])
                $sem = htmlspecialchars($result['res']->features['SEM']);
            $count = $result['count'];
        }
        ?>
        <form method="Post" class="resultdivelement">
            Sentence:
            <input type="text" name="sentence" value="<?php echo $Input; ?>">
            <input type="submit" value="Parse">
            <br/>
            <input type="radio" name="type" value="ChartParser" <?php if ($dotype == 'ChartParser') echo 'checked'; ?>>ChartParser
            <input type="radio" name="type" value="ATN" <?php if ($dotype == 'ATN') echo 'checked'; ?>>ATN 
        </form>
        <?php if ($_REQUEST) { ?>
            <div class="resultdivelement">
                <h3>Results</h3>
                <p>there [is|are] <?php echo $count; ?> Result[s] for <?php echo $dotype; ?>.</p>
            </div>
        <?php } ?>
        <?php if ($count) { ?>
            <?php if ($sem) { ?>
                <div class="resultdivelement">
                    <h3>Semantic</h3>
                    <p><?php echo $sem; ?></p>
                </div>
            <?php } ?>
            <?php if ($dotype == 'ATN') { ?>
                <div class="resultdivelement">
                    <h3>Brace Parse</h3>
                    <p><?php echo $result['res']->resultString; ?></p>
                </div>
            <?php } ?>
            <?php if (1 == 1) { ?>
                <div id="tree-container" class="resultdivelement"></div>
            <?php } ?>
        <?php } ?>

        <?php if ($_REQUEST) { ?>
            <div class="resultbox resultdivelement">
                <?php echo $result['dump']; ?>
            </div>
        <?php } ?>
    </body>
</html>
