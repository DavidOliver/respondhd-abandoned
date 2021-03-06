<?php
	
	require_once EXTENSIONS . '/respondhd/lib/hdapi/hdbase.php';
	require_once EXTENSIONS . '/respondhd/lib/class.hdapi_respondhd.php';
	
	class Extension_RespondHD extends Extension {
		
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
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendInitialised',
					'callback'	=> 'initialize'
				),
				array(
					'page'		=> '/system/preferences/',
					'delegate'	=> 'AddCustomPreferenceFieldsets',
					'callback'	=> 'appendPreferences'
				)
			);
		}
		
		public function appendPreferences($context) {
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Respond HD')));
			
			$label = Widget::Label(__('Handset Detection Email'));
			$label->appendChild(Widget::Input('settings[respondhd][hdemail]', $hdemail, 'text'));
			$group->appendChild($label);
			
			$label = Widget::Label(__('Handset Detection Secret'));
			$label->appendChild(Widget::Input('settings[respondhd][hdsecret]', $hdsecret, 'text'));
			$group->appendChild($label);
			
			$label = Widget::Label(__('Handset Detection Site ID'));
			$label->appendChild(Widget::Input('settings[respondhd][hdsiteid]', $hdsiteid, 'text'));
			$group->appendChild($label);
			
			$context['wrapper']->appendChild($group);
		}
		
		public function initialize() {
			
			//echo Symphony::Configuration()->get('hdemail', 'respondhd');
			//echo Symphony::Configuration()->get('hdsecret', 'respondhd');
			//echo Symphony::Configuration()->get('hdsiteid', 'respondhd');
			
			$hd = new HandsetDetection_RespondHD();
			$hd->setEmail(Symphony::Configuration()->get('hdemail', 'respondhd'));
			$hd->setSecret(Symphony::Configuration()->get('hdsecret', 'respondhd'));
			$hd->setSiteId(Symphony::Configuration()->get('hdsiteid', 'respondhd'));
			//$hd->setMobileSite(Symphony::Configuration()->get('mobilesite', 'respondhd'));
			$hd->setSoftwareToken('8dee56480a1cd3a11ec19b0ed81977b6');
			//$hd->setSoftwareToken('8a4a663ba61f9ac9407894a370871bf3');
			
			$hd->setup();
			
			$ret = $hd->detectAll('product_info, ajax, markup, display, rss');
			if ($ret) {
				$data = $hd->getDetect();
			}
			echo '<pre>' . print_r($data) . '</pre>';
			// $result
		}
		
	}
	
?>
