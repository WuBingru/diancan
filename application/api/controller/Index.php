<?php
namespace app\api\controller;

use think\Controller;
use think\Db;

class Index extends Controller
{
	//提交订单数据
    public function getOrder()
    {
        $post     = input('post.');
        $table_id = intval($post['table_id']);
        $open_id  = trim($post['open_id']);
        $items    = $post['items'];
        if (empty($table_id) || empty($open_id) || empty($items)) {
            return json(['status' => 0, 'info' => '提交失败', 'code' => 1, 'msg' => '必须字段不能为空','one'=>$table_id,'two'=>$open_id,'three'=>json_encode($items)]);
        }
        //查询是否存在此桌
        $re = Db::table('SCT')->where('ID',$table_id)->find();
        if(!$re) return json(['status'=>0,'info'=>'不存在此桌']);
        $time   = time();
        $f_time = date('Y-m-d H:i:s', $time);
        if (!is_array($items)) {
            return json(['status' => 0, 'info' => '提交失败', 'code' => 2, 'msg' => '数据格式错误']);
        }
        $db_error = ['status' => 0, 'info' => '提交失败', 'code' => 3, 'msg' => '数据库查询错误'];

        $total_price = 0;
        $h_detail = '';

        //插入订单表
        $oid = Db::table('ODR')->insertGetId(['Ti' => $table_id, 'St' => 3, 'Dt' => $f_time, 'Ds' => $f_time]);
        if(!$oid) return json($db_error);

        foreach ($items as $v) {
            $cnum = intval($v['cnum']);
            if($cnum <= 0) continue;
            $info = Db::table('SCD')->field('ID,Nm,Ns')->where('ID', $v['cid'])->find();//菜品表
            if ($info) {
                $total_price += $info['Ns'] * intval($cnum);
                //插入订单详细表
                Db::table('OCK')->insert(['Oi' => $oid, 'Ti' => $table_id, 'Ci' => $v['cid'], 'Cn' => $info['Nm'], 'Cp' => $info['Ns'], 'Cs' => $cnum, 'St' => 3, 'Dt' => $f_time]);
                //订单历史记录
                $h_detail .= $table_id.'|'.$info['Nm'].'|'.$info['Ns'].'|'.$cnum.'$';
            } else {
                return json($db_error);
            }
        }

        //更新订单表
        Db::table('ODR')->where('id',$oid)->update(['Pz'=>$total_price]);
        //更新桌号表
        Db::table('SCT')->where('ID',$table_id)->update(['Ni'=>1,'Oi'=>$oid]);
        //保存积分，1元=1积分
        $re = Db::name('member')->where('open_id',$open_id)->find();
        $total_price = $total_price/100;
        if(!$re) Db::name('member')->insert(['open_id'=>$open_id,'regtime'=>$time,'integrals'=>['exp','integrals+'.$total_price]]);
        else Db::name('member')->where('open_id',$open_id)->update(['integrals'=>['exp','integrals+'.$total_price]]);

        //插入订单历史记录
        $re = Db::name('ohistory')->insert(['open_id'=>$open_id,'detail'=>$h_detail,'posttime'=>$time,'total_price'=>$total_price]);
        if(!$re) return json($db_error);

        return json(['status'=>1,'info'=>'提交成功']);
    }
    //获取订单历史
    function getHistory(){
    	$open_id = input('post.open_id');
    	if(!$open_id) return json(['status'=>0,'info'=>'open_id不能为空']);
    	$list = Db::name('ohistory')->where('open_id',$open_id)->select();
    	$new_list = [];
    	if(!$list) return json(['status'=>0,'info'=>'查询无历史记录']);
    	foreach ($list as $v) {
    		$detail = rtrim($v['detail'],'$');
    		$cai = [];
    		$items = explode('$',$detail);
    		foreach ($items as $v2) {
    			$one = explode('|',$v2);
    			$data = ['name'=>$one[1],'price'=>($one[2]/100),'num'=>$one[3]];
    			$cai[] = $data;
    		}
    		$new_list[] = ['id'=>$v['id'],'posttime'=>date('Y-m-d H:i:s',$v['posttime']),'total_price'=>$v['total_price'],'items'=>$cai];
    	}
    	return json($new_list);
    }
    //获取用户信息
    function getUinfo(){
    	$open_id = input('post.open_id');
    	if(!$open_id) return json(['status'=>0,'info'=>'open_id不能为空']);
    	$list = Db::name('member')->where('open_id',$open_id)->find();
    	if(!$list) return json(['status'=>0,'info'=>'用户信息不存在']);
    	else return json($list);
    }
    function test_get_order(){
    	$url = 'http://dc.zhongda.com/api.php/Index/getOrder';
    	$items = [
    		['cid'=>10,'cnum'=>2],['cid'=>12,'cnum'=>2]
    	];
    	$re = curl_post($url,['table_id'=>3,'open_id'=>'test1','items'=>$items]);
    	echo $re;
    }
    function test(){
    	$url = 'http://dc.zhongda.com/api.php/Index/getHistory';
    	$re = curl_post($url,['open_id'=>'test']);
    	echo $re;
    }

}