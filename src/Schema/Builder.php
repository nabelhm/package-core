<?php

namespace Packagile\Package\Core\Schema;

use Doctrine\DBAL\Schema\Schema;

/**
 * @author Yosmany Garcia <yosmanyga@gmail.com>
 */
class Builder
{
    /**
     * @var string
     */
    private $table;

    /**
     * @param string|null $table
     */
    public function __construct($table = null)
    {
        $this->table = $table ?: 'pkgl_package_core';
    }

    /**
     * @return \Doctrine\DBAL\Schema\Schema
     */
    public function build()
    {
        $schema = new Schema();

        $table = $schema->createTable($this->table);
        $table->addColumn('package', 'string');
        $table->addColumn('url', 'string');
        $table->setPrimaryKey(array('package'));
        $table->addUniqueIndex(array('url'), 'url');

        return $schema;
    }
}