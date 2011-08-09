<?php

	class qa_html_theme_layer extends qa_html_theme_base {

	// register default settings

		function option_default($option) {
			
			$badges = qa_get_badge_list();
			
			$var = preg_replace('/badge_(.*)_var',"$1",$option);
			
			if($badges[$var]) {
				return $badges[$var]['var'];
			}
			
			$name = preg_replace('/badge_(.*)_name',"$1",$option);
			
			if($badges[$name]) {
				return $badges[$name]['name'];
			}
			
			
			switch($option) {
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

	// init function, after page loads
	
	function finish() {
		
		qa_html_theme_base::finish();
		
		// process per visit events 
		
		if (qa_opt('badge_active')) {
			
			require_once QA_INCLUDE_DIR.'qa-app-users.php';

			$userid = qa_get_logged_in_userid();
			if(!$userid) return; // not logged in?  die.
			
			// first visit check
			
			$user = @qa_db_read_one_assoc(
				qa_db_query_sub(
					'SELECT user_id,oldest_consec_visit,longest_consec_visit,total_days_visited,last_visit,first_visit FROM ^achievements WHERE user_id=# ',
					$userid
				),
				true
			);

			if(!$user['user_id']) {
				qa_db_query_sub(
					'INSERT INTO ^achievements (user_id, first_visit, oldest_consec_visit, longest_consec_visit, last_visit, total_days_visited, questions_read, posts_edited) VALUES (#, NOW(), NOW(), #, NOW(), #, #, #)',
					$userid, 1, 1, 0, 0
				);
				return;
			}

			// check lapse in days since last visit
			// using julian days
			
			$todayj = GregorianToJD(date('n'),date('j'),date('Y'));
			
			$last_visit = strtotime($user['last_visit']);
			$lastj = GregorianToJD(date('n',$last_visit),date('j',$last_visit),date('Y',$last_visit));
			$last_diff = $todayj-$lastj;
			
			$oldest_consec = strtotime($user['oldest_consec_visit']);
			$oldest_consecj = GregorianToJD(date('n',$oldest_consec),date('j',$oldest_consec),date('Y',$oldest_consec));
			$oldest_consec_diff = $todayj-$oldest_consecj+1; // include the first day
			
			$first_visit = strtotime($user['first_visit']);
			$first_visitj = GregorianToJD(date('n',$first_visit),date('j',$first_visit),date('Y',$first_visit));
			$first_visit_diff = $todayj-$first_visitj;
			
			if($last_diff < 0) return; // error
			
			if($last_diff < 2) { // one day or less, update last visit
				
				if($oldest_consec_diff > $user['longest_consec_visit']) {
					$user['longest_consec_visit'] = $oldest_consec_diff;
					qa_db_query_sub(
						'UPDATE ^achievements SET last_visit=NOW(), longest_consec_visit=#, total_days_visited=total_days_visited+#  WHERE user_id=#',
						$oldest_consec_diff, $last_diff, $userid 
					);		
				}
				else {
					qa_db_query_sub(
						'UPDATE ^achievements SET last_visit=NOW(), total_days_visited=total_days_visited+# WHERE user_id=#',
						$last_diff,$userid 
					);		
				}
				$badges = array('dedicated','devoted','zealous');
				qa_badge_award_check($badges, $user['longest_consec_visit'], $userid);
			}
			else { // 2+ days, reset consecutive days due to lapse
				qa_db_query_sub(
					'UPDATE ^achievements SET last_visit=NOW(), oldest_consec_visit=NOW(), total_days_visited=total_days_visited+1 WHERE user_id=#',
					$userid
				);		
			}

			$badges = array('visitor','trouper','veteran');
			qa_badge_award_check($badges, $user['total_days_visited'], $userid);
			
			$badges = array('regular','old_timer','ancestor');
			qa_badge_award_check($badges, $first_visit_diff, $userid);
		}
	}
	
		
	// theme replacement functions

		function head_script() {
			qa_html_theme_base::head_script();

			if (qa_opt('badge_active')) {
				$this->output("
				<script>".(qa_opt('badge_notify_time') != '0'?"
					$('document').ready(function() { $('.notify-container').delay(".((int)qa_opt('badge_notify_time')*1000).").fadeOut(); });":"")."
					function badgeEdit(slug,end) {
						if(end) {
							$('#badge_'+slug+'_edit').hide();
							$('#badge_'+slug+'_badge').show();
							$('#badge_'+slug+'_badge').html($('#badge_'+slug+'_edit').val());
							return;
						}
						$('#badge_'+slug+'_badge').hide();
						$('#badge_'+slug+'_edit').show();
						$('#badge_'+slug+'_edit').focus();
					}
				</script>");
			}
		}
		function head_css()
		{
			qa_html_theme_base::head_css();
			if (qa_opt('badge_active')) {
				$this->output('
				<style>
					.notify-container {
						left: 0;
						right: 0;
						top: 0;
						padding: 0;
						position: fixed;
						width: 100%;
						z-index: 10000;
					}
						.badge-notify {
						background-color: #F6DF30;
						color: #444444;
						font-weight: bold;
						width: 100%;
						text-align: center;
						font-family: sans-serif;
						font-size: 14px;
						padding: 10px 0;
						position:relative;
					}
					.notify-close {
						color: #735005;
						cursor: pointer;
						font-size: 18px;
						line-height: 18px;
						padding: 0 3px;
						position: absolute;
						right: 8px;
						text-decoration: none;
						top: 8px;
					}				
					.badge-table {
					}
					.badge-table-col {
						vertical-align:top;
					}
					.badge-bronze,.badge-silver, .badge-gold {
						margin-right:4px;
						cursor:pointer;
						color: #000;
						font-weight:bold;
						text-align:center;
						border-radius:4px;
						width:120px;
						padding: 0 10px;
						display: inline-block;
					}
					.badge-bronze {
						background-color: #CB9114;
						border:2px solid #6C582C;
					}				
					.badge-silver {
						background-color: #CDCDCD;
						border:2px solid #737373;
					}				
					.badge-gold {
						background-color: #EEDD0F;
						border:2px solid #5F5908;
					}				
					.badge-bronze-medal, .badge-silver-medal, .badge-gold-medal  {
						cursor:pointer;
						font-size: 14px;
						font-family:sans-serif;
					}
					.badge-bronze-medal {
						color: #CB9114;
					}				
					.badge-silver-medal {
						color: #CDCDCD;
					}				
					.badge-gold-medal {
						color: #EEDD0F;
					}				
					.badge-desc {
						padding-left:8px;
					}			
					.badge-source {
						padding:0;
					}			
				</style>');
			}
		}

		function body_prefix()
		{
			qa_html_theme_base::body_prefix();
			
			if (qa_opt('badge_active')) {
				$this->badge_notify();
			}
			
		}

		function body_suffix()
		{
			qa_html_theme_base::body_suffix();
			
			if (qa_opt('badge_active')) {
				if(isset($this->content['test-notify'])) $this->trigger_notify('Badge Tester');
			}
		}

		function main_parts($content)
		{
			if (qa_opt('badge_active')) {
			
				// add user badge list

				if((bool)qa_opt('badge_admin_user_field') && !empty($content['form_activity'])) { 
					if($content['q_list']) {  // paranoia
					
						// array splicing kungfu thanks to Stack Exchange
						
						// This adds custom-badges before q_list
					
						$keys = array_keys($content);
						$vals = array_values($content);

						$insertBefore = array_search('q_list', $keys);

						$keys2 = array_splice($keys, $insertBefore);
						$vals2 = array_splice($vals, $insertBefore);

						$keys[] = 'custom-badges';
						$vals[] = $this->user_badge_form();

						$content = array_merge(array_combine($keys, $vals), array_combine($keys2, $vals2));
					}
					else $content['custom'] = $this->user_badge_form();  // this shouldn't happen

				}
			}

			qa_html_theme_base::main_parts($content);

		}

		function post_meta_who($post, $class)
		{
			if (qa_opt('badge_active') && (bool)qa_opt('badge_admin_user_widget') && isset($post['who'])) {
				$handle = preg_replace('/<[^>]+>/','',$post['who']['data']); // this gets the 'who', not necessarily the post userid!
				$post['who']['suffix'] = (@$post['who']['suffix']).'&nbsp;'.$this->user_badge_widget($handle);
			}
			
			qa_html_theme_base::post_meta_who($post, $class);
		}
		
		function q_view_main($q_view) {
			qa_html_theme_base::q_view_main($q_view);

				// badge check on view update

			if (qa_opt('badge_active') && isset($this->content['inc_views_postid'])) {

					$uid = $q_view['raw']['userid'];

					if(!$uid) return; // anonymous

					$oid = $this->content['inc_views_postid'];

					// total views check

					$views = $q_view['raw']['views'];
					$views++; // because we haven't incremented the views yet
					
					$badges = array('notable_question','popular_question','famous_question');

					qa_badge_award_check($badges, $views, $uid, $oid);

				
					// personal view count increase and badge check
					
					$uid = qa_get_logged_in_userid();
					
					qa_db_query_sub(
						'UPDATE ^achievements SET questions_read=questions_read+1 WHERE user_id=# ',
						$uid
					);
					
					$views = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT questions_read FROM ^achievements WHERE user_id=# ',
							$uid
						),
						true
					);		
							
					$badges = array('reader','avid_reader','devoted_reader');

					qa_badge_award_check($badges, $views, $uid);
				
				}
		}

// worker functions

	// layout
		
		function user_badge_widget($handle) {
			
			// displays small badge widget, suitable for meta
			
			$userid = $this->getuserfromhandle($handle);
			
			if(!$userid) return;

			$result = qa_db_read_all_assoc(
				qa_db_query_sub(
					'SELECT ^badges.badge_type,COUNT(^userbadges.id) FROM ^badges,^userbadges WHERE ^badges.badge_slug=^userbadges.badge_slug AND ^userbadges.user_id=# GROUP BY ^badges.badge_type ORDER BY ^badges.badge_type',
					$userid
				)
			);

			if(count($result) == 0) return;

			$output='<span id="badge-medals-widget">';
			for($x = 2; $x >= 0; $x--) {
				if(!isset($result[$x])) continue;
				$a = $result[$x];
				$count = $a['COUNT('.QA_MYSQL_TABLE_PREFIX.'userbadges.id)'];
				if($count == 0) continue;

				$type = qa_get_badge_type($x);
				$types = $type['slug'];
				$typed = $type['name'];

				$output.='<span class="badge-'.$types.'-medal" title="'.$count.' '.$typed.'">●</span><span class="badge-'.$types.'-count" title="'.$count.' '.$typed.'"> '.$count.'</span> ';
			}
			$output = substr($output,0,-1);  // lazy remove space
			$output.='</span>';
			return($output);
		}

		function user_badge_form() {

			// displays badge list in user profile
			
			$handle = preg_replace('/.+[=\/]([^=\/]+)$/',"$1",qa_self_html()); // better way?

			$userid = $this->getuserfromhandle($handle);
			if(!$userid) return;

			$result = qa_db_read_all_assoc(
				qa_db_query_sub(
					'SELECT ^badges.badge_slug AS slug, ^badges.badge_type AS type, ^userbadges.object_id AS oid FROM ^badges,^userbadges WHERE ^badges.badge_slug=^userbadges.badge_slug AND ^userbadges.user_id=#',
					$userid
				)
			);
			
			if(count($result) == 0) return;
			
			$output = '
		<h2>'.qa_badge_lang('badges/badges').'</h2>
		<table class="qa-form-wide-table badge-table">
			<tbody>
				<tr>';
			// count badges
			
			$badges;
			
			foreach($result as $info) {
				$type = $info['type'];
				$slug = $info['slug'];
				if(isset($badges[$type][$slug])) $badges[$type][$slug]['count']++;
				else $badges[$type][$slug]['count'] = 1;
				if($info['oid']) $badges[$type][$slug]['oid'][] = $info['oid'];
			}
			
			foreach($badges as $type => $badge) {
				$typea = qa_get_badge_type($type);
				$types = $typea['slug'];
				$typed = $typea['name'];

				$output .= '
					<td class="badge-table-col">
						<table>
							<tr>
								<td class="qa-form-wide-label">
									<h3 class="badge-title" title="'.qa_badge_lang('badges/'.$types.'_desc').'">'.$typed.'</span>
								</td>
							</tr>';				
				foreach($badge as $slug => $info) {
					$badge_name=qa_badge_lang('badges/'.$slug);
					if(!qa_opt('badge_'.$slug.'_name')) qa_opt('badge_'.$slug.'_name',$badge_name);
					$name = qa_opt('badge_'.$slug.'_name');
					
					$count = $info['count'];
					$oids = $info['oid'];
					
					$var = qa_opt('badge_'.$slug.'_var');
					$desc = str_replace('#',$var,qa_badge_lang('badges/'.$slug.'_desc'));
					
					// badge row
					
					$output .= '
							<tr>
								<td class="qa-form-wide-label">
									<span class="badge-'.$types.'" title="'.$desc.' ('.$typed.')" onclick="jQuery(\'.badge-source-'.$slug.'\').slideToggle()">'.$name.'</span>
								</td>
								<td class="qa-form-wide-data">
									<span class="badge-count">x&nbsp;'.$count.'</span>
								</td>
							</tr>';
					
					// source row(s) if any	
					
					foreach($oids as $oid) {
						$post = qa_db_read_one_assoc(
							qa_db_query_sub(
								'SELECT title,type,parentid FROM ^posts WHERE postid=#',
								$oid
							),
							true
						);
						
						$title=$post['title'];
						
						$anchor = '';
						
						if($post['parentid']) {
							$anchor = urlencode(qa_anchor($post['type'],$oid));
							$oid = $post['parentid'];
							$title = qa_db_read_one_value(
								qa_db_query_sub(
									'SELECT title FROM ^posts WHERE postid=#',
									$oid
								),
								true
							);	
						}

						if(count($title > 20) $text = substr($title,0,20).'...';
						
						$output .= '
							<tr>
								<td colspan="2" class="badge-source">
									<a href="'.qa_path_html(qa_q_request($oid,$title),NULL,qa_opt('site_url')).($anchor?'#'.$anchor:'').'" class="badge-source-'.$slug.'" style="display:none">'.$text.'</a>
								</td>
							</tr>';
					}
				}
				$output .= '
						</table>
					</td>';
			}
			$output .= '
				</tr>
			</tbody>
		</table>';
			
			return $output;
			
		}

	// badge notification

		function badge_notify() {
			$userid = qa_get_logged_in_userid();
			
			$result = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND notify=1',
					$userid
				)
			);
			if(count($result) > 0) {

				$notice = '<div class="notify-container">';
				
				// populate notification list
				foreach($result as $slug) {
					$badge_name=qa_badge_lang('badges/'.$slug);
					if(!qa_opt('badge_'.$slug.'_name')) qa_opt('badge_'.$slug.'_name',$badge_name);
					$name = qa_opt('badge_'.$slug.'_name');
					
					$notice .= '<div class="badge-notify notify">'.qa_badge_lang('badges/badge_notify')."'".$name.'\'!&nbsp;&nbsp;'.qa_badge_lang('badges/badge_notify_profile_pre').'<a href="'.qa_path_html('user/'.qa_get_logged_in_handle()).'">'.qa_badge_lang('badges/badge_notify_profile').'</a><div class="notify-close" onclick="$(this).parent().hide(\'slow\')">x</div></div>';
				}

				$notice .= '</div>';
				
				// remove notification flag
				
				qa_db_query_sub(
					'UPDATE ^userbadges SET notify=0 WHERE user_id=# AND notify=1',
					$userid
				);
				$this->output($notice);
			}
		}

	// etc
		
		function trigger_notify($message) {
			$notice = '<div class="notify-container"><div class="badge-notify notify">'.qa_badge_lang('badges/badge_notify')."'".$message.'\'!&nbsp;&nbsp;'.qa_badge_lang('badges/badge_notify_profile_pre').'<a href="/user/'.qa_get_logged_in_handle().'">'.qa_badge_lang('badges/badge_notify_profile').'</a><div class="notify-close" onclick="$(this).parent().fadeOut()">x</div></div></div>';
			$this->output($notice);
		}
		
		function priviledge_notify() { // gained priviledge
		}

		function getuserfromhandle($handle) {
			require_once QA_INCLUDE_DIR.'qa-app-users.php';
			
			if (QA_FINAL_EXTERNAL_USERS) {
				$publictouserid=qa_get_userids_from_public(array($handle));
				$userid=@$publictouserid[$handle];
				
			} 
			else {
				$userid = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT userid FROM ^users WHERE handle = $',
						$handle
					),
					true
				);
			}
			if (!isset($userid)) return;
			return $userid;
		}
		
	}
	
