<?php
/*
** Copyright (c) Richard Uren 2009 <richard@teleport.com.au>
** All Rights Reserved
**
** --
**
** LICENSE: Redistribution and use in source and binary forms, with or
** without modification, are permitted provided that the following
** conditions are met: Redistributions of source code must retain the
** above copyright notice, this list of conditions and the following
** disclaimer. Redistributions in binary form must reproduce the above
** copyright notice, this list of conditions and the following disclaimer
** in the documentation and/or other materials provided with the
** distribution.
**
** THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
** WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
** MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
** NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
** INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
** BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
** OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
** ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
** TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
** USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
** DAMAGE.
**
** --
**
** This is a reference implementation for interfacing with www.handsetdetection.com
**
*/

if (! class_exists('WSSE')) {
	require_once('class.wsse.php');
}

if (! function_exists('json_encode')) {
	require_once('json.php');
}

class HandsetDetection {
	
	var $url = 'http://api.handsetdetection.com';
	var $software_token = '8a4a663ba61f9ac9407894a370871bf3';
	var $secret = '';
	var $email = '';
	var $siteId = 0;
	
	var $fastjson;
	var $timeout = 5;
	var $timeoutError = false;
	var $useSession = TRUE;
	var $detectRequest = array();
	var $detectReply = array();
	var $vendorReply = array();
	var $modelReply = array();	
	var $downloadReply = array();		
	var $error = '';
	
	var $mobile_site;
	
	// If enabled then use HTTP proxy
	var $useProxy = 0;
	var $proxyServer;
	var $proxyPort;	
	var $proxyUser = '';
	var $proxyPass = '';

	// If enabled then perform local detections
	var $localDetection = 0;
	var $localDatabaseType = 'sqlite';  	
	var $localDatabase = 'hd.db';
	var $tempFileName = '';
	
	// Its bound to be one of these .. hdconfig.php is the preferred filename (these days).
	var $configFiles = array('hdconfig.ini','hdconfig.php');
	
	function HandsetDetection() {
		
		// Find the config file - in the hdapi directory, someplace on the path or in a hdapi directory on the path.
		$dirs = explode(PATH_SEPARATOR, get_include_path());
		array_unshift($dirs, dirname(__FILE__));
		foreach ($this->configFiles as $file) {
			foreach ($dirs as $path) {
				if (file_exists($path.DIRECTORY_SEPARATOR.$file)) {
					$configFile = $path.DIRECTORY_SEPARATOR.$file;
					break 2;
				}
				if (file_exists($path.DIRECTORY_SEPARATOR.'hdapi'.DIRECTORY_SEPARATOR.$file)) {
					$configFile = $path.DIRECTORY_SEPARATOR.'hdapi'.DIRECTORY_SEPARATOR.$file;
					break 2;
				} 
			}
		}
		if (empty($configFile)) {
			echo 'Error : Missing configuration file (hdconfig.php)';
			exit;
		}
		
		$hdconfig = parse_ini_file($configFile);
		if (empty($hdconfig)){
			echo 'Error : Invalid config file. The config file should be in ini file format';
			exit;
		}
		
		if (empty($hdconfig['email']) || empty($hdconfig['secret'])) {
			echo 'Error : Please set your email address and secret in the hdconfig.php file<br/>';
			echo 'Error : Get the secret at your <a href="http://www.handsetdetection.com/users/index">Profile</a> page';
			exit;
		}

		$this->setEmail($hdconfig['email']);
		$this->setSecret($hdconfig['secret']);
			
		if(!empty($hdconfig['software_token'])) $this->setSoftwareToken($hdconfig['software_token']);
		if(!empty($hdconfig['mobile_site'])) $this->setMobileSite($hdconfig['mobile_site']);
		if(!empty($hdconfig['site_id'])) $this->setSiteId($hdconfig['site_id']);
		
		// Proxy settings
		if(!empty($hdconfig['use_proxy'])) $this->setUseProxy($hdconfig['use_proxy']);
		if(!empty($hdconfig['proxy_server'])) $this->setProxyServer($hdconfig['proxy_server']);
		if(!empty($hdconfig['proxy_port'])) $this->setProxyPort($hdconfig['proxy_port']);
		if(!empty($hdconfig['proxy_user'])) $this->setProxyUser($hdconfig['proxy_user']);
		if(!empty($hdconfig['proxy_pass'])) $this->setProxyPass($hdconfig['proxy_pass']);

		if(!empty($hdconfig['enable_local_detect'])) $this->setLocalDetection($hdconfig['enable_local_detect']);
		$this->fastjson =  function_exists('json_encode') && function_exists('json_decode');
	}
	
