<?php

	class badge_check {
		
	// main event processing function
		
		function process_event($event, $userid, $handle, $cookieid, $params) {
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
				case 'a_edit':
				case 'c_edit':
					break;

				// when an answer is selected or unselected as the best answer for its question. The IDs of the answer and its parent question are in $params['postid'] and $params['parentid'] respectively.
				case 'a_select':
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
				case 'q_vote_nil':
				case 'a_vote_nil':
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
					$this->award_badge(null,$user_id,'verified');
					break;

				// when a user successfully resets their password, which was emailed to $params['email'].
				case 'u_reset':
					break;

				// when a user saves (and has possibly changed) their Q2A account details.
					// check for full details
				case 'u_save':
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

	// badge checking functions
		
		// check on post
		
		function question_post($event,$event_user,$params) {
			$id = $params['postid'];

			// asker check
			
			$this->check_question_number($event_user,$id);
			
		}
		
		function answer_post($event,$event_user,$params) {
			$id = $params['postid'];

			// answerer check
			
			$this->check_answer_number($event_user,$id);
			
		}
		
		function comment_post($event,$event_user,$params) {
			$id = $params['postid'];

			// commenter check
			
			$this->check_comment_number($event_user,$id);
			
		}
		
		// count total posts
		
		function check_question_number($uid,$oid) {
			$posts = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT postid FROM ^posts WHERE userid=# AND type=$',
					$uid, 'Q'
				),
				true
			);
			
			if(count($posts) >= (int)qa_opt('badge_asker_var')-1) {
				$badge_slug = 'asker';
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge($id, $userid, $badge_slug);
				}
			}

			if(count($posts) >= (int)qa_opt('badge_questioner_var')-1) {
				$badge_slug = 'questioner';
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge($id, $userid, $badge_slug);
				}
			}
		
			if(count($posts) >= (int)qa_opt('badge_inquisitor_var')-1) {
				$badge_slug = 'inquisitor';
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge($id, $userid, $badge_slug);
				}
			}
		}
		
		function check_answer_number($uid,$oid) {
			$posts = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT postid FROM ^posts WHERE userid=# AND type=$',
					$uid, 'A'
				),
				true
			);
			
			if(count($posts) >= (int)qa_opt('badge_answerer_var')-1) {
				$badge_slug = 'answerer';
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge($id, $userid, $badge_slug);
				}
			}

			if(count($posts) >= (int)qa_opt('badge_lecturer_var')-1) {
				$badge_slug = 'lecturer';
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge($id, $userid, $badge_slug);
				}
			}
		
			if(count($posts) >= (int)qa_opt('badge_preacher_var')-1) {
				$badge_slug = 'preacher';
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge($id, $userid, $badge_slug);
				}
			}
		}
		
		function check_comment_number($uid,$oid) {
			$posts = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT postid FROM ^posts WHERE userid=# AND type=$',
					$uid, 'C'
				),
				true
			);
			
			if(count($posts) >= (int)qa_opt('badge_commenter_var')-1) {
				$badge_slug = 'commenter';
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge($id, $userid, $badge_slug);
				}
			}

			if(count($posts) >= (int)qa_opt('badge_commentator_var')-1) {
				$badge_slug = 'commentator';
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge($id, $userid, $badge_slug);
				}
			}
		
			if(count($posts) >= (int)qa_opt('badge_annotator_var')-1) {
				$badge_slug = 'annotator';
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge($id, $userid, $badge_slug);
				}
			}
		}
		
		// check number of votes on question
		
		function question_vote_up($event,$event_user,$params) {
			$id = $params['postid'];
			$post = $this->get_post_data($id);
			$votes = $post['netvotes'];
			$userid = $post['userid'];
			
			// voter check
			
			$this->checkvotes($event_user,$id);
			
			// nice question: 2 upvotes
						
			if($votes >= (int)qa_opt('badge_nice_question_var')-1) {  // -1, because we can't count this upvote
				$badge_slug = 'nice_question';
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				if (!$result) { // not already awarded for this question
					$this->award_badge($id, $userid, $badge_slug);
				}
			}

			// good question: 3 upvotes
						
			if($votes >= (int)qa_opt('badge_good_question_var')-1) {
				$badge_slug = 'good_question';
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				if (!$result) { // not already awarded for this question
					$this->award_badge($id, $userid, $badge_slug);
				}
			}

			// great question: 5 upvotes
						
			if($votes >= (int)qa_opt('badge_great_question_var')-1) {
				$badge_slug = 'great_question';
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				if (!$result) { // not already awarded for this question
					$this->award_badge($id, $userid, $badge_slug);
				}
			}

		}
		
		// check number of votes on answer
		
		function answer_vote_up($event,$event_user,$params) {
			$id = $params['postid'];
			$post = $this->get_post_data($id);
			$votes = $post['netvotes'];
			$userid = $post['userid'];

			// voter check
			
			$this->checkvotes($event_user,$id);
			
			// nice answer: 2 upvotes
						
			if($votes >= (int)qa_opt('badge_nice_answer_var')-1) {  // -1, because we can't count this upvote
				$badge_slug = 'nice_answer';
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				if (!$result) { // not already awarded for this answer
					$this->award_badge($id, $userid, $badge_slug);
				}
			}

			// good answer: 3 upvotes
						
			if($votes >= (int)qa_opt('badge_good_answer_var')-1) {
				$badge_slug = 'good_answer';
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				if (!$result) { // not already awarded for this answer
					$this->award_badge($id, $userid, $badge_slug);
				}
			}

			// great answer: 5 upvotes
						
			if($votes >= (int)qa_opt('badge_great_answer_var')-1) {
				$badge_slug = 'great_answer';
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				if (!$result) { // not already awarded for this answer
					$this->award_badge($id, $userid, $badge_slug);
				}
			}

						
		}

		function question_vote_down($event,$userid,$params) {
			$id = $params['postid'];
			
			// voter check
			
			$this->checkvotes($event_user,$id);
		}

		function answer_vote_down($event,$userid,$params) {
			$id = $params['postid'];
			
			// voter check
			
			$this->checkvoter($event_user,$id);
		}

		function check_voter($uid,$oid) {
			
			$votes = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT userid FROM ^uservotes WHERE userid=# AND postid=# AND vote !=#',
					$uid, $oid, 0
				),
				true
			);
			
			if(count($votes) >= (int)qa_opt('badge_voter_var')-1) {
				$badge_slug = 'voter';
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge($id, $userid, $badge_slug);
				}
			}

			if(count($votes) >= (int)qa_opt('badge_avid_voter_var')-1) {
				$badge_slug = 'voter';
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge($id, $userid, $badge_slug);
				}
			}

			if(count($votes) >= (int)qa_opt('badge_devoted_voter_var')-1) {
				$badge_slug = 'voter';
				
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$userid, $id, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge($id, $userid, $badge_slug);
				}
			}
		}

	// worker functions

		
		function award_badge($object_id, $user_id, $badge_slug) {
			
			// add badge to userbadges
			
			qa_db_query_sub(
				'INSERT INTO ^userbadges (awarded_at, notify, object_id, user_id, badge_slug, id) '.
				'VALUES (NOW(), 1, #, #, #, 0)',
				$object_id, $user_id, $badge_slug
			);
		}

		function priviledge_notify($object_id, $user_id, $badge_slug) {
			
			// add badge to userbadges
			
			qa_db_query_sub(
				'INSERT INTO ^userbadges (awarded_at, notify, object_id, user_id, badge_slug, id) '.
				'VALUES (NOW(), 1, #, #, #, 0)',
				$object_id, $user_id, $badge_slug
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
	}
