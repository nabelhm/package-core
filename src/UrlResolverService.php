<?php

namespace Packagile\Package\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;

/**
 * @author Yosmany Garcia <yosmanyga@gmail.com>
 */
class UrlResolverService implements UrlResolverServiceInterface
{
    /**
     * @var \GuzzleHttp\ClientInterface
     */
    private $browser;

    /**
     * @param ClientInterface|null $browser
     */
    function __construct(ClientInterface $browser = null)
    {
        $this->browser = $browser ?: new Client(array('base_url' => 'https://packagist.org/'));
    }

    /**
     * @inheritdoc
     */
    public function resolve($package)
    {
        if (!preg_match('{^[a-z0-9_.-]+/[a-z0-9_.-]+$}', $package)) {
            throw new InvalidPackageException($package);
        }

        try {
            $res = $this->browser->get(sprintf(
                "packages/%s.json",
                $package
            ));
        } catch (RequestException $e) {
            if (false !== strpos($e->getMessage(), 'Could not resolve host')) {
                throw new PackagistNotResolvedException();
            }

            if ($e->getResponse() && 404 == (int) $e->getResponse()->getStatusCode()) {
                throw new PackageNotFoundException($package);
            }

            throw $e;
        }

        $package = $res->json();

        $version = current($package['package']['versions']);

        return $version['source']['url'];
    }
}