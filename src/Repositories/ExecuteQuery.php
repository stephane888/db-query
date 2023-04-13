<?php
namespace Query\Repositories;

use Query\WbuJsonDb;
use Stephane888\Debug\Mysql\DebugErrors as MysqlError;

class ExecuteQuery {

  protected $BD;

  private $LastId;

  private $hasError = false;

  private $FirstError = null;

  function __construct(WbuJsonDb $BD)
  {
    $this->BD = $BD;
  }

  /**
   * Elle permet l'execution de toutes les requetes à conditions que cette derniere soit dans un format valide.
   *
   * @format select.
   */
  function applySelect($selects)
  {
    $results = [];
    foreach ($selects as $key => $select) {
      $results[$key] = $this->getSelect($select['req'], $select['filters']);
    }
    return $results;
  }

  /**
   *
   * @param string $req
   * @param array $filters
   */
  protected function getSelect(string $req, array $filters)
  {
    $where = '';
    if (! empty($filters)) {
      ;
    }
    return $this->BD->CustomRequest($req . ' ' . $where);
  }

  public function buildInserts($inserts)
  {
    $MysqlError = new MysqlError();
    $results = [];
    foreach ($inserts as $insert) {
      if (! empty($insert['table']) && ! empty($insert['fields']) && empty($insert['where'])) {
        $query = $this->BD->insert($insert['table'], $insert['fields']);
        $results[$insert['table']][] = $this->analyseError($MysqlError, $query, 'insert');
      } elseif (! empty($insert['table']) && ! empty($insert['fields']) && ! empty($insert['where'])) {
        $this->BD->Where = $insert['where'];
        $query = $this->BD->update($insert['table'], $insert['fields']);
        $results[$insert['table']][] = $this->analyseError($MysqlError, $query, 'update');
      }
    }
    return $results;
  }

  protected function analyseError(MysqlError $MysqlError, $query, $action)
  {
    $result = [];
    if ($MysqlError->analyseError($query)) {
      $this->hasError = true;
      if (! $this->FirstError) {
        $this->FirstError = $MysqlError->getCustomMessage();
      }
      $this->LastId = null;
      $result = [
        'message' => $MysqlError->getCustomMessage(),
        'status' => false,
        'query-message' => $query,
        'action' => $action
      ];
    } else {
      $this->LastId = $query;
      $result = [
        'message' => $query,
        'status' => true,
        'query-message' => $query,
        'action' => $action
      ];
    }
    return $result;
  }

  public function getLastId()
  {
    return $this->LastId;
  }

  public function FirstErrors()
  {
    return $this->FirstError;
  }

  public function hasErrors()
  {
    return $this->hasError;
  }
}