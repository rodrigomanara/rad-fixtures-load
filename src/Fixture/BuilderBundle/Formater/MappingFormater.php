<?php

/**
 * @author Rodrigo Manara<me@rodrigomanara.co.uk> 
 */

namespace Fixture\BuilderBundle\Formater;

interface MappingFormater {

    /**
     * return src
     */
    public function getSrc();

    /**
     * @param type $path
     */
    public function setSrc($path = null);

    /**
     * return root path
     */
    public function getRoot();

    /**
     * @param type $path
     */
    public function setRoot($path);
}