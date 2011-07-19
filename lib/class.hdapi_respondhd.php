<?php
class HandsetDetection_RespondHD extends HandsetDetection {
	var $software_token = '';
		
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