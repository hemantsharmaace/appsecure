<?php
/**
 * Installation File of the application
 * @author Quatrro
 * @version 1.0
 */  
 include(__DIR__."/includer.php");
 class Installer { 
    
	 public $applicationUsername ;
	 public $applicationPassword;
	 public $hostname;
	 public $username;
	 public $password;
	 public $database;
	 public $databaseAccess;
	 public $install_success = "1"; 
    
     public function __construct(){
		 
		//check all the post values
	 	$this->applicationUsername = (isset($_POST['app_username']))?trim($_POST['app_username']):"";
		$this->applicationPassword = (isset($_POST['app_password']))?trim($_POST['app_password']):"";
		$this->hostname = (isset($_POST['dbhost']))?trim($_POST['dbhost']):"";
		$this->username = (isset($_POST['dbuser']))?$this->clean(trim($_POST['dbuser'])):"";
		$this->password = (isset($_POST['dbpw']))?trim($_POST['dbpw']):"";
		$this->database = (isset($_POST['dbname']))?$this->clean(trim($_POST['dbname'])):"";
		$this->databaseAccess = (isset($_POST['db_root_access']))?$this->clean(trim($_POST['db_root_access'])):"";
        
 		try {
				
		  if(file_exists(__DIR__."/config.xml")){	
		      $installConfig = readInstallConfig(); 
			  $installed = $installConfig['installed'];
			  if($installed){	  				 
				throw new Exception(getMessage('application_already_installed'));   
 			  }  
		  }
	    
		  //check if
		  if($this->applicationUsername =='') {			
			throw new Exception(getMessage('application_username_blank'));
		  }
		  if($this->applicationPassword =='') {			
			throw new Exception(getMessage('application_password_blank'));
		  }
		  if($this->hostname =='') {			
			throw new Exception(getMessage('database_host_blank'));
		  }
		  if($this->username =='') {			
			throw new Exception(getMessage('database_username_blank'));
		  }
		  if($this->password =='') {			
			throw new Exception(getMessage('database_password_blank'));
		  }
		  if($this->database =='') {			
			throw new Exception(getMessage('database_name_blank'));
		  }
		  // check if password is less than 5 character
		  if(strlen($this->applicationPassword) < 5) {
			throw new Exception(getMessage('short_password_error'));
		  }  
		  
		  //check db permission and create db if not and process further sql script	 
		  $db = MySqlDatabase::getInstance();
          $db->connect($this->hostname, $this->username, $this->password);
		  
		  //check if database root access is available if yes then create database if database doesnt not exist.
		  if($this->databaseAccess && $this->database){   
			   //check db exists if not then create the database 
			   $checkDbExistsQuery = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".$this->database."'";
			   $checkDbExists = $db->query($checkDbExistsQuery);			    
			   if($checkDbExists['SCHEMA_NAME'] == NULL){
				   $createDbSql = "CREATE DATABASE ".$this->database; 
				   $result = $db->query($createDbSql);	
			   }  
		  }  	
		  $db->useDatabase($this->database); 
		} catch(Exception $e) { 	
			//failed as we cant log the issue either
		  	$response = json_encode(array('success' => false,'message' => $e->getMessage()));  			
			echo $response; 
			exit();
			 
		}    
	}
     
    public function process_install() { 
	 
	    $response = array(); 
		$db = MySqlDatabase::getInstance();
	 
 		try {  
    	  // check all the table already exist in the database provided if not run table create script and  if exist throw exception 
		  $val = $db->quickQuery('select 1 from '.TABLE_CONFIG.'  LIMIT 1');
		  if($val !== FALSE){
			  throw new Exception(TABLE_CONFIG.":".getMessage('table_already_exist'));		 
		   }
		  $val = $db->quickQuery('select 1 from '.TABLE_BLOCKED_REQUESTS.' LIMIT 1');
		  if($val !== FALSE){
			  throw new Exception(TABLE_BLOCKED_REQUESTS.":".getMessage('table_already_exist'));		 
		   }
		  $val = $db->quickQuery('select 1 from '.TABLE_EXCEPTION.' LIMIT 1');
		  if($val !== FALSE){
			  throw new Exception(TABLE_EXCEPTION.":".getMessage('table_already_exist'));	
		  }
		  $val = $db->quickQuery('select 1 from '.TABLE_REQUEST.' LIMIT 1');
		  if($val !== FALSE){
			  throw new Exception(TABLE_REQUEST.":".getMessage('table_already_exist'));	
		  }
		  $val = $db->quickQuery('select 1 from '.TABLE_ACTION_LOG.' LIMIT 1');
		  if($val !== FALSE){
			  throw new Exception(TABLE_ACTION_LOG.":".getMessage('table_already_exist'));		 
		  }
		  
		  
$sql = "/*Table structure for table ".TABLE_ACTION_LOG." */
DROP TABLE IF EXISTS ".TABLE_ACTION_LOG.";

CREATE TABLE ".TABLE_ACTION_LOG." (
  `id` int(25) NOT NULL AUTO_INCREMENT,
  `action` text,
  `action_date` datetime DEFAULT NULL,
  `action_user` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*Data for the table ".TABLE_ACTION_LOG." */
/*Table structure for table ".TABLE_BLOCKED_REQUESTS." */
DROP TABLE IF EXISTS ".TABLE_BLOCKED_REQUESTS.";
CREATE TABLE ".TABLE_BLOCKED_REQUESTS." (
  `blocked_request_id` int(25) NOT NULL AUTO_INCREMENT,
  `request_id` bigint(40) DEFAULT NULL,
  `blocked_date` datetime DEFAULT NULL,
  `blocked_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`blocked_request_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*Data for the table ".TABLE_BLOCKED_REQUESTS." */
/*Table structure for table ".TABLE_CONFIG." */
DROP TABLE IF EXISTS ".TABLE_CONFIG.";
CREATE TABLE ".TABLE_CONFIG." (
  `id` int(25) NOT NULL AUTO_INCREMENT,
  `app_username` varchar(255) DEFAULT NULL,
  `app_password` varchar(255) DEFAULT NULL,
  `database_host` varchar(255) DEFAULT NULL,
  `database_name` varchar(255) DEFAULT NULL,
  `database_user` varchar(255) DEFAULT NULL,
  `database_password` varchar(255) DEFAULT NULL,
  `installation_date` datetime DEFAULT NULL,
  `license_key` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table ".TABLE_CONFIG." */

/*Table structure for table ".TABLE_EXCEPTION." */

DROP TABLE IF EXISTS ".TABLE_EXCEPTION.";

CREATE TABLE ".TABLE_EXCEPTION." (
  `id` int(25) NOT NULL AUTO_INCREMENT,
  `request_exception` varchar(255) DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `added_by` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table ".TABLE_EXCEPTION." */

/*Table structure for table ".TABLE_REQUEST." */

DROP TABLE IF EXISTS ".TABLE_REQUEST.";

CREATE TABLE ".TABLE_REQUEST." (
  `id` int(25) NOT NULL AUTO_INCREMENT,
  `incoming_request` text,
  `request_recieved_date` datetime DEFAULT NULL,
  `request_header` text,
  `request_ip_address` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `request_processed_date` datetime DEFAULT NULL,
  `request_params` text,
  `request_global_params` text,
  `query_string` text,
  `user_agent` text,
  `request_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table ".TABLE_REQUEST." */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;";

	 //echo $sql;exit;
		 
 		  //now run the script in the db name provided
		  /* $sqlFileToExecute = 'tables.sql';
		 
		  if (!file_exists(__DIR__."/tables.sql")) {   
			 throw new Exception(getMessage('sql_file_error'));       
		  }*/
		  // read the sql file
		//  $f = fopen($sqlFileToExecute,"r+");
		//	$sqlFile = fread($f, filesize($sqlFileToExecute));
			$sqlArray = explode(';',$sql);
			
			foreach ($sqlArray as $stmt) {
			  if (strlen($stmt)>3 && substr(ltrim($stmt),0,2)!='/*') {				
				 $result = $db->query($stmt);
			  }
			} 
		
			$query = null;
			$query = 'truncate table '.TABLE_CONFIG;
			$result = $db->query($query);
			//insert into the config table the data provided
			if($result) {
				$query = "insert into ".TABLE_CONFIG." (`app_username`,`app_password`,`database_host`,`database_name`,`database_user`,`database_password`,`installation_date`) VALUES('".$db->realEscapeString($this->applicationUsername)."','".md5($this->applicationPassword)."','".$this->hostname."','".$db->realEscapeString($this->database)."','".$db->realEscapeString($this->username)."','".$this->password."',NOW())";
					
				$result = $db->query($query); 
				if($result){
				   //create an action log
				   $this->createActionLog(getMessage('application_installed_successfully'),date("Y-m-d H:i:s"),'SYSTEM');		
				   $this->updateInstallationConfig($this->install_success);	
				   $response = json_encode(array('success' => true,'message' => getMessage('application_installed_successfully'))); 
				   return $response;
				}				  
			}
			 
		}
		catch(Exception $e) { 
		    $this->createActionLog($e->getMessage(),date("Y-m-d H:i:s"),'SYSTEM');		     
		 	$response = json_encode(array('success' => false,'message' => $e->getMessage()));  
			return $response; 
		}  
    } 
	
	public function clean($string) {
		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	}
	 
	public function createActionLog($action,$date,$user='SYSTEM'){ 
		$query = "insert into ".TABLE_ACTION_LOG."  (`action`,`action_date`,`action_user`) VALUES ('".$action."','".$date."','".$user."')";  
		$db = MySqlDatabase::getInstance();		
		$result = $db->query($query); 
		return true;
	}    
	
	public function updateInstallationConfig($value){
	    $configInstallXML =simplexml_load_file(__DIR__."/config.xml");
		//$installValue = $configInstallXML->installed;
		$configInstallXML->installed = $value;
		$configInstallXML->dbhost = $this->hostname;
		$configInstallXML->dbpassword = $this->password;
		$configInstallXML->dbusername = $this->username;
		$configInstallXML->dbname = $this->database;
		$configInstallXML->asXML(__DIR__."/config.xml");   
		return true;
	} 
	
	private function redirect2success($post)
	{
	 header("Location:installed.php");
	 exit();
	}
 
} 
//end class 

// create install object
$install = new Installer; 
 
//call installation function
$res = $install->process_install();

echo $res;exit();
?>
