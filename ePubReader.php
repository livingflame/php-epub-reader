<?php 
class ePubReader {
	public $bookRoot;
	public $file;
	public $fileTypes = array();
	public $fileLocations = array();
	public $filesIds = array();
	public $bookTitle;
	public $bookAuthor;
	public $toc;
	public $files = array();
	public $chapters = array();
	public $headers = array();
	public $css = array();
	public $chaptersId = array();

	public $show_page = 1;
	public $show_toc = FALSE;
	public $ext;

	public $epub_version;
	public $epub3_toc;
	public $spineIds = array();
	public $spine;
	public $ncx;

	public function __construct() {
        
        if ( isset($_GET['showToc']) && $_GET['showToc'] == "true") {
            $this->show_toc = TRUE;
        }

        if ( isset($_GET['book']) && $_GET['book'] != "") {
            $tempFile = rawurldecode($_GET['book']);
            if (file_exists($tempFile) && is_file($tempFile)) {
                $book = $tempFile;
            }
        }

        if (!isset($book)) {
            die ("No file");
        }
        if ( isset($_GET['show']) && $_GET['show'] != "") {
            $this->show_page = (int)$_GET['show'];
        }
        if ( isset($_GET['ext']) &&  $_GET['ext'] != "") {
            $this->ext = $_GET['ext'];
        }
        if($this->parseEpub($book)){
            if ( $this->ext ) {
                if (preg_match('#https*://.+?\..+#i', $this->ext)) {
                    if (isset($_GET['extok']) && $_GET['extok'] == "true") {
                        header ("Location: " . $this->ext);
                        exit;
                    } else {
                        $this->askRedirect();
                    }
                } else {
                    $refId = $this->fileLocations[$this->ext];
                    $refType = $this->fileTypes[$refId];
                     if (isset($this->css[$this->ext])) {
                        $this->showCss();
                    } else {
                        $this->outputFile($refType);
                    }
                }
            } else {
                $this->showPage();
            }
        }
        else {
            echo "<p>File '$file' is not an ePub file</p>\n";
        }
	}
    
    public function showPage(){
        $page = $this->getPage($this->show_page-1); ?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
		"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<?php print $page['head']; ?>
			<link rel="stylesheet" type="text/css" href="css/style.css" />
			<script type="text/javascript" src="js/jquery.min.js"></script>
			<script type="text/javascript" src="js/script.js"></script>
		</head>
		<body>
		<?php 
		$nav .= "<a class=\"home\" href=\"index.php\">My Books</a>";
        $nav .= "\n<ul class=\"pagination\">";
		if ($this->show_page > 1) {
			$nav .= "<li><a href=\"" . $this->navAddr . "&show=" . "1\">&laquo;</a></li>";
			$nav .= "<li><a href=\"" . $this->navAddr . "&show=" . ($this->show_page-1) . "\">&lt;</a></li>";
		} else {
			$nav .= "<li><span>&laquo;</span></li>";
			$nav .= "<li><span>&lt;</span></li>";
		}
        
		if ($this->show_page < sizeof($this->chapters)) {
			$nav .= "<li><a href=\"" . $this->navAddr . "&show=" . ($this->show_page+1) . "\">&gt;</a></li>";
			$nav .= "<li><a href=\"" . $this->navAddr . "&show=" . sizeof($this->chapters) . "\">&raquo;</a></li>";
		} else {
			$nav .= "<li><span>&gt;</span></li>";
			$nav .= "<li><span>&raquo;</span></li>";
		}
        $nav .= "</ul>";

        if($this->show_toc){
            $nav .= " <a class=\"toc\" href=\"" . $this->navAddr . "&show=" . $this->show_page . "\">Back</a>";
        } else {
            $nav .= " <a class=\"toc\" href=\"" . $this->navAddr . "&show=" . $this->show_page . "&showToc=true" . "\">Table of Contents</a>";
        }
		if ($this->show_page < 1 || $this->show_page > sizeof($this->chapters)) {
			$this->show_page = 1;
		}
?>
		<div id="outer">
			<div id="contain-all">
				<div class="inner">
					<div class="epubbody" id="epubbody">
					<?php 
					if ($this->show_toc) {
						echo $this->toc;
					} else {
						echo $page['content'];;
					}
					?>
					</div>
				</div>
			</div>
		</div>
		<div id="top-bar">
			<div id="topbar-inner">
				<div class="nav">
				<?php echo $nav; ?>
				<div id="loginContainer">
					<a id="loginButton" class="active"><span>Listen</span></a>
					<div style="clear:both"></div>
					<div id="loginBox">
						<p hidden class="js-api-support">API not supported</p>
						<form id="tts" action="" method="get">
							<fieldset id="tts_body">
								<fieldset class="field-wrapper">
									<select id="voice"></select>
								</fieldset>
								<fieldset class="field-wrapper">
									<label for="rate">Rate:</label>
									<input type="range" id="rate" min="0.5" max="2" value="1" step="0.1">
								</fieldset>
								<fieldset class="field-wrapper">
									<label for="pitch">Pitch:</label>
									<input type="range" id="pitch" min="0" max="2" value="1" step="0.1">
								</fieldset>
							</fieldset>
							<fieldset class="buttons-wrapper">
								<button type="button" id="button-speak" class="button">Speak</button>
								<button type="button" id="button-stop" class="button">Stop</button>
								<button type="button" id="button-pause" class="button">Pause</button>
								<button type="button" id="button-resume" class="button">Resume</button>
							</fieldset>
						</form>
					</div>
				</div>
				</div>
			</div>
		</div>
		<div id="footer"><div id="footer-inner"><div class="nav"><?php echo $nav; ?></div></div></div>
	</body>
</html>
		<?php 
    }
    
