<?php
/*
Plugin Name: Plugin Notes
Plugin URI: http://wordpress.org/extend/plugins/plugin-notes/
Description: Allows you to add notes to plugins. Simple and sweet.
Author: Mohammad Jangda
Version: 1.1
Author URI: http://digitalize.ca/

Copyright 2009-2010 Mohammad Jangda

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

define( 'PLUGIN_NOTES_VERSION', 1.1 );

// Localization, what?!
$plugin_notes_plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'plugin_notes','wp-content/plugins/'.$plugin_notes_plugin_dir, $plugin_notes_plugin_dir);

class plugin_notes {
	
	var $notes = array();
	var $notes_option = 'plugin_notes';
	var $nonce_added = false;
	
	function plugin_notes() {
		$this->__construct();
	}
	
	/**
	 * Object constructor for plugin
	 */
	function __construct() {

		$this->notes = $this->_get_notes();
		
		// Add notes to plugin row
		add_filter('plugin_row_meta', array(&$this, 'plugin_row_meta'), 10, 4);
		
		// Add js and css files		
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));

		// Add ajax action to edit posts
		add_action('wp_ajax_plugin_notes_edit_comment', array(&$this, 'ajax_edit_plugin_note' ));
		
	}
	
	/**
	 * Adds necessary javascript and css files
	 */
	function enqueue_scripts() {
		global $pagenow;
		
		if($pagenow == "plugins.php") {
			wp_enqueue_script('plugin-notes', plugins_url('plugin-notes/plugin-notes.js'), array('jquery', 'wp-ajax-response'), PLUGIN_NOTES_VERSION, true);
			wp_enqueue_style('plugin-notes', plugins_url('plugin-notes/plugin-notes.css'), false, PLUGIN_NOTES_VERSION, 'all');
			?>
			<script type="text/javascript">
			if(!i18n || i18n == 'undefined') var i18n = {};
			i18n.plugin_notes = {};
			i18n.plugin_notes.confirm_delete = "<?php _e('Are you sure you want to delete this note?', 'plugin_notes'); ?>";
			</script>
			<?php
		}
	}
	
	/**
	 * Adds a nonce to the plugin page so we don't get nasty people doing nasty things
	 */
	function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $context ) {
		$note = isset( $this->notes[$plugin_file] ) ? $this->notes[$plugin_file] : array();
		$this->_add_plugin_note($note, $plugin_data, $plugin_file);
		
		if(!$this->nonce_added) {
			?><input type="hidden" name="wp-plugin_notes_nonce" value="<?php echo wp_create_nonce('wp-plugin_notes_nonce'); ?>" /><?php
			$this->nonce_added = true;
		}
		
		return $plugin_meta;
	}
	
	/**
	 * Outputs pluging note for the specified plugin
	 */
	function _add_plugin_note ( $note = null, $plugin_data, $plugin_file ) {
		$plugin_safe_name = $this->_get_plugin_safe_name($plugin_data['Name']);
		$actions = array();
		
		if(is_array($note) && !empty($note['note'])) {
			$note_class = 'wp-plugin_note_box';
			$note_text = $note['note'];
			$note_author = get_userdata($note['user']);
			$note_date = $note['date'];
			$actions[] = '<a href="#" onclick="edit_plugin_note(\''. $plugin_safe_name .'\'); return false;" id="wp-plugin_note_edit" class="edit">'. __('Edit note', 'plugin-notes') .'</a>';
			$actions[] = '<a href="#" onclick="delete_plugin_note(\''. $plugin_safe_name .'\'); return false;" id="wp-plugin_note_delete" class="delete">'. __('Delete note', 'plugin-notes') .'</a>';
		} else {
			$note_class = 'wp-plugin_note_box_blank';
			$actions[] = '<a href="#" onclick="edit_plugin_note(\''. $plugin_safe_name .'\'); return false;">'. __('Add plugin note', 'plugin-notes') .'</a>';
			$note_text = '';
			$note_author = null;
			$note_date = '';
		}
		?>
		<div class="<?php echo $note_class ?>">
			<?php $this->_add_plugin_form($note_text, $plugin_safe_name, $plugin_file, true); ?>
			
			<div id="wp-plugin_note_<?php echo $plugin_safe_name ?>" ondblclick="edit_plugin_note('<?php echo $plugin_safe_name ?>');" title="Double click to edit me!">
				<span class="wp-plugin_note"><?php echo nl2br( $note_text ); ?></span>
				<span class="wp-plugin_note_user"><?php echo ( $note_author ) ? $note_author->display_name : ''; ?></span>
				<span class="wp-plugin_note_date"><?php echo $note_date ?></span>
				<span class="wp-plugin_note_actions">
					<?php echo implode(' | ', $actions); ?>
					<span class="waiting" style="display: none;"><img alt="<?php _e('Loading...', 'plugin-notes') ?>" src="images/wpspin_light.gif" /></span>
				</span>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Outputs form to add/edit/delete a plugin note
	 */
	function _add_plugin_form ( $note = '', $plugin_safe_name, $plugin_file, $hidden = true ) {
		$plugin_form_style = ($hidden) ? 'style="display:none"' : '';
		?>
			<div id="wp-plugin_note_form_<?php echo $plugin_safe_name ?>" class="wp-plugin_note_form" <?php echo $plugin_form_style ?>>
				<textarea name="wp-plugin_note_text_<?php echo $plugin_safe_name ?>" cols="40" rows="3"><?php echo $note; ?></textarea>
				<span class="wp-plugin_note_error error" style="display: none;"></span>
				<span class="wp-plugin_note_edit_actions">
					<?php // TODO: Unobtrusify the javascript ?>
					<a href="#" onclick="save_plugin_note('<?php echo $plugin_safe_name ?>');return false;" class="button-primary"><?php _e('Save', 'plugin-notes') ?></a>
					<a href="#" onclick="cancel_plugin_note('<?php echo $plugin_safe_name ?>');return false;" class="button"><?php _e('Cancel', 'plugin-notes') ?></a>
					<span class="waiting" style="display: none;"><img alt="<?php _e('Loading...', 'plugin-notes') ?>" src="images/wpspin_light.gif" /></span>
				</span>
				<input type="hidden" name="wp-plugin_note_slug_<?php echo $plugin_safe_name ?>" value="<?php echo $plugin_file ?>" />
			</div>
		<?php
	}
	/**
	 * Returns a cleaned up version of the plugin name, i.e. it's slug
	 */
	function _get_plugin_safe_name ( $name ) {
		return sanitize_title($name);
	}
	
	/**
	 * Function that handles editing of the plugin via AJAX
	 */
	function ajax_edit_plugin_note ( ) {
		global $current_user;
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['_nonce'], 'wp-plugin_notes_nonce')) {
			die( __( 'Don\'t think you\'re supposed to be here...', 'plugin_notes' ) );
			return;
		}
		
		$current_user = wp_get_current_user();
		
		if (current_user_can('activate_plugins')) {
			// Get notes array
			$notes = $this->_get_notes();
			$note_text = trim(strip_tags( stripslashes( $_POST['plugin_note'] ), '<p><b><i><em><u><strong><a><img>'));
			// TODO: Escape this?
			$plugin = $_POST['plugin_slug'];
			$plugin_name = esc_html($_POST['plugin_name']);
			
			$response_data = array();
			$response_data['slug'] = $plugin;
			
			if($note_text) {
				
				$date_format = get_option('date_format');
				
				// setup the note data				
				$note = array();
				$note['date'] = date($date_format);
				$note['user'] = $current_user->ID;
				$note['note'] = $note_text;
				
				// Add new note to notes array
				$notes[$plugin] = $note;
				
				$response_data = array_merge($response_data, $note);
				$response_data['action'] = 'edit';
			} else {
				// no note sent, so let's delete it
				if(!empty($notes[$plugin])) unset($notes[$plugin]);
				$response_data['action'] = 'delete';
			}
			// Save the new notes array
			$this->_set_notes($notes);
			
		} else {
			// user can't edit plugins, so throw error
			die( __( 'Sorry, you do not have permission to edit plugins.', 'plugin_notes' ) );
			return;
		}
		
		// Prepare response
		$response = new WP_Ajax_Response();
		
		ob_start();
			$this->_add_plugin_note($note, array('Name' => $plugin_name), $plugin);
			$plugin_note_content = ob_get_contents();
		ob_end_clean();
		
		$response->add( array(
			'what' => 'plugin_note',
			'id' => $plugin,
			'data' => $plugin_note_content,
			'action' => ($note_text) ? 'edit' : 'delete',
		));
		$response->send();
		
		return; 
	}
	
	/* Some sweet function to get/set go!*/
	function _get_notes() { return get_option($this->notes_option);	}
	function _set_notes($notes) { return update_option($this->notes_option, $notes); }
}

add_action( 'admin_init', 'plugin_notes_init' );

function plugin_notes_init() {
	/** Let's get the plugin rolling **/
	// Create new instance of the plugin_notes object
	global $plugin_notes;
	$plugin_notes = new plugin_notes();
}
