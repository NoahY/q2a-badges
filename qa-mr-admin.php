<?php
	class qa_mr_admin {
		
		function allow_template($template)
		{
			return ($template!='admin');
		}

		function option_default($option) {
			
			switch($option) {
				default:
					return false;
			}
			
		}

		function admin_form(&$qa_content)
		{

		//	Process form input

			$ok = null;

            if (qa_clicked('mention_replace_save')) {
                qa_opt('mention_replace_enable',qa_post_text('mention_replace_enable'));
                $ok = 'Settings Saved.';
            }
            
                    
        // Create the form for display

            
            $fields = array();
            
            $fields[] = array(
                'label' => 'Enable mention replacement',
                'tags' => 'NAME="mention_replace_enable"',
                'value' => qa_opt('mention_replace_enable'),
                'type' => 'checkbox',
            );
 

            return array(           
                'ok' => ($ok && !isset($error)) ? $ok : null,
                    
                'fields' => $fields,
             
                'buttons' => array(
                    array(
                        'label' => 'Save',
                        'tags' => 'NAME="mention_replace_save"',
                    )
                ),
            );
        }
    }

