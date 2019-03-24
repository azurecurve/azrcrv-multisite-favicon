<?php
/**
 * ------------------------------------------------------------------------------
 * Plugin Name: Multisite Favicon
 * Description: Allows Setting of Separate Favicon For Each Site In A Multisite Installation.
 * Version: 1.0.0
 * Author: azurecurve
 * Author URI: https://development.azurecurve.co.uk/classicpress-plugins/
 * Plugin URI: https://development.azurecurve.co.uk/classicpress-plugins/multisite-favicon
 * Text Domain: multisite-favicon
 * Domain Path: /languages
 * ------------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.html.
 * ------------------------------------------------------------------------------
 */

// include plugin menu
require_once(dirname( __FILE__).'/pluginmenu/menu.php');

// Prevent direct access.
if (!defined('ABSPATH')){
	die();
}

/**
 * Setup registration activation hook, actions, filters and shortcodes.
 *
 * @since 1.0.0
 *
 */
// add actions
register_activation_hook(__FILE__, 'azrcrv_msfi_set_default_options');

// add actions
add_action('admin_menu', 'azrcrv_msfi_create_admin_menu');
add_action('admin_post_azrcrv_msfi_save_options', 'azrcrv_msfi_save_options');
add_action('network_admin_menu', 'azrcrv_msfi_create_network_admin_menu');
add_action('network_admin_edit_azrcrv_msfi_save_network_options', 'azrcrv_msfi_save_network_options');
add_action('wp_head', 'azurecurve_msfi_load_favicon');

// add filters
add_filter('plugin_action_links', 'azrcrv_msfi_add_plugin_action_link', 10, 2);

// add shortcodes
add_shortcode('shortcode', 'shortcode_function');

/**
 * Set default options for plugin.
 *
 * @since 1.0.0
 *
 */
function azrcrv_msfi_set_default_options($networkwide){
	
	$new_options = array(
				'min_length' => 10,
				'max_length' => 500,
				'mod_length' => 250,
				'use_network' => 1
			);
	
	// set defaults for multi-site
	if (function_exists('is_multisite') && is_multisite()){
		// check if it is a network activation - if so, run the activation function for each blog id
		if ($networkwide){
			global $wpdb;

			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			$original_blog_id = get_current_blog_id();

			foreach ($blog_ids as $blog_id){
				switch_to_blog($blog_id);

				if (get_option('azrcrv-msfi') === false){
					if (get_option('azc_msfi_options') === false){
						add_option('azrcrv-msfi', $new_options);
					}else{
						add_option('azrcrv-msfi', get_option('azc_msfi_options'));
					}
				}
			}

			switch_to_blog($original_blog_id);
		}else{
			if (get_option('azrcrv-msfi') === false){
				if (get_option('azc_msfi_options') === false){
					add_option('azrcrv-msfi', $new_options);
				}else{
					add_option('azrcrv-msfi', get_option('azc_msfi_options'));
				}
			}
		}
		if (get_site_option('azrcrv-msfi') === false){
				if (get_option('azc_msfi_options') === false){
					add_option('azrcrv-msfi', $new_options);
				}else{
					add_option('azrcrv-msfi', get_option('azc_msfi_options'));
				}
		}
	}
	//set defaults for single site
	else{
		if (get_option('azrcrv-msfi') === false){
				if (get_option('azc_msfi_options') === false){
					add_option('azrcrv-msfi', $new_options);
				}else{
					add_option('azrcrv-msfi', get_option('azc_msfi_options'));
				}
		}
	}
}

/**
 * Add Multisite Favicon action link on plugins page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_msfi_add_plugin_action_link($links, $file){
	static $this_plugin;

	if (!$this_plugin){
		$this_plugin = plugin_basename(__FILE__);
	}

	if ($file == $this_plugin){
		$settings_link = '<a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=azrcrv-msfi">'.esc_html__('Settings' ,'multisite-favicon').'</a>';
		array_unshift($links, $settings_link);
	}

	return $links;
}

/**
 * Add to menu.
 *
 * @since 1.0.0
 *
 */
