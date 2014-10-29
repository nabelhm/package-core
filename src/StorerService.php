<?php

namespace Packagile\Package\Core;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

/**
 * @author Yosmany Garcia <yosmanyga@gmail.com>
 */
class StorerService implements StorerServiceInterface
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var string
     */
    private $table;

    /**
     * @param Connection  $conn
     * @param string|null $table
     */
    public function __construct(
        Connection $conn,
        $table = null
    )
    {
        $this->conn = $conn;
        $this->table = $table ?: 'pkgl_package_core';
    }

    /**
     * {@inheritdoc}
     */
    public function add($package, $url)
    {
        if (!preg_match('{^[a-z0-9_.-]+/[a-z0-9_.-]+$}', $package)) {
            throw new InvalidPackageException($package);
        }

        if (!$url) {
            throw new InvalidUrlException($url);
        }

        try {
            $this->conn->insert(
                $this->table,
                array(
                    'package' => $package,
                    'url' => $url
                )
            );
        } catch (DBALException $e) {
            // Is an integrity constraint violation?
            if (false !== strpos($e->getMessage(), 'Integrity constraint violation')) {
                if (
                    // Is a MySQL error?
                    false !== strpos($e->getMessage(), "'PRIMARY'")
                    // Is a Sqlite error?
                    || false !== strpos($e->getMessage(), "package is not unique")
                    || false !== strpos($e->getMessage(), ".package")
                ) {
                    throw new DataDuplicatedException($package);
                } elseif (
                    // Is a MySQL error?
                    false !== strpos($e->getMessage(), "'url'")
                    // Is a Sqlite error?
                    || false !== strpos($e->getMessage(), "url is not unique")
                    || false !== strpos($e->getMessage(), ".url")
                ) {
                    throw new UrlDuplicatedException($url);
                }
            }

            // Throw the unknown exception
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($package)
    {
        $result = $this->conn->delete(
            $this->table,
            array(
                'package' => $package,
            )
        );

        if (0 == $result) {
            throw new DataNotFoundException($package);
        }
    }
}