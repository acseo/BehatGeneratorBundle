<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ACSEO\Bundle\BehatGeneratorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use AppBundle\HttpKernel\SimpleHttpKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * A console command to generate automatic tests
 *
 * @author Nicolas Potier <nicolas.potier@acseo-conseil.fr>
 */
class ACSEOAutomaticTestCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        if (!$this->getContainer()->has('router')) {
            return false;
        }
        $router = $this->getContainer()->get('router');
        if (!$router instanceof RouterInterface) {
            return false;
        }

        return parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('acseo:automatic-test')
            ->setDefinition(array(
                new InputOption('firewallProvider', null, InputOption::VALUE_REQUIRED, 'The firewall provider used to check if URI is public', 'main'),
                new InputOption('login', null, InputOption::VALUE_REQUIRED, 'A login for protected routes', null),
                new InputOption('password', null, InputOption::VALUE_REQUIRED, 'A password for protected routes', null),

            ))
            ->setDescription('Generate automatic tests')
        ;
    }

    /**
     * {@inheritdoc}
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $autoFeaturePath = $this->checkFolderStructure($io);

        $firewallProvider = $input->getOption('firewallProvider');
        $login = $input->getOption('login');
        $password = $input->getOption('password');
        $rememberLogin = false;
        if ($login != null && $password != null) {
            $rememberLogin = true;
        }

        $routes = $this->getContainer()->get('router')->getRouteCollection();


        foreach ($routes as $name => $route) {
            $this->convertController($route);

            // Suppression des routes n'ayant pas d'accès en GET
            if (sizeof($route->getMethods()) == 1 && $route->getMethods()[0] != "GET") {
                continue;
            }

            // Suppression des routes ayant des paramètres dans leur URL
            if (strpos($route->getPath(), "{") !== FALSE) {
                continue;
            }

            $isPublic = $this->isUriPublic($route->getPath(), $firewallProvider);

            $options = array ("isPublic" => true, "login" => $login, "password" => $password, "templateParams" => array());
            if ($isPublic) {
                $options["isPublic"] = true;
                $options["templateParams"] = $this->getUriTemplateParams($route->getPath(), $firewallProvider);
            }
            else {
                $options["isPublic"] = false;
                if ($rememberLogin == true ) {
                    $options["login"] = $login;
                    $options["password"] = $password;
                }
                else {
                    $dialog = $this->getHelper('dialog');

                    $login = $dialog->ask(
                        $io,
                        'The route '.$route->getPath()." is protected, please enter a login to generate the test : ",
                        'test@acseo-conseil.fr'
                    );
                    $password = $dialog->ask(
                        $io,
                        'The route '.$route->getPath()." is protected, please enter a password to generate the test : ",
                        'motdepasse'
                    );
                    $choices = array('yes', 'no');

                    $rememberLogin = $dialog->select(
                        $output,
                        'Remember this login and password for other tests',
                        $choices,
                        0
                    );
                    if ($choices[$rememberLogin] == "yes") {
                        $rememberLogin = true;
                    } else {
                        $rememberLogin = false;
                    }
                }
            }
            $test = $this->generateBasicTest($io, $name, $route, $options);
            $test.= "\n".$this->generateFormTest($io, $name, $route, $options);
            file_put_contents($autoFeaturePath."/".$name.".feature", $test);

            $io->success("Writing feature test for :".$name);
        }

    }

    private function generateBasicTest($io, $name, $route, $options)
    {
        $requestOption = "";
        if ($options["isPublic"] == false) {
            $requestOption =  'When I send a authenticated "GET" request to "%s" as "%s" using password "%s"';
            $requestOption = sprintf($requestOption, $route->getPath(), $options["login"], $options["password"]);
        }
        else {
            $requestOption = 'When I request "GET %s"';
            $requestOption = sprintf($requestOption, $route->getPath());
        }

        $template = <<<EOT
Feature: Automatic test of route %s
  In order to use the website
  the page %s
  should respond HTTP Code 200

  Scenario: Test page %s
    %s
    Then the response status code should be 200

EOT;

        $output = sprintf($template,
                            $name,
                            $name,
                            $name,
                            $requestOption
                        );

        return $output;
    }

    private function generateFormTest($io, $name, $route, $options)
    {

        $template = <<<EOT
Feature: Automatic test of the form id %s in route %s
  In order to use the website
  submitting the empty form with id %s
  should respond HTTP Code 200

  Scenario: Test form %s of page %s
    When I request "GET %s"
    And I Submit the form with id %s
    Then the response status code should be 200

EOT;

        $output = "";
        if ($options["isPublic"] == true && $options["templateParams"] != null ) {
            foreach ($options["templateParams"] as $param) {
                if (is_object($param) && get_class($param) == "Symfony\Component\Form\FormView") {
                    $io->comment("Formulaire trouvé dans le template de l'URI : ".$route->getPath());
                    $formId = $param->vars["full_name"];
                    /* TODO : générer les tests pour chaque champ
                     foreach ($param->getIterator() as $formChild) {
                        $name = $formChild->vars["full_name"];
                        $type = $formChild->vars["block_prefixes"][1];
                        //die();
                    }
                    */
                    $output .= sprintf($template,
                                        $formId,
                                        $name,
                                        $formId,
                                        $formId,
                                        $name,
                                        $route->getPath(),
                                        $formId
                                    );
                }
            }
        }

        return $output;
    }

    private function convertController(Route $route)
    {
        $nameParser = $this->getContainer()->get('controller_name_converter');
        if ($route->hasDefault('_controller')) {
            try {
                $route->setDefault('_controller', $nameParser->build($route->getDefault('_controller')));
            } catch (\InvalidArgumentException $e) {
            }
        }
    }

    private function checkFolderStructure($io)
    {
        $fs = new Filesystem();
        $featurePath = $this->getContainer()->get('kernel')->getRootDir()."/../features";
        $autoFeaturePath = $this->getContainer()->get('kernel')->getRootDir()."/../features/automatic";
        if(!$fs->exists($featurePath)) {
            throw new \Exception("The feature folder does not exist in this project");
        }
        if(!$fs->exists($autoFeaturePath)) {
            $fs->mkdir($autoFeaturePath);
            $io->comment("Creating the automatic folder in ".$featurePath);
        }

        return $autoFeaturePath;
    }

    private function getUriTemplateParams($uri, $firewallProvider)
    {
        list($request, $session) = $this->createRequestAndSession($uri, $firewallProvider);
        try {
            $response = $this->getContainer()->get('kernel')->handle($request);
            $templateParams = $this->getContainer()->get("appbundle.event_listener.twig_render_listener")->getLastTemplateParams();
            $session->save();
            session_write_close();
            return $templateParams;
        } catch (AccessDeniedException $ade) {
            return null;
        }
    }

    private function createRequestAndSession($uri, $firewallProvider)
    {
        $username = "test";
        $roles = array("IS_AUTHENTICATED_ANONYMOUSLY");
        $token = new AnonymousToken($firewallProvider, $username, $roles);
        $session = $this->getContainer()->get('session');
        $session->setName('security.debug.console');
        $session->set('_security_' . $firewallProvider, serialize($token));
        $this->getContainer()->get('security.context')->setToken($token);
        $kernel = new SimpleHttpKernel();
        $request = Request::create($uri, 'GET', array(), array('security.debug.console' => true));
        $request->setSession($session);

        return array($request, $session);
    }

    private function isUriPublic($uri, $firewallProvider)
    {
        /*
        $username = "test";
        $roles = array("IS_AUTHENTICATED_ANONYMOUSLY");
        $token = new AnonymousToken($firewallProvider, $username, $roles);
        $session = $this->getContainer()->get('session');
        $session->setName('security.debug.console');
        $session->set('_security_' . $firewallProvider, serialize($token));
        $this->getContainer()->get('security.context')->setToken($token);
        $kernel = new SimpleHttpKernel();
        $request = Request::create($uri, 'GET', array(), array('security.debug.console' => true));
        $request->setSession($session);
        */
        list($request, $session) = $this->createRequestAndSession($uri, $firewallProvider);
        $kernel = new SimpleHttpKernel();
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        try {
            $this->getContainer()->get('security.firewall')->onKernelRequest($event);
            $session->save();
            session_write_close();
            return true;

        } catch (AccessDeniedException $ade) {
            $session->save();
            session_write_close();
            return false;
        }
    }
}
