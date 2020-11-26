<?php

namespace Selvi;
use Selvi\Controller;
use Selvi\Database\Manager as Database;

class Model extends Controller {

    protected $db;
    protected $schema = 'default';
    protected $table = '';
    protected $primary = 'id';
    protected $increment = false;
    protected $selectable = null;
    protected $searchable = [];
    protected $join = [];
    protected $group = [];

    function __construct() {
        $this->db = Database::get($this->schema);
    }

    function getSchema() {
        return $this->db;
    }

    function getTable() {
        return $this->table;
    }

    function getPrimary() {
        return $this->primary;
    }

    function buildWhere($where) {
        foreach($where as $index => $w) {
            if(!strpos($w[0], '.')) {
                $where[$index][0] = $this->getTable().'.'.$where[$index][0];
            }
        }
        return $where;
    }

    function buildSearchable($q = null) {
        if($q !== null) {
            $where = $this->searchable;
            foreach($this->searchable as $index => $field) {
                if(!strpos($field, '.')) {
                    $where[] = [$this->getTable().'.'.$field, 'LIKE', '%'.$q.'%'];
                } else {
                    $where[] = [$field, 'LIKE', '%'.$q.'%'];
                }
            }
            return $where;
        } else {
            return [];
        }
    }

    function buildSort($order) {
        $sort = [];
        foreach($order as $k => $v) {
            if(!strpos($k, '.')) {
                $sort[$this->table.'.'.$k] = $v;
            } else {
                $sort[$k] = $v;
            }
        }
        return $sort;
    }

    function count($where = [], $q = null) {
        $query = $this->db->select('COUNT('.$this->table.'.'.$this->primary.') AS jumlah');
        if(is_callable($where)) {
            $where($query);
        } else {
            $this->db->where($this->buildWhere($where));
        }
        $query->orWhere($this->buildSearchable($q));
        foreach($this->join as $key => $value) {
            if($key == 'inner') {
                $query->innerJoin($value);
            }
        }
        $row = $query->get($this->table)->row();
        return $row->jumlah ?? 0;
    }

    function result($where = [], $q = null, $order = [], $limit = -1, $offset = 0) {
        $query = $this->db->select($this->selectable)->join($this->join)->order($this->buildSort($sort))->groupBy($this->group);
        if(is_callable($where)) { 
            $where($query);
        } else { 
            $query->where($this->buildWhere($where));
        }
        if($limit > -1) {
            $query->limit($limit)->offset($offset);
        }
        return $query->get($this->table)->result();
    }

    function row($where = []) {
        $query = $this->db->select($this->selectable)->join($this->join)->groupBy($this->group)->get($this->table);
        if(is_callable($where)) {
            $where($query);
        } else {
            $query->where($this->buildWhere($where));
        }
        return $query->row();
    }

    function insert($data) {
        if($this->db->insert($this->table, $data)) {
            if($this->increment) {
                return $this->db->getlastid();
            }
            return $data[$this->primary];
        }
        return false;
    }

    function update($where, $data) {
        $query = $this->db->where([]);
        if(is_callable($where)) {
            $where($query);
        } else {
            $query->where($this->buildWhere($where));
        }
        return $query->update($this->table, $data);
    }

    function delete($where) {
        $query = $this->db->join($this->join);
        if(is_callable($where)) {
            $where($query);
        } else {
            $query->where($this->buildWhere($where));
        }
        return $query->delete($this->table);
    }

}