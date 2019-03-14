<?php



//根据某个id获取与它相关联的其他id数组   可以得到一个id数组，并且自动转化为一维
$data_propartment= BureauPropartment::query()
    ->where('bureau_id', $bureau_id)
    ->select('id')
    ->get()
    ->pluck('id')
    ->toArray();

//根据某个id数组，查询某表内与它相关联的id数组，并且转化为一维数组
$data_propartment_x_project= BureauPropartmentXProject::query()
    ->whereIn('bureau_propartment_id', $data_propartment)
    ->select('project_id')
    ->get()
    ->pluck('project_id')
    ->toArray();

//一维数组，统计每个值出现的次数
$data=array_count_values($data_train);

//根据项目数组获取机具的id数组==》获取每个id对应的类别数组
$data_moving_machine = MovingMachine::query()
        ->whereIn('project_id', $res)
        ->select('id','type')
        ->get()
        ->pluck('type','id')
        ->toArray();
    $data_moving_machine=array_count_values($data_moving_machine);
	$train['sz_nums']   = $data_moving_machine['1'] ?? 0;//石渣机具数量
	$train['zbpb_nums'] = $data_moving_machine['2'] ?? 0;//自备平板机具数量
	$train['lypb_nums'] = $data_moving_machine['3'] ?? 0;//路用平板机具数量
	$train['yypb_nums'] = $data_moving_machine['4'] ?? 0;//运用平板机具数量



//删除数组的第一个元素并返回值
array_shift($path_arr);



//当使用关联模型查询时
  $coll = $coll
            ->with('engineering')
            ->with('construction_type')
            ->orderBy('id')
            ->paginate($request->page_size ?? 10);
        
        list($data,$total) = ArrLib::listDataTotal($coll);
        // dd($data);
        //获取关联模型内想要获取的字段，并且命名
        ArrLib::newBeeMethod($data,1,'engineering.title', 'engineering.eng_type_id', 'construction_type.name');
    	
    	///对数组内的某些字段进行转化重命名
        ArrLib::renameColumn($data, 'engineering_title->engineeringTitle');

        // 用于上面调用
		public static function newBeeMethod(&$coll, $is_need_unset, ...$column_paths)
		    {
		        if (!is_array($coll)) {
		            $coll = $coll->toArray();
		        }
		        if ($coll == []) {
		            return;
		        }
		        if (isset($coll[0])) {
		            //二维数组
		            foreach ($coll as &$obj) {
		                foreach ($column_paths as $path) {
		                    self::newBeeAppend($path, $obj);
		                }
		            }
		            if ($is_need_unset) {
		                foreach ($coll as &$obj) {
		                    foreach ($column_paths as $path) {
		                        self::newBeeUnset($path, $obj);
		                    }
		                }
		            }
		        } else {
		            //一维数组
		            foreach ($column_paths as $path) {
		                self::newBeeAppend($path, $coll);
		            }
		            if ($is_need_unset) {
		                foreach ($column_paths as $path) {
		                    self::newBeeUnset($path, $coll);
		                }
		            }
		        }
		    }

		    //用于上面调用
		    protected static function newBeeAppend($path, &$obj)
			    {
			        $path_arr = explode('.', $path);
			        $obj[$path_arr[count($path_arr) - 2] . '_' . $path_arr[count($path_arr) - 1]] = self::dataGet($obj, $path_arr);
			    }
			//用于上面调用   
			    protected static function newBeeUnset($path, &$obj)
			    {
			        $path_arr = explode('.', $path);
			        unset($obj[$path_arr[0]]);
			    }
			//用于上面调用    
			    public static function dataGet($obj, $path_arr)
			    {
			        $temp = $obj[$path_arr[0]];
			        if (count($path_arr) == 1) {
			            return $temp;
			        } else {
			            array_shift($path_arr);
			            return self::dataGet($temp, $path_arr);
			        }
			    }



			///用于重命名调用
			 /**
		     * @param $coll
		     * @param mixed ...$from_to a->b
		     */
		    public static function renameColumn(&$coll, ...$from_to)
		    {
		        if ($coll == []) {
		            return;
		        }
		        if (isset($coll[0])) {
		            foreach ($coll as &$obj) {
		                foreach ($from_to as $ft) {
		                    self::rename1($ft, $obj);
		                }
		            }
		        } else {
		            foreach ($from_to as $ft) {
		                self::rename1($ft, $coll);
		            }
		        }
		    }
		    
		    protected static function rename1($ft, &$obj)
		    {
		        $arr = explode('->', $ft);
		        $from = $arr[0];
		        $to = $arr[1];
		        $obj[$to] = $obj[$from];
		        unset($obj[$from]);
		    }



