<?php

/**
 * Plugin Name: ACF Agency Workflow
 * Description: Create, move, distribute and sync your Field Groups as JSON.
 * Version: 1.2.1
 * Plugin URI: https://github.com/Brugman/acf-agency-workflow
 * Author: Tim Brugman
 * Author URI: https://timbr.dev/
 */

if ( !defined( 'ABSPATH' ) )
    exit;

include 'functions.php';

/**
 * Respond to JSON changes.
 */

add_action( 'admin_init', function () {

    // Require ACF Pro.
    if ( !aaw_is_acf_active() )
        return;

    // Require administrator.
    if ( !current_user_can( 'manage_options' ) )
        return;

    // Only on the Dashboard and Field Groups page.
    global $pagenow;

    $is_dashboard         = ( $pagenow == 'index.php' );
    $is_field_groups_page = ( $pagenow == 'edit.php' && $_GET == [ 'post_type' => 'acf-field-group' ] );

    if ( !$is_dashboard && !$is_field_groups_page )
        return;

    // Add & update field groups.
    aaw_act_on_added_json();

    // Remove field groups.
    aaw_act_on_removed_json();
});

/**
 * Respond to Field Group Editor changes.
 */

add_action( 'acf/delete_field_group', function ( $field_group ) {
    // Delete field group from JSON.
    aaw_delete_field_group_from_json( $field_group['key'] );
});

/**
 * Replace the FG trash buttons with delete buttons.
 */

add_filter( 'page_row_actions', function ( $actions, $post ) {

    if ( $post->post_type == 'acf-field-group' )
    {
        // Remove trash.
        unset( $actions['trash'] );
        // Add delete.
        $actions['delete'] = '<a href="'.get_delete_post_link( $post->ID, '', true ).'" aria-label="Delete “'.$post->post_title.'” permanently">'.__( 'Delete Permanently' ).'</a>';
    }

    return $actions;

}, 10, 2 );

/**
 * Display JSON save locations list.
 */

add_action( 'acf/render_field_group_settings', function ( $field_group ) {

    $choices = [];

    $path_to_plugins = dirname( dirname( __FILE__ ) );
    $path_to_themes = dirname( get_stylesheet_directory() );

    $load_dirs = acf_get_setting('load_json');

    if ( empty( $load_dirs ) )
        return;

    foreach ( $load_dirs as $load_dir )
    {
        $display_title = $load_dir;

        if ( strpos( $load_dir, $path_to_plugins ) !== false )
        {
            $display_title = __( 'Plugin', 'acf-agency-workflow' ).': '.substr( str_replace( $path_to_plugins, '', $load_dir ), 1 );
        }

        if ( strpos( $load_dir, $path_to_themes ) !== false )
        {
            $label = __( 'Theme', 'acf-agency-workflow' );
            if ( is_child_theme() )
            {
                if ( strpos( $load_dir, get_stylesheet_directory() ) !== false )
                    $label = __( 'Theme (child)', 'acf-agency-workflow' );

                if ( strpos( $load_dir, get_template_directory() ) !== false )
                    $label = __( 'Theme (parent)', 'acf-agency-workflow' );
            }

            $display_title = $label.': '.substr( str_replace( $path_to_themes, '', $load_dir ), 1 );
        }

        $choices[ $load_dir ] = $display_title;

        if ( file_exists( $load_dir.'/'.$field_group['key'].'.json' ) )
            $load_dir_selected = $load_dir;
    }

    asort( $choices );

    acf_render_field_wrap([
        'label'        => __( 'JSON Save Path', 'acf-agency-workflow' ),
        'instructions' => __( 'Where do you want the Field Group saved?', 'acf-agency-workflow' ),
        'type'         => 'select',
        'name'         => 'json_save_path',
        'prefix'       => 'acf_field_group',
        'value'        => $load_dir_selected ?? '',
        'choices'      => $choices,
    ]);
});

/**
 * Get selected JSON save location.
 */

$global_preferred_save_path = false;

add_action( 'acf/update_field_group', function ( $field_group ) {

    // Skip everything if this is not a FGE location move.
    if ( !isset( $field_group['json_save_path'] ) )
        return $field_group;

    // Delete JSON cache for this field group.
    aaw_delete_field_group_from_json( $field_group['key'] );

    // Reset save location.
    global $global_preferred_save_path;
    $global_preferred_save_path = false;

    // Store save location.
    if ( $field_group['json_save_path'] != 'default' )
        $global_preferred_save_path = $field_group['json_save_path'];

    return $field_group;

}, 1, 1 );

