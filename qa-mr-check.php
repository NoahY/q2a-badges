<?php

	class mention_replace {

// register default settings

		function option_default($option) {
			
			switch($option) {
				default:
					return false;
			}
			
		}
		
// main event processing function
		
		function process_event($event, $userid, $handle, $cookieid, $params) {
			
			if (qa_opt('mention_replace_enable')) {
				switch ($event) {

					// when a new question, answer or comment is created. The $params array contains full information about the new post, including its ID in $params['postid'] and textual content in $params['text'].
					case 'q_post':
					case 'a_post':
					case 'c_post':
						$this->post($event,$userid,$params);
						break;
					default:
						break;
				}
			}
		}

		
		
		function post($event,$event_user,$params) {
			error_log($params['text']);
			$content = $params['text'];
			
			include_once( ABSPATH . WPINC . '/registration.php' );
			
			$pattern = '/[@]+([A-Za-z0-9-_\.]+)/';
			preg_match_all( $pattern, $content, $usernames );

			// Make sure there's only one instance of each username
			if ( !$usernames = array_unique( $usernames[1] ) )
				return $content;

			foreach( (array)$usernames as $username ) {
				if ( !$user_id = username_exists( $username ) )
					continue;

				// Increase the number of new @ mentions for the user
				$new_mention_count = (int)get_user_meta( $user_id, 'bp_new_mention_count', true );
				update_user_meta( $user_id, 'bp_new_mention_count', $new_mention_count + 1 );

				$content = str_replace( "@$username", "<a href='" . bp_core_get_user_domain( bp_core_get_userid( $username ) ) . "' rel='nofollow'>@$username</a>", $content );
			}

			qa_db_query_sub(
				'UPDATE ^posts SET content=$ WHERE postid=#',
				$content, $params['postid']
			);
			
		}
	}