	function setLocalDatabase($dbfile){$this->localDatabase = $dbfile;}
	function setLocalDetection($enable){$this->localDetection = $enable;}
	
	function setProxyUser($user){ $this->proxyUser = $user; }
	function setProxyPass($pass){ $this->proxyPass = $pass; }
	function setUseProxy($proxy){ $this->useProxy = $proxy; }
	function setProxyServer($name) { $this->proxyServer=$name; }
	function setProxyPort($number) {$this->proxyPort=$number; }
	function setError($msg) { $this->error = $msg; }
	function setUrl($url) { $this->url = $url; }	
	function setSoftwareToken($software_token) { $this->software_token = $software_token; }
	function setMobileSite($mobile_site) { $this->mobile_site = $mobile_site; }
	function setSecret($secret) { $this->secret = $secret; }
	function setEmail($email) { $this->email = $email; }
	function setTimeout($timeout) { $this->timeout = $timeout; }
	function setCacheInSession($bool) { $this->useSession = $bool; }
	function setDetectVar($key, $value) { $this->detectRequest[$key] = $value; }
	function setSiteId($siteid) { $this->siteId = $siteid; }
	
	function getLocalDatabase() { return $this->localDatabase; }
	function getLocalDetection() { return $this->localDetection; }
	function getProxyUser(){ return $this->proxyUser; }
	function getProxyPass(){ return $this->proxyPass; }
	function getUseProxy(){ return $this->useProxy; }
	function getProxyServer(){ return $this->proxyServer; }
	function getProxyPort(){ return $this->proxyPort; }
	function getError() { return $this->error; }
	function getUrl() { return $this->url; }	
	function getSoftwareToken() { return $this->software_token; }
	function getSecret() { return $this->secret; }
	function getEmail() { return $this->email; }
	function getTimeout() { return $this->timeout; }
	function getCacheInSession() { return $this->useSession; }
	function getDetect() { return $this->detectReply; }
	function getVendor() { return $this->vendorReply; }
	function getModel() { return $this->modelReply; }
	function getSiteId() { return $this->siteId; }
	
	function clearCache() { unset ($_SESSION['handsetdetection']); }

	function redirectToMobileSite(){
		if ($this->mobile_site != '') {
			header('Location: '.$this->mobile_site);
			exit;
		} 
	} 
	
	function getDeviceClickToCall() {
		if (! isset($this->detectReply['xhtml_ui']['xhtml_make_phone_call_string']))
			return FALSE;
		return $this->detectReply['xhtml_ui']['xhtml_make_phone_call_string'];
	}
	
	function getDeviceResolution() {
		if (! isset($this->detectReply['display']['resolution_width']) || ! isset($this->detectReply['display']['resolution_height']))
			return FALSE;
		$tmp = array();
		$tmp['width'] = $this->detectReply['display']['resolution_width'];
		$tmp['height'] = $this->detectReply['display']['resolution_height'];
		return $tmp;
	}
	
	function getDeviceSendSms() {
		if (! isset($this->detectReply['xhtml_ui']['xhtml_send_sms_string']))
			return FALSE;
		return $this->detectReply['xhtml_ui']['xhtml_send_sms_string'];
	}
	
	/** Public Functions **/
	
	// Backwards Compatibility .. 
	function detectInit() { $this->setup(); }
	function downloadInit() { $this->setup(); }
	
	// Read variables form the server - likely what you want to send to HD for detection.
	// You can override these with $this->setDetectVar($key, $value)
	function setup() {
		foreach ($_SERVER as $key => $value) {
			// Send any X* or HTTP* server variables through for header matching
			if (preg_match("/(^x|^X|^http|^HTTP|^Http)/", $key) &&  !(preg_match("/(^http_cookie)/i",$key)) ) {
				$this->detectRequest[$key] = $value;
			}
		}			
	
		if (isset($_SERVER['HTTP_USER_AGENT']))		
			$this->detectRequest['user-agent'] = $_SERVER['HTTP_USER_AGENT'];
		if (isset($_SERVER['REMOTE_ADDR']))
			$this->detectRequest['ipaddress'] = $_SERVER['REMOTE_ADDR'];
		if (isset($_SERVER['REQUEST_URI']))
			$this->detectRequest['request_uri'] = $_SERVER['REQUEST_URI'];	
	}

