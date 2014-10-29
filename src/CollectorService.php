<?php

namespace Packagile\Package\Core;

use Doctrine\DBAL\Connection;

/**
 * @author Yosmany Garcia <yosmanyga@gmail.com>
 */
class CollectorService implements CollectorServiceInterface
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
    public function pick($package)
    {
        $row = $this->conn
            ->fetchAssoc(
                sprintf(
                    'SELECT package, url FROM %s WHERE package = :package',
                    $this->table
                ),
                array(
                    'package' => $package,
                )
            );

        if (!$row) {
            throw new DataNotFoundException($package);
        }

        return new Data(
            $row['package'],
            $row['url']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function collect($vendor = null)
    {
        $rows = $this->conn
            ->fetchAll(
                sprintf(
                    'SELECT package, url FROM %s ORDER BY package',
                    $this->table
                )
            );

        $collection = array();
        foreach ($rows as $row) {
            $collection[] = new Data(
                $row['package'],
                $row['url']
            );
        }

        return $collection;
    }
}