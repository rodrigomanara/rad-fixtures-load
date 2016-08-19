<?php

/**
 * @author Rodrigo Manara<me@rodrigomanara.co.uk> 
 */

namespace Fixture\BuilderBundle\Formater;

use Fixture\BuilderBundle\Formater\MappingFormater;
use Fixture\BundlerBundle\Helper\HelperServiceContainer;

class MappingFormaterModel extends HelperServiceContainer implements MappingFormater {

    /**
     *
     * @var string
     */
    public $src;

    /**
     *
     * @var string 
     */
    public $root;
 

    /**
     * 
     * @return string
     */
    public function getRoot() {
        return $this->root;
    }

    /**
     * 
     * @return string
     */
    public function getSrc() {
        return $this->src;
    }

    /**
     * 
     * @param string $path
     */
    public function setRoot($path) {
        $this->root = $path;
    }

    /**
     * 
     * @param string $path
     */
    public function setSrc($path = null) {
        $this->src = $path;
    }
 

}