function azrcrv_msfi_create_admin_menu(){
	//global $admin_page_hooks;
	
	add_submenu_page("azrcrv-plugin-menu"
						,esc_html__("Multisite Favicon Settings", "multisite-favicon")
						,esc_html__("Multisite Favicon", "multisite-favicon")
						,'manage_options'
						,'azrcrv-msfi'
						,'azrcrv_msfi_display_options');
}

/**
 * Display Settings page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_msfi_display_options(){
	if (!current_user_can('manage_options')){
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'multisite-favicon'));
    }
	
	// Retrieve plugin configuration options from database
	$options = get_option('azrcrv-msfi');
	?>
	<div id="azrcrv-msfi-general" class="wrap">
		<fieldset>
			<h2><?php echo esc_html(get_admin_page_title()); ?></h2>
			<?php if(isset($_GET['settings-updated'])){ ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e('Site settings have been saved.', 'multisite-favicon') ?></strong></p>
				</div>
			<?php } ?>
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="azrcrv_msfi_save_options" />
				<input name="page_options" type="hidden" value="default_path, default_favicon" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field('azrcrv-msfi', 'azrcrv-msfi-nonce'); ?>
				<table class="form-table">
				<tr><td colspan=2>
					<p><?php esc_html_e('Set the path for where you will be storing the favicon; default is to the plugin/images folder.', 'multisite-favicon'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="width"><?php esc_html_e('Path', 'multisite-favicon'); ?></label></th><td>
					<input type="text" name="default_path" value="<?php echo esc_html(stripslashes($options['default_path'])); ?>" class="large-text" />
					<p class="description"><?php esc_html_e('Set folder for favicon', 'multisite-favicon'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="width"><?php esc_html_e('Favicon', 'multisite-favicon'); ?></label></th><td>
					<input type="text" name="default_favicon" value="<?php echo esc_html(stripslashes($options['default_favicon'])); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e('Set favicon name', 'multisite-favicon'); ?></p>
				</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary"/>
			</form>
		</fieldset>
	</div>
	<?php
}

/**
 * Save settings.
 *
 * @since 1.0.0
 *
 */
function azrcrv_msfi_save_options(){
	// Check that user has proper security level
	if (!current_user_can('manage_options')){
		wp_die(esc_html__('You do not have permissions to perform this action', 'multisite-favicon'));
	}
	
	// Check that nonce field created in configuration form is present
	if (! empty($_POST) && check_admin_referer('azrcrv-msfi', 'azrcrv-msfi-nonce')){
		// Retrieve original plugin options array
		$options = get_option('azrcrv-msfi');
		
		$option_name = 'default_path';
		if (isset($_POST[$option_name])){
			$options[$option_name] = sanitize_text_fields($_POST[$option_name]);
		}
		
		$option_name = 'default_favicon';
		if (isset($_POST[$option_name])){
			$options[$option_name] = sanitize_text_fields($_POST[$option_name]);
		}
		
		// Store updated options array to database
		update_option('azrcrv-msfi', $options);
		
		// Redirect the page to the configuration form that was processed
		wp_redirect(add_query_arg('page', 'azrcrv-msfi&settings-updated', admin_url('admin.php')));
		exit;
	}
}

/**
 * Add to Network menu.
 *
 * @since 1.0.0
 *
 */
function azrcrv_msfi_create_network_admin_menu(){
	if (function_exists('is_multisite') && is_multisite()){
		add_submenu_page(
						'settings.php'
						,esc_html__("Multisite Favicon Settings", "multisite-favicon")
						,esc_html__("Multisite Favicon", "multisite-favicon")
						,'manage_network_options'
						,'azrcrv-msfi'
						,'azrcrv_msfi_network_settings'
						);
	}
}

/**
 * Display network settings.
 *
 * @since 1.0.0
 *
 */
