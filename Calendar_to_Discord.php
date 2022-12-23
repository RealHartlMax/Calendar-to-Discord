<?php
/**
 * Plugin Name: Calendar to Discord
 * Plugin URI: https://www.github.com/RealHartlMax/Calendar-to-Discord
 * Description: This plugin sends a Discord message when a new event is created in The Events Calendar plugin.
 * Version: 1.0
 * Author: RealHartlMax
 * Author URI: https://www.hartlmax.de
 */

// Add settings page
add_action( 'admin_menu', 'my_plugin_menu' );
function my_plugin_menu() {
    add_options_page( 'Calendar to Discord Options', 'Calendar to Discord', 'manage_options', 'my-unique-identifier', 'my_plugin_options' );
}

function my_plugin_options() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    echo '<div class="wrap">';
    echo '<p>Here you can configure the settings for the plugin.</p>';
    </ hr>
    <form action="webhook_url" method="post">
        Webhook URL: <input type="text" name="webhook_url" value="">
            <input type="save">
            </form> 
    echo '</div>';
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
