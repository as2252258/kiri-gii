<?php
declare(strict_types=1);


namespace Gii;

use Exception;
use Kiri;
use ReflectionException;

/**
 * Class GiiController
 * @package Gii
 */
class GiiController extends GiiBase
{

    public string $className = '';

    public array $fields = [];


    /**
     * GiiController constructor.
     * @param $className
     * @param $fields
     */
    public function __construct($className, $fields, $tableName)
    {
        $this->className = $className;
        $this->fields    = $fields;
        $this->tableName = $tableName;
    }


    /**
     * @return string|bool
     * @throws ReflectionException
     * @throws Exception
     */
    public function generate(): string|bool
    {
        $path      = $this->getControllerPath();
        $modelPath = $this->getModelPath();

        $managerName = $this->className;

        $namespace       = rtrim($path['namespace'], '\\');
        $model_namespace = rtrim($modelPath['namespace'], '\\');

        $class      = '';
        $controller = str_replace('\\\\', '\\', "$namespace\\{$managerName}Controller");

        $html = "<?php

declare(strict_types=1);

namespace {$namespace};

";
        if (file_exists($path['path'] . '/' . $managerName . 'Controller.php')) {
            try {
                $class = new \ReflectionClass($controller);

                $import = $this->getImports($path['path'] . '/' . $managerName . 'Controller.php', $class);
            } catch (\Throwable $Exception) {
                error($Exception);
                exit();
            }
        } else {
            $import = "use Exception;
use Kiri\Router\Annotate\Post;
use Kiri\Router\Annotate\Get;
use Kiri\Core\Str;
use Kiri\Router\Base\Controller;
use Kiri\Core\Json;
use {$model_namespace}\\{$managerName};
use Kiri\Router\Validator\Validator;
use Psr\Http\Message\ResponseInterface;

";
        }
        if (!empty($import)) {
            $html .= $import;
        }

        $controllerName = $managerName;

        $historyModel = "use {$model_namespace}\\{$managerName};";
        if (!str_contains($html, $historyModel)) {
            $html .= $historyModel;
        }

        $html .= "
		
/**
 * Class {$controllerName}Controller
 *
 * @package controller
 */
class {$controllerName}Controller extends Controller
{

";


        $funcNames = [];
        if (is_object($class)) {
            $html .= $this->getClassProperty($class);
            $html .= $this->getClassMethods($class);
        }

        $default   = ['actionAdd', 'actionUpdate', 'actionAuditing', 'actionBatchAuditing', 'actionDetail', 'actionDelete', 'actionBatchDelete', 'actionList'];
        $tableName = str_replace($this->db->tablePrefix, '', $this->tableName);
        $tableName = str_replace('_', '-', $tableName);

        foreach ($default as $key => $val) {
            if (str_contains($html, ' function ' . $val . '(')) {
                continue;
            }
            $html .= $this->{'controllerMethod' . str_replace('action', '', $val)}($this->fields, $managerName, $managerName, $path, $tableName) . "\n";
        }

        $html .= '
}';

//        $file = APP_PATH . 'routes/' . $this->input->getOption('database') . '.php';
//        if (!file_exists($file)) {
//            touch($file);
//            file_put_contents($file, '<?php' . PHP_EOL);
//            file_put_contents($file, PHP_EOL, FILE_APPEND);
//            file_put_contents($file, PHP_EOL, FILE_APPEND);
//            file_put_contents($file, 'use Kiri\Message\Handler\Router;' . PHP_EOL, FILE_APPEND);
//            file_put_contents($file, PHP_EOL, FILE_APPEND);
//            file_put_contents($file, PHP_EOL, FILE_APPEND);
//        }
//
//
//        $addRouter = 'Router::group([\'prefix\' => \'' . $tableName . '\',\'namespace\' => \'' . $namespace . '\'], function () {
//	Router::post(\'add\', \'' . $controllerName . 'Controller@actionAdd\');
//	Router::get(\'list\', \'' . $controllerName . 'Controller@actionList\');
//	Router::post(\'update\', \'' . $controllerName . 'Controller@actionUpdate\');
//	Router::post(\'auditing\', \'' . $controllerName . 'Controller@actionAuditing\');
//	Router::post(\'batch-auditing\', \'' . $controllerName . 'Controller@actionBatchAuditing\');
//	Router::post(\'batch-delete\', \'' . $controllerName . 'Controller@actionBatchDelete\');
//	Router::post(\'delete\', \'' . $controllerName . 'Controller@actionDelete\');
//	Router::get(\'detail\', \'' . $controllerName . 'Controller@actionDetail\');
//});
//';
//        if (!str_contains($this->clearBlank(file_get_contents($file)), $this->clearBlank($addRouter))) {
//            file_put_contents($file, $addRouter, FILE_APPEND);
//        }

        $file = $path['path'] . '/' . $controllerName . 'Controller.php';
        if (file_exists($file)) {
            unlink($file);
        }

        Kiri::writeFile($file, $html);
        return $controllerName . 'Controller.php';
    }


