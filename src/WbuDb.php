<?php

namespace Query;

use PDO;
use Stephane888\Debug\ExceptionExtractMessage;

class WbuDb {
  /**
   * Nom d'utilisateur
   *
   * @var string
   */
  public static $user;
  /**
   *
   * @var string
   */
  public static $password;
  /**
   *
   * @var string
   */
  public static $dbName;
  public static $host = 'localhost';
  /**
   * si la valeur est false, vous devez effectuer le commit manuellement pour
   * valider la sauvegarde.
   * ( Dans la pluspart des utilisations mettez la valeur true.
   *
   * @var boolean
   */
  public static $autocommit = false;
  /**
   * Permet de construire sa propre connexion.
   *
   * @var PDO
   */
  /**
   * Permet de sauvegarder l'object de connexion.
   *
   * @var PDO
   */
  public static $BDD;
  /**
   * Format à utiliser, utf8, utf8mb4, ...
   * NB : utiliser le format utf8mb4 tant que possible. car utf8 est deprecié.
   *
   * @deprecated ( rappel pour changer la valeur utf8 en utf8mb4 à la version
   *             4x).
   * @see https://habeuk.com/fr/node/84
   * @var string
   */
  public static $format = "utf8";
  /**
   * Contient les informations sur la requete executée.
   *
   * @var mixed
   */
  private static $LastQuery;
  
