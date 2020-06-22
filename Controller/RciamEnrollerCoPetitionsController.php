<?php

App::uses('CoPetitionsController', 'Controller');
App::uses('Cache', 'Cache');
App::uses('CakeEmail', 'Network/Email');

class RciamEnrollerCoPetitionsController extends CoPetitionsController
{
  // Class name, used by Cake
  public $name = "RciamEnrollerCoPetitions";
  public $in_reauth = false;
  public $components = array(
    'Security' => array(
      'csrfUseOnce' => true,
    )
  );
  private $redirect_location = "/";
  
  public $uses = array(
    "CoEnrollmentFlow",
    "OrgIdentity",
    "CoGroupMember",
    "CoGroup",
    "AuthenticationEvent",
    "CoInvite",
    "CoPetition", // this is mandatory for the enroller plugin
    "RciamEnroller.RciamEnroller",
    "RciamEnroller.RciamState");
  
  /**
   * Enrollment Flow selectOrgIdentity (authenticate mode)
   *
   * @param Integer $id CO Petition ID
   * @param Array $oiscfg Array of configuration data for this plugin
   * @param Array $onFinish URL, in Cake format
   * @param Integer $actorCoPersonId CO Person ID of actor
   * @since  COmanage Registry v2.0.0
   */
  protected function execute_plugin_start($id, $onFinish)
  {
    $this->log(__METHOD__ . "::@", LOG_DEBUG);
    $fullBsUrl = Configure::read('App.fullBaseUrl');
    
    /*
     *  THIS IS THE ENTRY OF THE FIRST PASS
     * */
    // Get the EnrollmentFlows list and the CoEnrollmentAttributes
    $args = array();
    $args['conditions']['CoEnrollmentFlow.id'] = $this->request->params['named']['coef'];
    $args['conditions'][] = 'not CoEnrollmentFlow.deleted';
    $args['contain'] = array('CoEnrollmentAttribute');
    $args['fields'] = array(
      'CoEnrollmentFlow.co_id',
      'CoEnrollmentFlow.name',
      'CoEnrollmentFlow.authz_level',
      'CoEnrollmentFlow.match_policy');
    $eof_ea = $this->CoEnrollmentFlow->find('first',$args);
    unset($args);
    
    // Get the configuration
    $loiecfg = $this->RciamEnroller->getConfiguration($eof_ea['CoEnrollmentFlow']['co_id']);

   /*
    * Redirect onFinish, in the case that the plugin is:
    * 1. disabled
    * 2. No EOFs have been picked
    * 3. The EOF that invoked the plugin is not part of the list picked EOFs
    */
    if ( empty($loiecfg['RciamEof_list']) ||
         !array_key_exists($this->request->params['named']['coef'], $loiecfg['RciamEof_list']) ||
         $loiecfg['RciamEnroller']['status'] != RciamStatusEnum::Active){
      $this->log(__METHOD__ . "::Plugin is not supported for this enrollment ", LOG_DEBUG);
      $this->redirect($onFinish);
    }


//    // If we want to add and idp in the registered user, means that the auth array in the Session will have CO entries and won't be empty
//    // Now that i have the type and value i should check the registry
//    list($registrations, $orgIdentities_list) = $this->RciamEnroller->getCoPersonMatches($attribute_type, $attribute_value, $eof_ea['CoEnrollmentFlow']['co_id']);
//
//    // if there are no registered users continue with the enrollment
//    if(empty($registrations)){
//      $this->redirect($onFinish);
//    }
//
//    $target = array();
//    $this->Session->write('Auth.User.Registrations', $registrations);
//    $this->Session->write('Auth.User.OrgIdentities', $orgIdentities_list);
//    if($eof_ea['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::AuthUser ) {
//       $target['action'] = 'link';
//       $target['controller'] = Inflector::tableize($this->plugin);
//    }else if($eof_ea['CoEnrollmentFlow']['authz_level'] != EnrollmentAuthzEnum::AuthUser &&
//             $eof_ea['CoEnrollmentFlow']['authz_level'] != EnrollmentAuthzEnum::None &&
//             $eof_ea['CoEnrollmentFlow']['match_policy'] == EnrollmentMatchPolicyEnum::Self){
//      $target['action'] = 'logout';
//      $target['controller'] = Inflector::tableize($this->name);
//    } else if($eof_ea['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::None ) {
//      $this->redirect($onFinish);
//    }
//
//    $target['plugin'] = Inflector::singularize(Inflector::tableize($this->plugin));
//    $target['co'] = (int)$eof_ea['CoEnrollmentFlow']['co_id'];
//    $target['coef'] = (int)$onFinish['coef'];
//    $target['cfg'] = (int)$loiecfg['RciamEnroller']['id'];
//    if (!empty($this->request->query)) {
//      $target['?'] = $this->request->query;
//    }
//
//    $this->redirect($target);
    $this->redirect($onFinish);
  }
  
