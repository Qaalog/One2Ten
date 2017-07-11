<?php

require_once dirname(__FILE__).'/IRBaseModel.php';

class IRMSSqlDataSource extends IRBaseModel {

    private $_logs = array();
    private $_debug = true;
    private $_dataBaseName = null;
    
    public function __construct($config){
        if (isset($config['tableName'])) {
            $this->setTable( $config['tableName'] );
            unset($config['tableName']);
        }
        if (isset($config['database_name']) && $config['database_name'] > '') {
            $this->_dataBaseName = $config['database_name'];
        }
        parent :: __construct($config);
    }
    
    /**
     * Select data from table
     * @param array $filter
     * @param string $fields
     * @param array $params
     * @return array|boolean
     */
    public function select($filter = array(), $fields = '*', $params = array()) {
        $limit = isset($params['limit']) ? $params['limit'] : null;
        $offset = isset($params['offset']) ? $params['offset'] : null;
        $orderBy = isset($params['order_by']) ? $params['order_by'] : null;
        try {
            $filter = $this->checkFilter($filter);
            $filtered = !empty($filter) && isset($filter['filter']);
            $tSql = "SELECT $fields FROM " . $this->getTable() . ($filtered ? ' WHERE '.$filter['filter'] : '') . ' ' . $orderBy;
            $params = ( $filtered && isset($filter['params']) && is_array($filter['params']) ) ? $filter['params'] : array(); 
            $this->addLog($tSql, $params);
            $getData = $this->getDBConnection()->prepare($tSql);
            $getData->execute($params);
            if (empty($offset) && $limit && $limit == '1') {
                $data = $getData->fetch(PDO :: FETCH_ASSOC);
            } else {
                $data = $getData->fetchAll(PDO :: FETCH_ASSOC);
                if ($limit) {
                    $data = array_slice( $data, $offset, $limit);
                }
            }
            $this->setLastError(self :: SUCCESSFUL);
            return $data;
        } catch (Exception $e) {
            $this->setLastError(self :: QUERY_ERROR);
            $this->addLog($tSql, $e);
            return false;
        }
    }

    public function selectCount($filter = '', $fields = '*') {
        try {
            $tSql = "SELECT COUNT(*) AS NumberOfOrders FROM " . $this->getTable() . (empty($filter) ? '' : ' WHERE '.$filter);
            $getData = $this->getDBConnection()->prepare($tSql);
            $getData->execute();
            if ($data = $getData->fetch(PDO :: FETCH_COLUMN)) {
                $this->setLastError(self :: SUCCESSFUL);
            } else {
            }
            return $data;
        } catch (Exception $e) {
            $this->setLastError(self :: QUERY_ERROR);
            return false;
        }
    }

    public function getFields() {
        return $this->getTableFields();
    }

    protected function checkFilter($filter) {
        $filtered = is_array($filter) && isset($filter['filter']);
        if ( $filtered && isset($filter['params']) && is_array($filter['params']) ) {
            // 
        } elseif(is_string($filter)) {
            $filter = array( 'filter' => $filter );
        }
        if (!empty($filter['params']) && is_array($filter)) {
            foreach ($filter['params'] as $key => $value) {
                if (strpos($filter['filter'], $key) === false) {
                    unset($filter['params'][$key]);
                }
            }
        }
        $this->addLog($filter);
        return $filter;
    }

    function selectQuery($tSql, $params = array(), $limit = null, $offset = 0) {
        return $this->query($tSql, $params, $limit, $offset);
    }

    function queryIndexed($tSql, $params = array(), $indexField = 'Id') {
        $rows = $this->query($tSql, $params);
        if (!empty($rows)) {
            $indexedArray = array();
            foreach($rows as $row) {
                $index = $row[$indexField];
                $indexedArray[$index] = $row;
            }
            $rows = $indexedArray;
        }
        return $rows;
    }

    function query($tSql, $params = array(), $limit = null, $offset = 0) {
        try {
            $params = is_array($params) ? $params : array(); 
            //var_dump($tSql, $params);
            $this->addLog($tSql, $params);
            $getData = $this->getDBConnection()->prepare($tSql);
            $getData->execute($params);
            $data = $getData->fetchAll(PDO :: FETCH_ASSOC);
            $this->setLastError(self :: SUCCESSFUL);
            if ($limit) {
                if ($limit == 1) {
                    $data = array_shift($data);
                } else {
                    $data = array_slice( $data, $offset, $limit);
                }
            }
            return $data;
        } catch (Exception $e) {
            $this->setLastError(self :: QUERY_ERROR);
            $this->setLastException($e);
            $this->addLog($e);
            //var_dump($e);
            return false;
        }
    }

