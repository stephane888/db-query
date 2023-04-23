<?php

namespace Query;

use Stephane888\Debug\debugLog;

/**
 * when we use WbuJsonDB, donc call db.class.php
 *
 * @author stephane
 *        
 */
class WbuJsonDb {
  
  /**
   * Use for select.
   *
   * @var array
   */
  public $fields = [];
  public $GroupBy = [];
  public $OrderBy = [];
  
  /**
   * Exemple:$BD->Where = [
   * 'order-id' => [
   * 'field' => 'id_order',
   * 'value' => $value,
   * 'operator' => '='
   * ]
   * ];
   *
   * @var array
   */
  public $Where = [];
  protected $arg = [];
  public $Last_arg = [];
  public $INNER_JOIN = [];
  public $LEFT_JOIN = [];
  public $is_rebuild = true;
  public $last_req = NULL;
  
  /**
   * Use for Insert and Update
   *
   * @var array
   */
  public $fieldsValues = [];
  
  /**
   * Permet d'enregistrer les erreurs dans un log.
   */
  public $debug = true;
  
  /**
   * Pour verifier une erreur.
   */
  private $SqlHasError = false;
  public $lastErrorInfo = '';
  
  /**
   * cc
   */
  public $filename = '';
  /**
   * save the executed query
   */
  private $query;
  
  /**
   *
   * @param array $dataBaseConfig
   * @param boolean $autocommit
   */
  function __construct($dataBaseConfig, $autocommit = true) {
    $this->credentielDB($dataBaseConfig);
    $this->setAutocommit($autocommit);
  }
  
  public function resetValue() {
    if ($this->is_rebuild) {
      $this->fields = [];
      $this->GroupBy = [];
      $this->OrderBy = [];
      $this->Where = [];
      $this->arg = [];
      $this->INNER_JOIN = [];
      $this->LEFT_JOIN = [];
      $this->fieldsValues = [];
    }
  }
  
  public function hasError() {
    return $this->SqlHasError;
  }
  
  /**
   * Connection à la Base de donnée
   */
  protected function credentielDB($dataBaseConfig) {
    if (!empty($dataBaseConfig['user']) && isset($dataBaseConfig['password']) && !empty($dataBaseConfig['dbName'])) {
      WbuDb::$user = $dataBaseConfig['user'];
      WbuDb::$password = $dataBaseConfig['password'];
      WbuDb::$dbName = $dataBaseConfig['dbName'];
      if (!empty($dataBaseConfig['host'])) {
        WbuDb::$host = $dataBaseConfig['host'];
      }
    }
    else {
      throw new \Exception('Paramettre de connexion a la BD non definit');
    }
  }
  
  /**
   * Permet de modifier le status de l'auto commit de Mysql.
   * Ce paramettre est par defaut à true, i.e toutes les requetes sont
   * enregistés.
   * Dans la mesure ou on la definit à false, il faut faire le commit à la fin
   * des requetes.
   * example de commit: $this->getPDO()->commit();
   *
   * @param boolean $autocommit
   */
  public function setAutocommit($autocommit) {
    WbuDb::$autocommit = $autocommit;
  }
  
  public function select($table) {
    $result = $this->executeQuery($this->buildReq($table), $this->arg);
    // \Drupal\debug_log\debugLog::logs( [$req, $this->arg, $result],
    // 'select_req', 'kint0', $auto=false);
    // reset values
    $this->resetValue();
    return $result;
  }
  
  public function selectOne($table) {
    $result = $this->executeQueryOne($this->buildReq($table), $this->arg);
    // \customapi\debugLog::logs($commands, 'commandes-shopify_'.date('d-Y'));
    // reset values
    $this->resetValue();
    return $result;
  }
  
  /**
   * Pour effectuer une req sans argument, ou tout est definit dans la requete.
   * En cas d'erreur, elle sont transmise dans les logs.
   *
   * @param string $req
   * @return array // retourne plusieurs resultat.
   */
  public function CustomRequest($req) {
    return $this->executeQuery($req);
  }
  
