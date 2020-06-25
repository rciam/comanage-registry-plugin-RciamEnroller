<?php

App::uses("StandardController", "Controller");
App::uses('Cache', 'Cache');

class RciamEnrollersController extends StandardController
{
  // Class name, used by Cake
  public $name = "RciamEnrollers";
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $uses = array(
    "RciamEnroller.RciamEnroller",
    "Co",
  );
  
  /**
   * Configure RciamEnroller.
   *
   * @since  COmanage Registry v3.1.0
   */
  public function configure() {
    $configData = $this->RciamEnroller->getConfiguration($this->cur_co['Co']['id']);
    $id = isset($configData['RciamEnroller']) ? $configData['RciamEnroller']['id'] : -1;

    // Get the name of the current CO
    $args = array();
    $args['conditions']['Co.id'] = $this->request->params['named']['co'];
    $args['fields'] = array('Co.id', 'Co.name');
    $args['contain'] = false;

    $vv_co_list = $this->Co->find('list', $args);
    $this->set('vv_co_list', $vv_co_list);
    
    if($this->request->is('post')) {
      // We're processing an update
      // if i had already set configuration before, now retrieve the entry and update
      if($id > 0){
        $this->RciamEnroller->id = $id;
        $this->request->data['RciamEnroller']['id'] = $id;
      }
      
      try {
        /*
         * The check of the fields' values happen in two phases.
         * 1. The framework is responsible to ensure the presentation of all the keys
         * everytime i make a post. We achieve this by setting the require field to true.
         * 2. On the other hand not all fields are required to have a value for all cases. So we apply logic and apply the notEmpty logic
         * in the frontend through Javascript.
         * */
        $save_options = array(
          'validate'  => true,
          'atomic' => true,
          'provisioning' => false,
        );

        if($this->RciamEnroller->save($this->request->data, $save_options)){
          $this->Flash->set(_txt('rs.saved'), array('key' => 'success'));
        } else {
          $invalidFields = $this->RciamEnroller->invalidFields();
          $this->log(__METHOD__ . "::exception error => ".print_r($invalidFields, true), LOG_DEBUG);
          $this->Flash->set(_txt('rs.rciam_enroller.error'), array('key' => 'error'));
        }
      }
      catch(Exception $e) {
        $this->log(__METHOD__ . "::exception error => ".$e, LOG_DEBUG);
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
      // Redirect back to a GET
      $this->redirect(array('action' => 'configure', 'co' => $this->cur_co['Co']['id']));
    } else {
      $vv_enrollments_list = $this->RciamEnroller->getEnrollmentFlows($this->request->params['named']['co']);
      $this->set('vv_full_enrollments_list', $vv_enrollments_list);
      if($id > 0){
        $this->set('vv_enable_eofs_save', true);
        // Get the EOFs that are already picked
        $used_eof_ids = Hash::extract($configData['RciamEnrollerEof'], '{n}.co_enrollment_flow_id');
        $vv_enrollments_list = array_filter(
          $vv_enrollments_list,
          function($key) use ($used_eof_ids) {
            return !in_array($key, $used_eof_ids);
          },
        ARRAY_FILTER_USE_KEY);
      }
      $this->set('vv_enrollments_list', $vv_enrollments_list);
      // Return the settings
      $this->set('rciam_enrollers', $configData);
    }
  }


  /**
   * No Certificate User message
   *
   * @since  COmanage Registry v3.1.0
   */
  public function nocert() {
    $configData = $this->RciamEnroller->getConfiguration($this->cur_co['Co']['id']);
    $redirect_final = '/';
    // Create the route to user's profile and redirect
    if(!empty($_SESSION["Auth"]["User"]["co_person_id"])) {
      $redirect_final = [
        'controller' => 'co_people',
        'plugin' => '',
        'action' => 'canvas',
        $_SESSION["Auth"]["User"]["co_person_id"],
      ];
    }
    if (empty($configData["RciamEnroller"]["nocert_msg"])) {
      $this->Flash->set(_txt('er.rciam_enroller.no_cert', array(_txt('ct.rciam_enrollers.2'))), array('key' => 'error'));
      // If no CO Person ID in the Session redirect to root
      $this->redirect($redirect_final);
    }

    $this->set('vv_nocert_msg', $configData["RciamEnroller"]["nocert_msg"]);
    $this->set('vv_redirect_final', Router::url($redirect_final));
  }

  /**
   *
   */
  public function beforeRender()
  {
    parent::beforeRender();
  }

  /**
   * @param $reqdata
   * @param null $curdata
   * @param null $origdata
   * @return bool
   */
  function checkWriteFollowups($reqdata, $curdata = NULL, $origdata = NULL) {
    $this->Flash->set(_txt('rs.updated-a3', array(_txt('ct.rciam_enrollers.2'))), array('key' => 'success'));
    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for auth decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v2.0.0
   * @return Array Permissions
   */
  
  public function isAuthorized() {
    $this->log(__METHOD__ . "::@", LOG_DEBUG);
    $roles = $this->Role->calculateCMRoles();
  
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
  
    // Determine what operations this user can perform
    $p['configure'] = ($roles['cmadmin'] || $roles['coadmin']);
    $p['nocert'] = true;
    $p['logout'] = true;
    $this->set('permissions', $p);
    
    return($p[$this->action]);
  }


  /**
   * @param null $data
   * @return int|mixed
   */
  public function parseCOID($data = null) {
    if($this->action === 'configure'
       || $this->action === 'nocert'
          || $this->action === 'logout') {
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }
    
    return parent::parseCOID();
  }
}