<?php

/*
        Plugin Name: Badges
        Plugin URI: 
        Plugin Description: Awards Badges on events
        Plugin Version: 0.2
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

	require_once QA_INCLUDE_DIR.'qa-app-users.php';

//	Language support

	function qa_badge_lang($identifier)
/*
	Return the translated string for $identifier, unless we're using external translation logic.
	This will retrieve the 'site_language' option so make sure you've already loaded/set that if
	loading an option now will cause a problem (see issue in qa_default_option()). The part of
	$identifier before the slash (/) replaces the * in the qa-lang-*.php file references, and the
	part after the / is the key of the array element to be taken from that file's returned result.
*/
	{
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
	
	function qa_badges_init() {
	
		$badges_exists = qa_db_read_one_value(qa_db_query_sub("SHOW TABLES LIKE '^badges'"),true);

		if(!$badges_exists) {		

			// create tables

			qa_db_query_sub(
				'CREATE TABLE ^badges ('.
					'badge_slug VARCHAR (64) CHARACTER SET ascii DEFAULT \'\','.
					'badge_name VARCHAR (256) CHARACTER SET ascii DEFAULT \'\','.
					'PRIMARY KEY (badge_slug)'.
				') ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
			qa_import_badge_list();
		}

		$userbadges_exists = qa_db_read_one_value(qa_db_query_sub("SHOW TABLES LIKE '^userbadges'"),true);

		if(!$userbadges_exists) {		
			qa_db_query_sub(
				'CREATE TABLE ^userbadges ('.
					'awarded_at DATETIME NOT NULL,'.
					'user_id INT(11) NOT NULL,'.
					'notify TINYINT DEFAULT 0 NOT NULL,'.
					'object_id INT(10),'.
					'badge_slug VARCHAR (64) CHARACTER SET ascii DEFAULT \'\','.
					'id INT(11) NOT NULL AUTO_INCREMENT,'.
					'PRIMARY KEY (id)'.
				') ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
			
		}
		
		$achievements_exists = qa_db_read_one_value(qa_db_query_sub("SHOW TABLES LIKE '^achievements'"),true);

		if(!$achievements_exists) {		
			qa_db_query_sub(
				'CREATE TABLE ^achievements ('.
					'user_id INT(11) UNIQUE NOT NULL,'.
					'first_visit DATETIME,'.
					'oldest_consec_visit DATETIME,'.
					'last_visit DATETIME,'.
					'total_days_visited INT(10),'.
					'questions_read INT(10),'.
					'posts_edited INT(10)'.
				') ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
			
		}
		
		$badges = qa_get_badge_list();
		
		foreach ($badges as $slug => $info) {

			// set default badge options

			if($info['var'] && !qa_opt('badge_'.$slug.'_var')) {
				qa_opt('badge_'.$slug.'_var',$info['var']);
			}

			// set custom badge names
			
			if(qa_opt('badge_'.$slug.'_name')) {
				$qa_badge_lang_default['badges'][$slug] = qa_opt('badge_'.$slug.'_name');
			}

		}
		
		// set default settings
		
		if(!qa_opt('badge_notify_time')) qa_opt('badge_notify_time',0);
		
	
	// process per visit events 

		$userid = qa_get_logged_in_userid();
		if(!$userid) return; // not logged in?  die.
		
		// first visit check
		
		$user = qa_db_read_one_value(
			qa_db_query_sub(
				'SELECT user_id FROM ^achievements WHERE user_id=# ',
				$userid
			),
			true
		);


		if(!$user) {
			qa_db_query_sub(
				'INSERT INTO ^achievements (user_id, first_visit, oldest_consec_visit, last_visit, total_days_visited, questions_read, posts_edited) VALUES (#, NOW(), NOW(), NOW(), #, #, #)',
				$userid, 1, 0, 0
			);
			return;
		}

		// check lapse since last visit
		
		$result = qa_db_read_one_value(
			qa_db_query_sub(
				'SELECT DATEDIFF(NOW(),(SELECT last_visit FROM ^achievements WHERE user_id=#))',
				$userid
			),
			true
		);

		if((int)$result < 2) { // one day or less, update last visit
			qa_db_query_sub(
				'UPDATE ^achievements SET last_visit=NOW() WHERE user_id=#',
				$userid
			);		
			qa_badge_check_consec_days($userid);
		}
		else { // 2+ days, reset consecutive days due to lapse
			qa_db_query_sub(
				'UPDATE ^achievements SET oldest_consec_visit=NOW() WHERE user_id=#',
				$userid
			);		
		}
	}
	
	function qa_import_badge_list() {
		qa_db_query_sub(
			'TRUNCATE TABLE ^badges'
		);
		
		// import our list of badge types 

		$badges = qa_get_badge_list();
		
		foreach ($badges as $slug => $info) {
			
			// check if exists
			
			$result = qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT badge_slug FROM ^badges WHERE badge_slug=$',
					$slug
				),
				true
			);
			if (!$result) {
				qa_db_query_sub(
					'INSERT INTO ^badges (badge_slug, badge_name) '.
					'VALUES ($, $)',
					$slug, $info['name']
				);
			}
		}
	}
	
	function qa_get_badge_list() {
		
		// badges - add to this list to add a new badge, it will be imported when you run this function.  Don't change existing slugs!
		
		$badges = array();

		$badges['verified'] = array('name'=>qa_badge_lang('badges/verified'),'desc'=>qa_badge_lang('badges/verified_desc'), 'type'=>0);
		
		$badges['nice_question'] = array('name'=>qa_badge_lang('badges/nice_question'),'desc'=>qa_badge_lang('badges/nice_question_desc'),'var'=>2, 'type'=>0);
		$badges['good_question'] = array('name'=>qa_badge_lang('badges/good_question'),'desc'=>qa_badge_lang('badges/good_question_desc'),'var'=>3, 'type'=>1);
		$badges['great_question'] = array('name'=>qa_badge_lang('badges/great_question'),'desc'=>qa_badge_lang('badges/great_question_desc'),'var'=>5, 'type'=>2);

		$badges['nice_answer'] = array('name'=>qa_badge_lang('badges/nice_answer'),'desc'=>qa_badge_lang('badges/nice_answer_desc'),'var'=>2, 'type'=>0);
		$badges['good_answer'] = array('name'=>qa_badge_lang('badges/good_answer'),'desc'=>qa_badge_lang('badges/good_answer_desc'),'var'=>3, 'type'=>1);
		$badges['great_answer'] = array('name'=>qa_badge_lang('badges/great_answer'),'desc'=>qa_badge_lang('badges/great_answer_desc'),'var'=>5, 'type'=>2);
		
		$badges['voter'] = array('name'=>qa_badge_lang('badges/voter'),'desc'=>qa_badge_lang('badges/voter_desc'),'var'=>10, 'type'=>0);
		$badges['avid_voter'] = array('name'=>qa_badge_lang('badges/avid_voter'),'desc'=>qa_badge_lang('badges/avid_voter_desc'),'var'=>25, 'type'=>1);
		$badges['devoted_voter'] = array('name'=>qa_badge_lang('badges/devoted_voter'),'desc'=>qa_badge_lang('badges/devoted_voter_desc'),'var'=>50, 'type'=>2);

		$badges['asker'] = array('name'=>qa_badge_lang('badges/asker'),'desc'=>qa_badge_lang('badges/asker_desc'),'var'=>10, 'type'=>0);
		$badges['questioner'] = array('name'=>qa_badge_lang('badges/questioner'),'desc'=>qa_badge_lang('badges/questioner_desc'),'var'=>25, 'type'=>1);
		$badges['inquisitor'] = array('name'=>qa_badge_lang('badges/inquisitor'),'desc'=>qa_badge_lang('badges/inquisitor_desc'),'var'=>50, 'type'=>2);

		$badges['answerer'] = array('name'=>qa_badge_lang('badges/answerer'),'desc'=>qa_badge_lang('badges/answerer_desc'),'var'=>10, 'type'=>0);
		$badges['lecturer'] = array('name'=>qa_badge_lang('badges/lecturer'),'desc'=>qa_badge_lang('badges/lecturer_desc'),'var'=>25, 'type'=>1);
		$badges['preacher'] = array('name'=>qa_badge_lang('badges/preacher'),'desc'=>qa_badge_lang('badges/preacher_desc'),'var'=>50, 'type'=>2);

		$badges['commenter'] = array('name'=>qa_badge_lang('badges/commenter'),'desc'=>qa_badge_lang('badges/commenter_desc'),'var'=>10, 'type'=>0);
		$badges['commentator'] = array('name'=>qa_badge_lang('badges/commentator'),'desc'=>qa_badge_lang('badges/commentator_desc'),'var'=>25, 'type'=>1);
		$badges['annotator'] = array('name'=>qa_badge_lang('badges/annotator'),'desc'=>qa_badge_lang('badges/annotator_desc'),'var'=>50, 'type'=>2);

		$badges['learner'] = array('name'=>qa_badge_lang('badges/learner'),'desc'=>qa_badge_lang('badges/learner_desc'),'var'=>1, 'type'=>0);
		$badges['student'] = array('name'=>qa_badge_lang('badges/student'),'desc'=>qa_badge_lang('badges/student_desc'),'var'=>5, 'type'=>1);
		$badges['scholar'] = array('name'=>qa_badge_lang('badges/scholar'),'desc'=>qa_badge_lang('badges/scholar_desc'),'var'=>15, 'type'=>2);

		$badges['watchdog'] = array('name'=>qa_badge_lang('badges/watchdog'),'desc'=>qa_badge_lang('badges/watchdog_desc'),'var'=>1, 'type'=>0);
		$badges['bloodhound'] = array('name'=>qa_badge_lang('badges/bloodhound'),'desc'=>qa_badge_lang('badges/bloodhound_desc'),'var'=>5, 'type'=>1);
		$badges['pitbull'] = array('name'=>qa_badge_lang('badges/pitbull'),'desc'=>qa_badge_lang('badges/pitbull_desc'),'var'=>15, 'type'=>2);

		$badges['dedicated'] = array('name'=>qa_badge_lang('badges/dedicated'),'desc'=>qa_badge_lang('badges/dedicated_desc'),'var'=>10, 'type'=>0);
		$badges['devoted'] = array('name'=>qa_badge_lang('badges/devoted'),'desc'=>qa_badge_lang('badges/devoted_desc'),'var'=>25, 'type'=>1);
		$badges['zealous'] = array('name'=>qa_badge_lang('badges/zealous'),'desc'=>qa_badge_lang('badges/zealous_desc'),'var'=>50, 'type'=>2);

		$badges['gifted'] = array('name'=>qa_badge_lang('badges/gifted'),'desc'=>qa_badge_lang('badges/gifted_desc'),'var'=>5, 'type'=>0);
		$badges['wise'] = array('name'=>qa_badge_lang('badges/wise'),'desc'=>qa_badge_lang('badges/wise_desc'),'var'=>10, 'type'=>1);
		$badges['enlightened'] = array('name'=>qa_badge_lang('badges/enlightened'),'desc'=>qa_badge_lang('badges/enlightened_desc'),'var'=>20, 'type'=>2);

		$badges['grateful'] = array('name'=>qa_badge_lang('badges/grateful'),'desc'=>qa_badge_lang('badges/grateful_desc'),'var'=>1, 'type'=>0);
		$badges['respectful'] = array('name'=>qa_badge_lang('badges/respectful'),'desc'=>qa_badge_lang('badges/respectful_desc'),'var'=>8, 'type'=>1);
		$badges['reverential'] = array('name'=>qa_badge_lang('badges/reverential'),'desc'=>qa_badge_lang('badges/reverential_desc'),'var'=>20, 'type'=>2);

		$badges['medalist'] = array('name'=>qa_badge_lang('badges/medalist'),'desc'=>qa_badge_lang('badges/medalist_desc'),'var'=>10, 'type'=>0);
		$badges['champion'] = array('name'=>qa_badge_lang('badges/champion'),'desc'=>qa_badge_lang('badges/champion_desc'),'var'=>25, 'type'=>1);
		$badges['olympian'] = array('name'=>qa_badge_lang('badges/olympian'),'desc'=>qa_badge_lang('badges/olympian_desc'),'var'=>50, 'type'=>2);

		$badges['editor'] = array('name'=>qa_badge_lang('badges/editor'),'desc'=>qa_badge_lang('badges/editor_desc'),'var'=>1, 'type'=>0);
		$badges['copy_editor'] = array('name'=>qa_badge_lang('badges/copy_editor'),'desc'=>qa_badge_lang('badges/copy_editor_desc'),'var'=>15, 'type'=>1);
		$badges['senior_editor'] = array('name'=>qa_badge_lang('badges/senior_editor'),'desc'=>qa_badge_lang('badges/senior_editor_desc'),'var'=>50, 'type'=>2);

		return $badges;
	}
	
	function qa_get_badge_type($id) {
		
		// badge categories, e.g. bronze, silver, gold
		
		$badge_types = array();
		
		$badge_types[] = array('slug'=>'bronze','name'=>qa_badge_lang('badges/bronze'));
		$badge_types[] = array('slug'=>'silver','name'=>qa_badge_lang('badges/silver'));
		$badge_types[] = array('slug'=>'gold','name'=>qa_badge_lang('badges/gold'));
		
		return $badge_types[$id];
		
	}
	
	function qa_badge_check_consec_days($userid) {
		$days = qa_db_read_one_value(
			qa_db_query_sub(
				'SELECT DATEDIFF(NOW(),(SELECT oldest_consec_visit FROM ^achievements WHERE user_id=#))',
				$userid
			),
			true
		);

		$badges = array('dedicated','devoted','zealous');

		foreach($badges as $badge_slug) {
		
			if((int)$days >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					qa_db_query_sub(
						'INSERT INTO ^userbadges (awarded_at, notify, object_id, user_id, badge_slug, id) '.
						'VALUES (NOW(), 1, #, #, #, 0)',
						null, $userid, $badge_slug
					);
				}
			}
		}
	}
	
	qa_badges_init();
	
	if (qa_opt('badge_active')) {
		qa_register_plugin_module('event', 'qa-badge-check.php','badge_check','Badge Check');
		qa_register_plugin_layer('qa-badge-layer.php', 'Badge Notification Layer');	
	}
	
	qa_register_plugin_module('widget', 'qa-badge-admin.php', 'qa_badge_admin', qa_badge_lang('badges/badge_admin'));

	qa_register_plugin_module('page', 'qa-badge-page.php', 'qa_badge_page', qa_badge_lang('badges/badges'));


/*
	Omit PHP closing tag to help avoid accidental output
*/
