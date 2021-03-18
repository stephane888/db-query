<?php
namespace Query\Repositories;

use DateTime;
use Stephane888\Debug\debugLog;
use PhpParser\Error;

class Utility {

  public static $result;

  /**
   * Return les parametres pour la connection à la BD;
   * wb-universe est considere comme le du serveur en local.
   *
   * @param array $configDataBase
   * @param string $databaseType
   * @return array
   */
  public static function checkCredentiel($configDataBase, $databaseType)
  {
    if (! empty($databaseType)) {
      if ($_SERVER['SERVER_ADDR'] == "127.0.0.1" || "wb-universe" == gethostname()) {
        $databaseType = 'localhost';
      } elseif (empty($configDataBase[$databaseType])) {
        throw new Error('Base de donnée non definit');
      }
      self::$result['base de donée'][] = $configDataBase[$databaseType];
      return $configDataBase[$databaseType];
    } else {
      throw new Error('Echec de configuration de la BD');
    }
  }

  /**
   *
   * @param DateTime $date
   * @param string $format
   * @return string
   */
  public static function formatDateToMysql(DateTime $date, $format = "Y-m-d h:i:s")
  {
    return $date->format($format);
  }

  /**
   *
   * @param String $date
   * @param string $format
   * @return DateTime|false
   */
  public static function ValideDate($date, $format = "d-m-Y H:i:s")
  {
    $date = DateTime::createFromFormat($format, $date);
    if ($date) {
      return $date;
    } else {
      return false;
    }
  }

  public static function DateTimegetLastErrors()
  {
    return DateTime::getLastErrors();
  }

  public static function getColumnInfo(&$filters, $column, $operateur, $removeColumn = false)
  {
    if (! empty($filters['AND'])) {
      foreach ($filters['AND'] as $key => $value) {
        if ($value['column'] == $column && $value['operator'] == $operateur) {
          if ($removeColumn) {
            unset($filters['AND'][$key]);
          }
          return $value;
        }
      }
    }
    return false;
  }

  /**
   * Permet de changer le nom d'une colonne.
   *
   * @param array $filters
   * @param string $column
   * @param string $new_column
   */
  public static function ChangeNameColumn(&$filters, $column, $new_column)
  {
    if (! empty($filters['AND'])) {
      foreach ($filters['AND'] as $key => $value) {
        if ($value['column'] == $column) {
          $filters['AND'][$key]['column'] = $new_column;
        }
      }
    }
    if (! empty($filters['OR'])) {
      foreach ($filters['OR'] as $key => $value) {
        if ($value['column'] == $column) {
          $filters['OR'][$key]['column'] = $new_column;
        }
      }
    }
  }

  /**
   * Le filtres est construit sans le Where.
   *
   * @param array $filters
   * @return string
   */
  public static function buildFilterSql(array $filters)
  {
    // debugLog::saveJson($filters, 'buildFilterSql' . time());
    $sql = '';
    if (! empty($filters['AND'])) {
      $sql .= ' ' . self::buildFilterSql__AND($filters['AND']) . ' ';
    }
    if (! empty($filters['OR'])) {
      if ($sql == '') {
        $sql .= ' ' . self::buildFilterSql__OR($filters['OR']) . ' ';
      } else {
        $sql .= ' AND ( ' . self::buildFilterSql__OR($filters['OR']) . ' ) ';
      }
    }
    return $sql;
  }

  protected static function buildFilterSql__AND($filters)
  {
    $sql = '';
    foreach ($filters as $value) {
      if (! empty($value['column'])) {
        $prefix = (! empty($value['preffix'])) ? $value['preffix'] . '.' : '';
        $sql .= ' ' . $prefix . '`' . $value['column'] . '` ';
        if (! empty($value['operator'])) {
          $sql .= $value['operator'];
        } else {
          $sql .= '=';
        }

        if ($value['value'] === 0 || $value['value'] === "0") {
          $sql .= 0;
        } elseif (! empty($value['value'])) {
          if (trim($value['operator']) == 'LIKE') {
            $valeur = " '\%" . $value['value'] . "\%' ";
            $valeur = str_replace("\%", "%", $valeur);
            // $sql .= " '$valeur' ";
            $sql .= " '%" . $value['value'] . "%' ";
          } elseif (trim($value['operator']) == 'IN' || trim($value['operator']) == 'NOT IN') {
            $sql .= " ('" . $value['value'] . "') ";
          } else {
            $sql .= " '" . $value['value'] . "' ";
          }
        } elseif ($value['value'] === NULL) {
          $sql .= " NULL ";
        }
        $sql .= ' AND ';
      }
    }
    $sql = \trim($sql, ' AND ');
    return $sql;
  }

  protected static function buildFilterSql__OR($filters)
  {
    $sql = '';
    foreach ($filters as $value) {
      if (! empty($value['column'])) {
        $sql .= ' `' . $value['column'] . '` ';
        if (! empty($value['operator'])) {
          $sql .= $value['operator'];
        } else {
          $sql .= '=';
        }
        if (! empty($value['value'])) {
          $sql .= "'" . $value['value'] . "'";
        }
        $sql .= ' OR ';
      }
    }
    $sql = \trim($sql, ' OR ');
    return $sql;
  }
}