=== Forms: 3rdparty Phone Numbers ===
Contributors: zaus
Donate link: http://drzaus.com/donate
Tags: contact form, 3rdparty services, API, phone number, parsing, international number
Requires at least: 3.0
Tested up to: 4.3
Stable tag: trunk
License: GPLv2 or later

Forms: 3rdparty Integration add-on to parse phone numbers.

== Description ==

An add-on to ['Forms: 3rdparty Integration'](https://wordpress.org/plugins/forms-3rdparty-integration/), it parses phone number fields from Contact Form submissions and exposes them to additional mapping.

Uses the [PHP Port of Google's libphonenumber](https://github.com/giggsey/libphonenumber-for-php), which may be included via Composer instead -- see [GitHub version](https://github.com/zaus/f3i-phonenumber) instead.

== Installation ==

1. Unzip/upload plugin folder to your plugins directory (`/wp-content/plugins/`)
2. Make sure [Forms 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/) is installed and settings have been saved at least once.
3. Activate this plugin
4. Set the 'Phone number fields' option (fieldset may be collapsed by default) using URL-querystring format.

You can provide one or more phone numbers separated by `&` symbols.
You may specify the input format (per country) with `=country-code`.
You may specify both the input format and output format with `=input-format,output-format`.
You may use another submission field to define the format by prefixing that field name with `##`, e.g. `field_name=##another_field`

Will parse and split up input phone number(s) and create additional 'submission' fields (which you can map against):
* `FIELDNAME-CountryCode` = country code
* `FIELDNAME-NationalNumber` = regional number (without country code)
* `FIELDNAME-AreaCode` = regional code
* `FIELDNAME-Subscriber` = local number (without area code)
* `FIELDNAME-Extension` = telephone extension, if present
* `FIELDNAME-NumberOfLeadingZeros` = how many zeros it would start with if it had them
* `FIELDNAME-Out` = reformated phone number

Example: to convert input phone-number "9195551234" in various fields:
	
	field_name&field2=US,2&field3=2,3&field4=##field5
	
* `field_name` from assumed format into standard international `+1 919-555-1234`
* `field2` assuming US country code into standard regional `(919) 555-1234`
* `field3` from international format (requires country code `+X`) into url style `tel:+1-919-555-1234`
* `field4` from international format (requires country code `+X`) into a format defined by `field5`

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

= 0.4 =
* updated libphonenumber from v7.2.2 to v7.4.5
* including area code + subscriber number components

= 0.3 =
* use another submission field as the format

= 0.2 =
* support for input/output format

= 0.1 =
* started

== Upgrade Notice ==

N/A.