  /**
   * Process petitioner attributes
   *
   * @since  COmanage Registry v0.9.4
   */
  protected function execute_plugin_petitionerAttributes($id, $onFinish) {
    // The step is done
    $this->redirect($onFinish);
  }
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: If invalid enrollment flow provided, session flash message set
   *
   * @since  COmanage Registry v3.1.0
   */
  
//  function beforeFilter() {
//    // We need some special authorization logic here, depending on the type of flow.
//    // This is loosely based on parent::beforeFilter().
//    $noAuth = false;
//
//    // For self signup, we simply require a token (and for the token to match).
//    if( $this->action == "processConfirmation"
//      || $this->action == "collectIdentifier"
//      || $this->action == "duplicateCheck"
//      || $this->action == "checkEligibility"
//      || $this->action == "sendApprovalNotification"
//      || $this->action == "finalize"
//      || $this->action == "provision"
//      || $this->action == "redirectOnConfirm") {
//      $token = $this->CoPetition->field('enrollee_token', array('CoPetition.id' => $this->parseCoPetitionId()));
//    } else if ( $this->action == "start" &&
//                isset($this->request->params['named']['eaf']) &&
//                $this->request->params['named']['eaf'] == 1) {
//      if (!empty($this->RciamState->getStateByToken($this->request->params['named']['token']))) {
//        //TODO: improve the security here
//        $token = $this->request->params['named']['token'];
//        $this->Security->validatePost = false;
//        $this->Security->enabled = false;
//        $this->Security->csrfCheck = false;
//      }
//    }
//    else {
//      $token = $this->CoPetition->field('petitioner_token', array('CoPetition.id' => $this->parseCoPetitionId()));
//    }
//    $passedToken = $this->parseToken();
//
//    if($token && $token != '' && $passedToken) {
//      if($token == $passedToken) {
//        // If we were passed a reauth flag, we require authentication even though
//        // the token matched. This enables account linking.
//        if(!isset($this->request->params['named']['reauth'])
//          || $this->request->params['named']['reauth'] != 1) {
//          $noAuth = true;
//        } else {
//          // Store a hint for isAuthorized that we matched the token and are reauthenticating,
//          // so we can authorize the transaction.
//          $this->in_reauth = true;
//        }
//
//        // Dump the token into a viewvar in case needed
//        $this->set('vv_petition_token', $token);
//      } else {
//        $this->Flash->set(_txt('er.token'), array('key' => 'error'));
//        $this->redirect($this->redirect_location);
//      }
//    }
//
//    if($noAuth) {
//      $this->Auth->allow($this->action);
//    }
//  }
  
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    $petitionId = $this->parseCoPetitionId();
    $curToken = null;
  
    // For self signup, we simply require a token (and for the token to match)
    if($petitionId) {
      $curToken = $this->CoPetition->field('petitioner_token', array('CoPetition.id' => $this->parseCoPetitionId()));
    }
    
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Signup based collection, we need the user in the petition.
    // Note we can't invalidate this token because for the duration of the enrollment
    // $REMOTE_USER may or may not match a valid login identifier (though probably it should).
    $p['start'] = ($curToken == $this->parseToken());
    // Here we need a valid user
    $p['petitionerAttributes'] = empty($this->Session->check('Auth.User')) ?
                                 false :
                                 $this->Session->check('Auth.User');
  
    // Probably an account linking being initiated, so we need a valid user
    $p['selectOrgIdentityAuthenticate'] = $roles['copersonid'] || $this->in_reauth;
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
