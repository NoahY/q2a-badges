<?php

	class badge_check {
		
// main event processing function
		
		function process_event($event, $userid, $handle, $cookieid, $params) {
			
			if (qa_opt('badge_active')) {
				switch ($event) {

					// when a new question, answer or comment is created. The $params array contains full information about the new post, including its ID in $params['postid'] and textual content in $params['text'].
					case 'q_post':
						$this->question_post($event,$userid,$params);
						break;
					case 'a_post':
						$this->answer_post($event,$userid,$params);
						break;
					case 'c_post':
						$this->comment_post($event,$userid,$params);
						break;

					// when a question, answer or comment is modified. The $params array contains information about the post both before and after the change, e.g. $params['content'] and $params['oldcontent'].
					case 'q_edit':
						$this->question_edit($event,$userid,$params);
						break;
					case 'a_edit':
						$this->answer_edit($event,$userid,$params);
						break;
					case 'c_edit':
						$this->comment_edit($event,$userid,$params);
						break;

					// when an answer is selected or unselected as the best answer for its question. The IDs of the answer and its parent question are in $params['postid'] and $params['parentid'] respectively.
					case 'a_select':
						$this->answer_select($event,$userid,$params);
						break;
					case'a_unselect':
						break;

					// when a question, answer or comment is hidden or shown again after being hidden. The ID of the question, answer or comment is in $params['postid'].
					case 'q_hide':
					case 'a_hide':
					case 'c_hide':
					case 'q_reshow':
					case 'a_reshow': 
					case 'c_reshow':
						break;

					// when a question, answer or comment is permanently deleted (after being hidden). The ID of the appropriate post is in $params['postid'].
					case 'a_delete':
					case 'q_delete':
					case 'c_delete':
						break;

					// when an anonymous question, answer or comment is claimed by a user with a matching cookie clicking 'I wrote this'. The ID of the post is in $params['postid'].
					case 'q_claim':
					case 'a_claim':
					case 'c_claim':
						break;

					// when a question is moved to a different category, with more details in $params.
					case 'q_move':
						break;

					// when an answer is converted into a comment, with more details in $params.
					case 'a_to_c':
						break;

					// when a question or answer is upvoted, downvoted or unvoted by a user. The ID of the post is in $params['postid'].
					case 'q_vote_up':
						$this->question_vote_up($event,$userid,$params);
						break;
					case 'a_vote_up':
						$this->answer_vote_up($event,$userid,$params);
						break;
					case 'q_vote_down':
						$this->question_vote_down($event,$userid,$params);
						break;
					case 'a_vote_down':
						$this->answer_vote_down($event,$userid,$params);
						break;
					case 'c_vote_up':
						$this->comment_vote_up($event,$userid,$params);
						break;
					case 'c_vote_down':
						$this->check_voter($userid);
						break;
					case 'q_vote_nil':
					case 'a_vote_nil':
						break;
					// when a question, answer or comment is flagged or unflagged. The ID of the question, answer or comment is in $params['postid'].
					case 'q_flag':
						$this->question_flag($event,$userid,$params);
						break;
					case 'a_flag':
						$this->answer_flag($event,$userid,$params);
						break;
					case 'c_flag':
						$this->comment_flag($event,$userid,$params);
						break;
					case 'q_unflag':
						break;
					case 'a_unflag':
						break;
					case 'c_unflag':
						break;

					// when a new user registers. The email is in $params['email'] and the privilege level in $params['level'].
					case 'u_register':
						break;

					// when a user logs in or out of Q2A.
					case 'u_login': 
					case 'u_logout':
						break;

					// when a user successfully confirms their email address, given in $params['email'].
					case 'u_confirmed':
						$this->check_email_award($event,$userid,$params);
						break;

					// when a user successfully resets their password, which was emailed to $params['email'].
					case 'u_reset':
						break;

					// when a user saves (and has possibly changed) their Q2A account details.
						// check for full details
					case 'u_save':
						$this->check_user_fields($userid,$params);
						break;

					// when a user sets (and has possibly changed) their Q2A password.
					case 'u_password':
						break;

					// when a user's account details are saved by someone other than the user, i.e. an admin. Note that the $userid and $handle parameters to the process_event() function identify the user making the changes, not the user who is being changed. Details of the user being changed are in $params['userid'] and $params['handle'].
					case 'u_edit':
						break;

					// when a user's privilege level is changed by a different user. See u_edit above for how the two users are identified. The old and new levels are in $params['level'] and $params['oldlevel'].
						//$this->priviledge_flag($params['level'],$params['userid']);
					case 'u_level':
						break;

					// when a user is blocked or unblocked by another user. See u_edit above for how the two users are identified.
					case 'u_block':
					case 'u_unblock':
						break;

					// when a message is sent via the Q2A feedback form, with more details in $params.
					case 'feedback':
						break;

					// when a search is performed. The search query is in $params['query'] and the start position in $params['start'].
					case 'search':
						break;
				}
			}
		}

// badge checking functions
		
	// check on post
		
		function question_post($event,$event_user,$params) {
			$id = $params['postid'];

			// asker check
			
			if($event_user) $this->check_question_number($event_user,$id);
			
		}
		
		function answer_post($event,$event_user,$params) {
			$id = $params['postid'];

			// answerer check
			
			if($event_user) $this->check_answer_number($event_user,$id);
			
		}
		
		function comment_post($event,$event_user,$params) {
			$id = $params['postid'];

			// commenter check
			
			if($event_user) $this->check_comment_number($event_user,$id);
			
		}
		
		// count total posts
		
		function check_question_number($uid,$oid) {
			$posts = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT postid FROM ^posts WHERE userid=# AND type=$',
					$uid, 'Q'
				)
			);
			
			// sheer volume of posts
			$badges = array('asker','questioner','inquisitor');
			qa_badge_award_check($badges, count($posts), $uid);			
		}
		
		function check_answer_number($uid,$oid) {
			$posts = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT postid FROM ^posts WHERE userid=# AND type=$',
					$uid, 'A'
				)
			);

			// sheer volume of posts
			
			$badges = array('answerer','lecturer','preacher');
			qa_badge_award_check($badges, count($posts), $uid);		
		}
		
		function check_comment_number($uid,$oid) {
			$posts = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT postid FROM ^posts WHERE userid=# AND type=$',
					$uid, 'C'
				)
			);

			// sheer volume of posts
			
			$badges = array('commenter','commentator','annotator');
			qa_badge_award_check($badges, count($posts), $uid);			

		}
		
	// check on votes
		
		function question_vote_up($event,$event_user,$params) {
			$oid = $params['postid'];
			$post = $this->get_post_data($oid);
			$votes = $post['netvotes'];
			$uid = $post['userid'];

			// voted volume check

			$this->check_voted($uid);
			
			// vote volume check
			
			if($event_user) $this->check_voter($event_user);

			// post owner upvotes check

			$badges = array('nice_question','good_question','great_question');
			qa_badge_award_check($badges, $votes, $uid, $oid);		
		}
		
		// check number of votes on answer
		
		function answer_vote_up($event,$event_user,$params) {
			$oid = $params['postid'];
			$post = $this->get_post_data($oid);
			$votes = $post['netvotes'];
			$uid = $post['userid'];

			// voted volume check

			$this->check_voted($uid);
			
			// vote volume check
			
			if($event_user) $this->check_voter($event_user,$oid);

			// post owner upvotes check
			
			if(@$this->poll) return; // poll plugin integration
			
			$badges = array('nice_answer','good_answer','great_answer');

			foreach($badges as $badge_slug) {
				if($votes  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
							$uid, $oid, $badge_slug
						),
						true
					);
					if ($result == null) { // not already awarded for this answer
						$this->award_badge($oid, $uid, $badge_slug);
					}

					// self-answer vote checks TODO

					// old question answer vote checks
					
					$create = strtotime($post['created']);
					
					$qid = $post['parentid'];

					$parent = $this->get_post_data($qid);
					$pcreate = strtotime($parent['created']);
					
					$diff = round(abs($pcreate-$create)/60/60/24);

					$badge_slug2 = $badge_slug.'_old';
					
					if($diff  >= (int)qa_opt('badge_'.$badge_slug2.'_var') && qa_opt('badge_'.$badge_slug2.'_enabled') !== '0') {
						$result = qa_db_read_one_value(
							qa_db_query_sub(
								'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
								$uid, $oid, $badge_slug2
							),
							true
						);
						if ($result == null) { // not already awarded for this answer
							$this->award_badge($oid, $uid, $badge_slug2);
						}
					}
				}
			}
		}

		function comment_vote_up($event,$event_user,$params) {
			$oid = $params['postid'];
			$post = $this->get_post_data($oid);
			$votes = $post['netvotes'];
			$uid = $post['userid'];

			// voted volume check

			$this->check_voted($uid);
			
			// vote volume check
			
			if($event_user) $this->check_voter($event_user);

			// post owner upvotes check

			$badges = array('nice_comment','good_comment','great_comment');
			qa_badge_award_check($badges, $votes, $uid, $oid);		
		}

		function question_vote_down($event,$event_user,$params) {
			$id = $params['postid'];
			
			// vote volume check
			
			if($event_user) $this->check_voter($event_user);
		}

		function answer_vote_down($event,$event_user,$params) {
			$id = $params['postid'];
			
			// vote volume check
			
			if($event_user) $this->check_voter($event_user);
		}

		function check_voted($uid) {
						// upvotes received
			
			$votes = qa_db_read_one_assoc(
				qa_db_query_sub(
					'SELECT upvoteds FROM ^userpoints WHERE userid=#',
					$uid
				),
				true
			);
			$badges = array('liked','loved','revered');

			qa_badge_award_check($badges, $votes, $uid);
		}

		function check_voter($uid) {
			
			$voter = qa_db_read_one_assoc(
				qa_db_query_sub(
					'SELECT qupvotes,qdownvotes,aupvotes,adownvotes FROM ^userpoints WHERE userid=#',
					$uid
				),
				true
			);
			$votes = (int)$voter['qupvotes']+(int)$voter['qdownvotes']+(int)$voter['aupvotes']+(int)$voter['adownvotes'];
			$badges = array('voter','avid_voter','devoted_voter');

			qa_badge_award_check($badges, $votes, $uid);
		}

	// check on selected answer

		function answer_select($event,$uid,$params) {
			$qid = $params['parentid'];
			$aid = $params['postid'];
			$a = $this->get_post_data($aid);
			$auid = $a['userid'];
			

			if($auid) {

				// sheer number of answerer's answers selected by others

				$count = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT aselecteds FROM ^userpoints WHERE userid=#',
						$auid
					),
					true
				);			

				$badges = array('gifted','wise','enlightened');

				qa_badge_award_check($badges, $count, $auid);
			}

			if($uid) {
			
				// sheer number of answers selected by selecter

				$count = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT aselects FROM ^userpoints WHERE userid=#',
						$uid
					),
					true
				);

				$badges = array('grateful','respectful','reverential');

				qa_badge_award_check($badges, $count, $uid);
			}
		
		}
		
	// check on edit
	
		function question_edit($event,$event_user,$params) {

			if($params['content'] == $params['oldcontent']) return;
			
			if($event_user) $this->add_edit_count($event_user);
			
			// sheer edit volume
			if($event_user) $this->check_editor($event_user);
			
		}

		function answer_edit($event,$event_user,$params) {

			if($params['content'] == $params['oldcontent']) return;
			
			if($event_user) $this->add_edit_count($event_user);
			
			// sheer edit volume
			
			if($event_user) $this->check_editor($event_user);
			
		}

		function comment_edit($event,$event_user,$params) {

			if($params['content'] == $params['oldcontent']) return;
			
			if($event_user) $this->add_edit_count($event_user);
			
			// sheer edit volume
			
			if($event_user) $this->check_editor($event_user);
			
		}
		
		function add_edit_count($uid) {
			
			qa_db_query_sub(
				'UPDATE ^achievements SET posts_edited=posts_edited+1 WHERE user_id=#',
				$uid
			);
					
		}
		
		function check_editor($uid) {
			$count = qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT posts_edited FROM ^achievements WHERE user_id=#',
					$uid
				),
				true
			);

			$badges = array('editor','copy_editor','senior_editor');

			qa_badge_award_check($badges, $count, $uid);
		
		}		

	// check on flags

		function question_flag($event,$event_user,$params) {
			$id = $params['postid'];
			
			// flag volume check
			
			if($event_user) $this->check_flagger($event_user);
		}

		function answer_flag($event,$event_user,$params) {
			$id = $params['postid'];
			
			// flag volume check
			
			if($event_user) $this->check_flagger($event_user);
		}

		function comment_flag($event,$event_user,$params) {
			$id = $params['postid'];
			
			// flag volume check
			
			if($event_user) $this->check_flagger($event_user);
		}

		function check_flagger($uid) {
			$flags = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT userid FROM ^uservotes WHERE userid=# AND flag = #',
					$uid, 1
				)
			);

			$badges = array('watchdog','bloodhound','pitbull');

			qa_badge_award_check($badges, count($flags), $uid);

		}
		
		// verified email check for badge 
		function check_email_award($event,$event_user,$params) {
			
			$badges = array('verified');
			qa_badge_award_check($badges, false, $event_user);
		}
		
		// user field changes
		function check_user_fields($userid,$params) {
			list($useraccount, $userprofile, $userfields)=qa_db_select_with_pending(
				qa_db_user_account_selectspec($userid, true),
				qa_db_user_profile_selectspec($userid, true),
				qa_db_userfields_selectspec()
			);
				
			// avatar badge
			
			if (qa_opt('avatar_allow_upload') && isset($useraccount['avatarblobid'])) {
				$badges = array('avatar');
				qa_badge_award_check($badges, false, $userid);				
			}
			
			// profile completion
			
			$missing = false;
			foreach ($userfields as $userfield) {
				if(!isset($userprofile[$userfield['title']]) || @$userprofile[$userfield['title']] === '') {
					$missing = true;
					break;
				}
			}
			
			if(!$missing) {
				$badges = array('profiler');
				qa_badge_award_check($badges, false, $userid);			
			}
			
		}

	// check on badges
	
		function check_badges($uid) {
			$medals = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT user_id FROM ^userbadges WHERE user_id=#',
					$uid
				)
			);

			$badges = array('medalist','champion','olympian');

			foreach($badges as $badge_slug) {
				if(count($medals)  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
							$uid, $badge_slug
						),
						true
					);
					
					if ($result == null) { // not already awarded this badge
						$this->award_badge(null, $uid, $badge_slug, true); // this is a "badge badge"
					}
				}
			}			
		}