  /**
   *
   * @return \PDO
   */
  protected static function connectParam() {
    // Permet d'initialiser la requete avec des paramettres specifique.
    if (self::$BDD)
      return self::$BDD;
    
    // On se connecte
    if (self::$autocommit) {
      /**
       * On ne sauvegarde pas l'object $bdd afin de permettre d'initialiser
       * plusieurs connexion durant un cycle.
       *
       * @var PDO $bdd
       */
      $bdd = new PDO('mysql:host=' . self::$host . ';dbname=' . self::$dbName, self::$user, self::$password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="TRADITIONAL"'
      ]);
      $bdd->exec("set names " . self::$format);
    }
    else {
      /**
       * On sauvegarde la connexion dans (self::$BDD) afin de ne pas la modifié
       * tout au long du processus.
       */
      if (empty(self::$BDD)) {
        $bdd = new PDO('mysql:host=' . self::$host . ';dbname=' . self::$dbName, self::$user, self::$password, array(
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="TRADITIONAL"'
        ));
        $bdd->exec("set names " . self::$format);
        $bdd->beginTransaction();
        self::$BDD = $bdd;
      }
      $bdd = self::$BDD;
    }
    return $bdd;
  }
  
  /**
   *
   * @param mixed $req
   * @param mixed $arg
   * @param string $type
   * @return array|mixed|mixed
   */
  public static function selectPrepare($req, $arg = [], $type = '') {
    try {
      // On se con# code...necte
      $bdd = self::connectParam();
      
      // On prépare la requête
      $requete = $bdd->prepare($req);
      
      //
      foreach ($arg as $k => $j) {
        $requete->bindValue($k, $j, PDO::PARAM_STR);
      }
      
      // On sauvegarde la requête
      self::$LastQuery = [
        'sql' => $requete->queryString,
        'arg' => $arg
      ];
      
      // On exécute la requête
      $requete->execute();
      
      // On récupère le résultat
      if ($type == '') {
        $result = $requete->fetchAll(PDO::FETCH_ASSOC);
      }
      else {
        $result = $requete->fetch(PDO::FETCH_ASSOC);
      }
      $bdd = null;
      return $result;
    }
    catch (\Exception $e) {
      return ExceptionExtractMessage::errorMessage($e, 0);
    }
  }
  
  /**
   * Declenche une erreur PHP au cas ou.
   *
   * @param mixed $req
   * @param mixed $arg
   * @param string $type
   * @return array|mixed|mixed
   */
  public static function selectPrepareV2($req, $arg = [], $AllRows = true) {
    // On se connecte
    $bdd = self::connectParam();
    // On prépare la requête
    $requete = $bdd->prepare($req);
    
    // On lie la variable $email définie au-dessus au paramètre :email de la
    // requête préparée
    foreach ($arg as $k => $j) {
      $requete->bindValue($k, $j, PDO::PARAM_STR);
    }
    
    // On sauvegarde la requête
    self::$LastQuery = [
      'sql' => $requete->queryString,
      'arg' => $arg
    ];
    
    // On exécute la requête
    $requete->execute();
    // On récupère le résultat
    if ($AllRows) {
      $result = $requete->fetchAll(PDO::FETCH_ASSOC);
    }
    else {
      $result = $requete->fetch(PDO::FETCH_ASSOC);
    }
    $bdd = null;
    return $result;
  }
  
  /**
   * query
   *
   * @param mixed $req
   * @param mixed $arg
   * @return number|mixed
   */
  public static function updatePrepare($req, $arg) {
    try {
      // On se connecte
      $bdd = self::connectParam();
      // On prépare la requête
      $requete = $bdd->prepare($req);
      
      // On lie la variable $email définie au-dessus au paramètre :email de la
      // requête préparée
      foreach ($arg as $k => $j) {
        $requete->bindValue($k, $j, PDO::PARAM_STR);
      }
      
      // On sauvegarde la requête
      self::$LastQuery = [
        'sql' => $requete->queryString,
        'arg' => $arg
      ];
      
      // On exécute la requête
      $requete->execute();
      // \customapi\debugLog::logs($rt, 'execute_up');
      // \customapi\debugLog::logs($requete->rowCount(), 'rowCount');
      // return $rt;// true or false
      // return number line updated
      $result = $requete->rowCount();
      $bdd = null;
      return $result;
    }
    catch (\Exception $e) {
      return ExceptionExtractMessage::errorMessage($e, 0);
    }
  }
  
  /**
   *
   * @param mixed $req
   * @param mixed $arg
   * @return number|mixed
   */
  public static function update($req, $arg) {
    // On se connecte
    $bdd = self::connectParam();
    // On prépare la requête
    $requete = $bdd->prepare($req);
    
    // On lie la variable $email définie au-dessus au paramètre :email de la
    // requête préparée
    foreach ($arg as $k => $j) {
      $requete->bindValue($k, $j, PDO::PARAM_STR);
    }
    
    // On exécute la requête
    $requete->execute();
    // \customapi\debugLog::logs($rt, 'execute_up');
    // \customapi\debugLog::logs($requete->rowCount(), 'rowCount');
    // return $rt;// true or false
    // return number line updated
    $result = $requete->rowCount();
    $bdd = null;
    return $result;
  }
  
  public static function deletePrepare($req) {
    try {
      $bdd = self::connectParam();
      $requete = $bdd->prepare($req);
      
      // On sauvegarde la requête
      self::$LastQuery = $requete->queryString;
      
      $requete->execute();
      $result = $requete->rowCount();
      $bdd = null;
      return $result;
    }
    catch (\Exception $e) {
      return ExceptionExtractMessage::errorMessage($e, 0);
    }
  }
  
  public static function getConnectParam() {
    return self::connectParam();
  }
  
  /**
   * Capture l'erreur.
   * ( Utile dans les boucles ).
   * http://www.mustbebuilt.co.uk/php/insert-update-and-delete-with-pdo/
   *
   * @param mixed $req
   * @param mixed $arg
   * @return number|mixed
   */
  public static function insertPrepare($req, $arg) {
    try {
      return self::insert($req, $arg);
    }
    catch (\Exception $e) {
      return ExceptionExtractMessage::errorMessage($e, 0);
    }
  }
  
  /**
   * Permet D'ajouter les données en BD.
   *
   * @param string $req
   * @param array $arg
   * @return int
   */
  public static function insert(string $req, Array $arg) {
    // On se connecte
    $bdd = self::connectParam();
    // On prépare la requête
    $requete = $bdd->prepare($req);
    
    // On lie la variable $email définie au-dessus au paramètre :email de la
    // requête préparée
    foreach ($arg as $k => $j) {
      $requete->bindValue($k, $j, PDO::PARAM_STR);
    }
    
    // On sauvegarde la requête
    self::$LastQuery = [
      'sql' => $requete->queryString,
      'arg' => $arg
    ];
    
    // On exécute la requête
    $requete->execute();
    $insert = ($bdd->lastInsertId()) ? $bdd->lastInsertId() : $requete->rowCount();
    // \customapi\debugLog::logs($insert, 'lastInsertId');
    // \customapi\debugLog::logs($requete->rowCount(), 'lastInsertId2');
    // return last insert id
    $bdd = null;
    return $insert;
  }
  
  /**
   * Return executed query
   */
  public static function getQuery() {
    return self::$LastQuery;
  }
  
}