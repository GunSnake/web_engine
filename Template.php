<?php
/**
 * Created by PhpStorm.
 * User: 95
 * Date: 2016/8/8
 * Time: 10:34
 */

namespace Lib;
include 'CompileClass.php';
use Lib\CompileClass;
class Template
{
    private $arrayConfig = array(
        'suffix' => '.m',           //模板文件后缀
        'templateDir'=> 'template/',//模板文件的文件夹
        'compiledir'=>'cache/',     //编译后文件存放的目录
        'cache_htm'=> false,        //是否需要编译为静态的html文件
        'suffix_cache'=> '.htm',    //编译文件的后缀
        'cache_time'=>1800,         //缓存失效的时间
        'php_turn'  => false,       //是否启用php语法
        'debug' => false,
    );
    public $file;   //模板文件名
    static private $instance = null;
    private $value = array();
    private $compileTool;
    public $debug = array(); //调试信息
    private $controlData = array();

    public function __construct($arrayConfig = array())
    {
        $this->arrayConfig = $arrayConfig + $this->arrayConfig;
        $this->getPath();
    }

    public function getPath(){
        $this->arrayConfig['templateDir'] = strtr(realpath($this->arrayConfig['templateDir']), '\\', '/') . '/';
        $this->arrayConfig['compiledir'] = strtr(realpath($this->arrayConfig['compiledir']), '\\', '/') . '/';
    }
    /**
     * 获取模板实例
     * @return Template|null
     */
    public static function getInstance(){
        if (is_null(self::$instance)){
            self::$instance=new Template();
        }
        return self::$instance;
    }

    /**
     * 增加模板设置
     * @param $key
     * @param null $value
     */
    public function setConfig($key, $value=null){
        if (is_array($key)){
            $this->arrayConfig = $key + $this->arrayConfig;
        }else{
            $this->arrayConfig[$key] = $value;
        }
    }

    /**
     * 获取模板设置
     * @param $key
     * @return array|mixed
     */
    public function getConfig($key = null){
        if ($key){
            return $this->arrayConfig[$key];
        }else{
            return $this->arrayConfig;
        }
    }

    /**
     * 注入单个变量的值
     * @param $key
     * @param $value
     */
    public function assign($key, $value){
        $this->value[$key] = $value;
    }

    /**
     * 注入多个变量的值
     * @param $array
     */
    public function assignArray($array){
        if (is_array($array)){
            foreach($array as $k => $v){
                $this->value[$k] = $v;
            }
        }
    }

    /**
     * 模板文件路径地址
     * @return string
     */
    public function path(){
        return $this->arrayConfig['templateDir'].$this->file.$this->arrayConfig['suffix'];
    }

    /**
     * 判断是否开启了缓存
     * @return mixed
     */
    public function needCache(){
        return $this->arrayConfig['cache_htm'];
    }

    public function reCache($file){
        $flag = false;
        $cacheFile = $this->arrayConfig['compiledir'].md5($file).'.htm';
        if ($this->arrayConfig['cache_htm'] === true) {//是否需要缓存
            $timeFlag = (time()-filemtime($cacheFile)) < $this->arrayConfig['cache_time'] ? true : false;
            if (!is_file($cacheFile)&&filesize($cacheFile) > 1 && $timeFlag) $flag = true;
        }
        return $flag;
    }
    /**
     * 显示模板
     * @param $file
     */
    public function show($file){
        $this->file = $file;
        if (!is_file($this->path())){
            exit('找不到对应的模板');
        }
        $compileFile = $this->arrayConfig['compiledir'].md5($file).'.php';
        $cacheFile = $this->arrayConfig['compiledir'].md5($file).'.htm';
        if ($this->reCache($file) === false){
            $this->debug['cached'] = 'false';
            $this->debug['begin'] = microtime(true);
            $this->compileTool = new CompileClass($this->path(), $compileFile, $this->arrayConfig);
            if ($this->needCache()) ob_start();
            //将变量从数组导入到当前符号表中,仅原生php语法有效
            extract($this->value,EXTR_OVERWRITE);
            //缓存是否过期
            if (!is_file($compileFile) || filemtime($compileFile) < filemtime($this->path())){
                $this->compileTool->vars = $this->value;
                $this->compileTool->compile();
                include $compileFile;
            }else{
                include $compileFile;
            }
            if ($this->needCache()){
                $message = ob_get_contents();
                file_put_contents($cacheFile, $message);
            }
        }else{
            readfile($cacheFile);
            $this->debug['cached'] = 'true';
        }
        $this->debug['spend'] = microtime(true) - $this->debug['begin'];
        $this->debug['count'] = count($this->value);
        $this->debug_info();
    }

    public function debug_info(){
        if ($this->arrayConfig['debug'] === true){
            echo PHP_EOL,'---------debug info--------',PHP_EOL, '<br />';
            echo '程序运行日期', date('Y-m-d H:i:s'),PHP_EOL, '<br />';
            echo '模板解析耗时', $this->debug['spend'].'秒',PHP_EOL, '<br />';
            echo '模板包含标签数目',$this->debug['count'],PHP_EOL, '<br />';
            echo '是否使用静态缓存',$this->debug['cached'],PHP_EOL, '<br />';
            echo '模板引擎实例参数',var_dump($this->getConfig()),PHP_EOL, '<br />';
            echo '模板使用的数据', var_dump($this->value),PHP_EOL, '<br />';
        }
    }
    /**
     * 清理缓存
     * @param null $path
     */
    public function clean($path=null){
        if ($path === null){
            $path = $this->arrayConfig['compiledir'];
            $path = glob($path.'*'.$this->arrayConfig['suffix_cache']);
        }else{
            $path = $this->arrayConfig['compiledir'].md5($path).'.htm';
        }
        foreach ((array)$path as $v){
            unlink($v);
        }
    }
}