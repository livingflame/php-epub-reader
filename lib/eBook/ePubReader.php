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
	public $chapters = array();

	public $css = array();
	public $chaptersId = array();

	public $show_page = 1;
    public $extok = FALSE;
	public $ext;

	public $epub_version;
	public $cover;

	public $valid_epub = FALSE;
    
    
    
	public $config = array();
	public $zip;
	public $zip_open;
	public $cache_file;
	public $book_info;
	public $base64url;

	public function __construct($book_file = null, $config = array()) {
        $this->config = array_merge(array(
            'root_dir' => '',
            'read_url' => null,
			'dl' => FALSE,
            'show_page' => null,
            'ext' => null,
            'extok' => FALSE,
            'auto_title' => FALSE,
            'ajax' => FALSE,
            'toc' => NULL,
            'show_cover' => false,
        ), $config);
        
        $this->zip = new \ZipArchive;
        if ( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' )
        {
            $this->config['ajax'] = true;
        }
        if($this->config['show_page']){
            $this->show_page = (int)$this->config['show_page'];
        }
        $this->ext = $this->config['ext'];
        $this->extok = $this->config['extok'];
        if($book_file !== NULL){
            $this->base64url = $book_file;
            $epub_file = base64url_decode($book_file);
			$this->file = htmlentities(iconv(mb_detect_encoding($epub_file, mb_detect_order(), true),'UTF-8',$epub_file), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (file_exists($this->config['root_dir'].$epub_file) && is_file($this->config['root_dir'].$epub_file)) {
				$this->book = $epub_file;
				$this->nav_address = $this->config['read_url'] . "?book=" . $book_file;
                $this->cache_file = $this->config['root_dir'] . 'tmp/cache/' . $book_file . '.php';

                if (!file_exists($this->cache_file)) {
                    $book_info =  array( 'sha1' => sha1_file($this->config['root_dir'].$this->book), 'show' => 0 );
					savePhpHidden($this->cache_file,$book_info,true); 
                }
                $this->valid_epub = $this->parseEpub();
                
            } else {
                echo ($this->file . " does not exist");
            }
        }
	}
	public function download( $inline=false ){
		if(!empty($this->book)){
            
            $extract_dir = $this->config['root_dir'] . 'tmp/extract/' . sha1_file($this->config['root_dir'].$this->book) . '/';
            if(!is_dir($extract_dir)){
                mkdir($extract_dir, 0700);
            }

            if($this->zip->open($this->config['root_dir'] . $this->book)){
                $this->zip_open = true;
                
                /*
                    Some files cannot be extracted due to invalid filename
                */
                for ($i = 0; $i < $this->zip->numFiles; $i++) {
                    $stat = $this->zip->statIndex($i);
                    $name_index = $stat['name'];
                    $path_parts = pathinfo($name_index);
                    $dirname = isset($path_parts['dirname']) ? $path_parts['dirname'] : '';
                    $filename = isset($path_parts['filename']) ? $path_parts['filename'] : '';
                    if($dirname == '.'){
                        $dirname = '';
                        $path_parts['dirname'] = '';
                    }
                    if($stat['crc'] == 0){
                        if( !is_dir($extract_dir . $dirname) ){
                            mkdir($extract_dir . $dirname, 0700);
                        }
                    } else {
                        if( !is_dir($extract_dir . $dirname) ){
                            mkdir($extract_dir . $dirname, 0700);
                        }
                        if(!file_exists($extract_dir . $name_index)){
                            if($filename){
                                $path_parts['filename'] = slugify($filename);
                            }

                            $new_name_index = reversePathinfo($path_parts);
                            file_put_contents($extract_dir . $new_name_index,$this->zip->getFromIndex($i));
                        }
                    }
                }
                
                $mimetype = $this->zip->getFromName('mimetype');
                if($mimetype == false ){
                    $extract_dir = file_put_contents($extract_dir . 'mimetype','application/epub+zip');
                }

                $chapterDir = NULL;
                $has_end = false;
                $ncx = NULL;
                $tidy_config = array(
                    'numeric-entities' => true,
                    'markup' => true,
                    'output-xml' => true,
                    'input-xml' => true
                );
                
                $container = file_get_contents($extract_dir . "META-INF/container.xml");
                $xContainer = new \SimpleXMLElement(tidy_repair_string($container,$tidy_config));
                $opfPath = $xContainer->rootfiles->rootfile['full-path'];
                $opfType = $xContainer->rootfiles->rootfile['media-type'];
                $bookRoot = dirname($opfPath) . "/";
                if ($bookRoot == "./") {
                    $bookRoot = "";
                }
                $opf = new \SimpleXMLElement(tidy_repair_string(file_get_contents($extract_dir . $opfPath),$tidy_config));
                
                $doctype = '<!DOCTYPE html>';
                if($opf['version'] !== '3.0'){
                    $doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
                }
                $metadata = $opf->metadata;
                $chapterDir = NULL;
                $manifest = $opf->manifest;
                foreach ($manifest->item as $item) {
                    $id = (string)$item["id"];
                    $item["id"] = slugify($item["id"]);
                    
                    $href = (string)$item['href'];
                    $href = sluggedFilename($href);
					$item['href'] = $href;

                    $media = (string)$item['media-type'];
                    $properties = (isset($item['properties'])) ? (string) $item['properties'] : null;

                    if ($media == "application/xhtml+xml") {
                        $path_parts = pathinfo($href);
                        $chapterDir = $path_parts['dirname'];
                        
                        $oldContents = file_get_contents($extract_dir . $bookRoot . $href);
                        //Modify contents:
                        $tconfig = array(
                        
                            'join-styles' => true,
                            'clean' => true,
                            'numeric-entities' => true,
                            'markup' => true,
                            'indent' => true,
                            'lower-literals' => true,
                            'output-xhtml' => true,
                            'doctype' => 'strict',
                            'drop-empty-paras' => true,
                            'drop-proprietary-attributes' => true,
                            'enclose-block-text' => true,
                            'enclose-text' => true,
                        );
                        $newContents = tidy_repair_string($oldContents,$tconfig);
                        $newContents = preg_replace("#<!DOCTYPE(.*?)>#sim", $doctype, $newContents);
                        $newContents = $this->correctcsslinks($newContents);
                        $newContents = $this->correctLinks($newContents);
                        file_put_contents($extract_dir . $bookRoot . $href,$newContents);
                        if($properties == 'nav'){
                            $has_end = true;
                        }
                    }

                    if($media == 'application/x-dtbncx+xml'){
                        $ncx_content = file_get_contents($extract_dir . $bookRoot . $href);
                        $ncx_content = tidy_repair_string($ncx_content,$tidy_config);
                        $ncx_content = preg_replace("#<!DOCTYPE(.*?)>#sim", '',$ncx_content);
                        $ncx = new \SimpleXMLElement($ncx_content);
                        $this->rebuildNavMap($ncx->navMap);
                        
                        $ncx->asXML($extract_dir . $bookRoot . $href);
                        $ncx_content = file_get_contents($extract_dir . $bookRoot . $href);
                        $ncx_content = tidy_repair_string($ncx_content,$tidy_config);
                        file_put_contents($extract_dir . $bookRoot . $href,$ncx_content);
                    }
                }

                if($opf['version'] >= '3.0' && !$has_end && $ncx){
                    $toc_end =  template( 'toc', array('toc' => $this->makeNCXNav($ncx->navMap)) );
                    $toc_end = str_replace($chapterDir.'/','',$toc_end);
                    file_put_contents($extract_dir . $bookRoot . $chapterDir . '/epub_end.xhtml',$toc_end);

                    $item = $manifest->addChild('item');
                    $item->addAttribute('id', 'epub_end');
                    $item->addAttribute('href', $chapterDir .'/' . 'epub_end.xhtml');
                    $item->addAttribute('properties', 'nav');
                    $item->addAttribute('media-type', 'application/xhtml+xml');
                    
                    $itemref = $opf->spine->addChild('itemref');
                    $itemref->addAttribute('idref', 'epub_end');
                    $itemref->addAttribute('linear', 'yes');
                }

                $spine = $opf->spine;
                foreach($spine->itemref as  $itemref){
                    $id = (string) $itemref['idref'];
                    $itemref['idref'] = slugify($id);
                }

                if(!empty($opf->guide)){
                    $guide = $opf->guide;
                    
                    foreach($guide->reference as  $reference){
                        $href = (string)$reference['href'];
                        $href = sluggedFilename($href);
                        $reference['href'] = $href;
                    }
                }

                $has_modified = null;
                foreach($metadata->meta as $meta){
                    if((isset($meta['name']) && $meta['name'] == 'cover') && isset($meta['content'])){
                        $cover_id = slugify($meta['content']);
                        $meta['content'] = $cover_id;
                    }
                    if(isset($meta['property']) && $meta['property'] == 'dcterms:modified'){
                        $meta = date('Y-m-d\TH:i:s\Z');
                        $has_modified = TRUE;
                    }
                }

                if($opf['version'] >= '3.0' && !$has_modified){
                    $new_meta = $metadata->addChild('meta',date('Y-m-d\TH:i:s\Z'));
                    $new_meta->addAttribute('property',"dcterms:modified");
                }

                $opf->asXML($extract_dir . $opfPath);
                $opf_content = file_get_contents($extract_dir . $opfPath);
                $opf_content = tidy_repair_string($opf_content,$tidy_config);
                file_put_contents($extract_dir . $opfPath,$opf_content);

                $exporter = new PhpExportToEpub($this->zip,$extract_dir,array(
                    'mimetype',
                    'META-INF/container.xml',
                    'META-INF/calibre_bookmarks.txt',
                    'content.opf',
                    'toc.ncx',
                ));
                
                $file_path  = $this->config['root_dir'] . 'tmp/export/' . basename($this->config['root_dir'] . $this->book);
                $exporter->export( $file_path );
         
                clearstatcache();
                sleep(5);
                if (file_exists($file_path))
                {   
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/epub+zip');
                    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
                    header('Content-Transfer-Encoding: binary');
                    header('Content-Length: ' . filesize($file_path));
                    readfile($file_path);
                    exit;
                }
                else 
                {
                    // file couldn't be opened
                    header("HTTP/1.0 500 Internal Server Error");
                    exit;
                }

            } else {
                throw new \Exception('Failed to read epub file');
            }
		} else {
			header("HTTP/1.0 400 Bad Request");
			exit;
		}
	}
    
    public function correctLinks($chapter) {

        preg_match_all('#\s+src\s*=\s*"(.+?)"#im', $chapter, $links, PREG_SET_ORDER);
        $itemCount = count($links);
        for ($idx = 0; $idx < $itemCount; $idx++) {
            $link = $links[$idx];
            $src = $link[1];
            $src = sluggedFilename($src);
            $chapter = str_replace($link[0], " src=\"" . $src . "\"", $chapter);
        }
        
        preg_match_all('#\s+xlink:href\s*=\s*"(.+?)"#im', $chapter, $links, PREG_SET_ORDER);
        $itemCount = count($links);
        for ($idx = 0; $idx < $itemCount; $idx++) {
            $link = $links[$idx];
            $xlink = $link[1];
            $xlink = sluggedFilename($xlink);
            $chapter = str_replace($link[0], "  xlink:href=\"" . $xlink . "\"", $chapter);
        }
        preg_match_all('#\s+href\s*=\s*"(.+?)"#im', $chapter, $links, PREG_SET_ORDER);

        $itemCount = count($links);
        for ($idx = 0; $idx < $itemCount; $idx++) {
            $link = $links[$idx];
            $href = $link[1];
            $href = sluggedFilename($href);
            $chapter = str_replace($link[0], "  href=\"" . $href . "\"", $chapter);

        }

        return $chapter;
    }
    public function correctCSSLinks($cssData) {

        preg_match_all('#url\s*\([\'\"\s]*(.+?)[\'\"\s]*\)#im', $cssData, $links, PREG_SET_ORDER);

        $itemCount = count($links);
        for ($idx = 0; $idx < $itemCount; $idx++) {
            $link = $links[$idx];
			$src = $link[1];
            $src = sluggedFilename($src);
            $cssData = str_replace($link[0], " src=\"" . $src . "\"", $cssData);
        }

        return $cssData;
    }
    
	public function rebuildNavMap($navMap){
        foreach ($navMap->navPoint as $item) {
            $src =  (string)$item->content["src"];
            $item->content["src"] = sluggedFilename($src);
            if ((bool)$item->navPoint == TRUE) {
                $this->rebuildNavMap($item);
            }
        }
    }
    public function outputEpub(){
        $err = "<p>File '$this->file' is not an ePub file</p>\n";
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
                    if (isset($this->css[$this->ext])) {
                        $this->showCss();
                    } else {
                        $this->outputFile();
                    }
                } else {
                    echo $err;
                }
            }
        } else {
            if($this->valid_epub){
                if($this->config['show_cover']){
                    $this->outputBookCover();
                } elseif($this->config['dl']){
                    $this->download();
                } else {
                    $page = $this->getPage($this->show_page-1);
                    $this->showPage($page);
                }
            } else {
                echo $err;
            }
        }
    }
    public function parsePage($xhtml,$chapterDir,$chapter_order = 0){
        $headStart = strpos($xhtml, "<head");
        $headStart = strpos($xhtml, ">", $headStart) +1;
        $headEnd = strpos($xhtml, "</head", $headStart);
        
        $head =  substr($xhtml, $headStart, ($headEnd-$headStart));
        $head = trim(preg_replace('/\s+/', ' ', $head));
       
        if (!preg_match('/\<meta.*?charset=.*?([^"\']+)/siU', $head)) {
            $head = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'."\n".$head;
        }
        preg_match("/\<title\>(.*)\<\/title\>/siU",$head,$title);
        $page_title = isset($title[1]) ? $title[1] : NULL;
        if($this->config['auto_title']){
            if($chapter_order == 0){
                $head = str_replace ( $title[1] , $this->getBookTitle() . ":: Cover", $head );
            } else {
                $head = str_replace ( $title[1] , $chapter_order . "::" . $this->getBookTitle() , $head );
            }
        }
        if(empty($page_title)){
            $page_title = 'Chapter ' . $chapter_order . ":|:" . $this->getBookTitle();
            $head=preg_replace(array('/<title>(.*)<\/title>/i'),array('<title>'.$page_title.'</title>'),$head);
        }

        $head = preg_replace('/<link\s[^>]*type\s*=\s*"application\/vnd.adobe-page-template\+xml\"[^>]*\/>/i','',$head);
        $start = strpos($xhtml, "<body");
        $start = strpos($xhtml, ">", $start) +1;
        $end = strpos($xhtml, "</body", $start);
        $content =  substr($xhtml, $start, ($end-$start));
        return array(
            'chapter_dir' => $chapterDir,
            'title' => $page_title,
            'head' => $head,
            'content' => $content,
            'book' => (string) $this->getBookTitle(),
            'percentage' => null
        );
    }

    public function getPage($chapter_order){
        if(isset($this->chapters[$chapter_order])){
            $this->bookInfo();
            if($chapter_order != 0){
                $this->book_info['show'] = $chapter_order;
				savePhpHidden($this->cache_file,$this->book_info,true); 
            }
            

            if($chapter_order == 0 && !isset($this->config['show_page']) && $this->book_info['show'] != 0){
                if (file_exists($this->cache_file)) {
                    $location = $this->nav_address . "&show=" . $this->book_info['show'];
                    header('refresh:2;url=' . $location);
                }
            }

            $chapter_data = $this->chapters[$chapter_order];
            $chapterDir = $chapter_data["dir"];
            $itemref = $chapter_data["itemref"];
            $chapter = $this->zip->getFromName($chapter_data["content"]);
            $page = $this->parsePage($chapter,$chapterDir,$chapter_order);
            return array_merge($page,array(
                'file' => $this->bookRoot . $this->filesIds[$itemref],
                'percentage' => number_format((($chapter_order+1)/count($this->chapters)) * 100, 2, '.', '')
            ));

        }
        return array(
            'title' => '404',
            'file' => '',
            'head' => '<title>404</title><meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8"/>',
            'content' => '<div style="text-align: center; page-break-after: always;"><p>404 Page Not Found</p></div>',
            'book' => $this->getBookTitle(),
            'percentage' => 0
        );
    }
    public function showPage($page){
        $vars  = array(
            'base_url' => $this->config['base_url'],
            'nav_address' => $this->nav_address,
            'show_page' => $this->show_page,
            'chapter_count' => sizeof($this->chapters),
        );
        $nav =  template( 'nav', $vars );
        if ($this->show_page < 1 || $this->show_page > sizeof($this->chapters)) {
            $this->show_page = 1;
        }
        $head = $this->updateCSSLinks($page['head'], $page['chapter_dir']);
        $head = $this->updateLinks($head, $page['chapter_dir'], $this->chaptersId);
        $content = $this->updateLinks($page['content'], $page['chapter_dir'], $this->chaptersId);

        $tts_form = template( 'tts_form', array() );
        if($this->config['ajax']){
            header('Content-Type: application/json;charset=utf-8');
            $links = array();
            $styles = array();
            preg_match_all('/\<link([^>]+)>/', $head, $css_links, PREG_SET_ORDER);
            foreach($css_links as $link){
                $links[] = $link['0'];
            }
            preg_match_all('/\<style[^>]*\>(.+?)\<\/style\>/', $head, $css_styles, PREG_SET_ORDER);
            foreach($css_styles as $style){
                $styles[] = $style['0'];
            }
            echo json_encode(array(
                'nav' => $nav,
                'head' => $head,
                'title' => $page['title'],
                'css' => $links,
                'styles' => $styles,
                'content' => $content,
                'tts_form' => $tts_form,
                'book' => $page['book'],
                'percentage' => $page['percentage']
            ));
        } else { 
            $vars  = array(
                'nav' => $nav,
                'head' => $head,
                'title' => $page['title'],
                'base_url' => $this->config['base_url'],
                'content' => $content,
                'toc' => $this->toc,
                'tts_form' => $tts_form,
                'book' => $page['book'],
                'percentage' => $page['percentage'],
                
            );
            echo template( 'page', $vars );
        }
    }

    public function showCss(){
        if(isset($this->css[$this->ext])){
            $cssData = $this->updateCSSLinks($this->zip->getFromName($this->bookRoot . $this->ext), $this->css[$this->ext]);
            header("Pragma: public"); // required
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private",false); // required for certain browsers
            header("Content-Type: text/css");
            header("Content-Length: " . strlen($cssData));
            ob_clean();
            flush();
            echo $cssData;
        }
    }
    public function outputFile(){
        if ($this->zip->open($this->book) === TRUE) {
            $this->zip_open = true;
            $id = $this->fileLocations[$this->ext];
            if ($this->fileTypes[$id] == "application/xhtml+xml") {
                $content = $this->zip->getFromName($this->bookRoot . $this->ext);
                $chapterDir = dirname($this->ext);
                $page = $this->parsePage($content,$chapterDir);
                $this->showPage($page);
            } else {
                $stat = $this->zip->statName($this->bookRoot . $this->ext);
                $content = $this->zip->getFromName($this->bookRoot . $this->ext);
                $refType = $this->fileTypes[$id];
                header("Pragma: public"); // required
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: private",false); // required for certain browsers
                header("Content-Type: $refType");
                header("Content-Length: " . $stat['size']);
                ob_clean();
                flush();
                echo $content;
            }
        }
    }

    public function askRedirect(){ 
        $vars = array(
            'ext' => $this->ext,
            'url' => $this->config['read_url']
        );
        echo template( 'redirect', $vars );
        exit;
    }

    public function parseEpub(){

        if(!@$this->zip->open($this->book)){
            throw new \Exception('Failed to read epub file');
        }
        $this->zip_open = true;
        $mimetype = $this->zip->getFromName('mimetype');
        if($mimetype == false ){
            throw new \Exception('Failed to access epub mimetype file');
        }
        $toc = array();
        $chapterDir = NULL;

        if ($mimetype == 'application/epub+zip') {
			$tidy_config = array(
				'output-xml' => true,
				'input-xml' => true
			);
            /*  
            //iterate the archive files array and display the filename or each one
            for ($i = 0; $i < $this->zip->numFiles; $i++) {
                $this->files[$this->zip->getNameIndex($i)] = $this->zip->getNameIndex($i);
            }
            */
            
            $container = $this->zip->getFromName("META-INF/container.xml");
		
            $xContainer = new \SimpleXMLElement(tidy_repair_string($container,$tidy_config));
            $opfPath = $xContainer->rootfiles->rootfile['full-path'];
            $opfType = $xContainer->rootfiles->rootfile['media-type'];
            $this->bookRoot = dirname($opfPath) . "/";
            if ($this->bookRoot == "./") {
                $this->bookRoot = "";
            }
            if ($this->zip->locateName($opfPath) === false)
            {
                throw new Exception('Book' . $this->book . ' does not have a proper OPF file');
            }
		
            $opf = new \SimpleXMLElement(tidy_repair_string($this->zip->getFromName($opfPath),$tidy_config));
            $this->book_title = $opf->metadata->children('dc', true)->title;
            $this->book_author = $opf->metadata->children('dc', true)->creator;
            $this->book_description = $opf->metadata->children('dc', true)->description;
            $this->epub_version = (string)$opf->attributes()->version;
            
            $manifest = $opf->manifest;
            foreach ($manifest->item as $item) {
                $href = (string)$item['href'];
				if($this->zip->locateName($this->bookRoot . $href) !== false){
					$id = (string)$item["id"];
					$media = (string)$item['media-type'];
					$properties = (string)$item->attributes()->properties;
					$chapterDir = dirname($href);

					$this->filesIds[$id] = $href;
					$this->fileLocations[$href] = $id;
					$this->fileTypes[$id] = $media;
					if ($media == "text/css") {
						$this->css[$href] = $chapterDir;
					}
                    if(strpos($media, 'image/') === 0){
                        $this->images[$id] = $href;
                        if($properties == 'cover-image'){
                            $this->cover = rawurlencode($href);
                        }
                    }
                    if($media == "application/xhtml+xml"){
						if($properties == 'nav'){
							$toc = array(
								'dir' => $chapterDir,
								'id' => $id,
								'ncx' => false,
							);
						}
                    }

                    if(!$toc && $media == 'application/x-dtbncx+xml'){
                        $toc = array(
                            'dir' => $chapterDir,
                            'id' => $id,
                            'ncx' => true,
                        );
                    }
				}
            }
            
            $chapterNum = 1;
            foreach($opf->spine->itemref as $order => $itemref){
                $id = (string) $itemref->attributes()->idref;
                if (isset($this->fileTypes[$id]) && $this->fileTypes[$id] == "application/xhtml+xml") {
                    $this->chaptersId[$this->filesIds[$id]] = $chapterNum++;
                    $chapterDir = dirname($this->filesIds[$id]);
                    $this->chapters[] = array(
                        'dir' => $chapterDir,
                        'content' => $this->bookRoot . $this->filesIds[$id],
                        'itemref' => $id
                    );
                }
            }

            if($toc){
                $id = $toc['id'];
                if($toc['ncx']){ 
                    $xNcx = new \SimpleXMLElement(tidy_repair_string($this->zip->getFromName($this->bookRoot . $this->filesIds[$id]),$tidy_config));
                    $this->toc = $this->updateLinks($this->makeNCXNav($xNcx->navMap), $chapterDir, $this->chaptersId);
                } else {
                    $xhtml = $this->updateLinks($this->zip->getFromName($this->bookRoot . $this->filesIds[$id]), dirname($this->filesIds[$id]), $this->chaptersId);
                    $start = strpos($xhtml, "<body");
                    $start = strpos($xhtml, ">", $start) +1;
                    $end = strpos($xhtml, "</body", $start);
                    $content =  substr($xhtml, $start, ($end-$start));
                    $this->toc = $content;
                }
            } else {
                $this->toc = $this->updateLinks($this->makeSpineNav($opf->spine), $chapterDir, $this->chaptersId);
            }

            if(!$this->cover){
                foreach($opf->metadata->meta as $key => $meta){
					$attributes = $meta->attributes();
                    if((isset($attributes['name']) && strval($attributes['name']) == 'cover') && isset($attributes['content'])){
                        $cover_id = strval($attributes['content']);
						if(isset($this->filesIds[$cover_id])){
							$this->cover = rawurlencode($this->filesIds[$cover_id]);
						}

                    }
                }
            }
            
            $this->bookInfo();
            return TRUE;
        }
        return FALSE;
    }

    public function bookInfo(){
        $update_info = false;
        if(!$this->book_info){
            
            $this->book_info = (array) getPhpHidden($this->cache_file,true);
            if(!isset($this->book_info['cover'])){
                $this->book_info['cover'] = $this->getBookCover();
                $update_info = true;
            }
            if(!isset($this->book_info['title'])){
                $this->book_info['title'] = $this->getBookTitle();
                $update_info = true;
            }
            if(!isset($this->book_info['author'])){
                $this->book_info['author'] = $this->getBookAuthor();
                $update_info = true;
            }
            if(!isset($this->book_info['description'])){
                $this->book_info['description'] = strip_tags($this->getBookDescription());
                $update_info = true;
            }
            if(!isset($this->book_info['show'])){
                $this->book_info['show'] = 0;
                $update_info = true;
            }
        }
        if ($update_info) {
			savePhpHidden($this->cache_file,$this->book_info,true);              
        }
        return $this->book_info;
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

    public function getBookCover(){
        return $this->cover;
    }
    
    public function outputBookCover(){
        if ($this->zip->open($this->book) === TRUE) {
            if($this->cover){
                $ext = rawurldecode($this->cover);
                $this->zip_open = true;
                $id = $this->fileLocations[$this->ext];
                $stat = $this->zip->statName($this->bookRoot . $ext);
                $content = $this->zip->getFromName($this->bookRoot . $ext);
                $refType = $this->fileTypes[$id];
                header("Pragma: public"); // required
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: private",false); // required for certain browsers
                header("Content-Type: $refType");
                header("Content-Length: " . $stat['size']);
                ob_clean();
                flush();
                echo $content;
            } else {
                $top_text = (string) $this->getBookTitle();
                if($top_text){
                    $filename = $this->config['root_dir'] . 'tmp/cache/' . md5($top_text) . '.png';
                    if(!file_exists($filename)){
                        $bottom_text = (string) $this->getBookAuthor();
                        $tti = new CoverMaker(1200,1600);
                        $tti->margin('250 96 100 96');
                        $tti->makePattern();
                        $tti->drawBorder(20, '#000000', 0, 0);
                        $tti->drawBorder(5, '#000000', 30, 30);
                        $tti->drawBorder(2, '#000000', 40, 40);
                        $tti->drawBorder(5, '#000000', 48, 48);
                        $tti->stroke(5);
                        $tti->addText($top_text,array('font-size' => 80, 'font-weight' => "bold",'line-height' => 1.5),17,'#ffffff',CoverMaker::TOP, true);
                        if($bottom_text){
                            $tti->addText($bottom_text,array('font-size' => 65, 'font-style' => 'italic', 'line-height' => 1.5),30,'#ffffff',CoverMaker::BOTTOM);
                        }
                        $tti->output($filename,'png');
                    } else {
                        header('Content-Type: image/png');
                        echo file_get_contents($filename);
                    }
                }
            }
        }
    }

    public function makeSpineNav($spine, $level = 0) {
        $indent = str_repeat("    ", $level);
        $nav = $indent . "<ol>\n";
        foreach ($spine->itemref as $itemref) {
            $id = $itemref->idref;
            $src = $this->filesIds[$id];
            $label = $id;
            $nav .= $indent . "  <li><a href=\"" . $src . "\">$label</a>";
            $nav .= "</li>\n";
        }
        $nav .= $indent . "</ol>\n";
        return $nav;
    }

    public function makeNCXNav($navMap, $level = 0) {
        $indent = str_repeat("    ", $level);
        $nav = $indent . "<ul>\n";
        foreach ($navMap->navPoint as $item) {
            $id = (string)$item["id"];
            $label = (string)$item->navLabel->text;
            $src =  (string)$item->content["src"];
            $nav .= $indent . "  <li id=\"".$id."\"><a href=\"" . $src . "\">$label</a>";
            if ((bool)$item->navPoint == TRUE) {
                $nav .= $this->makeNCXNav($item, $level+1);
            }
            $nav .= "</li>\n";
        }
        $nav .= $indent . "</ul>\n";
        return $nav;
    }

    public function updateLinks($chapter, $chapterDir, $chaptersId) {

        preg_match_all('#\s+src\s*=\s*"(.+?)"#im', $chapter, $links, PREG_SET_ORDER);
        $itemCount = count($links);
        for ($idx = 0; $idx < $itemCount; $idx++) {
            $link = $links[$idx];
            if (preg_match('#https*://.+?\..+#i', $link[1])) {
                $chapter = str_replace($link[0], " src=\".\"", $chapter);
            } else {
                $refFile = RelativePath::pathJoin($chapterDir, $link[1]);
                $chapter = str_replace($link[0], " src=\"" . $this->nav_address . "&ext=" . rawurlencode($refFile) . "\"", $chapter);
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
                $chapter = str_replace($link[0], " xlink:href=\"" . $this->nav_address . "&ext=" . rawurlencode($refFile) . "\"", $chapter);
            }
        }
        preg_match_all('#\s+href\s*=\s*"(.+?)"#im', $chapter, $links, PREG_SET_ORDER);

        $itemCount = count($links);
        for ($idx = 0; $idx < $itemCount; $idx++) {
            $link = $links[$idx];
            $link_id = $link[1];
            if(isset($chaptersId[$link_id])){
                $chapter = str_replace($link[0], " href=\"" . $this->nav_address  . "&show=" . $chaptersId[$link_id] . "\"", $chapter);
            } else {
                if (preg_match('#https*://.+?\..+#i', $link[1])) {
                    $chapter = str_replace($link[0], " href=\"". $this->config['read_url'] . "?ext=" . rawurlencode($link[1]) . "\"", $chapter);
                } else {
                    if(startsWith($link[1],$chapterDir . "/") || startsWith($link[1],$chapterDir . "\\")){
                        $refFile = $link[1];
                    } else {
                        $refFile = RelativePath::pathJoin($chapterDir, $link[1]);
                    }

                    $id = "";
                    if (strpos($refFile, "#") > 0) {
                        $array = explode ("#", $refFile);
                        $refFile = $array[0];
                        $id = "#" . $array[1];
                    }
                    
                    if (isset($chaptersId[$refFile])) {
                        $chapter = str_replace($link[0], " href=\"" . $this->nav_address  . "&show=" . $chaptersId[$refFile] . $id . "\"", $chapter);
                    }  else {
                        $chapter = str_replace($link[0], " href=\"" . $this->nav_address  . "&ext=" . rawurlencode($refFile) . $id . "\"", $chapter);
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
                $cssData = str_replace($link[0], " src=\"" . $this->nav_address . "&ext=" . rawurlencode($refFile) . "\"", $cssData);
            }
        }

        return $cssData;
    }

    public function __destruct() {
        if($this->zip_open){
            $this->zip->close();
        }
    }
}