// worker functions

		
		function award_badge($object_id, $user_id, $badge_slug, $badge_badge = false) {
			if(!$user_id) return;
			
			// add badge to userbadges
			
			qa_db_query_sub(
				'INSERT INTO ^userbadges (awarded_at, notify, object_id, user_id, badge_slug, id) '.
				'VALUES (NOW(), 1, #, #, $, 0)',
				$object_id, $user_id, $badge_slug
			);
			
			if(qa_opt('event_logger_to_database')) { // add event
				
				$handle = qa_getHandleFromId($user_id);
				
				qa_db_query_sub(
					'INSERT INTO ^eventlog (datetime, ipaddress, userid, handle, cookieid, event, params) '.
					'VALUES (NOW(), $, $, $, #, $, $)',
					qa_remote_ip_address(), $user_id, $handle, qa_cookie_get(), 'badge_awarded', 'badge_slug='.$badge_slug.($object_id?"\t".'postid='.$object_id:'')
				);
			}
			
			if(qa_opt('badge_email_notify')) qa_badge_notification($user_id, $object_id, $badge_slug);	
			
			// check for sheer number of badges, unless this badge was for number of badges (avoid recursion!)
			if(!$badge_badge) $this->check_badges($user_id);
		}

		function priviledge_notify($object_id, $user_id, $badge_slug) {
			
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
	}
