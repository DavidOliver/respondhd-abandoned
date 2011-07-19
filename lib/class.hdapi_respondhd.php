<?php
class HandsetDetection_RespondHD extends HandsetDetection {
		
	function __construct() {
	}
	
	function HandsetDetection() {
		$this->setSoftwareToken('8dee56480a1cd3a11ec19b0ed81977b6');
		//$this->setSoftwareToken('8a4a663ba61f9ac9407894a370871bf3');
		
		$this->fastjson = function_exists('json_encode') && function_exists('json_decode');
	}
	
}
?>