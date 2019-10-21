<?php

/**
 * Plugin Name: ACF Agency Workflow
 * Description: Tweak ACF for a better team-based development workflow.
 */

/**
 * Todo:
 */

if ( !defined( 'ABSPATH' ) )
    exit;

include 'helpers.php';
include 'functions.php';

$aaw_feedback = [];

/**
 * Change settings.
 */

if ( !defined( 'WP_ENV' ) || WP_ENV != 'local' )
{
    if ( aaw_is_acf_active() )
    {
        add_action( 'acf/init', function () {
            // https://www.advancedcustomfields.com/resources/acf-settings/
            // Hide the 'Custom fields' menu item.
            acf_update_setting( 'show_admin', false );
        });
    }
}

/**
 * Admin notices.
 */

add_action( 'admin_notices', function () {

    global $aaw_feedback;

    if ( empty( $aaw_feedback ) )
        return;

    foreach ( $aaw_feedback as $feedback )
        printf( '<div class="%1$s">%2$s</div>', 'notice notice-success', $feedback );
});

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