	// Perform Detection
	// Cache reply in session if required (and return cached result - speedy!) 
	// If the user agent changes then detect again.
	function detect($options="all") {
		if ($this->useSession && isset($_SESSION['handsetdetection']) && $_SERVER['HTTP_USER_AGENT'] == $_SESSION['handsetdetectionagent']) {
			$this->detectReply = $_SESSION['handsetdetection'];
			return TRUE;
		}

		$this->detectRequest['options'] = $options;
		$result = $this->_sendjson($this->detectRequest, "/devices/detect.json");
		$this->detectReply = $result;

		if ($this->useSession && session_id() != "") {
			$_SESSION['handsetdetection'] = $result;
			$_SESSION['handsetdetectionagent'] = $_SERVER['HTTP_USER_AGENT'];
		}

		if ($result && $result['message'] == 'OK') {
			$this->error = '';
			return TRUE;
		}

		return FALSE;
	}

	// detectAll - Detects Tablets & Consoles as well.
	function detectAll($options='all') {
		if ($this->useSession && isset($_SESSION['handsetdetection']) && $_SERVER['HTTP_USER_AGENT'] == $_SESSION['handsetdetectionagent']) {
			$this->detectReply = $_SESSION['handsetdetection'];
			return TRUE;
		}

		$this->detectRequest['options'] = $options;
		$result = $this->_sendjson($this->detectRequest, "/devices/detectall.json");
		$this->detectReply = $result;

		if ($this->useSession && session_id() != "") {
			$_SESSION['handsetdetection'] = $result;
			$_SESSION['handsetdetectionagent'] = $_SERVER['HTTP_USER_AGENT'];
		}

		if ($result && $result['message'] == 'OK') {
			$this->error = '';
			return TRUE;
		}

		return FALSE;
	}
	
	// Get a list of all vendors - That was easy.
	function vendor() {
		$result = $this->_sendjson(null, "/devices/vendors.json");
		$this->vendorReply = $result;

		if ($result && $result['message'] == 'OK') {
			$this->error = '';
			return TRUE;
		}
		$this->error = $result['message'];
		return FALSE;
	}	
	
	// Get a list of all models for a specific vendor
	function model($vendor) {
		$vendorRequest = array('vendor' => $vendor);
		$result = $this->_sendjson($vendorRequest, "/devices/models.json");
		$this->modelReply = $result;

		if ($result && $result['message'] == 'OK') {
			$this->error = '';
			return TRUE;
		}
		$this->error = $result['message'];
		return FALSE;
	}		
	
	// Convenience wrappers for common 'What is this device' questions.
	function ismobile($options='product_info, display, geoip') {
		if (! isset($this->detectReply)) {
			$this->detectAll($options);
		}
		
		if (isset($this->detectReply['class']) && $this->detectReply['class'] == 'Mobile')
			return true;
		return false;
	}
	
	function istablet($options='product_info, display, geoip') {
		if (! isset($this->detectReply)) {
			$this->detectAll($options);
		}
		
		if (isset($this->detectReply['class']) && $this->detectReply['class'] == 'Tablet')
			return true;
		return false;		
	}
	
	function isconsole($options='product_info, display, geoip') {
		if (! isset($this->detectReply)) {
			$this->detectAll($options);
		}
		
		if (isset($this->detectReply['class']) && $this->detectReply['class'] == 'Console')
			return true;
		return false;
	}
	
	
	// Returns true if the http_referrer is the same as the server's domain OR 
	// the mobile site domain.
	function isLocal() {
		$referrer_host = '';
		if (isset($_SERVER['HTTP_REFERER'])) $referrer = parse_url($_SERVER['HTTP_REFERER']);
		if (isset($referrer['host'])) $referrer_host = $referrer['host'];
		
		$mobile_host = '';
		$mobile = parse_url($this->mobile_site);
		if (isset($mobile['host'])) $mobile_host = $mobile['host'];
		
		// NOTE : server name is www.jones.com no need to parse_url it
		$server_host = $_SERVER['HTTP_HOST'];		
		
		//print "Referrer $referrer_host Mobile $mobile_host Server $server_host";		
		if ($referrer_host == $mobile_host) return TRUE;
		if ($referrer_host == $server_host) return TRUE;
		return FALSE;
	}

