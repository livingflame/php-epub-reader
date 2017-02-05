<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>ePub decode and read test</title>
        <link href="css/notosans-fontface.css" rel="stylesheet"> 
        <style type="text/css">
        body {font-family: 'Noto Sans', sans-serif;}
        </style>
    </head>
    <body>
		<?php
		echo "<h1>ePub decode and read test</h1>\n";

		$iter = new DirectoryIterator("./books");
		//$iter = scandir("./books");
		//asort($iter);
		foreach ($iter as $file) {
			$di = pathinfo($file);
            $from=mb_detect_encoding($file); 
            $file=iconv($from,'UTF-8',$file);
            
			if (isset($di['extension']) && strtolower($di['extension']) == "epub") {
				echo "<p>".$file."<br />\n - <a href=\"scanbook.php?book=books/".urlencode($file)."\">Read</a> or <a href=\"books/".urlencode($file)."\">Download</a> </p>\n";
			}
		}
		?>
		<hr />
		<p><a href="PHPEPubRead-src.zip">Source Code</a></p>
    </body>
</html>