/**
 * Remove locations that don't exist.
 */

add_filter( 'acf/settings/load_json', function ( $paths ) {

    return array_filter( $paths, function ( $path ) {
        return file_exists( $path );
    });

}, 20, 1 );

/**
 * Set selected JSON save location.
 */

add_action( 'acf/settings/save_json', function ( $path ) {

    global $global_preferred_save_path;

    // Set save location.
    if ( $global_preferred_save_path )
        return $global_preferred_save_path;

    return $path;

}, 999 );

/**
 * Remove save path value before it gets written to JSON.
 */

add_filter( 'acf/prepare_field_group_for_export', function ( $field_group ) {

    if ( isset( $field_group['json_save_path'] ) )
        unset( $field_group['json_save_path'] );

    return $field_group;
});

/**
 * Notice: Sync feedback.
 */

$aaw_feedback = [];

add_action( 'admin_notices', function () {

    global $aaw_feedback;

    if ( empty( $aaw_feedback ) )
        return;

    foreach ( $aaw_feedback as $feedback )
        printf( '<div class="%1$s">%2$s</div>', 'notice notice-success', $feedback );
});

/**
 * Notice: Requirements not met.
 */

add_action( 'admin_notices', function () {

    $unmet_reqs = aaw_unmet_requirements();

    if ( empty( $unmet_reqs ) )
        return;

    $feedback = '';
    $feedback .= '<p><strong>'.__( 'ACF Agency Workflow is not ready!', 'acf-agency-workflow' ).'</strong></p>';
    $feedback .= '<ol>';
    foreach ( $unmet_reqs as $unmet_req )
        $feedback .= '<li>'.__( $unmet_req, 'acf-agency-workflow' ).'</li>';
    $feedback .= '</ol>';
    printf( '<div class="%1$s">%2$s</div>', 'notice notice-warning', $feedback );
});

/**
 * Notice: Non-development environment.
 */

add_action( 'admin_notices', function () {

    if ( aaw_env_is_dev() )
        return;

    if ( !isset( $_GET['post_type'] ) || $_GET['post_type'] != 'acf-field-group' )
        return;

    if ( isset( $_GET['page'] ) && $_GET['page'] == 'acf-settings-updates' )
        return;

    $feedback = '';
    $feedback .= '<p><strong>'.__( 'Non-development environment detected!', 'acf-agency-workflow' ).'</strong></p>';
    $feedback .= '<p>'.__( 'You are not on a development environment. Please do not add or edit Field Groups. If you do not know why this message is here, contact the developer who installed <em>ACF Agency Workflow</em> before continuing.', 'acf-agency-workflow' ).'</p>';
    printf( '<div class="%1$s">%2$s</div>', 'notice notice-warning', $feedback );
});

/**
 * Notice: New version available.
 */

add_action( 'admin_notices', function () {

    global $pagenow;

    if ( $pagenow != 'plugins.php' )
        return;

    $new_version = aaw_new_version_available();

    if ( !$new_version )
        return;

    $feedback = '';
    $feedback .= '<p><strong>There is a new version of ACF Agency Workflow available.</strong></p>';

    if ( aaw_env_is_dev() )
    {
        $feedback .= '<p>Current version: <strong>'.aaw_current_version().'</strong>, latest version: <strong>'.$new_version['version'].'</strong>.</p>';
        $feedback .= '<p>Find out what\'s new in the <a href="https://github.com/Brugman/acf-agency-workflow/blob/develop/CHANGELOG.md" target="_blank">changelog</a>.</p>';
        $feedback .= '<p>Updating has to be done manually. Download the <a href="'.$new_version['zip'].'">ZIP file</a> or <a href="'.$new_version['tar'].'">tarball</a>, and replace the contents of the plugin folder. Or <code>git pull</code> from inside the plugin\'s directory.</p>';
    }
    else
    {
        $feedback .= '<p>Please ask your website developer to perform the update.</p>';
    }

    printf( '<div class="%1$s">%2$s</div>', 'notice notice-warning', $feedback );
});

