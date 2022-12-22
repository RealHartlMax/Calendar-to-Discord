<?php
/**
 * Plugin Name: Calendar to Discord
 * Plugin Uri: https://www.github.com/RealHartlMax/Calendar-to-Discord
 * Description: Sends a message to a Discord channel when an event is created in the Wordpress Plugin My Events Calendar.
 * Version: 1.0
 * Author: RealHartlMax
 * Author Uri: https://www.hartlmax.de
 * License: GPL2
 */

// Register the setting
register_setting( 'my_events_calendar_extension_settings', 'my_events_calendar_extension_discord_webhook' );

// Add a settings section
add_settings_section(
  'my_events_calendar_extension_section',
  'Discord Webhook Settings',
  'my_events_calendar_extension_section_callback',
  'my_events_calendar_extension_settings'
);

// Add a settings field
add_settings_field(
  'my_events_calendar_extension_discord_webhook',
  'Discord Webhook URL',
  'my_events_calendar_extension_webhook_callback',
  'my_events_calendar_extension_settings',
  'my_events_calendar_extension_section'
);

// Callback function for the settings section
function my_events_calendar_extension_section_callback() {
  echo 'Enter the Discord webhook URL to use for this extension:';
}

// Callback function for the webhook settings field
function my_events_calendar_extension_webhook_callback() {
  $webhook_url = esc_attr( get_option( 'my_events_calendar_extension_discord_webhook' ) );
  echo '<input type="text" name="my_events_calendar_extension_discord_webhook" value="' . $webhook_url . '" />';
}

// Function to send a message to Discord using the webhook
function my_events_calendar_extension_send_discord_message( $event_id ) {
  // Get the webhook URL
  $webhook_url = get_option( 'my_events_calendar_extension_discord_webhook' );

  // Get the event data
  $event = get_post( $event_id );
  $event_title = $event->post_title;
  $event_url = get_permalink( $event_id );
  $event_thumbnail = get_the_post_thumbnail_url( $event_id );

  // Build the message payload
  $payload = array(
    'username' => 'Calendar to Discord',
    'avatar_url' => $event_thumbnail,
    'embeds' => array(
      array(
        'title' => $event_title,
        'url' => $event_url,
      ),
    ),
  );

  // Send the message to Discord
  $ch = curl_init( $webhook_url );
  curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
  curl_setopt( $ch, CURLOPT_POST, 1 );
  curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $payload ) );
  curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
  curl_setopt( $ch, CURLOPT_HEADER, 0 );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
  $response = curl_exec( $ch );
  curl_close( $ch );
}

// Hook into the event creation process
add_action( 'tribe_events_create_event', 'my_events_calendar_extension_send_discord_message' );

// Add the options page
function my_events_calendar_extension_options_page() {
  ?>
  <div class="wrap">
    <h1>Calendar to Discord Settings</h1>
    <form method="post" action="options.php">
      <?php
      settings_fields( 'my_events_calendar_extension_settings' );
      do_settings_sections( 'my_events_calendar_extension_settings' );
      submit_button();
      ?>
    </form>
  </div>
  <?php
}

// Add the options page to the WordPress admin menu
function my_events_calendar_extension_add_options_page() {
  add_submenu_page(
    'edit.php?post_type=tribe_events',
    'Calendar to Discord Settings',
    'Calendar to Discord',
    'manage_options',
    'my_events_calendar_extension_settings',
    'my_events_calendar_extension_options_page'
  );
}
add_action( 'admin_menu', 'my_events_calendar_extension_add_options_page' );

// Ensure that the Discord webhook URL is not empty
function my_events_calendar_extension_validate_webhook_url( $input ) {
  if ( empty( $input ) ) {
    add_settings_error(
      'my_events_calendar_extension_discord_webhook',
      'my_events_calendar_extension_discord_webhook_error',
      'Please enter a valid Discord webhook URL',
      'error'
    );
    return '';
  }
  return $input;
}
add_filter( 'pre_update_option_my_events_calendar_extension_discord_webhook', 'my_events_calendar_extension_validate_webhook_url' );
