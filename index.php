<?php 
/**
 * Main file to handle install of Qprevent
 * @author Quatrro
 * @version 1.0
 */  
 
include(__DIR__."/includer.php");
 
//check if application admin folder exists  throw error if not
$folderName = "";
$folderName = strtolower(trim($config['application_admin_folder_name']));
if(isset($folderName) && !folder_exist($folderName)){
	echo getMessage('admin_folder_error');
	exit();
} 
 
//check if plugin is installed
$installed = null; 
$installConfig = readInstallConfig(); 
//echo "<pre>";print_r($installConfig );echo "</pre>";exit(); 
$installed = $installConfig['installed'];

if(!$installed){	
	//run the installation script
	//show db config box
	$file = __DIR__ . '/templates/configuration.html';
    $output = '';
	$output.= template($file);	
	print $output;
	exit();
}  

// we have reached here means everything went well now we can handle requests directly
$incomingRequests = new Requests; 
 
//call  request function
$incomingRequests->handleRequests();  


?>