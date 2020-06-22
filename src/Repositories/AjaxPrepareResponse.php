<?php
namespace Query\Repositories;

class AjaxPrepareResponse {

  public static $codeSuccess;

  public static $codeError;

  public static function successRequest($datas, $title, $code = 200)
  {
    $code = 200;
    if (! empty(self::$codeSuccess)) {
      $code = self::$codeSuccess;
    }
    return [
      'datas' => $datas,
      'code' => $code,
      'title' => $title
    ];
  }

  public static function failureRequest($datas, $title, $code = 499)
  {
    if (! empty(self::$codeError)) {
      $code = self::$codeError;
    }
    return [
      'datas' => $datas,
      'code' => $code,
      'title' => $title
    ];
  }
}