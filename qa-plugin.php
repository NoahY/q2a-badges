<?php

/*
        Plugin Name: Badges
        Plugin URI: 
        Plugin Description: Awards Badges on events
        Plugin Version: 0.4
        Plugin Date: 2011-07-30
        Plugin Author: NoahY
        Plugin Author URI: 
        Plugin License: GPLv2
        Plugin Minimum Question2Answer Version: 1.3
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
			header('Location: ../../');
			exit;
	}

// init functions

	//	Language support ala qa-base.php

	function qa_badge_lang($identifier) {
		$languagecode=qa_opt('site_language');
		
		list($group, $label)=explode('/', $identifier, 2);
		
		if (strlen($languagecode)) {
			global $qa_badge_lang_custom;
		
			if (!isset($qa_badge_lang_custom[$group])) { // only load each language file once
				
				$directory=QA_LANG_DIR.$languagecode.'/';
				
				$phrases=@include $directory.'qa-lang-'.$group.'.php'; // can tolerate missing file or directory

				$qa_badge_lang_custom[$group]=is_array($phrases) ? $phrases : array();
			}
			
			if (isset($qa_badge_lang_custom[$group][$label]))
				return $qa_badge_lang_custom[$group][$label];
		}
		
		global $qa_badge_lang_default;
		
		if (!isset($qa_badge_lang_default[$group])) // only load each default language file once
			$qa_badge_lang_default[$group]=require 'qa-lang-badges.php';
		
		if (isset($qa_badge_lang_default[$group][$label]))
			return $qa_badge_lang_default[$group][$label];
			
		return '['.$identifier.']'; // as a last resort, return the identifier to help in development
	}
	
// worker functions


	function qa_import_badge_list() {

		// import our list of badge types 
		
		qa_db_query_sub('DROP TABLE IF EXISTS ^badges');
		qa_db_query_sub(
				'CREATE TABLE ^badges ('.
						'badge_slug VARCHAR (64) CHARACTER SET ascii DEFAULT \'\','.
						'badge_type INT(10),'.
						'PRIMARY KEY (badge_slug)'.
				') ENGINE=MyISAM DEFAULT CHARSET=utf8'
		);		

		$badges = qa_get_badge_list();
		
		foreach ($badges as $slug => $info) {
			
				qa_db_query_sub(
					'INSERT INTO ^badges (badge_slug, badge_type) '.
					'VALUES ($, #)',
					$slug, $info['type']
				);
		}
	}
	
	function qa_get_badge_list() {
		
		// badges - add to this list to add a new badge, it will be imported when you run this function.  Don't change existing slugs!
		
		$badges = array();

		$badges['verified'] = array('type'=>0);

		$badges['asker'] = array('var'=>10, 'type'=>0);
		$badges['questioner'] = array('var'=>25, 'type'=>1);
		$badges['inquisitor'] = array('var'=>50, 'type'=>2);		

		$badges['answerer'] = array('var'=>25, 'type'=>0);
		$badges['lecturer'] = array('var'=>50, 'type'=>1);
		$badges['preacher'] = array('var'=>100, 'type'=>2);

		$badges['commenter'] = array('var'=>50, 'type'=>0);
		$badges['commentator'] = array('var'=>100, 'type'=>1);
		$badges['annotator'] = array('var'=>500, 'type'=>2);


		$badges['nice_question'] = array('var'=>2, 'type'=>0);
		$badges['good_question'] = array('var'=>5, 'type'=>1);
		$badges['great_question'] = array('var'=>10, 'type'=>2);

		$badges['notable_question'] = array('var'=>50, 'type'=>0);
		$badges['popular_question'] = array('var'=>100, 'type'=>1);
		$badges['famous_question'] = array('var'=>500, 'type'=>2);

		$badges['nice_answer'] = array('var'=>2, 'type'=>0);
		$badges['good_answer'] = array('var'=>5, 'type'=>1);
		$badges['great_answer'] = array('var'=>10, 'type'=>2);

		$badges['nice_answer_old'] = array('var'=>30, 'type'=>0);
		$badges['good_answer_old'] = array('var'=>60, 'type'=>1);
		$badges['great_answer_old'] = array('var'=>120, 'type'=>2);


		$badges['gifted'] = array('var'=>1, 'type'=>0);
		$badges['wise'] = array('var'=>10, 'type'=>1);
		$badges['enlightened'] = array('var'=>30, 'type'=>2);

		$badges['grateful'] = array('var'=>1, 'type'=>0);
		$badges['respectful'] = array('var'=>20, 'type'=>1);
		$badges['reverential'] = array('var'=>50, 'type'=>2);


		$badges['liked'] = array('var'=>20, 'type'=>0);
		$badges['loved'] = array('var'=>50, 'type'=>1);
		$badges['revered'] = array('var'=>200, 'type'=>2);

		$badges['voter'] = array('var'=>10, 'type'=>0);
		$badges['avid_voter'] = array('var'=>25, 'type'=>1);
		$badges['devoted_voter'] = array('var'=>50, 'type'=>2);

		$badges['editor'] = array('var'=>1, 'type'=>0);
		$badges['copy_editor'] = array('var'=>15, 'type'=>1);
		$badges['senior_editor'] = array('var'=>50, 'type'=>2);

		$badges['watchdog'] = array('var'=>1, 'type'=>0);
		$badges['bloodhound'] = array('var'=>10, 'type'=>1);
		$badges['pitbull'] = array('var'=>30, 'type'=>2);


		$badges['reader'] = array('var'=>20, 'type'=>0);
		$badges['avid_reader'] = array('var'=>50, 'type'=>1);
		$badges['devoted_reader'] = array('var'=>200, 'type'=>2);


		$badges['dedicated'] = array('var'=>10, 'type'=>0);
		$badges['devoted'] = array('var'=>25, 'type'=>1);
		$badges['zealous'] = array('var'=>50, 'type'=>2);

		$badges['visitor'] = array('var'=>30, 'type'=>0);
		$badges['trouper'] = array('var'=>100, 'type'=>1);
		$badges['veteran'] = array('var'=>200, 'type'=>2);

		$badges['regular'] = array('var'=>90, 'type'=>0);
		$badges['old_timer'] = array('var'=>180, 'type'=>1);
		$badges['ancestor'] = array('var'=>365, 'type'=>2);


		$badges['medalist'] = array('var'=>10, 'type'=>0);
		$badges['champion'] = array('var'=>30, 'type'=>1);
		$badges['olympian'] = array('var'=>100, 'type'=>2);


		return $badges;
	}
	
	function qa_get_badge_type($id) {
		
		// badge categories, e.g. bronze, silver, gold
		
		$badge_types = array();
		
		$badge_types[] = array('slug'=>'bronze','name'=>qa_badge_lang('badges/bronze'));
		$badge_types[] = array('slug'=>'silver','name'=>qa_badge_lang('badges/silver'));
		$badge_types[] = array('slug'=>'gold','name'=>qa_badge_lang('badges/gold'));
		
		$id = (int)$id;
		
		return $badge_types[$id];
		
	}
	
	function qa_badge_award_check($badges, $var, $uid, $oid = NULL, $notify = 1) {
		
		
		foreach($badges as $badge_slug) {
			
			if((int)$var >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
				if($oid) {
					$result = @qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$ AND object_id=#',
							$uid, $badge_slug, $oid
						),
						true
					);
				}
				else {
					$result = @qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
							$uid, $badge_slug
						),
						true
					);				
				}
				
				if ($result == null) { // not already awarded this badge
					qa_db_query_sub(
						'INSERT INTO ^userbadges (awarded_at, notify, object_id, user_id, badge_slug, id) '.
						'VALUES (NOW(), #, #, #, #, 0)',
						$notify, $oid, $uid, $badge_slug
					);
				}
			}
		}
	}
	
	function qa_badge_desc_replace($slug,$var) {
		
		// var replace
		
		$desc = str_replace('#',$var,qa_badge_lang('badges/'.$slug.'_desc'));
		
		// other badge reference replace
		
		preg_match_all('|\$(\S+)|',$desc,$others,PREG_SET_ORDER);
		
		if(!$others) return $desc;
		
		foreach($others as $other) {
			if(!qa_opt('badge_'.$other[1].'_name')) qa_opt('badge_'.$other[1].'_name',qa_badge_lang('badges/'.$other[1]));
			$name = qa_opt('badge_'.$other[1].'_name');

			$desc = str_replace($other[0],$name,$desc);
		}
		return $desc;
	}

	qa_register_plugin_module('event', 'qa-badge-check.php','badge_check','Badge Check');
	qa_register_plugin_layer('qa-badge-layer.php', 'Badge Notification Layer');	
	
	qa_register_plugin_module('module', 'qa-badge-admin.php', 'qa_badge_admin', qa_badge_lang('badges/badge_admin'));

	qa_register_plugin_module('page', 'qa-badge-page.php', 'qa_badge_page', qa_badge_lang('badges/badges'));
	
	// dev dump
	
	function qa_error_log($x) {
		ob_start();
		var_dump($x);
		$contents = ob_get_contents();
		ob_end_clean();
		error_log($contents);
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/
