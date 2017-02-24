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
    DOC_ROOT,
    DOC_ROOT . 'vendors' . DS
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
        $yourDataArray = array();
        function showEpubs($dir,$config){
            $ffs = scandir($dir);
            global $yourDataArray;
            
            foreach($ffs as $ff){
                if($ff != '.' && $ff != '..'){
                    
                    if(is_dir($dir. DS .$ff)){
                        showEpubs($dir. DS .$ff,$config);
                    } else if(is_file($dir. DS .$ff)){
                        $di = pathinfo($ff);
                        $from=mb_detect_encoding($ff); 
                        $file=iconv($from,'UTF-8',$ff);
                        if (isset($di['extension']) && strtolower($di['extension']) == "epub") {
                            $book = rawurlencode($file);
                            $root = str_replace("\\",'/',DOC_ROOT);
                            $url_dir = str_replace("\\",'/',$dir. DS);
                            $url_dir = str_replace($root,"",$url_dir);
                            $url_file = urlencode($url_dir . $file);
                            $epub = new \LivingFlame\eBook\ePubReader($url_dir . $book,$config);
                            $description = strip_tags($epub->getBookDescription(), '<br>');
                            $yourDataArray[] = array(
                                'file' => $ff,
                                'dir' => $dir,
                                'rd_url' => getBaseUrl(FALSE)."/read/?book=".$url_file,
                                'dl_url' => getBaseUrl().$url_file,
                                'description' => substr($description, 0, 200) .((strlen($description) > 200) ? '...' : ''),
                                'title' => strip_tags($epub->getBookTitle()),
                                'cover' => $epub->getCoverUrl(),
                            );
                            unset($epub);
                        }
                    }
                } 
                
            }
        }
        showEpubs(DOC_ROOT . "books",$config);
    $pagination = new ArrayPagination();
    $data = $pagination->generate($yourDataArray,20);
    

    foreach($data as $ebook){
        $file = $ebook['file'];
        $dir = $ebook['dir'];
        $book = rawurlencode($file);
        $root = str_replace("\\",'/',DOC_ROOT);
        $url_dir = str_replace("\\",'/',$dir. DS);
        $url_dir = str_replace($root,"",$url_dir);
        $url_file = urlencode($url_dir . $file);
        $epub = new \LivingFlame\eBook\ePubReader($url_dir . $book,$config);
        echo "<div class=\"book_block\">";
        echo "<div class=\"img-container\"><a href=\"".getBaseUrl(FALSE)."/read/?book=".$url_file."\"><img src=\"".$epub->getCoverUrl()."\" /></a></div>";
        echo "<div class=\"book_info\">";
        echo "<h2>".strip_tags($epub->getBookTitle(), '<br>')."</h2>";
        $description = strip_tags($epub->getBookDescription(), '<br>');
        echo "<p>" . substr($description, 0, 200) .((strlen($description) > 200) ? '...' : '') . "</p>";
        echo "<div class=\"book_links\"><a href=\"".getBaseUrl(FALSE)."/read/?book=".$url_file."\">Read</a> or <a href=\"".getBaseUrl().$url_file."\">Download</a></div>";
        echo "</div>";
        echo "<br style=\"clear:both;\" />";
        echo "</div>";
    }
    ?>
    <div class="pagination"><?php echo $pagination->links(); ?></div>
    <?php
    

}

} catch (Exception $e) {
  echo $e->getMessage();
}
?>
        </div>
		<hr />
		<p><a href="PHPEPubRead-src.zip">Source Code</a></p>
    </body>
</html>