    public function showCss(){
        if(isset($this->css[$this->ext])){
            $cssData = $this->css[$this->ext];
            header("Pragma: public"); // required
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private",false); // required for certain browsers
            header("Content-Type: text/css");
            header("Content-Disposition: attachment; filename=\"".$this->ext."\";" );
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: " . strlen($cssData));
            ob_clean();
            flush();
            echo $cssData;
        }
    }
    public function outputFile($refType){
        $zipArchive = zip_open($this->file);
        while($zipEntry = zip_read($zipArchive)) {
            if (zip_entry_filesize($zipEntry) > 0) {
                if (zip_entry_name($zipEntry) == ($this->bookRoot . $this->ext)) {
                    header("Pragma: public"); // required
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    header("Cache-Control: private",false); // required for certain browsers
                    header("Content-Disposition: attachment; filename=\"".$this->ext."\";" );
                    header("Content-Type: $refType");
                    header("Content-Transfer-Encoding: binary");
                    header("Content-Length: " . zip_entry_filesize($zipEntry));
                    ob_clean();
                    flush();
                    echo $this->readZipEntry($zipEntry);
                }
            }

        }
        zip_close($zipArchive);
        exit;
    }

    public function askRedirect(){ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
   "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" />
<title>Redirection alert</title>
</head>
<body>
    <h1>Redirection alert</h1>
    <p>You are about to leave the epub reader</p>
    <p>Click on the link below if you are certain.</p>
    <p>
        <a href="scanbook.php?extok=true&ext=<?php echo $this->ext; ?>"><?php echo htmlspecialchars($this->ext); ?> </a>
    </p>
</body>
</html>
<?php
exit;
    }

