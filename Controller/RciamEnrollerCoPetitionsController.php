<?php

App::uses('CoPetitionsController', 'Controller');
App::uses('Cache', 'Cache');
App::uses('CakeEmail', 'Network/Email');

class RciamEnrollerCoPetitionsController extends CoPetitionsController
{
  // Class name, used by Cake
  public $name = "RciamEnrollerCoPetitions";

  public $components = array(
    'Security' => array(
      'csrfUseOnce' => true,
    )
  );
  private $redirect_location = "/";

  public $uses = array(
    "CoEnrollmentFlow",
    "CoPetition", // this is mandatory for the enroller plugin
    "RciamEnroller.RciamEnroller");

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
    $eof_ea = $this->CoEnrollmentFlow->find('first', $args);
    unset($args);

    // Get the configuration
    $loiecfg = $this->RciamEnroller->getConfiguration($eof_ea['CoEnrollmentFlow']['co_id']);

    /*
     * Redirect onFinish, in the case that the plugin is:
     * 1. disabled
     * 2. No EOFs have been picked
     * 3. The EOF that invoked the plugin is not part of the list picked EOFs
     */
    if (empty($loiecfg['RciamEnrollerEof_list'])
        || !array_key_exists($this->request->params['named']['coef'], $loiecfg['RciamEnrollerEof_list'])
        || $loiecfg['RciamEnroller']['status'] != RciamStatusEnum::Active) {
      $this->log(__METHOD__ . "::Plugin is not supported for this enrollment ", LOG_DEBUG);
      $this->redirect($onFinish);
    }

    $orgIdentities = $this->RciamEnroller->getCoPersonOrgIdentitiesContain($_SESSION["Auth"]["external"]["user"]);

    // if there are no registered users continue with the enrollment
    if (empty($orgIdentities)
        && $eof_ea["CoEnrollmentFlow"]["authz_level"] === EnrollmentAuthzEnum::AuthUser) {
      // todo: This user has no orgIdentities. If the EOf is not for Authenticated users redirect to signup
      // todo: Add as target new the current eof. Perhaps this will solve the problem of registering users to EGI first and then to COU
      // Current petition add to query target
      $target_new_params = [
        'controller' => Inflector::tableize($this->plugin),
        'plugin' => Inflector::singularize(Inflector::tableize($this->plugin)),
        'action' => 'start',
        'coef' => (int)$onFinish['coef'],
        ];
      if (!empty($this->request->query)) {
        $target_new_params['?'] = urlencode($this->request->query);
      }
      $target_new = Router::url($target_new_params);
      $this->redirect($fullBsUrl . '/registry/signup?target_new=' . urlencode($fullBsUrl . $target_new));
    }

    // Get Enrollment Flows Actions
    $eof_actions = $loiecfg["RciamEnrollerAction_list"][$loiecfg["RciamEnrollerEof_list"][$this->request->params['named']['coef']]];
    if(empty($eof_actions)) {
      $this->redirect($onFinish);
    }

    // Certificate Action
    if($eof_actions === RciamActionsEnum::LinkedCertificate) {
      $certificates = Hash::extract($orgIdentities, '{n}.Cert.{n}.subject');
      if (empty($certificates)) {
        // Redirect to modal page
        $nocert_redirect = [
          'controller' => 'rciam_enrollers',
          'plugin' => Inflector::singularize(Inflector::tableize($this->plugin)),
          'action' => 'nocert',
          'co' => (int)$eof_ea['CoEnrollmentFlow']['co_id'],
        ];
        $this->redirect($nocert_redirect);
      }
    }

    $this->redirect($onFinish);
  }

  /**
   * Process petitioner attributes
   *
   * @since  COmanage Registry v0.9.4
   */
  protected function execute_plugin_petitionerAttributes($id, $onFinish)
  {
    // The step is done
    $this->redirect($onFinish);
  }


  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @return Array Permissions
   * @since  COmanage Registry v3.1.0
   */

  function isAuthorized()
  {
    $roles = $this->Role->calculateCMRoles();

    $petitionId = $this->parseCoPetitionId();
    $curToken = null;

    // For self signup, we simply require a token (and for the token to match)
    if ($petitionId) {
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
    $p['selectOrgIdentityAuthenticate'] = $roles['copersonid'];

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
