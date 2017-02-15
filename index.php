<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>ePub decode and read test</title>
        <link href="css/notosans-fontface.css" rel="stylesheet"> 
        <style type="text/css">
        * {margin:0;padding:0}
         body {font-family: 'Noto Sans', sans-serif;}
         h2{font-size:20px;margin:10px 0 0 10px}
         .inner{display:inline-block;width:100%;}
        .content{
            width:45%;
            border:1px solid #000;
            margin:10px;
            min-height:206px;
            display:inline-block;
            vertical-align:top;
        }
        .content p.right{
            margin:10px 10px 0 120px;
            font-size:12px;
        }
        .content p.img-container{
            float:left;
            text-align:center;	
            padding:10px;
            width:100px;
            
        }
        .content p.img-container a{text-decoration:none;}
        .content p.img-container img{max-width:100%;max-height:100%;}
        /* mac hide 3 px jog\*/
        * html .content p.right {height:1%}
        /* end hide*/
        .clearer{
        height:1px;
        overflow:hidden;
        margin-top:-1px;
        clear:both;

        }
        </style>
    </head>
    <body>
        <h1>Ebooks</h1>
        <div class="inner">
		<?php
        include_once("RelativePath.php");
        include_once("ePubReader.php");
		$iter = new DirectoryIterator("./books");
		//$iter = scandir("./books");
		//asort($iter);
		foreach ($iter as $file) {
			$di = pathinfo($file);
            $from=mb_detect_encoding($file); 
            $file=iconv($from,'UTF-8',$file);
			if (isset($di['extension']) && strtolower($di['extension']) == "epub") {
                $epub = new ePubReader(rawurlencode("books/" . $file));
                echo "<div class=\"content\">";
                echo "<h2>".$epub->getBookTitle()."</h2>";
                echo "<p class=\"img-container\"><a href=\"scanbook.php?book=books/".urlencode($file)."\"><img src=\"".$epub->getCoverUrl()."\" /></a></p>";
                echo "<p class=\"right\">";
                $description = $epub->getBookDescription();
                echo substr($description, 0, 200) .((strlen($description) > 200) ? '...' : '');
				echo "<br /><br /><a href=\"scanbook.php?book=books/".urlencode($file)."\">Read</a> or <a href=\"books/".urlencode($file)."\">Download</a>\n";
                echo "</p>";
                echo "<div class=\"clearer\"></div>";
                echo "</div>";
			}
		}
		?>
        </div>
		<hr />
		<p><a href="PHPEPubRead-src.zip">Source Code</a></p>
    </body>
</html>