	// Deprecated because its bogus !!	
	function redirectOnceOnly($options="all"){
		if (! empty($this->mobile_site)) {
			$referURL = $_SERVER['HTTP_REFERER'];
			$arrRefer = parse_url($referURL);
			$arrSite = parse_url($this->mobile_site);
			if($arrRefer['host'] == $arrSite['host']){
				//already on this site, nothing to do
				return FALSE;
			}
		} 
		return $this->detect($options);
	}

	// Download the Handset Detection database.
	function download(){
		// Up the timeout as files can be large.
		$this->timeout = 60;

		// Set credentials & send empty payload & get CSV file.
		$wsse = new WSSE($this->email, $this->secret);
		$hdr = $wsse->get_header(TRUE);
		$data = $this->_post("", '/devices/download.csv', $hdr);

		$this->tempFileName = tempnam(null,'hd');
		$fp = fopen($this->tempFileName,'w+');
		if ($fp) {
			fwrite($fp, $data);
			fclose($fp);
		} else {
			$this->error = "Failed to open tempfile for writing ".$this->tempFileName;
			return FALSE;
		}
		// Sanity check
		if (strlen($data) < 1024) { 			
			$this->error = "Failed to download, more details in file: ".$this->tempFileName;
			return FALSE;
		}
		return TRUE;
	}

	// Local Detection, try matching a user agent or a profile.
	function localDetect() {
		$xhdrs = array();
		// Bundle up interesting headers and stuff
		foreach ($_SERVER as $key => $value) {
			if (preg_match("/(^x|^X)/", $key)) {
				$xhdrs[strtolower($key)] = $value;
			}
		}			
		$agent = '';
		$profile = '';			
		if (isset($_SERVER['HTTP_USER_AGENT'])) $agent = $_SERVER['HTTP_USER_AGENT'];
		if (isset($xhdrs['x-wap-profile'])) $profile = $xhdrs['x-wap-profile'];
		
		return $this->_localSQLiteDetect($agent, $profile, $xhdrs);
	}

	// Import Handset Detection datafile, downloaded previously with download
	// One day there may be more importers and we may take config options for which database to use .. one day
	function import($filename = '') {
		return $this->_importSQLite($filename);
	}

	//************ Private Functions ***********//
	// From http://www.enyem.com/wiki/index.php/Send_POST_request_(PHP)
	// PHP 4/5 http post function
	// And modified to fit
	
	// Wrapper around _postData - Tries multiple hosts on timeout.
	// There's 3 pieces of data, the url, the host and the proxy
	function _post($data, $service, $optional_headers) {
		$url = $this->url.$service;
		$start	= strpos($url, '//') + 2;
		$end = min(strpos($url,"/",$start),strlen($url));
		$portPos  = strpos($url,":",$start);

		if ($portPos > 0) {
			$port = (int) substr($url, $portPos+1, ($end-$portPos)-1);
			$host = substr($url, $start, $portPos - $start);
		} else {
			$port = 80;
			$host = substr($url, $start, $end - $start);
		}

		// Resolve host
		// Make a list of ipAddresses from hostname
		// If multiple addresses then try each in random order if timeout.
		$ipList = gethostbynamel($host);
		$ipListLength = sizeof($ipList);
		
		if ($ipListLength == 0) {
			$this->setError("ERROR : Unknown host ($host)");
			return false;
		} elseif ($ipListLength == 1) {
			// Only one key (0)
			$rndKeys = array(0 => 0);
		} else {
			$rndKeys = array_rand($ipList, $ipListLength);
			shuffle($rndKeys);
		}
		foreach ($rndKeys as $key) {
			$host = $ipList[$key];
			$url = 'http://'.$host.$service;
			$status = $this->_postData($ipList[$key], $port, $url, $data, $optional_headers);
			if ($status)
				break;
		}
		return $status;
	}

