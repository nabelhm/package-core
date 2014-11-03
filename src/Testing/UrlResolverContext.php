<?php

namespace Packagile\Package\Core\Testing;

use Assert\Assertion;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Exception\RequestException;
use Packagile\Package\Core\PackageNotFoundException;
use Packagile\Package\Core\PackagistNotResolvedException;
use Packagile\Package\Core\UrlResolverService;
use Packagile\Package\Core\InvalidPackageException;
use Packagile\Package\Core\Data;
use Prophecy\Argument;
use Prophecy\Prophet;

/**
 * @author Yosmany Garcia <yosmanyga@gmail.com>
 */
class UrlResolverContext implements SnippetAcceptingContext
{
    /**
     * @var \Packagile\Package\Core\UrlResolverServiceInterface
     */
    private $resolver;

    /**
     * @var mixed
     */
    private $result;

    /**
     * @BeforeScenario
     */
    function prepareServices()
    {
        $this->resolver = new UrlResolverService();
    }

    /**
     * @Given packagist.org is not resolved
     */
    public function packagistIsNotResolved()
    {
        $prophet = new Prophet();

        $prophecy = $prophet->prophesize();
        $prophecy->willExtend('\GuzzleHttp\Message\Request');
        /** @var \GuzzleHttp\Message\Request $request */
        $request = $prophecy->reveal();
        $prophecy = $prophet->prophesize();
        $prophecy->willImplement('\GuzzleHttp\ClientInterface');
        /** @var \GuzzleHttp\ClientInterface $browser */
        $prophecy->get(Argument::any())->willThrow(new RequestException('... Could not resolve host ...', $request));
        /** @var \GuzzleHttp\ClientInterface $browser */
        $browser = $prophecy->reveal();

        $this->resolver = new UrlResolverService($browser);
    }

    /**
     * @When I resolve the url using the following parameters:
     */
    public function iResolveTheUrlUsingTheFollowingParameters(TableNode $table)
    {
        $data = $table->getRowsHash();

        try {
            $this->result = $this->resolver->resolve($data['package']);
        } catch (InvalidPackageException $e) {
            $this->result = $e;
        } catch (PackagistNotResolvedException $e) {
            $this->result = $e;
        }  catch (PackageNotFoundException $e) {
            $this->result = $e;
        }
    }
    /**
     * @Then I should get the following data:
     */
    public function iShouldGetTheFollowingData(TableNode $table)
    {
        $data = $table->getRowsHash();

        Assertion::eq(
            $this->result,
            new Data(
                $data['package'],
                $data['url']
            )
        );
    }

    /**
     * @Then I should get the url :url
     */
    public function iShouldGetTheUrl($url)
    {
        Assertion::eq(
            $this->result,
            $url
        );
    }

    /**
     * @Then I should get an invalid package exception containing the following data:
     */
    public function iShouldGetAnInvalidPackageExceptionContainingTheFollowingData(TableNode $table)
    {
        $data = $table->getRowsHash();

        /** @var \Packagile\Package\Core\InvalidPackageException $exception */
        $exception = $this->result;

        Assertion::isInstanceOf(
            $exception,
            '\Packagile\Package\Core\InvalidPackageException'
        );

        Assertion::eq(
            $exception->getPackage(),
            $data['package']
        );
    }

    /**
     * @Then I should get a package not found exception containing the following data:
     */
    public function iShouldGetADataNotFoundException(TableNode $table)
    {
        $data = $table->getRowsHash();

        /** @var \Packagile\Package\Core\PackageNotFoundException $exception */
        $exception = $this->result;

        Assertion::isInstanceOf(
            $exception,
            '\Packagile\Package\Core\PackageNotFoundException'
        );

        Assertion::eq(
            $exception->getPackage(),
            $data['package']
        );
    }

    /**
     * @Then I should get a packagist not resolved exception
     */
    public function iShouldGetAPackagistNotResolvedException()
    {
        /** @var \Packagile\Package\Core\PackagistNotResolvedException $exception */
        $exception = $this->result;

        Assertion::isInstanceOf(
            $exception,
            '\Packagile\Package\Core\PackagistNotResolvedException'
        );
    }
}
