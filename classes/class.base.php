<?php
/**
 * Base class for the system
 * @author Quatrro
 * @version 1.0
 */  
  class Base {
	 
	public $db_host;
    public $db_name;
	public $db;
    public $db_username;
    public $db_password;
	public $installed = null;
    public function __construct() {
		
		include('../functions.php');
		 
		//read the installation config after the installation is completed as the values needs to be there in the config file if the value doesnt exist it will throw error. Database host,database name , database username , database password and installed parameter		
		$installationConfig = null;
		$installationConfig = readInstallConfig();
		$this->db_host = $installationConfig['dbhost'];
		$this->db_name = $installationConfig['dbname'];
		$this->db_username = $installationConfig['dbusername'];
		$this->db_password = $installationConfig['dbpassword'];		
		
       	try {
			
		  if(file_exists(__DIR__."/config.xml")){	
		    
			 $this->installed = $installationConfig['installed'];
			  if(!$this->installed){	  
				throw new Exception(getMessage('configuration_error'));
 			  }  
		  }
		  
		  if($this->db_host =='') {			
			throw new Exception(getMessage('database_host_blank'));
		  }
		  if($this->db_username =='') {			
			throw new Exception(getMessage('database_username_blank'));
		  }
		  if($this->db_password =='') {			
			throw new Exception(getMessage('database_password_blank'));
		  }
		  if($this->db_name =='') {			
			throw new Exception(getMessage('database_name_blank'));
		  }	
		  //create database object to use throughout
		  $this->db = MySqlDatabase::getInstance();
		  $this->db->connect($this->db_host, $this->db_username, $this->db_password,$this->db_name);	
		} catch(Exception $e) {	 
			die($e->getMessage()); 
		}  
		  
    }
	
	public function createLog($action,$date,$ip_address="",$user='SYSTEM'){ 
		$query = "insert into ".TABLE_ACTION_LOG."  (`action`,`action_date`,`action_user`,`ip_address`) VALUES ('".$action."','".$date."','".$user."','".$ip_address."')";  
		$result = $this->db->query($query); 
		if($result){
			return true;
		}
		return false;
	} 

    /**
     * send403()
     *
     */
    public function send403() {
        $status   = '403 Access Denied';
        $protocol = ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? substr( $_SERVER[ 'SERVER_PROTOCOL' ], 0, 8 ) : 'HTTP/1.1' ) . ' ';
        $header   = array(
             $protocol . $status,
            'Status: ' . $status,
            'Content-Length: 0'
        ); 
		 
		//echo "<pre>";print_r($header);echo "</pre>";
		//exit();
         foreach ( $header as $sent ) {
            header( $sent );
        }  
        exit();
    }
    
    /**
     * send444()
     *
     */
    public function send444() {
        error_reporting( 0 );
        $status   = '444 No Response';
        $protocol = ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? substr( $_SERVER[ 'SERVER_PROTOCOL' ], 0, 8 ) : 'HTTP/1.1' ) . ' ';
        $header   = array(
             $protocol . $status,
            'Status: ' . $status 
        );
        
        foreach ( $header as $sent ) {
            header( $sent );
        }
        exit();
    }	
	
	public function getUserIpAddress(){
		
		   if (!empty($_SERVER['HTTP_CLIENT_IP'])) //if from shared
			{
				return $_SERVER['HTTP_CLIENT_IP'];
			}
			else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //if from a proxy
			{
				return $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			else{
				return $_SERVER['REMOTE_ADDR'];
			}
	}
}
//end class 
 
?>
