<?php
/*
Plugin Name: Theme Blvd WPML Bridge
Plugin URI: http://wpml.themeblvd.com
Description: This plugin creates a bridge between the Theme Blvd framework and the WPML plugin.
Version: 1.0.0-beta1
Author: Jason Bobich
Author URI: http://jasonbobich.com
License: GPL2
*/

/*
Copyright 2012 JASON BOBICH

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/*-----------------------------------------------------------------------------------*/
/* Frontend Integration
/*
/* In the Theme Blvd framework, there are many processes that are completely 
/* separate depending on whether we're in the WordPress admin or the frontend
/* of the website. The items in this section are only relevant when the theme
/* loads on the frontend.
/*-----------------------------------------------------------------------------------*/

/**
 * Re-configure theme global settings if this isn't default 
 * language.
 *
 * When the theme runs on the frontend, the Theme Blvd framework 
 * creates a global array in which it stores all of the current 
 * theme's options. This makes it so every time themeblvd_get_option
 * is called, we don't have to pull from the database with WP's 
 * get_option.
 *
 * So, in this function, what we're doing is essentally setting up the 
 * global variable in the same way, but with the twist of taking into 
 * account the current language in pulling the option set. And then, 
 * we're hooking it with a priority "6" to template_redirect so it 
 * comes just AFTER the default framework function.
 *
 * @since 1.0.0
 */
 
function tb_wpml_options() {
	
	global $_themeblvd_theme_settings;
	
	// Only continue if WPML is running and the
	// current language constant has been defined.
	if( defined( 'ICL_LANGUAGE_CODE' ) ) {
	
		// Current language
		$current_lang = ICL_LANGUAGE_CODE;
		
		// Set default language
		$default_lang = 'en'; // backup
		$wpml_options = get_option( 'icl_sitepress_settings' );
		if( isset( $wpml_options['default_language'] ) ) 
			$default_lang = $wpml_options['default_language'];
		
		// Adjust theme settings to match language if 
		// it's different than the default language.
		if( $current_lang != $default_lang ) {
			$config = get_option( 'optionsframework' );
			if ( isset( $config['id'] ) )
				$_themeblvd_theme_settings = get_option( $config['id'].'_'.$current_lang );
		}
		
	}
	
}
add_action( 'template_redirect', 'tb_wpml_options', 6 ); // Run after framework's function with priority 5


/**
 * Get flag list.
 *
 * This function returns a simple list of flags for all 
 * available languages for the current page.
 *
 * @since 1.0.0
 */

function tb_wpml_get_flaglist() {
	// Get languages
	$langs = icl_get_languages();
	// Start output
	$output = '';
	if( $langs ) {
		$output .= '<div class="tb-wpml-flaglist">';
		$output .= '<ul>';
		foreach( $langs as $lang ) {
			$classes = $lang['language_code'];
			if( $lang['active'] ) $classes .= ' active';
			$output .= '<li class="'.$classes.'">';
			$output .= '<a href="'.$lang['url'].'" title="'.$lang['translated_name'].'">';
			$output .= '<img src="'.$lang['country_flag_url'].'" alt="'.$lang['translated_name'].'" />';
			$output .= '</a>';
			$output .= '</li>';
		}
		$output .= '</ul>';
		$output .= '</div><!-- .tb-wpml-flaglist (end) -->';
	}
	return apply_filters( 'tb_wpml_flaglist', $output );
}

/**
 * Display flag list.
 *
 * Any compatible theme to automatically show the flaglist 
 * will have do_action('themeblvd_wpml_nav') somewhere in
 * the theme.
 *
 * This can also be removed from automatically showing easily
 * from a Child theme with:
 * remove remove_action('themeblvd_wpml_nav', 'tb_wpml_flaglist' );
 *
 * @since 1.0.0
 */

function tb_wpml_flaglist() {
	echo tb_wpml_get_flaglist();
}
add_action( 'themeblvd_wpml_nav', 'tb_wpml_flaglist' );

/**
 * New display for action: themeblvd_breadcrumbs
 *
 * @since 1.0.0
 */

