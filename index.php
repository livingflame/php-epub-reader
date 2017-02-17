<?php
define('INCLUDE_CHECK',true);
define('DS', DIRECTORY_SEPARATOR );
define('DOC_ROOT',      realpath(dirname(__FILE__)) . DS);

date_default_timezone_set( 'UTC' );
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
include_once DOC_ROOT . 'functions.php';
include_once DOC_ROOT . 'loader.php';

try {

$loader = new loader(array(
    'LivingFlame' => DOC_ROOT . 'lib',
    'League' => DOC_ROOT . 'vendors' . DS . 'League',
));
$loader->paths( array(
    DOC_ROOT
));

$path_info = !empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : (!empty($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : '');
$url_path = array_values(array_filter(explode('/',$path_info)));
$config = array();
$config['base_url'] = getBaseUrl();
$config['read_url'] = getBaseUrl(FALSE) . "/read/";
if(isset($url_path[0]) && $url_path[0] == 'read'){
    if ( isset($_GET['book']) && $_GET['book'] != "") {
        
        if ( isset($_GET['showToc']) && $_GET['showToc'] == "true") {
            $config['show_toc'] = TRUE;
        }
        if ( isset($_GET['show']) && $_GET['show'] != "") {
            $config['show_page'] = (int)$_GET['show'];
        }
        if ( isset($_GET['ext']) &&  $_GET['ext'] != "") {
            $config['ext'] = $_GET['ext'];
        }
        if ( isset($_GET['extok']) &&  $_GET['extok'] != "") {
            $config['extok'] = TRUE;
        }
        
        
        $eReader = new \LivingFlame\eBook\ePubReader(rawurlencode($_GET['book']),$config);
        $eReader->outputEpub();
    }
} else {?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>ePub decode and read test</title>
        <link href="css/style.css" rel="stylesheet"> 
    </head>
    <body>
        <h1>Ebooks</h1>
        <div class="book_list">
		<?php
		$iter = new \DirectoryIterator(DOC_ROOT . "books");
		//$iter = scandir("./books");
		//asort($iter);
		foreach ($iter as $file) {
			$di = pathinfo($file);
            $from=mb_detect_encoding($file); 
            $file=iconv($from,'UTF-8',$file);
			if (isset($di['extension']) && strtolower($di['extension']) == "epub") {
                $epub = new \LivingFlame\eBook\ePubReader(rawurlencode("books/" . $file),$config);
                echo "<div class=\"book_block\">";
                
                echo "<div class=\"img-container\"><a href=\"".getBaseUrl(FALSE)."/read/?book=books/".urlencode($file)."\"><img src=\"".$epub->getCoverUrl()."\" /></a></div>";
                echo "<div class=\"book_info\">";
                echo "<h2>".$epub->getBookTitle()."</h2>";
                $description = $epub->getBookDescription();
                echo "<p>" . substr($description, 0, 200) .((strlen($description) > 200) ? '...' : '') . "</p>";
				echo "<div class=\"book_links\"><a href=\"".getBaseUrl(FALSE)."/read/?book=books/".urlencode($file)."\">Read</a> or <a href=\"".getBaseUrl()."books/".urlencode($file)."\">Download</a></div>";
                echo "</div>";
                echo "<br style=\"clear:both;\" />";
                echo "</div>";
			}
		}
		?>
        </div>
		<hr />
		<p><a href="PHPEPubRead-src.zip">Source Code</a></p>
    </body>
</html>
<?php
}

} catch (Exception $e) {
  echo $e->getMessage();
}
?>