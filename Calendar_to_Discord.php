<?php
/**
 * Plugin Name: Calendar to Discord
 * Plugin URI: https://www.github.com/RealHartlMax/Calendar-to-Discord
 * Description: This plugin sends a Discord message when a new event is created in The Events Calendar plugin.
 * Version: 1.0
 * Author: RealHartlMax
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
        ?>
        <div class="wrap">
            <?php screen_icon();?>
            <h2>Webhook for Discord</h2>
            <p>
                <?php
                    if (isset($_GET['webhook']) && $_GET['webhook'] === 'test') {
                        echo 'Test wurde ausgeführt. ';
                        send_discord_message();
                    } else {
                        echo '<a href="options-general.php?page=' . $this->slug . '&amp;webhook=test">Teste den Webhook</a>';
                    }
                ?>
            </p>
            <form method="post" action="options.php">
             <?php
                // This prints out all hidden setting fields
                settings_fields($this->optionGroup);
                do_settings_sections($this->slug);
                submit_button();
            ?>
            </form>
        </div>
        <?php
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
                'description' => 'Mit Komma getrennt ohne @. z.B. "everyone,events"'
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
add_action('tribe_events_single_event_before_the_content', 'send_discord_message_on_new_event');
function send_discord_message_on_new_event()
{
    // Check if this is a new event
    if (is_new_event()) {
        // Send Discord message
        send_discord_message();
    }
}

function is_new_event()
{
    // Check if event is new
    return true;
}

function send_discord_message()
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
        'content' => $webhook_message
    ];

    if (strlen($mentions) > 0) {
        $body['allowed_mentions'] = [
            "parse" => explode(',', $mentions)
        ];
        $message_mentions = '';
        foreach (explode(',', $mentions) as $mention) {
            $message_mentions = '@' . $mention . ' ';
        }
        $body['content'] = $message_mentions . $body['content'];
    }


    // Set up request data
    $request_data = [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($body)
    ];

    // Send request
    $response = wp_remote_post($webhook_url, $request_data);

    // Check for errors
    if (is_wp_error($response)) {
        // Log error message
        if (is_admin()) {
            echo 'Fehler: ' . $response->get_error_message();
        }
        error_log('Error sending Discord message: ' . $response->get_error_message());
    } else {
        // Log success message
        if (is_admin()) {
            echo 'Ausführen war erfolgreich.';
        }
        error_log('Successfully sent Discord message.');
    }
}
