<?php
		include('../../qa-include/qa-db.php');

		function award_badge($object_id, $user_id, $badge_slug) {
			
			// add badge to userbadges
			
			qa_db_query_sub(
				'INSERT INTO ^userbadges (awarded_at, notify, object_id, user_id, badge_slug, id) '.
				'VALUES (NOW(), #, #, #, #, 0)',
				0, $object_id, $user_id, $badge_slug
			);
			
		}
// imported user badge checking function
		function qa_check_all_users_badges() {

	// check posts
			$awarded = 0;
			$userposts;
		
			$result = qa_db_read_all_assoc(
				qa_db_query_sub(
					'SELECT * FROM ^posts'
				)
			);
			
			foreach ($result as $post) {
				$uid='user'.$post['userid'];
				$pid = $post['postid'];
				$pt = $post['type'];
				
				// get post count
				
				if(isset($userposts[$uid]) && isset($userposts[$uid][$pt])) $userposts[$uid][$pt]++;
				else $userposts[$uid][$pt] = 1;
				
				// get post votes
				
				if($post['netvotes'] !=0) $userposts[$uid][$pt.'votes'][] = array('id'=>$pid,'votes'=>$post['netvotes']);
				 
			} 
			
			foreach ($userposts as $user => $data) {
				$uid = (int)substr($user,4);
				
				$badges['Q'] = array('asker','questioner','inquisitor');
				$badges['A'] = array('answerer','lecturer','preacher');
				$badges['C'] = array('commenter','commentator','annotator');
			
				foreach($badges as $pt => $slugs) {
					foreach($slugs as $badge_slug) {
						if($data[$pt] >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
							
							$result = qa_db_read_one_value(
								qa_db_query_sub(
									'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
									$uid, $badge_slug
								),
								true
							);
							
							if (!$result) { // not already awarded this badge
								award_badge($pid, $uid, $badge_slug,false,true);
								$awarded++;
							}
						}
					}
				}

				$badges['Q'] = array('nice_question','good_question','great_question');
				$badges['A'] = array('nice_answer','good_answer','great_answer');
			
				foreach($badges as $pt => $slugs) {
					foreach($slugs as $badge_slug) {
						foreach($data[$pt.'votes'] as $idv) {
							
							if($idv['votes'] >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
								
								$result = qa_db_read_one_value(
									qa_db_query_sub(
										'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
										$uid, $idv['id'], $badge_slug
									),
									true
								);
								
								if (!$result) { // not already awarded this badge
									award_badge($idv['id'], $uid, $badge_slug,false,true);
									$awarded++;
								}
							}
						}
					}
				}
			}
			error_log($awarded.' post badges awarded');
			$awarded = 0;

		// vote volume check

			$result = qa_db_read_all_assoc(
				qa_db_query_sub(
					'SELECT * FROM ^uservotes WHERE vote <> 0'
				)
			);
			foreach ($result as $vote) {
				$uid='user'.$vote['userid'];
				$pid = $vote['postid'];
				
				// get vote count
				
				if(isset($userposts[$uid]) && isset($userposts[$uid]['votes'])) $userposts[$uid]['votes'] = $userposts[$uid]['votes']++;
				else $userposts[$uid]['votes'] = 1;
			} 

			foreach($userposts as $user => $data) {
				$uid = (int)substr($user,4);
				$votes = $data['votes']; 
				
				$badges = array('voter','avid_voter','devoted_voter');

				foreach($badges as $badge_slug) {
					if($votes  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
						$result = qa_db_read_one_value(
							qa_db_query_sub(
								'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
								$uid, $badge_slug
							),
							true
						);
						
						if (!$result) { // not already awarded this badge
							award_badge(NULL, $uid, $badge_slug,false,true);
							$awarded++;
						}
					}
				}
			}
			error_log($awarded.' vote badges awarded');
			$awarded = 0;

		// selects, selecteds
			
			$selects = qa_db_read_all_assoc(
				qa_db_query_sub(
					'SELECT aselects, aselecteds userid FROM ^userpoints'
				)
			);

			
			foreach($selects as $s) {
				$uid = $s['userid'];

				$badges = array('gifted','wise','enlightened');
				$count = $s['aselects'];

				foreach($badges as $badge_slug) {
					if((int)$count  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
						$result = qa_db_read_one_value(
							qa_db_query_sub(
								'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
								$uid, $badge_slug
							),
							true
						);
						
						if (!$result) { // not already awarded this badge
							award_badge(NULL, $uid, $badge_slug,false,true);
							$awarded++;
						}
					}
				}

				$badges = array('grateful','respectful','reverential');
				$count = $s['aselecteds'];

				foreach($badges as $badge_slug) {
					if((int)$count  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
						$result = qa_db_read_one_value(
							qa_db_query_sub(
								'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
								$uid, $badge_slug
							),
							true
						);
						
						if (!$result) { // not already awarded this badge
							award_badge(NULL, $uid, $badge_slug,false,true);
							$awarded++;
						}
					}			
				}
			}
			error_log($awarded.' select badges awarded');
			$awarded = 0;		
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

				foreach($badges as $badge_slug) {
					if((int)$count  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
						$result = qa_db_read_one_value(
							qa_db_query_sub(
								'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
								$uid, $badge_slug
							),
							true
						);
						
						if (!$result) { // not already awarded this badge
							award_badge(NULL, $uid, $badge_slug,false,true);
							$awarded++;
						}
					}
				}
			}
			error_log($awarded.' edit badges awarded');
			$awarded = 0;
		// flags

			$counts = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT userid FROM ^uservotes WHERE flag > 0'
				),
				true
			);

			$badges = array('watchdog','bloodhound','pitbull');


			foreach ($counts as $flag) {
				$uid='user'.$flag['userid'];
				
				// get flag count
				
				if(isset($userposts[$uid]) && isset($userposts[$uid]['flags'])) $userposts[$uid]['flags'] = $userposts[$uid]['flags']++;
				else $userposts[$uid]['flags'] = 1;
			} 

			foreach($userposts as $user => $data) {
				$uid = (int)substr($user,4);
				
				$flags = $data['flags']; 
				
				$badges = array('watchdog','bloodhound','pitbull');

				foreach($badges as $badge_slug) {
					if((int)$flags  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
						$result = qa_db_read_one_value(
							qa_db_query_sub(
								'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
								$uid, $badge_slug
							),
							true
						);
						
						if (!$result) { // not already awarded this badge
							award_badge(NULL, $uid, $badge_slug,false,true);
							$awarded++;
						}
					}
				}
			}
			error_log($awarded.' flag badges awarded');
			$awarded = 0;
		// check badges
	
			$counts = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT user_id FROM ^userbadges'
				),
				true
			);
			foreach ($counts as $medal) {
				$uid='user'.$medal['userid'];
				
				// get flag count
				
				if(isset($userposts[$uid]) && isset($userposts[$uid]['medals'])) $userposts[$uid]['medals'] = $userposts[$uid]['medals']++;
				else $userposts[$uid]['medals'] = 1;
			} 

			foreach($userposts as $user => $data) {
				$uid = (int)substr($user,4);
				
				$medals = $data['medals']; 
				
				$badges = array('medalist','champion','olympian');

				foreach($badges as $badge_slug) {
					if((int)$medals  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
						$result = qa_db_read_one_value(
							qa_db_query_sub(
								'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
								$uid, $badge_slug
							),
							true
						);
						
						if (!$result) { // not already awarded this badge
							award_badge(NULL, $uid, $badge_slug,false,true);
							$awarded++;
						}
					}
				}
			}
			error_log($awarded.' badge badges awarded');
			$awarded = 0;
		}
		qa_check_all_users_badges();

