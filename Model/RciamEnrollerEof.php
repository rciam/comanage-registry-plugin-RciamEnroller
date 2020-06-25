<?php

class RciamEnrollerEof extends AppModel
{
  // Define class name for cake
  public $name = "RciamEnrollerEof";
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    'CoEnrollmentFlow',
    'RciamEnroller',
  );

  public $hasMany = array(
    // An Enrollment Flow can have one or more actions
    'RciamEnrollerAction' => array(
      'dependent' => true,
      'foreignKey' => 'rciam_enroller_eof_id',
      'className' => 'RciamEnrollerAction',
    ),
  );
  
  // Validation rules for table elements
  public $validate = array(
    'co_enrollment_flow_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'An enrollment flow must be provided.',
    ),
    'rciam_enroller_id' => array(
      'rule' => 'numeric',
      'notBlank' => true,
      'message' => 'A Link Enroller config id must be provided.',
    ),
  );
}