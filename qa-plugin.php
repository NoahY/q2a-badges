<?php

/*
        Plugin Name: Badges
        Plugin URI: 
        Plugin Description: Awards Badges on events
        Plugin Version: 0.1
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

    include('qa-lang-badges.php');
	
	function qa_badges_init() {
		
		$b_exists = qa_db_read_one_value(qa_db_query_sub("SHOW TABLES LIKE '^badges'"),true);

		if(!$b_exists) {		

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

		$u_exists = qa_db_read_one_value(qa_db_query_sub("SHOW TABLES LIKE '^userbadges'"),true);

		if(!$u_exists) {		
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
		

		$badges = qa_get_badge_list();
		
		foreach ($badges as $slug => $info) {

			// set default badge options

			if($info['var'] && !qa_opt('badge_'.$slug.'_var')) {
				qa_opt('badge_'.$slug.'_var',$info['var']);
			}

			// set custom badge names
			
			if(qa_opt('badge_'.$slug.'_name')) {
				$qa_lang_default['badges'][$slug] = qa_opt('badge_'.$slug.'_name');
			}

		}
		
		// set default settings
		
		if(!qa_opt('badge_notify_time')) qa_opt('badge_notify_time',0);
		
		
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

		$badges['verified'] = array('name'=>qa_lang('badges/verified'),'desc'=>qa_lang('badges/verified_desc'), 'type'=>0);
		
		$badges['nice_question'] = array('name'=>qa_lang('badges/nice_question'),'desc'=>qa_lang('badges/nice_question_desc'),'var'=>2, 'type'=>0);
		$badges['good_question'] = array('name'=>qa_lang('badges/good_question'),'desc'=>qa_lang('badges/good_question_desc'),'var'=>3, 'type'=>1);
		$badges['great_question'] = array('name'=>qa_lang('badges/great_question'),'desc'=>qa_lang('badges/great_question_desc'),'var'=>5, 'type'=>2);

		$badges['nice_answer'] = array('name'=>qa_lang('badges/nice_answer'),'desc'=>qa_lang('badges/nice_answer_desc'),'var'=>2, 'type'=>0);
		$badges['good_answer'] = array('name'=>qa_lang('badges/good_answer'),'desc'=>qa_lang('badges/good_answer_desc'),'var'=>3, 'type'=>1);
		$badges['great_answer'] = array('name'=>qa_lang('badges/great_answer'),'desc'=>qa_lang('badges/great_answer_desc'),'var'=>5, 'type'=>2);
		
		$badges['voter'] = array('name'=>qa_lang('badges/voter'),'desc'=>qa_lang('badges/voter_desc'),'var'=>10, 'type'=>0);
		$badges['avid_voter'] = array('name'=>qa_lang('badges/avid_voter'),'desc'=>qa_lang('badges/avid_voter_desc'),'var'=>25, 'type'=>1);
		$badges['devoted_voter'] = array('name'=>qa_lang('badges/devoted_voter'),'desc'=>qa_lang('badges/devoted_voter_desc'),'var'=>50, 'type'=>2);

		$badges['asker'] = array('name'=>qa_lang('badges/asker'),'desc'=>qa_lang('badges/asker_desc'),'var'=>10, 'type'=>0);
		$badges['questioner'] = array('name'=>qa_lang('badges/questioner'),'desc'=>qa_lang('badges/questioner_desc'),'var'=>25, 'type'=>1);
		$badges['inquisitor'] = array('name'=>qa_lang('badges/inquisitor'),'desc'=>qa_lang('badges/inquisitor_desc'),'var'=>50, 'type'=>2);

		$badges['answerer'] = array('name'=>qa_lang('badges/answerer'),'desc'=>qa_lang('badges/answerer_desc'),'var'=>10, 'type'=>0);
		$badges['lecturer'] = array('name'=>qa_lang('badges/lecturer'),'desc'=>qa_lang('badges/lecturer_desc'),'var'=>25, 'type'=>1);
		$badges['preacher'] = array('name'=>qa_lang('badges/preacher'),'desc'=>qa_lang('badges/preacher_desc'),'var'=>50, 'type'=>2);

		$badges['commenter'] = array('name'=>qa_lang('badges/commenter'),'desc'=>qa_lang('badges/commenter_desc'),'var'=>10, 'type'=>0);
		$badges['commentator'] = array('name'=>qa_lang('badges/commentator'),'desc'=>qa_lang('badges/commentator_desc'),'var'=>25, 'type'=>1);
		$badges['annotator'] = array('name'=>qa_lang('badges/annotator'),'desc'=>qa_lang('badges/annotator_desc'),'var'=>50, 'type'=>2);

		$badges['learner'] = array('name'=>qa_lang('badges/learner'),'desc'=>qa_lang('badges/learner_desc'),'var'=>1, 'type'=>0);
		$badges['student'] = array('name'=>qa_lang('badges/student'),'desc'=>qa_lang('badges/student_desc'),'var'=>5, 'type'=>1);
		$badges['scholar'] = array('name'=>qa_lang('badges/scholar'),'desc'=>qa_lang('badges/scholar_desc'),'var'=>15, 'type'=>2);

		return $badges;
	}
	
	function qa_get_badge_type($id) {
		
		// badge categories, e.g. bronze, silver, gold
		
		$badge_types = array();
		
		$badge_types[] = array('slug'=>'bronze','name'=>qa_lang('badges/bronze'));
		$badge_types[] = array('slug'=>'silver','name'=>qa_lang('badges/silver'));
		$badge_types[] = array('slug'=>'gold','name'=>qa_lang('badges/gold'));
		
		return $badge_types[$id];
		
	}
	
	qa_badges_init();
	
	if (qa_opt('badge_active')) {
		qa_register_plugin_module('event', 'qa-badge-check.php','badge_check','Badge Check');
		qa_register_plugin_layer('qa-badge-layer.php', 'Badge Notification Layer');	
	}
	
	qa_register_plugin_module('widget', 'qa-badge-admin.php', 'qa_badge_admin', qa_lang('badges/badge_admin'));

	qa_register_plugin_module('page', 'qa-badge-page.php', 'qa_badge_page', qa_lang('badges/badges'));


/*
	Omit PHP closing tag to help avoid accidental output
*/
