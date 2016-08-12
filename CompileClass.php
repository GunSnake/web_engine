<?php
/**
 * Created by PhpStorm.
 * User: 95
 * Date: 2016/8/8
 * Time: 10:59
 */

namespace Lib;


class CompileClass
{
    private $template;          //待编译的文件
    private $content;           //需要替换的文本
    private $comfile;           //编译后的文件
    private $left='{';          //左定界符
    private $right='}';         //右定界符
    private $value = array();   //值栈
    private $T_P = array();     //正则语句
    private $T_R = array();     //替换后的语句

    public function __construct($template, $compileFile, $config)
    {
        $this->template = $template;
        $this->comfile = $compileFile;
        $this->content = file_get_contents($template);
        if ($config['php_turn'] === false){
            $this->T_P[] = "#<\?(=|php |)(.+?)\?>#is";//php长短标签语法
            $this->T_R[] = "&lt;?\\1\\2?&gt;";
        }
        $this->T_P[] = "#\{\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}#";//{$var}形式
        $this->T_P[] = "#\{(loop|foreach) \\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}#i";// loop|foreach $v 形式
        $this->T_P[] = "#\{\/(loop|foreach|if)\}#i";// /if|/foreach|/loop
        $this->T_P[] = "#\{([k|v])\}#";//{k}|{v}
        $this->T_P[] = "#\{if (.* ?)\}#i";// if $etc.
        $this->T_P[] = "#\{(elseif|else if) (.* ?)\}#i";//elseif|else if $etc.
        $this->T_P[] = "#\{else\}#i";// else
        $this->T_P[] = "#\{(\#|\* )(.* ?)(\#|\* )\}#"; //注释语法{#etc.#}
        $this->T_P[] = "#\<\!\-\-(.* ?)\-\-\>#";//html注释语法<!--etc.-->

        $this->T_R[] = "<?php echo \$this->value['\\1']; ?>";
        $this->T_R[] = "<?php foreach ((array)\$this->value['\\2'] as \$k => \$v) { ?>";
        $this->T_R[] = "<?php } ?>";
        $this->T_R[] = "<?php echo \$\\1; ?>";
        $this->T_R[] = "<?php if (\\1) { ?>";
        $this->T_R[] = "<?php }else if (\\2){ ?>";
        $this->T_R[] = "<?php }else{ ?>";
        $this->T_R[] = "";
        $this->T_R[] = "";
    }

    /**
     * 编译文件
     */
    public function compile(){
        $this->c_var();
        file_put_contents($this->comfile,$this->content);
    }

    /**
     * 把模板语言翻译为php语法
     */
    public function c_var(){
        $this->content = preg_replace($this->T_P, $this->T_R, $this->content);
    }

    /**
     * 对静态js文件解析，
     */
    public function c_staticFile(){
        $this->content = preg_replace('#\{\!(.* ?)\!\}', '<script src=\\1'.'?t='.time().'><script>', $this->content);
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }
}