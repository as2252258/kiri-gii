<?php
declare(strict_types=1);


namespace Gii;


use Database\Connection;
use Exception;
use Kiri\Core\Json;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use const APP_PATH;

/**
 * Class GiiBase
 * @package Gii
 */
abstract class GiiBase
{

	public array $fileList = [];


	protected InputInterface $input;

	public string $modelPath = APP_PATH . 'models/';
	public string $modelNamespace = 'app\Model\\';

	public string $controllerPath = APP_PATH . 'controllers/';
	public string $controllerNamespace = 'app\\Controller\\';

	public ?string $module = null;

	public array $rules = [];
	public array $type = [
		'int'       => ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'],
		'string'    => ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'enum'],
		'date'      => ['date'],
		'time'      => ['time'],
		'year'      => ['year'],
		'datetime'  => ['datetime'],
		'timestamp' => ['timestamp'],
		'float'     => ['float', 'double', 'decimal',],
	];
	public ?string $tableName = NULL;

	public ?Connection $db = null;

	/**
	 * @param string $modelPath
	 */
	public function setModelPath(string $modelPath): void
	{
		$this->modelPath = $modelPath;
	}

	/**
	 * @param string $modelNamespace
	 */
	public function setModelNamespace(string $modelNamespace): void
	{
		$this->modelNamespace = $modelNamespace;
	}

	/**
	 * @param string $controllerPath
	 */
	public function setControllerPath(string $controllerPath): void
	{
		$this->controllerPath = $controllerPath;
	}

	/**
	 * @param $module
	 */
	public function setModule($module)
	{
		$this->module = $module;
	}

	/**
	 * @param string $controllerNamespace
	 */
	public function setControllerNamespace(string $controllerNamespace): void
	{
		$this->controllerNamespace = $controllerNamespace;
	}


	/**
	 * @param InputInterface $input
	 */
	public function setInput(InputInterface $input)
	{
		$this->input = $input;
	}


	/**
	 * @param ReflectionClass $object
	 * @param                  $className
	 *
	 * @return string
	 */
	public function getUseContent(ReflectionClass $object, $className): string
	{
		if (empty($object)) {
			return '';
		}
		$file = $this->getFilePath($className);
		if (!file_exists($file)) {
			return '';
		}
		$content = file_get_contents($file);
		$explode = explode(PHP_EOL, $content);
		$exists = array_slice($explode, 0, $object->getStartLine());
		$_tmp = [];
		foreach ($exists as $key => $val) {
			if (trim($val) == '/**') {
				break;
			}
			$_tmp[] = $val;
		}
		return trim(implode(PHP_EOL, $_tmp));
	}


	/**
	 * @param string $fileName
	 * @param ReflectionClass $class
	 * @return string
	 */
	protected function getImports(string $fileName, ReflectionClass $class): string
	{
		$startLine = 1;
		$array = [];
		$fileOpen = fopen($fileName, 'r');
		while (($content = fgets($fileOpen)) !== false) {
			if (str_starts_with($content, 'use ')) {
				$array[] = $content;
			}
			if ($startLine == $class->getStartLine()) {
				break;
			}
			++$startLine;
		}
		return implode($array);
	}


	/**
	 * @param ReflectionClass $class
	 * @return string
	 * @throws ReflectionException
	 */
	protected function getClassProperty(ReflectionClass $class): string
	{
		$html = '';

		$rc = $class->getParentClass()->getConstants();

		foreach ($class->getConstants() as $key => $val) {
			if (isset($rc[$key])) {
				continue;
			}
			if (is_numeric($val)) {
				$html .= '
    const ' . $key . ' = ' . $val . ';' . "\n";
			} else {
				$html .= '
    const ' . $key . ' = \'' . $val . '\';' . "\n";
			}
		}

		foreach ($class->getDefaultProperties() as $key => $val) {
			$property = $class->getProperty($key);
			if ($key == 'primary' || $key == 'table' || $key == 'connection' || $key == 'rules') {
				continue;
			}
			if ($property->class != $class->getName()) continue;
			if (is_array($val)) {
				$val = '[\'' . implode('\', \'', $val) . '\']';
			} else if (!is_numeric($val)) {
				$val = '\'' . $val . '\'';
			}

			if ($property->isProtected()) {
				$debug = 'protected';
			} else if ($property->isPrivate()) {
				$debug = 'private';
			} else {
				$debug = 'public';
			}
			if ($property->hasType()) {
				$type = ' ' . $property->getType() . ' $' . $key . ' = ' . $val . ';' . "\n";
			} else {
				$type = ' $' . $key . ' = ' . $val . ';' . "\n";
			}
			if ($property->isStatic()) {
				$html .= '
    ' . $debug . ' static' . $type;
			} else {
				$html .= '
    ' . $debug . $type;
			}

		}
		return $html;
	}


	/**
	 * @param ReflectionClass $class
	 * @param array $filters
	 * @return string
	 * @throws Exception
	 */
	protected function getClassMethods(ReflectionClass $class, array $filters = []): string
	{
		$methods = $class->getMethods();

		$classFileName = str_replace(APP_PATH, '', $class->getFileName());

		$content = [];
		if (!empty($methods)) foreach ($methods as $key => $val) {
			if ($val->class != $class->getName()) continue;
			if (in_array($val->name, $filters)) continue;
			$over = "
	" . $val->getDocComment() . "\n";

			$attributes = $val->getAttributes();
			if (!empty($attributes)) {
				foreach ($attributes as $attribute) {
					$explode = explode('\\', $attribute->getName());

					$_array = [];
					foreach ($attribute->getArguments() as $_key => $argument) {
						$argument = $this->resolveArray($argument);
						if (is_numeric($_key)) {
							$_array[] = $argument;
						} else {
							$_array[] = $_key . ': ' . $argument . '';
						}
					}

					if (empty($_array)) {
						$end = "	#[" . end($explode) . "]
";
					} else {
						$end = "	#[" . end($explode) . "(" . implode(',', $_array) . ")]
";
					}
					if (str_contains($over, $end)) {
						$over = str_replace($end, '', $over);
					}
					$over .= $end;
				}
			}

			$func = $this->getFuncLineContent($class, $classFileName, $val->name) . "\n";

			$content[] = $over . $func;
		}
		return implode(PHP_EOL, $content);
	}


	/**
	 * @param $argument
	 * @return string
	 */
	private function resolveArray($argument): string
	{
		if (is_array($argument)) {
			$__array = [];
			foreach ($argument as $key => $value) {
				if (is_string($value)) {
					if (str_contains($value, '\\') && class_exists($value)) {
						$explode_class = explode('\\', $value);

						$__array[] = end($explode_class) . '::class';
					} else {
						$__array[] = '\'' . $value . '\'';
					}
				} else {
					$value = str_replace('{', '[', Json::encode($value));
					$value = str_replace('}', ']', Json::encode($value));
					$value = str_replace(':', '=>', Json::encode($value));

					$value = preg_replace('/"\d+"\=\>/', '', $value);

					$__array[] = $value;
				}
			}

			$argument = '[' . implode(', ', $__array) . ']';
		} else {
			$argument = '\'' . $argument . '\'';
		}
		return $argument;
	}


	/**
	 * @param $fields
	 * @return mixed 返回表主键
	 * 返回表主键
	 */
	public function getPrimaryKey($fields): mixed
	{
		$condition = ['PRI', 'UNI'];
		foreach ($fields as $field) {
			if ($field['Extra'] == 'auto_increment') {
				return $field['Field'];
			}
			if (in_array($field['Key'], $condition)) {
				return $field['Field'];
			}
		}
		return null;
	}

	/**
	 * @param $className
	 * @return string
	 */
	private function getFilePath($className): string
	{
		if (strpos($className, '\\')) {
			$className = str_replace('\\', '/', $className);
		}
		if (strpos($className, '\\')) {
			$className = str_replace('\\', '/', $className);
		}

		return APP_PATH . $className;
	}

	/**
	 * @param ReflectionClass $object
	 * @param                  $className
	 * @param                  $method
	 * @return string
	 * @throws Exception
	 */
	public function getFuncLineContent(ReflectionClass $object, $className, $method): string
	{
		$fun = $object->getMethod($method);

		$content = file_get_contents($this->getFilePath($className));
		$explode = explode(PHP_EOL, $content);
		$exists = array_slice($explode, $fun->getStartLine() - 1, $fun->getEndLine() - $fun->getStartLine() + 1);
		return implode(PHP_EOL, $exists);
	}


	/**
	 * @return array
	 */
	protected function getModelPath(): array
	{
		$dbName = $this->db->id;
		if (empty($dbName) || $dbName == 'db') {
			$dbName = '';
		}

		$modelPath = [
			'namespace' => $this->modelNamespace,
			'path'      => $this->modelPath,
		];
		if (!is_dir($modelPath['path'])) {
			mkdir($modelPath['path']);
		}
		if (!empty($dbName)) {
			$modelPath['namespace'] = $this->modelNamespace . ucfirst($dbName);
			$modelPath['path'] = $this->modelPath . ucfirst($dbName);
		}

		if (!is_dir($modelPath['path'])) {
			mkdir($modelPath['path']);
		}
		return $modelPath;
	}

	/**
	 * @param $db
	 */
	public function setConnection($db)
	{
		$this->db = $db;
	}

	/**
	 * @param $val
	 * @return string
	 */
	protected function checkIsRequired($val): string
	{
		return strtolower($val['Null']) == 'no' && $val['Default'] === NULL ? 'true' : 'false';
	}

	/**
	 * @return array
	 */
	public function getFileLists(): array
	{
		return $this->fileList;
	}

}
