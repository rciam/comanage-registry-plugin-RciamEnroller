<?php


App::uses('AppHelper', 'View/Helper');

class RciamHelper extends AppHelper
{
  public function createIdProperty($value)
  {
    $valToLower = strtolower($value);
    return str_replace(" ", "_", $valToLower);
  }
}