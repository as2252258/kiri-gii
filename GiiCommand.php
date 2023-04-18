<?php
declare(strict_types=1);

namespace Gii;


use Exception;
use Kiri;
use Kiri\Di\LocalService;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
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


	private LocalService $service;


	/**
	 *
	 */
	protected function configure()
	{
		$this->service = Kiri::getDi()->get(LocalService::class);
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
			/** @var Gii $gii */
			$gii = $this->service->get('gii');
			if (($db = $input->getOption('database')) != null) {
				$gii->run($this->service->get($db), $input);
			} else {
				$action = $input->getOption('make');
				if (!in_array($action, ['model', 'controller'])) {
					$gii->run(null, $input);
				} else {
					$array = [];
					foreach (Config::get('databases.connections') as $key => $connection) {
						$array[$key] = $gii->run($this->service->get($key), $input);
					}
					$output->writeln(json_encode($array, JSON_UNESCAPED_UNICODE));
				}
			}
		} catch (\Throwable $throwable) {
			$output->writeln(throwable($throwable));
		} finally {
			return 1;
		}
	}

}
