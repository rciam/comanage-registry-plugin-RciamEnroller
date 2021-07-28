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
    "RciamEnroller.RciamEnroller", // XXX There is a bug and this should go first
    "CoPetition", // this is mandatory for the enroller plugin
    "CoEnrollmentFlow");

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
    $args['contain'][] = 'CoEnrollmentAttribute';
    $args['contain']['CoEnrollmentAttribute'][] = 'CoEnrollmentAttributeDefault';
    $args['fields'] = array(
      'CoEnrollmentFlow.co_id',
      'CoEnrollmentFlow.name',
      'CoEnrollmentFlow.authz_level',
      'CoEnrollmentFlow.match_policy');
    $eof_ea = $this->CoEnrollmentFlow->find('first', $args);
    unset($args);

    // Get the configuration
    $loiecfg = $this->RciamEnroller->getConfiguration($eof_ea['CoEnrollmentFlow']['co_id']);
    if($loiecfg['RciamEnroller']['forbid_duplicate_eof']) {
      $cou_eofs = $this->RciamEnroller->getEnrollmentFlows($eof_ea['CoEnrollmentFlow']['co_id'], true);
      //If Enrollement flow is a COU Enrollment flow
      if(array_key_exists($this->request->params['named']['coef'], $cou_eofs)) {
        // Get the petition(s) of this EOF
        $petition = $this->RciamEnroller->getPetitionsByCoPersonIdAndEnrollemntFlow($_SESSION["Auth"]["User"]["co_person_id"], $this->request->params['named']['coef']);
  
        // If petition already exists then redirect user to another page
        if(!empty($petition)) {
          // Redirect to petition page
          $petition_redirect = [
            'controller' => 'co_petitions',
            'plugin' => null,
            'action' => 'view/',
            $petition['CoPetition']['id']
          ];
          $this->redirect($petition_redirect);  
        }
      }
    }
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
      else {
        // XXX Level of Assurance for the OrgIdentity used for Login
        $org_list = Hash::extract($orgIdentities, '{n}.OrgIdentity.id');
        $cert_list = Hash::combine(
          $orgIdentities,
          '{n}.Cert.{n}.id',
          array( '%s@separator@%s@separator@%d',
            '{n}.Cert.{n}.subject',
            '{n}.Cert.{n}.issuer',
            '{n}.Cert.{n}.ordr'),
          '{n}.Cert.{n}.org_identity_id'
        );
        $ident_list = Hash::combine(
          $orgIdentities,
          '{n}.Identifier.{n}.id',
          '{n}.Identifier.{n}.identifier',
          '{n}.Identifier.{n}.org_identity_id');
        $assurance_list = Hash::combine(
          $orgIdentities,
          '{n}.Assurance.{n}.id',
          array( '%s@%s', '{n}.Assurance.{n}.type', '{n}.Assurance.{n}.value'),
          '{n}.Assurance.{n}.org_identity_id');
        $processed_list = array();
        foreach( $org_list as $org_id) {
          $processed_list[$org_id]['Cert'] = !empty($cert_list[$org_id]) ? $cert_list[$org_id] : array();
          $processed_list[$org_id]['Identifier'] = !empty($ident_list[$org_id]) ? $ident_list[$org_id] : array();
          $processed_list[$org_id]['Assurance'] = !empty($assurance_list[$org_id]) ? $assurance_list[$org_id] : array();
        }

        // Explode certificate information
        foreach($processed_list as $org_id => $orgid_models) {
          if(!empty($orgid_models['Cert'])) {
            foreach($orgid_models['Cert'] as $certid => $denseval) {
              list($subjectex, $issuerex, $ordrex) = explode('@separator@', $denseval);
              $processed_list[$org_id]['Cert'][$certid] = array(
                'issuer' => $issuerex,
                'subject' => $subjectex,
                'ordr' => (int)$ordrex,
              );
            }
          }
        }

        // Order paths according to Certificate Ordering
        $flattened_proc_list = Hash::flatten($processed_list);
        $ordering_flatten = array_filter(
          $flattened_proc_list,
          function ($value, $key) {
            return (strpos($key, '.ordr') !== false);
          },
          ARRAY_FILTER_USE_BOTH
        );
        asort($ordering_flatten);

        // XXX Get the Assurance pre-requisites from the Configuration
        $vos_assurance_prerquisite = !empty($loiecfg["RciamEnroller"]["vos_assurance_level"]) ? $loiecfg["RciamEnroller"]["vos_assurance_level"] : "";
        $vo_config_list = $this->RciamEnroller->parseAssurancePrereqConfig($vos_assurance_prerquisite);

        // XXX Get COU name associated with the Enrollment Flow
        $cou_id_eof_attribute = Hash::extract($eof_ea, 'CoEnrollmentAttribute.{n}[attribute=r:cou_id].CoEnrollmentAttributeDefault.{n}.value');
        $cou_name = "";
        if(!empty($cou_id_eof_attribute)) {
          $this->Cou = ClassRegistry::init('Cou');
          $cou_name = $this->Cou->field("name",array('Cou.id' => (int)array_pop($cou_id_eof_attribute)));
        }
        // Get the required assurance level from the configuration
        $config_required_assurance = $vo_config_list[$cou_name]['implode'];

        // XXX Iterate over OrgIdentities and get the first which:
        // * assurane level matches the level requested by the configuration
        // * Has a certificate
        // Store the orgId into a variable in order to use below
        $issuer = null;
        $subject = null;
        $org_id_picked = null;
        $cert_id_picked = null;
        foreach($ordering_flatten as $path => $order) {
          $full_path = Hash::expand(array($path => $order));
          $org_id = key($full_path);
          $cert_id = key($full_path[$org_id]['Cert']);
          $orgid_models = $processed_list[$org_id];
          $has_assurance = in_array($config_required_assurance, $orgid_models['Assurance']) ? true : false;

          if(!$has_assurance
            && $this->RciamEnroller->assuranceValueOrder($config_required_assurance) > 0
            && $this->RciamEnroller->assuranceValueOrder($orgid_models['Assurance']) > 0 ) {
            $required_assurance_order = $this->RciamEnroller->assuranceValueOrder($config_required_assurance);
            $org_assurance_order = $this->RciamEnroller->assuranceValueOrder($orgid_models['Assurance']);
            if($org_assurance_order >= $required_assurance_order) {
              $has_assurance = true;
            }
          }
          $has_certificate = false;

          if(!empty($processed_list[$org_id]['Cert'])) {
            $certificate = $processed_list[$org_id]['Cert'][$cert_id];
            if(!empty($certificate['subject'])
              && !empty($certificate['issuer'])) {
              $has_certificate = true;
            }
          }
          if($has_assurance && $has_certificate) {
            $issuer = $certificate['issuer'];
            $subject = $certificate['subject'];
            $org_id_picked = $org_id;
            $cert_id_picked = $cert_id;
            break;
          }
        }

        if(is_null($issuer) || is_null($subject)) {
          // Only RCauth is available. Redirect on low
          $lowcert_redirect = [
            'controller' => 'rciam_enrollers',
            'plugin' => Inflector::singularize(Inflector::tableize($this->plugin)),
            'action' => 'lowcert',
            'co' => (int)$eof_ea['CoEnrollmentFlow']['co_id'],
          ];
          $this->redirect($lowcert_redirect);
        } else {
          // Everythin is ok. Redirect on Finish
          $this->redirect($onFinish);
        }
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

  protected function execute_plugin_selectEnrollee($id, $onFinish)
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
    $authorized = parent::isAuthorized();
    return $authorized;
  }
}
