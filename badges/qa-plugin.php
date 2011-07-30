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
		
		$exists = qa_db_read_one_value(qa_db_query_sub("SHOW TABLES LIKE '^badges'"),true);

		if(!$exists) {		

			// create tables

			qa_db_query_sub(
				'CREATE TABLE IF NOT EXISTS ^badges ('.
					'badge_id INT(11) NOT NULL AUTO_INCREMENT,'.
					'badge_slug VARCHAR (64) CHARACTER SET ascii DEFAULT \'\','.
					'badge_name VARCHAR (256) CHARACTER SET ascii DEFAULT \'\','.
					'PRIMARY KEY (badge_id)'.
				') ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
			
			qa_db_query_sub(
				'CREATE TABLE IF NOT EXISTS ^userbadges ('.
					'awarded_at DATETIME NOT NULL,'.
					'user_id INT(11) NOT NULL,'.
					'notify TINYINT DEFAULT 0 NOT NULL,'.
					'object_type VARCHAR(64) CHARACTER SET ascii NOT NULL DEFAULT \'\','.
					'object_id INT(10) NOT NULL,'.
					'badge_id INT(11) NOT NULL,'.
					'id INT(11) NOT NULL AUTO_INCREMENT,'.
					'PRIMARY KEY (id)'.
				') ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
			
		}
		qa_import_badge_list();
	}
	
	function qa_import_badge_list() {
		
		// import our list of badge types - add to this list to add a new badge type, it will be imported when you run this function.  Don't change existing slugs, unless you delete the old db entry!
		
		$badges['nice_question'] = 'Nice Question';
		
		foreach ($badges as $slug => $name) {

			// check if exists
			
			$result = qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT badge_id FROM ^badges WHERE badge_slug=$',
					$badge_slug
				),
				true
			);
			if (!$result) {
				qa_db_query_sub(
					'INSERT INTO ^badges (badge_id, badge_slug, badge_name) '.
					'VALUES (0, $, $)',
					$slug, $name
				);
			}
		}
	}
	
	qa_badges_init();

	qa_register_plugin_module('event', 'qa-badge-check.php','badge_check','Badge Check');
	qa_register_plugin_layer('qa-badge-layer.php', 'Badge Notification Layer');	

/*
	Omit PHP closing tag to help avoid accidental output
*/
