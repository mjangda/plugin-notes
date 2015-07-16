=== Plugin Notes ===
Contributors: batmoo, cdillon27, jrf
Donate link: http://digitalize.ca/donate
Tags: plugin, plugin notes, memo, meta, plugins
Tested up to: 4.2
Requires at least: 3.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to add notes to plugins.

== Description ==

Allows you to add notes to plugins. Useful when you're using lots of plugins and/or make modifications to a plugin and want to make a note of them, and/or work on your WordPress install with a group of people. This plugin was inspired by a post by [Chris Coyier](http://digwp.com): (http://digwp.com/2009/10/ideas-for-plugins/)

= Features =
* Add/edit/delete notes for each plugin on the plugin page
* You can use HTML in notes (v1.1+)
* You can use [markdown syntax](http://daringfireball.net/projects/markdown/syntax) in notes (v1.5+)
* You can use a number of variables which will be automagically replaced when the note displays (v1.5+)
* Save a note as a template for new notes (v1.5+)
* You can color-code notes to see in one glance what's up or down (v1.6+)
* Links within note automagically have `target="_blank"` added so you won't accidently leave your site while working with the plugins.

Please have a look at the [FAQ](http://wordpress.org/plugins/plugin-notes/faq/) for more information about these features.




*****

= Credits =

**Markdown script**: [PHP Markdown 1.0.1.o](http://michelf.ca/projects/php-markdown/)

**External link indicator**: liberally nicked from the [Better WP External Links](http://wordpress.org/plugins/bwp-external-links/) plugin


= Translations =
Dutch - [jrf](http://wordpress.org/support/profile/jrf)

Please help us make this plugin available in more language by translating it. See the [FAQ](http://wordpress.org/plugins/plugin-notes/faq/) for more info.


== Frequently Asked Questions ==

= Where is the Plugin Notes data stored? =

The notes are stored in the options table of the database.


= Which variables can I use ? =

There are a number of variables you can use in the notes which will automagically be replaced. Most aren't that useful as the info is provided by default for the plugin, but they are included anyway for completeness.

Example use: you want a link to the WordPress Plugin repository for each plugin.
Instead of manually adding each and every link, you can just add the following note to each plugin and the link will be automagically placed:

`	Plugin: %WPURI_LINK%`

**Available variables**:
`%PLUGIN_PATH%` : Plugin uri path on your website
`%WPURI%` : URI of the WordPress repository of the plugin (Please note: it is not tested whether the plugin is actually registered in the WP plugin repository!)
`%WPURI_LINK%` : A link to the above WordPress repository of the plugin

**Already showing for each plugin (less useful)**:
`%NAME%`: Plugin Name
`%URI%`: URI of the plugin website
`%AUTHOR%`: Name of the plugin author
`%AUTHORURI%`: Website of the plugin author
`%VERSION%`: Current plugin version
`%DESCRIPTION%`: Description of the plugin


= Can I use the markdown syntax in the notes ? =

Yes, you can use markdown.
The markdown syntax conversion is done on the fly. The notes are saved to the database without conversion.

Don't like markdown ?
Just add the following snippet to your (child-)themes functions.php file to turn markdown parsing off:
`add_filter( 'plugin_notes_markdown', '__return_false' );`


= How do I use Markdown syntax? =

Please refer to [markdown syntax](http://daringfireball.net/projects/markdown/syntax).


= Can I use html in the notes ? =

Yes, you can use html in the notes. The following tags are allowed: `a, br, p, b, strong, i, em, u, img, hr`.
The html is saved to the database with the note.


= Can I change the allowed html tags ? =

Yes, you can, though be careful as you might open up your WP install to XSS attacks.

To change the allowed html tags, just add a variation of the following snippet to your (child-)themes functions.php file:
`add_filter( 'plugin_notes_allowed_tags', 'your_function', 10, 1 );
function your_function( $allowed_tags ) {
	//do something with the $allowed_tags array
	return $allowed_tags;
}`


= Can I change the output of the plugin ? =

Yes, you can. There are filters provided at three points:
1. The actual note to be displayed -> `plugin_notes_note`
1. The html for the note including the surrounding box -> `plugin_notes_row`
1. The html for the input form -> `plugin_notes_form`

Hook into those filters to change the output before it's send to the screen.

`add_filter( 'plugin_notes_note', 'your_function', 10, 3 );
function your_function( $note, $plugin_data, $plugin_file ) {
	//do something
	return $output;
}`

`add_filter( 'plugin_notes_row', 'your_function', 10, 3 );
function your_function( $output, $plugin_data, $plugin_file ) {
	//do something
	return $output;
}`

`add_filter( 'plugin_notes_form', 'your_function', 10, 2 );
function your_function( $output, $plugin_safe_name ) {
	//do something
	return $output;
}`

If you want to filter the note output before the variable replacements are made and markdown syntax is applied, set the priority for your `plugin_notes_note` filter to lower than 10.

Example:
`	add_filter( 'plugin_notes_note', 'your_function', 8, 3 );`


= How can I translate the plugin? =

The plugin is translation ready, though there is not much to translate. Use the `/languages/plugin-notes.pot` file to create a new .po file for your language. If you would like to offer your translation to other users, please [open a pull request on GitHub](https://github.com/mjangda/plugin-notes).


== Changelog ==

= 2015-06-13 / 1.6 Originally released Dec 2012 by jrf =
* [_New feature_] Added ability to change the background color of notes.


= 2015-06-09 / 1.5 Originally released Dec 2012 by jrf =

* [_Bug fix_] Fixed AJAX delete bug (kept 'waiting').
* [_New feature_] Added output filters for html output (`plugin_notes_row` and `plugin_notes_form`) and the note itself (`plugin_notes_note`).
* [_New feature_] Added ability to use a number of variables in notes which will automagically be replaced - see [FAQ](http://wordpress.org/plugins/plugin-notes/faq/) for more info.
* [_New feature_] Added ability to use markdown syntax in notes - see [FAQ](http://wordpress.org/plugins/plugin-notes/faq/) for more info.
* [_Usability improvement_] Added `<hr />` to allowed tags list and made the tag list filterable through the new `plugin_notes_allowed_tags` filter.
* [_Usability improvement_] Made the default text area for adding a note larger.
* [_Usability improvement_] Added automagical target="_blank" to all links in plugin notes including external link indicator.
* [_Usability improvement_] Added contextual help for WP 3.3+,
* [_Usability improvement_] Added FAQ section and plugin license info to the readme file ;-)
* [_Usability improvement_] Added uninstall script for clean uninstall of the plugin.
* [_Usability improvement_] Added minified versions of the js and css files.
* [_I8n_] Created .POT file and added Dutch translation.
* [_Security_] Improved output escaping.

= 2015-04-15 / 1.2 =
* Fix strict warning: Redefining already defined constructor.
* Version bump for WordPress 4.1.

= 2010-10-15 / 1.1 =

* Certain HTML tags are now allowed in notes: `<p> <a> <b> <strong> <i> <em> <u> <img>`. Thanks to [Dave Abrahams](http://www.boostpro.com) for suggesting this feature. 
* Some style tweaks
* Fixed PHP Error Notices

= 2009-12-04 / 1.0 =

* Fixed a major bug that was causing fatal errors
* Added some inline code comments
* Changed around some minor styling.
* Bumping release number up to 1.0 because I feel like it

= 2009-10-24 / 0.1 =

* Initial beta release

== Installation ==

1. Extract the .zip file and upload its contents to the `/wp-content/plugins/` directory. Alternately, you can install directly from the Plugin directory within your WordPress Install.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Add notes to your plugins from the Manage Plugins page (Plugins > Installed)
1. Party.

== Screenshots ==
1.  Easily add/edit/delete note or save as notes-template. Uses AJAX so you'll save at least a couple seconds for each note you add/edit/delete.
2.  Example of saved note using markdown syntax and variable replacement.
3.  A bunch of multi-coloured notes added to plugins.


== Upgrade Notice ==

= 2015-05-xx - 1.6 =
New feature: color-code notes.

= 2015-05-xx - 1.5 =
Improved security and new features: plugin notes template, markdown syntax support and variable replacement.

= 2015-04-15 - 1.2 =
Fix strict warning: Redefining already defined constructor. Tested in WordPress 4.1.