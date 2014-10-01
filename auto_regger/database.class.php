<?php

/**
 * Database operation class
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Doodstrap
 */
class DB
{

    public $connection;

    private $active_query, $error_info;

    function mysql_insert_id()
    {
        //var_dump($this);
        return $this->connection->lastInsertId();
    }

    function mysql_affected_rows()
    {
        return $this->active_query->rowCount();
    }

    function mysql_errno()
    {
        $this->error_info = $this->active_query->errorInfo();
        return (int)$this->error_info[1];
    }

    /**
     * Sets mode to non-gui debug. Query times and errors will be printed directly to page.
     */
    function debug()
    {
        $this->debug = true;
    }

    /**
     * Gets row count as SELECT COUNT(*) FROM ...
     * @param string $table Table to be selected
     * @param string $suffix Options to select
     * @return int Count of rows
     */
    function get_row_count($table, $suffix = "")
    {
        if ($suffix)
            $suffix = " $suffix";
        $r = $this->query("SELECT COUNT(*) FROM $table $suffix");
        $a = $r->fetch(PDO::FETCH_NUM);
        return $a[0] ? $a[0] : 0;
    }

    /**
     * Builds safe update subquery
     * @param array $ar Associative array of column names and values
     * @return string UPDATE subquery
     */
    function build_update_query($ar)
    {
        foreach ($ar as $k => $v) {
            if (strlen($v))
                $to_update[] = "$k =" . $this->sqlesc($v);
            else
                $to_update[] = "$k = NULL"; //.$DB->sqlesc($v);
        }
        return implode(', ', $to_update);
    }

    /**
     * Builds safe instert subquery
     * @param array $ar Associative array of column names and values
     * @return string INSERT subquery
     */
    function build_insert_query($ar)
    {
        foreach ($ar as $k => $v) {
            $keys[] = $k;
            if (strlen($v))
                $vals[] = $this->sqlesc($v);
            else
                $vals[] = 'NULL';
        }
        return "(" . implode(',', $keys) . ") VALUES (" . implode(',', $vals) . ")";
    }

    /**
     * Class constructor
     * @param array $db Associative array of database configuration
     */
    function __construct($db)
    {
        $this->ttime = 0;
        try {
            $this->connection = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['db'] . ';charset=' . $db['charset'], $db['user'], $db['pass']);
        } catch (PDOException $e) {
            die("Error " . $e->getMessage() . ". Failed to establish connection to SQL server");
        }
        $this->query = array();
        //$this->query[0] = array("seconds" => 0, "query" => 'TOTAL');
    }

    /**
     * Class destructor
     */
    function __destruct()
    {
        $this->connection = null;
    }

    /**
     * Preforms a sql query and writes query and time to statistics
     * @param string $query Query to be performed
     * @return resource Mysql resource
     */
    function query($query)
    {

        $query_start_time = microtime(true); // Start time
        $this->active_query = $this->connection->prepare($query);
        $this->active_query->execute();
        $query_end_time = microtime(true); // End time
        $query_time = ($query_end_time - $query_start_time);
        $this->ttime = $this->ttime + $query_time;
        if ($this->debug) {
            print "$query<br/>took $query_time, total {$this->ttime}, status " . var_export($this->mysql_errno(), true) . "<hr/>";
        }
        if ($this->mysql_errno() && $this->mysql_errno() != 1062) {

            $to_log = "ERROR:  - " . var_export($this->error_info, true) . "<br/>$query<br/>took $query_time, total {$this->ttime}";

            print $to_log;
            if (!$this->debug())
                die();
        }
        $this->query[] = array("seconds" => $query_time, "query" => $query);
        return $this->active_query;
    }

    /**
     * Escapes value to make safe $DB->query
     * @param string $value Value to be escaped
     * @return string Escaped value
     * @see $DB->query()
     */
    function sqlesc($value)
    {
        // Quote if not a number or a numeric string
        //if (!is_numeric($value)) {
            $value = $this->connection->quote((string)$value);
        //}
        return $value;
    }

    /**
     * Escapes value making search query.
     * <code>
     * sqlwildcardesc ('The 120% alcohol');
     * </code>
     * @param string $x Value to be escaped
     * @return string Escaped value
     */
    function sqlwildcardesc($x)
    {
        return $this->connection->quote('%' . str_replace(array("%", "_"), array("\\%", "\\_"), $x) . '%');
    }

    /**
     * Preforms a sql query, returning a results
     * @param string $query query to be executed
     * @param string $type Type of returned data, assoc (default) - associative array, array - array, object - object
     * @return mixed
     */
    function query_return($query, $type = 'assoc')
    {
        $return = array();
        $res = $this->query($query);
        if ($type == 'assoc')
            while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                $return[] = $row;
            } elseif ($type == 'array')
            while ($row = $res->fetch(PDO::FETCH_NUM)) {
                $return[] = $row;
            }
        elseif ($type == 'object')
            while ($row = $res->fetch(PDO::FETCH_OBJ)) {
                $return[] = $row;
            }
        return $return;
    }

    /**
     * Preforms an sql query, returns first row
     * @param string $query query to be executed
     * @param string $type Type of returned data, assoc (default) - associative array, array - array, object - object
     * @return mixed
     */
    function query_row($query, $type = 'assoc')
    {
        $res = $this->query($query);
        if ($type == 'assoc')
            return $res->fetch(PDO::FETCH_ASSOC);
        elseif ($type == 'array')
            return $res->fetch(PDO::FETCH_NUM);
        elseif ($type == 'object')
            return $res->fetch(PDO::FETCH_OBJ);
    }

}

?>