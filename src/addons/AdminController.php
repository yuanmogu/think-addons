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

class AdminController  extends addons
{
 

    // 初始化
    protected function initialize()
    {
		if(intval($this->app->session->get('user.id', 0)) === 0){
			$this->error('请先登录后再操作');
		}
	
	}

    /**
     * 返回失败的操作
     * @param mixed $info 消息内容
     * @param mixed $data 返回数据
     * @param mixed $code 返回代码
     */
    public function error($info, $data = '{-null-}', $code = 0): void
    {
        if ($data === '{-null-}') $data = new stdClass();
        throw new HttpResponseException(json([
            'code' => $code, 'info' => $info, 'data' => $data,
        ]));
    }

    /**
     * 返回成功的操作
     * @param mixed $info 消息内容
     * @param mixed $data 返回数据
     * @param mixed $code 返回代码
     */
    public function success($info, $data = '{-null-}', $code = 1): void
    {
        if ($data === '{-null-}') $data = new stdClass();
        throw new HttpResponseException(json([
            'code' => $code, 'info' => $info, 'data' => $data,
        ]));
    }




}
