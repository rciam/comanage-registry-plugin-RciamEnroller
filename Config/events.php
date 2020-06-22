<?php
// Load the Local listeners we created
require_once LOCAL . 'Plugin' . DS . 'RciamEnroller' . DS . 'Lib' . DS . 'Event' . DS . 'RciamListener.php';
// Load the frameworks Utility library
App::uses('ClassRegistry', 'Utility');

// Attach the RciamListener to event Model.afterDelete for the specific Model
// CoEnrollmentFlow
$CoEnrollmentFlow = ClassRegistry::init('CoEnrollmentFlow');
$rciam_listener = new RciamListener();
$CoEnrollmentFlow->getEventManager()->attach($rciam_listener);
