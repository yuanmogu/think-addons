<?php
namespace think\addons;

use think\facade\Db;
use think\Exception;
use libs\DirExtend;


/**
 * 插件构造函数
 * Addons constructor.
 */
class Manager
{

	/**
     * 安装插件
     *
     * @param $name  插件名称
     * @param $force 是否覆盖
     *
     * @return void
     */
	public static function install($name, $force = false)
	{
		try {
			// 检查插件是否完整
			self::check($name);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		// 复制静态资源
		$sourceAssetsDir = self::getSourceAssetsDir($name);
		$destAssetsDir = self::getDestAssetsDir($name);
		if (is_dir($sourceAssetsDir)) {
			DirExtend::copyDir($sourceAssetsDir, $destAssetsDir);
		}

		Db::startTrans();
		try {
			// 执行安装脚本
			$obj = get_addons_instance($name);

			if (!empty($obj) && method_exists($obj,'install')) {
				// 调用插件安装 
                $obj->install();
            }

			self::runSQL($name);

			self::setMenu($name);

			// 提交事务
			Db::commit();
		} catch (\Exception $e) {
			// 回滚事务
			Db::rollback();
			throw new Exception($e->getMessage());
		}


		return true;
	}
	/**
     * 卸载插件.
     *
     * @param string  $name
     * @param boolean $force 是否强制卸载
     *
     * @return bool
     * @throws Exception
     */
	public static function uninstall($name, $force = false)
	{
		$addons_path = app()->addons->getAddonsPath();
		if (empty($name) || !is_dir($addons_path . $name)) {
			throw new Exception('插件不存在！');
		}
		//移除插件基础资源目录
		$destAssetsDir = self::getDestAssetsDir($name);
		if (is_dir($destAssetsDir)) {
			DirExtend::delDir($destAssetsDir);
		}

		Db::startTrans();
		try {

			// 执行卸载脚本
			$obj = get_addons_instance($name);
			
			// 调用插件卸载
			if (!empty($obj) && method_exists($obj,'uninstall')) {
                $obj->uninstall();
            }

			self::runSQL($name,'uninstall');

			self::setMenu($name,'uninstall');

			// 提交事务
			Db::commit();

		} catch (\Exception $e) {
			// 回滚事务
			Db::rollback();
			throw new Exception($e->getMessage());
		}

		return true;
	}
	/**
     * 执行安装数据库脚本
     *
     * @param type $name 模块名(目录名)
     *
     * @return boolean
     */
	public static function runSQL($name = '', $Dir = 'install')
	{
		$addons_path = app()->addons->getAddonsPath();
		$sql_file = $addons_path . "{$name}" . DIRECTORY_SEPARATOR . "{$Dir}.sql";
		if (file_exists($sql_file)) {
			$sqlTxt = file_get_contents($sql_file);
			if (!empty($sqlTxt)) {
				$sqlTxt = str_ireplace('__PREFIX__', config('database.prefix'), $sqlTxt);
			
				$sqlFormat = str_replace(PHP_EOL,'',$sqlTxt);
				$sqlRecords = explode(";", $sqlFormat);
				
				try {
					foreach($sqlRecords as $sql){
						if(empty($sql)) continue;
						Db::execute($sql);
					}
				} catch (\Exception $e) {				
					throw new Exception('执行SQL失败：'.$e->getMessage());
				}
			}
		}
		return true;
	}


	/**
     * 执行添加/删除插件菜单
     *
     * @param type $name 模块名(目录名)
     *
     * @return boolean
     */
	public static function setMenu($name = '', $action = 'install')
	{
		$addons_admin_path = app()->addons->getAddonsPath(). "{$name}" . DIRECTORY_SEPARATOR . "controller" . DIRECTORY_SEPARATOR . 'admin';

		if (is_dir($addons_admin_path)) {
			if ($action == 'install') {
				try {
					$info = get_addons_info($name);
					$data = [];
					$data['pid'] = 21;
					$data['title'] = $info['title'];
					$data['url'] = '@addons/'.$name.'/admin.index/index';
					$data['node'] = 'addons.'.$name;
					Db::name('system_menu')->insert($data);

				} catch (\Exception $e) {
					throw new Exception('添加管理菜单失败');
				}
			}
			if ($action == 'uninstall') {
				Db::table('system_menu')->where('node','addons.'.$name)->delete();
			}
		}
		return true;
	}



	/**
     * 检测插件是否完整.
     *
     * @param string $name 插件名称
     *
     * @return bool
     * @throws Exception
     */
	public static function check($name)
	{
		$addons_path = app()->addons->getAddonsPath();

		if (empty($name) || !is_dir($addons_path . $name)) {
			throw new Exception('插件不存在！');
		}
		$addonClass = get_addons_class($name);

		if (!$addonClass) {
			throw new Exception('插件主启动程序不存在');
		}
		return true;
	}

	/**
     * 获取插件源资源文件夹
     *
     * @param string $name 插件名称
     *
     * @return  string
     */
	protected static function getSourceAssetsDir($name)
	{
		$addons_path = app()->addons->getAddonsPath();
		return $addons_path . $name . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
	}

	/**
     * 获取插件目标资源文件夹
     *
     * @param string $name 插件名称
     *
     * @return  string
     */
	protected static function getDestAssetsDir($name)
	{
		$assetsDir = public_path() . str_replace("/", DIRECTORY_SEPARATOR, "static/addons/{$name}/");
		if (!is_dir($assetsDir)) {
			mkdir($assetsDir, 0755, true);
		}
		return $assetsDir;
	}

}