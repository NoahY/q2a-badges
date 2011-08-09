<?php
	class qa_badge_admin {
		
		function allow_template($template)
		{
			return ($template!='admin');
		}

		function option_default($option) {
			
			$badges = qa_get_badge_list();

			$slug = preg_replace('/badge_(.*)_.+/',"$1",$option);
			
			switch($option) {
				case 'badge_'.$slug.'_name':
					return $badges[$slug]['name'];
				case 'badge_'.$slug.'_var':
					return $badges[$slug]['var'];
				case 'badge_'.$slug.'_enabled':
					return '0';
				case 'badge_notify_time':
					return 0;
				case 'badge_admin_user_field':
					return false;
				case 'badge_admin_user_widget':
					return false;
				case 'badge_active':
					return false;
			}
			
		}

		function admin_form(&$qa_content)
		{

		//	Process form input

			$ok = null;

			$badges = qa_get_badge_list();
			if (qa_clicked('badge_rebuild_button')) {
				qa_import_badge_list();
				$ok = qa_badge_lang('badges/badge_list_rebuilt');
			}
			else if (qa_clicked('badge_award_button')) {
				if((bool)qa_post_text('badge_award_delete')) {
					qa_db_query_sub(
						'DROP TABLE ^userbadges'
					);
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
				$ok = $this->qa_check_all_users_badges();
			}
			else if (qa_clicked('badge_reset_names')) {
				foreach ($badges as $slug => $info) {
					qa_opt('badge_'.$slug.'_name',qa_badge_lang('badges/'.$slug));
				}
				$ok = qa_badge_lang('badges/badge_names_reset');
			}
			else if (qa_clicked('badge_reset_values')) {
				foreach ($badges as $slug => $info) {
					if(isset($info['var'])) {
						qa_opt('badge_'.$slug.'_var',$info['var']);
					}
				}
				$ok = qa_badge_lang('badges/badge_values_reset');
			}
			else if (qa_clicked('badge_trigger_notify')) {
				$qa_content['test-notify'] = 1;
			}
			else if(qa_clicked('badge_save_settings')) {
				$was_active = qa_opt('badge_active');
				qa_opt('badge_active', (bool)qa_post_text('badge_active_check'));			
				if (qa_opt('badge_active')) {
					error_log($was_active.' '.qa_opt('badge_active'));
					// check databases
					
					$badges_exists = qa_db_read_one_value(qa_db_query_sub("SHOW TABLES LIKE '^badges'"),true);

					if(!$badges_exists) {		

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
								'longest_consec_visit INT(10),'.
								'last_visit DATETIME,'.
								'total_days_visited INT(10),'.
								'questions_read INT(10),'.
								'posts_edited INT(10)'.
							') ENGINE=MyISAM DEFAULT CHARSET=utf8'
						);
						
					}
					
					if($was_active) {
						// set badge names, vars and states
						
						foreach ($badges as $slug => $info) {
							
							// update var
							
							if(isset($info['var']) && qa_post_text('badge_'.$slug.'_var')) {
								qa_opt('badge_'.$slug.'_var',qa_post_text('badge_'.$slug.'_var'));
							}

							// toggle activation

							if((bool)qa_post_text('badge_'.$slug.'_enabled') === false) {
								qa_opt('badge_'.$slug.'_enabled','0');
							}
							else qa_opt('badge_'.$slug.'_enabled','1');

							// set custom names
							
							if (qa_post_text('badge_'.$slug.'_edit') != qa_opt('badge_'.$slug.'_name')) {
								qa_opt('badge_'.$slug.'_name',qa_post_text('badge_'.$slug.'_edit'));
								$qa_badge_lang_default['badges'][$slug] = qa_opt('badge_'.$slug.'_name');
							}
							
						}
						
						// options
						
						qa_opt('badge_notify_time', (int)qa_post_text('badge_notify_time'));			
						qa_opt('badge_admin_user_widget',(bool)qa_post_text('badge_admin_user_widget'));
						qa_opt('badge_admin_user_field',(bool)qa_post_text('badge_admin_user_field'));
					}
				}
				$ok = qa_badge_lang('badges/badge_admin_saved');
			}
			
		//	Create the form for display
			
			
			$fields = array();
			
			$fields[] = array(
				'label' => qa_badge_lang('badges/badge_admin_activate'),
				'tags' => 'NAME="badge_active_check"',
				'value' => qa_opt('badge_active'),
				'type' => 'checkbox',
			);

			if(qa_opt('badge_active')) {

				$fields[] = array(
						'label' => qa_badge_lang('badges/active_badges').':',
						'type' => 'static',
				);
				
				$fields[] = array(
					'label' => qa_badge_lang('badges/badge_admin_select_all'),
					'tags' => 'onclick="var isx = this.checked; jQuery(\'.badge-listing :checkbox\').prop(\'checked\',isx);"',
					'value' => false,
					'type' => 'checkbox',
				);	

				foreach ($badges as $slug => $info) {
					$badge_name=qa_badge_lang('badges/'.$slug);
					$badge_desc=qa_badge_lang('badges/'.$slug.'_desc');
					if(!qa_opt('badge_'.$slug.'_name')) qa_opt('badge_'.$slug.'_name',$badge_name);
					$name = qa_opt('badge_'.$slug.'_name');
					
					$type = qa_get_badge_type($info['type']);
					$types = $type['slug'];
					
					if(isset($info['var'])) $badge_desc = str_replace('#','<input type="text" name="badge_'.$slug.'_var" size="4" value="'.qa_opt('badge_'.$slug.'_var').'">',$badge_desc);

					$fields[] = array(
							'type' => 'static',
							'note' => '<table class="badge-listing"><tr><td><input type="checkbox" name="badge_'.$slug.'_enabled"'.(qa_opt('badge_'.$slug.'_enabled') !== '0' ? ' checked':'').'></td><td><input type="text" name="badge_'.$slug.'_edit" id="badge_'.$slug.'_edit" style="display:none" size="16" onblur="badgeEdit(\''.$slug.'\',true)" value="'.$name.'"><span id="badge_'.$slug.'_badge" class="badge-'.$types.'" onclick="badgeEdit(\''.$slug.'\')" title="'.qa_badge_lang('badges/badge_admin_click_edit').'">'.$name.'</span></td><td>'.$badge_desc.'</td></tr></table>'
					);
				}
				$fields[] = array(
						'label' => '<hr/>'.qa_badge_lang('badges/notify_time').':',
						'type' => 'number',
						'value' => qa_opt('badge_notify_time'),
						'tags' => 'NAME="badge_notify_time"',
						'note' => '<em>'.qa_badge_lang('badges/notify_time_desc').'</em><hr/>',
				);
				$fields[] = array(
					'label' => qa_badge_lang('badges/badge_admin_user_field'),
					'tags' => 'NAME="badge_admin_user_field"',
					'value' => (bool)qa_opt('badge_admin_user_field'),
					'type' => 'checkbox',
				);				
				$fields[] = array(
					'label' => qa_badge_lang('badges/badge_admin_user_widget'),
					'tags' => 'NAME="badge_admin_user_widget"',
					'value' => (bool)qa_opt('badge_admin_user_widget'),
					'type' => 'checkbox',
					'note' => '<hr/>',
				);				
				if (qa_clicked('badge_trigger_notify')) {
					$fields['test-notify'] = 1;
				}
			}
			
			return array(
				'ok' => ($ok && !isset($error)) ? $ok : null,
				
				'fields' => $fields,
				
				'buttons' => array(
					array(
						'label' => qa_badge_lang('badges/badge_trigger_notify'),
						'tags' => 'name="badge_trigger_notify"'.(qa_opt('badge_active')?'':' disabled="true"'),
						'note' => '<br/><em>'.qa_badge_lang('badges/badge_trigger_notify_desc').'</em><br/>',
					),
					array(
						'label' => qa_badge_lang('badges/badge_reset_names'),
						'tags' => 'NAME="badge_reset_names"',
						'note' => '<br/><em>'.qa_badge_lang('badges/badge_reset_names_desc').'</em><br/>',
					),
					array(
						'label' => qa_badge_lang('badges/badge_reset_values'),
						'tags' => 'NAME="badge_reset_values"',
						'note' => '<br/><em>'.qa_badge_lang('badges/badge_reset_values_desc').'</em><br/>',
					),					
					array(
						'label' => qa_badge_lang('badges/badge_recreate'),
						'tags' => 'NAME="badge_rebuild_button"',
						'note' => '<br/><em>'.qa_badge_lang('badges/badge_recreate_desc').'</em><br/>',
					),
					array(
						'label' => qa_badge_lang('badges/badge_award_button'),
						'tags' => 'NAME="badge_award_button"',
						'note' => '<br/><em>'.qa_badge_lang('badges/badge_award_button_desc').'</em><br/><input type="checkbox" name="badge_award_delete"><b>'.qa_badge_lang('badges/badge_award_delete_desc').'</b><br/>',
					),
					array(
						'label' => qa_badge_lang('badges/save_settings'),
						'tags' => 'NAME="badge_save_settings"',
						'note' => '<br/><em>'.qa_badge_lang('badges/save_settings_desc').'</em><br/>',
					),
				),
			);
		}
		
// imported user badge checking functions

		function award_badge($object_id, $user_id, $badge_slug) {
			
			// add badge to userbadges
			
			qa_db_query_sub(
				'INSERT INTO ^userbadges (awarded_at, notify, object_id, user_id, badge_slug, id) '.
				'VALUES (NOW(), #, #, #, #, 0)',
				0, $object_id, $user_id, $badge_slug
			);
			
		}
		function get_post_data($id) {
			$result = qa_db_read_one_assoc(
				qa_db_query_sub(
					'SELECT * FROM ^posts WHERE postid=#',
					$id
				),
				true
			);
			return $result;
		}
		
	// post check

		function qa_check_all_users_badges() {

			$awarded = 0;
			$users;
		
			$post_result = qa_db_read_all_assoc(
				qa_db_query_sub(
					'SELECT * FROM ^posts'
				)
			);
			
			foreach ($post_result as $post) {
				if(!$post['userid']) continue;
				$user='user'.$post['userid'];
				$pid = $post['postid'];
				$pt = $post['type'];
				
				// get post count
				
				if(isset($users[$user]) && isset($users[$user][$pt])) $users[$user][$pt]++;
				else $users[$user][$pt] = 1;
				
				// get post votes
				
				if($post['netvotes'] !=0) $users[$user][$pt.'votes'][] = array(
					'id'=>$pid,
					'votes'=>(int)$post['netvotes'],
					'parentid'=>$post['parentid'],
					'created'=>$post['created']
				);

				// get post views
				
				if($post['views']) $users[$user]['views'][] = array(
					'id'=>$pid,
					'views'=>$post['views']
				);
				qa_error_log($users);
				 
			} 


			$vote_result = qa_db_read_all_assoc(
				qa_db_query_sub(
					'SELECT * FROM ^uservotes WHERE vote <> 0'
				)
			);
			foreach ($vote_result as $vote) {
				$user='user'.$vote['userid'];
				$pid = $vote['postid'];
				
				// get vote count
				
				if(isset($users[$user]) && isset($users[$user]['votes'])) $users[$user]['votes']++;
				else $users[$user]['votes'] = 1;
			} 

			// flags

			$flag_result = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT userid FROM ^uservotes WHERE flag > 0'
				)
			);

			foreach ($flag_result as $flag) {
				$user='user'.$flag;
				
				// get flag count
				
				if(isset($users[$user]) && isset($users[$user]['flags'])) $users[$user]['flags']++;
				else $users[$user]['flags'] = 1;
			}
			
			foreach ($users as $user => $data) {
				$uid = (int)substr($user,4);
				
				// bulk posts
				
				$badges = array(
					'Q' => array('asker','questioner','inquisitor'),
					'A' => array('answerer','lecturer','preacher'),
					'C' => array('commenter','commentator','annotator')
				);
				
				foreach($badges as $pt => $slugs) {
					if(!isset($data[$pt])) continue;

					qa_badge_award_check($slugs, $data[$pt], $uid, null, 0);

				}

				// nice Q&A
				
				$badges = array(
					'Q' => array('nice_question','good_question','great_question'), 
					'A' => array('nice_answer','good_answer','great_answer')
				);
			
				foreach($badges as $pt => $slugs) {
					foreach($slugs as $badge_slug) {
						if(!isset($data[$pt.'votes'])) continue;
						foreach($data[$pt.'votes'] as $idv) {
							
							if((int)$idv['votes'] >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
								
								$result = qa_db_read_one_value(
									qa_db_query_sub(
										'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
										$uid, $idv['id'], $badge_slug
									),
									true
								);
								
								if ($result == null) { // not already awarded this badge
									$this->award_badge($idv['id'], $uid, $badge_slug,false,true);
									$awarded++;
								}

							// old question answer vote checks
								if($pt == 'A') {
									$qid = $idv['parentid'];
									$create = strtotime($idv['created']);
									
									$parent = $this->get_post_data($qid);
									$pcreate = strtotime($parent['created']);
									
									$diff = round(abs($pcreate-$create)/60/60/24);
									

									$badge_slug2 = $badge_slug.'_old';
									
									if($diff  >= (int)qa_opt('badge_'.$badge_slug2.'_var') && qa_opt('badge_'.$badge_slug2.'_enabled') !== '0') {
										$result = qa_db_read_one_value(
											qa_db_query_sub(
												'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
												$uid, $idv['id'], $badge_slug2
											),
											true
										);
										if ($result == null) { // not already awarded for this answer
											$this->award_badge($idv['id'], $uid, $badge_slug2);
											$awarded++;
										}
									}
								}
							}
						}
					}
				}

				// votes per user badges

				if(isset($data['votes'])) {

					$votes = $data['votes']; 
					
					$badges = array('voter','avid_voter','devoted_voter');

					qa_badge_award_check($badges, $votes, $uid, null, 0);

				}		
						
				// views per post badges
				
				
				if(isset($data['views'])) {

					$badges = array('notable_question','popular_question','famous_question');

					foreach($data['views'] as $idv) {
						qa_badge_award_check($badges, $idv['views'], $idv['id'], null, 0);
					}
				}
				
				// flags per user
				if(isset($data['flags'])) {
					$flags = $data['flags']; 
					
					$badges = array('watchdog','bloodhound','pitbull');

					qa_badge_award_check($badges, $flags, $uid, null, 0);

				}
			}



		// selects, selecteds
			
			$selects = qa_db_read_all_assoc(
				qa_db_query_sub(
					'SELECT aselects, aselecteds, userid FROM ^userpoints'
				)
			);
			
			foreach($selects as $s) {

				$uid = $s['userid'];


				if(isset($s['aselecteds'])) {
					$count = $s['aselects'];
					$badges = array('gifted','wise','enlightened');

					qa_badge_award_check($badges, $count, $uid, null, 0);
			
				}
				if(isset($s['aselects'])) {
					$count = $s['aselecteds'];
					$badges = array('grateful','respectful','reverential');

					qa_badge_award_check($badges, $count, $uid, null, 0);

				}
			}
			
		// edits
		
			$counts = qa_db_read_all_assoc(
				qa_db_query_sub(
					'SELECT posts_edited,user_id FROM ^achievements WHERE posts_edited > 0'
				)
			);
			
			foreach($counts as $c) {
				$count = $c['posts_edited'];
				$uid = $c['user_id'];
				$badges = array('editor','copy_editor','senior_editor');

				qa_badge_award_check($badges, $count, $uid, null, 0);

			}

		// badges
			
			$badgelist = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT user_id FROM ^userbadges'
				)
			);
			
			$users = array();
			
			foreach ($badgelist as $medal) {
				$user='user'.$medal;
				
				// get flag count
				
				if(isset($users[$user]) && isset($users[$user]['medals'])) $users[$user]['medals']++;
				else $users[$user]['medals'] = 1;
			} 
			foreach($users as $user => $data) {
				$uid = (int)substr($user,4);

				// check badges

				if(isset($data['medals'])) {
					$uid = (int)substr($user,4);
					
					$count = $data['medals']; 
					
					$badges = array('medalist','champion','olympian');

					qa_badge_award_check($badges, $count, $uid, null, 0);

				}
			}

			return $awarded.' badge'.($awarded != 1 ? 's':'').' awarded.';
		}
	}
