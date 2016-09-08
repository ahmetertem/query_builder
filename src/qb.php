<?php

namespace ahmetertem;

/**
 * Query builder.
 */
class qb
{
    private $_conditions = array();
    private $_table_names = array();
    private $_read_fields = array();
    private $_write_fields = array();
    private $_write_values = array();
    private $_write_field_types = array();
    private $_order_fields = array();
    private $_group_fields = array();
    private $_joins = array();
    public $limit = 100;
    public $limit_offset = -1;
    public static $default_limit = 100;

    public function __construct()
    {
        $this->limit = self::$default_limit;
    }

    public function resetTable()
    {
        $this->_table_names = array();

        return true;
    }

    public function setTable($table_name)
    {
        $this->resetTable();
        $this->table($table_name);

        return $this;
    }

    public function table($table_name)
    {
        $this->_table_names[] = $table_name;

        return $this;
    }

    public function resetSet()
    {
        $this->_write_fields = array();
        $this->_write_values = array();
        $this->_write_field_types = array();

        return $this;
    }

    /**
     * $type :
     *        - 0 = string
     *        - 1 = integer
     *        - 2 = raw.
     */
    public function set($field, $value, $type = 0)
    {
        $this->_write_fields[] = $field;
        $this->_write_values[] = $value;
        $this->_write_field_types[] = $type;

        return $this;
    }

    public function resetWhere()
    {
        $this->_conditions = array();

        return $this;
    }

    public function where($field, $value = null, $operator = '=')
    {
        $this->_conditions[] = $this->c($field, $value, $operator);

        return $this;
    }

    /**
     * Adds "<strong><em>or</em></strong>" condition to current and condition
     * array.
     */
    public function whereOr()
    {
        $this->_conditions[] = '('.implode(' or ', func_get_args()).')';

        return $this;
    }

    public static function c($field, $value = null, $operator = '=')
    {
        if (is_null($value)) {
            return $field;
        } else {
            return "{$field} {$operator} {$value}";
        }
    }

    public function resetSelect()
    {
        $this->_read_fields = array();

        return $this;
    }

    public function select($field)
    {
        $this->_read_fields[] = $field;

        return $this;
    }

    public function groupBy($field)
    {
        $this->_group_fields[] = $field;

        return $this;
    }

    public function orderBy($field, $asc = true)
    {
        $this->_order_fields[$field] = array($field, $asc);

        return $this;
    }

    public function setLimit($limit, $limit_offset = -1)
    {
        $this->limit = $limit;
        if ($limit_offset != -1) {
            $this->limit_offset = $limit_offset;
        }

        return $this;
    }

    public function join($table_name, $on, $join_type = null)
    {
        $this->_joins[] = array('table' => $table_name, 'on' => $on, 'type' => $join_type);

        return $this;
    }

    public function getSelect()
    {
        if (count($this->_table_names) == 0) {
            throw new \Exception('Specify at least 1 table name');
        }

        //
        // selects
        //
        $_read_fields = $this->_read_fields;
        if (count($_read_fields) == 0) {
            $_read_fields[] = '*';
        }

        //
        // joins
        //
        $joins = null;
        foreach ($this->_joins as $join) {
            $joins .= $join['type'].' JOIN '.$join['table'].' on '.$join['on'].' ';
        }

        //
        // limits
        //
        $limit = null;
        if ($this->limit > 0) {
            $limit = ' limit '.($this->limit_offset != -1 ? $this->limit_offset.', ' : null).$this->limit;
        }

        //
        // group bys
        //
        $group = null;
        if (count($this->_group_fields) > 0) {
            $group = ' group by '.implode(', ', $this->_group_fields).' ';
        }

        //
        // order bys
        //
        $order = null;
        if (count($this->_order_fields) > 0) {
            $order = ' order by ';
            $i = 0;
            foreach ($this->_order_fields as $of) {
                $order .= ($i > 0 ? ', ' : null);
                if (!is_null($of[1])) {
                    $pos = strpos($of[0], '.');
                    if ($pos !== false) {
                        $t = explode('.', $of[0]);
                        $t[1] = '`'.$t[1].'`';
                        $of[0] = implode('.', $t);
                        $order .= $of[0];
                    } else {
                        $order .= '`'.$of[0].'`';
                    }
                    $order .= ' '.($of[1] ? 'asc' : 'desc');
                } else {
                    $order .= $of[0];
                }
                ++$i;
            }
        }
        $string = sprintf('select %1$s from %2$s%7$s%3$s%6$s%4$s%5$s', implode(', ', $_read_fields), implode(', ', $this->_table_names), count($this->_conditions) > 0 ? (' where '.implode(' and ', $this->_conditions)) : null, $order, $limit, $group, $joins);

        return $string;
    }

    public function getUpdate()
    {
        if (count($this->_table_names) == 0) {
            throw new \Exception('Specify at least 1 table name');
        }
        if (count($this->_write_fields) == 0) {
            throw new \Exception('Specify at least 1 write field (set function)');
        }
        $updates = array();
        for ($d = 0, $m = count($this->_write_fields); $d < $m; ++$d) {
            $t = '`' . $this->_write_fields[$d].'`=';
            switch ($this->_write_field_types[$d]) {
            case 0:
              $t .= "'".$this->_write_values[$d]."'";
              break;
          default:
              $t .= $this->_write_values[$d];
          }
            $updates[] = $t;
        }
        $update_fields = implode(', ', $updates);
        $limit = null;
        if ($this->limit > 0) {
            $limit = ' limit '.($this->limit_offset != -1 ? $this->limit_offset.', ' : null).$this->limit;
        }
        $where = count($this->_conditions) > 0 ? (' where '.implode(' and ', $this->_conditions)) : null;
        $string = sprintf('update %1$s set %2$s %3$s %4$s', $this->_table_names[0], $update_fields, $where, $limit);

        return $string;
    }

    public function getInsert()
    {
        if (count($this->_table_names) == 0) {
            throw new \Exception('Specify at least 1 table name');
        }
        if (count($this->_write_fields) == 0) {
            throw new \Exception('Specify at least 1 write field (set function)');
        }
        $table = $this->_table_names[0];
        $fields = '`'.implode('`, `', $this->_write_fields).'`';
        $values = array();
        for ($d = 0, $m = count($this->_write_fields); $d < $m; ++$d) {
            switch ($this->_write_field_types[$d]) {
            case 0:
              $values[] = "'".$this->_write_values[$d]."'";
              break;
            default:
              $values[] = $this->_write_values[$d];
          }
        }
        $string = sprintf('insert into %1$s (%2$s) values(%3$s)', $table, $fields, implode(', ', $values));

        return $string;
    }

    public function getDelete()
    {
        if (count($this->_table_names) == 0) {
            throw new \Exception('Specify at least 1 table name');
        }
        $table = $this->_table_names[0];
        $where = count($this->_conditions) > 0 ? (' where '.implode(' and ', $this->_conditions)) : null;
        $limit = null;
        if ($this->limit > 0) {
            $limit = ' limit '.($this->limit_offset != -1 ? $this->limit_offset.', ' : null).$this->limit;
        }
        $string = sprintf('delete from %1$s %2$s %3$s', $table, $where, $limit);

        return $string;
    }
}
