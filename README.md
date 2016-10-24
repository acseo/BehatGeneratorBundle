# ACSEO Behat Generator Bundle

## Introduction

This Bundle allows you to generate *.feature* file for each route of your project.


## Installation

### Behat

You don't need behat to generate the tests, but to run them.
To add behat in your project, you can follow the procedure here : http://docs.behat.org/en/v3.0/cookbooks/1.symfony2_integration.html

```
$ composer require --dev behat/behat
$ composer require --dev behat/mink-extension
$ composer require --dev behat/mink-zombie-driver
```

Then your ```behat.yml``` file should look like :

```
# behat.yml
default:
    extensions:
        Behat\MinkExtension:
            base_url: "http://localhost:8000/"
            sessions:
                default:
                    zombie: ~
                javascript:
                    zombie: ~
    suites:
        default:
            contexts:
                - FeatureContext: { container: "@service_container" }
```

You will need to define a FeatureContext class :

```
<?php
// features/bootstrap/FeatureContext.php

use Behat\MinkExtension\Context\MinkContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct($container)
    {
         $this->container = $container;
    }

    /**
     * Some forms do not have a Submit button just pass the ID
     *
     * @When I Submit the form with id :arg1
     */
    public function iSubmitTheFormWithId($arg1)
    {
        // https://jsfiddle.net/v3rv47hL/
        $jsTemplate = <<<EOT
        "var forms = document.getElementsByTagName('form');var search = '%s';var x = null;if (forms.length == 1) {x = forms[0];} else { for (var i = 0; i < forms.length; i++) { if (forms[i].getAttribute('class') == search || forms[i].getAttribute('id') == search || forms[i].getAttribute('name') == search) { x = forms[i]; break; } } } if (x != null) x.submit();"
EOT;
        $js = sprintf($jsTemplate, $arg1);

        $node = $this->getSession()->getPage()->find('named', array('id', $arg1));
        if($node) {
            $this->getSession()->executeScript($js);
        } else {
            throw new Exception('No form with id : '.$arg1.' found on this page');
        }
    }

}
```

### Zombie

We will use Zombie.js as browser emulator. Why ? Because it allows us to check the status code after a JS test.

The installation documentation can be found here : http://mink.behat.org/en/latest/drivers/zombie.html :

* Install node.js by following instructions from the official site: http://nodejs.org/.
* Install npm (node package manager) by following the instructions from http://npmjs.org/.
* Install zombie.js with npm:

```
$ npm install -g zombie
```

### BehatGeneratorBundle

Include the bundle in your project :

```
$ composer require acseo/behat-generator-bundle
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
$ app/console acseo:automatic-test --access "main uri /login" --access "main loginField username " --access "main passwordField password" --access "main submitField Login" --access "main login test@test.com" --access "main password test"

```

## Customization.

TBD.


## How does it works ?

* Step One : Get all the routes and filter them, in order to only keep routes that accept GET and doesn't have any parameters
* Step Two : Generate a simple test : get the URL and check if a 200 code is returned.
* Step Thee : Use a custom event to generate the view, check if it contains a FormView parameter in it. If so, generate a simple test : submit the form that may be in the view and check if a 200 code is returned.

## API auto generated tests

When using an api, it's often not needed to make custom tests. Knowing that everything works in regular conditions is enough. In that case, you can use the Acseo auto generated tests for api. It will create a behat feature test in folder features/automatic/api/ for each api resource in your project. That is to say, a test to create a resource, get it, get all, edit one, delete one (some steps could be missing if you did not opened the action in your api). All of these tests will be run in a temporary SQL Lite database, so your real database won't be compromised. Obviously, if a resource does not have a route for the creation of a new item, no test can be written (console will warn you then), and it's up to you to create tests on your own.

For now, it is only available for api using NelmioApiDoc, that we use to get all elements needed to write tests (route, methods, parameters to send).

You also need these dependencies, add them in your composer.json if it's not already the case:
    - "behat/mink-extension": "~2.0"
    - "behat/mink-browserkit-driver": "~1.1"
    - "behatch/contexts": "dev-master"
    - "fzaninotto/faker": "~1.6.0"

And add in behat.yml our specific features:
default:
  ...
  suites:
    default:
      contexts:
        - ACSEO\Bundle\BehatGeneratorBundle\Context\ApiContext: { container: "@service_container" }
        - ACSEO\Bundle\BehatGeneratorBundle\Context\ExtendedMinkContext
        - ACSEO\Bundle\BehatGeneratorBundle\Context\ParallelContext: { container: "@service_container" }

To generate these tests, just run this command in your terminal:
app/console acseo:automatic-api-test

You will see all generated features appear.

Then, if everything all right:
bin/behat

If you need authentication, please add to your config_test.yml
parameters:
    ACSEOBehatGeneratorBundle:
        entity_manager: doctrine.orm.entity_manager # Put your own entity manager
        authentication:
            user:
                class: \AcmeBundle\Entity\User      # Put your own class for user
                attributes:                         # Enter value to fill out on user creation
                    username: user
                    email: user@test.com
                    plainPassword: test
                    enabled: true
            route:                                  # Define the route
                url: /login_check
                parameters:                         # And parameters to send
                    _username: user.username
                    _password: user.plainPassword



## License

This bundle is under the MIT license. See the complete license in the bundle:

```
Resources/meta/LICENSE
```
