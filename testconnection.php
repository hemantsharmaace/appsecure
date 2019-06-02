<?php
include('includer.php');

//handle test connection via ajax from the installation area
if($_SERVER['REQUEST_METHOD'] == 'POST')
{	   
		$hostname = (isset($_POST['dbhost']))?trim($_POST['dbhost']):"";
		$username = (isset($_POST['dbuser']))?trim($_POST['dbuser']):"";
		$password = (isset($_POST['dbpw']))?trim($_POST['dbpw']):"";
		$database = (isset($_POST['dbname']))?trim($_POST['dbname']):"";
		
		if(!$hostname){
			echo getMessage('db_host_blank');		
			exit();
		}
		if(!$database){
			echo getMessage('db_dbname_blank');
			exit();			
		}
		if(!$username){
			echo getMessage('db_username_blank');	
			exit();			
		}
		if(!$password){
			echo getMessage('db_password_blank');	
			exit();			
		}
		
		$hostname = (isset($_POST['dbhost']))?trim($_POST['dbhost']):"";
		$username = (isset($_POST['dbuser']))?trim($_POST['dbuser']):"";
		$password = (isset($_POST['dbpw']))?trim($_POST['dbpw']):"";
		$database = (isset($_POST['dbname']))?trim($_POST['dbname']):"";
		
			
		$db = MySqlDatabase::getInstance();
     
        // connect to a MySQL database (use your own login information)
       try {
           $db->connect($hostname, $username, $password);	
			echo getMessage('db_server_connect_success').$hostname.".";
       } 
       catch (Exception $e) {
          die($e->getMessage());
       }
		  
		
	   if ($database) {
			try {
				$db->useDatabase($database);
				echo getMessage('db_connect_success'). $database ;		
			  } 
			   catch (Exception $e) {
				  die($e->getMessage());
			 }
		}
		 
}
exit();
?>