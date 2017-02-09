<?php
/**
 *套餐控制器
 */

namespace app\admin\controller;

use think\Config;
use think\Controller;
use think\Db;
use think\Request;

class Combo extends Common{
    function _initialize(){
        parent::_initialize();
        //菜品分类
        $cats = Db::table('SCL')->field('ID,Nm')->select();
        $this->assign('cats',$cats);
        //全部菜品
        $dishes = Db::table('SCD')->field('ID,Nm,Ns')->select();
        $this->assign('dishes',$dishes);
    }
    /*
     * 添加菜品
     */
    public function add()
    {
        //菜单分类
        $cat = Db::table('SCL')->order('ID asc')->select();
        $cid = $cat[0]['ID'];

        if (request()->isPost()) {
            //修改处理
            $params = input('post.');
            if (isset($params['pic_url'])) {
                $params['pic'] = implode('|',$params['pic_url']);
                unset($params['pic_url']);
            }else{
                $params['pic'] = '';
            }
            if (isset($params['Nt'])) {
                $params['Nt'] = implode(',',$params['Nt']);
            }else{
                $params['Nt'] = $cid;
            }
            $params['Nm'] = $params['Nm']?$params['Nm']:'未命名';
            $params['No'] = $params['No']?$params['No']:99;
            $data = [
                'Nm' => $params['Nm'],
                'Nt' =>  ','.$params['Nt'].',',
                'No' => $params['No'],
                'Ni' => $params['Ni'],
                'pic' => $params['pic'],
                'Ns' =>$params['Ns']*100,
                'Nh' => $params['Nh'],
                'Np'=>0,
                'DD'=>0
            ];

            if (isset($params['ID'])) {
                $flag = Db::table('SCD')->where('id',$params['ID'])->update($data);
                if ($flag) {
                    exit(json_encode(['status' => 1, 'msg' => '修改成功', 'url' => url('menu/index')]));
                } else {
                    exit(json_encode(['status' => 0, 'msg' => '修改失败,请稍后重试', 'url' => '']));
                }
            }else{
                //新增
                $flag=Db::table('SCD')->insert($data);
                if ($flag) {
                    exit(json_encode(['status' => 1, 'msg' => '添加成功', 'url' => url('menu/index')]));
                } else {
                    exit(json_encode(['status' => 0, 'msg' => '添加失败,请稍后重试', 'url' => '']));
                }
            }

        } else {
            return $this->fetch();
        }
    }
    function edit1(){
        return $this->fetch('edit');
    }
    function edit($id){
        $data = Db::table('SCD')->where(['id'=>$id])->find($id);
        $this->assign('data',$data);
        $this->assign('item',$data);
        return $this->fetch();
    }
}