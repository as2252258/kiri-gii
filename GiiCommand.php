<?php
declare(strict_types=1);

namespace Gii;


use Database\DatabasesProviders;
use Exception;
use Kiri;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Command
 * @package Http
 */
class GiiCommand extends Command
{

    public string $command = 'sw:gii';


    public string $description = './snowflake sw:gii make=model|controller|task|interceptor|limits|middleware name=xxxx';


    /**
     *
     */
    protected function configure()
    {
        $this->setName('sw:gii')
             ->addOption('make', 'm', InputArgument::OPTIONAL)
             ->addOption('table', 't', InputArgument::OPTIONAL)
             ->addOption('database', 'd', InputArgument::OPTIONAL)
             ->setDescription('php kiri.php sw:gii --table u_user --database users --make model');
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $database = Kiri::getDi()->get(DatabasesProviders::class);

            /** @var Gii $gii */
            $gii = Kiri::getDi()->get(Gii::class);
            if (($db = $input->getOption('database')) != null) {
                return count($gii->run($database->get($db), $input));
            }
            $action = $input->getOption('make');
            if (!in_array($action, ['model', 'controller'])) {
                return count($gii->run(null, $input));
            }
            $array = [];
            foreach (\config('databases.connections') as $key => $connection) {
                $array[$key] = $gii->run($database->get($key), $input);
            }
            $output->writeln(json_encode($array, JSON_UNESCAPED_UNICODE));
            return 0;
        } catch (\Throwable $throwable) {
            $output->writeln(throwable($throwable));
            return 1;
        }
    }

}
