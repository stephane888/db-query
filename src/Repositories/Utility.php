<?php
namespace Query\Repositories;

class Utility {

  public static $result;

  /**
   * Return les parametres pour la connection à la BD;
   *
   * @param array $configDataBase
   * @param string $databaseType
   * @return array
   */
  public static function checkCredentiel($configDataBase, $databaseType)
  {
    if (! empty($databaseType)) {
      if ($_SERVER['SERVER_ADDR'] == "127.0.0.1") {
        $databaseType = 'localhost';
      } elseif (empty($configDataBase[$databaseType])) {
        die('Base de donnée non definit');
      }
      self::$result['base de donée'][] = $configDataBase[$databaseType];
      return $configDataBase[$databaseType];
    } else {
      die('Echec de configuration de la BD');
    }
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
   * Le filtres est construit sans le Where.
   *
   * @param array $filters
   * @return string
   */
  public static function buildFilterSql(array $filters)
  {
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
        if (! empty($value['value'])) {
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