	// $host - Host to connect to
	// $port - Port to connect to on host
	// $url - Service endpoint
	// $data - What to send
	// $optional_headers - Optional headers. :-)	
	function _postData($host, $port, $url, $data, $optional_headers) {
		
		if ($this->useProxy) {
			$host = $this->proxyServer;
			$port = $this->proxyPort;
		}
		
		$timeout = $this->timeout;
		$this->timeoutError = false;
														
		// * Connect *
		//echo "Connecting to $host, port $port, url $url<br/>";
		$errno = ""; $errstr="";
		$fp = fsockopen($host, $port, $errno, $errstr, $timeout ); 		
		//* * handle errors */
		if ( ! $fp ) {
			$this->setError("Cannot connect to $host, port $port, path $domain: $errstr");
			return false;
		}													
		//* * connection successful, write headers */
		// Use HTTP/1.0 (to disable content chunking on large replies).
		fputs($fp,"POST $url HTTP/1.0\r\n");  
		fputs($fp, "Host: $host\r\n");
		if ($this->useProxy && ! empty($this->proxyUser) && ! empty($this->proxyPass)) {
			fputs($fp,"Proxy-Authorization:Basic ".base64_encode("$this->proxyUser:$this->proxyPass")."\r\n");
		}
		fputs($fp, "Content-Type: application/json\r\n");
		fputs($fp, "Software-Token: " . $this->software_token . "\r\n");
		fputs($fp, "Authorization: ". $optional_headers['Authorization']."\r\n");
		fputs($fp, "X-wsse: " . $optional_headers['X-WSSE']."\r\n");
		fputs($fp, "Content-length: " . strlen($data) . "\r\n\r\n");
		fputs($fp, "$data\r\n\r\n");
									
		$response = "";
		$time = time();

		/*
		 * Get response. Badly behaving servers might not maintain or close the stream properly, 
		 * we need to check for a timeout if the server doesn't send anything.
		 */
		$timeout_status = FALSE;
		
		stream_set_blocking ( $fp, 0 );
		while ( ! feof( $fp )  and ! $timeout_status) {
			$r = fgets($fp, 1024*25);
			if ( $r ) {
				$response .= $r;
				$time = time();
			}
			if ((time() - $time) > $timeout)
				$timeout_status = TRUE;
		}
		
		if($timeout_status == TRUE){
			$this->setError("Timeout when reading the stream."); 	
			$this->timeoutError = true;	
			return false;
		}
		if(!feof($fp)){
			$this->setError("Reading stream but failed to read the entire stream.");	
			return false;
		}

		fclose($fp); 

   		$hunks = explode("\r\n\r\n",trim($response));
   		if (!is_array($hunks) or count($hunks) < 2) {
			$this->setError("The response too short.");
       		return FALSE;
       	}
   		$header = $hunks[count($hunks) - 2];
   		$body = $hunks[count($hunks) - 1];
   		$headers = explode("\n",$header);

		if (strlen($body)) return $body;
		$this->setError("The response body is empty.");
		return FALSE;
	}

	// Send some JSON to the Handset Detection Web Service.
	function _sendjson($data, $service) {
		$tmp = '';
		
		// Add site id into all requests
		if ($this->siteId != 0) {
			$data['site'] = $this->siteId;
		}
		
		if ($this->fastjson) {
			if ($data) $tmp = json_encode($data);
		} else {
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
			if ($data) $tmp = $json->encode($data);
		}
		$wsse = new WSSE($this->email, $this->secret);
		$hdr = $wsse->get_header(TRUE);
		$reply = $this->_post($tmp, $service, $hdr);

		if ($reply === FALSE) return FALSE;

		if ($this->fastjson) {
			$answer = json_decode($reply, true);
		} else {
			$answer = $json->decode($reply);			
		}
		return $answer;
	}
	
