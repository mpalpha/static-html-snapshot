=== Static Snapshot ===
Contributors: Jason Lusk
Author link: http://jasonlusk.com
Tags: static, snapshot, relative
Requires at least: 4.3.1
Tested up to: 4.9.5
Stable tag: 0.0.1
License: MIT License or later
License URI: https://opensource.org/licenses/MIT

Download a fully functional static version of your WordPress website.

== Description ==

Produces a static snapshot of your WordPress site.
Files are renamed with the correct extension if they come from a CDN.
All links produced are relative - they won't redirect to the original website but to the other static files.

This plugin is a wrapper for the Linux/Unix wget application. Your server must use Linux/Unix!

== Installation ==

1. Download the plugin.
2. Install the plugin.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. A new item "Get Snapshot" is now available in the admin sidebar.
5. Go nuts!

== Frequently Asked Questions ==

= Will this plugin work locally on Windows? =

No, sorta. This plugin has been tested and works locally with Linux or Unix - but by all means, give it a go with wget for Windows (https://eternallybored.org/misc/wget/) and see what happens.
Mac OS X users need wget installed - here's a guide: http://osxdaily.com/2012/05/22/install-wget-mac-os-x/
