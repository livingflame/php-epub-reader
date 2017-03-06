<?php
namespace LivingFlame\eBook;
class ePubReader {
	public $bookRoot;
	public $book;
	public $file;
	public $fileTypes = array();
	public $fileLocations = array();
	public $filesIds = array();
	public $book_title;
	public $book_author;
	public $book_description;
	public $toc;
	public $files = array();
	public $chapters = array();
	public $headers = array();
	public $css = array();
	public $chaptersId = array();

	public $show_page = 1;
	public $show_toc = FALSE;
    public $extok = FALSE;
	public $ext;

	public $epub_version;
	public $epub3_toc;
	public $cover;
	public $spineIds = array();
	public $spine;
	public $ncx;
	public $valid_epub = FALSE;
	public $auto_title;
	public $config = array();
	public $edit;
	public $zip;

	public function __construct($book_file = null, $config = array()) {
        $this->config = array_merge(array(
            'read_url' => null,
            'show_toc' => FALSE,
            'show_page' => null,
            'ext' => null,
            'extok' => FALSE,
            'auto_title' => FALSE,
            'edit' => FALSE,
        ), $config);
        
        $this->zip = new \ZipArchive;
        $this->edit = $this->config['edit'];
        $this->show_toc = $this->config['show_toc'];
        $this->auto_title = $this->config['auto_title'];

        if($this->config['show_page']){
            $this->show_page = (int)$this->config['show_page'];
        }
        $this->ext = $this->config['ext'];
        $this->extok = $this->config['extok'];
        if($book_file !== NULL){
            $this->book = rawurldecode($book_file);
            if (file_exists($this->book) && is_file($this->book)) {
                $this->file = $this->book;
                $this->valid_epub = $this->parseEpub();
            } else {
                die ("No file");
            }
        }
	}
    public function outputEpub(){
        if ( $this->ext ) {
            if (preg_match('#https*://.+?\..+#i', $this->ext)) {
                if ($this->extok) {
                    header ("Location: " . $this->ext);
                    exit;
                } else {
                    $this->askRedirect();
                }
            } else {
                if($this->valid_epub){
                    $refId = $this->fileLocations[$this->ext];
                    $refType = $this->fileTypes[$refId];
                     if (isset($this->css[$this->ext])) {
                        $this->showCss();
                    } else {
                        $this->outputFile($refType);
                    }
                } else {
                    echo "<p>File '$this->book' is not an ePub file</p>\n";
                }
            }
        } else{
            if($this->valid_epub){
                if($this->edit !== FALSE) {
                    $this->editPage();
                } else {
                    $this->showPage();
                }
            } else {
                echo "<p>File '$this->book' is not an ePub file</p>\n";
            }
        }
        
    }
    public function showPage(){
        $page = $this->getPage($this->show_page-1);?>
		<!DOCTYPE html>
		<html>
		<head>
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <?php 
            $head = $this->updateCSSLinks($page['head'], $page['chapter_dir']);
            echo $this->updateLinks($head, $page['chapter_dir'], $this->chaptersId, $this->css); 
            ?>
			<link rel="stylesheet" type="text/css" href="<?php print $this->config['base_url']; ?>css/style.css" />
			<script type="text/javascript" src="<?php print $this->config['base_url']; ?>js/jquery.min.js"></script>
			<script type="text/javascript" src="<?php print $this->config['base_url']; ?>js/script.js"></script>
		</head>
		<body>
		<?php 
		$nav = "<a class=\"home\" href=\"". $this->config['base_url'] . "\">My Books</a>";
        $nav .= "\n<ul class=\"pagination\">";
		if ($this->show_page > 1) {
			$nav .= "<li><a href=\"" . $this->navAddr . "&show=" . "1\">&laquo;</a></li>";
			$nav .= "<li><a href=\"" . $this->navAddr . "&show=" . ($this->show_page-1) . "\" title=\"prev_page\">&lt;</a></li>";
		} else {
			$nav .= "<li><span>&laquo;</span></li>";
			$nav .= "<li><span>&lt;</span></li>";
		}

		if ($this->show_page < sizeof($this->chapters)) {
			$nav .= "<li><a href=\"" . $this->navAddr . "&show=" . ($this->show_page+1) . "\" title=\"next_page\">&gt;</a></li>";
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
        $nav .= " <a class=\"edit\" href=\"" . $this->navAddr . "&show=" . $this->show_page . "&edit=true\">Edit</a>";
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
                        echo $this->updateLinks($page['content'], $page['chapter_dir'], $this->chaptersId, $this->css);
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
				<div id="speakContainer">
                <a id="speakNext" href="#"><span>&gt;</span></a>
					<a id="speakButton" class="active"><span>Narrate</span></a>
                    <a id="speakPrev" href="#"><span>&lt;</span></a>
					<div style="clear:both"></div>
					<div id="speakBox">
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
    public function editPage(){
        $page = $this->getPage($this->show_page-1);?>
		<!DOCTYPE html>
		<html>
		<head>
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <?php 
            $head = $this->updateCSSLinks($page['head'], $page['chapter_dir']);
            echo $this->updateLinks($head, $page['chapter_dir'], $this->chaptersId, $this->css); 
            ?>
			<link rel="stylesheet" type="text/css" href="<?php print $this->config['base_url']; ?>css/style.css" />
			<script type="text/javascript" src="<?php print $this->config['base_url']; ?>js/jquery.min.js"></script>
			<script type="text/javascript" src="<?php print $this->config['base_url']; ?>js/script.js"></script>
			<script>
            $(document).ready(function(){
              var txt = $('#edit_page'),
                hiddenDiv = $(document.createElement('div')),
                content = null;

                txt.addClass('txtstuff');
                hiddenDiv.addClass('hiddendiv common');

                $('body').append(hiddenDiv);

                txt.on('keyup', function () {
                    content = $(this).val();
                    content = content.replace(/\n/g, '<br>');
                    hiddenDiv.html(content + '<br class="lbr">');
                    $(this).css('height', hiddenDiv.height());
                    $(this).css('width', hiddenDiv.width());
                });
                txt.trigger('keyup');
                $( window ).resize(function() {
                    txt.trigger('keyup');
                });
            });
            
            
            </script>
			
		</head>
		<body>
		<?php 
		$nav = "<a class=\"home\" href=\"". $this->config['base_url'] . "\">My Books</a>";
        $nav .= "\n<ul class=\"pagination\">";
		if ($this->show_page > 1) {
			$nav .= "<li><a href=\"" . $this->navAddr . "&show=" . "1&edit=true\">&laquo;</a></li>";
			$nav .= "<li><a href=\"" . $this->navAddr . "&show=" . ($this->show_page-1) . "&edit=true\" title=\"prev_page\">&lt;</a></li>";
		} else {
			$nav .= "<li><span>&laquo;</span></li>";
			$nav .= "<li><span>&lt;</span></li>";
		}

		if ($this->show_page < sizeof($this->chapters)) {
			$nav .= "<li><a href=\"" . $this->navAddr . "&show=" . ($this->show_page+1) . "&edit=true\" title=\"next_page\">&gt;</a></li>";
			$nav .= "<li><a href=\"" . $this->navAddr . "&show=" . sizeof($this->chapters) . "&edit=true\">&raquo;</a></li>";
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
        $nav .= " <a class=\"read\" href=\"" . $this->navAddr . "&show=" . $this->show_page . "\">read</a>";
		if ($this->show_page < 1 || $this->show_page > sizeof($this->chapters)) {
			$this->show_page = 1;
		}
        ?>
		<div id="outer">
			<div id="contain-all">
				<div class="inner">
					<div class="epubbody" id="epubbody">
					<?php if ($this->show_toc) {
						echo $this->toc;
					} else { ?>
                        <form name="edit_epub_page" method="post">
                            <input type="hidden" name="page_file" value="<?php echo $page['file']; ?>"/>
                            <input type="text" name="edit_title" id="edit_title" value="<?php echo $page['title']; ?>" />
                            <textarea id="edit_page" name="edit_page"><?php 
                            $converter = new \League\HTMLToMarkdown\HtmlConverter(array('header_style'=>'atx'));
                            $markdown = $converter->convert($page['content']);
                            echo $markdown;
                            ?></textarea>
                            <input type="submit" value="Submit" />
                            
                        </form>                        
					<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<div id="top-bar">
			<div id="topbar-inner">
				<div class="nav">
				<?php echo $nav; ?>
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
        if ($this->zip->open($this->file) === TRUE) {
            //Read contents into memory
            $stat = $this->zip->statName($this->bookRoot . $this->ext);
            $content = $this->zip->getFromName($this->bookRoot . $this->ext);
            header("Pragma: public"); // required
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private",false); // required for certain browsers
            header("Content-Disposition: attachment; filename=\"".$this->ext."\";" );
            header("Content-Type: $refType");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: " . $stat['size']);
            ob_clean();
            flush();
            echo $content;
            $zip->close();

        } else {
            echo 'failed';
        }

    }

    public function askRedirect(){ ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" type="text/css" href="style.css" />
<title>Redirection alert</title>
</head>
<body>
    <h1>Redirection alert</h1>
    <p>You are about to leave the epub reader</p>
    <p>Click on the link below if you are certain.</p>
    <p>
        <a href="<?php echo $this->config['read_url']; ?>?extok=true&ext=<?php echo $this->ext; ?>"><?php echo htmlspecialchars($this->ext); ?> </a>
    </p>
</body>
</html>
<?php
exit;
    }

    public function parseEpub(){
        if(!@$this->zip->open($this->file)){
            throw new Exception('Failed to read epub file');
        }
        $mimetype = $this->zip->getFromName('mimetype');
        if($mimetype == false ){
            throw new Exception('Failed to access epub mimetype file');
        }

        $chapterDir = NULL;

        if ($mimetype == 'application/epub+zip') {
            //iterate the archive files array and display the filename or each one
            for ($i = 0; $i < $this->zip->numFiles; $i++) {
                $this->files[$this->zip->getNameIndex($i)] = $this->zip->getNameIndex($i);
            }
            
            $this->navAddr = $this->config['read_url'] . "?book=" . rawurlencode($this->file);
            $container = $this->zip->getFromName("META-INF/container.xml");
            $xContainer = new \SimpleXMLElement($container);
            $opfPath = $xContainer->rootfiles->rootfile['full-path'];
            $opfType = $xContainer->rootfiles->rootfile['media-type'];
            $this->bookRoot = dirname($opfPath) . "/";
            if ($this->bookRoot == "./") {
                $this->bookRoot = "";
            }

            // Read the OPF file:
            if(!isset($this->files["$opfPath"])){
                throw new Exception('Book' . $this->book . ' does not have a proper OPF file');
            }

            $opf = new \SimpleXMLElement($this->zip->getFromName($opfPath));
            $this->book_title = $opf->metadata->children('dc', true)->title;
            $this->book_author = $opf->metadata->children('dc', true)->creator;
            $this->book_description = $opf->metadata->children('dc', true)->description;
            $this->epub_version = (string)$opf->attributes()->version;
            $this->spine = $opf->spine;
            
            foreach ($this->spine->itemref as $itemref) {
                $id = (string)$itemref['idref'];
                if(isset($itemref['linear'])){
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
                    $cssData = $this->zip->getFromName($this->bookRoot . $this->filesIds[$id]);
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
                        'content' => $this->zip->getFromName($this->bookRoot . $this->filesIds[$itemref]),
                        'itemref' => $itemref
                    );
                }
            }
            foreach($opf->metadata->meta as $key => $meta){

                if($meta->attributes()->name == 'cover'){
                    $cover_id = (string)$meta->attributes()->content;
                    $this->cover = $this->config['read_url'] . "?book=" . rawurlencode($this->file) . "&ext=" . rawurlencode($this->filesIds[$cover_id]);
                }

            }
            $ncxId = (string)$this->spine['toc'];
            $this->ncx = $this->zip->getFromName($this->bookRoot . $this->filesIds[$ncxId]);
            $this->buildToc($chapterDir);

            return TRUE;
        }
        return FALSE;
    }
    public function editFile($file_to_modify,$new_content){
        if(!@$this->zip->open($this->file)){
            throw new Exception('Failed to read epub file');
        }
        $this->zip->deleteName($file_to_modify);
        $this->zip->addFromString($file_to_modify, $new_content);
        $this->zip->close();
    }
    public function getBookDescription(){
        return $this->book_description;
    }
    public function getBookAuthor(){
        return $this->book_author;
    }
    public function getBookTitle(){
        return $this->book_title;
    }
    public function getCoverUrl(){
        return $this->cover;
    }
    public function getPage($chapter_order){
        if(isset($this->chapters[$chapter_order])){
            if($chapter_order != 0 && $this->edit === FALSE && $this->show_toc){
                $absolute_url = $this->fullUrl( $_SERVER );
                $file = 'tmp/' . md5($this->getBookTitle()) . '.txt';
                file_put_contents($file, $absolute_url);
            }
            if($chapter_order == 0 && !isset($_GET['show'])){
                $filename = 'tmp/' . md5($this->getBookTitle()) . '.txt';
                if (file_exists($filename)) {
                    $location = file_get_contents($filename);
                    header('refresh:2;url=' . $location);
                }
            }

            $chapter_data = $this->chapters[$chapter_order];
            $chapterDir = $chapter_data["dir"];
            $chapter = $chapter_data["content"];
            $itemref = $chapter_data["itemref"];

            $headStart = strpos($chapter, "<head");
            $headStart = strpos($chapter, ">", $headStart) +1;
            $headEnd = strpos($chapter, "</head", $headStart);
            
            $head =  substr($chapter, $headStart, ($headEnd-$headStart));
            $head = trim(preg_replace('/\s+/', ' ', $head)); 
            preg_match("/\<title\>(.*)\<\/title\>/siU",$head,$title);
            $page_title = $title[1];
            if($this->auto_title){
                if($chapter_order == 0){
                    $head = str_replace ( $title[1] , $this->getBookTitle() . ":: Cover", $head );
                }elseif($this->show_toc){
                    $head = str_replace ( $title[1] , $this->getBookTitle() . ":: Table of Contents", $head );
                } else {
                    $head = str_replace ( $title[1] , $chapter_order . "::" . $this->getBookTitle() , $head );
                }
            }

            if (!preg_match('#<meta.+?http-equiv\s*=\s*"Content-Type#i', $head)) {
                $head = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n".$head;
            }

           
            $head = preg_replace('/<link\s[^>]*type\s*=\s*"application\/vnd.adobe-page-template\+xml\"[^>]*\/>/i','',$head);
            
            $start = strpos($chapter, "<body");
            $start = strpos($chapter, ">", $start) +1;
            $end = strpos($chapter, "</body", $start);
            $chapter =  substr($chapter, $start, ($end-$start));

            if($this->epub3_toc == $itemref){
                $this->toc = $this->updateLinks($chapter, $chapterDir, $this->chaptersId, $this->css);
            }
            return array(
                'chapter_dir' => $chapterDir,
                'title' => $page_title,
                'file' => $this->bookRoot . $this->filesIds[$itemref],
                'head' => $head,
                'content' => $chapter
            );
        }
        return array(
            'title' => '404',
            'file' => '',
            'head' => '<title>404</title><meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8"/>',
            'content' => '<div style="text-align: center; page-break-after: always;"><p>404 Page Not Found</p></div>'
        );
    }
    public function buildToc($chapterDir){
        if(!$this->toc){
            // Read the NCX file:
            $xNcx = new \SimpleXMLElement($this->ncx);
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
        
        preg_match_all('#\s+xlink:href\s*=\s*"(.+?)"#im', $chapter, $links, PREG_SET_ORDER);
        $itemCount = count($links);
        for ($idx = 0; $idx < $itemCount; $idx++) {
            $link = $links[$idx];
            if (preg_match('#https*://.+?\..+#i', $link[1])) {
                $chapter = str_replace($link[0], " src=\".\"", $chapter);
            } else {
                $refFile = RelativePath::pathJoin($chapterDir, $link[1]);
                $chapter = str_replace($link[0], " xlink:href=\"" . $this->navAddr . "&ext=" . rawurlencode($refFile) . "\"", $chapter);
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
                    $chapter = str_replace($link[0], " href=\"". $this->config['read_url'] . "?ext=" . rawurlencode($link[1]) . "\"", $chapter);
                } else {
                    $refFile = RelativePath::pathJoin($chapterDir, $link[1]);
                    $id = "";
                    if (strpos($refFile, "#") > 0) {
                        $array = explode ("#", $refFile);
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
        $cssData = str_replace('@page', ".epubbody", $cssData);
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
    function urlOrigin( $s, $use_forwarded_host = false )
    {
        $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
        $sp       = strtolower( $s['SERVER_PROTOCOL'] );
        $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
        $port     = $s['SERVER_PORT'];
        $port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
        $host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
        $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
        return $protocol . '://' . $host;
    }

    function fullUrl( $s, $use_forwarded_host = false )
    {
        return $this->urlOrigin( $s, $use_forwarded_host ) . $s['REQUEST_URI'];
    }
    
}