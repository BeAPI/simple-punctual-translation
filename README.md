<a href="https://beapi.fr">![Be API Github Banner](.github/banner-github.png)</a>

# Simple Punctual Translation

A plugin for WordPress that allow to translate any post type in another languages. Translate only the single view.

## Description

A plugin for WordPress that allow to translate any post type in another languages.

The user features can be summarized in the ability to switch between multiple languages and on the single view of content. Thus, a page can be translated in X languages.

The architecture chosen for development is fully consistent with WordPress 3.0, we created a content type translation, and we created a taxonomy for the site languages.
We customized the WordPress admin console to provide the translation functionality, a bit of AJAX to make the convenient interface. Finally, we created a widget that displays the languages available for the currently loaded content.

A translator role is automatically created with the plugin, it allows a user to this role only to create and manage translations.

The plugin offers settings:

* Automatic insertion of languages available to the end of the article
* Language detection URL : via a "lang" parameter in the address or via a prefix beginning of the address:
    * http://www.herewithme.fr/contenu/?lang=de
    * or http://www.herewithme.fr/de/contenu
* Enabling translations on their choice of post types
* 2 modes for the translation mechanism, which I will explain in FAQ.

## Frequently Asked Questions

### What differences between the 2 translation engines ?

For this plugin, we did not impose an architecture defined for the translation engine, so we proposed an automatic or manual mode.

**Automatic mode**

The automatic mode is rather aimed at the general public, because no change is necessary in the source code. The idea is that, when sailing on the German version of a page, WordPress retrieves data from the original page, and our plugin is automatically injected the contents of German 3 fields, title, content and extract .
This means that the German version in automatic mode will keep, if your theme display it, publication date, comments, author, tags and categories of the original post.
This mode is quite sufficient to use the basic translation plugin on content types native, it is compatible to 99% on the existing WordPress installations.

**Manual mode**

This second mode is much more powerful than the first. The manual mode does not modify any data from the initial query of WordPress, so no modification is made on the theme, your content will not even be translated! To switch language, we were inspired by functions of WordPress Mu allowing switch between blogs, either `switch_to_blog()` and `restore_current_blog()`.
And we have created 2 functions `switch_to_language()` and `restore_original_language()`.

The first function `switch_to_language()` toggles the content in the translated version, while the second function `restore_original_language()` allow to restore the original language of the content.

Example :

`<?php
the_title(); // Title in English

switch_to_language();
the_title(); // Title in French
restore_original_language();

the_title(); // Title in English
?>`

This pair of functions allows developers to be extremely precise about which fields to translate. This mode in my opinion, should be widely preferred because it is clean, it does not interact with the original application of WordPress. Nevertheless, there are some flaws such as:

* The title page's HTML is not translated
* Plugins breadcrumb does not take into account the translation

These are mainly defects on the SEO aspect, and indeed on this first version of the plugin that we have worked the functional aspect. We rely on community feedback to improve the plugin ...

## Requirements

## Installation

The Simple Punctual Translation can be installed in 3 easy steps:

1. Unzip "Simple Punctual Translations" archive and put all files into your "plugins" folder (/wp-content/plugins/) or to create a sub directory into the plugins folder (recommanded), like /wp-content/plugins/simple-punctual-translation/
2. Activate the plugin
3. Inside the Wordpress admin, go to Options > Translations, adjust the parameters according to your needs, and save them.

## Screenshots

2. Settings page
3. Translations post type admin
4. Menu translations
5. Meta box for original content
6. Meta boxes for translation. Allow to choose the original content.
7. Widget settings

## Who ?

Created by [Be API](https://beapi.fr), the French WordPress leader agency since 2009. Based in Paris, we are more than 30 people and always [hiring](https://beapi.workable.com) some fun and talented guys. So we will be pleased to work with you.

This plugin is only maintained, which means we do not guarantee some free support. Consider reporting an [issue](#issues--features-request--proposal) and be patient.

If you really like what we do or want to thank us for our quick work, feel free to [donate](https://www.paypal.me/BeAPI) as much as you want / can, even 1€ is a great gift for buying cofee :)


## Changelog

* Version 1.1.5 :
  * Allow to customize query_var and rewrite keyword
  * Security, add some missing sanitizing
  * Security, add some SQL preparing
  * Fix some PHP compat 8+ bug
* Version 1.1.4 :
  * Fix wrong condition on save_post
* Version 1.1.3 :
  * Fix translate select dropdown query
* Version 1.1.2 :
  * Fix infinite redirect for rest API & WP-Cli
* Version 1.1.1 :
  * Fix error for not available roles
* Version 1.1.0 :
  * Put all constructors on __construct
  * Remove useless not working code
  * Add quality tools
  * Remove create_function
  * Fix code style
  * Fix some translation strings missing
* Version 1.0.5 :
  * Fix notice / sql error on 404 page
* Version 1.0.4 :
  * Use method __construct() for Widget Constructor
* Version 1.0.3 :
  * Fix preview link on admin
* Version 1.0.2 :
  * Add french translation
  * Add readme.txt
  * Add screenshots
  * Fix a bug with protection of post_parent with quick edit
* Version 1.0.1 :
  * Fix some PHP typos
* Version 1.0
  * Initial version
  