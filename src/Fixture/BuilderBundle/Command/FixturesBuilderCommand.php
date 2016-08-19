<?php

namespace Application\UtilityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Process\Process;

class FixturesBuilderCommand extends ContainerAwareCommand {

    protected function configure() {

        $this
                ->setName('utility:fixture:rebuild')
                ->setDescription('Creates fixtures of provided bundle') 
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
     
        $processes = array();

        $processes[] = array(
            'process' => new Process('php app/console doctrine:schema:update --force'),
            'msg' => 'Forcing doctrine schema update...'
        );
   
        $processes[] = array(
            'process' => new Process('php app/console  rad:fixtures:load -r -f users -f site -f menu -f dashboard -f block'),
            'msg' => 'Loading users, pages, menus and dashboards fixtures...'
        );
     
        $progress = new ProgressBar($output, count($processes));
        $progress->setFormat('%current%/%max% %message% [%bar%] %percent:3s%% | %elapsed:6s%/%estimated:-6s% | %memory:6s% ');
        $progress->setMessage('Start Deployment');
        $progress->start();
        foreach ($processes as $process) {
            try {
                $currentProcess = $process['process'];
                $currentProcess->run(function ($type, $buffer) {
                    echo $buffer;
                });
                $currentProcess->setTimeout(3600);
                $currentProcess->setIdleTimeout(3600);

                while ($currentProcess->isRunning()) {
                    //Waiting for process to finish...
                }
                $progress->setMessage($process['msg']);
                $progress->advance();
            } catch (\Exception $e) {
                //$io->text(sprintf('<error>%s</error>', $e->getMessage()));
                break;
            }
        }
        $progress->setMessage('Fixture rebuild is complete.');
        $progress->finish();
    }

}
