<?php

/**
 * @author Rodrigo Manara<me@rodrigomanara.co.uk> 
 * @depends Finder
 */

namespace Fixture\BuilderBundle\Mapping;

use Symfony\Component\Finder\Finder;
use Fixture\BuilderBundle\Formater\MappingFormater;

class MappingFinder extends MappingFormaterModel {

    /**
     * get Mapping List
     *
     * @return array <p> will return the list of all mapping files </p>
     */
    public function getMappingList() {
        $finder = new Finder();


        $finder->files()->in( $this->getSrc())->depth(">4")->name("*mapping.yml");
        
        $files = array();

        foreach ($finder as $file) {
            array_push($files, $file->getRelativePathname());
        }

        return $files;
    }
 

}
