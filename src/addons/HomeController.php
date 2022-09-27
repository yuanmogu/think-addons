<?php
/**
 * +----------------------------------------------------------------------
 * | think-addons [thinkphp6]
 * +----------------------------------------------------------------------
 */
declare(strict_types=1);

namespace think\addons;

use stdClass;
use think\Response;
use think\exception\HttpResponseException;

use think\addons;

class HomeController  extends addons
{
 
    // 初始化
    protected function initialize()
    {
		$this->view->config([
            'tpl_replace_string'=>[
               '__addons__' => '/static/addons/'.$this->name.'/'
            ]
        ]);
	
	}



    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  mixed     $data 返回的数据
     * @param  integer   $wait 跳转等待时间
     * @param  array     $header 发送的Header信息
     * @return void
     */
    protected function success($msg = '操作成功！', string $url = null, $data = '',  int $code = 1, int $wait = 3, array $header = [])
    {
		
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        } elseif ($url) {
			$url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string)$this->app->route->buildUrl($url);
        }
		
	
        $result = [
            'code'  => $code,
            'msg'   => $msg,
			'info'   => $msg,
            'data'  => $data,
            'url'   => $url,
            'wait'  => $wait,
        ];

		$type = ($this->request->isJson() || $this->request->isAjax()) ? 'json' : 'html';

        if (strtolower($type) == 'html'){
			$response = Response::create(config('app.dispatch_success_tmpl'), 'view')->assign($result)->header($header);
        } else {
			$response = Response::create($result, $type)->header($header);
        }
        
        throw new HttpResponseException($response);
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
    protected function error($msg = '操作失败！',  $url = null, $data = '', int $code = 0, int $wait = 3, array $header = [])
    {
        if (is_null($url)) {
            $url = $this->request->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ($url) {
			$url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string)$this->app->route->buildUrl($url);
        }
		

        $result = [
            'code' => $code,
            'msg'  => $msg,
			'info'   => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];

        $type = ($this->request->isJson() || $this->request->isAjax()) ? 'json' : 'html';
        if ($type == 'html'){
			$response = Response::create(config('app.dispatch_error_tmpl'), 'view')->assign($result)->header($header);
        } else {
			$response = Response::create($result, $type)->header($header);
        }
        throw new HttpResponseException($response);
    }



}
