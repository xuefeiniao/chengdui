<?php

namespace app\admincoin\controller;

use think\Controller;
use think\Request;
use app\admincoin\model\ArticleClasss;
use app\admincoin\model\Article;
use app\admincoin\validate\ArticleClasssValidate;

class MessageManage extends Base
{
    /**
     * 系统公告
     *
     * @return \think\Response
     */
    public function notice()
    {
        return view('gonggao_set');
    }

    /**
     * 文章列表.
     *
     * @return \think\Response
     */
    public function information()
    {
        $list   = Article::order('sort','desc')->paginate(5);
        $this->assign('list',$list);
        $this->assign('count',$list->total());
        return view('zixun_set');
    }

    /**
     * 添加文章.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function addArticle(Request $request)
    {
        if ($request->isPost()) 
        {
            
        }
        else
        {
            $classs     = ArticleClasss::order('sort','desc')->column('class_name','class_id');
            $this->assign('class',$classs);
            return view('zixun_add');
        }
    }

    /**
     * 编辑文章.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function editArticle(Request $request)
    {
        if ($request->isPost()) 
        {
            $param  = $request->param('');
            $n      = Article::update($param);
            if ($n) return json_success('更新成功'); else return json_error('更新失败');
        }
        else
        {
            $aInfo  = Article::get($request->id);
            $aType  = ArticleClasss::column('class_name','class_id');
            $this->assign('aInfo',$aInfo);
            $this->assign('aType',$aType);
            return view('zixun_edit');
        }
    }

    /**
     * 编辑文章.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delArticle($id)
    {
        $n = Article::destroy($id);
        if ($n) return json_success('删除成功'); else return json_error('删除失败');
    }

    /**
     * 分类管理
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function class(Request $request)
    {
        if ($request->isPost())
        {
            $data       = $request->param();
            $validate   = new ArticleClasssValidate();
            if (!$validate->check($data)) return json_error($validate->getError());
            $data['create_time'] = date('Y-m-d H:i:s',time());
            $n          = ArticleClasss::create($data);
            if ($n) return json_success('添加成功'); else return json_error('添加失败');
        }
        else
        {
            $list   = ArticleClasss::order('sort','asc')->paginate(4);
            $this->assign('list',$list);
            return view('fenlei_set');
        }
    }

    /**
     * 编辑分类
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function editClass(Request $request)
    {
        if ($request->isPost()) 
        {
            $param  = $request->param();
            $validate = new ArticleClasssValidate();
            if (!$validate->check($param)) return json_error($validate->getError());
            $param['update_time'] = date('Y-m-d H:i:s',time());
            $n = ArticleClasss::update(['class_name' => $param['class_name'], 'sort' => $param['sort']], ['class_id' => $param['id']]);
            // $n = ArticleClasss::update(['class_name' => $param->class_name, 'sort' => $param->sort], ['class_id' => $param->id]);
            if ($n) return json_success('编辑成功'); else return json_error('编辑失败');
        }
        return json_error('非法请求');
    }

    /**
     * 删除分类
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delClass($id)
    {
        $n  = ArticleClasss::destroy($id);
        if ($n) return json_success('删除成功'); else return json_error('删除失败');
    }

    

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    
}
