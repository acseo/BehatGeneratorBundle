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

Configure the services

```
# app/config/services

parameters:
    templating.engine.delegating.class: ACSEO\Bundle\BehatGeneratorBundle\Templating\EventableDelegatingEngine

services:
    appbundle.event_listener.twig_render_listener:
        class: ACSEO\Bundle\BehatGeneratorBundle\Listener\TwigRenderListener
        tags:
            - { name: kernel.event_subscriber }
```

That's it ! now the command ```php app/console acseo:automatic-test``` is ready.

## Usage

You can use the command ```app/console acseo:automatic-test``` in order to generate feature files.

The command has many options : use ```app/console acseo:automatic-test --help``` to see them.

## Customization.

TBD.


## How does it works ?

* Step One : Get all the routes and filter them, in order to only keep routes that accept GET and doesn't have any parameters
* Step Two : Generate a simple test : get the URL and check if a 200 code is returned.
* Step Thee : Use a custom event to generate the view, check if it contains a FormView parameter in it. If so, generate a simple test : submit the form that may be in the view and check if a 200 code is returned.