  /**
   * Pour effectuer une req sans argument, ou tout est definit dans la requete.
   * Declenche une erreur PHP au cas ou.
   *
   * @param string $req
   */
  public function CustomRequestV2($req) {
    return WbuDb::selectPrepareV2($req, []);
  }
  
  /**
   * Pour effectuer une req sans argument, ou tout est definit dans la requete
   *
   * @deprecated use queryFirstRow
   * @param string $req
   *
   * @return array // retourne une ligne.
   */
  public function CustomRequestFirst($req) {
    return $this->executeQueryOne($req);
  }
  
  /**
   * Pour effectuer une req sans argument, ou tout est definit dans la requete
   *
   * @param string $req
   * @return array // Retourne une ligne.
   */
  public function queryFirstRow($req) {
    return $this->executeQueryOne($req);
  }
  
  /**
   * exemple : DELETE FROM Users WHERE nom='Giraud'
   *
   * @param string $req
   * @return boolean[]|NULL[]
   */
  public function deleteDatas($req) {
    return WbuDb::deletePrepare($req);
  }
  
  public function delete($req) {
    return $this->deleteDatas($req);
  }
  
  protected function executeQuery($req, $arg = []) {
    $result = WbuDb::selectPrepare($req, $arg);
    $this->query = WbuDb::getQuery();
    
    if (!empty($result['PHP_execution_error'])) {
      $this->SqlHasError = true;
      $this->lastErrorInfo = $result;
      if ($this->debug) {
        $errors = [
          'req' => $req,
          'error' => $result
        ];
        $filename = ($this->filename != '') ? $this->filename : 'executeQuery_error-' . date('d-m-Y');
        debugLog::saveLogs($errors, 'sql__' . $filename);
      }
    }
    
    return $result;
  }
  
  protected function executeQueryOne($req, $arg = []) {
    $result = WbuDb::selectPrepare($req, $arg, 'one');
    if (!empty($result['PHP_execution_error'])) {
      $this->SqlHasError = true;
      $this->lastErrorInfo = $result;
      if ($this->debug) {
        $errors = [
          'req' => $req,
          'error' => $result
        ];
        $filename = ($this->filename != '') ? $this->filename : 'executeQueryOne_error-' . date('d-m-Y');
        debugLog::saveLogs($errors, 'sql__' . $filename);
      }
    }
    
    return $result;
  }
  
  /**
   * Buld select requette
   *
   * @param string $table
   * @return string
   */
  protected function buildReq($table) {
    $fields = '';
    if (!empty($this->fields)) {
      foreach ($this->fields as $field) {
        $fields .= $field . ',';
      }
      $fields = trim($fields, ',');
    }
    else {
      $fields = '*';
    }
    // select
    $req = "SELECT $fields FROM {$table} ";
    // INNER_JOINquery
    if (!empty($this->INNER_JOIN)) {
      $fields = '';
      foreach ($this->INNER_JOIN as $field) {
        $fields .= " INNER JOIN $field ";
      }
      $req .= " $fields ";
    }
    // LEFT_JOIN
    if (!empty($this->LEFT_JOIN)) {
      $fields = '';
      foreach ($this->LEFT_JOIN as $field) {
        $fields .= " LEFT JOIN $field ";
      }
    }
    // WHERE
    if (!empty($this->Where)) {
      $fields = '';
      foreach ($this->Where as $field) {
        $operator = '=';
        if (isset($field['column'])) {
          $field['field'] = $field['column'];
        }
        if (!empty($field['operator'])) {
          $operator = $field['operator'];
        }
        
        if (!empty($field['join'])) {
          $fields .= $field['join'] . '.' . $field['field'] . $operator . ':' . $field['join'] . $field['field'] . ' AND ';
          $this->arg[':' . $field['join'] . $field['field']] = $field['value'];
        }
        else {
          $fields .= $field['field'] . $operator . ':' . $field['field'] . ' AND ';
          $this->arg[':' . $field['field']] = $field['value'];
        }
      }
      $fields = trim($fields, 'AND ');
      $req .= " WHERE $fields ";
    }
    // GroupBy
    if (!empty($this->GroupBy)) {
      $fields = '';
      foreach ($this->GroupBy as $field) {
        $fields .= $field . ',';
      }
      $fields = trim($fields, ',');
      $req .= " GROUP BY $fields ";
    }
    // ORDER BY
    if (!empty($this->OrderBy)) {
      $fields = '';
      foreach ($this->OrderBy as $field) {
        $fields .= $field . ',';
      }
      $fields = trim($fields, ',');
      $req .= " ORDER BY $fields ";
    }
    
    $this->last_req = $req;
    $this->Last_arg = $this->arg;
    return $req;
  }
  
