<?php
/**
 * Plugin Name: Calendar to Discord
 * Plugin URI: https://www.github.com/RealHartlMax/Calendar-to-Discord
 * Description: This plugin sends a Discord message when a new event is created in The Events Calendar plugin.
 * Version: 1.0
 * Author: RealHartlMax
 * Author URI: https://www.hartlmax.de
 */

$rhmc2d_slug = 'rhmc2d';

// Add settings page
add_action( 'admin_menu', 'my_plugin_menu' );
function my_plugin_menu() {
    add_options_page( 'Calendar to Discord Options', 'Calendar to Discord', 'manage_options', $rhmc2d_slug, 'my_plugin_options' );
}

// Add Settings Fields
add_action( 'admin_init',  'rhmc2d_settings_fields' );
function rhmc2d_settings_fields(){

	// I created variables to make the things clearer
	$page_slug = 'rhm_c2d';
	$option_group = 'rhm_c2d_settings';
    $section_id = 'rhm_c2d_section_id';

	// 1. create section
	add_settings_section(
		$section_id, // section ID
		'', // title (optional)
		'', // callback function to display the section (optional)
		$page_slug
	);

	// 2. register fields
	register_setting( $option_group, 'rhm_c2d_webhook_url');
	register_setting( $option_group, 'rhm_c2d_webhook_name');
	register_setting( $option_group, 'rhm_c2d_webhook_avatar');
	register_setting( $option_group, 'rhm_c2d_webhook_message');

	// 3. add fields
	add_settings_field(
		'rhm_c2d_webhook_url',
		'WebHook URL',
		'rhmc2d_field_webhook_url', // function to print the field
		$page_slug,
		$section_id // section ID
	);
	add_settings_field(
		'rhm_c2d_webhook_name',
		'WebHook Name',
		'rhmc2d_field_webhook_name', // function to print the field
		$page_slug,
		$section_id // section ID
	);
	add_settings_field(
		'rhm_c2d_webhook_avatar',
		'WebHook Avatar',
		'rhmc2d_field_webhook_avatar', // function to print the field
		$page_slug,
		$section_id // section ID
	);
	add_settings_field(
		'rhm_c2d_webhook_message',
		'WebHook Message',
		'rhmc2d_field_webhook_message', // function to print the field
		$page_slug,
		$section_id // section ID
	);
}

// custom callback functions to print field HTML
function rhmc2d_field_webhook_url( $args ){
	printf(
		'<input type="text" id="%s" name="%s" value="%s" />',
		$args[ 'name' ],
		$args[ 'name' ],
		get_option( $args[ 'name' ], '' ) // Second parameter is default value of the field
	);
}
function rhmc2d_field_webhook_name( $args ){
	printf(
		'<input type="text" id="%s" name="%s" value="%s" />',
		$args[ 'name' ],
		$args[ 'name' ],
		get_option( $args[ 'name' ], '' ) // Second parameter is default value of the field
	);
}
function rhmc2d_field_webhook_avatar( $args ){
	printf(
		'<input type="text" id="%s" name="%s" value="%s" />',
		$args[ 'name' ],
		$args[ 'name' ],
		get_option( $args[ 'name' ], '' ) // Second parameter is default value of the field
	);
}
function rhmc2d_field_webhook_message( $args ){
	printf(
		'<input type="text" id="%s" name="%s" value="%s" />',
		$args[ 'name' ],
		$args[ 'name' ],
		get_option( $args[ 'name' ], '' ) // Second parameter is default value of the field
	);
}

function my_plugin_options() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
	?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title() ?></h1>
			<form method="post" action="<?php echo admin_url('options-general.php?page=' . $rhmc2d_slug); ?>">
				<?php
					settings_fields( 'rhm_calendar_to_discord' ); // settings group name
					do_settings_sections( 'rhm_c2d' ); // just a page slug
					submit_button(); // "Save Changes" button
				?>
			</form>
		</div>
	<?php
}

// Send Discord message when new event is created
add_action( 'tribe_events_single_event_before_the_content', 'send_discord_message_on_new_event' );
function send_discord_message_on_new_event() {
    // Check if this is a new event
    if ( is_new_event() ) {
        // Send Discord message
        send_discord_message();
    }
}

function is_new_event() {
    // Check if event is new
    return true;
}

function send_discord_message() {
    // Get plugin settings
    $webhook_url = get_option( 'webhook_url' );
    $webhook_name = get_option( 'webhook_name' );
    $webhook_avatar = get_option( 'webhook_avatar' );
    $webhook_message = get_option( 'webhook_message' );

    // Set up request data
    $request_data = array(
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode( array(
            'username' => $webhook_name,
            'avatar_url' => $webhook_avatar,
            'content' => $webhook_message,
        ) ),
    );

    // Send request
    $response = wp_remote_post( $webhook_url, $request_data );

    // Check for errors
    if ( is_wp_error( $response ) ) {
        // Log error message
        error_log( 'Error sending Discord message: ' . $response->get_error_message() );
    } else {
        // Log success message
        error_log( 'Successfully sent Discord message.' );
    }
}
