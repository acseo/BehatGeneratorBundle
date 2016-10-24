<?php

namespace ACSEO\Bundle\BehatGeneratorBundle\Context;

use Behat\Gherkin\Node\PyStringNode;
use Sanpi\Behatch\Context\RestContext;
use Sanpi\Behatch\HttpCall\Request;

use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Defines application features from the specific context of a REST api.
 */
class ApiContext extends RestContext
{
    protected $token;
    protected $container;

    public function __construct(Request $request, $container)
    {
        parent::__construct($request);

        $this->request = $request;
        $this->container = $container;
    }

    /**
     * Sends a HTTP request after authentication
     *
     * @When I send an authenticated :method request to :url
     */
    public function iSendAnAuthenticatedRequestTo($method, $url)
    {
        return $this->sendAnAuthenticatedRequest($method, $url);
    }

    /**
     * Sends a HTTP request after authentication
     *
     * @When I send an authenticated :method request to :url with body:
     */
    public function iSendAnAuthenticatedRequestToWithBody($method, $url, PyStringNode $body = null)
    {
        return $this->sendAnAuthenticatedRequest($method, $url, $body);
    }

    /**
     * Sends a HTTP request after authentication
     *
     * @When I send an authenticated :method request to :url with parameters:
     */
    public function iSendAnAuthenticatedRequestToWithParameters($method, $url, TableNode $datas = null)
    {
        return $this->sendAnAuthenticatedRequest($method, $url, null, $datas);
    }

    /**
     * Send request with an header Authorization.
     *
     * @param  string            $method
     * @param  string            $url
     * @param  PyStringNode|null $body
     * @param  TableNode|array   $datas
     * @return Request
     */
    protected function sendAnAuthenticatedRequest($method, $url, PyStringNode $body = null, TableNode $datas = null)
    {
        return $this->request->send(
            $method,
            $this->locatePath($url),
            [],
            $datas ? $datas : [],
            $body,
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => sprintf('Bearer %s', $this->getToken())
            ]
        );
    }

    /**
     * Fetch a token for authenticated request.
     *
     * @return string
     */
    protected function getToken()
    {
        if (!$this->token) {

            // Get user config from parameters.
            $conf = $this->container->getParameter('ACSEOBehatGeneratorBundle');
            $authConf = $conf['authentication'];

            // Create user
            $user = new $authConf['user']['class']();
            foreach ($authConf['user']['attributes'] as $attrName => $value) {
                $setter = $this->guessSetter($attrName, $user);
                if ($setter) {
                    $user->$setter($value);
                } else {
                    throw new \Exception('Attribute with name "'. $attrName . '" of class "'. $authConf['user']['class'] .'" has no setter defined.');
                }
            }

            // Save it
            $em = $this->container->get($conf['entity_manager']);
            $em->persist($user);
            $em->flush();

            // Building parameters to send for login
            $parameters = [];
            foreach ($authConf['route']['parameters'] as $attrName => $value) {
                $parameters[$attrName] = $this->getValue($value, array('user' => $authConf['user']['attributes']));
            }

            // Login
            $loginConf = $authConf['route'];
            $responseLogin = $this->request->send(
                'POST',
                $this->locatePath($authConf['route']['url']),
                [],
                [],
                json_encode($parameters),
                [
                    'CONTENT_TYPE' => 'application/json'
                ]
            );

            // Parsing response
            $responseLoginData = json_decode($responseLogin->getContent(), true);

            // Store token
            $this->token = $responseLoginData['token'];
        }

        return $this->token;
    }

    /**
     * Guess and check setter for a given object attribute.
     *
     * @param  string $attrName
     * @param  mixed  $object
     * @return string|null
     */
    protected function guessSetter($attrName, $object)
    {
        $setter = 'set' . ucfirst($attrName);
        if (false === method_exists($object, $setter)) {
            return;
        }

        return $setter;
    }

    /**
     * Get a value in an multidimensional array with a path dot separated.
     *
     * @param  string $path
     * @param  array  $array
     * @return mixed
     */
    protected function getValue($path, array $array)
    {
        $value = $array;
        $path = explode('.', $path);
        foreach ($path as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return;
            }
        }

        return $value;
    }
}
