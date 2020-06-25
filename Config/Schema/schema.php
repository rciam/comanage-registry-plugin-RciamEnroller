<?php
App::uses('ClassRegistry', 'Utility');
App::uses('RciamEnroller.RciamEnroller', 'Model');

class AppSchema extends CakeSchema
{
  public $connection = 'default';

  public function before($event = array())
  {
    // No Database cache clear will be needed afterwards
    $db = ConnectionManager::getDataSource($this->connection);
    $db->cacheSources = false;


    if (isset($event['drop'])) {
      switch ($event['drop']) {
        case 'rciam_enrollers':
          $RciamEnroller = ClassRegistry::init('RciamEnroller.RciamEnroller');
          $RciamEnroller->useDbConfig = $this->connection;
          $backup_file = __DIR__ . '/rciam_enrollers_' . date('y_m_d') . '.csv';
          if(!file_exists($backup_file)) {
            touch($backup_file);
            chmod($backup_file, 0766);
          }
          try {
            $RciamEnroller->query("COPY cm_rciam_enrollers TO '" . $backup_file . "' DELIMITER ',' CSV HEADER");
          } catch (Exception $e){
            // Ignore the Exception
          }
          break;
        case 'rciam_enroller_eofs':
          $RciamEnrollerEofs = ClassRegistry::init('RciamEnroller.RciamEnrollerEofs');
          $RciamEnrollerEofs->useDbConfig = $this->connection;
          $backup_file = __DIR__ . '/rciam_enrollers_' . date('y_m_d') . '.csv';
          if(!file_exists($backup_file)) {
            touch($backup_file);
            chmod($backup_file, 0766);
          }
          try {
            $RciamEnrollerEofs->query("COPY cm_rciam_enroller_eofs TO '" . $backup_file . "' DELIMITER ',' CSV HEADER");
          } catch (Exception $e){
            // Ignore the Exception
          }
          break;
      }
    }

    return true;
  }
  
  public function after($event = array())
  {
    if (isset($event['create'])) {
      switch ($event['create']) {
        case 'rciam_enrollers':
          $RciamEnroller = ClassRegistry::init('RciamEnroller.RciamEnroller');
          $RciamEnroller->useDbConfig = $this->connection;
          // Add the constraints or any other initializations
          $RciamEnroller->query("ALTER TABLE ONLY public.cm_rciam_enrollers ADD CONSTRAINT cm_rciam_enrollers_co_id_fkey FOREIGN KEY (co_id) REFERENCES public.cm_cos(id);");
          break;
        case 'rciam_enroller_eofs':
          $RciamEnrollerEof = ClassRegistry::init('RciamEnroller.RciamEnrollerEof');
          $RciamEnrollerEof->useDbConfig = $this->connection;
          // Add the constraints or any other initializations
          $RciamEnrollerEof->query("ALTER TABLE ONLY public.cm_rciam_enroller_eofs ADD CONSTRAINT cm_rciam_enroller_eofs_rciam_enroller_id_fkey FOREIGN KEY (rciam_enroller_id) REFERENCES public.cm_rciam_enrollers(id);");
          $RciamEnrollerEof->query("ALTER TABLE ONLY public.cm_rciam_enroller_eofs ADD CONSTRAINT cm_rciam_enroller_eofs_co_enrollment_flow_id_fkey FOREIGN KEY (co_enrollment_flow_id) REFERENCES public.cm_co_enrollment_flows(id);");
          break;
        case 'rciam_enroller_actions':
          $RciamEnrollerAction = ClassRegistry::init('RciamEnroller.RciamEnrollerAction');
          $RciamEnrollerAction->useDbConfig = $this->connection;
          // Add the constraints or any other initializations
          $RciamEnrollerAction->query("ALTER TABLE ONLY public.cm_rciam_enroller_actions ADD CONSTRAINT cm_rciam_enroller_actions_rciam_enroller_id_fkey FOREIGN KEY (rciam_enroller_eof_id) REFERENCES public.cm_rciam_enroller_eofs(id) on delete cascade;");
          break;
      }
    }
  }

  public $rciam_enrollers = array(
    'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11, 'key' => 'primary'),
    'co_id' => array('type' => 'integer', 'null' => true, 'default' => null),
    'status' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 1),
    'nocert_msg' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 4000),
    'return' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 50),
    'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
    'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
    'indexes' => array(
      'PRIMARY' => array('unique' => true, 'column' => 'id')
    ),
    'tableParameters' => array()
  );

  public $rciam_enroller_eofs = array(
    'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11, 'key' => 'primary'),
    'co_enrollment_flow_id' => array('type' => 'integer', 'null' => false, 'default' => null),
    'rciam_enroller_id' => array('type' => 'integer', 'null' => true, 'default' => null),
    'mode' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 1),
    'created' => array('type' => 'datetime', 'null' => false, 'default' => null),
    'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
    'indexes' => array(
      'PRIMARY' => array('unique' => true, 'column' => 'id')
    ),
    'tableParameters' => array()
  );

  public $rciam_enroller_actions = array(
    'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11, 'key' => 'primary'),
    'rciam_enroller_eof_id' => array('type' => 'integer', 'null' => true, 'default' => null),
    'type' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 2),
    'created' => array('type' => 'datetime', 'null' => false, 'default' => null),
    'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
    'indexes' => array(
      'PRIMARY' => array('unique' => true, 'column' => 'id')
    ),
    'tableParameters' => array()
  );
}
