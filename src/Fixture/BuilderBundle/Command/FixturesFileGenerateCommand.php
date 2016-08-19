<?php

/**
 * Created by PhpStorm.
 * User: PatelK
 * Date: 20/03/2016
 * Time: 20:05
 */

namespace Application\UtilityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;
use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Finder\Finder;
use \ReflectionClass as Reflecion;

class FixturesFileGenerateCommand extends ContainerAwareCommand {

    /**
     * filemanager
     * @var type
     */
    protected $filemanager;
    protected $container;
    protected $root;

    /*
     * @var OutputInterface
     */
    private $output;

    /**
     * initialize
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);

        $this->container = $this->getContainer();
        $this->filemanager = $this->container->get('fixture.file.utilities');
        $this->path = $this->container->getParameter('kernel.root_dir');
        $this->em = $this->container->get('doctrine')->getManager();
    }

    protected function configure() {

        $this
                ->setName('utility:fixture:build')
                ->setDescription(
                        'Creates fixtures of provided bundle'
                )//->addArgument('name', InputArgument::REQUIRED, 'Specify the bundle name')
        ;
    }

    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->output = $output;
        $file_list = $this->findMapping();
        $this->readMapping($file_list);
    }

    /**
     * Get list of Mapping and dump the data
     * @param type $filePath
     */
    private function readMapping($filePath = null, $siteId = 1) {
        foreach ($filePath as $path) {
            $fmappingPath = $this->path . "/../src/" . $path;

            $this->output->writeln("<fg=green;>\r Processing  mapping file at: {$path}... \r </>");
            $yaml = Yaml::parse($fmappingPath);
            $this->compileYml($yaml, $fmappingPath, $siteId);
        }
    }

    /**
     * Get Data from YML and build Object
     * @param type $array
     */
    private function compileYml($dataYml = array(), $fmappingPath, $siteId) {


        foreach ($dataYml as $key => $Mapping) {

            $this->output->writeln("<fg=green;>\r Processing  @{$key}... \r </>");
            $fixturePath = explode("\\mapping\\", $fmappingPath);

            // check is mapping exist    
            if (isset($fixturePath[0])) {
                $outFileName = $fixturePath[0] . DIRECTORY_SEPARATOR . "orm/" . $this->renameFile($fixturePath[1]);

                $yaml = array($key => $this->geMethods($key, $Mapping, $siteId));

                $this->output->writeln("<fg=white;>\r saving yml in @{$outFileName}... \r </>");
                $this->filemanager->generate_yml_file($yaml, $outFileName, 3);
            }
        }
    }

    /**
     * 
     * @param type $name
     * @return type
     */
    public function renameFile($name) {
        $filename = str_replace("bundle_export_mapping", "", $name);
        $filename = str_replace("beluga", "", $filename);
        return $filename;
    }

    /**
     * this will get all elements from Entity
     * @param type $Mapping
     */
    private function geMethods($class, $Mapping, $siteId) {
        $repository = $this->em->getRepository($class);
        $criteria = [];
        $items = $repository->findBy($criteria);


        $build_list = array();
        $reflect = new Reflecion($class);
        $this->output->writeln("<fg=green;>\r generating item yaml... \r </>");
        foreach ($items as $element) {
            $item = array();
            foreach ($Mapping as $key => $map) {
                $item[$key] = $this->checkRule($element, $map, $reflect, $siteId);
            }

            //ID macro -> can set in rule if requred.
            $id = $item['id'];
            unset($item['id']);
            $build_list[$id] = $item;
        }


        $this->output->writeln("<fg=green;>\r yaml generated now saving... \r </>");
        return $build_list;
    }

    /**
     *
     * @param type $object
     * @param string $map
     * @param Reflection $reflection
     * @return string
     */
    private function checkRule($item, $map, $reflection, $siteId) {
        $value = '';
        $macro = array();

        if (strpos($map, '|') !== false) {
            $items = explode('|', $map);
            for ($count = 0; $count < count($items); $count++) {
                if ($count == 0) {
                    $map = trim($items[0]);
                    continue;
                }

                array_push($macro, trim($items[$count]));
            }
        }

        if ($reflection->hasMethod($map)) {
            $method = $reflection->getMethod($map);
            $value = $method->invoke($item);
        } else {
            $value = $map;
        }

        $value = $this->applyMacro($value, $macro, $siteId);
        return $value;
    }
    /**
     * 
     * @param type $value
     * @param type $macro
     * @param type $siteId
     * @return string
     */
    private function applyMacro($value, $macro, $siteId) {

        if (count($macro) > 0) {
            foreach ($macro as $key => $macroItem) {
                if (strpos($macroItem, "createBlockKey") !== false) {
                    $value = 'block_' . $value . '_{' . $siteId . '}';
                }

                if (strpos($macroItem, "createDashboardServiceKey") !== false) {
                    $value = 'dashboard_service_' . $value . '_{' . $siteId . '}';
                }

                if (strpos($macroItem, "createMenuLinkKey") !== false) {
                    $value = 'menu_link_' . $value . '_{' . $siteId . '}';
                }

                if (strpos($macroItem, "createKeyUsingName") !== false) {
                    $value = str_replace($siteId, '{' . $siteId . '}', $value);
                    $value = str_replace(' ', '_', $value);
                }

                if (strpos($macroItem, "replaceSiteId") !== false) {
                    $value = str_replace($siteId, '<current()>', $value);
                }

                if (strpos($macroItem, "replaceDashboardId") !== false && array_key_exists('dashboard', $value)) {
                    $id = $this->getRelKey($value['menu'][0], $siteId, 'Beluga\DashboardBundle\Entity\Dashboard');
                    $value['menu'][0] = '@' . $id . '->id';
                }

                if (strpos($macroItem, "replaceMenuId") !== false && array_key_exists('menu', $value)) {
                    if (array_key_exists('dashboard', $value))
                        continue;
                    $id = $this->getRelKey($value['menu'][0], $siteId, 'Beluga\MenuBundle\Entity\Menu');
                    $value['menu'][0] = '@' . $id . '->id';
                }

                if (strpos($macroItem, "replaceNameForKey") !== false && method_exists($value, 'getName')) {
                    $value = $this->applyMacro($value->getName(), array('createKeyUsingName'), $siteId);
                    $value = str_replace('{' . $siteId . '}', '<current()>', $value);
                    $value = '@' . $value;
                }
            }
        }

        return $value;
    }
    /**
     * 
     * @param type $objId
     * @param type $siteId
     * @param type $class
     * @return string
     */
    private function getRelKey($objId, $siteId, $class) {
        //Query from repository if required.
        $repo = $this->em->getRepository($class);
        $menu = $repo->find($objId);
        $id = $menu->getName();
        $id = str_replace($siteId, '', $id);
        $id = str_replace(' ', '_', $id);
        $id = $id . '<current()>';

        return $id;
    }

    /**
     * combine list of Mapping
     * @return array
     */
    private function findMapping() {
        $finder = new Finder();


        $finder->files()->in($this->path . DIRECTORY_SEPARATOR . "../src")->depth(">4")->name("*mapping.yml");
        $files = array();

        foreach ($finder as $file) {
            array_push($files, $file->getRelativePathname());
        }

        return $files;
    }

}
