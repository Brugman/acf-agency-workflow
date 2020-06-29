# ACF Agency Workflow

## What do you get?

### Git your fields

You already use git for collaboration and version control. Let's put your custom fields in Git! We've made the ACF JSON *cache* the source of truth by forcefully syncing them to your database when you load the Dashboard or Field Groups page. If you've added, modified or deleted a field, then you have something to commit. If you pulled in new, modified or deleted fields, then you have something to sync. Every developer on your team now works with the same Field Groups.

### Store your fields with your themes and plugins

You already separate your website's looks from it's functionality (themes vs plugins). You can now do the same with your custom fields. For example: Store the header settings with your theme, the client's address fields with their core functionality plugin, and those API settings with your API plugin. Made a mistake? No problem. Edit the Field Group, choose a new location, save, and it's been moved.

## Requirements

This plugin was developed with:

- PHP 7.3
- WordPress 5.3.2
- Advanced Custom Fields PRO 5.8.7

Requirements may be lowered after proper testing.

## Installation

1. Back up any existing Field Groups as a precaution.
1. Define `WP_ENV` as `local` on your local environment. (`wp-config-local.php` perhaps?)
1. Download & activate ACF PRO.
1. Download & activate ACF Agency Workflow.
1. Set ACF local JSON load locations.

## How to

### Set an ACF local JSON location in a theme

Create an `acf-json` folder in your theme.

### Set an ACF local JSON location in a child theme

Create an `acf-json` folder in your child theme.

### Set an ACF local JSON location in a parent theme

Create an `acf-json` folder in your parent theme.\
Add this code to your parent theme's `functions.php`.

```
/**
 * ACF Local JSON location for a parent theme.
 */

add_filter( 'acf/settings/load_json', function ( $paths ) {

    $paths[] = get_template_directory().'/acf-json';

    return $paths;
});
```

### Set an ACF local JSON location in a plugin

Create an `acf-json` folder in your plugin.\
Add this code to your plugin's main file. (`plugin-name/plugin-name.php`)

```
/**
 * ACF local JSON location for a plugin.
 */

add_filter( 'acf/settings/load_json', function ( $paths ) {
    
    $paths[] = plugin_dir_path( __FILE__ ).'acf-json';

    return $paths;
});
```

### Add custom WP_ENV values for local

If you have a `WP_ENV` defined locally that AAW does not consider "local", you can add yours to the list with this filter.\
Add this code to a custom plugin or the theme's `functions.php`.

```
add_filter( 'aaw_local_envs', function ( $local_envs ) {

    return array_merge(
        $local_envs,
        ['lokaal'] // local in Dutch
    );
});
```

## Contributing

Found a bug? Anything you'd like to ask, add, change, or have added or changed? Please open an issue so we can talk about it.

## Disclaimer

The author(s) are not responsible for lost fields or other data. This plugin deletes things, so it can be dangerous. Backups are a good idea. Git can be your backup too.

## License

[MIT](/LICENSE) &copy; [Tim Brugman](https://timbr.dev/)