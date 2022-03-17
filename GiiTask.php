<?php
declare(strict_types=1);


namespace Gii;

use Exception;
use Kiri;
use const APP_PATH;

/**
 * Class GiiModel
 * @package Gii
 */
class GiiTask extends GiiBase
{


	/**
	 * @return string[]
	 * @throws Exception
	 */
	public function generate(): array
	{

		$managerName = $this->input->getArgument('name', null);
		if (empty($managerName)) {
			throw new Exception('文件名称不能为空~');
		}
		$html = '<?php
		
		
namespace App\Async;

use Kiri\Task\OnTaskInterface;

';

		$managerName = ucfirst($managerName);
		$html .= '
/**
 * Class ' . $managerName . '
 * @package App\Async
 */
class ' . $managerName . ' implements Task
{
	
	protected $params = [];


	/**
	 * @return mixed|void
	 */
	public function onHandler()
	{
		// TODO: Implement handler() method.
	}


	/**
	 * @param $params
	 * @return $this
	 */
	public function setParams(array $params)
	{
		$this->params = $params;
		return $this;
	}


	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}


}';

		$file = APP_PATH . 'app/Async/' . $managerName . '.php';
		if (file_exists($file)) {
			throw new Exception('File exists.');
		}

		Kiri::writeFile($file, $html);
		return [$managerName . '.php'];
	}

}
