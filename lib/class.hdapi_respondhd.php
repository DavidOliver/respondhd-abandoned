<?php
class HandsetDetection_RespondHD extends HandsetDetection {
	var $software_token = '';
	
	function HandsetDetection() {
		
		//parent::__construct();
		//parent::HandsetDetection();
		
		// Set config based on Symphony Preferences
		$this->setEmail('');
		$this->setSecret('');
		$this->setSoftwareToken('');
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