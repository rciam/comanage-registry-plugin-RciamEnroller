<?php

global $cm_lang, $cm_texts;
// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.
$cm_rciam_enroller_texts['en_US'] = array(
  // Titles, per-controller
  'ct.rciam_enroller.1'                        => 'RCIAM Enroller Plugin',
  'ct.rciam_enroller.2'                        => 'RCIAM Enroller',
  'ct.rciam_enroller.pl'                       => 'RCIAM Enroller Plugin',
  'ct.rciam_enroller.info'                     => 'This plugin will run each time an Enrollment Flow starts. The supported flows must be selected in this configuration page. Otherwise the plugin will be skipped.',
  // Error messages
  'er.rciam_enroller.search'                   => 'Search request returned %1$s',
  'er.rciam_enroller.no_remote_user'           => 'Remote User was empty.',
  'er.rciam_enroller.no_cert'                  => 'No Linked Certificate.',
  'er.rciam_enroller.low_cert'                 => 'Certificate with in-adequate Assurance Level.',
  // Plugin text
  'pl.rciam_enroller.co_name'                  => 'CO Name',
  'pl.rciam_enroller.co_name.desc'             => 'This is the CO Name the enroller plugin belongs to',
  'pl.rciam_enroller.flow'                     => 'Enrollment Flow',
  'pl.rciam_enroller.flow.desc'                => 'Choose the Enrollment Flows to enable the plugin',
  'pl.rciam_enroller.attribute'                => 'Attribute',
  'pl.rciam_enroller.attribute.desc'           => 'This attribute will be checked for duplicates',
  'pl.rciam_enroller.available_users'          => 'Available Users',
  'pl.rciam_enroller.nocert_msg'               => 'Info Message',
  'pl.rciam_enroller.nocert_msg.desc'          => 'Optional text to display if no Certificate is available.',
  'pl.rciam_enroller.vos_assurance_level'      => 'VOs Assurance Prerequisite',
  'pl.rciam_enroller.vos_assurance_level.desc' => 'Optional list of the required level of Assurance per VO/COU',
  'pl.rciam_enroller.lowcert_msg'              => 'Info Message',
  'pl.rciam_enroller.lowcert_msg.desc'         => 'Optional text to display if Low Assurance Certificate is available.',
  'pl.rciam_enroller.return_target'            => 'Return parameter',
  'pl.rciam_enroller.return_target.desc'       => 'This is the return query parameter with the Service URL. At the end of linking we will redirect at the url stored in this parameter.',
  'pl.rciam_enroller.redirect_url'             => 'No Certificate URL',
  'pl.rciam_enroller.redirect_url.desc'        => 'Specify a URL to redirect to if the action fails due to no Certificate.',
  'pl.rciam_enroller.low_redirect_url'         => 'Bad Certificate URL',
  'pl.rciam_enroller.low_redirect_url.desc'    => 'Specify a URL to redirect to if the action fails due to Low Assurance Certificate.',
  'pl.rciam_enroller.forbid_duplicate_eof'     => 'Forbid Duplicate Enrollment Flow',
  'pl.rciam_enroller.forbid_duplicate_eof.desc'=> 'If enabled, it will not allow the user to make a new enrollement flow for the same COU if there is already one in progress',
  'pl.rciam_enroller.return'                   => 'Return to Profile',
  'pl.rciam_enroller.redirecturl'              => 'Get RCAuth Certificate',
  'pl.rciam_enroller.lowcert.redirecturl'      => 'Enroll to catch-All VO',
  'pl.rciam_enroller.name_lbl'                 => 'Name:',
  'pl.rciam_enroller.action_lbl'               => 'Actions:',

  // Database
  'rs.rciam_enroller.error'                    => 'Save failed',
  'rs.rciam_enroller_eof.deleted'              => 'Entry Deleted',

  
  'fd.rciam_enroller.user'                     => 'User',
);