	// SQLite local Detection 
	function _localSQLiteDetect($agent, $profile, $xhdrs) {

		// Open SQLite DB
		$db = @sqlite_open($this->getLocalDatabase());
		if(! $db) {
			$this->error = "Could not open local database:".$this->getLocalDatabase();
			return FALSE;
		}

		$escagent = $this->_escape($agent);
		$escprofile = $this->_escape($profile);
		$device = NULL;

		// 4 checks, agent & profile, then agent, then profile, ... lastly dredge through xheaders.
		if (! empty($agent) && ! empty($profile)) {
			$sql = "select * from agents where agent = '$escagent' and profile = '$escprofile'";
			$device = @sqlite_array_query($db, $sql, SQLITE_ASSOC);
		}
		
		if (empty($device) && ! empty($agent)) {
			$sql = "select * from agents where agent = '$escagent'";
			$device = @sqlite_array_query($db, $sql, SQLITE_ASSOC);
		}

		if (empty($device) && ! empty($profile)) {
			$sql = "select * from agents where profile = '$escprofile'";
			$device = @sqlite_array_query($db, $sql, SQLITE_ASSOC);
		}
		
		// Iterate over the xheaders incase there's something interesting buried by a transcoder/proxy
		if (empty($device) && count($xhdrs)) {
			foreach ($xhdrs as $xhkey => $xhvalue) {
				$xhagent = $this->_escape($xhvalue);
				$sql = "select * from agents where agent = '$xhagent'";
				$device = @sqlite_array_query($db, $sql, SQLITE_ASSOC);
				if (! empty($device)) break;
			}	
		}

		// Nothing found - return the bad news.
		if (empty($device)) {
			$this->detectReply['message'] = 'Not Found';
			$this->detectReply['status'] = 301;			
			return FALSE;
		}
		
		$this->detectReply['product_info'] = $device[0];
		$this->detectReply['message'] = 'OK';
		$this->detectReply['status'] = 0;
		return TRUE;
	}

	function _escape($string) {
		return sqlite_escape_string(trim($string));
	}
	
	// Worker Function - importSQLite - one day there may be importers for lots of databases.
	function _importSQLite($filename) {
		if (empty($filename)) $filename = $this->tempFileName;
		if (! file_exists($filename)){
			$this->error = "$filename does not exist.";
			return FALSE;
		}

		// Check the databse
		if(!function_exists('sqlite_open') || !function_exists('sqlite_exec') || !function_exists('sqlite_close')) {
			$this->error = "<br />SQLite not supported. Please upgrade to PHP5 or install PECL sqlite >= 1.0.0.<br />Your datafile can be found in $filename";
			return FALSE;
		}
		
		$fp = @fopen($filename,'r+');
		if (!$fp) {
			$this->error = "Failed to open $filename.";
			return FALSE;
		}

		$head = @fgetcsv($fp,1024,',','"');	
		
		// Ready to import data into local database
		$dbHandler = @sqlite_open($this->getLocalDatabase());
		if(! $dbHandler) {
			$this->error = "Could not open local database:".$this->getLocalDatabase();
			return FALSE;
		}
		
		// Delete all existing data in local database first, then insert the data
		$sql = "drop table agents";
		$ret = @sqlite_exec($dbHandler, $sql, $error);
		if(!$ret && !strstr($error,'no such table')){
			$this->error = "Failed to drop table agents";
			return FALSE;
		}
		
		$sql = "CREATE TABLE agents (
			   id  int(11) NOT NULL ,
			   agent  varchar(255) NOT NULL,
			   brand_name  varchar(80) DEFAULT NULL,
			   model_name  varchar(80) DEFAULT NULL,
			   profile varchar(255) DEFAULT NULL,
			   created  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			   device_os  varchar(40) NOT NULL DEFAULT '',
			   device_os_version  varchar(20) NOT NULL DEFAULT '',
			   mobile_browser  varchar(40) NOT NULL DEFAULT '',
			   mobile_browser_version  varchar(20) NOT NULL DEFAULT '',
			   language  varchar(2) NOT NULL DEFAULT '',
			   mobilebcp  int(1) NOT NULL DEFAULT '0',
			   display  varchar(11) NOT NULL DEFAULT '',
			  PRIMARY KEY ( id )
			)";
			
		$ret = @sqlite_exec($dbHandler, $sql, $error);
		if(!$ret) {
			$this->error = "Failed to create table agents";
			return FALSE;
		}

		$total = 0;
		$bad = 0;
		while($row = fgetcsv($fp,1024,',','"')) {  			
			$insert="insert into agents(id, agent, brand_name, model_name, profile, created, device_os, device_os_version, mobile_browser, mobile_browser_version, language, mobilebcp, display) values (";
			$insert.=$this->_clean($row[0],'int').",";
			$insert.=$this->_clean($row[1]).",";
			$insert.=$this->_clean($row[2]).",";
			$insert.=$this->_clean($row[3]).",";
			$insert.=$this->_clean($row[4]).",";
			$insert.=$this->_clean($row[5]).",";
			$insert.=$this->_clean($row[6]).",";
			$insert.=$this->_clean($row[7]).",";
			$insert.=$this->_clean($row[8]).",";
			$insert.=$this->_clean($row[9]).",";
			$insert.=$this->_clean($row[10]).",";
			$insert.=$this->_clean($row[11],'int').",";
			$insert.=$this->_clean($row[12]).")";
			$ret = @sqlite_exec($dbHandler, $insert, $error);
			$total++;
			if(! $ret) {
				$bad++; 
				echo "Failure ";var_dump($row);
				echo "<br />$error<br />$insert<br /><br />";
			}
		}
		