if( ! function_exists( 'tb_wpml_breadcrumbs' ) ) {
	function tb_wpml_breadcrumbs() {
		wp_reset_query();
		global $post;
		$display = '';
		// Pages and Posts
		if( is_page() || is_single() )
			$display = get_post_meta( $post->ID, '_tb_breadcrumbs', true );
		// Standard site-wide option
		if( ! $display || $display == 'default' )
			$display = themeblvd_get_option( 'breadcrumbs', null, 'show' );
		// Disable on posts homepage
		if( is_home() )
			$display = 'hide';
		// Show breadcrumbs if not hidden
		if( $display == 'show' ) {
			$atts = array(
				//'delimiter' => '&raquo;', // Not using because plugin allows you to set it.
				'home' => themeblvd_get_local('home'),
				'home_link' => home_url(),
				'before' => '<span class="current">',
				'after' => '</span>'
			);
			$atts = apply_filters( 'themeblvd_breadcrumb_atts', $atts );	
			// Start output
			echo '<div id="breadcrumbs">';
			echo '<div class="breadcrumbs-inner" class="tb-wpml-breadcrumbs">';
			echo '<div class="breadcrumbs-content">';
			do_action( 'icl_navigation_breadcrumb' ); // Display WPML breadcrumbs
			echo '</div><!-- .breadcrumbs-content (end) -->';
			echo '</div><!-- .breadcrumbs-inner (end) -->';
			echo '</div><!-- #breadcrumbs (end) -->';
		}
	}
}

/**
 * Action adjustments.
 *
 * Because the theme will run obviously run after this 
 * plugin, we will put any framework functions we want 
 * to unhook or swap here.
 *
 * @since 1.0.0
 */

function tb_wpml_actions(){
	// Only swap breadcrumbs if user has "WPML CMS Nav" add-on installed.
	if( class_exists( 'WPML_CMS_Navigation' ) ) {
		remove_action( 'themeblvd_breadcrumbs', 'themeblvd_breadcrumbs_default' );
		add_action( 'themeblvd_breadcrumbs', 'tb_wpml_breadcrumbs' );
	}
}
add_action( 'after_setup_theme', 'tb_wpml_actions' );

/*-----------------------------------------------------------------------------------*/
/* Theme Options (Admin)
/*
/* The purpose of this section of our plugin is to allow the user to save theme
/* options based on each of the languages setup with WPML. This will result in a lot
/* redunant option selection, as most theme options won't actually effect the chosen 
/* language. However, this solution will provide the most flexibility.
/*-----------------------------------------------------------------------------------*/

/**
 * Set default values for option set.
 * 
 * This function is run within the next function of this plugin called 
 * "tb_wpml_optionsframework_init" -- Basically it is a copy of our 
 * options framework default function "optionsframework_setdefaults".
 * 
 * The options framework's original function is not designed to be 
 * used more than once with different option sets. So in our modifed 
 * version here, we're allowing a unique value for each option set 
 * to be feed in. This allows us to loop through each language's option
 * set with calling this function within "tb_wpml_optionsframework_init"
 * in order to set default values for each language's option set when it
 * hasn't been configured yet.
 *
 * @since 1.0.0
 */

function tb_wpml_optionsframework_setdefaults( $option_name ) {
	// Gets the default options data from the array in options.php
	$options = themeblvd_get_formatted_options();
	// If the options haven't been added to the database yet, they are added now
	$values = of_get_default_values();
	// Add option with default settings
	if ( isset( $values ) )
		add_option( $option_name, $values );
}

/**
 * Initiate theme options after the framework has initiated it's default
 * theme options system.
 * 
 * In regards to the WPML plugin, we're using the default theme options
 * with the default WPML language. These default options are setup and
 * registered with the function "optionsframework_init" within the
 * framework.
 *
 * This function is hooked to "admin_init" with priority 11 so it comes 
 * just after the the framework's "optionsframework_init" function. Its
 * purpose is to register the additional settings groups for all WPML
 * languages other than the default language. This registration also links
 * these non-default languages up to this plugin's modified sanitation
 * function called "tb_wpml_optionsframework_validate".
 *
 * And additionally, this current function adds the action of including CSS 
 * files for all of the framework's admin modules.
 *
 * @since 1.0.0
 */

