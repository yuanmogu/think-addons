<?php
/**
 * +----------------------------------------------------------------------
 * | think-addons [thinkphp6]
 * +----------------------------------------------------------------------
 */
declare(strict_types=1);

namespace think\addons;

use think\Response;
use think\helper\Str;
use think\facade\Event;
use think\facade\Config;
use think\exception\HttpException;
use think\exception\HttpResponseException;


class Route
{
    /**
     * 插件路由请求
     * @param null $addon
     * @param null $controller
     * @param null $action
     * @return mixed
     */
    public static function execute($addon = null, $controller = null, $action = null)
    {
        $app = app();
        $request = $app->request;

        Event::trigger('addons_begin', $request);

        if (empty($addon) || empty($controller) || empty($action)) {
			self::error(lang('addon can not be empty'));
            throw new HttpException(500, lang('addon can not be empty'));
        }
		

		//检测后台操作，管理员权限
		if (strpos($controller,'admin.') !== false && intval($app->session->get('user.id', 0)) === 0) {
			self::error(lang('Please login first'));
            throw new HttpException(500, lang('Please login first'));
        }
	

		//检测插件安装状态
		$installed = check_addons_value($addon, 'status');
		if (empty($installed) || $installed == '0') {
			self::error(lang('You have no permission'));
			throw new HttpException(500, lang('You have no permission'));
		}


        $request->addon = $addon;
        // 设置当前请求的控制器、操作
        $request->setController($controller)->setAction($action);

		//加载插件应用文件
		$appPath = $app->addons->getAddonsPath() . $addon . DIRECTORY_SEPARATOR;
        if (is_file($appPath . 'common.php')) {
            include_once $appPath . 'common.php';
        }

        // 获取插件基础信息
        $info = get_addons_info($addon);
        if (!$info) {
			self::error(lang('addon %s not found', [$addon]));
            throw new HttpException(404, lang('addon %s not found', [$addon]));
        }

        // 监听addon_module_init
        Event::trigger('addon_module_init', $request);
        $class = get_addons_class($addon, 'controller', $controller);
        if (!$class) {
			self::error(lang('addon controller %s not found', [Str::studly($controller)]));
            throw new HttpException(404, lang('addon controller %s not found', [Str::studly($controller)]));
        }

        // 重写视图基础路径
        $config = Config::get('view');
        $config['view_path'] = $app->addons->getAddonsPath() . $addon . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        Config::set($config, 'view');

        // 生成控制器对象
        $instance = new $class($app);
        $vars = [];
        if (is_callable([$instance, $action])) {
            // 执行操作方法
            $call = [$instance, $action];
        } elseif (is_callable([$instance, '_empty'])) {
            // 空操作
            $call = [$instance, '_empty'];
            $vars = [$action];
        } else {
            // 操作不存在
			self::error(lang('addon action %s not found', [get_class($instance).'->'.$action.'()']));
            throw new HttpException(404, lang('addon action %s not found', [get_class($instance).'->'.$action.'()']));
        }
        Event::trigger('addons_action_begin', $call);

        return call_user_func_array($call, $vars);
    }



   /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  mixed     $data 返回的数据
     * @param  integer   $wait 跳转等待时间
     * @param  array     $header 发送的Header信息
     * @return void
     */
    protected static function error($msg = '操作失败！',  $url = null, $data = '', int $code = 0, int $wait = 3, array $header = [])
    {

		$app = app();
        $request = $app->request;

        if (is_null($url)) {
            $url = $request->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ($url) {
			$url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string)$app->route->buildUrl($url);
        }
		
		if(strpos($url,'adx.php') !== false){ 
			$url = $url.'#'.$_SERVER["REQUEST_URI"];
		}

        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];

        $type = ($request->isJson() || $request->isAjax()) ? 'json' : 'html';
        if ($type == 'html'){
			$response = Response::create(config('app.dispatch_error_tmpl'), 'view')->assign($result)->header($header);
        } else {
			$response = Response::create($result, $type)->header($header);
        }
        throw new HttpResponseException($response);
    }

}