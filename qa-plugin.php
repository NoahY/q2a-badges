<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/event-logger/qa-plugin.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Initiates event logger plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

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
		
		// set default badge options

		$badges = qa_get_badge_list();
		
		foreach ($badges as $slug => $info) {
			if($info['var'] && !qa_opt('badge_'.$slug.'_var')) {
				qa_opt('badge_'.$slug.'_var',$info['var']);
			}
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
		
		$badges['nice_question'] = array('name'=>'Nice Question','desc'=>'Question received +# upvote', 'var'=>2, 'type'=>0);
		$badges['good_question'] = array('name'=>'Good Question','desc'=>'Question received +# upvote', 'var'=>3, 'type'=>1);
		$badges['great_question'] = array('name'=>'Great Question','desc'=>'Question received +# upvote', 'var'=>5, 'type'=>2);

		$badges['nice_answer'] = array('name'=>'Nice Answer','desc'=>'Answer received +# upvote', 'var'=>2, 'type'=>0);
		$badges['good_answer'] = array('name'=>'Good Answer','desc'=>'Answer received +# upvote', 'var'=>3, 'type'=>1);
		$badges['great_answer'] = array('name'=>'Great Answer','desc'=>'Answer received +# upvote', 'var'=>5, 'type'=>2);
		
		$badges['verified'] = array('name'=>'Verified Human','desc'=>'Successfully verified email address', 'type'=>0);
		
		$badges['voter'] = array('name'=>'Voter','desc'=>'Voted # times', 'var'=>10, 'type'=>0);
		$badges['avid_voter'] = array('name'=>'Avid Voter','desc'=>'Voted # times', 'var'=>25, 'type'=>1);
		$badges['devoted_voter'] = array('name'=>'Devoted Voter','desc'=>'Voted # times', 'var'=>50, 'type'=>2);

		$badges['asker'] = array('name'=>'Asker','desc'=>'Asked # questions', 'var'=>10, 'type'=>0);
		$badges['questioner'] = array('name'=>'Questioner','desc'=>'Asked # questions', 'var'=>25, 'type'=>1);
		$badges['inquisitor'] = array('name'=>'Inquisitor','desc'=>'Asked # questions', 'var'=>50, 'type'=>2);

		$badges['answerer'] = array('name'=>'Answerer','desc'=>'Posted # answers', 'var'=>10, 'type'=>0);
		$badges['lecturer'] = array('name'=>'Lecturer','desc'=>'Posted # answers', 'var'=>25, 'type'=>1);
		$badges['preacher'] = array('name'=>'Preacher','desc'=>'Posted # answers', 'var'=>50, 'type'=>2);

		$badges['commenter'] = array('name'=>'Commenter','desc'=>'Posted # comments', 'var'=>10, 'type'=>0);
		$badges['commentator'] = array('name'=>'Commentator','desc'=>'Posted # comments', 'var'=>25, 'type'=>1);
		$badges['annotator'] = array('name'=>'Annotator','desc'=>'Posted # comments', 'var'=>50, 'type'=>2);

		$badges['learner'] = array('name'=>'Learner','desc'=>'Accepted answers to # questions', 'var'=>1, 'type'=>0);
		$badges['student'] = array('name'=>'Student','desc'=>'Accepted answers to # questions', 'var'=>5, 'type'=>1);
		$badges['scholar'] = array('name'=>'Scholar','desc'=>'Accepted answers to # questions', 'var'=>15, 'type'=>2);

		return $badges;
	}
	
	function qa_get_badge_type($id) {
		
		// badge categories, e.g. bronze, silver, gold
		
		$badge_types = array();
		
		$badge_types[] = array('slug'=>'bronze','name'=>'Bronze');
		$badge_types[] = array('slug'=>'silver','name'=>'Silver');
		$badge_types[] = array('slug'=>'gold','name'=>'Gold');
		
		return $badge_types[$id];
		
	}
	
	qa_badges_init();
	
	if (qa_opt('badge_active')) {
		qa_register_plugin_module('event', 'qa-badge-check.php','badge_check','Badge Check');
		qa_register_plugin_layer('qa-badge-layer.php', 'Badge Notification Layer');	
	}
	
	qa_register_plugin_module('widget', 'qa-badge-admin.php', 'qa_badge_admin', 'Badge Admin');

	qa_register_plugin_module('page', 'qa-badge-page.php', 'qa_badge_page', 'Badges');


/*
	Omit PHP closing tag to help avoid accidental output
*/
