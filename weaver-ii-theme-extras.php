<?php
/*
Plugin Name: Weaver II Theme Extras
Plugin URI: http://weavertheme.com
Description: Weaver II Theme Extras - Adds shortcodes and other features to the Weaver II theme.
Author: Bruce Wampler
Author URI: http://weavertheme.com/about
Version: 2.3
License: GPL

GPL License: http://www.opensource.org/licenses/gpl-license.php

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

define ('WEAVER_II_EXTRAS_VERSION','Weaver II Extras Version 2.3');
define ('WEAVER_II_EXTRAS_VN', '2.3');

$cur_theme = wp_get_theme();
$parent = $cur_theme->parent(); // might be a child, so see if Weaver II is parent...
if ($parent)
    $cur_theme = $parent;
if ( strcmp($cur_theme->Name, 'Weaver II' ) == 0
    ||
    ( strcmp($cur_theme->Name, 'Weaver II Pro' ) == 0
    && (version_compare($cur_theme->Version, '1.9' , '<')
        || version_compare($cur_theme->Version, '2.0.90' , '>='))) ) {  // only need this for Weaver II free or old Pro

// ===============================>>> Handle loading scripts <<<===============================

    add_action( 'plugins_loaded', 'weaverii_tx_plugins_loaded');

function weaverii_tx_plugins_loaded() {
    add_action( 'wp_enqueue_scripts', 'weaverii_tx_enqueue_scripts' );
    add_action( 'wp_footer','weaverii_tx_the_footer', 9);	// make it 9 so we can dequeue scripts
    add_action( 'wp_footer','weaverii_tx_the_footer_late', 99);	// make it 12 to load late
    register_deactivation_hook( __FILE__, 'weaverii_tx_deactivate' );
}


// ========================================= >>> weaverii_tx_deactivate <<< ===============================

function weaverii_tx_deactivate() {	// deactivate

    $ac_dir = WP_CONTENT_DIR . '/ac-plugins';

    $ac_file = $ac_dir . '/weaver-ac-plugin.php';

    unlink ( $ac_file );        // delete the file if there...

}

// ========================================= >>> weaverii_tx_enqueue_scripts <<< ===============================

function weaverii_tx_enqueue_scripts() {	// enqueue runtime scripts

    $at_end = true;

    wp_enqueue_script('weaverii-tx-fitvids',
        plugins_url('/includes/fitvids/wvr.fitvids.min.js',__FILE__),array('jquery'),
        WEAVER_II_EXTRAS_VN, $at_end);
}

// ========================================= >>> weaverii_tx_the_footer <<< ===============================

function weaverii_tx_the_footer() {
    $use_fitvids = false;
    if (function_exists( 'weaverii_getopt' )) {
        if ( weaverii_getopt( 'use_fitvids' ) ) {
            $use_fitvids = true;
        }

    }
    if (!$use_fitvids && !isset($GLOBALS['wvr_videos_count']) ) {  // dequeue scripts if not used
        wp_dequeue_script( 'weaverii-tx-fitvids' );
        return;
    }
}

function weaverii_tx_the_footer_late() {
    if (function_exists( 'weaverii_getopt' )) {
        if ( weaverii_getopt( 'use_fitvids' ) ) {
            echo "<script type='text/javascript'>jQuery('#wrapper').fitVids();</script>\n";
        }
    }
}

/* PART 1 - Extras hooks - market, update */

