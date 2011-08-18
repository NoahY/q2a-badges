<?php

	class qa_badge_page {
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}
		
		function suggest_requests() // for display in admin interface
		{	
			return array(
				array(
					'title' => qa_badge_lang('badges/badges'),
					'request' => 'badges',
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		function match_request($request)
		{
			if ($request=='badges')
				return true;

			return false;
		}

		function option_default($option) {
			
			$badges = qa_get_badge_list();

			$slug = preg_replace('/badge_(.+)_.+/',"$1",$option);
			
			switch($option) {
				case 'badge_'.$slug.'_name':
					return qa_badge_lang('badges/badge_'.$slug);
				case 'badge_'.$slug.'_var':
					return $badges[$slug]['var'];
				case 'badge_'.$slug.'_enabled':
					return '0';
				case 'badge_notify_time':
					return 0;
				case 'badge_email_subject':
					return '['.qa_opt('site_title').'] ';
				case 'badge_email_subject':
					return 'Dear ^handle,\n\nYou have earned a "^badge_name" badge from ['.qa_opt('site_title').']!  Please log in and visit your profile:\n\n^profile_url';
				default:
					return false;
			}
			
		}
		
		function process_request($request)
		{
			$qa_content=qa_content_prepare();

			$qa_content['title']=qa_badge_lang('badges/badge_list_title');

			$badges = qa_get_badge_list();
			
			$totalawarded = 0;
			
			$qa_content['custom']='<em>'.qa_badge_lang('badges/badge_list_pre').'</em><br />';
			$qa_content['custom2']='<table cellspacing="20">';
			$c = 2;
			
			$result = qa_db_read_all_assoc(
				qa_db_query_sub(
					'SELECT user_id,badge_slug  FROM ^userbadges'
				)
			);
			
			$count = array();
			
			foreach($result as $r) {
				if(qa_opt('badge_'.$r['badge_slug'].'_enabled') == '0') continue;
				if(isset($count[$r['badge_slug']][$r['user_id']])) $count[$r['badge_slug']][$r['user_id']]++;
				else $count[$r['badge_slug']][$r['user_id']] = 1;
				$totalawarded++;
				if(isset($count[$r['badge_slug']]['count'])) $count[$r['badge_slug']]['count']++;
				else $count[$r['badge_slug']]['count'] = 1;
			}
			

			foreach($badges as $slug => $info) {
				if(qa_opt('badge_'.$slug.'_enabled') == '0') continue;
				if(!qa_opt('badge_'.$slug.'_name')) qa_opt('badge_'.$slug.'_name',qa_badge_lang('badges/'.$slug));
				$name = qa_opt('badge_'.$slug.'_name');
				$var = qa_opt('badge_'.$slug.'_var');
				$desc = qa_badge_desc_replace($slug,$var,$name);
				$type = qa_get_badge_type($info['type']);
				$types = $type['slug']; 
				$typen = $type['name']; 
				$qa_content['custom'.++$c]='<tr><td class="badge-entry"><div class="badge-entry-badge"><span class="badge-'.$types.'" title="'.$typen.'">'.$name.'</span>&nbsp;<span class="badge-entry-desc">'.$desc.'</span>'.(isset($count[$slug])?'&nbsp;<span title="'.$count[$slug]['count'].' '.qa_badge_lang('badges/awarded').'" class="badge-count-link" onclick="jQuery(\'#badge-users-'.$slug.'\').slideToggle()">x'.$count[$slug]['count'].'</span>':'').'</div>';
				
				// source users

				if(qa_opt('badge_show_source_users') && isset($count[$slug])) {
					
					$users = array();
					
					require_once QA_INCLUDE_DIR.'qa-app-users.php';

					$qa_content['custom'.$c] .='<div style="display:none" id="badge-users-'.$slug.'" class="badge-users">';
					foreach($count[$slug] as $uid => $ucount) {
						if($uid == 'count') continue;
						
						if (QA_FINAL_EXTERNAL_USERS) {
							$handles=qa_get_public_from_userids(array($uid));
							$handle=@$handles[$uid];
						} 
						else {
							$useraccount=qa_db_select_with_pending(
								qa_db_user_account_selectspec($uid, true)
							);
							$handle=@$useraccount['handle'];
						}
						
						if(!$handle) continue;
						
						$users[] = '<a href="'.qa_path_html('user/'.$handle).'">'.$handle.($ucount>1?' x'.$ucount:'').'</a>';
					}
					$qa_content['custom'.$c] .= implode('<br/>',$users).'</div>';
				}
				$qa_content['custom'.$c] .= '</td></tr>';
			}
			
			
			$qa_content['custom'.++$c]='<tr><td class="badge-entry"><span class="total-badges">'.count($badges).' '.qa_badge_lang('badges/badges_total').'</span>'.($totalawarded > 0 ? ', <span class="total-badge-count">'.$totalawarded.' '.qa_badge_lang('badges/awarded_total').'</span>':'').'</td></tr></table>';

			if(isset($qa_content['navigation']['main']['custom-2'])) $qa_content['navigation']['main']['custom-2']['selected'] = true;

			return $qa_content;
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
	
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
