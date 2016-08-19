<?php
 
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
use \ReflectionClass as Reflecion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * @package Application\UtilityBundle\Command
 * @version 1
 * @author Rodrigo Manara <me@rodrigomanara.co.uk>
 * @internal all changes need to be tested before been pushed to dev
 * @category backup 
 * 
 * This Package will provide mapping for all entities in src
 * Mapping will only be generated if data is saved on the database
 */
class FixturesMappingGeneratorCommand extends ContainerAwareCommand {

    /**
     * filemanager
     * @var type
     */
    private $filemanager;
    private $helper;
    private $input;
    private $output;
    private $container;
    private $bundleList;
    private $buildFixtureFolderList;
    private $outputDir;
    private $kernel;

    /**
     * initialize
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);

        $this->container = $this->getContainer();
        $this->filemanager = $this->container->get('fixture.file.utilities');
        $this->helper = $this->getHelper('question');
        $this->input = $input;
        $this->output = $output;
        $this->em = $this->container->get('doctrine')->getManager();
        $this->kernel = $this->container->get('kernel');
        $this->outputDir = $this->kernel->getRootDir();
    }

    /**
     * command is set 
     */
    protected function configure() {

        $this
                ->setName('utility:fixture:mapping')
                ->setDescription('Creates fixtures mapping of provided bundle')
        ;
    }

    /**
     * buildBundleList
     * this method will find all bundle that is inside and remove any bundle that is in Vendor Folder
     * @return array
     */
    private function buildBundleList() {

        $this->output->writeln("<fg=yellow;>\r find bundles ... \r </>");

        $path = $this->container->getParameter('kernel.root_dir');
        $search = explode("\\", $path);
        array_pop($search);
        $search = implode("\\", $search) . "\\src\\";

        $bundles_list = $this->container->getParameter('kernel.bundles');

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
        $this->bundleList = $append_bundle;
    }

    /**
     * this bundle will interact with the developer to generate the mapping
     * @param OutputInterface $output
     * @param type $metadatas
     * @return string
     */
    private function BundleInteract() {

        $question = new ConfirmationQuestion("Do you want to build fixture mapping for all Bundle from <fg=yellow;> src/ Diretory</>? : ", true);

        $askBundleByBundle = $this->helper->ask($this->input, $this->output, $question); //check if bundle is set to all or only for some, so few question will be asked until to the end of the build
        if (!$askBundleByBundle)
            $this->output->write("<fg=red;> \r   Please carefull as all bunlde will be display and the mapping will be create for only the bundle you agreed \r\n </> ");

        $this->buildBundleList(); //get Bundle List
        $this->buildInteractFixtureFolderList($askBundleByBundle); //build fixture folder list
    }

    /**
     * this method will find all entity inside the bundle and will build compile a final list 
     * also this method will interact with the developer to build a mapping file
     *
     *  @param type $askBundleByBundle <p> if return is false a rule will be trigger to ask bundle by bundle</p>
     */
    private function buildInteractFixtureFolderList($askBundleByBundle = false) {

        $appendBundle = array();


        foreach ($this->bundleList as $key => $bundle) { // get all bundle
            $checkBundle = true;
            if (!$askBundleByBundle) {
                $question_2 = new ConfirmationQuestion(" \n\r Do you want to build fixture for <fg=yellow;> " . $key . "  src/ Diretory</> ?: ", true, "/^n/i");
                $checkBundle = $this->helper->ask($this->input, $this->output, $question_2);
            }

            if ($checkBundle) {  // void build fixtute for bundle that extends a vendor  
                $appendBundle[$key] = array('file' => $bundle . "Resources/fixtures/mapping", 'class' => $bundle);
            }
        }

        $this->buildFixtureFolderList = $appendBundle;
    }

