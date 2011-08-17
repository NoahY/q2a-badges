<?php

/*
        Plugin Name: Buddypress Mention Replace
        Plugin URI: 
        Plugin Description: 
        Plugin Version: 0.1
        Plugin Date: 2011-08-15
        Plugin Author: NoahY
        Plugin Author URI: 
        Plugin License: GPLv2
        Plugin Minimum Question2Answer Version: 1.3
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
			header('Location: ../../');
			exit;
	}

	qa_register_plugin_module('event', 'qa-mr-check.php','mention_replace','Mention Event');
	
	qa_register_plugin_layer('qa-mr-layer.php', 'Mention Replacement Layer');	
	
	qa_register_plugin_module('module', 'qa-mr-admin.php', 'qa_mr_admin', 'Mention Replace Admin');

/*
	Omit PHP closing tag to help avoid accidental output
*/
