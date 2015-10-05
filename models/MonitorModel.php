<?php

class MonitorModel extends CModel{
    
    public function getDepots() {
        
        $query  = 'select distinct department from person order by 1';
        $result = $this->select($query);
        
        return array_column($result['data'], 'department');
    }
    
    public function getActions($params) {
        
        
        $query = "
        select 
                p.tabnum,  p.department,
                concat_ws(' ', p.lname, p.fname, p.pname) fio,
                if (e.ev_type = 2, 'ВХОД', 'ВЫХОД') evt,
                e.ev_date, e.ev_time
        from events e 
        left join person p on e.person_id = p.id
        where 1 = 1
            and e.ev_date between :bdate and :edate
            and e.ev_time between :btime and :etime
            and p.lname like :lname
            and p.fname like :fname
            and p.pname like :pname
            and p.tabnum like :tabn
        order by e.ev_date, e.ev_time
        limit 50";
        
        $params['lname'] = toLike(get_param($params, 'lname', ''));
        $params['fname'] = toLike(get_param($params, 'fname', ''));
        $params['pname'] = toLike(get_param($params, 'pname', ''));
        $params['tabn' ] = toLike(get_param($params, 'tabn',  ''));
        
        //var_dump($params);
        //and p.department in ('', '')
        
        $data = $this->select($query, $params);
        
        return $data;
        //return $param;
    }
    
}