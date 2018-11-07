INTRODUCTION
------------

The Social Auth Mastodon adds a possibility to log in to Drupal
with a Mastodon account.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/social_auth_mastodon

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/social_auth_mastodon

REQUIREMENTS
------------

This module requires the following modules:

 * Social Auth (https://www.drupal.org/project/social_auth)
 * Social API (https://www.drupal.org/project/social_api)

INSTALLATION
------------

 * Install 3rd party dependencies with Composer:
   composer require "drupal/social_auth_mastodon"

 * Install the dependencies: Social API and Social Auth

 * Install like a normal contributed module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules

CONFIGURATION
-------------

 * Add your Mastodon application OAuth information and instance URL in
   Configuration » User Authentication » Mastodon.

 * Place a Social Auth Mastodon block in Structure » Block Layout.

 * If you already have a Social Auth Login block in the site, rebuild the cache.

HOW IT WORKS
------------

User can click on the Mastodon logo on the Social Auth Login block
You can also add a button or link anywhere on the site that points
to /user/login/mastodon, so theming and customizing the button or link
is very flexible.

When the user opens the /user/login/mastodon link, it automatically takes
user to Mastodon instance specified in configuration for authentication. 
Google then returns the user to Drupal site. If we have an existing Drupal
user with connected account ID, that user is logged in. Otherwise a new
Drupal user is created.

SUPPORT REQUESTS
----------------

Before posting a support request, carefully read the installation
instructions provided in module documentation page.

Before posting a support request, check Recent log entries at
admin/reports/dblog

Once you have done this, you can post a support request at module issue queue:
https://www.drupal.org/project/issues/social_auth_mastodon

When posting a support request, please inform if you were able to see any errors
in Recent log entries.

MAINTAINERS
-----------

Current maintainers:
 * rnickson (rnickson) - https://www.drupal.org/u/rnickson