function azrcrv_msfi_network_settings(){
	if(!current_user_can('manage_network_options')) wp_die(esc_html__('You do not have permissions to perform this action', 'multisite-favicon'));
	$options = get_site_option('azrcrv-msfi');

	?>
	<div id="azrcrv-msfi-general" class="wrap">
		<fieldset>
			<h2><?php echo esc_html(get_admin_page_title()); ?></h2>
			<form action="edit.php?action=update_azc_msfi_network_options" method="post">
				<input type="hidden" name="action" value="save_azc_msfi_network_options" />
				<input name="page_options" type="hidden" value="default_path, default_favicon" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field('azrcrv-msfi', 'azrcrv-msfi-nonce'); ?>
				<table class="form-table">
				<tr><td colspan=2>
					<p><?php esc_html_e('Set the default path for where you will be storing the favicons; default is to the plugin/images folder.', 'multisite-favicon'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="width"><?php esc_html_e('Default Path', 'multisite-favicon'); ?></label></th><td>
					<input type="text" name="default_path" value="<?php echo esc_html(stripslashes($options['default_path'])); ?>" class="large-text" />
					<p class="description"><?php esc_html_e('Set default folder for favicons', 'multisite-favicon'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="width"><?php esc_html_e('Default Favicon', 'multisite-favicon'); ?></label></th><td>
					<input type="text" name="default_favicon" value="<?php echo esc_html(stripslashes($options['default_favicon'])); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e('Set default favicon used when no img attribute set', 'multisite-favicon'); ?></p>
				</td></tr>
				</table>
				<input type="submit" value="Save Changes" class="button-primary" />
			</form>
		</fieldset>
	</div>
	<?php
}

/**
 * Save network settings.
 *
 * @since 1.0.0
 *
 */
function azrcrv_msfi_save_network_options(){     
	if(!current_user_can('manage_network_options')){
		wp_die(esc_html__('You do not have permissions to perform this action', 'multisite-favicon'));
	}
	
	if (! empty($_POST) && check_admin_referer('azrcrv-msfi', 'azrcrv-msfi-nonce')){
		// Retrieve original plugin options array
		$options = get_site_option('azc_msfi_options');

		$option_name = 'default_path';
		if (isset($_POST[$option_name])){
			$options[$option_name] = sanitize_text_fields($_POST[$option_name]);
		}

		$option_name = 'default_favicon';
		if (isset($_POST[$option_name])){
			$options[$option_name] = sanitize_text_fields($_POST[$option_name]);
		}
		
		update_site_option('azc_msfi_options', $options);

		wp_redirect(network_admin_url('settings.php?page=azurecurve-multisite-favicon'));
		exit;  
	}
}

function azurecurve_msfi_load_favicon(){
	$options = get_option('azc_msfi_options');
	$network_options = get_site_option('azc_msfi_options');
	
	$icon_url = '';
	if (strlen($options['default_path']) > 0 and strlen($options['default_favicon']) > 0){
		$icon_url = stripslashes($options['default_path']).stripslashes($options['default_favicon']);
	}elseif (strlen($options['default_path']) > 0 and strlen($options['default_favicon']) == 0 and strlen($network_options['default_favicon']) > 0){
		$icon_url = stripslashes($options['default_path']).stripslashes($network_options['default_favicon']);
	}elseif (strlen($options['default_path']) == 0 and strlen($options['default_favicon']) > 0 and strlen($network_options['default_path']) > 0){
		$icon_url = stripslashes($network_options['default_path']).stripslashes($options['default_favicon']);
	}elseif (strlen($options['default_path']) == 0 and strlen($options['default_favicon']) == 0 and strlen($network_options['default_path']) > 0 and strlen($network_options['default_favicon']) > 0){
		$icon_url = stripslashes($network_options['default_path']).stripslashes($network_options['default_favicon']);
	}

	if (strlen($icon_url) > 0){
		echo '<link rel="shortcut icon" href="'.esc_url($icon_url).'" />';
	}
	
}

?>