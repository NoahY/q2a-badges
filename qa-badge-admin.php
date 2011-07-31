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
				}
			}
			else if(qa_clicked('badge_save_settings')) {
				foreach ($badges as $slug => $info) {
					if(isset($info['var']) && qa_post_text('badge_'.$slug.'_var')) {
						qa_opt('badge_'.$slug.'_var',qa_post_text('badge_'.$slug.'_var'));
					}
				}
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
					$type = qa_get_badge_type($info['type']);
					$types = $type['slug'];
					if(isset($info['var'])) {
						$htmlout = str_replace('#','<input type="text" name="badge_'.$slug.'_var" size="4" value="'.qa_opt('badge_'.$slug.'_var').'">',$info['desc']);
						$fields[] = array(
								'type' => 'static',
								'note' => '<span class="badge-'.$types.'">'.$info['name'].'</span> - '.$htmlout
						);
					}
					else {
						$fields[] = array(
								'type' => 'static',
								'note' => '<span class="badge-'.$types.'">'.$info['name'].'</span> - '.$info['desc']
						);
					}
				}
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
