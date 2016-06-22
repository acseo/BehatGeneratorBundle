# ACSEO Behat Generator Bundle

## Introduction

This Bundle allows you to generate *.feature* file for each route of your project.


## Installation

Include the bundle in your project :

```
composer require acseo/behat-generator-bundle
```

Enable the bundle in AppKernel.php

```
// app/AppKernel.php
// ...
public function registerBundles()
{
    $bundles = array(
        // ...
        new ACSEO\Bundle\BehatGeneratorBundle\ACSEOBehatGeneratorBundle(),
        // ...
    );
```

That's it ! now the command ```php app/console acseo:automatic-test``` is ready.

## Usage

You can use the command ```app/console acseo:automatic-test``` in order to generate feature files.

If you do so, there is a lot of chances that the command will detect protected routes that *require to login*.
It will prompt you multiple information that will be used, such as the login url, username and password, etc.

If you want to avoid prompt, you can call the command with the usefull information :

```
app/console acseo:automatic-test --access "main uri /login" --access "main loginField username " --access "main passwordField password" --access "main submitField Login" --access "main login test@test.com" --access "main password test"

```

## Customization.

TBD.


## How does it works ?

* Step One : Get all the routes and filter them, in order to only keep routes that accept GET and doesn't have any parameters
* Step Two : Generate a simple test : get the URL and check if a 200 code is returned.
* Step Thee : Use a custom event to generate the view, check if it contains a FormView parameter in it. If so, generate a simple test : submit the form that may be in the view and check if a 200 code is returned.


## License

This bundle is under the MIT license. See the complete license in the bundle:

```
Resources/meta/LICENSE
```
