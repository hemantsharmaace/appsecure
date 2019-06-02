<?php
/**
 * Class that handle all the incoming requests in the system
 * @author Quatrro
 * @version 1.0
 */  
 class Requests extends Base { 
 
    public $exceptions = array('+++','testtesttest++++++++///'); 
    	  
    public function __construct(){	 
        parent::__construct();
	}
     
    public function handleRequests() {  

		$request_string = null;	
		$request_recieved_date = null;
		$request_header = null;
		$request_param = null;
		$request_type = null;
		$global_post_data = null;
		$request_ip_address = $this->getUserIpAddress();	
   		$request_recieved_date = date('Y-m-d H:i:s');		 
		$request_header = json_encode(apache_request_headers());
		
		$request_string = $_SERVER['REQUEST_URI'];		
		$query_string = $_SERVER['QUERY_STRING'];
        $user_agent = $_SERVER['HTTP_USER_AGENT']; 
		$request_type = $_SERVER['REQUEST_METHOD'];
		
		$request_params = json_encode($_REQUEST);   
		
		
		$global_post_data = file_get_contents("php://input"); 
		 
		 		 
	  
		//check if the current request  is blocked or not from already saved requests in the blocked request table 
	    /*$selectRequestSql = "";
		$sSQL = "";
		$selectRequestSql .= "select * from ".TABLE_REQUEST." as tr INNER join ".TABLE_BLOCKED_REQUESTS." as tbr on tr.id = tbr.request_id";
		 
		$where ="";
		
        if($request_string){
 		  $where .=" tr.incoming_request ='".$this->db->realEscapeString($request_string)."' AND";
		}	
		
		if($query_string){
 		  $where .=" tr.query_string ='".$this->db->realEscapeString($query_string)."' AND";
		}	 
		
		if($_REQUEST){
			$where .=" tr.request_params LIKE '%".$this->db->realEscapeString($request_params)."%' AND";
		}
		
		$where = rtrim($where," AND");
		
		if($where){
			$sSQL = $selectRequestSql." WHERE ".$where;
		} 
	//	echo $sSQL; exit; 
		
 		  
		$response = $this->db->fetchOneRow($sSQL); 
		
		  
	    // we get the request in the  table so block request immediately
		if($response->id != NULL){ 
			$this->blockRequest();	
		}*/
		
		//now create log for the request received
		$this->createLog(getMessage('request_received'),$request_recieved_date,$request_ip_address); 
		
     	//check the complete request if found malicious block the request, make an entry into blocked requests and received requests	
		$result = $this->checkRequest($request_string,$query_string,$user_agent,$request_params,$request_type,$global_post_data);	
	 
		//if the result is true than we will block the request insert into the block request table.
		if($result){
			 
			
			$insertId = $this->insertRequests($request_string,$request_recieved_date,$request_header,$request_ip_address,$request_params,$query_string,$user_agent,$request_type,$global_post_data);	
		 
			if($insertId){
				 $this->inserBlockedRequests($insertId);
			}
			$this->createLog(getMessage('request_blocked'),$request_recieved_date,$request_ip_address);	
			$this->blockRequest();			
		} else {
			// found the false result than we are good to go no blocking
			$this->insertRequests($request_string,$request_recieved_date,$request_header,$request_ip_address,$request_params,$query_string,$user_agent,$request_type,$global_post_data);		   
		}
		
		return true; 
    } 
    
	// insert all the request function
    public function insertRequests($requestString,$requestRecievedDate,$requestHeader,$requestIpAddress,$requestParams,$queryString,$userAgent,$requestType,$globalPostData){
		 
        $query = "insert into ".TABLE_REQUEST." (incoming_request, request_recieved_date,request_header, request_ip_address,request_params,query_string,user_agent,request_type,request_global_params) VALUES ('".$this->db->realEscapeString($requestString)."', '".$requestRecievedDate."', '".$requestHeader."','".$requestIpAddress."','".$this->db->realEscapeString($requestParams)."','".$this->db->realEscapeString($queryString)."','".$userAgent."','".$requestType."','".$globalPostData."')";
	 
	 
		$result =  $this->db->insert($query);
		
		if($result){
			 return $result;			
		} 
		return false; 
	}	
	
	// insert the blocked request function
	 public function inserBlockedRequests($requestId,$blockedBy='SYSTEM'){
		 
        $query = "insert into ".TABLE_BLOCKED_REQUESTS." (request_id, blocked_date,blocked_by) VALUES ('".$requestId."', '".date("Y-m-d H:i:s")."', '".$blockedBy."')";
	 
		$result =  $this->db->insert($query);
		
		if($result){
			 return $result;			
		} 
		return false; 
	}	
	
	/*
	* Main function to check the request all the incoming request 
	* If it returns true than the request is to be blocked otherwise it will return false
	* $request_uri =  URL 
	* $query_string =  ALL THE QUERY STRING PARAMETERS THAT ARE COMING IN THE URL
	* $user_agent = THE SYSTEM FROM WHERE THE REQUEST IS GENERATED
	* $request_params = ALL THE REQUEST  PARAMETERS THAT MAY BE GET AND POST
	*/	
	
	public function checkRequest($request_uri="",$query_string="",$user_agent="",$request_params="",$req_method="",$global_post_data="") {
		
		/*You must analyze the SCHEME, HTTP_X_FORWARDED_PROTO, HOST, SERVER_NAME, SERVER_ADDR, REQUEST_URI, UNENCODED_URL, HTTP_X_ORIGINAL_URL, ORIG_PATH_INFO, and QUERY_STRING elements of the $_SERVER superglobal elements in order to fully and accurately determine the request URI in a cross-platform way.*/
		  
		// request uri
		
		/* Method Blacklist*/
		if ( $req_method != "" && preg_match( "/^(TRACE|DELETE|TRACK)/i", $req_method) ) {
				return true;
		}
		
		/* User Agent Empty */
		if ( $user_agent != "" && preg_match( "/(^$)/i", $user_agent) ) {
			return true;
		}
		
		//if sql injection 			
		$sql  = "[\x22\x27](\s)*(or|and)(\s).*(\s)*\x3d|";
		$sql .= "cmd=ls|cmd%3Dls|";
		$sql .= "(drop|alter|create|truncate).*(index|table|database)|";
		$sql .= "insert(\s).*(into|member.|value.)|";
		$sql .= "(select|union|order).*(select|union|order)|";
		$sql .= "0x[0-9a-f][0-9a-f]|";
		$sql .= "benchmark\([0-9]+,[a-z]+|benchmark\%28+[0-9]+%2c[a-z]+|";
		$sql .= "eval\(.*\(.*|eval%28.*%28.*|";
		$sql .= "update.*set.*=|delete.*from";
			
		if ( preg_match( "/^.*(".$sql.").*/i", $query_string) ) {
				return true;
		} 		
		
		$traversal = "\.\.\/|\.\.\\|%2e%2e%2f|%2e%2e\/|\.\.%2f|%2e%2e%5c";
		$rfi  = "%00|";
		$rfi .= "(?:((?:ht|f)tp(?:s?)|file|webdav)\:\/\/|~\/|\/).*\.\w{2,3}|";
		$rfi .= "(?:((?:ht|f)tp(?:s?)|file|webdav)%3a%2f%2f|%7e%2f%2f).*\.\w{2,3}";
		
		$xss  = "javascript|vbscript|expression|applet|meta|xml|blink|";
        $xss .= "link|style|script|embed|object|iframe|frame|frameset|";
        $xss .= "ilayer|layer|bgsound|title|base|form|img|body|href|div|cdata";
		
		
		/* Query - Cross Site Scripting */
		if ( preg_match( "/(<|<.)[^>]*(".$xss.")[^>]*>/i", $query_string) ) {
			return true;
		} elseif ( preg_match( "/((\%3c)|(\%3c).)[^(\%3e)]*(".$xss.")[^(\%3e)]*(%3e)/i", $query_string) ) {
			return true;
		}
		
		/* Query - traversal */
		if ( preg_match( "/^.*(".$traversal.").*/i", $query_string) ) {
			return true;
		}
		 
		/* Query - Remote File Inclusion */
		if ( preg_match( "/^.*(".$rfi.").*/i", $query_string) ) {
			return true;
		}
		
		if ($request_uri != "" && ((stripos($request_uri, 'eval(') !== false) || 
			 (stripos($request_uri, 'CONCAT') !== false) || 
			 (stripos($request_uri, 'UNION+SELECT') !== false) || 
			 (stripos($request_uri, '(null)') !== false) || 
			 (stripos($request_uri, 'base64_') !== false) || 
			 (stripos($request_uri, '/localhost') !== false) || 
			 (stripos($request_uri, '/pingserver') !== false) || 
			 (stripos($request_uri, '/config.') !== false) || 
			 (stripos($request_uri, '/wwwroot') !== false) || 
			 (stripos($request_uri, '/makefile') !== false) || 
			 (stripos($request_uri, 'crossdomain.') !== false) || 
			 (stripos($request_uri, 'proc/self/environ') !== false) || 
			 (stripos($request_uri, 'etc/passwd') !== false) || 
			 (stripos($request_uri, '/https/') !== false) || 
			 (stripos($request_uri, '/http/') !== false) || 
			 (stripos($request_uri, '/ftp/') !== false) || 
			 (stripos($request_uri, '/cgi/') !== false) || 
			 (stripos($request_uri, '.cgi') !== false) || 
			 (stripos($request_uri, '.exe') !== false) || 
			 (stripos($request_uri, '.sql') !== false) || 
			 (stripos($request_uri, '.ini') !== false) || 
			 (stripos($request_uri, '.dll') !== false) || 
			 (stripos($request_uri, '.asp') !== false) || 
			 (stripos($request_uri, '.jsp') !== false) || 
			 (stripos($request_uri, '/.bash') !== false) || 
			 (stripos($request_uri, '/.git') !== false) || 
			 (stripos($request_uri, '/.svn') !== false) || 
			 (stripos($request_uri, '/.tar') !== false) || 
			 (stripos($request_uri, ' ') !== false) || 
			 (stripos($request_uri, '<') !== false) || 
			 (stripos($request_uri, '>') !== false) || 
			 (stripos($request_uri, '/=') !== false) || 
			 (stripos($request_uri, '...') !== false) || 
			 (stripos($request_uri, '+++') !== false) || 
			 (stripos($request_uri, '://') !== false) || 
			 (stripos($request_uri, '/&&') !== false))) {   
				return true;
			}    
			 
			// query strings
		   if($query_string != "" && 
		     ((stripos($query_string, '?') !== false) || 
			 (stripos($query_string, ':') !== false) || 
			 (stripos($query_string, '[') !== false) || 
			 (stripos($query_string, ']') !== false) || 
			 (stripos($query_string, '../') !== false) || 
			 (stripos($query_string, '127.0.0.1') !== false) || 
			 (stripos($query_string, 'loopback') !== false) || 
			 (stripos($query_string, '%0A') !== false) || 
			 (stripos($query_string, '%0D') !== false) || 
			 (stripos($query_string, '%22') !== false) || 
			 (stripos($query_string, '%27') !== false) || 
			 (stripos($query_string, '%3C') !== false) || 
			 (stripos($query_string, '%3E') !== false) || 
			 (stripos($query_string, '%00') !== false) || 
			 (stripos($query_string, '%2e%2e') !== false) || 
			 (stripos($query_string, 'union') !== false) || 
			 (stripos($query_string, 'input_file') !== false) || 
			 (stripos($query_string, 'execute') !== false) || 
			 (stripos($query_string, 'mosconfig') !== false) || 
			 (stripos($query_string, 'environ') !== false) || 
			//stripos($query_string, 'scanner') !== false) || 
			(stripos($query_string, 'path=.') !== false) || 
			 (stripos($query_string, 'mod=.') !== false))) { 
			 
 			  return true;
			}    
		 
			
			// user agents
		  if($user_agent != "" && ((stripos($user_agent, 'binlar') !== false) || 
			 (stripos($user_agent, 'casper') !== false) || 
			 (stripos($user_agent, 'cmswor') !== false) || 
			 (stripos($user_agent, 'diavol') !== false) || 
			 (stripos($user_agent, 'dotbot') !== false) || 
			 (stripos($user_agent, 'finder') !== false) || 
			 (stripos($user_agent, 'flicky') !== false) || 
			 (stripos($user_agent, 'libwww') !== false) || 
			 (stripos($user_agent, 'nutch') !== false) || 
			 (stripos($user_agent, 'planet') !== false) || 
			 (stripos($user_agent, 'purebot') !== false) || 
			 (stripos($user_agent, 'pycurl') !== false) || 
			 (stripos($user_agent, 'skygrid') !== false) || 
			 (stripos($user_agent, 'sucker') !== false) || 
			 (stripos($user_agent, 'turnit') !== false) || 
			 (stripos($user_agent, 'vikspi') !== false) || 
			 (stripos($user_agent, 'zmeu') !== false))) {  
				return true;
			}    
			
			
			$request_uri = htmlspecialchars_decode($request_uri);
			$query_string = htmlspecialchars_decode($query_string);
			$checkRequestStringValid = false;
			$queryStringValid = false;
			$checkRequestStringValid = $this->checkMaliciousValue($request_uri);
			$queryStringValid = $this->checkMaliciousValue($query_string);
			
			
			if($checkRequestStringValid){
				return true;
			}
			if($queryStringValid){
				return true;
			}
			$checkParamValue = false;
			$checkGlobalParamValue = false;
			
			$requestParams = array();
			$requestParams = json_decode($request_params,true);		
 		
			if(is_array($requestParams)){
				foreach($requestParams as $param){					
				    $checkParamValue = $this->checkMaliciousValue($param);
					if($checkParamValue){
						break;
					}
				} 
			}
			
			$globalParams = array();
		    
			if($global_post_data){
				
				 $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:','ns8869:'], '', $global_post_data);
			     $xml = simplexml_load_string($clean_xml); 
 				 $response = $xml->Body->children();  
				 $globalParams = simpleXmlToArray($response);
			} 
			
			if(is_array($globalParams)){
				foreach($globalParams as $param){					
				    $checkGlobalParamValue = $this->checkMaliciousValue($param);
					if($checkGlobalParamValue){
						break;
					}
				} 
			}
			
			if($checkGlobalParamValue){
				return true;
			} 
			return false; 
	}
	
	public function checkMaliciousValue($value){  
	
	        if($value != "" && 
			 ((stripos($value, 'eval(') !== false) || 
			 (stripos($value, 'CONCAT') !== false) || 
			 (stripos($value, 'UNION+SELECT') !== false) || 
			 (stripos($value, '(null)') !== false) || 
			 (stripos($value, 'base64_') !== false) || 
			 (stripos($value, '/localhost') !== false) || 
			 (stripos($value, '/pingserver') !== false) || 
			 (stripos($value, '/config.') !== false) || 
			 (stripos($value, '/wwwroot') !== false) || 
			 (stripos($value, '/makefile') !== false) || 
			 (stripos($value, 'crossdomain.') !== false) || 
			 (stripos($value, 'proc/self/environ') !== false) || 
			 (stripos($value, 'etc/passwd') !== false) || 
			 (stripos($value, '/https/') !== false) || 
			 (stripos($value, '/http/') !== false) || 
			 (stripos($value, '/ftp/') !== false) || 
			 (stripos($value, '/cgi/') !== false) || 
			 (stripos($value, '.cgi') !== false) || 
			 (stripos($value, '.exe') !== false) || 
			 (stripos($value, '.sql') !== false) || 
			 (stripos($value, '.ini') !== false) || 
			 (stripos($value, '.dll') !== false) || 
			 (stripos($value, '.asp') !== false) || 
			 (stripos($value, '.jsp') !== false) || 
			 (stripos($value, '/.bash') !== false) || 
			 (stripos($value, '/.git') !== false) || 
			 (stripos($value, '/.svn') !== false) || 
			 (stripos($value, '/.tar') !== false) || 
			 (stripos($value, ' ') !== false) || 
			 (stripos($value, '<') !== false) || 
			 (stripos($value, '>') !== false) || 
			 (stripos($value, '/=') !== false) || 
			 (stripos($value, '...') !== false) || 
			 (stripos($value, '+++') !== false) || 
			 (stripos($value, '://') !== false) || 
			 (stripos($value, '/&&') !== false)|| 			
			 (stripos($value, ':') !== false) || 
			 (stripos($value, '[') !== false) || 
			 (stripos($value, ']') !== false) || 
			 (stripos($value, '../') !== false) || 
			 (stripos($value, '127.0.0.1') !== false) || 
			 (stripos($value, 'loopback') !== false) || 
			 (stripos($value, '%0A') !== false) || 
			 (stripos($value, '%0D') !== false) || 
			 (stripos($value, '%22') !== false) || 
			 (stripos($value, '%27') !== false) || 
			 (stripos($value, '%3C') !== false) || 
			 (stripos($value, '%3E') !== false) || 
			 (stripos($value, '%00') !== false) || 
			 (stripos($value, '%2e%2e') !== false) || 
			 (stripos($value, 'union') !== false) || 
			 (stripos($value, 'input_file') !== false) || 
			 (stripos($value, 'execute') !== false) || 
			 (stripos($value, 'mosconfig') !== false) || 
			 (stripos($value, 'environ') !== false) || 			 
		     (stripos($value, 'path=.') !== false) || 
			 (stripos($value, 'mod=.') !== false))) { 
			  return true;
			}    
		 
	
			if(preg_match( '#</*(?:applet|b(?:ase|gsound|link)|embed|noembed|noframes|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|area|img|a|object|s(?:cript|tyle)|title|xml|noscript|video|audio|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|base|form|body|href|div|cdata)[^>]*+>#i', $value ) || 
			preg_match('/\.{2,}/', $value) || 
			preg_match('/\+{3,}/', $value) || 
			preg_match('/\~{3,}/', $value) || 
			preg_match('/\`{2,}/', $value) || 
			preg_match('/\!{2,}/', $value) || 
			preg_match('/\s\s+/', $value) || 
			preg_match('#(?<=<)\w+(?=[^<]*?>)#',$value) || 
			preg_match('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', $value )){
				 
				return true;
			} 
			
			//need to write for this kind of request id=1+un/**/ion+sel/**/ect+1,2,3-- 
		    return false; 
		 			   
	}
	  
	
	public function blockRequest(){  
		 $this->send403(); 			   
	}
	 
	 
} 
//end class 
 
?>