		@sqlite_close($hdHandler);
		@fclose($fp);
		echo "Total Handsets Loaded $total Failures $bad<br/>";
		return TRUE;
	}
	
	function _clean($value, $type='char') {
		if ($type == 'int') return (int) $value;
		return "'".sqlite_escape_string(stripslashes(trim($value)))."'";
	}
	/***
	//uses: import the download file into mysql database
	//note: if the table already exists in provided db, it will be replaced.
	function importDownloadIntoMysql($filename,$host,$user,$pass,$db,$table){
		if($filename=='' && isset($this->tempFileName)) $filename=$this->tempFileName;
		if(!file_exists($filename)){
			echo "$filename does not exist.";return false;
		}
		if(filesize($filename)<1024){
			echo "<br />$filename is too short, not imported yet.<br />";return false;
		}
		
		//mysql db connection
		$link = @mysql_connect($host, $user, $pass);
		if (!$link) {
		    echo '<br />Could not connect local database: ' . mysql_error();return false;
		}
	
		$db_selected = mysql_select_db($db, $link);
		if (!$db_selected) {
		    echo "<br />Can not use $db :" . mysql_error();	return false;
		}
		
		$fp=@fopen($filename,'r+');
		if(!$fp){
			echo "Failed to open $filename.";return false;
		}

		$head=@fgetcsv($fp,1024,',','"'); //read the head which is the first line
		
		//read the data
		$ret=mysql_query("DROP TABLE IF EXISTS `".$table."`");
		if(!$ret){
			echo "<br />Failed to execute SQL drop table if exists... :".mysql_error(); return false;
		}
		$ret=mysql_query("CREATE TABLE `".$table."` (
			   `id`  int(11) NOT NULL ,
			   `agent`  varchar(255) NOT NULL,
			   `brand_name`  varchar(80) DEFAULT NULL,
			   `model_name`  varchar(80) DEFAULT NULL,
			   `profile` varchar(255) 	DEFAULT NULL,
			   `created`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			   `device_os`  varchar(40) NOT NULL DEFAULT '',
			   `device_os_version`  varchar(20) NOT NULL DEFAULT '',
			   `mobile_browser`  varchar(40) NOT NULL DEFAULT '',
			   `mobile_browser_version`  varchar(20) NOT NULL DEFAULT '',
			   `language`  varchar(2) NOT NULL DEFAULT '',
			   `mobilebcp`  int(1) NOT NULL DEFAULT '0',
			   `display`  varchar(11) NOT NULL DEFAULT '',
			  PRIMARY KEY ( `id` )
			)");
		if(!$ret){
			echo "<br />Failed to execute SQL create table... :".mysql_error(); return false;
		}

		$succNumber=0;
		$failNumber=0;
		while($row=fgetcsv($fp,1024,',','"')){ 		
			$insert="insert into $table(`id`,`agent`,`brand_name`,`model_name`,`profile`,`created`,`device_os`,`device_os_version`,`mobile_browser`,`mobile_browser_version`,`language`,`mobilebcp`,`display`)values(";
			$insert.=(int)$row[0].",";
			$insert.="'".trim($row[1],"'")."',";
			$insert.='"'.($row[2]).'",';
			$insert.='"'.($row[3]).'",';
			$insert.="'".($row[4])."',";
			$insert.="'".($row[5])."',";
			$insert.="'".($row[6])."',";
			$insert.="'".($row[7])."',";
			$insert.="'".($row[8])."',";
			$insert.="'".($row[9])."',";
			$insert.="'".($row[10])."',";
			$insert.=((int)$row[11]).",";
			$insert.="'".($row[12])."'";
			$insert.=")";																									
			$ret=mysql_query($insert);
			if($ret){
				$succNumber+=1;
			}else{
				$failNumber+=1;
				echo "<br />";var_dump($row);echo "<br />$error<br /><br />";
			}
		}
		
		//close db connection
		mysql_close($link);
	
		@fclose($fp);
		return array('ok',"$succNumber inserted, $failNumber failed.");
	}
	****/
}
?>
