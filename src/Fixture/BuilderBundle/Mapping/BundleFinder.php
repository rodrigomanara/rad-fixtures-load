<?php

/**
 * @author Rodrigo Manara<me@rodrigomanara.co.uk> 
 */

namespace Fixture\BuilderBundle\Mapping;

use Fixture\BuilderBundle\Formater\MappingFormaterModel;


class BundleFinder extends MappingFormaterModel {

    public $kernel;

    public function __construct() {
        $this->kernel = $this->getContainer()->get('kernel');
    }
    /**
     * Responsible to return a list of bundle that sit on SRC
     * @return array
     */
    public function buildBundleList() {
 
        $bundles_list = $this->getContainer()->getParameter('kernel.bundles');
        $append_bundle = array();
         
        foreach ($bundles_list as $key => $bundle) {

            $locate = $this->kernel->locateResource("@$key");

            $compare = explode("\\", $locate);
            $compare = array_slice($compare, -3, 3);
            $compare = str_replace("/", "", implode("", $compare));

            $compare_bundle = explode("\\", $bundle);
            $compare_bundle = end($compare_bundle);

            if (strpos($locate, "\\vendor\\") == false && $compare_bundle == $compare) {
                $append_bundle[$key] = $locate;
            } elseif (strpos($locate, "\\vendor\\") == false && strpos($compare, "src") !== false && $compare_bundle !== $compare) {
                $append_bundle[$key] = $locate;
            }
        }
        return $append_bundle;
    }

}
