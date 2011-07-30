<?php

	class qa_html_theme_layer extends qa_html_theme_base {

	// theme replacement functions

		function body() {
			$this->output('<BODY');
			$this->body_tags();
			$this->output('>');

			$this->badge_alert_user();  // <- this is our addition
			
			if (isset($this->content['body_header']))
				$this->output_raw($this->content['body_header']);
				
			$this->body_content();
			
			if (isset($this->content['body_footer']))
				$this->output_raw($this->content['body_footer']);
				
			$this->output('</BODY>');
		}

		function form_body($form)
		{
			foreach($form['fields'] as $f => $q){
				error_log($f.' '.$q);
			}
			$columns=$this->form_columns($form);
			
			if ($columns)
				$this->output('<TABLE CLASS="qa-form-'.$form['style'].'-table">');
			
			$this->form_ok($form, $columns);
			$this->form_fields($form, $columns);
			$this->form_buttons($form, $columns);

			if ($columns)
				$this->output('</TABLE>');

			$this->form_hidden($form);

			if(preg_match('/^\.\.\/user\//',qa_self_html())) { // <- this is our addition
				$this->badge_form();
			}
		}

	// worker functions

		function badge_alert_user() {
			$result = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT ^badges.badge_name FROM ^userbadges,^badges WHERE ^badges.badge_id=^userbadges.badge_id AND ^userbadges.user_id=# AND ^userbadges.notify=1',
					qa_get_logged_in_userid()
				),
				true
			);
			if(count(result) > 0) {
				foreach($result as $name) {
					$notice .= '<div class="badge-notify notify">Congratulations!  You\'ve earned a badge \''.$name.'\'</div>';
				}
				qa_db_query_sub(
					'UPDATE ^userbadges SET notify=0 WHERE user_id=# AND notify=1',
					qa_get_logged_in_userid()
				);
			}
			$this->output($notice);
		}

		function badge_form() {
			$result = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT ^badges.badge_name FROM ^userbadges,^badges WHERE ^badges.badge_id=^userbadges.badge_id AND ^userbadges.user_id=#',
					qa_get_logged_in_userid()
				),
				true
			);
			if(count(result) > 0) {
				
				$output = '
			<h2>Badges</h2>
			<table class="qa-form-wide-table">
				<tbody>';
				// count badges
				
				$badges;
				
				foreach($result as $name) {
					
					if($badges[$name]) $badges[$name]++;
					else $badges[$name] = 1;
					
				}
				
				foreach($badges as $name => $count) {
					$output .= '
					<tr>
						<td class="qa-form-wide-label">
							<span class="badge-name">'.$name.'</span>
						</td>
						<td class="qa-form-wide-data">
							<span class="badge-count">x&nbsp;'.$count.'</span>
						</td>
					</tr>';
				}
				$output .= '
				</tbody>
			</table>';
				$this->output($output);
			}
		}
	}
	
