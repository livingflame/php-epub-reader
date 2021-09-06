<?php
define('INCLUDE_CHECK',true);
define('DS', DIRECTORY_SEPARATOR );
define('DOC_ROOT',      realpath(dirname(__FILE__)) . DS);
ini_set('max_execution_time', 300);

 date_default_timezone_set( 'UTC' );
// ----------------------------------------------------------------------------------------------------
// - Display Errors
// ----------------------------------------------------------------------------------------------------
ini_set('display_errors', 'On');
ini_set('html_errors', 0);

// ----------------------------------------------------------------------------------------------------
// - Error Reporting
// ----------------------------------------------------------------------------------------------------
error_reporting(-1);

// ----------------------------------------------------------------------------------------------------
// - Shutdown Handler
// ----------------------------------------------------------------------------------------------------
function ShutdownHandler()
{
    if(@is_array($error = @error_get_last()))
    {
        return(@call_user_func_array('ErrorHandler', $error));
    };

    return(TRUE);
};

register_shutdown_function('ShutdownHandler');

// ----------------------------------------------------------------------------------------------------
// - Error Handler
// ----------------------------------------------------------------------------------------------------
function ErrorHandler($type, $message, $file, $line)
{
    $_ERRORS = Array(
        0x0001 => 'E_ERROR',
        0x0002 => 'E_WARNING',
        0x0004 => 'E_PARSE',
        0x0008 => 'E_NOTICE',
        0x0010 => 'E_CORE_ERROR',
        0x0020 => 'E_CORE_WARNING',
        0x0040 => 'E_COMPILE_ERROR',
        0x0080 => 'E_COMPILE_WARNING',
        0x0100 => 'E_USER_ERROR',
        0x0200 => 'E_USER_WARNING',
        0x0400 => 'E_USER_NOTICE',
        0x0800 => 'E_STRICT',
        0x1000 => 'E_RECOVERABLE_ERROR',
        0x2000 => 'E_DEPRECATED',
        0x4000 => 'E_USER_DEPRECATED'
    );

    if(!@is_string($name = @array_search($type, @array_flip($_ERRORS))))
    {
        $name = 'E_UNKNOWN';
    };

    return(print(@sprintf("<p>[%s] Error in file '%s' at line %d: %s</p>\n", $name, @basename($file), $line, $message)));
};

$old_error_handler = set_error_handler("ErrorHandler");
include_once DOC_ROOT . 'lib/functions.php';
include_once DOC_ROOT . 'lib/loader.php';

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
$config['root_dir'] = DOC_ROOT;
if(isset($url_path[0]) && $url_path[0] == 'read'){
    $book = NULL;
    if ( isset($_GET['book']) && $_GET['book'] != "") {
        $book = rawurlencode($_GET['book']);
    }
    if ( isset($_GET['show_toc']) && strtolower($_GET['show_toc']) == "true") {
        $config['show_toc'] = TRUE;
    }
    if ( isset($_GET['show']) && $_GET['show'] != "") {
        $config['show_page'] = (int)$_GET['show'];
    }

    if ( isset($_GET['ext']) &&  $_GET['ext'] != "") {
        $config['ext'] = $_GET['ext'];
    }
    if ( isset($_GET['ajax']) &&  strtolower($_GET['ajax'])  == "true") {
        $config['ajax'] = TRUE;
    }
    if ( isset($_GET['toc']) && (strtolower($_GET['toc'])  == "epub2" || strtolower($_GET['toc'])  == "epub3")) {
        $config['toc'] = strtolower($_GET['toc']);
    }
    if ( isset($_GET['extok']) &&  strtolower($_GET['extok'])  == "true") {
        $config['extok'] = TRUE;
    }
    if ( isset($_GET['cover']) &&  strtolower($_GET['cover'])  == "true") {
        $config['show_cover'] = TRUE;
    }
    if ( isset($_GET['dl']) &&  strtolower($_GET['dl'])  == "true") {
        $config['dl'] = TRUE;
    }
    $eReader = new \LivingFlame\eBook\ePubReader($book,$config);
    $eReader->outputEpub();
} else { 
    $yourDataArray = array();
    $cachePath = DOC_ROOT . 'tmp/cache/books.php'; //location of cache file
    $current_time = time();

    if(file_exists($cachePath) && ($current_time < strtotime('+1 day', filemtime($cachePath)))){ //check if cache file exists and hasn't expired yet
        $yourDataArray = getPhpHidden($cachePath,true); 
    }else{
        function showEpubs($dir,$config,&$yourDataArray){
            $ffs = scandir($dir);
            foreach($ffs as $ff){
                if($ff != '.' && $ff != '..'){
                    if(is_dir($dir. DS .$ff)){
                        showEpubs($dir. DS .$ff,$config,$yourDataArray);
                    } else if(is_file($dir. DS .$ff)){
                        $di = pathinfo($ff);
                        if (isset($di['extension']) && strtolower($di['extension']) == "epub") {
                            $root = str_replace("\\",'/',DOC_ROOT);
                            $url_dir = str_replace("\\",'/',$dir. DS);
                            $url_dir = str_replace($root,"",$url_dir);
                            $yourDataArray[] = base64url_encode($url_dir.$ff);
                        }
                    }
                } 
            }
        }
        showEpubs(DOC_ROOT . "books",$config,$yourDataArray);
		savePhpHidden($cachePath,$yourDataArray,true);
    }
    $pagination = new ArrayPagination();
    $yourDataArray = (array) $yourDataArray;
    $data = $pagination->generate($yourDataArray,10);

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>ePub decode and read test</title>
        <link type="text/css" href="<?php echo $config['base_url']; ?>assets/css/style.css" rel="stylesheet">
        <script type="text/javascript" src="<?php echo $config['base_url']; ?>assets/js/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo $config['base_url']; ?>assets/js/script.js"></script>
    </head>
    <body>
        <div id="content">
            <div class="inner">
                <h1 class="title">Ebooks</h1>
                <hr />
                <ul class="book_list">
                <?php
                foreach($data as $ebook){
                    $epub = new \LivingFlame\eBook\ePubReader($ebook,$config);
                    $rd_link = getBaseUrl(FALSE).'/read/?book=' . $ebook;
                    $dl_link = $rd_link . '&dl=true';
                    $title = strip_tags($epub->getBookTitle());
                    echo "<li>";
                    echo "<div class=\"book_block\">";
                    echo "<h2><a href=\"". $rd_link ."\">".$title."</a></h2>";
                    echo "<div class=\"book_info\">"; 
                    echo "<div class=\"img-container\">";
                    echo '<a href="'. $rd_link .'"><img src="'.$rd_link.'&cover=true" /></a></div>';

                    echo "<i>" . implode(', ',(array) $epub->getBookAuthor()) . "</i><br />";
                    $description = strip_tags($epub->getBookDescription(), '<br>');
                    echo "<p>" . substr($description, 0, 200) .((strlen($description) > 200) ? '...' : '') . "</p>";
                    echo '<div class="book_links"><a href="'. $rd_link .'">Read</a> or <a href="'. $dl_link .'">Download</a></div>';
                    echo "</div>";
                    echo "</div>";
                    echo "</li>";
                }
                ?>
                </ul>
                <hr />
                <?php echo $pagination->links(); ?>
            </div>
        </div>
    </body>
</html>
    <?php
}

} catch (Exception $e) {
    echo debugTraceAsString($e->getTrace());
  echo $e->getMessage();
}
?>
