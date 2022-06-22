<?php
declare(strict_types=1);


namespace Gii;


use Kiri\Abstracts\Providers;
use Kiri\Di\LocalService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Application;

/**
 * Class DatabasesProviders
 * @package Database
 */
class GiiProviders extends Providers
{


	/**
	 * @param LocalService $application
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function onImport(LocalService $application): void
	{
		$application->set('gii', ['class' => Gii::class]);

		$console = $this->container->get(Application::class);
		$console->add($this->container->get(GiiCommand::class));
	}
}
