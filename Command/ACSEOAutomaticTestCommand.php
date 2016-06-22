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
                new InputOption('access', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Information for access. Must be of type : firewallName uri|loginField|passwordField|submitField|login|password value', array()),
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

        $providerAccess = $this->managerProviderAccessOption($input, $io);
        if (false === $providerAccess) {
            return;
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

            list($isPublic, $firewallProvider) = $this->isUriPublic($route->getPath());

            if (!isset($providerAccess[$firewallProvider])) {
                $providerAccess[$firewallProvider] = array(
                    "uri" => null,
                    "loginField" => null,
                    "passwordField" => null,
                    "submitField" => null,
                    "login" => null,
                    "password" => null,
                    "remember" => false
                );
            }

            $options = array ("isPublic" => true, "providerAccess" => $providerAccess, "firewallProvider" => $firewallProvider, "templateParams" => array());
            if ($isPublic) {
                $options["isPublic"] = true;
                $options["templateParams"] = $this->getUriTemplateParams($route->getPath());
            }
            else {
                $options["isPublic"] = false;
                if ($providerAccess[$firewallProvider]["remember"] == false ) {
                    $dialog = $this->getHelper('dialog');

                    $providerAccess[$firewallProvider]["uri"] = $dialog->ask(
                        $io,
                        'The route '.$route->getPath()." is protected by the firewall $firewallProvider, please enter an uri to login : ",
                        '/login'
                    );
                    $providerAccess[$firewallProvider]["loginField"] = $dialog->ask(
                        $io,
                        "Please enter the login field to fill on ".$providerAccess[$firewallProvider]["uri"]." : ",
                        '_username'
                    );
                    $providerAccess[$firewallProvider]["passwordField"] = $dialog->ask(
                        $io,
                        "Please enter the password field to fill on ".$providerAccess[$firewallProvider]["uri"]." : ",
                        '_password'
                    );
                    $providerAccess[$firewallProvider]["submitField"] = $dialog->ask(
                        $io,
                        "Please enter the submit field to click on ".$providerAccess[$firewallProvider]["uri"]." : ",
                        'Login'
                    );

                    $providerAccess[$firewallProvider]["login"] = $dialog->ask(
                        $io,
                        "Please enter the username to type in  ".$providerAccess[$firewallProvider]["loginField"]." field : ",
                        'test@test.com'
                    );
                    $providerAccess[$firewallProvider]["password"] = $dialog->ask(
                        $io,
                        "Please enter the password to type in  ".$providerAccess[$firewallProvider]["passwordField"]." field : ",
                        'test'
                    );

                    $choices = array('yes', 'no');

                    $rememberLogin = $dialog->select(
                        $output,
                        'Remember this informations for other tests with this firewall ?',
                        $choices,
                        0
                    );
                    if ($choices[$rememberLogin] == "yes") {
                        $providerAccess[$firewallProvider]["remember"] = true;
                    } else {
                        $providerAccess[$firewallProvider]["remember"] = false;
                    }
                    $options["providerAccess"] = $providerAccess;
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
            $authenticateTemplate = <<<EOT
Given I am on "%s"
    When I fill in "%s" with "%s"
    And I fill in "%s" with "%s"
    And I press "%s"
    Then I am on "%s"
EOT;
            $accessInfos = $options["providerAccess"][$options["firewallProvider"]];

            $requestOption = sprintf($authenticateTemplate,
                $accessInfos["uri"],
                $accessInfos["loginField"], $accessInfos["login"],
                $accessInfos["passwordField"], $accessInfos["password"],
                $accessInfos["submitField"],
                $route->getPath()
            );
        }
        else {
            $requestOption = 'Given I am on "%s"';
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
    Then the url should match "%s"
EOT;

        $output = sprintf($template,
                            $name,
                            $name,
                            $name,
                            $requestOption,
                            $route->getPath()
                        );

        return $output;
    }

    private function generateFormTest($io, $name, $route, $options)
    {

        $template = <<<EOT
@javascript
  Scenario: Test form %s of page %s
    Given I am on "%s"
    And I Submit the form with id "%s"
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
                                        $route->getPath(),
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

    private function getUriTemplateParams($uri)
    {
        list($request, $session) = $this->createRequestAndSession($uri);
        try {
            $response = $this->getContainer()->get('kernel')->handle($request);
            $templateParams = $this->getContainer()->get("acseo.event_listener.twig_render_listener")->getLastTemplateParams();
            $session->save();
            session_write_close();
            return $templateParams;
        } catch (AccessDeniedException $ade) {
            return null;
        }
    }

    private function createRequestAndSession($uri)
    {

        $request = Request::create($uri, 'GET', array(), array('security.debug.console' => true));
        $firewallProvider = $this->getContainer()->get("acseo.util.firewall_manager")->getFirewallNameForRequest($request);
        $username = "anon.";
        $roles = array("IS_AUTHENTICATED_ANONYMOUSLY");
        $token = new AnonymousToken($firewallProvider, $username, $roles);
        $session = $this->getContainer()->get('session');
        $session->setName('security.debug.console');
        $session->set('_security_' . $firewallProvider, serialize($token));
        $this->getContainer()->get('security.context')->setToken($token);
        $kernel = new SimpleHttpKernel();
        $request->setSession($session);

        return array($request, $session, $firewallProvider);
    }

    private function isUriPublic($uri)
    {
        list($request, $session, $firewallProvider) = $this->createRequestAndSession($uri);
        $kernel = new SimpleHttpKernel();
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        try {
            $this->getContainer()->get('security.firewall')->onKernelRequest($event);
            $session->save();
            session_write_close();
            return array(true, $firewallProvider);

        } catch (AccessDeniedException $ade) {
            $session->save();
            session_write_close();
            return array(false, $firewallProvider);
        }
    }

    private function managerProviderAccessOption($input, $io)
    {
        $access = $input->getOption('access');
        $providerAccess = array();
        foreach ($access as $a) {
            $data = explode(" " , $a);
            if(sizeof($data) < 3) {
                throw new \Exception("You must provide 3 arguments with access option : [firewallName] [attribute] [value]");
            }
            $f = array_shift($data);
            $k = array_shift($data);
            $v = implode("", $data);

            if (!isset($providerAccess[$f])) {
                $providerAccess[$f] = array(
                    "uri" => null,
                    "loginField" => null,
                    "passwordField" => null,
                    "submitField" => null,
                    "login" => null,
                    "password" => null,
                    "remember" => true
                );
            }

            if (!array_key_exists($k, $providerAccess[$f])) {
                $io->error("The ".$k." attribute is not valid. Available attributes : ".implode(", ", array_keys($providerAccess[$f])));
                return false;
            }
            $providerAccess[$f][$k] = $v;
        }

        foreach ($providerAccess as $p => $d) {
            $missing = array();
            foreach ($d as $k => $v) {
                if ($v == null ) {
                    $missing[] = $k;
                }
            }
            if (sizeof($missing) > 0 ) {
                $io->error("Some information are missing for the firewall ".$p." : ".implode(", " , $missing));
                $s = "";
                foreach ($missing as $m) {
                    $s .='--access "'.$p.' '.$m.' value" ';
                }
                $io->comment($s);
                return false;
            }

        }

        return $providerAccess;
    }
}
