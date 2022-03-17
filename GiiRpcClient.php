<?php


namespace Gii;


use Exception;
use Kiri;
use const APP_PATH;

/**
 * Class GiiRpcClient
 * @package Gii
 */
class GiiRpcClient extends GiiBase
{

	/**
	 * @return array
	 * @throws Exception
	 */
	public function generate(): array
	{

		$managerName = $this->input->getArgument('name', null);
		if (empty($managerName)) {
			throw new Exception('文件名称不能为空~');
		}

		$service = $this->input->getArgument('service', strtolower($managerName));

		$port = $this->input->getArgument('port', 443);
		$mode = $this->input->getArgument('mode', 'SWOOLE_SOCK_TCP');

		$html = '<?php
		
		
namespace App\Client\Rpc;

use Kiri\Annotation\Rpc\Consumer;
use Kiri\Annotation\Rpc\RpcClient;
use Kiri\Annotation\Target;
use Exception;
use Rpc\Client;
use Kiri\Core\Json;
use Kiri;

';

		$managerName = ucfirst($managerName);
		$html .= '
 /**
 * Class ' . $managerName . 'Consumer
 * @package App\Client\Rpc
 */
#[Target]
#[RpcClient(cmd: \'' . $service . '\', port: ' . $port . ', timeout: 1, mode: ' . $mode . ')]
class ' . $managerName . 'Consumer extends \Rpc\Consumer
{
	
		public array $node = [\'host\' => \'127.0.0.1\', \'port\' => 5377];


	/**
	 * @return Client
	 * @throws Exception
	 */
	public function initClient(): Client
	{
		// TODO: Implement initClient() method.
		return $this->client = $this->rpc->getClient(\'' . $service . '\');
	}



	/**
	 * @param string $event
	 * @param array $params
	 * @throws Exception
	 */
	#[Consumer(\'default\')]
	public function push(string $event, array $params)
	{
		
	}
}';

		if (!is_dir(APP_PATH . 'app/Client/Rpc/')) {
			mkdir(APP_PATH . 'app/Client/Rpc/', 0777, true);
		}

		$file = APP_PATH . 'app/Client/Rpc/' . $managerName . 'Middleware.php';
		if (file_exists($file)) {
			throw new Exception('File exists.');
		}

		Kiri::writeFile($file, $html);
		return [$managerName . 'Middleware.php'];
	}
}
