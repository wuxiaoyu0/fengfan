<?php
namespace app\common\controller;
use think\Controller;
use think\Response;
use think\Db;
use think\Session;
use app\common\exception\TimeoutException;
use app\common\exception\UnauthorizedException;

class FengfanController extends Controller {
	// 不需要做session校验的地址
	protected $exceptUrls = [
		'api/users/add',
		'api/users/signin',
		'api/users/forgotpwd',
		'api/users/resetpwd',
		'api/users/hassignin',
		'api/users/signout'
	];

    protected $beforeActionList = [
    	'userCheck',
    	'authorizedCheck',
    ];

	protected function authorizedCheck() {
		$urlpath = request()->path();
		// 只要不在$this->exceptUrls数组中的url地址都应该进行权限验证。
		if(!in_array($urlpath, $this->exceptUrls)) {
			// 管理员无需校验API权限
			if(Session::get("roles") == "0") {
				return;
			}

			$rolesConfig = config('roles.'. Session::get("roles"));
			// 如果不在权限列表中，则抛出没有权限的异常
			if(!in_array($urlpath, $rolesConfig)) {
				throw new UnauthorizedException();
			}
		}
	}

	protected function userCheck() {
		$urlpath = request()->path();
		if(!in_array($urlpath, $this->exceptUrls) && config("session_check")) {
			// 如果session不存在
			if(empty(Session::get('username'))) {
				throw new TimeoutException();
			}
		}
	}

	// 使用正则表达式对target进行检查.
	protected function isMatchInArray($target, $checkArray) {
		foreach ($checkArray as $pattern) {
			$checkRst = preg_match($pattern, $target) ? true : false;
			if($checkRst) {
				return true;
			}
		}
		return false;
	}

	protected function uid() {
		return Session::get('uid');
	}

	protected function corsjson($data) {
		// response()->header("Access-Control-Allow-Origin", "*");
		$header = ["Access-Control-Allow-Origin" => "*"];
		return Response::create($data, "json", 200, $header);
	}
	
	protected function requiredCheck($checkFilds) {
    	$result =  [
    		"errcode"=> 0, // 错误代码：[数值：必填] 0 无错误 -1 有错误
			"errmsg"=> "", // 错误信息：[字符串：默认为空]
		];

		foreach ($checkFilds as $key => $value) {
			if(!isset($value) || $value == "") {
				$result["errcode"] = -1;
				$result["errmsg"] = $key . "不能为空";
				return $result;
			}
		}

		return false;
	}

	// 添加用户浏览记录信息
	protected function addViewHistory($uid, $type, $visited_id) {
		$data = [
			'uid' => $uid,
			'type' => $type,
			'visited_id' => $visited_id
		];
		Db::table("visit_history")->insert($data);
	}

	// 添加收藏
	protected function addFavorite($uid, $type, $favorite_id) {
		$data = [
			'uid' => $uid,
			'type' => $type,
			'favorite_id' => $favorite_id
		];

		$rst = Db::table("favorite")->where($data)->find();
		// 检查是否已经收藏了
		if(!empty($rst)) {
			return "您已经收藏过了。";
		}

		return Db::table("favorite")->insert($data);
	}

	// 检查session
	public function Sessioncheck() {
		echo "session check";
		return false;
	}
}
