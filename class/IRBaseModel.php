<?php
class IRBaseModel {
  const SUCCESSFUL = 0;
  const CONFIGURATION_ERROR = 0;
  const CONNECTION_ERROR = 2;
  const QUERY_ERROR = 3;
  const FIELDS_ERROR = 4;
  const DUPLICATED_ERROR = 5;
  
  protected $table;
  protected $db_con = false;
  private $config;
  private $lastError;
  protected $_exception;
  
  protected function array_change_case($params, $case = CASE_UPPER){
    $params = array_change_key_case($params, $case);
    foreach ( $params as &$value ) {
      if (is_scalar($value)) {
        if ($case == CASE_UPPER) {
          $value = strtoupper($value);
        } else {
          $value = strtolower($value);
        }
      } elseif (is_array($value)) {
        if ($case == CASE_UPPER) {
          $value = array_map("strtoupper", $value);
        } else {
          $value = array_map("strtolower", $value);
        }
      }
    }
    unset($value);
    return $params;
  }
  
  public function __construct($config){
    $this -> setConfig($config);
    $this -> getDBConnection();
  }
  public function setConfig($config){
    $this -> config = $config;
  }
  public function getConfig(){
    return $this -> config;
  }
  public function getTable(){
    return $this -> table;
  }

  /**
   * 
   * @param type $table
   * @return IRBaseModel
   */
  public function setTable($table){
    $this -> table = $table;
    return $this;
  }
  public function getDBConnection(){
    if ($this -> db_con) {
      return $this -> db_con;
    } else {
      $serverName = '';
      $databaseName = '';
      $userName = '';
      $userPassword = '';
      if (isset($this -> config['server_name'])) {
        $serverName = $this -> config['server_name'];
      } else {
        $this -> setLastError(self :: CONFIGURATION_ERROR);
        return false;
      }
      if (isset($this -> config['database_name'])) {
        $databaseName = $this -> config['database_name'];
      } else {
        $this -> setLastError(self :: CONFIGURATION_ERROR);
        return false;
      }
      if (isset($this -> config['user_name'])) {
        $userName = $this -> config['user_name'];
      }
      if (isset($this -> config['user_password'])) {
        $userPassword = $this -> config['user_password'];
      }
      try {
        if (isset($this -> config['driver_type']) && $this -> config['driver_type'] == 'sqlsrv') {
            $this -> db_con = new PDO("sqlsrv:server={$serverName};Database={$databaseName}", "$userName", "$userPassword");    ///, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        } else {
            $this -> db_con = new PDO("dblib:host={$serverName};dbname={$databaseName}", "$userName", "$userPassword", array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        }
        $this -> db_con -> setAttribute(PDO :: ATTR_ERRMODE, PDO :: ERRMODE_EXCEPTION);
        $this -> setLastError(self :: SUCCESSFUL);
        return $this -> db_con;
      } catch ( Exception $e ) {
        //var_dump($e);
        $this->setLastException($e);
        $this -> setLastError(self :: CONNECTION_ERROR);
        return false;
      }
    }
  }
  public function closeConnection(){
    $this -> db_con = null;
  }
  protected function getTableFields(){
    try {
      $tsql = "SELECT column_name, is_nullable, data_type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$this->table'";
      $getData = $this -> db_con -> prepare($tsql);
      $getData -> execute();
      $data = $getData -> fetchAll(PDO :: FETCH_ASSOC);
      $this -> setLastError(self :: SUCCESSFUL);
      return $data;
    } catch ( Exception $e ) {
      $this -> setLastError(self :: QUERY_ERROR);
      return false;
    }
  }
  
  public function getRecordById($id, $field = 'Id', $fields='*'){
    if (isset($id)) {
      try {
        if ($field == 'Id' && !is_numeric($id)) {
          $this -> setLastError(self :: QUERY_ERROR);
          return false;
        }
        $tSql = "SELECT $fields FROM $this->table WHERE $field = :id";
        $params[':id'] = $id;
        $getData = $this -> db_con -> prepare($tSql);
        $getData -> execute($params);
        $data = $getData -> fetch(PDO :: FETCH_ASSOC);
        $this -> setLastError(self :: SUCCESSFUL);
        return $data;
      } catch ( Exception $e ) {
        $this -> setLastError(self :: QUERY_ERROR);
        $this->setLastException($e);
        return false;
      }
    } else {
      $this -> setLastError(self :: QUERY_ERROR);
      return false;
    }
  }
  public function getAllRecords($fields = '*'){
    try {
      $tsql = "SELECT $fields FROM " . $this -> table;
      $getData = $this -> db_con -> prepare($tsql);
      $getData -> execute();
      $data = $getData -> fetchAll(PDO :: FETCH_ASSOC);
      $this -> setLastError(self :: SUCCESSFUL);
      return $data;
    } catch ( Exception $e ) {
      $this -> setLastError(self :: QUERY_ERROR);
      return false;
    }
  }
  public function getPaginatedRecords($filter,$fields,$pagesize,$page){
    // SELECT * FROM (
    // SELECT ROW_NUMBER() OVER(ORDER BY ID_EXAMPLE) AS NUMBER,
    // ID_EXAMPLE, NM_EXAMPLE, DT_CREATE FROM TB_EXAMPLE
    // ) AS TBL
    // WHERE NUMBER BETWEEN ((@PageNumber - 1) * @RowspPage + 1) AND (@PageNumber * @RowspPage)
    // ORDER BY ID_EXAMPLE
  
  }
  public function prepareFilter($base = '', $keys, $keysCondition, $baseCondition = false){
    $result = array('filter'=>'','params'=>array());
    $fields = $this -> getTableFields();
    if ($fields) {
      $k=0;
      foreach ( $keys as $key => &$rec ) {
        foreach ( $fields as &$record ) {
          $field = array_keys($rec);
          $field = $field[0];
          if (strtoupper($field) == strtoupper($record['column_name'])) {
            $result['filter'] .= $field . ' = :param' . $k.' '. $keysCondition . ' ';
            $result['params']['param'.$k] = $keys[$key][$field];
            $k++;
            break;
          }
        }
        unset($record);
      }
      unset($rec);
      $result['filter'] = '(' . rtrim($result['filter'], $keysCondition . ' ') . ')';
      if ($base && $baseCondition) {
        $result['filter'] .= $baseCondition . ' ' . $base;
      }
    }
    return $result;
  }

  public function getFilteredRecords($filter, $fields='*'){ // filter must be prepared
    try {
      $tsql = "SELECT $fields FROM " . $this -> table;
      if ($filter) {
        $tsql .= ' WHERE ' . $filter['filter'];
      }
      $getData = $this -> db_con -> prepare($tsql);
      $getData -> execute($filter['params']);
      $data = $getData -> fetchAll(PDO :: FETCH_ASSOC);
      $this -> setLastError(self :: SUCCESSFUL);
      return $data;
    } catch ( Exception $e ) {
      $this -> setLastError(self :: QUERY_ERROR);
      return false;
    }
  
  }
  public function checkInsertData($data){
    $fields = $this -> getTableFields();
    $notNullableFields = array();
    $allFields = array();
    $dataKeys = $this -> array_change_case(array_keys($data));
    if ($fields) {
      foreach ( $fields as &$record ) {
        $allFields[] = $record['column_name'];
        if ($record['is_nullable'] == 'NO' && strtoupper($record['column_name']) != 'ID') {
          $notNullableFields[] = $record['column_name'];
        }
      }
      unset($record);
      $allFields = $this -> array_change_case($allFields);
      $notNullableFields = $this -> array_change_case($notNullableFields);
      $diff = array();
      $diff = array_diff($dataKeys, $allFields);
      if ($diff) {
        $this -> setLastError(self :: FIELDS_ERROR);
        return false;
      } else {
        $diff = array();
        $diff = array_diff($notNullableFields, $dataKeys);
        if ($diff) {
          $this -> setLastError(self :: FIELDS_ERROR);
          return false;
        }
      }
      $this -> setLastError(self :: SUCCESSFUL);
      return true;
    } else {
      $this -> setLastError(self :: QUERY_ERROR);
      return false;
    }
  }

  public function checkUpdateData($data){
    return true;
    $fields = $this -> getTableFields();
    $allFields = array();
    $dataKeys = $this -> array_change_case(array_keys($data));
    if ($fields) {
      foreach ( $fields as &$record ) {
        $allFields[] = $record['column_name'];
      }
      unset($record);
      $allFields = $this -> array_change_case($allFields);
      $diff = array();
      $diff = array_diff($dataKeys, $allFields);
      if ($diff) {
        $this -> setLastError(self :: FIELDS_ERROR);
        return false;
      }
      $this -> setLastError(self :: SUCCESSFUL);
      return true;
    } else {
      $this -> setLastError(self :: QUERY_ERROR);
      return false;
    }
  }

  public function createRecord($data, $noCheck = false){ // data array(key=>value)
    try {
      $checkedData = $noCheck || $this -> checkInsertData($data);
      if ($checkedData) {
        $fieldsList = implode(',', array_keys($data));
        $valueList = implode(',:', array_keys($data));
        $valueList = ':' . $valueList;
        $values = array_combine(explode(',', $valueList), array_values($data));
        $tSql = 'INSERT INTO ' . $this -> table . ' (' . $fieldsList . ') OUTPUT Inserted.* VALUES (' . $valueList . ')';
        $getData = $this -> db_con -> prepare($tSql);
        $getData -> execute($values);
        $result = $getData -> fetch(PDO :: FETCH_ASSOC);
        $this -> setLastError(self :: SUCCESSFUL);
        return $result;
      }
    } catch ( Exception $e ) {
      $this -> setLastError(self :: QUERY_ERROR);
      var_dump($e, $tSql, $values);
      return false;
    }
  }

  public function updateRecord($id, $data, $field = 'Id'){
    try {
      $checkedData = $this -> checkUpdateData($data);
      if ($checkedData) {
        $updateStmt = 'SET ';
        $dataKeys = array_keys($data);
        foreach ( $dataKeys as &$value ) {
          $updateStmt .= $value . '=:' . $value . ',';
          $updateValues[':' . $value] = $data[$value];
        }
        unset($value);
        $updateStmt = rtrim($updateStmt, ',');
        $updateValues[':TableKey'] = $id;
        $tSql = 'UPDATE ' . $this -> table . ' ' . $updateStmt . ' WHERE ' . $field . '=:TableKey';
        $getData = $this -> db_con -> prepare($tSql);
        $getData -> execute($updateValues);
        $this -> setLastError(self :: SUCCESSFUL);
        return true;
      }
    } catch ( Exception $e ) {
      $this -> setLastError(self :: QUERY_ERROR);
      $this->setLastException($e);
      //var_dump($e, $tSql, $updateValues);
      return false;
    }
  }
  public function deleteRecord($id, $field = 'Id'){
    try {
      if ($field == 'Id' && !is_numeric($id)) {
        $this -> setLastError(self :: QUERY_ERROR);
        return false;
      }
      $tsql = 'DELETE FROM ' . $this -> table . ' WHERE ' . $field . '= :id';
      $params[':id'] = $id;
      $getData = $this -> db_con -> prepare($tsql);
      $getData -> execute($params);
      $this -> setLastError(self :: SUCCESSFUL);
      return true;
    } catch ( Exception $e ) {
      $this -> setLastError(self :: QUERY_ERROR);
      return false;
    }
  }
  
  public function getLastError(){
    return $this -> lastError;
  }
  protected function setLastError($error){
    $this -> lastError = $error;
  }
  
  public function getLastErrorMessage(){
    $result = 'Unknown Error';
    switch ($this -> lastError) {
      case self::SUCCESSFUL : $result = 'Successful';
        break;
      case self::CONFIGURATION_ERROR : $result =  'Configuration error';
        break;
      case self::CONNECTION_ERROR : $result = 'Connection error';
        break;
      case self::DUPLICATED_ERROR : $result = 'Duplicated error';
        break;
      case self::FIELDS_ERROR : $result = 'Fields error';
        break;
      case self::QUERY_ERROR : $result = 'SQL Server Query Error. Please ask admin for details.';
        break;
      default : 
        ;
        break;
    }
    return $result;
  }

  function setLastException($error) {
    $this->_exception = $error;
  }

  /*
   * @return Exception
   */
  function getLastException() {
    return $this->_exception;
  }
}