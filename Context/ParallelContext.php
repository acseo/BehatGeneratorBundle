<?php

namespace ACSEO\Bundle\BehatGeneratorBundle\Context;

use Behat\Behat\Context\Context;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Defines context for running test in a SQL lite.
 */
class ParallelContext implements Context
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $manager;

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
        $this->manager = $container->get('doctrine')->getManager();
        $this->schemaTool = new SchemaTool($this->manager);
        $this->classes = $this->manager->getMetadataFactory()->getAllMetadata();
    }

    /**
     * Create an SQL lite database to run tests.
     *
     * @BeforeScenario @createSchema
     */
    public function createDatabase()
    {
        $this->schemaTool->dropSchema($this->classes);
        $this->schemaTool->createSchema($this->classes);
    }
}
