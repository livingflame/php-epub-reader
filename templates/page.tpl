<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link id="xui_style" rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>assets/css/style.css" />
        <?php echo $head; ?>
        <link id="xui_icofont" rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>assets/icofont/icofont.min.css" />
        <script type="text/javascript" src="<?php echo $base_url; ?>assets/js/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo $base_url; ?>assets/js/script.js"></script>
    </head>
    <body>
        <div id="content">
            <div class="inner">
                <div class="epubbody" id="epubbody">
                <?php echo $content; ?>
                </div>
            </div>
        </div>
        <div id="aside">
            <div class="inner">
                <?php echo $toc; ?>
            </div>
        </div>
        <div id="header">
            <div class="inner">
                <div class="nav">
                    <div class="nav_container">
                    <?php echo $nav; ?>
                    </div>
                    <div id="speakContainer">
                    <?php echo $tts_form; ?>
                    </div>
                </div>
            </div>
        </div>
        <div id="footer">
            <div class="inner">
                <div class="book_title"><?php echo $book; ?></div>
                <div class="book_percent"><?php echo $percentage; ?>%</div>
                <div class="page_title"><?php echo $title; ?></div>
            </div>
        </div>

    </body>
</html>