# ACF Agency Workflow

## Ideals

### Fields are stored with themes and plugins.

You already separate your website's looks from it's functionality. You should do the same with your custom fields. For example: Store the header settings with your theme, the client's address fields with their core functionality plugin, and those API settings with your API plugin.

### Fields are in your Git repo.

You collaborate using git. So we've made the ACF JSON *cache* the source of truth. How? Your JSON files get forcefully synced to your database when an administrator loads the Dashboard or Field Groups page. If you've made changes to field, then you have JSON changes to commit.

### Fields are managed by developers on local with the Field Group Editor.

We can't expect every staging and production server to have git. Nor can we expect people to push changes from a server to git. So to prevent needing to do that, we hide the ACF Field Groups interface entirely on any non-local environment.

Developers manage the fields locally. Nobody else, nowhere else. Period. Your staging and production servers only *listen*, and sync your changes to their database, making them active.

## For your information

- You can safely move existing Field Groups to a new location by editing them and setting a new JSON Save path.
- Do not rename Field Group JSON files. For better performance this plugin grabs the Key from the filename, not from the inside of the file. (`group_5e287670568af.json`) Traditionally you could rename the JSON files and the Field Groups would still work.
- Remove and stop using `"private": true,` in your JSON. It's incompatible with this plugin.
- There is no Field Group Trash anymore. The Field Group Trash link has been replaced with a Delete Permanently link. Traditional Trash has no JSON file, so it would get purged from the database by the sync functionality. To avoid confusion about this, it's been replaced with a Delete link.
- The Field Groups backend *menu link* has been removed on non-local environments to deter use. The page is still available however, if you visit `/edit.php?post_type=acf-field-group`.

## Usage examples

Coming later.

## Requirements

This plugin was developed with:

- PHP 7.3
- WordPress 5.3.2
- Advanced Custom Fields PRO 5.8.7

Requirements may be lowered after proper testing.

## Installation

1. Back up any existing Field Groups as a precaution.
1. Define `WP_ENV` as `local` on your local environment.
1. Download & activate ACF PRO.
1. Download & activate ACF Agency Workflow.
1. Set ACF local JSON load locations.

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

Found a bug? Anything you'd like to ask, add, change, or have added or changed? Please open an issue so we can talk about it.

## Disclaimer

The author(s) are not responsible for lost fields or other data. This plugin deletes things, so it can be dangerous. Backups are a good idea. Git can be your backup too.

## License

[MIT](/LICENSE) &copy; [Tim Brugman](https://timbr.dev/)