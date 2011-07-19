<?php
	
	require_once TOOLKIT . '/class.datasource.php';
	
	class DataSourceRespondHD extends Datasource {
		public function __construct($parent, $env = null, $process_params = true) {
			parent::__construct($parent, $env, $process_params);
			
			$this->_dependencies = array();
		}
		
		public function about() {
			return array(
				'name'			=> 'Respond Handset Detection',
				'version'		=> '0.1',
				'release-date'	=> '2011-07-18',
				'author'		=> array(
					'name'			=> 'David Oliver',
					'website'		=> 'http://doliver.co.uk',
					'email'			=> 'david@doliver.co.uk'
				)
			);
		}
		/*
		public function allowEditorToParse() {
			return false;
		}
		
		public function grab($param_pool) {
			$data = MobileDetector::detect();
			$result = new XMLElement('device');
			$result->setAttribute('is-mobile', 'no');
			
			if ($data->passed()) $result->setAttribute('is-mobile', 'yes');
			
			foreach ($data->devices() as $type => $values) {
				if (!$values->detected) continue;
				
				$item = new XMLElement($type);
				
				foreach ($values->captures as $name => $value) {
					$item->setAttribute($name, $value);
				}
				
				$result->appendChild($item);
			}
			
			return $result;
		}*/
	}
	
?>