<?php
namespace Query;

use PDO;
use Stephane888\Debug\debugLog;

/**
 * when we use WbuJsonDB, donc call db.class.php
 *
 * @author stephane
 *
 */
class WbuJsonDb {

  public $fields = [];

  public $GroupBy = [];

  public $OrderBy = [];

  /**
   * Exemple:$BD->Where = [
   * 'order-id' => [
   * 'field' => 'id_order',
   * 'value' => $value
   * ]
   * ];
   *
   * @var array
   */
  public $Where = [];

  protected $arg = [];

  public $INNER_JOIN = [];

  public $LEFT_JOIN = [];

  public $is_rebuild = true;

  public $last_req = NULL;

  /**
   * for Insert and Update
   *
   * @var array
   */
  public $fieldsValues = [];

  /**
   * Permet de d'enregistrer les erreurs dans un log.
   */
  public $debug = false;

  function __construct($dataBaseConfig)
  {
    $this->credentielDB($dataBaseConfig);
  }

  public function resetValue()
  {
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

  /**
   * connection à la Base de donnée
   */
  protected function credentielDB($dataBaseConfig)
  {
    if (! empty($dataBaseConfig['user']) && ! empty($dataBaseConfig['password']) && ! empty($dataBaseConfig['dbName'])) {
      DB::$user = $dataBaseConfig['user'];
      DB::$password = $dataBaseConfig['password'];
      DB::$dbName = $dataBaseConfig['dbName'];
    } else {
      die('Error de connection à la base de donnée');
    }
  }

  public function select($table)
  {
    $result = $this->executeQuery($this->buildReq($table), $this->arg);
    // \Drupal\debug_log\debugLog::logs( [$req, $this->arg, $result], 'select_req', 'kint0', $auto=false);
    // reset values
    $this->resetValue();
    return $result;
  }

  public function selectOne($table)
  {
    $result = $this->executeQueryOne($this->buildReq($table), $this->arg);
    // \customapi\debugLog::logs($commands, 'commandes-shopify_'.date('d-Y'));
    // reset values
    $this->resetValue();
    return $result;
  }

  /**
   * Pour effectuer une req sans argument, ou tout est definit dans la requete
   *
   * @param string $req
   * @return array // retourne plusieurs resultat.
   */
  public function CustomRequest($req)
  {
    return $this->executeQuery($req);
  }

  /**
   * Pour effectuer une req sans argument, ou tout est definit dans la requete.
   * Declenche une erreur PHP au cas ou.
   *
   * @param string $req
   */
  public function CustomRequestV2($req)
  {
    return DB::selectPrepareV2($req, []);
  }

  /**
   * Pour effectuer une req sans argument, ou tout est definit dans la requete
   *
   * @deprecated use queryFirstRow
   * @param string $req
   *
   * @return array // retourne une ligne.
   */
  public function CustomRequestFirst($req)
  {
    return $this->executeQueryOne($req);
  }

  /**
   * Pour effectuer une req sans argument, ou tout est definit dans la requete
   *
   * @param string $req
   * @return array // retourne une ligne.
   */
  public function queryFirstRow($req)
  {
    return $this->executeQueryOne($req);
  }

  protected function executeQuery($req, $arg = [])
  {
    $result = DB::selectPrepare($req, $arg);
    if ($this->debug && ! empty($result['PHP_execution_error'])) {
      $errors = [
        'name' => 'update',
        'req' => $req,
        'error' => $result
      ];
      $filename = 'sql__debug-' . date('m-Y');
      debugLog::saveLogs($errors, $filename);
    }
    return $result;
  }

  protected function executeQueryOne($req, $arg = [])
  {
    $result = DB::selectPrepare($req, $arg, 'one');
    if ($this->debug && ! empty($result['PHP_execution_error'])) {
      $errors = [
        'name' => 'update',
        'req' => $req,
        'error' => $result
      ];
      $filename = 'sql__debug-' . date('m-Y');
      debugLog::saveLogs($errors, $filename);
    }
    return $result;
  }

  /**
   * Buld select requette
   *
   * @param string $table
   * @return string
   */
  protected function buildReq($table)
  {
    $fields = '';
    if (! empty($this->fields)) {
      foreach ($this->fields as $field) {
        $fields .= $field . ',';
      }
      $fields = trim($fields, ',');
    } else {
      $fields = '*';
    }
    // select
    $req = "SELECT $fields FROM {$table} ";
    // INNER_JOIN
    if (! empty($this->INNER_JOIN)) {
      $fields = '';
      foreach ($this->INNER_JOIN as $field) {
        $fields .= " INNER JOIN $field ";
      }
      $req .= " $fields ";
    }
    // LEFT_JOIN
    if (! empty($this->LEFT_JOIN)) {
      $fields = '';
      foreach ($this->LEFT_JOIN as $field) {
        $fields .= " LEFT JOIN $field ";
      }
      $req .= " $fields ";
    }
    // WHERE
    if (! empty($this->Where)) {
      $fields = '';
      foreach ($this->Where as $field) {
        $operator = '=';
        if (isset($field['column'])) {
          $field['field'] = $field['column'];
        }
        if (! empty($field['operator'])) {
          $operator = $field['operator'];
        }

        if (! empty($field['join'])) {
          $fields .= $field['join'] . '.' . $field['field'] . $operator . ':' . $field['join'] . $field['field'] . ' AND ';
          $this->arg[':' . $field['join'] . $field['field']] = $field['value'];
        } else {
          $fields .= $field['field'] . $operator . ':' . $field['field'] . ' AND ';
          $this->arg[':' . $field['field']] = $field['value'];
        }
      }
      $fields = trim($fields, 'AND ');
      $req .= " WHERE $fields ";
    }
    // GroupBy
    if (! empty($this->GroupBy)) {
      $fields = '';
      foreach ($this->GroupBy as $field) {
        $fields .= $field . ',';
      }
      $fields = trim($fields, ',');
      $req .= " GROUP BY $fields ";
    }
    // ORDER BY
    if (! empty($this->OrderBy)) {
      $fields = '';
      foreach ($this->OrderBy as $field) {
        $fields .= $field . ',';
      }
      $fields = trim($fields, ',');
      $req .= " ORDER BY $fields ";
    }

    $this->last_req = $req;
    return $req;
  }

  /**
   *
   * @param string $table
   * @param array $fields
   * @return mixed
   */
  public function insert($table, $fields)
  {
    // $resul = DB::insert($table, $fields);
    if (! empty($fields)) {
      $this->fieldsValues = $fields;
    }
    if (! empty($this->fieldsValues)) {
      $req = $this->buildReqIn($table);
      $this->last_req = $req;
      $result = DB::insertPrepare($req, $this->arg);
      if ($this->debug && ! empty($result['PHP_execution_error'])) {
        $errors = [
          'name' => 'update',
          'req' => $req,
          'error' => $result
        ];
        $filename = 'sql__debug-' . date('m-Y');
        debugLog::saveLogs($errors, $filename);
      }
      $this->resetValue();
      return $result;
    }
    return false;
  }

  /**
   *
   * @param string $table
   * @param string $fields
   */
  public function update($table, $fields)
  {
    if (! empty($fields)) {
      $this->fieldsValues = $fields;
    }
    if (! empty($this->fieldsValues) && ! empty($this->Where)) {
      $req = $this->buildReqUp($table);
      $this->last_req = $req;
      $result = DB::updatePrepare($req, $this->arg);
      $this->resetValue();
      if ($this->debug && ! empty($result['PHP_execution_error'])) {
        $errors = [
          'name' => 'update',
          'table' => $table,
          'error' => $result
        ];
        $filename = 'sql__debug-' . date('m-Y');
        debugLog::saveLogs($errors, $filename);
      }
      return $result;
    }
    return false;
  }

  /**
   * Buld select requette;
   * NB requete update with operator =
   *
   * @param string $table
   * @return string
   */
  protected function buildReqUp($table)
  {
    $fields = '';
    foreach ($this->fieldsValues as $key => $field) {
      $keyFormat = $this->filterstring($key);
      $fields .= '`' . $key . '`=:' . $keyFormat . ',';
      $this->arg[':' . $keyFormat] = $field;
    }
    $fields = trim($fields, ',');
    $req = "UPDATE $table SET $fields";

    /**
     * Construction de la partie WHERE
     */
    $fields = '';
    foreach ($this->Where as $field) {
      $operator = '=';
      /**
       * Cas particulier si on a utilisé column pour identifier la colonne de la table.
       * ( par defaut on utilise field ).
       */
      if (isset($field['column'])) {
        $field['field'] = $field['column'];
      }
      /**
       * Il faut definit ->arg different pour where pour permettre la MAJ d'une colonne qui est dans la condition et l'update.
       * On ajoute un prefix 'upd_'.
       */
      if (! empty($field['join'])) {
        /**
         * Ce bloc est à mettre à jour.
         * avec ...'upd_' . $this->filterstring
         */
        $fields .= $field['join'] . '.' . $field['field'] . $operator . ':' . $field['join'] . $field['field'] . ' AND ';
        $this->arg[':' . $field['join'] . $field['field']] = $field['value'];
      } else {
        $keyFormat = 'upd_' . $this->filterstring($field['field']);
        $fields .= '`' . $field['field'] . '`' . $operator . ':' . $keyFormat . ' AND ';
        $this->arg[':' . $keyFormat] = $field['value'];
      }
    }
    $fields = trim($fields, 'AND ');
    $req .= " WHERE $fields ";
    // echo '<pre><hr><hr>requete Update : <br>'; print_r($req); echo '</pre>';
    // echo '<pre><hr><hr>Argument Update : <br>'; print_r($this->arg); echo '</pre>';
    return $req;
  }

  protected function filterstring($string)
  {
    return str_replace("-", "_", $string);
  }

  /**
   * Buld insert requette
   *
   * @param string $table
   * @return string
   */
  protected function buildReqIn($table)
  {
    $fields = $values = '';
    foreach ($this->fieldsValues as $key => $field) {
      $keyFormat = $this->filterstring($key);
      $fields .= '`' . $key . '`,';
      $values .= ':' . $keyFormat . ',';
      $this->arg[':' . $keyFormat] = $field;
    }
    $fields = trim($fields, ',');
    $values = trim($values, ',');
    $req = "INSERT INTO `$table`  ( $fields ) VALUES ( $values )";

    return $req;
  }

  public function getPDO()
  {
    // On se connecte
    $bdd = new PDO('mysql:host=localhost;dbname=' . DB::$dbName, DB::$user, DB::$password, array(
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ));
    $bdd->exec("set names utf8");
    return $bdd;
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
  public static function addFilter($filters, $column, $value, $operator = '=', $logique = 'AND', $unique = false, $preffix = '')
  {
    if (! $unique) {
      $filters[$logique][] = [
        'column' => $column,
        'value' => $value,
        'operator' => $operator,
        'preffix' => $preffix
      ];
      return $filters;
    } else {
      if (empty($filters[$logique])) {
        $filters[$logique][] = [
          'column' => $column,
          'value' => $value,
          'operator' => $operator,
          'preffix' => $preffix
        ];
        return $filters;
      } else {
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
}

















