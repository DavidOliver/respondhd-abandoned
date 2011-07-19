<?php
	
	//require_once EXTENSIONS . '/respondhd/lib/.php';
	
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
				),
				array(
					'page' => '/system/preferences/',
					'delegate' => 'Save',
					'callback' => '__SavePreferences'
				)
			);
		}
		
		/*public function __SavePreferences(){
			$settings = $_POST['settings'];
			
			$setting_group = 'respondhd';
			$setting_name = 'hdsecret';
			$setting_value = $settings['general']['hdsecret'];
			
			Symphony::Configuration()->set($setting_name, $setting_value, $setting_group);
			Administration::instance()->saveConfig();
		}*/
		
		public function appendPreferences($context) {
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Respond HD')));
			
			$label = Widget::Label(__('Handset Detection secret'));
			$label->appendChild(Widget::Input('settings[respondhd][hdsecret]', $hdsecret, 'text'));
			$group->appendChild($label);
			
			$context['wrapper']->appendChild($group);
		}
		
	}
	
?>