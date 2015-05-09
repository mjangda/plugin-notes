<?php
/*
Plugin Name: Plugin Notes
Plugin URI: http://wordpress.org/plugins/plugin-notes/
Description: Allows you to add notes to plugins. Simple and sweet.
Author: Mohammad Jangda
Version: 1.6
Author URI: http://digitalize.ca/
Contributor: Chris Dillon
Contributor URI: http://gapcraft.com/
Contributor: Juliette Reinders Folmer
Contributor URI: http://adviesenzo.nl/
Text Domain: plugin-notes
Domain Path: /languages


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

// Avoid direct calls to this file
if ( !function_exists('add_action')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if( !class_exists('plugin_notes')) {

	class plugin_notes {

		const VERSION = 1.6;

		var $notes = array();
		var $notes_option = 'plugin_notes';
		var $nonce_added = false;

		var $allowed_tags = array(
			'a' => array(
				'href' => array(),
				'title' => array(),
				'target' => array(),
			),
			'br' => array(),
			'p' => array(),
			'b' => array(),
			'strong' => array(),
			'i' => array(),
			'em' => array(),
			'u' => array(),
			'img' => array(
				'src' => array(),
				'height' => array(),
				'width' => array(),
			),
			'hr' => array(),
		);

		var $boxcolors = array(
			'#EBF9E6', // light green
			'#F0F8E2', // lighter green
			'#F9F7E6', // light yellow
			'#EAF2FA', // light blue
			'#E6F9F9', // brighter blue
			'#F9E8E6', // light red
			'#F9E6F4', // light pink
			'#F9F0E6', // earth
			'#E9E2F8', // light purple
			'#D7DADD', // light grey
		);
		var $defaultcolor	= '#EAF2FA';

		/**
		 * Object constructor for plugin
		 *
		 * Runs on the admin_init hook
		 */
		function __construct() {

			$this->load_textdomain();

			$this->notes = $this->_get_notes();

			// Add notes to plugin row
			add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 4);

			// Add string replacement and markdown syntax filters to the note
			add_filter('plugin_notes_note', array($this, 'filter_kses'), 10, 1);
			add_filter('plugin_notes_note', array($this, 'filter_variables_replace'), 10, 3);
			if( apply_filters( 'plugin_notes_markdown', true ) ) {
				add_filter('plugin_notes_note', array($this, 'filter_markdown'), 10, 1);
			}
			add_filter('plugin_notes_note', array($this, 'filter_breaks'), 10, 1);

			// Add js and css files
			add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

			// Add helptab
			add_action('admin_head-plugins.php', array($this, 'add_help_tab'));

			// Add ajax action to edit posts
			add_action('wp_ajax_plugin_notes_edit_comment', array($this, 'ajax_edit_plugin_note'));

			// Allow filtering of the allowed html tags
			$this->allowed_tags = apply_filters('plugin_notes_allowed_tags', $this->allowed_tags);
		}

		/**
		 * Localization, what?!
		 */
		function load_textdomain() {
			load_plugin_textdomain( 'plugin-notes', false, plugin_dir_path(__FILE__) . 'languages/' );
		}


		/**
		 * Adds necessary javascript and css files
		 */
		function enqueue_scripts() {

			if($GLOBALS['pagenow'] === 'plugins.php') {
				$suffix = ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min' );

				wp_enqueue_script('plugin-notes', plugins_url('plugin-notes'.$suffix.'.js', __FILE__), array('jquery', 'wp-ajax-response'), self::VERSION, true);
				wp_enqueue_style('plugin-notes', plugins_url('plugin-notes'.$suffix.'.css', __FILE__), false, self::VERSION, 'all');
				wp_localize_script( 'plugin-notes', 'i18n_plugin_notes', $this->localize_script() );
			}
		}


		/**
		 * Localize text strings for use in javascript
		 */
		function localize_script() {
			return array(
				'confirm_delete' => esc_js(__('Are you sure you want to delete this note?', 'plugin-notes')),
				'confirm_new_template' => esc_js(__('Are you sure you want to save this note as a template?\n\rAny changes you made will not be saved to this particular plugin note.\n\r\n\rAlso beware: saving this note as the plugin notes template will overwrite any previously saved templates!', 'plugin-notes')),
				'success_save_template' => esc_js(__('New notes template saved succesfully', 'plugin-notes' )),
			);
		}

		/**
		 * Adds contextual help tab to the plugin page
		 */
		function add_help_tab() {

			$screen = get_current_screen();

			if( method_exists( $screen, 'add_help_tab' ) === true ) {
				$screen->add_help_tab( array(
					'id'      => 'plugin-notes-help', // This should be unique for the screen.
					'title'   => 'Plugin Notes',
					'content' => '
						<p>' . sprintf( __( 'The <em><a href="%s">Plugin Notes</a></em> plugin let\'s you add notes for each installed plugin. This can be useful for documenting changes you made or how and where you use a plugin in your website.', 'plugin-notes' ), 'http://wordpress.org/plugins/plugin-notes/" target="_blank" class="ext-link') . '</p>
						<p>' . sprintf( __( 'You can use <a href="%s">Markdown syntax</a> in your notes as well as HTML.', 'plugin-notes' ), 'http://daringfireball.net/projects/markdown/syntax" target="_blank" class="ext-link' ) . '</p>
						<p>' . sprintf( __( 'On top of that, you can even use a <a href="%s">number of variables</a> which will automagically be replaced, such as for example <em>%%WPURI_LINK%%</em> which would be replaced by a link to the WordPress plugin repository for this plugin. Neat isn\'t it ?', 'plugin-notes' ), 'http://wordpress.org/plugins/plugin-notes/faq/" target="_blank" class="ext-link' ) . '</p>
						<p>' . sprintf( __( 'Lastly, you can save a note as a template for new notes. If you use a fixed format for your plugin notes, you will probably like the efficiency of this.', 'plugin-notes' ), '' ) . '</p>
						<p>' . sprintf( __( 'For more information: <a href="%1$s">Plugin home</a> | <a href="%2$s">FAQ</a>', 'plugin-notes' ), 'http://wordpress.org/plugins/plugin-notes/" target="_blank" class="ext-link', 'http://wordpress.org/extend/plugins/plugin-notes/faq/" target="_blank" class="ext-link' ) . '</p>',
					// Use 'callback' instead of 'content' for a function callback that renders the tab content.
					)
				);
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
		function _add_plugin_note ( $note = null, $plugin_data, $plugin_file, $echo = true ) {

			$plugin_safe_name = $this->_get_plugin_safe_name($plugin_data['Name']);
			$actions = array();

			if(is_array($note) && !empty($note['note'])) {
				$note_class = 'wp-plugin_note_box';

				$note_text = $note['note'];
				$filtered_note_text = apply_filters( 'plugin_notes_note', $note_text, $plugin_data, $plugin_file );

				$note_author = get_userdata($note['user']);
				$note_date = $note['date'];

				$note_color = ( ( isset( $note['color'] ) && $note['color'] !== '' ) ? $note['color'] : $this->defaultcolor );

				$actions[] = '<a href="#" onclick="edit_plugin_note(\''. esc_js( $plugin_safe_name ) .'\'); return false;" id="wp-plugin_note_edit'. esc_attr( $plugin_safe_name ) .'" class="edit">'. __('Edit note', 'plugin-notes') .'</a>';
				$actions[] = '<a href="#" onclick="delete_plugin_note(\''. esc_js( $plugin_safe_name ) .'\'); return false;" id="wp-plugin_note_delete'. esc_attr( $plugin_safe_name ) .'" class="delete">'. __('Delete note', 'plugin-notes') .'</a>';
			} else {
				$note_class = 'wp-plugin_note_box_blank';
				$actions[] = '<a href="#" onclick="edit_plugin_note(\''. esc_js( $plugin_safe_name ) .'\'); return false;">'. __('Add plugin note', 'plugin-notes') .'</a>';
				$filtered_note_text = $note_text = '';
				$note_author = null;
				$note_date = '';
				$note_color = $this->defaultcolor;
			}

			$note_color_style = ( ( $note_color !== $this->defaultcolor ) ? ' style="background-color: ' . $note_color . ';"' : '' );

			$output = '
			<div id="wp-plugin_note_' . esc_attr( $plugin_safe_name ) . '" ondblclick="edit_plugin_note(\'' . esc_js( $plugin_safe_name ) . '\');" title="' . __('Double click to edit me!', 'plugin-notes') . '">
				<span class="wp-plugin_note">' . $filtered_note_text . '</span>
				<span class="wp-plugin_note_user">' . ( ( $note_author ) ? esc_html( $note_author->display_name ) : '' ) . '</span>
				<span class="wp-plugin_note_date">' . esc_html( $note_date ) . '</span>
				<span class="wp-plugin_note_actions">
					' . implode(' | ', $actions) . '
					<span class="waiting" style="display: none;"><img alt="' . __('Loading...', 'plugin-notes') . '" src="images/wpspin_light.gif" /></span>
				</span>
			</div>';

			$output = apply_filters( 'plugin_notes_row', $output, $plugin_data, $plugin_file );

			// Add the form to the note
			$output = '
			<div class="' . $note_class . '"' . $note_color_style . '>
				' . $this->_add_plugin_form($note_text, $note_color, $plugin_safe_name, $plugin_file, true, false) .
				$output . '
			</div>';

			if( $echo === true ) {
				echo $output;
			}
			else {
				return $output;
			}
		}

		/**
		 * Outputs form to add/edit/delete a plugin note
		 */
		function _add_plugin_form ( $note = '', $note_color, $plugin_safe_name, $plugin_file, $hidden = true, $echo = true ) {
			$plugin_form_style = ($hidden) ? 'style="display:none"' : '';

			$new_note_class = '';
			if( $note === '' ) {
				$note = ( isset( $this->notes['plugin-notes_template'] ) ? $this->notes['plugin-notes_template'] : '' );
				$new_note_class = ' class="new_note"';
			}

			$output = '
			<div id="wp-plugin_note_form_' . esc_attr( $plugin_safe_name ) . '" class="wp-plugin_note_form" ' . $plugin_form_style . '>
				 <label for="wp-plugin_note_color_' . esc_attr( $plugin_safe_name ) . '">' . __( 'Note color:', 'plugin-notes') . '
				 <select name="wp-plugin_note_color_' . esc_attr( $plugin_safe_name ) . '" id="wp-plugin_note_color_' . esc_attr( $plugin_safe_name ) . '">
			';

			// Add color options
			foreach( $this->boxcolors as $color ){
				$output .= '
					<option value="' . $color . '" style="background-color: ' . $color . '; color: ' . $color . ';"' .
					( ( $color === $note_color ) ? ' selected="selected"' : '' ) .
					'>' . $color . '</option>';
			}

			$output .= '
				</select></label>
				<textarea name="wp-plugin_note_text_' . esc_attr( $plugin_safe_name ) . '" cols="90" rows="10"' . $new_note_class . '>' . esc_textarea( $note ) . '</textarea>
				<span class="wp-plugin_note_error error" style="display: none;"></span>
				<span class="wp-plugin_note_success success" style="display: none;"></span>
				<span class="wp-plugin_note_edit_actions">
'.					// TODO: Unobtrusify the javascript
'					<a href="#" onclick="save_plugin_note(\'' . esc_js( $plugin_safe_name ) . '\');return false;" class="button-primary">' . __('Save', 'plugin-notes') . '</a>
					<a href="#" onclick="cancel_plugin_note(\'' . esc_js( $plugin_safe_name ) . '\');return false;" class="button">' . __('Cancel', 'plugin-notes') . '</a>
					<a href="#" onclick="templatesave_plugin_note(\'' . esc_js( $plugin_safe_name ) . '\');return false;" class="button-secondary">' . __('Save as template for new notes', 'plugin-notes') . '</a>
					<span class="waiting" style="display: none;"><img alt="' . __('Loading...', 'plugin-notes') . '" src="images/wpspin_light.gif" /></span>
				</span>
				<input type="hidden" name="wp-plugin_note_slug_' . esc_attr( $plugin_safe_name ) . '" value="' . esc_attr( $plugin_file ) . '" />
				<input type="hidden" name="wp-plugin_note_new_template_' . esc_attr( $plugin_safe_name ) . '" id="wp-plugin_note_new_template_' . esc_attr( $plugin_safe_name ) . '" value="n" />
			</div>';

			if( $echo === true ) {
				echo apply_filters( 'plugin_notes_form', $output, $plugin_safe_name );
			}
			else {
				return apply_filters( 'plugin_notes_form', $output, $plugin_safe_name );
			}
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
				die( __( 'Don\'t think you\'re supposed to be here...', 'plugin-notes' ) );
				return;
			}

			$current_user = wp_get_current_user();

			if (current_user_can('activate_plugins')) {

				// Get notes array
				$notes = $this->_get_notes();
				$note_text = $this->filter_kses( stripslashes( trim( $_POST['plugin_note'] ) ) );
				$note_color = ( isset( $_POST['plugin_note_color'] ) && in_array( $_POST['plugin_note_color'], $this->boxcolors ) ? $_POST['plugin_note_color'] : $this->defaultcolor );
				// TODO: Escape this?
				$plugin = $_POST['plugin_slug'];
				$plugin_name = esc_html($_POST['plugin_name']);

				$response_data = array();
				$response_data['slug'] = $plugin;

					$note = array();

				if($note_text) {

					// Are we trying to save the note as a note template ?
					if( $_POST['plugin_new_template'] === 'y' ) {

						$notes['plugin-notes_template'] = $note_text;

						$response_data = array_merge($response_data, $note);
						$response_data['action'] = 'save_template';
					}

					// Ok, no template, save the note to the specific plugin
					else {

						$date_format = get_option('date_format');

						// setup the note data
						$note['date'] = date($date_format);
						$note['user'] = $current_user->ID;
						$note['note'] = $note_text;
						$note['color'] = $note_color;

						// Add new note to notes array
						$notes[$plugin] = $note;

						$response_data = array_merge($response_data, $note);
						$response_data['action'] = 'edit';
					}

				} else {
					// no note sent, so let's delete it
					if(!empty($notes[$plugin])) unset($notes[$plugin]);
					$response_data['action'] = 'delete';
				}

				// Save the new notes array
				$this->_set_notes($notes);

				// Prepare response
				$response = new WP_Ajax_Response();

				$plugin_note_content = $this->_add_plugin_note($note, array('Name' => $plugin_name), $plugin, false);
				$response->add( array(
					'what' => 'plugin_note',
					'id' => $plugin,
					'data' => $plugin_note_content,
					'action' => ( ($note_text) ? ( ( $_POST['plugin_new_template'] === 'y' ) ? 'save_template' : 'edit' ) : 'delete' ),
				));
				$response->send();

				return;

			} else {
				// user can't edit plugins, so throw error
				die( __( 'Sorry, you do not have permission to edit plugins.', 'plugin-notes' ) );
				return;
			}

		}


		/**
		 * Applies the wp_kses html filter to the note string
		 *
		 * @param		string	$pluginnote
		 * @return		string	altered string $pluginnote
		 */
		function filter_kses( $pluginnote ) {
			return wp_kses( $pluginnote, $this->allowed_tags );
		}


		/**
		 * Adds additional line breaks to the note string
		 *
		 * @param		string	$pluginnote
		 * @return		string	altered string $pluginnote
		 */
		function filter_breaks( $pluginnote) {
			return wpautop( $pluginnote );
		}


		/**
		 * Applies markdown syntax filter to the note string
		 *
		 * @param		string	$pluginnote
		 * @return		string	altered string $pluginnote
		 */
		function filter_markdown( $pluginnote ) {
			include_once( dirname(__FILE__) . '/inc/markdown/markdown.php' );

			return Markdown( $pluginnote );
		}


		/**
		 * Replaces a number of variables in the note string
		 *
		 * @param		string	$pluginnote
		 * @return		string	altered string $pluginnote
		 */
		function filter_variables_replace( $pluginnote, $plugin_data, $plugin_file ) {

			if( !isset($plugin_data ) || count( $plugin_data ) === 1 ) {
				$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, $translate = true );
			}

			$find = array(
				'%NAME%',
				'%PLUGIN_PATH%',
				'%URI%',
				'%WPURI%',
				'%WPURI_LINK%',
				'%AUTHOR%',
				'%AUTHORURI%',
				'%VERSION%',
				'%DESCRIPTION%',
			);
			$replace = array(
				esc_html($plugin_data['Name']),
				esc_html( plugins_url() . '/' . plugin_dir_path( $plugin_file ) ),
				( isset( $plugin_data['PluginURI'] ) ? esc_url( $plugin_data['PluginURI'] ) : '' ),
				esc_url( 'http://wordpress.org/plugins/' . substr( $plugin_file, 0, strpos( $plugin_file, '/') ) ),
				'<a href="' . esc_url( 'http://wordpress.org/plugins/' . substr( $plugin_file, 0, strpos( $plugin_file, '/') ) ) . '" target="_blank">' . esc_html($plugin_data['Name']) . '</a>',
				( isset($plugin_data['Author'] ) ? esc_html( $plugin_data['Author'] ) : '' ),
				( isset($plugin_data['AuthorURI'] ) ? esc_html( $plugin_data['AuthorURI'] ) : '' ),
				( isset($plugin_data['Version'] ) ? esc_html( $plugin_data['Version'] ) : '' ),
				( isset($plugin_data['Description'] ) ? esc_html( $plugin_data['Description'] ) : '' ),
			);

			return str_replace( $find, $replace, $pluginnote );
		}


		/* Some sweet function to get/set go!*/
		function _get_notes() { return get_option($this->notes_option);	}
		function _set_notes($notes) { return update_option($this->notes_option, $notes); }

	} /* End of class */


	add_action( 'admin_init', 'plugin_notes_init' );

	function plugin_notes_init() {
		/** Let's get the plugin rolling **/
		// Create new instance of the plugin_notes object
		$GLOBALS['plugin_notes'] = new plugin_notes();
	}

} /* End of class-exists wrapper */
