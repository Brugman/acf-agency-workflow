<?php

function aaw_unmet_requirements()
{
    $unmet_reqs = [];

    /**
     * WP_ENV.
     */

    if ( !defined( 'WP_ENV' ) )
        $unmet_reqs[] = 'WP_ENV is not defined.';

    /**
     * Missing local JSON locations.
     */

    $load_dirs = acf_get_setting('load_json');

    if ( !empty( $load_dirs ) )
    {
        $missing_load_dirs = array_filter( $load_dirs, function ( $dir ) {
            return !file_exists( $dir );
        });

        if ( !empty( $missing_load_dirs ) )
        {
            $wp_content_path = str_replace( '\\', '/', dirname( dirname( dirname( __FILE__ ) ) ) );

            foreach ( $missing_load_dirs as &$dir )
            {
                $dir = str_replace( '\\', '/', $dir.'/' );
                $dir = explode( $wp_content_path, $dir )[1];
                $dir = '<strong>'.$dir.'</strong>';
            }

            $unmet_reqs[] = 'ACF local JSON locations are missing: '.implode( ' and ', $missing_load_dirs );
        }
    }

    return $unmet_reqs;
}

function aaw_requirements_are_met()
{
    if ( !empty( aaw_unmet_requirements() ) )
        return false;

    return true;
}

function aaw_env_is_dev()
{
    if ( !defined( 'WP_ENV' ) )
        return true;

    $local_envs = [
        'local',
        'dev',
        'develop',
        'development',
    ];

    $local_envs = apply_filters( 'aaw_local_envs', $local_envs );

    if ( in_array( WP_ENV, $local_envs ) )
        return true;

    return false;
}

function aaw_is_acf_active()
{
    if ( in_array( 'advanced-custom-fields-pro/acf.php', get_option( 'active_plugins' ) ) )
        return true;

    return false;
}

function aaw_act_on_added_json()
{
    $unsynced_groups = aaw_get_unsynced_field_groups();

    if ( empty( $unsynced_groups ) )
        return;

    $synced_groups = aaw_sync_field_groups( $unsynced_groups );

    global $aaw_feedback;
    $aaw_feedback['synced'] = '';
    if ( aaw_env_is_dev() )
    {
        $aaw_feedback['synced'] .= '<p>'.__( 'The following ACF Field Groups were synced from their JSON cache:', 'acf-agency-workflow' ).'</p>';
        $aaw_feedback['synced'] .= '<ol>';
        foreach ( $synced_groups as $group )
            $aaw_feedback['synced'] .= '<li><a href="'.get_edit_post_link( $group['ID'] ).'">'.$group['title'].'</a></li>';
        $aaw_feedback['synced'] .= '</ol>';
    }
    else
    {
        $aaw_feedback['synced'] .= '<p>'.__( 'One or more ACF Field Groups were synced from their JSON cache.', 'acf-agency-workflow' ).'</p>';
    }
}

function aaw_get_unsynced_field_groups()
{
    // Get all FGs, also the unsynced json ones.
    $groups = acf_get_field_groups();
    // No FGs? No sync needed.
    if ( empty( $groups ) )
        return false;

    $unsynced_groups = [];

    foreach ( $groups as $group )
    {
        // TODO: double check acf_maybe_get

        // Not local? No sync needed.
        if ( !isset( $group['local'] ) )
            continue;
        // Not json? No sync needed.
        if ( $group['local'] !== 'json' )
            continue;
        // No id? Sync needed!
        if ( !$group['ID'] )
            $unsynced_groups[ $group['key'] ] = $group;
        // Has ID, but modified date is newer than the db? Sync needed!
        if ( $group['ID'] && $group['modified'] && $group['modified'] > get_post_modified_time( 'U', true, $group['ID'], true ) )
            $unsynced_groups[ $group['key'] ] = $group;
    }
    // Empty sync list? No sync needed.
    if ( empty( $unsynced_groups ) )
        return false;

    return $unsynced_groups;
}