    function queryRow($tSql, $params = array()) {
        return $this->query($tSql, $params, 1);
    }

    function queryColumn($tSql, $params = array(), $limit = null, $offset = 0) {
        try {
            $params = is_array($params) ? $params : array(); 
            //var_dump($tsql, $params);
            $this->addLog($tSql, $params);
            $getData = $this->getDBConnection()->prepare($tSql);
            $getData->execute($params);
            $data = $getData->fetchAll(PDO :: FETCH_COLUMN);
            $this->setLastError(self :: SUCCESSFUL);
            if ($limit) {
                if ($limit == 1) {
                    $data = array_shift($data);
                } else {
                    $data = array_slice( $data, $offset, $limit);
                }
            }
            return $data;
        } catch (Exception $e) {
            $this->setLastError(self :: QUERY_ERROR);
            $this->addLog($tSql, $params, $e);
            return false;
        }
    }

    function queryValue($tSql, $params = array()) {
        try {
            $params = is_array($params) ? $params : array(); 
            //var_dump($tsql, $params);
            $this->addLog($tSql, $params);
            $getData = $this->getDBConnection()->prepare($tSql);
            $getData->execute($params);
            $data = $getData->fetchColumn();
            $this->setLastError(self :: SUCCESSFUL);
            return $data;
        } catch (Exception $e) {
            $this->setLastError(self :: QUERY_ERROR);
            $this->addLog($e);
            return false;
        }
    }

    function execute($tSql, $params = array()) {
        try {
            $params = is_array($params) ? $params : array(); 
            //var_dump($tsql, $params);
            $getData = $this->getDBConnection()->prepare($tSql);
            return $getData->execute($params);
        } catch (Exception $e) {
            $this->setLastError(self :: QUERY_ERROR);
            $this->addLog($tSql, $params, $e);
            $this->setLastException($e);
            return false;
        }
    }
    
    function transaction( $action ) {
        $result = '';
        try {
            switch ($action) {
                case 'start':
                    $operationResult = $this->getDBConnection()->beginTransaction();
                    $result = "Transaction: $action [{$operationResult}]";
                    break;
                case 'commit':
                    $operationResult = $this->getDBConnection()->commit();
                    $result = "Transaction: $action [{$operationResult}]";
                    break;
                case 'rollback':
                    $operationResult = $this->getDBConnection()->rollBack();
                    $result = "Transaction: $action [{$operationResult}]";
                    break;
                default:
                    break;
            }
        } catch (Exception $e) {
            $result = "Transaction Error: ".$e->getMessage();
        }
        return $result;
    }

    public function getLogs() {
        return $this->_logs;
    }

    private function addLog() {
        $numArgs = func_num_args();
        $argList = func_get_args();
        if ($numArgs) {
            foreach($argList as $object) {
                $this->_logs[] = $object;
                if ($this->_debug) {
                    //var_dump($object);
                }
            }
        }
    }

    /*
     * @return string
     */
    function getLastExceptionMessage() {
        return is_object($this->_exception) ? $this->_exception->getMessage() : ( empty($this->_exception) ? '' : 'Something wrong! Please notify system admin.' );
    }

    function getDataBaseName() {
        return $this->_dataBaseName;
    }

    public function createRow($data) {
        try {
            $fieldsList = implode(',', array_keys($data));
            $values = $this->getQueryParams($data);
            $valueList = implode(',', array_keys($values));
            $tSql = 'INSERT INTO ' . $this->table . ' (' . $fieldsList . ') OUTPUT Inserted.* VALUES (' . $valueList . ')';
            $query = $this->db_con->prepare($tSql);
            $query->execute($values);
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result;
        } catch (Exception $e) {
            $this->setLastException($e);
            var_dump($e, $tSql, $values);
            return false;
        }
    }

    function getQueryParams(array $data) {
        $result = array();
        foreach ($data as $key => $value) {
            $result[':' . $key] = $value;
        }
        return $result;
    }

}