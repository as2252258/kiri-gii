<?php
declare(strict_types=1);


namespace Gii;


use Exception;
use Kiri;
use const APP_PATH;

/**
 * Class GiiMiddleware
 * @package Gii
 */
class GiiMiddleware extends GiiBase
{


	/**
	 * @return array
	 * @throws Exception
	 */
	public function generate(): array
	{

		$managerName = $this->input->getArgument('name');
		if (empty($managerName)) {
			throw new Exception('文件名称不能为空~');
		}
		$html = '<?php
		
		
namespace App\Middleware;

use Closure;
use Psr\Http\Server\MiddlewareInterface;
use Kiri\Server\Constrict\RequestInterface;

';

		$managerName = ucfirst($managerName);
		$html .= '
 /**
 * Class ' . $managerName . 'Middleware
 * @package App\Middleware
 */
class ' . $managerName . 'Middleware implements Middleware
{
	
	/**
	 * @param Request $request
	 * @param Closure $closure
	 * @return mixed
	 */
	public function handler(RequestInterface $request, Closure $closure)
	{
		return $closure($request);
	}


}';

		$file = APP_PATH . 'app/Http/Middleware/' . $managerName . 'Middleware.php';
		if (file_exists($file)) {
			throw new Exception('File exists.');
		}

		Kiri::writeFile($file, $html);
		return [$managerName . 'Middleware.php'];
	}

}
