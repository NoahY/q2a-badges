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

			if (qa_clicked('badge_rebuild_button')) {
				qa_import_badge_list(true);
				$ok = 'Badge list rebuilt.';
			}
			else if(qa_clicked('badge_save_settings')) {
				qa_opt('badge_active', (bool)qa_post_text('badge_active_check'));			
				$ok = 'Options saved.';
			}
			
		//	Create the form for display
			
			$badges = qa_get_badge_list();
			
			$fields = array();
			
			$fields[] = array(
				'label' => 'Activate badges',
				'tags' => 'NAME="badge_active_check"',
				'value' => qa_opt('badge_active'),
				'type' => 'checkbox',
			);

			if(qa_opt('badge_active')) {

				$fields[] = array(
						'label' => 'Active Badges:',
						'type' => 'static',
				);


				foreach ($badges as $info) {
					$fields[] = array(
							'type' => 'static',
							'note' => '<b>'.$info['name'].':</b> '.$info['desc'],
					);
				}
			}
			
			return array(
				'ok' => ($ok && !isset($error)) ? $ok : null,
				
				'fields' => $fields,
				
				'buttons' => array(
					array(
						'label' => 'Recreate Badge List',
						'tags' => 'NAME="badge_rebuild_button"',
						'note' => '<br/><em>DELETE and rebuild list</em><br/><br/>',
					),
					array(
						'label' => 'Save Changes',
						'tags' => 'NAME="badge_save_settings"',
					),
				),
			);
		}
	}
