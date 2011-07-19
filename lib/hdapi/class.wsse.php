<?php

/***********************************************************\
                 WSSE Authentication Class
                       version 1.1

               Written by Beau Lebens, 2004
                 beau@dentedreality.com.au
         http://www.dentedreality.com.au/phpatomapi/

      - Created to support an Atom API Implementation -

  More Info;
    PHP Atom API: http://www.dentedreality.com.au/phpatomapi/
    Atom API: http://www.xml.com/pub/a/2003/10/15/dive.html
    Atom Authentication: http://www.xml.com/pub/a/2003/12/17/dive.html
    WSSE Spec: http://www.oasis-open.org/committees/wss/documents/WSS-Username-02-0223-merged.pdf
    Date Format: http://www.w3.org/TR/NOTE-datetime
    
Copyright (c) 2005, Beau Lebens
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

 - Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 - Neither the name of Dented Reality nor the names of the author may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
    
\***********************************************************/

class WSSE {
	var $username  = '';
	var $password  = '';
	var $profile   = 'UsernameToken';
	var $timestamp = '';
	var $digest    = '';
	var $nonce     = '';
	
	/**
	* @return WSSE
	* @param String $username
	* @param String $password
	* @desc Creates a WSSE object which can give back a digest, header string etc
 	*/
	function WSSE($username, $password, $profile = false) {
		// If no profile, use default
		if ($profile !== false) {
			$this->profile = $profile;
		}
		
		// Set username and password
		$this->set_username($username);
		$this->set_password($password);
		
		// Calculate digest (requires nonce calculation)
		$this->rebuild();
		
		// return compiled object
		return $this;
	}
	
	/**
	* @return void
	* @param String $username
	* @desc Sets the username for this WSSE Object
 	*/
	function set_username($username) {
		$this->username = $username;
	}
	
	/**
	* @return void
	* @param String $password
	* @desc Sets the password for this WSSE Object
 	*/
	function set_password($password) {
		$this->password = $password;
	}
	
	/**
	* @return void
	* @param String $profile
	* @desc Sets the profile of the authentication method. Used in the header output. Defaults to UsernameToken.
 	*/
	function set_profile($profile) {
		$this->profile = $profile;
	}
	
	/**
	* @return void
	* @param String $digest
	* @desc Creates a digest of the password, using the nonce and timestamp
 	*/
	function set_digest($digest = false) {
		if (!$digest) {
			$digest = base64_encode(pack("H*", sha1($this->get_nonce()
									. $this->get_timestamp()
									. $this->get_password())));
		}
		$this->digest = $digest;
	}
	
	/**
	* @return void
	* @param String $nonce
	* @desc Makes an attempt at creating a randomised string for use as a nonce
 	*/
	function set_nonce($nonce = false) {
		if (!$nonce) {
			$mt = substr(microtime(), 2, 8);
			$md5 = md5($mt);
			$seed = $mt . rand(rand(100, 2000), 4000) . $md5;
			$nonce = md5($seed);
		}
		$this->nonce = $nonce;
	}
	
	/**
	* @return void
	* @param String $ts
	* @desc Creates a timestamp for the object, W3DTF Format
    */
	function set_timestamp($ts = false) {
		if (!$ts) {
			$this->timestamp = gmdate('Y-m-d\TH:i:s\Z');
		}
		else {
			$this->timestamp = $ts;
		}
	}
	

	/**
	* @return String/Array
	* @param Boolean $array TRUE to return an array, FALSE to return a string
	* @desc Returns a WSSE header for use in cURL or socket requests. If $array is TRUE, then returns an array suitable for cURL CUSTOMHEADER
	*/
	function get_header($array = false) {
		if ($array) {
			return array('Authorization' => 'WSSE profile="' . $this->get_profile() . '"',
						  'X-WSSE' => $this->get_profile() . ' Username="' . $this->get_username() . '", '
						. 'PasswordDigest="' . $this->digest . '", Nonce="' . base64_encode($this->get_nonce()) . '", '
						. 'Created="' . $this->get_timestamp() . '"');
		}
		else {
			return 'Authorization: WSSE profile="' . $this->get_profile() . '"' . "\r\n"
					. 'X-WSSE: ' . $this->get_profile() . ' Username="' . $this->get_username() . '", '
					. 'PasswordDigest="' . $this->digest . '", Nonce="' . base64_encode($this->get_nonce()) . '", '
					. 'Created="' . $this->get_timestamp() . '"' . "\r\n";
		}
	}
	
	/**
	* @return String
	* @desc Returns plain string containing the username
 	*/
	function get_username() {
		if (isset($this->username)) {
			return $this->username;
		}
		else {
			return false;
		}
	}
	
	/**
	* @return String
	* @desc Returns plain string containing the password
 	*/
	function get_password() {
		if (isset($this->password)) {
			return $this->password;
		}
		else {
			return false;
		}
	}
	
	/**
	* @return String
	* @desc Retrieve the nonce (or false if not set)
 	*/
	function get_nonce() {
		if ($this->nonce != '') {
			return $this->nonce;
		}
		else {
			return false;
		}
	}
	
	/**
	* @return String
	* @desc Retrieve the profile (or false if not set)
 	*/
	function get_profile() {
		if ($this->profile != '') {
			return $this->profile;
		}
		else {
			return false;
		}
	}
	
	/**
	* @return Timestamp
	* @desc Get the timestamp being used, or false if not set
 	*/
	function get_timestamp() {
		if ($this->timestamp != '') {
			return $this->timestamp;
		}
		else {
			return false;
		}
	}
	
	/**
	* @return String
	* @desc Retrieve the digest (or false if not set)
 	*/
	function get_digest() {
		if ($this->digest != '') {
			return $this->digest;
		}
		else {
			return false;
		}
	}
	
	/**
	* @return void
	* @desc Recreate dynamic components, effectively does everything
	*       required to rebuild the digest
 	*/
	function rebuild() {
		$this->set_timestamp();
		$this->set_nonce();
		$this->set_digest();
	}
}

?>
