<?php
/**
 * Plugin Name: CLI Adventure
 * Description: A choose-your-own-adventure game played entirely in a terminal-like interface via shortcode [cli_adventure].
 * Version: 0.1.0
 * Author: Caleb Vorwerk
 * Text Domain: cli-adventure
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin paths
if ( ! defined( 'CLI_ADVENTURE_PATH' ) ) {
    define( 'CLI_ADVENTURE_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'CLI_ADVENTURE_URL' ) ) {
    define( 'CLI_ADVENTURE_URL', plugin_dir_url( __FILE__ ) );
}

// Include core classes
require_once CLI_ADVENTURE_PATH . 'includes/class-game-engine.php';
require_once CLI_ADVENTURE_PATH . 'includes/class-state-manager.php';

/**
 * Enqueue styles and scripts for the terminal interface
 */
function cli_adventure_enqueue_assets() {
    // 1) jQuery Terminal core CSS
    wp_enqueue_style(
        'jquery-terminal-css',
        'https://cdn.jsdelivr.net/npm/jquery.terminal@2.37.0/css/jquery.terminal.min.css',
        array(),
        '2.37.0'
    );

    // 2) Custom terminal overrides (dependent on core CSS)
    wp_enqueue_style(
        'cli-adventure-terminal-css',
        CLI_ADVENTURE_URL . 'assets/css/terminal.css',
        array( 'jquery-terminal-css' ),
        '0.1.0'
    );

    // 3) jQuery Terminal JS
    wp_enqueue_script(
        'jquery-terminal',
        'https://cdn.jsdelivr.net/npm/jquery.terminal@2.37.0/js/jquery.terminal.min.js',
        array( 'jquery' ),
        '2.37.0',
        true
    );

    // 4) Custom terminal initialization
    wp_enqueue_script(
        'cli-adventure-terminal-js',
        CLI_ADVENTURE_URL . 'assets/js/terminal.js',
        array( 'jquery', 'jquery-terminal' ),
        '0.1.0',
        true
    );

    // Pass AJAX URL and nonce to JS
    wp_localize_script(
        'cli-adventure-terminal-js',
        'CLI_ADVENTURE_Ajax',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'cli_adventure_nonce' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'cli_adventure_enqueue_assets' );

/**
 * Shortcode to render the terminal container
 */
function cli_adventure_shortcode() {
    ob_start();
    include CLI_ADVENTURE_PATH . 'templates/terminal-shortcode.php';
    return ob_get_clean();
}
add_shortcode( 'cli_adventure', 'cli_adventure_shortcode' );

/**
 * AJAX handler for processing commands
 */
function cli_adventure_handle_command() {
    check_ajax_referer( 'cli_adventure_nonce', 'nonce' );

    $state   = isset( $_POST['state'] ) ? json_decode( stripslashes( $_POST['state'] ), true ) : array();
    $command = isset( $_POST['command'] ) ? sanitize_text_field( $_POST['command'] ) : '';

    $engine = new CLI_Adventure_Game_Engine();
    $result = $engine->handle_command( $state, $command );

    wp_send_json_success( $result );
}
add_action( 'wp_ajax_cli_adventure', 'cli_adventure_handle_command' );
add_action( 'wp_ajax_nopriv_cli_adventure', 'cli_adventure_handle_command' );
