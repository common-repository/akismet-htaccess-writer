=== Akismet htaccess writer ===
Tags: akismet, htaccess, comments, spam
Requires at least: 2.5.1
Tested up to: 2.5.1
Stable tag: 1.0.1

in connection with Akismet, write in a .htaccess file

== Description ==

* When judging that Akismet is SPAM when the comment is processed
* When marking it as SPAM in the comment section of the dashboard
* When you make the approval status SPAM when the comment section of the dashboard edits the comment

List IP address from the database in case of the above-mentioned, and Deny the IP address. .htaccess is updates.

It changes in the comment block from
# BEGIN written by WordPress plugin - Akismet htaccess writer
to
# END written by WordPress plugin - Akismet htaccess writer
in the content of the .htaccess file.

After the .htaccess file, it is written in the tail when the block identifier is not found.

***** Please do not do the contact to the author of the Akismet plug-in for this plug-in *****

== Installation ==

If you have ever installed a WordPress plugin, then installation will be pretty easy:

1. Download the akismet-htaccess-writer plugin archive and extract the files
2. Copy the resulting akismet-htaccess-writer directory into /wp-content/plugins/
3. Activate the plugin through the 'Plugins' menu of WordPress
4. It becomes a writing object. The .htaccess filename is configuration.