function aaw_sync_field_groups( $unsynced_groups = [] )
{
    // Required stuff copied from ACF.
    acf_disable_filters();
    acf_enable_filter( 'local' );
    acf_update_setting( 'json', false );

    $synced_groups = [];

    foreach ( $unsynced_groups as $group )
    {
        // Add the fields.
        $group['fields'] = acf_get_fields( $group );
        // Sync.
        $synced_groups[] = acf_import_field_group( $group );
    }

    return $synced_groups;
}

function aaw_act_on_removed_json()
{
    $group_keys_db = aaw_get_field_group_keys_from_db();

    if ( empty( $group_keys_db ) )
        return;

    $group_keys_json = aaw_get_field_group_keys_from_json();

    $group_keys_delete_from_db = array_diff( $group_keys_db, $group_keys_json );

    if ( empty( $group_keys_delete_from_db ) )
        return;

    aaw_delete_field_groups_from_db( $group_keys_delete_from_db );

    global $aaw_feedback;
    $aaw_feedback['removed'] = '<p>'.__( 'One or more ACF Field Groups were removed because their JSON cache was removed.', 'acf-agency-workflow' ).'</p>';
}

function aaw_get_field_group_keys_from_db()
{
    $post_ids = get_posts([
        'post_type'      => 'acf-field-group',
        'posts_per_page' => -1,
        'post_status'    => ['publish','acf-disabled'],
        'fields'         => 'ids',
    ]);

    if ( empty( $post_ids ) )
        return [];

    $keys = [];

    foreach ( $post_ids as $post_id )
        $keys[] = get_post_field( 'post_name', $post_id );

    return $keys;
}

function aaw_get_field_group_keys_from_json()
{
    $load_dirs = acf_get_setting('load_json');

    if ( empty( $load_dirs ) )
        return [];

    $keys = [];

    foreach ( $load_dirs as $load_dir )
    {
        $json_files = glob( $load_dir.'/*.json' );
        foreach ( $json_files as $json_file )
        {
            // A. Actually check the key inside.
            // $group_json = file_get_contents( $json_file );
            // $group = json_decode( $group_json );
            // $keys[] = $group->key;

            // B. Take the filename.
            $keys[] = pathinfo( $json_file, PATHINFO_FILENAME );
        }
    }

    return $keys;
}

function aaw_delete_field_groups_from_db( $keys = [] )
{
    if ( empty( $keys ) )
        return;

    foreach ( $keys as $key )
        acf_delete_field_group( $key );
}

function aaw_delete_field_group_from_json( $key = false )
{
    if ( !$key )
        return;

    $load_dirs = acf_get_setting('load_json');

    if ( empty( $load_dirs ) )
        return;

    foreach ( $load_dirs as $load_dir )
    {
        $current_path = $load_dir.'/'.$key.'.json';
        if ( file_exists( $current_path ) )
            unlink( $current_path );
    }
}

function aaw_plugin_main_file()
{
    return dirname( __FILE__ ).'/acf-agency-workflow.php';
}

function aaw_current_version()
{
    return get_plugin_data( aaw_plugin_main_file(), false, false )['Version'];
}

function aaw_latest_version()
{
    $repo = 'Brugman/acf-agency-workflow';
    $latest_version_service = 'https://github-repo-latest-version.timbr.dev/';

    $remote_data = @file_get_contents( $latest_version_service.$repo );

    if ( !$remote_data )
        return false;

    $remote_data = json_decode( $remote_data, true );

    if ( isset( $remote_data['error'] ) )
        return false;

    return $remote_data;
}

function aaw_new_version_available()
{
    $current_version = aaw_current_version();
    $latest_version_data = aaw_latest_version();

    if ( !$latest_version_data )
        return false;

    $latest_version = $latest_version_data['version'];

    if ( version_compare( $latest_version, $current_version ) > 0 )
        return $latest_version_data;

    return false;
}