    public function parseEpub($file){
        $this->file = $file;
        $zipArchive = zip_open($file);
        $zipEntry = zip_read($zipArchive);
        $name = zip_entry_name($zipEntry);
        $size = zip_entry_filesize($zipEntry);
        $chapterDir = NULL;

        if ($name == "mimetype" && zip_entry_read($zipEntry, $size) == 'application/epub+zip') {
            $this->files[$name] = $zipEntry;
            $this->navAddr = "scanbook.php?book=" . rawurlencode($file);

            while($zipEntry = zip_read($zipArchive)) {
                if (zip_entry_filesize($zipEntry) > 0) {
                    $this->files[zip_entry_name($zipEntry)] = $zipEntry;
                }
            }

            $compressed = 0;
            $uncompressed = 0;

            while (list($name, $zipEntry) = each($this->files)) {
                $compressed += zip_entry_compressedsize($zipEntry);
                $uncompressed += zip_entry_filesize($zipEntry);
            }

            $zipEntry = $this->files["META-INF/container.xml"];
            $container = $this->readZipEntry($zipEntry);
            $xContainer = new SimpleXMLElement($container);
            $opfPath = $xContainer->rootfiles->rootfile['full-path'];
            $opfType = $xContainer->rootfiles->rootfile['media-type'];
            $this->bookRoot = dirname($opfPath) . "/";
            if ($this->bookRoot == "./") {
                $this->bookRoot = "";
            }


            // Read the OPF file:

            $opf = new SimpleXMLElement($this->readZipEntry($this->files["$opfPath"]));

            $this->epub_version = (string)$opf->attributes()->version;
            $this->spine = $opf->spine;

            foreach ($this->spine->itemref as $itemref) {
                $id = (string)$itemref['idref'];
                if(isset($itemref['linear'])){
                    //var_dump($itemref['linear']);
                    $this->epub3_toc = $id;
                }
                $this->spineIds[] = $id;
      
            }
            
            $manifest = $opf->manifest;
            foreach ($manifest->item as $item) {
                $id = (string)$item["id"];
                $href = (string)$item['href'];
                $this->filesIds[$id] = $href;
                $this->fileLocations[$href] = $id;
                $this->fileTypes[$id] = (string)$item['media-type'];
                if ($this->fileTypes[$id] == "text/css") {
                    $cssData = $this->readZipEntry($this->files[$this->bookRoot . $this->filesIds[$id]]);
                    $chapterDir = dirname($this->filesIds[$id]);
                    $this->css[$this->filesIds[$id]] = $this->updateCSSLinks($cssData, $chapterDir);
                }
            }

            $chapterNum = 1;
            foreach($this->spineIds as $order => $itemref){
                if ($this->fileTypes[$itemref] == "application/xhtml+xml") {
                    $this->chaptersId[$this->filesIds[$itemref]] = $chapterNum++;
                    $this->chapters[] = array(
                        'dir' => dirname($this->filesIds[$itemref]),
                        'content' => $this->readZipEntry($this->files[$this->bookRoot . $this->filesIds[$itemref]]),
                        'itemref' => $itemref
                    );
                }
            }
            $ncxId = (string)$this->spine['toc'];
            $ncxPath = $this->bookRoot . $this->filesIds[$ncxId];
            $this->ncx = $this->readZipEntry($this->files[$ncxPath]);
            $this->buildToc($chapterDir);

            zip_close($zipArchive);
            return TRUE;
        }
        return FALSE;
    }

    public function getPage($chapter_order){
        $chapter_data = $this->chapters[$chapter_order];
        $chapterDir = $chapter_data["dir"];
        $chapter = $chapter_data["content"];
        $itemref = $chapter_data["itemref"];
        $headStart = strpos($chapter, "<head");
        $headStart = strpos($chapter, ">", $headStart) +1;
        $headEnd = strpos($chapter, "</head", $headStart);
        $head =  substr($chapter, $headStart, ($headEnd-$headStart));

        if (!preg_match('#<meta.+?http-equiv\s*=\s*"Content-Type#i', $head)) {
            $head = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n".$head;
        }

        $head = $this->updateCSSLinks($head, $chapterDir);
        $head = preg_replace('/<link\s[^>]*type\s*=\s*"application\/vnd.adobe-page-template\+xml\"[^>]*\/>/i','',$head);
        
        $head = $this->updateLinks($head, $chapterDir, $this->chaptersId, $this->css);

        $start = strpos($chapter, "<body");
        $start = strpos($chapter, ">", $start) +1;
        $end = strpos($chapter, "</body", $start);
        $chapter =  substr($chapter, $start, ($end-$start));
        $page = $this->updateLinks($chapter, $chapterDir, $this->chaptersId, $this->css);

        if($this->epub3_toc == $itemref){
            $this->toc = $page;
        }
        
        return array(
            'head' => $head,
            'content' => $page
        );
    }
    public function buildToc($chapterDir){
        if(!$this->toc){
            // Read the NCX file:
            $xNcx = new SimpleXMLElement($this->ncx);
            $this->toc = $this->updateLinks($this->parseNavMap($xNcx->navMap), $chapterDir, $this->chaptersId, $this->css);
        }
    }


