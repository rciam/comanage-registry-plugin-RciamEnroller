<?php

class RciamEnrollerAction extends AppModel
{
  // Define class name for cake
  public $name = "RciamEnrollerAction";
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    'RciamEnrollerEof' => array(
      'foreignKey' => 'rciam_enroller_eof_id',
    ),
  );
  
  // Validation rules for table elements
  public $validate = array(
    'type' => array(
      'rule' => array('inList',
        array(
          RciamActionsEnum::LinkedCertificate,
        )
      ),
      'required' => true,
      'message' => 'An enrollment flow must be provided.',
    ),
    'rciam_enroller_eof_id' => array(
      'rule' => 'numeric',
      'notBlank' => true,
      'message' => 'A Enroller EOF config id must be provided.',
    ),
  );
}