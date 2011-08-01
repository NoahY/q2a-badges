<?php
	class qa_badge_admin {
		
		function allow_template($template)
		{
			return ($template!='admin');
		}

		function admin_form(&$qa_content)
		{

		//	Process form input

			$ok = null;

			$badges = qa_get_badge_list();
			if (qa_clicked('badge_rebuild_button')) {
				qa_import_badge_list(true);
				$ok = qa_lang('badges/list_rebuilt');
			}
			else if (qa_clicked('badge_reset_button')) {
				foreach ($badges as $slug => $info) {
					if(isset($info['var'])) {
						qa_opt('badge_'.$slug.'_var',$info['var']);
					}
					qa_opt('badge_'.$slug.'_name',$info['name']);
					qa_opt('badge_'.$slug.'_enabled','1');
				}
			}
			else if(qa_clicked('badge_save_settings')) {
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
						$qa_lang_default['badges'][$slug] = qa_opt('badge_'.$slug.'_name');
					}
						
					
				}
				qa_opt('badge_notify_time', (int)qa_post_text('badge_notify_time'));			
				qa_opt('badge_active', (bool)qa_post_text('badge_active_check'));			
				$ok = qa_lang('badges/badge_admin_saved');
			}
			
		//	Create the form for display
			
			
			$fields = array();
			
			$fields[] = array(
				'label' => qa_lang('badges/badge_admin_activate'),
				'tags' => 'NAME="badge_active_check"',
				'value' => qa_opt('badge_active'),
				'type' => 'checkbox',
			);

			if(qa_opt('badge_active')) {

				$fields[] = array(
						'label' => qa_lang('badges/active_badges').':',
						'type' => 'static',
				);


				foreach ($badges as $slug => $info) {
					if(!qa_opt('badge_'.$slug.'_name')) qa_opt('badge_'.$slug.'_name',$info['name']);
					$name = qa_opt('badge_'.$slug.'_name');
					
					$type = qa_get_badge_type($info['type']);
					$types = $type['slug'];
					
					if(isset($info['var'])) {
						$htmlout = str_replace('#','<input type="text" name="badge_'.$slug.'_var" size="4" value="'.qa_opt('badge_'.$slug.'_var').'">',$info['desc']);
						$fields[] = array(
								'type' => 'static',
								'note' => '<table><tr><td><input type="checkbox" name="badge_'.$slug.'_enabled"'.(qa_opt('badge_'.$slug.'_enabled') !== '0' ? ' checked':'').'></td><td><input type="text" name="badge_'.$slug.'_edit" id="badge_'.$slug.'_edit" style="display:none" size="16" onblur="badgeEdit(\''.$slug.'\',true)" value="'.$name.'"><span id="badge_'.$slug.'_badge" class="badge-'.$types.'" onclick="badgeEdit(\''.$slug.'\')">'.$name.'</span></td><td>'.$htmlout.'</td></tr></table>'
						);
					}
					else {
						$fields[] = array(
								'type' => 'static',
								'note' => '<table><tr><td><input type="checkbox" name="badge_'.$slug.'_enabled"'.(qa_opt('badge_'.$slug.'_enabled') !== '0' ? ' checked':'').'></td><td><input type="text" name="badge_'.$slug.'_edit" id="badge_'.$slug.'_edit" style="display:none" size="16" onblur="badgeEdit(\''.$slug.'\',true)" value="'.$name.'"><span id="badge_'.$slug.'_badge" class="badge-'.$types.'" onclick="badgeEdit(\''.$slug.'\')">'.$name.'</span></td><td>'.$info['desc'].'</td></tr></table>'
						);
					}
				}
				$fields[] = array(
						'label' => qa_lang('badges/notify_time').':',
						'type' => 'number',
						'value' => qa_opt('badge_notify_time'),
						'tags' => 'NAME="badge_notify_time"',
						'note' => '<em>'.qa_lang('badges/notify_time_desc').'</em>',
				);
				
			}
			
			return array(
				'ok' => ($ok && !isset($error)) ? $ok : null,
				
				'fields' => $fields,
				
				'buttons' => array(
					array(
						'label' => qa_lang('badges/badge_recreate'),
						'tags' => 'NAME="badge_rebuild_button"',
						'note' => '<br/><em>'.qa_lang('badges/badge_recreate_desc').'</em><br/><br/>',
					),
					array(
						'label' => qa_lang('badges/reset_values'),
						'tags' => 'NAME="badge_reset_button"',
						'note' => '<br/><em>'.qa_lang('badges/reset_values_desc').'</em><br/><br/>',
					),
					array(
						'label' => qa_lang('badges/save_settings'),
						'tags' => 'NAME="badge_save_settings"',
						'note' => '<br/><em>'.qa_lang('badges/save_settings_desc').'</em><br/><br/>',
					),
				),
			);
		}
	}
