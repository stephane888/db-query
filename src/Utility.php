<?php
namespace Query;

/**
 *
 * @deprecated cette ne doit plus etre utiliser, utiliser Query\Repositories\Utility;
 * @author stephane
 *
 */
class Utility {

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
        $sql .= ' `' . $value['column'] . '` ';
        if (! empty($value['operator'])) {
          $sql .= $value['operator'];
        } else {
          $sql .= '=';
        }
        if (! empty($value['value'])) {
          if ($value['operator'] == 'LIKE') {
            $valeur = " '\%" . $value['value'] . "\%' ";
            $valeur = str_replace("\%", "%", $valeur);
            // $sql .= " '$valeur' ";
            $sql .= " '%" . $value['value'] . "%' ";
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