    public function parseNavMap($navMap, $level = 0) {
        $indent = str_repeat("    ", $level);
        $nav = $indent . "<ul>\n";

        foreach ($navMap->navPoint as $item) {
            $id = (string)$item["id"];
            $label = (string)$item->navLabel->text;
            $src =  (string)$item->content["src"];
            $nav .= $indent . "  <li><a href=\"" . $src . "\">$label</a></li>\n";

            if ((bool)$item->navPoint == TRUE) {
                $nav .= $this->parseNavMap($item, $level+1);
            }
        }
        $nav .= $indent . "</ul>\n";
        return $nav;
    }
	public function readZipEntry($zipEntry) {
        return zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
    }
    
    public function updateLinks($chapter, $chapterDir, $chaptersId, $css) {

        preg_match_all('#\s+src\s*=\s*"(.+?)"#im', $chapter, $links, PREG_SET_ORDER);
        $itemCount = count($links);
        for ($idx = 0; $idx < $itemCount; $idx++) {
            $link = $links[$idx];
            if (preg_match('#https*://.+?\..+#i', $link[1])) {
                $chapter = str_replace($link[0], " src=\".\"", $chapter);
            } else {
                $refFile = RelativePath::pathJoin($chapterDir, $link[1]);
                $chapter = str_replace($link[0], " src=\"" . $this->navAddr . "&ext=" . rawurlencode($refFile) . "\"", $chapter);
            }
        }

        preg_match_all('#\s+href\s*=\s*"(.+?)"#im', $chapter, $links, PREG_SET_ORDER);

        $itemCount = count($links);
        for ($idx = 0; $idx < $itemCount; $idx++) {
            $link = $links[$idx];
            $link_id = $link[1];
            if(isset($chaptersId[$link_id])){
                $chapter = str_replace($link[0], " href=\"" . $this->navAddr  . "&show=" . $chaptersId[$link_id] . "\"", $chapter);
            } else {
                if (preg_match('#https*://.+?\..+#i', $link[1])) {
                    //$chapter = str_replace($link[0], " href=\"scanbook.php?ext=" . rawurlencode($link[1]) . "\"", $chapter);
                } else {
                    $refFile = RelativePath::pathJoin($chapterDir, $link[1]);
                    $id = "";
                    if (strpos($refFile, "#") > 0) {
                        $array = split("#", $refFile);
                        $refFile = $array[0];
                        $id = "#" . $array[1];
                    }
                    
                    if (isset($chaptersId[$refFile])) {
                        $chapter = str_replace($link[0], " href=\"" . $this->navAddr  . "&show=" . $chaptersId[$refFile] . $id . "\"", $chapter);
                    }  else {
                        $chapter = str_replace($link[0], " href=\"" . $this->navAddr  . "&ext=" . rawurlencode($refFile) . $id . "\"", $chapter);
                    }
                }
            }
        }

        return $chapter;
    }
    
    public function updateCSSLinks($cssData, $chapterDir) {
        preg_match_all('#url\s*\([\'\"\s]*(.+?)[\'\"\s]*\)#im', $cssData, $links, PREG_SET_ORDER);
        
        $itemCount = count($links);
        for ($idx = 0; $idx < $itemCount; $idx++) {
            $link = $links[$idx];
            if (preg_match('#https*://.+?\..+#i', $link[1])) {
                $cssData = str_replace($link[0], " src=\".\"", $chapterDir);
            } else {
                $refFile = RelativePath::pathJoin($chapterDir, $link[1]);
                $cssData = str_replace($link[0], " src=\"" . $this->navAddr . "&ext=" . rawurlencode($refFile) . "\"", $cssData);
            }
        }

        return $cssData;
    }
}