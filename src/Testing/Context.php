<?php

namespace Packagile\Package\Core\Testing;

use Assert\Assertion;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Doctrine\DBAL\DBALException;
use Packagile\Package\Core\CollectorService;
use Packagile\Package\Core\DataDuplicatedException;
use Packagile\Package\Core\InvalidPackageException;
use Packagile\Package\Core\StorerService;
use Packagile\Package\Core\Data;
use Packagile\Package\Core\DataNotFoundException;
use Packagile\Package\Core\InvalidUrlException;
use Packagile\Package\Core\Schema\Builder;
use Packagile\Package\Core\UrlDuplicatedException;
use Yosmanyga\DoctrineDbalExtension\Context\DoctrineDbalAwareContext;
use Doctrine\DBAL\Connection;

/**
 * @author Yosmany Garcia <yosmanyga@gmail.com>
 */
class Context implements SnippetAcceptingContext, DoctrineDbalAwareContext
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var \Packagile\Package\Core\CollectorServiceInterface
     */
    private $collector;

    /**
     * @var \Packagile\Package\Core\StorerServiceInterface
     */
    private $storer;

    /**
     * @var mixed
     */
    private $result;

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @BeforeScenario
     */
    public function resetSchema()
    {
        $platform = $this->conn->getDatabasePlatform();

        $builder  = new Builder();
        $schema = $builder->build();

        /* Truncate */

        $queries = array();
        $queries[] = 'SET FOREIGN_KEY_CHECKS=0';
        foreach ($schema->getTables() as $table) {
            $queries[] = $platform->getTruncateTableSQL($table->getName());
        }
        $queries[] = 'SET FOREIGN_KEY_CHECKS=1';

        foreach ($queries as $query) {
            try {
                $this->conn->query($query);
            } catch (DBALException $e) {
                continue;
            }
        }

        /* Create */

        $queries = $schema->toSql($platform);
        foreach ($queries as $query) {
            try {
                $this->conn->query($query);
            } catch (DBALException $e) {
                continue;
            }
        }
    }

    /**
     * @BeforeScenario
     */
    function prepareServices()
    {
        $this->collector = new CollectorService($this->conn);
        $this->storer = new StorerService($this->conn);
    }

    /**
     * @When I add a core data using the following parameters:
     */
    public function iAddACoreDataUsingTheFollowingParameters(TableNode $table)
    {
        $data = $table->getRowsHash();

        try {
            $this->result = $this->storer->add(
                $data['package'],
                $data['url']
            );
        } catch (InvalidPackageException $e) {
            $this->result = $e;
        } catch (InvalidUrlException $e) {
            $this->result = $e;
        } catch (DataDuplicatedException $e) {
            $this->result = $e;
        } catch (UrlDuplicatedException $e) {
            $this->result = $e;
        }
    }

    /**
     * @When I pick a core data using the following parameters:
     */
    public function iPickACoreDataUsingTheFollowingParameters(TableNode $table)
    {
        $data = $table->getRowsHash();

        try {
            $this->result = $this->collector->pick($data['package']);
        } catch (DataNotFoundException $e) {
            $this->result = $e;
        }
    }

    /**
     * @When I collect core data
     */
    public function iCollectCoreData()
    {
        $this->result = $this->collector->collect();
    }

    /**
     * @When I delete a core data using the following parameters:
     */
    public function iDeleteACoreDataUsingTheFollowingParameters(TableNode $table)
    {
        $data = $table->getRowsHash();

        try {
            $this->storer->delete(
                $data['package']
            );
        } catch (DataNotFoundException $e) {
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
     * @Then I should get the following data collection:
     */
    public function iShouldGetTheFollowingDataCollection(TableNode $table)
    {
        $rows = $table->getHash();

        foreach ($rows as $i => $row) {
            Assertion::eq(
                $this->result[$i],
                new Data(
                    $row['package'],
                    $row['url']
                )
            );
        }
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
     * @Then I should get an invalid url exception containing the following data:
     */
    public function iShouldGetAnInvalidUrlExceptionContainingTheFollowingData(TableNode $table)
    {
        $data = $table->getRowsHash();

        /** @var \Packagile\Package\Core\InvalidUrlException $exception */
        $exception = $this->result;

        Assertion::isInstanceOf(
            $exception,
            '\Packagile\Package\Core\InvalidUrlException'
        );

        Assertion::eq(
            $exception->getUrl(),
            $data['url']
        );
    }
    
    /**
     * @Then I should get a data duplicated exception containing the following data:
     */
    public function iShouldGetADuplicatedPackageException(TableNode $table)
    {
        $data = $table->getRowsHash();

        /** @var \Packagile\Package\Core\DataDuplicatedException $exception */
        $exception = $this->result;

        Assertion::isInstanceOf(
            $exception,
            '\Packagile\Package\Core\DataDuplicatedException'
        );

        Assertion::eq(
            $exception->getPackage(),
            $data['package']
        );
    }

    /**
     * @Then I should get a data not found exception containing the following data:
     */
    public function iShouldGetADataNotFoundException(TableNode $table)
    {
        $data = $table->getRowsHash();

        /** @var \Packagile\Package\Core\DataNotFoundException $exception */
        $exception = $this->result;

        Assertion::isInstanceOf(
            $exception,
            '\Packagile\Package\Core\DataNotFoundException'
        );

        Assertion::eq(
            $exception->getPackage(),
            $data['package']
        );
    }

    /**
     * @Then I should get an url duplicated exception containing the following data:
     */
    public function iShouldGetADuplicatedUrlException(TableNode $table)
    {
        $data = $table->getRowsHash();

        /** @var \Packagile\Package\Core\UrlDuplicatedException $exception */
        $exception = $this->result;

        Assertion::isInstanceOf(
            $exception,
            '\Packagile\Package\Core\UrlDuplicatedException'
        );

        Assertion::eq(
            $exception->getUrl(),
            $data['url']
        );
    }
}
