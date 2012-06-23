sfErrorNotifierPlugin
=====================

The `sfErrorNotifierPlugin` sends automatically email notifications when application errors (exceptions) occur and are not caught.

Its easy configuration allows you to set which environments to enable for the notifications.

The details about the thrown exceptions and other useful parameteers are sent via email.

NEW! It is also possible to trigger notifications explicitly by using this line from anywhere in your code (the email will be sent only from the environments enabled for the notifications):

```php
sfErrorNotifier::send(new Exception('Message To Send'));
```

Installation
------------

* Install the plugin
  
```bash
php symfony plugin:install sfErrorNotifierPlugin
```

* In the file /config/error_notifier.yml, set the email address(es) to deliver the notifications to, for the environments you want to enable (tipically only 'prod').
You must set an array, allawed multiple recipients.

```php
prod:
  email:
    config:
      to: [errors@mysite.com]
```

In this configuration file, you can also set the format of the email (html or txt) and, optionally, the `from:` field for the email.

* Clear the cache

```bash
php symfony cc
```

* Some users have mentioned they needed to override the default error page in order to get this plugin to work. You shouldn't need that, but just in case the plugin doesn't work, try to do it. The way to set a custom error page is explained in the Symfony books.

* You're done.

Changelog
=========

2010-03-23 | 1.2
----------------

* Fixed a bug in the documentation
* Made the code a bit more robust

2009-10-28 | 1.1
----------------

* Added nice HTML format for the email (thanks to Gustavo Garcia)
* Added user information to the email (thanks to Gustavo Garcia)
* Added the possibility to also trigger the notification email
explicitly via a standard method call 

2009-04-30 | 1.0.2
------------------

* Improved the documentation
* Made the email subject more explanatory

2009-04-27 | 1.0.1
------------------

* Improved the documentation

2009-04-26 | 1.0.0
------------------

* converted the plugin to 1.1
