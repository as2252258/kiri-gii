<?php
declare(strict_types=1);


namespace Gii;


use Exception;
use Kiri;
use Kiri\Abstracts\Providers;
use Kiri\Application;

/**
 * Class DatabasesProviders
 * @package Database
 */
class GiiProviders extends Providers
{


	/**
	 * @param Application $application
	 * @throws Exception
	 */
	public function onImport(Application $application)
	{
		$application->set('gii', ['class' => Gii::class]);

		$container = Kiri::getDi();

		$console = $container->get(\Symfony\Component\Console\Application::class);
		$console->add($container->get(GiiCommand::class));
	}
}
