# ACF Agency Workflow

## What's good?

### Field Groups are stored with themes and plugins.

You already separate your website's looks from it's functionality. You should do the same with your custom fields. Store the header customizations with your theme, the company address fields with a core plugin, and those API settings with your API plugin.

### The JSON is the truth.

You build websites with others using git. You can't pull or push your database, so we've made the ACF JSON "cache" the source of truth. Your JSON files will automatically get synced to your database, because fields are only active if they're in the database.

### Developers manage Field Groups on local.

We can't expect every staging and production server to have git, or people to commit changes from that server. So to prevent needing to do that, we hide the ACF Field Groups interface entirely. Developers manage the fields on their local environment. Period. Your staging and production servers only "listen", and sync your changes to their database, making them active.

## Usage examples

Coming later.

## Requirements

This plugin was tested with:

- PHP 7.3
- WordPress 5.3.2
- Advanced Custom Fields PRO 5.8.7

Requirements may be lowered after proper testing.

## Installation

1. Download the plugin.
1. Activate the plugin.
1. Set JSON load locations in your theme(s) or plugin(s).

## Set an ACF local JSON location in a theme

Create an `acf-json` folder in your theme.

## Set an ACF local JSON location in a plugin

```
/**
 * ACF local JSON location.
 */

add_filter( 'acf/settings/load_json', function ( $paths ) {
    $paths[] = plugin_dir_path( __FILE__ ).'acf-json';
    return $paths;
});
```

## Contributing

If there's anything you'd like to see added or changed, please open an issue so we can talk about it. Forking is cool too.

## License

[MIT](/LICENSE) &copy; [Tim Brugman](https://timbr.dev/)