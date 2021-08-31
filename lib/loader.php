<?php 
class Loader {
	 /**
     * An associative array where the key is a namespace prefix and the value
     * is an array of base directories for classes in that namespace.
     *
     * @var array
     */
	public $prefixes = array();
	 /**
     * An array of directories for classes that does not use namespace
     *
     * @var array
     */
	public $paths = array();
	public function __construct($namespaces = array()) {
		spl_autoload_register( array( $this, 'loadClass' ) );
		$this->addNamespaces($namespaces );
	}
    /*  /////////// ---psr4 autoloader--- /////////  */
    public function addNamespaces($namespace){
        foreach($namespace as $namespace => $dir){
            $this->addNamespace($namespace,$dir);
        }
    }
    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string $prefix The namespace prefix.
     * @param string $base_dir A base directory for class files in the
     * namespace.
     * @param bool $prepend If true, prepend the base directory to the stack
     * instead of appending it; this causes it to be searched first rather
     * than last.
     * @return void
     */
    public function addNamespace( $prefix, $base_dir, $prepend = false )
    {
        // normalize namespace prefix
        $prefix = trim( $prefix, '\\' ) . '\\';// normalize the base directory with a trailing separator
        $base_dir = rtrim( $base_dir, DIRECTORY_SEPARATOR ) . '/';// initialize the namespace prefix array
        if( isset( $this->prefixes[ $prefix ] ) === false )
        {
            $this->prefixes[ $prefix ] = array();
        }// retain the base directory for the namespace prefix
        if( $prepend )
        {
            array_unshift( $this->prefixes[ $prefix ], $base_dir );
        }
        else
        {
            array_push( $this->prefixes[ $prefix ], $base_dir );
        }
    }

    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name.
     * @return mixed The mapped file name on success, or boolean false on
     * failure.
     */
    public function loadClass( $class )
    {
        // the current namespace prefix
        $prefix = $class;// work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while( false !== $pos = strrpos( $prefix, '\\' ) )
        {
            // retain the trailing namespace separator in the prefix
            $prefix = substr( $class, 0, $pos + 1 );// the rest is the relative class name
            $relative_class = substr( $class, $pos + 1 );// try to load a mapped file for the prefix and relative class
            $mapped_file = $this->loadMappedFile( $prefix, $relative_class );

            if( $mapped_file )
            {
                return $mapped_file;
            }

            $prefix = rtrim( $prefix, '\\' );
        }// never found a mapped file
        return $this->load( $class ); // lets use another loader
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix The namespace prefix.
     * @param string $relative_class The relative class name.
     * @return mixed Boolean false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedFile( $prefix, $relative_class )
    {
        /*  are there any base directories for this namespace prefix?  */
        if( isset( $this->prefixes[ $prefix ] ) === false )
        {
            return false;
        }// look through base directories for this namespace prefix
        foreach( $this->prefixes[ $prefix ] as $base_dir )
        {
            // replace the namespace prefix with the base directory,
            // replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';// if the mapped file exists, require it
            if( $this->requireFile( $file ) )
            {
                // yes, we're done
                return $file;
            }
        }// never found it
        return false;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file The file to require.
     * @return bool True if the file exists, false if not.
     */
    protected function requireFile( $file )
    {
        if( file_exists( $file ) )
        {
            require $file;
            return true;
        }

        return false;
    }
	
    /**
	 *
     * tries to find the file from the list of paths that contains the class definition
	 *
     * @param  string  $file name of the class without the prefix
     * @return string  or boolean false
     */
	public function findResource($file) {
		$found = FALSE;
		foreach ($this->paths as $dir) {
			if (is_file($dir . DIRECTORY_SEPARATOR . $file)) {
				$found = $dir . DIRECTORY_SEPARATOR . $file; // A path has been found
				break; // Stop searching
			}
		}
		return $found;
	}

    /**
     * method for loading classes
     * 
     * @acess  	public
     * @param   string	$class class you want to load
     */
    public function load($class) {
        // Transform the class name into a path
        $file = str_replace('_', DIRECTORY_SEPARATOR, $class);
        if ($path = $this->findResource($file . '.php')) {
            include_once($path);
			return TRUE;
        } else {
        	$file = str_replace('_', DIRECTORY_SEPARATOR, strtolower($class));
        	if ($path = $this->findResource($file . '.php')) {
	            include_once($path);
				return TRUE;
        	}	
        }
		return FALSE;
    }

    /**
	 *
     * Sets the location where the container should look for classes
	 *
     * @param  array  $paths
     * @return void
     */
	public function paths($paths){
		$this->paths = array_merge($this->paths,$paths);
	}
}