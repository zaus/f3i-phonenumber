=== Forms: 3rdparty Phone Numbers ===
Contributors: zaus
Donate link: http://drzaus.com/donate
Tags: contact form, 3rdparty services, API, phone number, parsing, international number
Requires at least: 3.0
Tested up to: 4.3
Stable tag: trunk
License: GPLv2 or later

Save referral variables to temporary storage (cookies)

== Description ==

An add-on to ['Forms: 3rdparty Integration'](https://wordpress.org/plugins/forms-3rdparty-integration/), it parses phone number fields from Contact Form submissions and exposes them to additional mapping.

== Installation ==

1. Unzip/upload plugin folder to your plugins directory (`/wp-content/plugins/`)
2. Make sure [Forms 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/) is installed and settings have been saved at least once.
3. Activate this plugin
4. Set the 'Phone number fields' option (fieldset may be collapsed by default) using URL-querystring format.

You can provide one or more phone numbers separated by `&` symbols.
You may specify the input format (per country) with `=country-code`.
You may specify both the input format and output format with `=input-format,output-format`.

== Frequently Asked Questions ==

= It doesn't work right... =

Drop an issue at https://github.com/zaus/f3i-phonenumber

= Formats =

From [here](https://github.com/giggsey/libphonenumber-for-php/blob/master/src/libphonenumber/PhoneNumberFormat.php), among other places

* *US* = United States
* *CH* = Switzerland
* _other iso country codes_
* *1* = (default) international format
* *2* = national format
* *3* = RFC3966 (url-format)

== Screenshots ==

N/A.

== Changelog ==

= 0.2 =
* support for input/output format

= 0.1 =
* started

== Upgrade Notice ==