function tb_wpml_optionsframework_init() {
	
	// Don't continue if the WPML plugin 
	// hasn't been installed.
	if( ! function_exists( 'icl_get_languages' ) )
		return;
	
	// Get current settings	
	$optionsframework_settings = get_option('optionsframework' );
	
	// Gets the unique id, returning a default if it isn't defined
	if ( isset($optionsframework_settings['id']) ) {
		$option_name = $optionsframework_settings['id'];
	} else {
		$option_name = 'optionsframework';
	}
	
	// Get all languages
	$langs = icl_get_languages();
	
	// Set default language
	$default_lang = 'en'; // backup
	$wpml_options = get_option( 'icl_sitepress_settings' );
	if( isset( $wpml_options['default_language'] ) ) 
		$default_lang = $wpml_options['default_language'];
	
	// Register settings for each language only if its not the default 
	// language. The default language's options set wil be saved with 
	// no language code appended, and thus was already registered above.
	foreach ( $langs as $key => $lang ) {
		if( $key != $default_lang ) {
			// If the option has no saved data, load the defaults
			if ( ! get_option( $option_name.'_'.$key ) )
				tb_wpml_optionsframework_setdefaults( $option_name.'_'.$key );
			// Register settings
			register_setting( 'optionsframework'.'_'.$key, $option_name.'_'.$key, 'tb_wpml_optionsframework_validate' );
		}
	}
	
	// Add CSS files to framework's admin pages
	add_action( 'admin_print_styles-appearance_page_options-framework','tb_wpml_optionsframework_load_styles' );
	add_action( 'admin_print_styles-appearance_page_sidebar_blvd','tb_wpml_optionsframework_load_styles' );
	add_action( 'admin_print_styles-toplevel_page_builder_blvd','tb_wpml_optionsframework_load_styles' );
	add_action( 'admin_print_styles-toplevel_page_slider_blvd','tb_wpml_optionsframework_load_styles' );
	
}
add_action( 'admin_init', 'tb_wpml_optionsframework_init', 11 ); // Priority 11 to execute AFTER Theme Blvd framework

/**
 * Load CSS files.
 * 
 * These are the CSS files used for Theme Blvd admin module pages 
 * only. Mainly they're intended for the Theme Options page only.
 * In the above function "tb_wpml_optionsframework_init" we make
 * sure to load these styles ONLY on our admin pages.
 *
 * @since 1.0.0
 */
 
function tb_wpml_optionsframework_load_styles() {
	wp_register_style( 'tb_wpml_optionsframework_styles', plugins_url( 'assets/css/optionsframework.css', __FILE__ ), false, '1.0' );
	wp_enqueue_style( 'tb_wpml_optionsframework_styles' );
}

/**
 * Setup sanitization for WPML's option set.
 *
 * The Theme Blvd options framework uses the function 
 * "optionsframework_validate" to santize options when they're 
 * saved. This is essentially a copy of that function with the 
 * slight modifications of allowing the user to "match" current 
 * language's options to default language's options. 
 * 
 * This new function is used when we call register_setting up
 * above in the "tb_wpml_optionsframework_init" function in order 
 * to register an option set specific to current theme for each 
 * language outside of the default language.
 *
 * @since 1.0.0
 */

function tb_wpml_optionsframework_validate( $input ) {
	
	// Match language's options to default language's options.
	if ( isset( $_POST['match'] ) ) {
		$default_lang_options = get_option( $_POST['option_page_base'] );
		return $default_lang_options;
	} 
	
	// Restore Defaults.
	if ( isset( $_POST['reset'] ) ) {
		add_settings_error( 'options-framework', 'restore_defaults', __( 'Default options restored.', 'tb_wpml' ), 'updated fade' );
		return of_get_default_values();
	}

	// Udpdate Settings.	 
	if ( isset( $_POST['update'] ) ) {
		$clean = array();
		$options = themeblvd_get_formatted_options();
		foreach ( $options as $option ) {

			if ( ! isset( $option['id'] ) ) {
				continue;
			}

			if ( ! isset( $option['type'] ) ) {
				continue;
			}

			$id = preg_replace( '/\W/', '', strtolower( $option['id'] ) );

			// Set checkbox to false if it wasn't sent in the $_POST
			if ( 'checkbox' == $option['type'] && ! isset( $input[$id] ) ) {
				$input[$id] = '0';
			}

			// Set each item in the multicheck to false if it wasn't sent in the $_POST
			if ( 'multicheck' == $option['type'] && ! isset( $input[$id] ) ) {
				foreach ( $option['options'] as $key => $value ) {
					$input[$id][$key] = '0';
				}
			}

			// For a value to be submitted to database it must pass through a sanitization filter
			if ( has_filter( 'of_sanitize_' . $option['type'] ) ) {
				$clean[$id] = apply_filters( 'of_sanitize_' . $option['type'], $input[$id], $option );
			}
		}

		add_settings_error( 'options-framework', 'save_options', __( 'Options saved.', 'tb_wpml' ), 'updated fade' );
		return $clean;
	}

	// Request Not Recognized.
	return of_get_default_values();
}

