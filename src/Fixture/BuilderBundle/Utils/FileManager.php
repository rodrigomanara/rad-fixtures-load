<?php

/**
 * @author Rodrigo Manara<me@rodrigomanara.co.uk> 
 * @depends Filesystem , IOException , Finder , Dumper
 */

namespace Fixture\BuilderBundle\Utils;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Dumper;
use Symfony\Bridge\Monolog\Logger;

/**
 * FileManager
 * 
 * this class is resposible to manage the folder and files build and handler
 */
class FileManager extends Filesystem {

    protected $logger;

    /**
     * checkDir
     * 
     * @param type $dir
     * @return boolean
     */
    public function checkDir($dir) {

        if (is_dir($dir)) {
            return true;
        }
        return false;
    }

    /**
     * 
     * @param Logger $logger
     */
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * createDir
     * @param type $dir
     * @throws IOException
     */
    public function createDir($dir) {

        try {
            if (!is_file($dir) && !is_dir($dir)) {

                $this->mkdir($dir, 0777);
            }
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }
    }

    /**
     * removeDir
     * this method will delete a folder
     * 
     * @param type $dir
     * @throws IOException
     */
    public function removeDir($dir) {
        try {
            $this->remove($dir);
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }
    }

    /**
     * createFile
     * 
     * this method is resposible to generate file and check if the files already existe
     * 
     * @param type $filename
     * @throws IOException
     */
    public function createFile($filename) {
        if (!is_file($filename)) {
            $this->touch($filename);
        } else {
            $this->logger->warning($e->getMessage());
        }
    }

    /**
     * hardCopy
     * 
     * this method is resposible to copy files from point a to b
     * 
     * @param type $originDir
     * @param type $targetDir
     */
    public function hardCopy($originDir, $targetDir) {

        $this->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $this->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));
    }

    /**
     * generate_yml_file
     * using this function you will be able to generate a yaml file by just send an array
     * 
     * @param array $data
     * @param type $path
     * @param type $level
     * {@source 3}
     */
    public function generate_yml_file(array $data, $path, $level = 3) {
        $dumper = new Dumper();
        $file = $dumper->dump($data, $level);
        file_put_contents($path, $file);
    }

}