  /**
   * Permet de faire un envoie tout en gerant les erreurs si $ignorError = true
   *
   * @param string $table
   * @param array $fields
   * @param boolean $ignorError
   *        doit etre supprimer pour la version 2.0.0.
   * @return mixed
   *
   */
  public function insert($table, $fields, $ignorError = true) {
    if (!empty($fields)) {
      $this->fieldsValues = $fields;
    }
    if (!empty($this->fieldsValues)) {
      $req = $this->buildReqIn($table);
      $this->last_req = $req;
      if ($ignorError) {
        $result = WbuDb::insertPrepare($req, $this->arg);
        $this->SqlHasError = false;
        $this->lastErrorInfo = '';
        if (!empty($result['PHP_execution_error'])) {
          $this->lastErrorInfo = $result;
          $this->SqlHasError = true;
          /**
           * Pour renvoyer les erreurs vers un fichiers.
           */
          if ($this->debug) {
            $errors = [
              'req' => $req,
              'error' => $result
            ];
            $filename = ($this->filename != '') ? $this->filename : 'insert_error-' . date('d-m-Y');
            debugLog::saveLogs($errors, 'sql__' . $filename);
          }
        }
      }
      else {
        $result = WbuDb::insert($req, $this->arg);
      }
      $this->resetValue();
      return $result;
    }
    return false;
  }
  
  /**
   * Cette version est utilisé par une application.
   *
   * @param string $table
   * @param array $fields
   * @return mixed|boolean|number
   * @deprecated
   */
  public function insert_v2($table, $fields) {
    return $this->insert($table, $fields);
  }
  
  /**
   *
   * @param string $table
   * @param string $fields
   */
  public function update($table, $fields, $ignorError = true) {
    if (!empty($fields)) {
      $this->fieldsValues = $fields;
    }
    if ($ignorError) {
      if (!empty($this->fieldsValues) && !empty($this->Where)) {
        $req = $this->buildReqUp($table);
        $this->last_req = $req;
        $result = WbuDb::updatePrepare($req, $this->arg);
        $this->resetValue();
        $this->SqlHasError = false;
        $this->lastErrorInfo = '';
        if ($this->debug && !empty($result['PHP_execution_error'])) {
          $this->lastErrorInfo = $result;
          $this->SqlHasError = true;
          $errors = [
            'table' => $table,
            'error' => $result
          ];
          $filename = ($this->filename != '') ? $this->filename : 'update_error-' . date('m-Y');
          debugLog::saveLogs($errors, 'sql__' . $filename);
        }
        return $result;
      }
    }
    else {
      $req = $this->buildReqUp($table);
      $result = WbuDb::update($req, $this->arg);
      $this->resetValue();
      return $result;
    }
  }
  
