<?php

namespace Query\Repositories;

use Query\WbuJsonDb;
use Stephane888\Debug\debugLog;
use Symfony\Component\Cache\Adapter\FilesystemAdapter as FilesystemCache;

class Bootstrap {
  protected $filters = [];
  protected $defualtFilters = [];
  protected $querys = [];
  protected $ErrorMessage = [
    'var' => [
      'label' => 'Une ou plusieurs variable non definit'
    ]
  ];
  
  /**
   *
   * @var string
   */
  protected $query = '';
  protected $BD;
  protected $codeAjax = 200;
  protected $TitleAjax;
  private $cacheTime = 3600;
  protected $cache;
  
  function __construct(WbuJsonDb $BD) {
    $this->BD = $BD;
  }
  
  protected function initCache($namespace) {
    if (!$namespace) {
      throw new \Exception("Vous devez une valeur pour activer le cache.");
    }
    if ($_SERVER['SERVER_ADDR'] == "127.0.0.1") {
      $this->cacheTime = 86400;
    }
    $this->cache = new FilesystemCache($namespace, $this->cacheTime);
  }
  
  protected function BuildError($titleError = null, $code = null, $e = null) {
    $this->codeAjax = 400;
    $this->TitleAjax = "une errreur s'est produite";
  }
  
  protected function selectData($query, $ref) {
    $this->query = $query;
    $this->saveQuery($this->query, $ref);
    $result = $this->BD->CustomRequest($this->query);
    if (!empty($result['PHP_execution_error'])) {
      $errors = [
        'name' => $ref,
        'error' => $result
      ];
      $filename = 'selectData__debug-' . date('m-Y');
      debugLog::saveLogs($errors, $filename);
      return null;
    }
    else {
      return $result;
    }
  }
  
  protected function selectOneData($query, $ref) {
    $this->query = $query;
    $this->saveQuery($this->query, $ref);
    $result = $this->BD->queryFirstRow($this->query);
    if (!empty($result['PHP_execution_error'])) {
      $errors = [
        'name' => $ref,
        'error' => $result
      ];
      $filename = 'selectData__debug-' . date('m-Y');
      debugLog::saveLogs($errors, $filename);
      return null;
    }
    else {
      return $result;
    }
  }
  
  /**
   * ;
   *
   * @return array
   */
  public function getQuerys() {
    return $this->querys;
  }
  
  /**
   *
   * @param string $query
   * @param string $name
   */
  protected function saveQuery($query, $name) {
    $this->querys[$name][] = $query;
  }
  
  public function getCodeAjax() {
    return $this->codeAjax;
  }
  
  public function getTitleAjax() {
    return $this->TitleAjax;
  }
  
  protected function checkSqlError() {
    if (!empty($this->BD->lastErrorInfo) && $this->BD->hasError()) {
      $this->codeAjax = 400;
      $this->TitleAjax = $this->BD->lastErrorInfo['message'];
    }
  }
  
}