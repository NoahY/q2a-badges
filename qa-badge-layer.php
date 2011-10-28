<?php

	class qa_html_theme_layer extends qa_html_theme_base {

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
						'SELECT user_id AS uid,oldest_consec_visit AS ocv,longest_consec_visit AS lcv,total_days_visited AS tdv,last_visit AS lv,first_visit AS fv FROM ^achievements WHERE ^achievements.user_id=# ',
						$userid
					),
					true
				);
				$usera = @qa_db_read_one_assoc(
					qa_db_query_sub(
						'SELECT points FROM ^userpoints WHERE userid=# ',
						$userid
					),
					true
				);
				
				if($usera) $user = array_merge($user, $usera);

				if(!$user['uid']) {
					qa_db_query_sub(
						'INSERT INTO ^achievements (user_id, first_visit, oldest_consec_visit, longest_consec_visit, last_visit, total_days_visited, questions_read, posts_edited) VALUES (#, NOW(), NOW(), #, NOW(), #, #, #) ON DUPLICATE KEY UPDATE first_visit=NOW(), oldest_consec_visit=NOW(), longest_consec_visit=#, last_visit=NOW(), total_days_visited=#, questions_read=#, posts_edited=#',
						$userid, 1, 1, 0, 0, 1, 1, 0, 0
					);
					return;
				}

				// check lapse in days since last visit
				// using julian days
				
				$todayj = GregorianToJD(date('n'),date('j'),date('Y'));
				
				$last_visit = strtotime($user['lv']);
				$lastj = GregorianToJD(date('n',$last_visit),date('j',$last_visit),date('Y',$last_visit));
				$last_diff = $todayj-$lastj;
				
				$oldest_consec = strtotime($user['ocv']);
				$oldest_consecj = GregorianToJD(date('n',$oldest_consec),date('j',$oldest_consec),date('Y',$oldest_consec));
				$oldest_consec_diff = $todayj-$oldest_consecj+1; // include the first day
				
				$first_visit = strtotime($user['fv']);
				$first_visitj = GregorianToJD(date('n',$first_visit),date('j',$first_visit),date('Y',$first_visit));
				$first_visit_diff = $todayj-$first_visitj;
				
				if($last_diff < 0) return; // error
				
				if($last_diff < 2) { // one day or less, update last visit
					
					if($oldest_consec_diff > $user['lcv']) {
						$user['lcv'] = $oldest_consec_diff;
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
					qa_badge_award_check($badges, $user['lcv'], $userid,null,2);
				}
				else { // 2+ days, reset consecutive days due to lapse
					qa_db_query_sub(
						'UPDATE ^achievements SET last_visit=NOW(), oldest_consec_visit=NOW(), total_days_visited=total_days_visited+1 WHERE user_id=#',
						$userid
					);		
				}

				$badges = array('visitor','trouper','veteran');
				qa_badge_award_check($badges, $user['tdv'], $userid,null,2);
				
				$badges = array('regular','old_timer','ancestor');
				qa_badge_award_check($badges, $first_visit_diff, $userid,null,2);
				
				// check points
				
				$badges = array('100_club','1000_club','10000_club');
				qa_badge_award_check($badges, $user['points'], $userid,null,2);	
			}
		}
	
	// init before start

	function doctype() {
		if($this->template == 'user') {
			if(!isset($this->content['navigation']['sub'])) {
				$this->content['navigation']['sub'] = array(
					'profile' => array(
						'url' => qa_path_html('user/'.$this->_user_handle(), null, qa_opt('site_url')),
						'label' => $this->_user_handle(),
						'selected' => !qa_get('tab')?true:false
					),
					'badges' => array(
						'url' => qa_path_html('user/'.$this->_user_handle(), array('tab'=>'badges'), qa_opt('site_url')),
						'label' => qa_badge_lang('badges/badges'),
						'selected' => qa_get('tab')=='badges'?true:false
					),
				);
			}
			else {
				$this->content['navigation']['sub']['badges'] = array(
					'url' => qa_path_html('user/'.$this->_user_handle(), array('tab'=>'badges'), qa_opt('site_url')),
					'label' => qa_badge_lang('badges/badges'),
					'selected' => qa_get('tab')=='badges'?true:false
				);
			}
		}
		qa_html_theme_base::doctype();
	}
		
	// theme replacement functions

		function head_script() {
			qa_html_theme_base::head_script();

			if (qa_opt('badge_active')) {
				$this->output("
				<script>".(qa_opt('badge_notify_time') != '0'?"
					jQuery('document').ready(function() { jQuery('.notify-container').delay(".((int)qa_opt('badge_notify_time')*1000).").fadeOut(); });":"")."
					function badgeEdit(slug,end) {
						if(end) {
							jQuery('#badge_'+slug+'_edit').hide();
							jQuery('#badge_'+slug+'_badge').show();
							jQuery('#badge_'+slug+'_badge').html(jQuery('#badge_'+slug+'_edit').val());
							return;
						}
						jQuery('#badge_'+slug+'_badge').hide();
						jQuery('#badge_'+slug+'_edit').show();
						jQuery('#badge_'+slug+'_edit').focus();
					}
				</script>");
			}
		}
		function head_css()
		{
			qa_html_theme_base::head_css();
			if (qa_opt('badge_active')) {
				$this->output('
				<style>',qa_opt('badges_css'),'</style>');
			}
		}

		function body_prefix()
		{
			qa_html_theme_base::body_prefix();
			
			if (qa_opt('badge_active') && $this->template != 'admin') {
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
			if (qa_opt('badge_active') && $this->template == 'user' && qa_opt('badge_admin_user_field') && qa_get('tab')=='badges') { 
					$content = array();
					$content['form-badges-list'] = $this->user_badge_form();  // this shouldn't happen
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

				qa_badge_award_check($badges, $views, $uid, $oid,2);

			
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

				qa_badge_award_check($badges, $views, $uid,null,2);
			
			}
		}

		// add badges to users list

		function ranking($ranking) {
			
			if(@$ranking['type']=='users' && qa_opt('badge_show_users_badges')) {
				foreach($ranking['items'] as $idx => $item) {
					$handle = preg_replace('/ *<[^>]+> */', '', $item['label']);
					
					if(isset($ranking['items'][$idx]['score'])) $ranking['items'][$idx]['score'] .= '</td><td class="qa-top-users-score">'.$this->user_badge_widget($handle);
				}
			}
			qa_html_theme_base::ranking($ranking);
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

				$output.='<span class="badge-pointer badge-'.$types.'-medal" title="'.$count.' '.$typed.'">●</span><span class="badge-pointer badge-'.$types.'-count" title="'.$count.' '.$typed.'"> '.$count.'</span> ';
			}
			$output = substr($output,0,-1);  // lazy remove space
			$output.='</span>';
			return($output);
		}

		function user_badge_form() {
			// displays badge list in user profile
			
			global $qa_request;
			
			$handle = preg_replace('/^[^\/]+\/([^\/]+).*/',"$1",$qa_request);
			
			$userid = $this->getuserfromhandle($handle);
			
			if(!$userid) return;

			$result = qa_db_read_all_assoc(
				qa_db_query_sub(
					'SELECT ^badges.badge_slug AS slug, ^badges.badge_type AS type, ^userbadges.object_id AS oid FROM ^badges,^userbadges WHERE ^badges.badge_slug=^userbadges.badge_slug AND ^userbadges.user_id=#',
					$userid
				)
			);
			
			$fields = array();
			
			if(count($result) > 0) {
				
				// count badges
				
				$badges = array();
				
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

					$output = '
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
						
						if(qa_opt('badge_show_source_posts')) {
							$oids = @$info['oid'];
						}
						else $oids = null;
						
						$var = qa_opt('badge_'.$slug.'_var');
						$desc = qa_badge_desc_replace($slug,$var,$name);
						
						// badge row
						
						$output .= '
								<tr>
									<td class="badge-container">
										<div class="badge-container-badge">
											<span class="badge-'.$types.'" title="'.$desc.' ('.$typed.')">'.qa_html($name).'</span>
											<span onclick="jQuery(\'.badge-container-sources-'.$slug.'\').slideToggle()" class="badge-count'.(is_array($oids)?' badge-count-link" title="'.qa_badge_lang('badges/badge_count_click'):'').'">x&nbsp;'.$count.'</span>
										</div>';
						
						// source row(s) if any	
						if(is_array($oids)) {
							$output .= '
										<div class="badge-container-sources-'.$slug.'" style="display:none">';
							foreach($oids as $oid) {
								$post = qa_db_select_with_pending(
									qa_db_full_post_selectspec(null, $oid)
								);								
								$title=$post['title'];
								
								$anchor = '';
								
								if($post['parentid']) {
									$anchor = urlencode(qa_anchor($post['type'],$oid));
									$oid = $post['parentid'];
									$title = qa_db_read_one_value(
										qa_db_query_sub(
											'SELECT BINARY title as title FROM ^posts WHERE postid=#',
											$oid
										),
										true
									);	
								}
								
								$length = 30;
								
								$text = (qa_strlen($title) > $length ? qa_substr($title,0,$length).'...' : $title);
								
								$output .= '
											<div class="badge-source"><a href="'.qa_path_html(qa_q_request($oid,$title),NULL,qa_opt('site_url')).($anchor?'#'.$anchor:'').'">'.qa_html($text).'</a></div>';
							}
							
						}
						$output .= '
									</td>
								</tr>';
					}
					$output .= '
							</table>';
					
					$outa[] = $output;
				}

				$fields[] = array(
						'label' => implode('</td><td class="qa-form-wide-label">',$outa),
						'type' => 'static',
				);
			}

			$ok = null;
			$tags = null;
			$buttons = array();
			
			if((bool)qa_opt('badge_email_notify') && qa_get_logged_in_handle() == $handle) {
			// add badge notify checkbox

				
				if(qa_clicked('badge_email_notify_save')) {
					qa_opt('badge_email_notify_id_'.$userid, (bool)qa_post_text('badge_notify_email_me'));
					$ok = qa_badge_lang('badges/badge_notified_email_me');
				}

				$select = (bool)qa_opt('badge_email_notify_id_'.$userid);
				
				$tags = 'id="badge-form" action="'.qa_self_html().'#signature_text" method="POST"';
				
				$fields[] = array(
					'type' => 'blank',
				);
				
				$fields[] = array(
					'label' => qa_badge_lang('badges/badge_notify_email_me'),
					'type' => 'checkbox',
					'tags' => 'NAME="badge_notify_email_me"',
					'value' => $select,
				);
									
				$buttons[] = array(
					'label' => qa_lang_html('main/save_button'),
					'tags' => 'NAME="badge_email_notify_save"',
				);
			}



			return array(				
				'ok' => ($ok && !isset($error)) ? $ok : null,
				'style' => 'wide',
				'tags' => $tags,
				'title' => qa_badge_lang('badges/badges'),
				'fields'=>$fields,
				'buttons'=>$buttons,
			);
			
		}

	// badge popup notification

		function badge_notify() {
			$userid = qa_get_logged_in_userid();
			
			$result = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND notify>=1',
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
					
					$notice .= '<div class="badge-notify notify">'.qa_badge_lang('badges/badge_notify')."'".$name.'\'!&nbsp;&nbsp;'.qa_badge_lang('badges/badge_notify_profile_pre').'<a href="'.qa_path_html('user/'.qa_get_logged_in_handle()).'">'.qa_badge_lang('badges/badge_notify_profile').'</a><div class="notify-close" onclick="jQuery(this).parent().hide(\'slow\')">x</div></div>';
				}

				$notice .= '</div>';
				
				// remove notification flag
				
				qa_db_query_sub(
					'UPDATE ^userbadges SET notify=0 WHERE user_id=# AND notify>=1',
					$userid
				);
				$this->output($notice);
			}
		}

	// etc
		
		function trigger_notify($message) {
			$notice = '<div class="notify-container"><div class="badge-notify notify">'.qa_badge_lang('badges/badge_notify')."'".$message.'\'!&nbsp;&nbsp;'.qa_badge_lang('badges/badge_notify_profile_pre').'<a href="/user/'.qa_get_logged_in_handle().'">'.qa_badge_lang('badges/badge_notify_profile').'</a><div class="notify-close" onclick="jQuery(this).parent().fadeOut()">x</div></div></div>';
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
	
