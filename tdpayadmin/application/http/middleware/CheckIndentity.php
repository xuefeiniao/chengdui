<?php

namespace app\http\middleware;

class CheckIndentity
{
    public function handle($request, \Closure $next)
    {
    	/* 验证身份 */
    	/*if (!($token = $request->header('Token')) || strpos($token, '.') === false) 
    	{
    		return json(['code' => 1,'mes' => '请先登录']);
    	}
    	$user = Db::name('user')->where('user_token',$token)->find();
    	if(!$user) return json(['code' => 1, 'mes' => '请先登录']);
    	$request->user = $user;
    	return $next($request);*/

    	if(!$request->has('time','post') || !$request->has('sign','post')) return json_error('非法请求');
    	$time 	= $request->param('time');
    	$sign 	= $request->param('sign');
    	$signn 	= substr(md5($time.'hujin@123'), 0, 18);
    	if ($sign != $signn) return json_error('非法请求');
    	return $next($request);
    }
}
