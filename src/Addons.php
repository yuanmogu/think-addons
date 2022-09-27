<?php
/**
 * +----------------------------------------------------------------------
 * | think-addons [thinkphp6]
 * +----------------------------------------------------------------------
 */
declare(strict_types=1);

namespace think;

use think\App;
use think\helper\Str;
use think\facade\Config;
use think\facade\View;
use think\facade\Db;
use think\view\driver\Think;


abstract class Addons
{
    // app 容器
    protected $app;
    // 请求对象
    protected $request;
    // 当前插件标识
    protected $name;
    // 插件路径
    protected $addon_path;
    // 视图模型
    protected $view;
    // 插件配置
    protected $addon_config;
    // 插件信息
    protected $addon_info;
    // 错误信息
    protected $error = '';

    /**
     * 插件构造函数
     * Addons constructor.
     * @param \think\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->name = $this->getName();
        $this->addon_path = $app->addons->getAddonsPath() . $this->name . DIRECTORY_SEPARATOR;
        $this->addon_config = "addon_{$this->name}_config";
        $this->addon_info = "addon_{$this->name}_info";

        $this->view = new Think($app, config('view'));
        $this->view->config([
            'view_path' => $this->addon_path . 'view' . DIRECTORY_SEPARATOR
        ]);

	
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}


    /**
     * 获取插件标识
     * @return mixed|null
     */
    final protected function getName()
    {
        $class = get_class($this);
        list(, $name, ) = explode('\\', $class);
        $this->request->addon = $name;

        return $name;
    }


    /**
     * 模板变量赋值
     * @access protected
     * @param  mixed $name  要显示的模板变量
     * @param  mixed $value 变量的值
     * @return $this
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign([$name => $value]);

        return $this;
    }

    /**
     * 加载模板输出
     * @param string $template
     * @param array $vars           模板文件名
     * @return false|mixed|string   模板输出变量
     * @throws \think\Exception
     */
    protected function fetch($template = '', $vars = [])
    {
        return $this->view->fetch($template, $vars);
    }

    /**
     * 渲染内容输出
     * @access protected
     * @param  string $content 模板内容
     * @param  array  $vars    模板输出变量
     * @return mixed
     */
    protected function display($content = '', $vars = [])
    {
        return $this->view->display($content, $vars);
    }

    /**
     * 初始化模板引擎
     * @access protected
     * @param  array|string $engine 引擎参数
     * @return $this
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);

        return $this;
    }



    /**
     * 插件基础信息
     * @return array
     */
    final public function getInfo()
    {
        // 文件属性
        $info = $this->info ?? [];
        // 文件配置
        $info_file = $this->addon_path . 'info.ini';
        if (is_file($info_file)) {
            $_info = parse_ini_file($info_file, true, INI_SCANNER_TYPED) ?: [];
            $_info['url'] = addons_url();
            $info = array_merge($_info, $info);
        }
   
        return isset($info) ? $info : [];
    }

    /**
     * 获取配置信息
     * @param bool $type 是否获取完整配置
     * @return array|mixed
     */
    final public function getConfig($type = false)
    {
 
        $config_file = $this->addon_path . 'config.php';

        if (is_file($config_file)) {
            $temp_arr = (array)include $config_file;
		
			//先从数据库中读取数值
			$config = Db::name('site_addons')->where('name',$this->name)->value('config');
			if(!empty($config)){

				$config = unserialize($config);
				
				//如果只取值，直接返回。
				if(!$type) return $config;

				//重新赋值
				foreach($temp_arr as $key => &$val){

					if (isset($config[$key])) {
						foreach ($val['item'] as $k=>&$v) {
							
							if (isset($config[$key][$v['name']])) {
								$v['value'] = $config[$key][$v['name']];
							}
						}
						
					}
			  
				}

				return $temp_arr;
		
			}

			if($type) return $temp_arr;
				
			//读取基础配置信息
			$config = [];
            foreach ($temp_arr as $key => $value) {

				foreach ($value['item'] as $kk=>$v) {
					
					if (in_array($v['type'], ['checkbox'])) {
						$config[$key][$v['name']]= explode(',', $v['value']);
					} else if (in_array($v['type'], ['images'])) {
						$config[$key][$v['name']]= explode('|', $v['value']);
					} else {
						$config[$key][$v['name']] = $v['value'];
					}
				}
		  
            }
            unset($temp_arr);
			return $config;
        }
 
        
    }


}
