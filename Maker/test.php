<?php 
class PhpExportToEpub {
	
	private $dir;
	private $zip;
	private $zipped = array();
	private $files;

	public function __construct($dir,$files) {
		$this->dir = str_replace('\\', '/', $dir);
		$this->zip = new ZipArchive();
        $this->files = $files;
	}
    public function zipDir($dir){
        $ffs = scandir($dir);
        foreach($ffs as $ff){
            if($ff != '.' && $ff != '..'){
                if(is_dir($dir. '/' .$ff)){
                    $this->zipDir($dir. '/' .$ff);
                } else if(is_file($dir. '/' .$ff)){
                    $this->zipFile($dir. '/' .$ff);
                }
            } 
            
        }
    }

	public function zipFile ($file) {
        $filename = str_replace( $this->dir . '/', '', $file);
        
        if(!in_array($filename,$this->zipped)){
            $this->zip->addFromString($filename, file_get_contents($file));
            $this->zipped[] = $filename;
        }
	}

	public function editFile($file, $newContents) {
        $fileToModify = str_replace( $this->dir . '/', '', $file);
        $fileToModify = str_replace( '\\', '/', $fileToModify);
        $this->zip->deleteName($fileToModify);
        $this->zip->addFromString($fileToModify, $newContents);
	}

    public function export($destination = '',$overwrite = false){
        if($this->zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
			return false;
		}
        foreach ($this->files as $file)
        {
            $file = str_replace('\\', '/', $this->dir . '/' . $file);
            if (is_dir($file) === true){
                $this->zipDir($file);
            } else if (is_file($file) === true)
            {
                $this->zipFile($file);
            }
        }
        $this->zipDir($this->dir);
    }

    public function listContent($file){
        if ($this->zip->open($file) === TRUE) {
            //iterate the archive files array and display the filename or each one
            for ($i = 0; $i < $this->zip->numFiles; $i++) {
                echo $i . ': ' . $this->zip->getNameIndex($i) . '<br />';
            }
        } else {
            echo 'Failed to open the archive!';
        }
    }

    public function __destruct(){
        $this->zip->close();
        $this->zipped = array();
    }
}
/*  
zip the files, saving it as epub
 */
 
$dir = realpath(realpath( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'Bringing The Farm To Live In Another World');
$exporter = new PhpExportToEpub($dir,array(
    'mimetype',
    'META-INF/container.xml',
    'META-INF/calibre_bookmarks.txt',
    'content.opf',
    'toc.ncx',
));
$exporter->export('Bringing The Farm To Live In Another World.epub');
$exporter->listContent('Bringing The Farm To Live In Another World.epub');