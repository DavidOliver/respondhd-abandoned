<?php
class HandsetDetection_RespondHD extends HandsetDetection {
	var $software_token = '8dee56480a1cd3a11ec19b0ed81977b6';
		
	function __construct() {
	}
	
	function HandsetDetection() {
		
		$this->setEmail('');
		$this->setSecret('');
		$this->setMobileSite('');
		$this->setSiteId('');
		
		$this->setUseProxy('');
		$this->setProxyServer('');
		$this->setProxyPort('');
		$this->setProxyUser('');
		$this->setProxyPass('');
		
		$this->setLocalDetection('');
		$this->fastjson = function_exists('json_encode') && function_exists('json_decode');
	}
	
}
?>