    /**
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln("<fg=black;bg=cyan>\n\n\r Welcome to fixture command generater ... \n\r </>");
        $output->writeln("<fg=cyan;>\n\r  \"press enter\" for y  and \"n\" for No... \r </>");


        $this->BundleInteract(); //ask if all bundle will be added and you be bundle check and a question will be done
        $this->buildEntityMapping(); //build entity file
    }

    /**
     * build Entity 
     * this method will find all method that is set on each bundle and will create a file
     */
    function buildEntityMapping() {

        $this->output->writeln("<fg=yellow;>\r start building entity from Doctrine Metadata ... \n\r </>");

        $metadatas = $this->em->getMetadataFactory()->getAllMetadata();
        $manager = new DisconnectedMetadataFactory($this->container->get('doctrine'));

        foreach ($this->buildFixtureFolderList as $bundleName => $bundles) {

            $bundle = $this->kernel->getBundle($bundleName);

            try {
                $metadata = $manager->getBundleMetadata($bundle);
                $count = 0;

                foreach ($metadata->getMetadata() as $data) {
                    $count++;
                    // check if it is a valid entity void abstract
                    if ($this->isEntity($this->em, $data->getName())) {

                        //$this->output->writeln("<fg=white;>\r find Metadata for {$data->getName()} ... \r</>");
                        $repository = $this->em->getRepository($data->getName());

                        $criteria = [];
                        $items = $repository->findBy($criteria);
                        $buildYmlArray = array();
                        $temp = array();

                        foreach ($items as $item) {
                            $class_name = get_class($item);
                            $class_methods = get_class_methods($class_name);

                            $reflect = new Reflecion($class_name);
                            $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);

                            foreach ($props as $key => $prop) {
                                foreach ($class_methods as $key => $method_name) {
                                    $method_string_name = strtolower($method_name);
                                    if (strpos($method_string_name, $prop->getName()) !== false && preg_match("/^get|is/", $method_name))
                                        $temp[$prop->getName()] = $method_name;
                                }
                            }
                        }
                        if (!empty($temp)) {
                            $this->output->writeln(" <fg=green;> \r Generate Mapping ...  </>");
                            $buildYmlArray = $temp;
                        }
                    }

                    $dataYaml = array(
                        $data->getName() => $buildYmlArray,
                    );
                    if (!empty($buildYmlArray)) {
                        $this->saveFile($dataYaml, $count, $bundleName, $bundles);
                    }
                }
            } catch (\Exception $e) {
                $this->output->writeln(" <fg=red;> \r {$e->getMessage()} ... </>");
            }
        }
    }

    /**
     * this method will generate the file and create the folder to save the mapping
     * @param type $dataYaml
     * @param type $count
     * @param type $bundleName
     * @param type $bundleArray
     */
    function saveFile($dataYaml, $count, $bundleName, $bundleArray) {

        $this->output->writeln(" <fg=yellow;> \r Check if Directory {$bundleArray['class']}  ...  </>");
        $file = $bundleArray['file'] . DIRECTORY_SEPARATOR . "{$count}." . strtolower($bundleName) . "_export_mapping.yml";
        if (!$this->filemanager->checkDir($bundleArray['file'])) {
            $this->output->writeln(" <fg=yellow;> \r building Directory  ...  </>");
            $this->filemanager->createDir($bundleArray['file']);
        }

        if (file_exists($file)) {
            $this->output->writeln(" <fg=yellow;> \r checking existing file and ament new values and skipping changed   ...  </>");

            $dataCheckYaml = array();
            $yaml = Yaml::parse($file);

            if (!empty($dataYaml)) {
                $check = array_diff_assoc($yaml[key($yaml)], $dataYaml[key($dataYaml)]);
                $check_assoc = array_diff_key($dataYaml[key($dataYaml)], $yaml[key($yaml)]);


                if (!empty($check_assoc)) {
                    $question_ = new ConfirmationQuestion(" \n\r New elemels added do you want to add it? ", true, "/^y/i");

                    if ($this->helper->ask($this->input, $this->output, $question_)) {
                        foreach ($check_assoc as $key => $value) {
                            $question_2 = new ConfirmationQuestion(" \n\r New element mapping <fg=yellow;> name is \"{$key}\" </> and <fg=yellow;> method is \"$value\"  </> do you want to add it? ,  \n\r type <fg=yellow;> \"n\"  </> for not change or <fg=yellow;> press enter  </> to change with new values:", true, "/^y/i");
                            if ($this->helper->ask($this->input, $this->output, $question_2)) {
                                $dataYaml[key($dataYaml)][$key] = $value;
                                $this->output->writeln("  <fg=green;> added \"{$key}\" </> and <fg=green;> method \"$value\"  </>");
                            } else {
                                unset($dataYaml[key($dataYaml)][$key]); // unset individualy item there are not set to be added
                            }
                        }
                    }
                }

                if (!empty($check)) {
                    foreach ($check as $key => $value) {
                        $question = new ConfirmationQuestion(" \n\r Do you want to keep change value \"$value\" ,  \n\r type <fg=yellow;> \"n\"  </> for not change or <fg=yellow;> press enter  </> to change with new values:", true, '/^y/i');

                        if ($this->helper->ask($this->input, $this->output, $question)) {
                            $dataYaml[key($dataYaml)][$key] = $dataYaml[key($dataYaml)][$key];
                        } else {
                            $dataYaml[key($dataYaml)][$key] = $value;
                        }
                    }
                }
            }
        }

        $this->output->writeln(" <fg=green;> \r saving file  ...  </>");
        $this->filemanager->generate_yml_file($dataYaml, $file, 3);
    }

    /**
     * @param EntityManager $em
     * @param string|object $class
     *
     * @return boolean
     */
    function isEntity(EntityManager $em, $class) {
        if (is_object($class)) {
            $class = ClassUtils::getClass($class);
        }
        return !$em->getMetadataFactory()->isTransient($class);
    }

}