//控制器中连表查询，并且获取相应的字段值
public function get_list(Request $request)
    {
        try {
            $coll = AlarmRecord::query();
            
            if ($request->filled('project_id')) {
                $coll->where('alarm_record.project_id', $request->project_id);
            } elseif ($request->filled('bureau_id')) {
                $coll->whereIn('alarm_record.project_id', ProjectHelper::pidsByBureauId($request->bureau_id));
            } else {
                $coll->where('alarm_record.project_id', $this->getPID());
            }
            if ($request->filled('from_time')) {
                $coll->where('alarm_record.alarm_time', '>=', $request->from_time);
            }
            if ($request->filled('end_time')) {
                $coll->where('alarm_record.alarm_time', '<=', $request->end_time);
            }
            if ($request->filled('train_id')) {
                $coll->where('alarm_record.train_id', $request->train_id);
            }
            if ($request->filled('type')) {
                $coll->where('alarm_record.type', $request->type);
            }
            
            $coll = $coll
                ->leftJoin('train', 'alarm_record.train_id', 'train.id')
                ->leftJoin('bureau_user as user_train_leader', 'user_train_leader.id', 'train.train_leader_user_id')
                ->leftJoin('bureau_user as user_driver', 'user_driver.id', 'train.driver_user_id')
                ->leftJoin('track_line_sub', 'track_line_sub.id', 'alarm_record.track_line_sub_id')
                ->leftJoin('track_line', 'track_line.id', 'track_line_sub.track_line_id')
                ->orderBy('alarm_record.created_at', 'desc')
                ->select(
                    'alarm_record.*',
                    'train.name as train_name',
                    'user_train_leader.nickname as train_leader_nickname',
                    'user_driver.nickname as user_driver_nickname',
                    'track_line.name as line_nickname'
                )
                ->paginate($request->page_size ?? 10)
                ->toArray();
            
            list($data, $total) = ArrLib::listDataTotal($coll);
            
            foreach ($data as &$datum) {
                $datum['transType'] = AlarmRecord::transType($datum['type']);
            }
            
            return getJson(0, null, $data, $total > 500 ? 500 : $total);
        } catch (\Exception $e) {
            return getJson(-100, $e->getMessage());
        }
    }






// laravel采用自定义分页的方法查询
     Model::offset(a)->limit(b)->get();
     b=$page_size;==>每页多少条
     a=($page-1)*$page_size;==>从多少条开始查询
     Model::offset(a)->take(b)->get();

     // 封装类似的也可以
	public static function skipTake(&$query, $page, $page_size = 10)
    {
        $query->skip(($page - 1) * $page_size)
            ->take($page_size);
    }



//laravel中 查询时 使用 or语句
if ($request->filled('train_status')) {
                if ($request->train_status == 0) {
                    //没有返回车次的, 出发到达时间为空
                    //有返回的, 返回到达时间为空
                    $sql->whereRaw("((back_num='' and actual_train_end_time is null) or
                        (back_num!='' and actual_train_return_stop_time is null))");
                } else {
                    //没有返回车次的, 出发到达时间不为空
                    //有返回的, 返回到达时间不为空
                    $sql->whereRaw("((back_num='' and actual_train_end_time is not null) or
                        (back_num!='' and actual_train_return_stop_time is not null))");
                }
            }
//形参一一对应
            ->whereRaw('(go_benwu_train_id = ? or back_benwu_train_id = ?)', [$request->train_id, $request->train_id])
                ->whereRaw("(
                    plan_train_stop_time >= ?
                    or actual_train_end_time is null
                    or (back_num != '' and (plan_train_return_stop_time >= ? or actual_train_return_stop_time is null))
                )", [$start_time, $start_time])


// 联合查询 以及获取相应的值
->leftJoin('track_line as go_track_line', 'go_track_line.id', 'construction_plan_4.go_track_line_id')
                ->leftJoin('track_line as back_track_line', 'back_track_line.id', 'construction_plan_4.back_track_line_id')
                ->leftJoin('track_line', 'track_line.id', 'construction_plan_4.track_line_id')
                ->leftJoin('construction_progress', 'construction_progress.id', 'construction_plan_4.work_id')
                ->select(
                    'construction_plan_4.*',
                    'construction_plan_workitem.name as workitem_name',
                    'construction_plan_worksequence.name as worksequence_name'
                )
                ->orderByDesc('actual_end_time')
                ->paginate($request->page_size ?? 10)
                ->toArray();


//使用关联模型查询时，当出现1对多时需要限定查询的字段  增快查询数据
    public function getWorkitem(Request $request)
    {
        $project_id = api_pid($request);
        $workitems = ConstructionPlanWorkitem::query()
            ->where('project_id', $project_id)
            ->with(['worksequence' => function($query) {
                return $query->select('id', 'name', 'construction_plan_workitem_id');
            }])
            ->select('id', 'name')
            ->get();
        
        foreach ($workitems as $workitem) {
            foreach ($workitem['worksequence'] as $item) {
                unset($item['construction_plan_workitem_id']);
            }
        }
        
        return getJson(0, null, $workitems, count($workitems));
    }

//with 关联模型内继续使用连表查询
//获取施工计划列表
    public function get_construction_progresses(Request $request)
    {
        try {
            //获取施工进度的类型
            $eid = api_eid($request);
            $pid = api_pid($request);
            
            $coll = ConstructionProgress::query();
            if ($eid) {
                $coll->whereIn('project_id', ProjectHelper::pidsByEid($eid));
            } else {
                $coll->where('project_id', $pid);
            }
            
            $coll = $coll
                ->with(['son' => function($query) {
                    return $query->leftJoin('track_line', 'track_line.id', 'construction_progress_son.track_line_id')
                        ->select(
                            'construction_progress_son.id',
                            'construction_progress_son.construction_progress_id',
                            'construction_progress_son.track_line_id',
                            'track_line.name as line_name'
                        );
                }])
                ->select('id', 'name', 'count_type', 'unit')
                ->get();
            
            return getJson(0, null, $coll, count($coll));
        } catch (\Exception $e) {
            return getJson(-100, $e->getMessage());
        }
    }

    