/**
 * Builds the options panel.
 *
 * We're modifying the framework's theme options page slightly. 
 * The theme's options framework module already declares a
 * function called "optionsframework_page" and so by declaring it
 * here, we're overriding when WordPress arrives at the options 
 * framework later in its loading process.
 * 
 * So, to start this function, we just copied the function from 
 * the framework and made the following modifications:
 * 
 * (1) Add check for WPML plugin, and if it's not installed, kill 
 * the options page. 
 * 
 * (2) Add in all of our needed items to pull new inserted language 
 * variable in the $_GET and match it against all current WPML 
 * languages.
 * 
 * (3) When determing the $option_name parameter to pull the correct 
 * current option settings, we've added in the current language code 
 * into the mix.
 * 
 * (4) Moved wrapping <form> tags wider to make sure they include the
 * action "themeblvd_admin_module_header" WITHIN the form. This makes 
 * it possible for our hooked WPML bridge header to have a form button 
 * to match current language option set to default language.
 *
 * (5) When "settings_fields" is called, we've made it use a dynamic 
 * variable called $settings_fields instead of the static string 
 * 'optionsframework' -- This allows us to pull WordPress's hidden form 
 * fields for different option sets based on current language.
 * 
 * (6) Added button at bottom of form that allows user to match current 
 * language's option set to default option set. This will only show if
 * we're not currently on the default language.
 *
 * @since 1.0.0
 */

function optionsframework_page() {
	
	global $_GET;
	
	// Don't continue if the WPML plugin 
	// hasn't been installed.
	if( ! function_exists( 'icl_get_languages' ) ) {
		echo '<div class="tb-wpml-warning">';
		echo '<p><strong>'.__( 'WARNING: You\'ve activated the Theme Blvd WPML Bridge plugin, but you haven\'t installed the official WPML plugin. You\'ll need that plugin installed in order to move forward.', 'tb_wpml' ).'</strong></p>';
		echo '<p><a href="http://wpml.org/?aid=8007&affiliate_key=MNKoTksdyWns" target="_blank">'.__('Download WPML Plugin', 'tb_wpml' ).'</p></a>';
		echo '</div>';
		return;
	}

	// Get all languages
	$langs = icl_get_languages();

	// Setup check array
	$langs_check = array();
	foreach( $langs as $key => $lang )
		$langs_check[] = $key;
	
	// Set default language
	$default_lang = 'en'; // backup
	$wpml_options = get_option( 'icl_sitepress_settings' );
	if( isset( $wpml_options['default_language'] ) ) 
		$default_lang = $wpml_options['default_language'];
	
	// Set current options language
	$current_lang = $default_lang;
	if( isset( $_GET['themeblvd_lang'] ) )
		$current_lang = $_GET['themeblvd_lang'];
	if( ! in_array( $current_lang, $langs_check ) )
		$current_lang = $default_lang;
		
	// Retrieive options framework container same as normal
	$optionsframework_settings = get_option('optionsframework');
	
	// Gets the unique option id
	if ( isset( $optionsframework_settings['id'] ) ) {
		// Retrieve options ID as normal
		$option_name = $optionsframework_settings['id'];
		$option_base = $optionsframework_settings['id'];
		
		// And here's our twist to the system --
		// If we're editing options for a specific language 
		// that is NOT the default, we adjust the options 
		// framework ID to append '_{language}'.
		if( $default_lang != $current_lang )
			$option_name .= '_'.$current_lang;
			
	} else {
		// Total fallback. Should never get used.
		$option_name = 'optionsframework';
		$option_base = 'optionsframework';
	}
	
	// Determine value for settings_fields()
	$settings_fields = 'optionsframework';
	if( $current_lang != $default_lang )
		$settings_fields .= '_'.$current_lang;
	
	// Get settings and form
	$settings = get_option($option_name);
    $options = themeblvd_get_formatted_options();
	$return = optionsframework_fields( $option_name, $options, $settings  );
	settings_errors();
	?>
	<div class="wrap">
		<form action="options.php" method="post">
			<div class="admin-module-header">
				<?php do_action( 'themeblvd_admin_module_header', 'options' ); ?>
			</div>
		    <?php screen_icon( 'themes' ); ?>
		    <h2 class="nav-tab-wrapper">
		        <?php echo $return[1]; ?>
		    </h2>
			<div class="metabox-holder">
			    <div id="optionsframework">
					<input type="hidden" value="<?php echo $option_base; ?>" name="option_page_base">
					<?php settings_fields($settings_fields); ?>
					<?php echo $return[0]; /* Settings */ ?>
			        <div id="optionsframework-submit">
					<input type="submit" class="button-primary" name="update" value="<?php esc_attr_e( 'Save Options', 'tb_wpml' ); ?>" />
					<input type="submit" class="reset-button button-secondary" name="reset" value="<?php esc_attr_e( 'Restore Defaults', 'tb_wpml' ); ?>" onclick="return confirm( '<?php print esc_js( __( 'Click OK to reset. Any theme settings will be lost!', 'tb_wpml' ) ); ?>' );" />
					<?php if( $current_lang != $default_lang ) : ?>
						<input type="submit" class="reset-button button-secondary" name="match" value="<?php esc_attr_e( 'Match Default Language', 'tb_wpml' ); ?>" onclick="return confirm( '<?php print esc_js( __( 'Click OK to match options. You will lose your current settings for this language and they will be matched to whatever you\'ve set for your default language.', 'tb_wpml' ) ); ?>' );" />
					<?php endif; ?>
					<div class="clear"></div>
					</div>
					<div class="tb-footer-text">
						<?php do_action( 'themeblvd_options_footer_text' ); ?>
					</div><!-- .tb-footer-text (end) -->
				</div> <!-- #container (end) -->
				<div class="admin-module-footer">
					<?php do_action( 'themeblvd_admin_module_footer', 'options' ); ?>
				</div>
			</div>
		</form>
	</div><!-- .wrap (end) -->
<?php
}

