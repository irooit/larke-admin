<?php

namespace Larke\Admin\Controller;

use Illuminate\Http\Request;

use Larke\Admin\Model\AdminLog as AdminLogModel;

/**
 * 操作日志
 *
 * @title 操作日志
 * @desc 操作日志管理
 * @order 109
 * @auth true
 *
 * @create 2020-10-23
 * @author deatil
 */
class AdminLog extends Base
{
    /**
     * 列表
     *
     * @title 日志列表
     * @desc 操作日志全部列表
     * @order 1091
     * @auth true
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        $start = (int) $request->get('start', 0);
        $limit = (int) $request->get('limit', 10);
        
        $order = $this->formatOrderBy($request->get('order', 'ASC'));
        
        $searchword = $request->get('searchword', '');
        $orWheres = [];
        if (! empty($searchword)) {
            $orWheres = [
                ['admin_name', 'like', '%'.$searchword.'%'],
                ['url', 'like', '%'.$searchword.'%'],
                ['method', 'like', '%'.$searchword.'%'],
            ];
        }

        $wheres = [];
        
        $startTime = $this->formatDate($request->get('start_time'));
        if ($startTime !== false) {
            $wheres[] = ['create_time', '>=', $startTime];
        }
        
        $endTime = $this->formatDate($request->get('end_time'));
        if ($endTime !== false) {
            $wheres[] = ['create_time', '<=', $endTime];
        }
        
        $status = $this->switchStatus($request->get('status'));
        if ($status !== false) {
            $wheres[] = ['status', $status];
        }
        
        $method = $request->get('method');
        if (!empty($method)) {
            $wheres[] = ['method', $method];
        }
        
        $query = AdminLogModel::orWheres($orWheres)
            ->wheres($wheres);
        
        $total = $query->count(); 
        $list = $query
            ->offset($start)
            ->limit($limit)
            ->withCertain('admin', ['name', 'nickname', 'email', 'avatar', 'last_active', 'last_ip'])
            ->orderBy('create_time', $order)
            ->get()
            ->toArray(); 
        
        return $this->success(__('获取成功'), [
            'start' => $start,
            'limit' => $limit,
            'total' => $total,
            'list' => $list,
        ]);
    }
    
    /**
     * 详情
     *
     * @title 日志详情
     * @desc 操作日志详情
     * @order 1092
     * @auth true
     *
     * @param string $id
     * @return Response
     */
    public function detail(string $id)
    {
        if (empty($id)) {
            return $this->error(__('日志ID不能为空'));
        }
        
        $info = AdminLogModel::where(['id' => $id])
            ->withCertain('admin', ['name', 'email', 'avatar', 'last_active', 'last_ip'])
            ->first();
        if (empty($info)) {
            return $this->error(__('日志信息不存在'));
        }
        
        return $this->success(__('获取成功'), $info);
    }
    
    /**
     * 删除
     *
     * @title 日志删除
     * @desc 操作日志删除
     * @order 1093
     * @auth true
     *
     * @param string $id
     * @return Response
     */
    public function delete(string $id)
    {
        if (empty($id)) {
            return $this->error(__('日志ID不能为空'));
        }
        
        $info = AdminLogModel::where('id', $id)
            ->first();
        if (empty($info)) {
            return $this->error(__('日志信息不存在'));
        }
        
        $deleteStatus = $info->delete();
        if ($deleteStatus === false) {
            return $this->error(__('日志删除失败'));
        }
        
        return $this->success(__('日志删除成功'));
    }
    
    /**
     * 清空一个月前的操作日志|清空特定ID日志
     *
     * @title 清空日志
     * @desc 清空操作日志
     * @order 1094
     * @auth true
     *
     * @param  Request  $request
     * @return Response
     */
    public function clear(Request $request)
    {
        $ids = $request->get('ids');
        if (! empty($ids)) {
            $ids = explode(',', $ids);
            $status = AdminLogModel::whereIn('id', $ids)->delete();
        } else {
            $status = AdminLogModel::where('create_time', '<=', time() - (86400 * 30))
                ->delete();
        }
        
        if ($status === false) {
            return $this->error(__('日志清空失败'));
        }
        
        return $this->success(__('日志清空成功'));
    }
    
}