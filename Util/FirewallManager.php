<?php

namespace ACSEO\Bundle\BehatGeneratorBundle\Util;

use Symfony\Component\HttpFoundation\Request;

class FirewallManager
{
    /**
     * @var \Symfony\Component\HttpFoundation\RequestMatcher[]
     */
    private $map;

    /**
     * @param \Symfony\Component\HttpFoundation\RequestMatcher[] $map
     */
    public function __construct(array $map)
    {
        $this->map = $map;
    }

    /**
     * @param  Request     $request
     * @return string|null
     */
    public function getFirewallNameForRequest(Request $request)
    {
        foreach ($this->map as $firewallName => $requestMatcher) {
            if (null === $requestMatcher || $requestMatcher->matches($request)) {
                return $firewallName;
            }
        }

        return null;
    }
}
