<?php

	class badge_check {
		
	// main event processing function
		
		function process_event($event, $userid, $handle, $cookieid, $params) {
			switch ($event) {
				case 'q_post':
				case 'a_post':
				case 'c_post':
				// when a new question, answer or comment is created. The $params array contains full information about the new post, including its ID in $params['postid'] and textual content in $params['text'].
					break;
				case 'q_edit':
				case 'a_edit':
				case 'c_edit':
				// when a question, answer or comment is modified. The $params array contains information about the post both before and after the change, e.g. $params['content'] and $params['oldcontent'].
					break;
				case 'a_select':
				case'a_unselect':
				// when an answer is selected or unselected as the best answer for its question. The IDs of the answer and its parent question are in $params['postid'] and $params['parentid'] respectively.
					break;
				case 'q_hide':
				case 'a_hide':
				case 'c_hide':
				case 'q_reshow':
				case 'a_reshow': 
				case 'c_reshow':
				// when a question, answer or comment is hidden or shown again after being hidden. The ID of the question, answer or comment is in $params['postid'].
					break;
				case 'a_delete':
				case 'q_delete':
				case 'c_delete':
				// when a question, answer or comment is permanently deleted (after being hidden). The ID of the appropriate post is in $params['postid'].
					break;
				case 'q_claim':
				case 'a_claim':
				case 'c_claim':
				// when an anonymous question, answer or comment is claimed by a user with a matching cookie clicking 'I wrote this'. The ID of the post is in $params['postid'].
					break;
				case 'q_move':
				// when a question is moved to a different category, with more details in $params.
					break;
				case 'a_to_c':
				// when an answer is converted into a comment, with more details in $params.
					break;
				case 'q_vote_up':
					$this->event_vote($event,$userid,$params);
				case 'q_vote_down':
				case 'q_vote_nil':
				case 'a_vote_up':
				case 'a_vote_down':
				case 'a_vote_nil':
				// when a question or answer is upvoted, downvoted or unvoted by a user. The ID of the post is in $params['postid'].
					break;
				case 'u_register':
				// when a new user registers. The email is in $params['email'] and the privilege level in $params['level'].
					break;
				case 'u_login': 
				case 'u_logout':
				// when a user logs in or out of Q2A.
					break;
				case 'u_confirmed':
				// when a user successfully confirms their email address, given in $params['email'].
					break;
				case 'u_reset':
				// when a user successfully resets their password, which was emailed to $params['email'].
					break;
				case 'u_save':
				// when a user saves (and has possibly changed) their Q2A account details.
					break;
				case 'u_password':
				// when a user sets (and has possibly changed) their Q2A password.
					break;
				case 'u_edit':
				// when a user's account details are saved by someone other than the user, i.e. an admin. Note that the $userid and $handle parameters to the process_event() function identify the user making the changes, not the user who is being changed. Details of the user being changed are in $params['userid'] and $params['handle'].
					break;
				case 'u_level':
				// when a user's privilege level is changed by a different user. See u_edit above for how the two users are identified. The old and new levels are in $params['level'] and $params['oldlevel'].
					break;
				case 'u_block':
				case 'u_unblock':
				// when a user is blocked or unblocked by another user. See u_edit above for how the two users are identified.
					break;
				case 'feedback':
				// when a message is sent via the Q2A feedback form, with more details in $params.
					break;
				case 'search':
				// when a search is performed. The search query is in $params['query'] and the start position in $params['start'].
					break;
			}
		}

	// event processing functions

		function event_vote($event,$userid,$params) {
			$post = $this->get_post_data($params['postid']);
			$post_type = $post['type'];
			switch ($post_type) {
				case 'Q':
					$this->check_question($post);
					break;
				}
		}
		
	// badge checking functions
		
		function check_question($post) {
			$id = $post['postid'];
			$votes = $post['netvotes'];
			$userid = $post['userid'];
			if($votes >= 0) {
				$badge_id = $this->get_badge_id('nice_question');
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_id FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_id=#',
						$userid, $id, $badge_id
					),
					true
				);
				if (!$result) {
					$this->award_badge($id, $userid, 'Q', $badge_id);
				}
			}
		}
		

	// worker functions
		
		function award_badge($object_id, $user_id, $object_type, $badge_id) {
			
			// add badge to userbadges
			
			qa_db_query_sub(
				'INSERT INTO ^userbadges (awarded_at, notify, object_id, user_id, object_type, badge_id, id) '.
				'VALUES (NOW(), 1, #, #, $, #, 0)',
				$object_id, $user_id, $object_type, $badge_id
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
		
		function get_badge_id($slug) {
			$result = qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT badge_id FROM ^badges WHERE badge_slug=$',
					$slug
				),
				true
			);
			return $result;
		}

	}
