<?php
/*
Plugin Name: Live YouTube Stream
Description: Display live YouTube streams from a specified channel with a custom message and blank player when no stream is active. Automatically removes the player when the live ends.
Version: 1.7
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Add a settings page for the plugin
function lys_add_settings_page() {
    add_options_page(
        'Live YouTube Stream Settings',
        'Live YouTube Stream',
        'manage_options',
        'live-youtube-stream',
        'lys_render_settings_page'
    );
}
add_action('admin_menu', 'lys_add_settings_page');

// Render the settings page
function lys_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Live YouTube Stream Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('lys_settings_group');
            do_settings_sections('live-youtube-stream');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function lys_register_settings() {
    register_setting('lys_settings_group', 'lys_channel_id');
    register_setting('lys_settings_group', 'lys_api_key');

    add_settings_section(
        'lys_main_settings',
        'Main Settings',
        null,
        'live-youtube-stream'
    );

    add_settings_field(
        'lys_channel_id',
        'YouTube Channel ID',
        'lys_channel_id_callback',
        'live-youtube-stream',
        'lys_main_settings'
    );

    add_settings_field(
        'lys_api_key',
        'YouTube API Key',
        'lys_api_key_callback',
        'live-youtube-stream',
        'lys_main_settings'
    );
}
add_action('admin_init', 'lys_register_settings');

function lys_channel_id_callback() {
    $channel_id = get_option('lys_channel_id', '');
    echo '<input type="text" name="lys_channel_id" value="' . esc_attr($channel_id) . '" class="regular-text">';
}

function lys_api_key_callback() {
    $api_key = get_option('lys_api_key', '');
    echo '<input type="text" name="lys_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
}

// Shortcode to display live stream
function lys_live_stream_shortcode() {
    $channel_id = get_option('lys_channel_id', '');
    $api_key = get_option('lys_api_key', '');

    if (!$channel_id || !$api_key) {
        return '<p>Please configure the plugin settings to display the live stream.</p>';
    }

    $api_url = "https://www.googleapis.com/youtube/v3/search?part=snippet&channelId={$channel_id}&type=video&eventType=live&key={$api_key}";
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return '<p>Unable to fetch live stream data.</p>';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($data['items'])) {
        $video_id = $data['items'][0]['id']['videoId'];
        return '<div style="text-align: center;">
            <iframe id="youtube-player" width="800" height="450" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '?autoplay=1" 
            frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen></iframe>
            <script>
                setInterval(function() {
                    var iframe = document.getElementById("youtube-player");
                    if (iframe) {
                        fetch("' . esc_url($api_url) . '").then(response => response.json()).then(data => {
                            if (!data.items || data.items.length === 0) {
                                iframe.src = ""; // Reset the player if no live stream is active
                                document.getElementById("live-status").style.display = "block";
                            }
                        });
                    }
                }, 30000); // Check every 30 seconds
            </script>
        </div>
        <div id="live-status" style="display: none; text-align: center; font-size: 20px; font-weight: bold;">
            We\'re not live right now, but we\'d love for you to explore more on our YouTube channel. Click below to visit and stay connected!
            <a href="https://www.youtube.com/@uspcliverpoolpentecostalchurch" target="_blank">
                <img src="https://uspcliverpool.com/wp-content/uploads/2024/11/youtube.png" alt="YouTube Channel" width="100" />
            </a>
        </div>';
    } else {
        return '<div style="text-align: center;">
            <div style="margin-bottom: 20px;">
                <iframe width="800" height="450" src="" frameborder="0" style="background-color: #000;"></iframe>
            </div>
            <div style="font-size: 20px; font-weight: bold; margin-bottom: 20px;">
                We\'re not live right now, but we\'d love for you to explore more on our YouTube channel. Click below to visit and stay connected!
            </div>
            <a href="https://www.youtube.com/@uspcliverpoolpentecostalchurch" target="_blank">
                <img src="https://uspcliverpool.com/wp-content/uploads/2024/11/youtube.png" alt="YouTube Channel" width="100" />
            </a>
        </div>';
    }
}
add_shortcode('live_youtube_stream', 'lys_live_stream_shortcode');
