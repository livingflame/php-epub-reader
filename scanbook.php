<?php
date_default_timezone_set( 'UTC' );
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
session_start();

include_once("RelativePath.php");
include_once("ePubReader.php");

if ( isset($_GET['book']) && $_GET['book'] != "") {
    $config = array();
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
    $eReader = new ePubReader(rawurlencode($_GET['book']),$config);
    $eReader->outputEpub();
}


?>

