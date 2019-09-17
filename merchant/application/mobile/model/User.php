<?php
/**
 * Created by PhpStorm.
 * User: Zhang
 * Date: 2019/3/19
 * Time: 10:31
 */

namespace app\mobile\model;
use think\Model;

class User extends Model
{
    protected $field=true;
    protected $autoWriteTimestamp=true;
    protected $createTime="createtime";
    protected $updateTime="updatetime";

    /**
     * 商户ID,APIKEY,回调密码生成
     */
    public static function random($title,$len,$type){
        while(true){
            if($type==1){
                $chars = "0123456789";
            }else if($type==2){
                $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            }else if($type==3){
                $chars = "0123456789";
            }
            mt_srand(10000000*(double)microtime());
            for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
                $str .= $chars[mt_rand(0, $lc)];
            }
            if($type==1){
                $res=db("user")->where("shopid",$str)->value("id");
            }else if($type==2){
                $res=db("user")->where("apikey",$str)->value("id");
            }else if($type==3){
                $res=db("user")->where("apipassword",$str)->value("id");
            }
            if(empty($res) && (!empty($str))) break;
            $str="";
        }
        return $title.$str;
    }

}