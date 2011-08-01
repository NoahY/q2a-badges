<?php

	class qa_html_theme_layer extends qa_html_theme_base {

	// theme replacement functions

		function head_script() {
			qa_html_theme_base::head_script();
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
		function head_css()
		{
			qa_html_theme_base::head_css();
			$this->output('
			<style>
				.notify-container {
					left: 0;
					right: 0;
					top: 0;
					padding: 0;
					position: fixed;
					width: 100%;
					z-index: 100;
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
				.badge-bronze,.badge-silver, .badge-gold {
					margin-right:4px;
					cursor:pointer;
					color: #000;
					font-weight:bold;
					text-align:center;
					border-radius:4px;
					width:120px;
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
				.badge-desc {
					padding-left:8px;
				}				
			</style>');
		}

		function body() {
			$this->output('<BODY');
			$this->body_tags();
			$this->output('>');

			$this->badge_notify();  // <- this is our addition
			
			if (isset($this->content['body_header']))
				$this->output_raw($this->content['body_header']);
				
			$this->body_content();
			
			if (isset($this->content['body_footer']))
				$this->output_raw($this->content['body_footer']);
				
			$this->output('</BODY>');
		}

		function form_body($form)
		{
			qa_html_theme_base::form_body($form);

			if(preg_match('/^\.\.\/user\//',qa_self_html())) { // <- this is our addition
				$this->user_badge_form();
			}
		}

	// worker functions

		function badge_notify() {
			$userid = qa_get_logged_in_userid();
			
			$result = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND notify=1',
					$userid
				),
				true
			);
			if(count($result) > 0) {

				$notice = '<div class="notify-container">';

				// populate notification list
				foreach($result as $name) {
					$notice .= '<div class="badge-notify notify">'.qa_badge_lang('badges/badge_notify')."'".qa_badge_lang('badges/'.$name).'\'!<div class="notify-close" onclick="$(this).parent().hide(\'slow\')">x</div></div>';
				}

				$notice .= '</div>';
				
				// remove notification flag
				
				qa_db_query_sub(
					'UPDATE ^userbadges SET notify=0 WHERE user_id=# AND notify=1',
					$userid
				);
			}
			$this->output($notice);
		}
		
		function trigger_notify($message) {
			$notice = '<div class="notify-container"><div class="badge-notify notify">'.$message.'<div class="notify-close" onclick="$(this).parent().fadeOut()">x</div></div></div>';
			$this->output($notice);
		}
		
		function priviledge_notify() { // gained priviledge
		}

		function user_badge_form() {
			$result = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT badge_name FROM ^badges,^userbadges WHERE ^badges.badge_slug=^userbadges.badge_slug AND ^userbadges.user_id=#',
					qa_get_logged_in_userid()
				)
			);
			if(count($result) > 0) {
				
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
	
