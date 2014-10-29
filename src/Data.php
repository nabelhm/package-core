<?php

namespace Packagile\Package\Core;

/**
 * @author Yosmany Garcia <yosmanyga@gmail.com>
 */
class Data implements DataInterface
{
    /**
     * @var string
     */
    private $package;

    /**
     * @var string
     */
    private $url;

    /**
     * @param string $package
     * @param string $url
     */
    function __construct($package, $url)
    {
        $this->package = $package;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}