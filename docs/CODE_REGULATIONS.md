# Code Regulations

These regulations can be updated frequently so please check this document before editing any code to ensure you are acquainted with the latest regulations.
This document does not outline every single coding standard in the project, some may have been missed, please have a read over some current project code and observe the style and follow it as best possible.

Naming
---------------------------

* Variables should be named using only alphabetical characters (a-z) and written in camelCase with the first word having a lowercase first character. e.g. `$thisIsAVariable`
* Constants should be named using only alphabetical characters (a-z) in all upper case with words separated by underscores. e.g. `THIS_IS_A_CONSTANT`
* Functions should be named using only alphabetical characters (a-z) and written in camelCase with the first word having a lowercase first character.. e.g. `thisIsAFunction()`
* Classes should be named using only alphabetical characters (a-z) and written in CamelCase with the first word having an uppercase first character. e.g. `ThisIsAClass`
* Files should be named identically to the class contained in them
* Namespaces should be used where possible and should be a valid path to the class from the `/lib` directory if the backslashes were changed to forward slashes
* Database tables and columns should be named using only alphabetical characters (a-z) in all lower case with words separated by underscores. e.g. `this_is_a_table`
* View file names (Twig templates) should be named using only alphabetical characters (a-z) in all lower case with words separated by underscores. e.g. `this_is_a_view.twig`

Spacing
---------------------------

* Indentation should be used liberally throughout your code
* Indents should be **tabs only**, spaces are prohibited for the purpose of indentation
* A single tab should be used for each level of standard indentation
* A double tab should be used when continuing a line of code (wrapping)

Views (Twig templates)
---------------------------

* The [Twig coding standards](http://twig.sensiolabs.org/doc/coding_standards.html) should be followed at all times
* All views should be suffixed with a .twig extension

Pure PHP Files
---------------------------

* Files containing only PHP should not have an ending PHP tag (`?>`)
* All pure PHP files should have a copyright notice at the start of the file, e.g.:

```php
/**
 * Project
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
```

* Pure PHP files should be hard limited to a 100 character width, lines longer than 100 characters must be wrapped on to the next line

General Style
---------------------------

* Double quotes should be used in all cases except where there is a conflict with a string containing double quotes. e.g. `"this is a string"`
* The PHP array shorthand should be used at all times. e.g. `["element one", "element two"]`. Use of `array()` should be avoided

Execution Limits
---------------------------

* Requests must take no longer than 1.0s for a page to be sent to the browser, or in exceptional cases up to 1.5s. Pages which require a longer period of time to load (e.g. if they make heavy use of an external API) should load a simple page quickly which then uses AJAX to load the remainder of the page and includes an on-screen message indicating that the content is loading. Long request times for a basic page can cause users to rapidly refresh or close the tab.
* Request times should be measured by setting the SHOW_REQUEST_TIMES config value to true, they can then be found in the footer on every page.

Documentation
---------------------------

* All classes, methods and functions should have a PHPDoc style comment directly before them explaining the purpose of the code and how it achieves that purpose. It should also detail all parameters, possible exceptions and return values. An example has been shown below:

```php
/**
 * Gets a user
 *
 * @param int $id user id to get
 * @return \sma\models\User user
 * @throws NonExistentObjectException if specified user id does not exist
 */
```