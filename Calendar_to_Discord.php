<?php
/**
 * Plugin Name: Calendar to Discord
 * Plugin URI: https://www.github.com/RealHartlMax/Calendar-to-Discord
 * Description: This plugin sends a Discord message when a new event is created in The Events Calendar plugin.
 * Version: 1.0.0
 * Author: RealHartlMax, Florian König-Heidinger
 * Author URI: https://www.hartlmax.de
 */

class RHM_C2D_OptionsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $slug = 'rhm_c2d';
    private $optionName = 'rhm_c2d';
    private $optionGroup = 'rhm_c2d_options';
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_init', [$this, 'page_init']);
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Calendar to Discord', // page title
            'Calendar to Discord', // menu title
            'manage_options', // capability
            $this->slug, // menu slug
            [$this, 'create_admin_page']// function to print admin page
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option($this->optionName);

        echo '<div class="wrap">';
        screen_icon();
        echo '<h2>Webhook for Discord</h2>';
        if (isset($_GET['webhook']) && $_GET['webhook'] === 'test') {
            echo '<p>';
            echo 'Test wurde ausgeführt. ';
            send_discord_message([
                'title' => get_option('blogname'),
                'url' => get_option('home'),
                'description' => get_option('blogdescription'),
                'color' => 15258703,
            ]);
            echo '<br />';
            echo '<a href="options-general.php?page=' . $this->slug . '">Testmodus beenden</a>';
            echo '</p>';
        } else {
            echo '<a href="options-general.php?page=' . $this->slug . '&amp;webhook=test">Teste den Webhook</a>';
            echo '<form method="post" action="options.php">';
            // This prints out all hidden setting fields
            settings_fields($this->optionGroup);
            do_settings_sections($this->slug);
            submit_button();
            echo '</form>';
        }
        echo '</div>';
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            $this->optionGroup, // Option group
            $this->optionName, // Option name
            [$this, 'sanitize']// Sanitize
        );

        $sectionId = 'webhook-section';
        add_settings_section(
            $sectionId, // ID
            'Webhook', // Title
            [$this, 'print_section_info'], // Callback
            $this->slug// Page
        );

        add_settings_field(
            'url', // ID
            'URL', // Title
            [$this, 'print_input_field'], // Callback
            $this->slug, // Page
            $sectionId, // Section
            [
                'name' => 'url',
            ]
        );
        add_settings_field(
            'name', // ID
            'Name', // Title
            [$this, 'print_input_field'], // Callback
            $this->slug, // Page
            $sectionId, // Section
            [
                'name' => 'name',
            ]
        );
        add_settings_field(
            'avatar', // ID
            'Avatar', // Title
            [$this, 'print_input_field'], // Callback
            $this->slug, // Page
            $sectionId, // Section
            [
                'name' => 'avatar',
            ]
        );
        add_settings_field(
            'message', // ID
            'Message', // Title
            [$this, 'print_input_field'], // Callback
            $this->slug, // Page
            $sectionId, // Section
            [
                'name' => 'message',
            ]
        );
        add_settings_field(
            'mentions', // ID
            'Mentions', // Title
            [$this, 'print_input_field'], // Callback
            $this->slug, // Page
            $sectionId, // Section
            [
                'name' => 'mentions',
                'description' => 'Mit Komma getrennt ohne @. z.B. "everyone,events"',
            ]
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input)
    {

        if (!empty($input['url'])) {
            $input['url'] = sanitize_text_field($input['url']);
        }

        if (!empty($input['name'])) {
            $input['name'] = sanitize_text_field($input['name']);
        }

        if (!empty($input['avatar'])) {
            $input['avatar'] = sanitize_text_field($input['avatar']);
        }

        if (!empty($input['message'])) {
            $input['message'] = sanitize_text_field($input['message']);
        }

        return $input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Webhook:';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function print_input_field($args)
    {
        printf(
            '<input type="text" id="%s" name="%s[%s]" value="%s" />',
            $args['name'],
            $this->optionName,
            $args['name'],
            esc_attr($this->options[$args['name']])
        );
        if (isset($args['description'])) {
            echo '<p>' . $args['description'] . '</p>';
        }
    }
}

if (is_admin()) {
    // Load class for Options Page
    $my_settings_page = new RHM_C2D_OptionsPage();
}

// Send Discord message when new event is created
add_action('transition_post_status', 'send_discord_message_on_new_event', 10, 3);
function send_discord_message_on_new_event($new_status, $old_status, $post)
{

    if ( 'tribe_events' !== get_post_type($post) ) {
        return;
    }

    if ( $old_status === $new_status ) {
        return;
    }

    // Check if this is a new event
    if ($new_status === 'publish') {
        // Send Discord message with Link to website
        $embed = [
            'title' => get_the_title($post),
            'url' => get_permalink($post),
            'description' => get_the_excerpt($post),
            'color' => 6570405,
        ];
        $thumbnail = get_the_post_thumbnail_url($post);
        if ($thumbnail) {
            $embed['image'] = ['url' => $thumbnail];
        }

        send_discord_message($embed);
    }
}


function send_discord_message($embed = null)
{
    // Get plugin settings
    $options = get_option('rhm_c2d');
    $webhook_url = $options['url'];
    $webhook_name = $options['name'];
    $webhook_avatar = $options['avatar'];
    $webhook_message = $options['message'];
    $mentions = $options['mentions'] ?: '';

    $body = [
        'username' => $webhook_name,
        'avatar_url' => $webhook_avatar,
        'content' => $webhook_message,
    ];

    if (strlen($mentions) > 0) {
        $body['allowed_mentions'] = [
            "parse" => explode(',', $mentions),
        ];
        $message_mentions = '';
        foreach (explode(',', $mentions) as $mention) {
            $message_mentions = '@' . $mention . ' ';
        }
        $body['content'] = $message_mentions . $body['content'];
    }

    if ($embed) {
        $body['embeds'] = [$embed];
    }

    // Set up request data
    $request_data = [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($body),
    ];

    // Send request
    $response = wp_remote_post($webhook_url, $request_data);

    // Check for errors
    if (is_wp_error($response)) {
        // Log error message
        // if (is_admin()) {
        //     echo 'Fehler: ' . $response->get_error_message();
        // }
        error_log('Error sending Discord message: ' . $response->get_error_message());
    } else {
        // Log success message
        // if (is_admin()) {
        //     echo 'Ausführen war erfolgreich.';
        // }
        error_log('Successfully sent Discord message.');
    }
}
