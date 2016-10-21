<?php

namespace ACSEO\Bundle\BehatGeneratorBundle\Context;

use Behat\MinkExtension\Context\MinkContext;

class ExtendedMinkContext extends MinkContext
{
    /**
     * @Then /^I should get a success code$/
     */
    public function assertSuccessResponseStatus()
    {
        $statusCode = $this->getSession()->getStatusCode();
        if ($statusCode < 200 || $statusCode > 299) {
            throw new \Exception('The response is '. $statusCode .'. It sould not be.');
        }
    }
}