  /**
   * Buld select requette;
   * NB requete update with operator =
   *
   * @param string $table
   * @return string
   */
  protected function buildReqUp($table) {
    $fields = '';
    foreach ($this->fieldsValues as $key => $field) {
      $keyFormat = $this->filterstring($key);
      $fields .= '`' . $key . '`=:' . $keyFormat . ',';
      $this->arg[':' . $keyFormat] = $field;
    }
    $fields = trim($fields, ',');
    $req = "UPDATE `$table` SET $fields";
    
    /**
     * Construction de la partie WHERE
     */
    $fields = '';
    foreach ($this->Where as $field) {
      $operator = '=';
      /**
       * Cas particulier si on a utilisé column pour identifier la colonne de la
       * table.
       * ( par defaut on utilise field ).
       */
      if (isset($field['column'])) {
        $field['field'] = $field['column'];
      }
      /**
       * Il faut definit ->arg different pour where pour permettre la MAJ d'une
       * colonne qui est dans la condition et l'update.
       * On ajoute un prefix 'upd_'.
       */
      if (!empty($field['join'])) {
        /**
         * Ce bloc est à mettre à jour.
         * avec ...'upd_' . $this->filterstring
         */
        $fields .= $field['join'] . '.' . $field['field'] . $operator . ':' . $field['join'] . $field['field'] . ' AND ';
        $this->arg[':' . $field['join'] . $field['field']] = $field['value'];
      }
      else {
        $keyFormat = 'upd_' . $this->filterstring($field['field']);
        $fields .= '`' . $field['field'] . '`' . $operator . ':' . $keyFormat . ' AND ';
        $this->arg[':' . $keyFormat] = $field['value'];
      }
    }
    $fields = trim($fields, 'AND ');
    $req .= " WHERE $fields ";
    // echo '<pre><hr><hr>requete Update : <br>'; print_r($req); echo '</pre>';
    // echo '<pre><hr><hr>Argument Update : <br>'; print_r($this->arg); echo
    // '</pre>';
    return $req;
  }
  
  protected function filterstring($string) {
    return str_replace("-", "_", $string);
  }
  
  /**
   * Buld insert requette
   *
   * @param string $table
   * @return string
   */
  protected function buildReqIn($table) {
    $fields = $values = '';
    foreach ($this->fieldsValues as $key => $field) {
      $keyFormat = $this->filterstring($key);
      $fields .= '`' . $key . '`,';
      $values .= ':' . $keyFormat . ',';
      $this->arg[':' . $keyFormat] = $field;
    }
    $fields = trim($fields, ',');
    $values = trim($values, ',');
    $req = " INSERT INTO `$table`  ( $fields ) VALUES ( $values ) ";
    
    return $req;
  }
  
  /**
   *
   * @return \PDO
   */
  public function getPDO() {
    return WbuDb::getConnectParam();
  }
  
  /**
   *
   * @param array $filters
   * @param string $column
   * @param string $value
   * @param string $operator
   * @param string $logique
   * @param boolean $unique
   * @return array
   */
  public static function addFilter($filters, $column, $value, $operator = '=', $logique = 'AND', $unique = false, $preffix = '') {
    if (!$unique) {
      $filters[$logique][] = [
        'column' => $column,
        'value' => $value,
        'operator' => $operator,
        'preffix' => $preffix
      ];
      return $filters;
    }
    else {
      if (empty($filters[$logique])) {
        $filters[$logique][] = [
          'column' => $column,
          'value' => $value,
          'operator' => $operator,
          'preffix' => $preffix
        ];
        return $filters;
      }
      else {
        foreach ($filters[$logique] as $key => $val) {
          if ($val['column'] == $column) {
            $filters[$logique][$key] = [
              'column' => $column,
              'value' => $value,
              'operator' => $operator,
              'preffix' => $preffix
            ];
            return $filters;
          }
        }
        $filters[$logique][] = [
          'column' => $column,
          'value' => $value,
          'operator' => $operator,
          'preffix' => $preffix
        ];
        return $filters;
      }
    }
  }
  
  public function getQuery() {
    return $this->query;
  }
  
}