/**
 * Add WPML title to top of theme options along with menu to switch 
 * language.
 * 
 * This is the header that allows the user to switch between option 
 * sets for each language. Its hooked onto "themeblvd_admin_module_header"
 * which is called from the above "optionsframework_page" function.
 *
 * @since 1.0.0
 */

function tb_wpml_admin_module_header( $page ) {
	if( $page == 'options' ) {
		
		// Don't continue if the WPML plugin 
		// hasn't been installed.
		if( ! function_exists( 'icl_get_languages' ) )
			return;
		
		// Get all languages
		$langs = icl_get_languages();
	
		// Setup check array
		$langs_check = array();
		foreach( $langs as $key => $lang )
			$langs_check[] = $key;
		
		// Set default language
		$default_lang = 'en'; // backup
		$wpml_options = get_option( 'icl_sitepress_settings' );
		if( isset( $wpml_options['default_language'] ) ) 
			$default_lang = $wpml_options['default_language'];
		
		// Set current options language
		$current_lang = $default_lang;
		if( isset( $_GET['themeblvd_lang'] ) )
			$current_lang = $_GET['themeblvd_lang'];
		if( ! in_array( $current_lang, $langs_check ) )
			$current_lang = $default_lang;

		?>
		<div class="tb-wpml-header">
			<h3>
				<span class="tb-wpml-flag"><img src="<?php echo $langs[$current_lang]['country_flag_url']; ?>" /></span>
				<?php printf( __( '%1$s Theme Options', 'tb_wpml' ), $langs[$current_lang]['translated_name'] ); ?>
			</h3>
			<span class="tb-wpml-logo">Theme Blvd WPML Bridge</span>
			<div class="tb-wpml-nav">
				<ul>
					<?php if( $langs ) : ?>
						<?php foreach( $langs as $key => $lang ) : ?>
							<li<?php if($key == $current_lang ) echo ' class="active"'; ?>>
								<a href="?page=options-framework&themeblvd_lang=<?php echo $key ?>">
									<?php echo $lang['translated_name']; ?>
								</a>
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
				<?php if( $current_lang != $default_lang ) : ?>
					<input type="submit" class="reset-button button-secondary" name="match" value="<?php esc_attr_e( 'Match Default Language', 'tb_wpml' ); ?>" onclick="return confirm( '<?php print esc_js( __( 'Click OK to match options. You will lose your current settings for this language and they will be matched to whatever you\'ve set for your default language.', 'tb_wpml' ) ); ?>' );" />
				<?php endif; ?>
			</div><!-- .tb-wpml-nav (end) -->
		</div><!-- .tb-wpml-header (end) -->
		<?php
	}
}
add_action( 'themeblvd_admin_module_header', 'tb_wpml_admin_module_header');