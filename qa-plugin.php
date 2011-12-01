<?php

/*
        Plugin Name: Badges
        Plugin URI: 
        Plugin Description: Awards Badges on events
        Plugin Version: 1.2
        Plugin Date: 2011-07-30
        Plugin Author: NoahY
        Plugin Author URI: 
        Plugin License: GPLv2
        Plugin Minimum Question2Answer Version: 1.4
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
			header('Location: ../../');
			exit;
	}
	
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
		
		// check custom badges

		$moduletypes=qa_list_module_types();
		
		foreach ($moduletypes as $moduletype) {
			$modulenames=qa_list_modules($moduletype);
			
			foreach ($modulenames as $modulename) {
				$module=qa_load_module($moduletype, $modulename);
				
				if (method_exists($module, 'custom_badges') && method_exists($module, 'option_default')) {
					$name = $module->option_default($identifier);
					error_log($identifier);
					if($name)
						return $name;
				}
			}
		}
			
		return '['.$identifier.']'; // as a last resort, return the identifier to help in development
	}
	
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

		if(!QA_FINAL_EXTERNAL_USERS) {
			$badges['verified'] = array('type'=>0);
			$badges['profiler'] = array('type'=>0);
			$badges['avatar'] = array('type'=>0);
		}

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


		$badges['asker'] = array('var'=>10, 'type'=>0);
		$badges['questioner'] = array('var'=>25, 'type'=>1);
		$badges['inquisitor'] = array('var'=>50, 'type'=>2);		

		$badges['answerer'] = array('var'=>25, 'type'=>0);
		$badges['lecturer'] = array('var'=>50, 'type'=>1);
		$badges['preacher'] = array('var'=>100, 'type'=>2);

		$badges['commenter'] = array('var'=>50, 'type'=>0);
		$badges['commentator'] = array('var'=>100, 'type'=>1);
		$badges['annotator'] = array('var'=>500, 'type'=>2);


		$badges['voter'] = array('var'=>10, 'type'=>0);
		$badges['avid_voter'] = array('var'=>50, 'type'=>1);
		$badges['devoted_voter'] = array('var'=>200, 'type'=>2);

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


		$badges['100_club'] = array('var'=>100, 'type'=>0);
		$badges['1000_club'] = array('var'=>1000, 'type'=>1);
		$badges['10000_club'] = array('var'=>10000, 'type'=>2);

		$badges['medalist'] = array('var'=>10, 'type'=>0);
		$badges['champion'] = array('var'=>30, 'type'=>1);
		$badges['olympian'] = array('var'=>100, 'type'=>2);

		// get badges from other plugins - experimental!

		$moduletypes=qa_list_module_types();
		
		foreach ($moduletypes as $moduletype) {
			$modulenames=qa_list_modules($moduletype);
			
			foreach ($modulenames as $modulename) {
				$module=qa_load_module($moduletype, $modulename);
				
				if (method_exists($module, 'custom_badges'))
					$badges=array_merge($badges,$module->custom_badges());
			}
		}

		return $badges;
	}
	
	function qa_get_badge_type_by_slug($slug) {
		$badges = qa_get_badge_list();
		return qa_get_badge_type($badges[$slug]['type']);
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
	
	function qa_badge_award_check($badges, $var, $uid, $oid = NULL, $notify = 1) {  // oid is the postid (if), notify = 1 for email and popup, 2 for just popup.
		
		$awarded = array();
		foreach($badges as $badge_slug) {
			
			if(($var === false || (int)$var >= (int)qa_opt('badge_'.$badge_slug.'_var')) && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
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
					
					if(qa_opt('badge_email_notify') && $notify == 1) qa_badge_notification($uid, $oid, $badge_slug);
					
					if(qa_opt('event_logger_to_database') && $notify > 0 ) { // add event
						
						$handle = qa_getHandleFromId($uid);
						
						qa_db_query_sub(
							'INSERT INTO ^eventlog (datetime, ipaddress, userid, handle, cookieid, event, params) '.
							'VALUES (NOW(), $, $, $, #, $, $)',
							qa_remote_ip_address(), $uid, $handle, qa_cookie_get_create(), 'badge_awarded', 'badge_slug='.$badge_slug.($oid?"\t".'postid='.$oid:'')
						);
					}
					
					array_push($awarded,$badge_slug);
				}
			}
		}
		return $awarded;
	}
	
	function qa_badge_notification($uid, $oid, $badge_slug) {
		
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		require_once QA_INCLUDE_DIR.'qa-app-emails.php';
		
		if (QA_FINAL_EXTERNAL_USERS) {
			$publictohandle=qa_get_public_from_userids(array($uid));
			$handle=@$publictohandle[$uid];
			
		} 
		else {
			$user = qa_db_single_select(qa_db_user_account_selectspec($uid, true));
			$handle = @$user['handle'];
		}

		$subject = qa_opt('badge_email_subject');
		$body = qa_opt('badge_email_body');

		$body = preg_replace('/\^if_post_text="([^"]*)"/',($oid?'$1':''),$body); // if post text
		
		$site_url = qa_opt('site_url');
		$profile_url = qa_path_html('user/'.$handle, null, $site_url);
		


		if($oid) {
			$post = qa_db_read_one_assoc(
				qa_db_query_sub(
					'SELECT * FROM ^posts WHERE postid=#',
					$oid
				),
				true
			);
			if($post['parentid']) $parent = qa_db_read_one_assoc(
				qa_db_query_sub(
					'SELECT * FROM ^posts WHERE postid=#',
					$post['parentid']
				),
				true
			);
			if(isset($parent)) {
				$anchor = urlencode(qa_anchor($post['basetype'], $oid));

				$post_title = $parent['title'];
				$post_url = qa_path_html(qa_q_request($parent['postid'], $parent['title']), null, qa_opt('site_url'),null, $anchor);
			}
			else {
				$post_title = $post['title'];
				$post_url = qa_path_html(qa_q_request($post['postid'], $post['title']), null, qa_opt('site_url'));
			}

		}
		
		
		$subs = array(
			'^badge_name'=> qa_opt('badge_'.$badge_slug.'_name'),
			'^post_title'=> @$post_title,
			'^post_url'=> @$post_url,
			'^profile_url'=> $profile_url,
			'^site_url'=> $site_url,
		);
		
		qa_send_notification($uid, '@', $handle, $subject, $body, $subs);
	}
	
	function qa_badge_desc_replace($slug,$var=null) {
		
		$desc = qa_opt('badge_'.$slug.'_desc')?qa_opt('badge_'.$slug.'_desc'):qa_badge_lang('badges/'.$slug.'_desc');

		// var replace
		
		if($var) {
			$desc = str_replace('#',$var,$desc);
			$desc = preg_replace('/\^([^^]+)\^(\S+)/',($var == 1?"$1":"$2"),$desc);
		}
		
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
	
	if(!function_exists('qa_getHandleFromId')) {
		
		function qa_getHandleFromId($userid) {
			require_once QA_INCLUDE_DIR.'qa-app-users.php';
			
			if (QA_FINAL_EXTERNAL_USERS) {
				$publictohandle=qa_get_public_from_userids(array($userid));
				$handle=@$publictohandle[$userid];
				
			} 
			else {
				$user = qa_db_single_select(qa_db_user_account_selectspec($userid, true));
				$handle = @$user['handle'];
			}
			return $handle;
		}
	}

	qa_register_plugin_module('event', 'qa-badge-check.php','badge_check','Badge Check');

	qa_register_plugin_module('module', 'qa-badge-admin.php', 'qa_badge_admin', 'Badge Admin');

	qa_register_plugin_module('page', 'qa-badge-page.php', 'qa_badge_page', 'Badges');

	qa_register_plugin_module('widget', 'qa-badge-widget.php', 'qa_badge_widget', 'Recent Badge Widget');

	qa_register_plugin_layer('qa-badge-layer.php', 'Badge Notification Layer');	


/*
	Omit PHP closing tag to help avoid accidental output
*/