add_action('weaverii_child_process_options','weaverii_child_process_options');
function weaverii_child_process_options() {
    if ( !defined ('WEAVERII_EXTRAS_ADMIN_HELPERS'))
        require_once (dirname( __FILE__ ) . '/includes/wtx-admin-page.php');	// admin info

    if (weaverii_submitted('set_added_subtheme') ) {	// Set to selected addon - theme
        if (isset($_POST['wii_addon_name']))
        {
            $name = $_POST['wii_addon_name'];

            $openname = weaverii_f_uploads_base_dir() . 'weaverii-subthemes/addon-subthemes/' . $name . '.w2t';
            $contents = file_get_contents($openname);

            if ( !weaverii_ex_set_current_to_serialized_values($contents,'weaverii_uploadit:'.$openname) ) {
                echo '<div id="message" class="updated fade"><p><strong><em style="color:red;">Sorry,
                there was a problem uploading your add on theme. The name you picked did not have a valid
                Weaver II theme file in  the /wevaerii-subthemes/addon-subthemes directory.</em></strong></p></div>';
            } else {
                weaverii_save_msg('Weaver II theme options reset to ' .
                    ucwords(str_replace('-',' ',$name )) . ' add-on subtheme.');
                weaverii_setopt('wii_addon_name',$name);
            }
        }
    }

    else if (weaverii_submitted('delete_added_subtheme') ) {	// Delete selected addon theme
        if (isset($_POST['wii_addon_name']))
        {
            $name = $_POST['wii_addon_name'];
            @unlink(weaverii_f_uploads_base_dir() . 'weaverii-subthemes/addon-subthemes/' . $name . '.w2t');
            @unlink(weaverii_f_uploads_base_dir() . 'weaverii-subthemes/addon-subthemes/' . $name . '.jpg');
            weaverii_save_msg('Deleted ' .
                ucwords(str_replace('-',' ',$name )) . ' add-on subtheme.');
        }
    }

    else if (weaverii_submitted('upload_addon')
        && isset($_POST['uploadaddon'])
        && $_POST['uploadaddon'] == 'yes') {
        // upload theme from users computer
        // they've supplied and uploaded a file
        $ok = weaverii_unpackzip('uploaded_addon', weaverii_f_uploads_base_dir() . 'weaverii-subthemes/addon-subthemes/');
    }

    else if (weaverii_submitted('upload_theme')) {
        // upload theme from users computer
        // they've supplied and uploaded a file

        if (isset($_FILES['uploaded_theme']['name']))	// uploaded_theme
            $filename = $_FILES['uploaded_theme']['name'];
        else
            $filename = "";

        $to = weaverii_f_themes_dir();

        if (strpos($filename,'weaver-ii-pro-') === false && strpos($filename, 'weaver-ii-') === false) {
?>
            <div id="message" class="updated fade"><p><strong><em style="color:red;">ERROR</em></strong></p>
            <p>You did not select a Weaver II theme .zip file: "<?php echo $filename;?>".
            The theme file name must start with 'weaver-ii-'
            or 'weaver-ii-pro-'. Please use a file you downloaded from WeaverTheme.com.</p>
            </div>
<?php
            return;
        }
        $ok = weaverii_unpackzip('uploaded_theme',$to);
        if ($ok)
            weaverii_save_msg('Your Weaver II Theme has been successfully updated. Please click "Clear Messages"
            button right now to refresh the scrren and start using the updated version.');
    }

}

// ========= add actions and filters from admin lib

function weaverii_tx_head_opts( $args ) {
    return true;
}

add_filter('weaverii_child_extrathemes','weaverii_child_extrathemes_filter');
function weaverii_child_extrathemes_filter($msg) {
    return '';
}

add_action('weaverii_child_show_extrathemes','weaverii_child_show_extrathemes_action');

add_action('weaverii_child_saverestore','weaverii_child_saverestore_action');

add_action('weaverii_child_update','weaverii_child_update_action');

add_action('weaverii_favicon','weaverii_favicon_action');

add_action('weaverii_facebook','weaverii_facebook_action');


/* PART 2 - Shortcodes */

// check if we are running Weaver II 1.x

if ( version_compare($cur_theme->Version, '1.8' , '>') ) {

    function weaverii_extras_shortcodes_installed() {
        return true;
}

function weaverii_ex_admin() {
    require_once(dirname( __FILE__ ) . '/includes/weaverii-sc-basic.php');	// shortcode descriptions
    if ( !defined ('WEAVERII_EXTRAS_ADMIN_HELPERS'))
        require_once(dirname( __FILE__ ) . '/includes/wtx-admin-page.php');	// admin info

    weaverii_tx_sc_admin();
}

add_action('weaverii_extras_info', 'weaverii_ex_admin');

function weaverii_ex_load_descriptions_action() {
    require_once(dirname( __FILE__ ) . '/includes/weaverii-sc-basic.php');	// shortcode descriptions
}

add_action('weaverii_ex_load_descriptions', 'weaverii_ex_load_descriptions_action');

function weaverii_ex_wphead() {
    printf("\n<!-- %s -->\n",WEAVER_II_EXTRAS_VERSION);
}

add_action('wp_head', 'weaverii_ex_wphead');

require_once(dirname( __FILE__ ) . '/includes/shortcodes.php');	// standard runtime library

} // end not using Weaver II 1.x

} // end of check if using Weaver II


if ( strcmp($cur_theme->Name, 'Weaver II' ) == 0 || strcmp($cur_theme->Name, 'Weaver II Pro' ) == 0 ) {
	if ( version_compare($cur_theme->Version, '2.2' , '<') ) {

	if ( strcmp($cur_theme->Name, 'Weaver II' ) == 0 ) {		// let's add the autoupdater for Weaver II (but not pro)
		require_once('wp-updates-theme-1373.php');
	$theme = basename(get_template_directory());
	new WPUpdatesThemeUpdater_1373( 'http://wp-updates.com/api/2/theme', $theme );

	}


function weaverii_tx_admin_notice() {

	$cur_theme = wp_get_theme();
	$parent = $cur_theme->parent(); // might be a child, so see if Weaver II is parent...
	if ($parent)
		$cur_theme = $parent;
?>
<div class="updated">
<p><strong>A newer version of <em><?php echo $cur_theme->Name; ?></em> is available.
Please visit <a href="//weavertheme.com/update-weaver-ii" target="_blank"><span style="font-size:120%;text-decoration:underline;">Update Weaver II</span></a>
for complete instructions.</strong><br />
Updating to the new <em>Weaver II</em> version requires using the WordPress automatic theme update process
(open the Dashboard:Updates menu), or an optional simple, one-time manual update.
The new version has some important tweaks, as well as support for the standard automatic process for future updates.
<small>Because the update is so important, this notice will remain visible until
you perform the update.</small>
</div>
<?php
}
add_action('admin_notices', 'weaverii_tx_admin_notice');


	}
}

?>