    /**
     * @return array
     */
    private function getControllerPath(): array
    {
        $dbName = $this->db->id;
        if (empty($dbName) || $dbName == 'db') {
            $dbName = '';
        }

        $module                 = empty($this->module) ? '' : $this->module;
        $modelPath['namespace'] = $this->controllerNamespace . $module;
        $modelPath['path']      = $this->controllerPath . $module;
        if (!is_dir($modelPath['path'])) {
            mkdir($modelPath['path']);
        }
        if (!empty($dbName)) {
            $modelPath['namespace'] = $this->controllerNamespace . ucfirst($dbName);
            $modelPath['path']      = $this->controllerPath . ucfirst($dbName);
        }

        $modelPath['namespace'] = rtrim($modelPath['namespace'], '\\');
        $modelPath['path']      = rtrim($modelPath['path'], '\\');

        if (!is_dir($modelPath['path'])) {
            mkdir($modelPath['path']);
        }
        return $modelPath;
    }

    /**
     * @param $fields
     * @param $className
     * @param null $object
     * @param $path
     * @param string $tableName
     * @return string
     * 新增
     */
    public function controllerMethodAdd($fields, $className, $object, $path, string $tableName): string
    {
        $_path = str_replace(CONTROLLER_PATH, '', $path['path']);
        $_path = lcfirst(rtrim($_path, '/')) . '/' . lcfirst($className);

        $_path = ltrim($_path, '/');

		$this->getData($path, $className, $fields);

        return '
    /**
	 * @return array
	 * @throws Exception
	 */
	#[Post(\'' . $this->db->database . '/' . $tableName . '/add\')]
	public function actionAdd(): array
	{
		$model = new ' . $className . '();
		$model->attributes = $this->request->all();
		if (!$model->save()) {
			return [\'code\' => 500, \'message\' => $model->getLastError()];
		} else {
			return [\'code\' => 0, \'param\' => $model->toArray()];
		}
	}';
    }


    /**
     * @param $fields
     * @param $className
     * @param $object
     * @param $path
     * @param string $tableName
     * @return string
     */
    public function controllerMethodAuditing($fields, $className, $object, $path, string $tableName): string
    {
        return '
	/**
	 * @return array
	 * @throws Exception
	 */
	#[Post(\'' . $this->db->database . '/' . $tableName . '/auditing\')]
	public function actionAuditing(): array
	{
		$model = ' . $className . '::findOne($this->request->post(\'id\', 0));
		if (empty($model)) {
			return [\'code\' => 500, \'message\' => \'必填项不能为空\'];
		}
		if (!$model->update([\'state\' => 1])) {
			return [\'code\' => 500, \'message\' => $model->getLastError()];
		} else {
			return [\'code\' => 0, \'param\' => $model->toArray()];
		}
	}';
    }


    /**
     * @param $fields
     * @param $className
     * @param $object
     * @param $path
     * @param string $tableName
     * @return string
     */
    public function controllerMethodBatchAuditing($fields, $className, $object, $path, string $tableName): string
    {
        return '
	/**
	 * @return array
	 * @throws Exception
	 */
	#[Post(\'' . $this->db->database . '/' . $tableName . '/batch/auditing\')]
	public function actionBatchAuditing(): array
	{
		$ids = $this->request->post(\'ids\', []);
		if (empty($ids)) {
			return [\'code\' => 500, \'message\' => \'必填项不能为空\'];
		}
		$model = ' . $className . '::query()->whereIn(\'id\', $ids);
		if (!$model->update([\'state\' => 1])) {
			return [\'code\' => 500, \'message\' => \'系统繁忙, 请稍后再试\'];
		} else {
			return [\'code\' => 0, \'message\' => \'ok\'];
		}
	}';
    }


    /**
     * @param $fields
     * @param $className
     * @param null $object
     * @param array $path
     * @param string $tableName
     * @return string
     * 构建更新
     */
    public function controllerMethodUpdate($fields, $className, $object = NULL, array $path = [], string $tableName = ''): string
    {
        $_path = str_replace(CONTROLLER_PATH, '', $path['path']);
        $_path = lcfirst(rtrim($_path, '/')) . '/' . lcfirst($className);

        $_path = ltrim($_path, '/');

        return '
    /**
	 * @return array
	 * @throws Exception
	 */
	#[Post(\'' . $this->db->database . '/' . $tableName . '/update\')]
	public function actionUpdate(): array
	{
		$model = ' . $className . '::findOne($this->request->post(\'id\', 0));
		if (empty($model)) {
			return [\'code\' => 500, \'message\' => SELECT_IS_NULL];
		}
		$model->attributes = $this->request->all();
		if (!$model->save()) {
			return [\'code\' => 500, \'message\' => $model->getLastError()];
		} else {
			return [\'code\' => 0, \'param\' => $model->toArray()];		
		}
	}';
    }

    /**
     * @param $fields
     * @param $className
     * @param null $object
     * @param array $path
     * @param string $tableName
     * @return string
     * 构建更新
     */
    public function controllerMethodBatchDelete($fields, $className, $object = NULL, array $path = [], string $tableName = ''): string
    {
        $_path = str_replace(CONTROLLER_PATH, '', $path['path']);
        $_path = lcfirst(rtrim($_path, '/')) . '/' . lcfirst($className);

        $_path = ltrim($_path, '/');

        return '
    /**
	 * @return array
	 * @throws Exception
	 */
	#[Post(\'' . $this->db->database . '/' . $tableName . '/batch/delete\')]
	public function actionBatchDelete(): array
	{
		$_key = $this->request->post(\'ids\', []);		
		if (empty($_key)) {
			return [\'code\' => 500, \'message\' => PARAMS_IS_NULL];
		}
		
		$model = ' . $className . '::query()->whereIn(\'id\', $_key);
		if (!$model->delete()) {
			return [\'code\' => 500, \'message\' => DB_ERROR_BUSY];
		} else {
			return [\'code\' => 0, \'param\' => $_key];
        }
	}';
    }

    /**
     * @param $fields
     * @param $className
     * @param $managerName
     * @param array $path
     * @param string $tableName
     * @return string
     * 构建详情
     */
    public function controllerMethodDetail($fields, $className, $managerName, array $path = [], string $tableName = ''): string
    {
        $_path = str_replace(CONTROLLER_PATH, '', $path['path']);
        $_path = lcfirst(rtrim($_path, '/')) . '/' . lcfirst($className);

        $_path = ltrim($_path, '/');

        return '
    /**
	 * @return array
	 * @throws Exception
	 */
	#[Get(\'' . $this->db->database . '/' . $tableName . '/detail\')]
    public function actionDetail(): array
    {
        $model = ' . $managerName . '::findOne($this->request->query(\'id\'));
        if (empty($model)) {
			return [\'code\' => 500, \'message\' => SELECT_IS_NULL];
		} else {
			return [\'code\' => 0, \'param\' => $model->toArray()];
        }
    }';
    }

    /**
     * @param $fields
     * @param $className
     * @param $managerName
     * @param $path
     * @param string $tableName
     * @return string
     * 构建删除操作
     */
    public function controllerMethodDelete($fields, $className, $managerName, $path, string $tableName = ''): string
    {
        $_path = str_replace(CONTROLLER_PATH, '', $path['path']);
        $_path = lcfirst(rtrim($_path, '/')) . '/' . lcfirst($className);

        $_path = ltrim($_path, '/');

        return '
    /**
	 * @return array
	 * @throws Exception
	 */
    #[Post(\'' . $this->db->database . '/' . $tableName . '/delete\')]
    public function actionDelete(): array
    {
		$_key = $this->request->post(\'id\', 0);
		
		$model = ' . $managerName . '::findOne($_key);
		if (empty($model)) {
			return [\'code\' => 500, \'message\' => SELECT_IS_NULL];
		}
        if (!$model->delete()) {
			return [\'code\' => 500, \'message\' => $model->getLastError()];
		} else {
			return [\'code\' => 0, \'message\' => \'ok\'];
        }
    }';
    }

    /**
     * @param $fields
     * @param $className
     * @param $managerName
     * @param array $path
     * @param string $tableName
     * @return string
     * 构建查询列表
     */
    public function controllerMethodList($fields, $className, $managerName, array $path = [], string $tableName = ''): string
    {
        $_path = str_replace(CONTROLLER_PATH, '', $path['path']);
        $_path = lcfirst(rtrim($_path, '/')) . '/' . lcfirst($className);


        $_path = ltrim($_path, '/');
        return '
    /**
	 * @return array
	 * @throws Exception
	 */
	 #[Get(\'' . $this->db->database . '/' . $tableName . '/list\')]
    public function actionList(): array
    {        
        //分页处理
	    $count   = $this->request->query(\'count\', -1);
	    $order   = $this->request->query(\'order\', \'id\');
	    if (!empty($order)) {
	        $order .= !$this->request->query(\'isDesc\', 0) ? \' asc\' : \' desc\';
	    } else {
	        $order = \'id desc\';
	    }
	    $pWhere = [];
	    ' . $this->getWhere($fields) . '
	    //列表输出
	    $model = ' . $managerName . '::query()->where($pWhere)->orderBy($order);

	   	$keyword = $this->request->query(\'keyword\', null); 
	    if (!empty($keyword)) {
	        $model->whereLike(\'keyword\', $keyword);
	    }
  
        if ((int) $count === 1) {
		    $count = $model->count();
	    }
	    
	    [$offset, $size] = $this->getPageInfo();
	    if ($count != -100) {
		    $model->offset($offset)->limit($size);
	    }
	    
		$data = $model->get()->toArray();
		
		return [\'code\' => 0, \'param\' => $data, \'count\' => $count];
    }
    ';
    }


    private function getData($path, $formClass, $fields): string
    {
        $html = '';

        $length = $this->getMaxLength($fields);

        $class   = '';
        $header  = [];
        $toArray = [];
        foreach ($fields as $key => $val) {
            if (str_starts_with($val['Type'], 'enum')) {
                preg_match('/\(.*\)/', $val['Type'], $number);
                $number[0] = trim($number[0], '()');

                $values    = explode(',', $number[0]);
                $number[1] = 0;
                foreach ($values as $evalue) {
                    $evalue = trim($evalue, '\'');

                    $leng = mb_strlen($evalue);
                    if ($number[1] < $leng) {
                        $number[1] = $leng;
                    }
                }

                $type = strtolower(preg_replace('/\(\'\w+\'(,\'\w+\')*\)/', '', $val['Type']));

                $first = preg_replace('/\s+\w+/', '', $type);
            } else {
                preg_match('/\((\d+)(,(\d+))*\)/', $val['Type'], $number);
                $type = strtolower(preg_replace('/\(\d+(,\d+)*\)/', '', $val['Type']));

                $first = preg_replace('/\s+\w+/', '', $type);
            }

            if ($val['Extra'] == 'auto_increment') continue;
            if ($type == 'timestamp') continue;
            $_field             = [];
            $_field['required'] = $this->checkIsRequired($val);
            foreach ($this->type as $_key => $value) {
                if (!in_array(strtolower($first), $value)) continue;
                $comment        = $val['Comment'];
                $_field['type'] = $_key;

                $toArray[] = '
			\'' . str_pad($val['Field'] . '\'', $length, ' ', STR_PAD_RIGHT) . ' => $this->' . $val['Field'] . ',';
                if ($type == 'date' || $type == 'datetime' || $type == 'time') {
                    if (!in_array('use Kiri\Router\Validator\Inject\Length;', $header)) {
                        $header[] = 'use Kiri\Router\Validator\Inject\Length;';
                    }
                    $class .= match ($type) {
                        'date' => '
	/**
	 * ' . (empty($comment) ? '这批懒的很，没写注释' : $comment) . '
	 */
	#[Length(10)]
	public string $' . $val['Field'] . ' = \'1960-06-01\';

',
                        'time' => '
	/**
	 * ' . (empty($comment) ? '这批懒的很，没写注释' : $comment) . '
	 */
	#[Length(5)]
	public string $' . $val['Field'] . ' = \'00:00\';

',
                        default => '
	/**
	 * ' . (empty($comment) ? '这批懒的很，没写注释' : $comment) . '
	 */
	#[Length(16)]
	public string $' . $val['Field'] . ' = \'\';

',
                    };
                } else if ($type == 'json' || $type == 'text' || $type == 'longtext') {
                    $class .= '
	/**
	 * ' . (empty($comment) ? '这批懒的很，没写注释' : $comment) . '
	 */
	public string $' . $val['Field'] . ' = \'\';

';
                } else {
                    if (isset($number[0])) {
                        if (strpos(',', $number[0])) {
                            $_field['min'] = $number[1];
                            $_field['max'] = $number[3];
                        } else {
                            $_field['min'] = 0;
                            $_field['max'] = $number[1];
                        }
                    }
                    if ($type == 'enum' && !in_array('use Kiri\Router\Validator\Inject\In;', $header)) {
                        $header[] = 'use Kiri\Router\Validator\Inject\In;';
                    }
                    if ($_field['required'] == 'true' && !in_array('use Kiri\Router\Validator\Inject\Required;', $header)) {
                        $header[] = 'use Kiri\Router\Validator\Inject\Required;';
                    }
                    if (!in_array('use Kiri\Router\Validator\Inject\MaxLength;', $header)) {
                        $header[] = 'use Kiri\Router\Validator\Inject\MaxLength;';
                    }
                    $class .= '
	/**
	 * ' . (empty($comment) ? '这批懒的很，没写注释' : $comment) . '
	 */' . ($type == 'enum' ? '
	#[In([' . $number[0] . '])]' : '') . ($_field['required'] == 'true' ? '
	#[Required]' : '') . '
	#[MaxLength(' . ($number[1] ?? 0) . ')]
	public ' . $_key . ' $' . $val['Field'] . ' = ' . (match ($type) {
                            'tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'float', 'double', 'decimal', 'timestamp', 'year' => 0,
                            'bool', 'boolean' => false,
                            default => '\'\'',
                        }) . ';

';
                }
            }
            $this->rules[$val['Field']] = $_field;
        }

        $namespace = str_replace('Controller', 'Form', $path['namespace']);
        $path      = str_replace('Controller', 'Form', $path['path']);
        if (!is_dir($_SERVER['PWD'] . '/app/Form/')) {
            mkdir($_SERVER['PWD'] . '/app/Form/');
        }
        if (!is_dir($path)) {
            mkdir($path);
        }
        if (!file_exists($path . '/' . $formClass . 'Form.php')) {
            touch($path . '/' . $formClass . 'Form.php');
        }
        file_put_contents($path . '/' . $formClass . 'Form.php', '<?php 

namespace ' . $namespace . ';

' . implode(PHP_EOL, $header) . PHP_EOL . PHP_EOL . '
/**
 * FormData
 */
class ' . $formClass . 'Form implements \Arrayable
{

' . $class . '
	

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return [' . implode($toArray) . '
		];
	}

}');
        return $html;
    }


    /**
     * @param $fields
     * @return int
     */
    private function getMaxLength($fields): int
    {
        $length = 0;
        foreach ($fields as $key => $val) {
            if (mb_strlen($val['Field'] . ' >=') > $length) $length = mb_strlen($val['Field'] . ' >=');
        }
        return $length;
    }

    /**
     * @param $fields
     * @return string
     */
    private function getWhere($fields): string
    {
        $html = '';

        $length = $this->getMaxLength($fields);
        foreach ($fields as $key => $val) {
            preg_match('/\d+/', $val['Type'], $number);

            $type = strtolower(preg_replace('/\(\d+\)/', '', $val['Type']));

            $first = preg_replace('/\s+\w+/', '', $type);

            if ($type == 'timestamp') continue;
            if ($type == 'json') continue;

            foreach ($this->type as $_key => $value) {
                if (!in_array(strtolower($first), $value)) continue;
                $comment = '//' . $val['Comment'];
                if ($type == 'date' || $type == 'datetime' || $type == 'time') {
                    $_tps = '$this->request->query(\'' . $val['Field'] . '\', null)';
                    $html .= '
        $pWhere[\'' . str_pad($val['Field'] . ' <=\']', $length, ' ', STR_PAD_RIGHT) . ' = ' . str_pad($_tps . ';', 60, ' ', STR_PAD_RIGHT) . $comment;
                    $html .= '
        $pWhere[\'' . str_pad($val['Field'] . ' >=\']', $length, ' ', STR_PAD_RIGHT) . ' = ' . str_pad($_tps . ';', 60, ' ', STR_PAD_RIGHT) . $comment;
                } else {

                    $_tps = '$this->request->query(\'' . $val['Field'] . '\', null)';
                    $html .= '
        $pWhere[\'' . str_pad($val['Field'] . '\']', $length, ' ', STR_PAD_RIGHT) . ' = ' . str_pad($_tps . ';', 60, ' ', STR_PAD_RIGHT) . $comment;
                }
            }
        }
        return $html;
    }
}
