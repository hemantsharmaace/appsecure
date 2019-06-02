<?php 
/**
 * Function file to use throughout the application
 * @author Quatrro
 * @version 1.0
 */ 
 
/**
 * Convert a SimpleXML object to an associative array
 *
 * @param object $xmlObject
 *
 * @return array
 * @access public
 */
function simpleXmlToArray($xmlObject)
{
    $array = array();
    foreach ($xmlObject->children() as $node) {
        $array[$node->getName()] = is_array($node) ? simplexml_to_array($node) : (string) $node;
    }
    return $array;
}

/**
 * Checks if a folder exist and return canonicalized absolute pathname (sort version)
 * @param string $folder the path being checked.
 * @return mixed returns the canonicalized absolute pathname on success otherwise FALSE is returned
 */
function folder_exist($folder)
{ 
    // Get canonicalized absolute pathname
    $path = realpath(__DIR__."/".$folder); 
 
    // If it exist, check if it's a directory
    return ($path !== false AND is_dir($path)) ? $path : false;
}

/**
 * Simple Templating function
 *
 * @param $file   - Path to the PHP file that acts as a template.
 * @param $args   - Associative array of variables to pass to the template file.
 * @return string - Output of the template file. Likely HTML.
 */
function template( $file, $args ){
  // ensure the file exists
  if ( !file_exists( $file ) ) {
    return '';
  }

  // Make values in the associative array easier to access by extracting them
  if ( is_array( $args ) ){
    extract( $args );
  }

  // buffer the output (including the file is "output")
  ob_start();
    include $file;
  return ob_get_clean();
}

function readInstallConfig(){
	
	$install_config =array();
	
	if(!file_exists(__DIR__."/config.xml")){
		echo getMessage('config_file_error');		
		exit();
	}   
		
	// check already installed or not;
	$install_config= simpleXmlToArray(simplexml_load_file(__DIR__."/config.xml"));		 
	return $install_config; 	 
}

function getMessage($key){
	
	if(!file_exists(__DIR__."/lang.php")){
		echo "Unable to locate language File.";
		exit();
	} 
	include(__DIR__."/lang.php");	 
	return $string[$key];  
}

 

?>