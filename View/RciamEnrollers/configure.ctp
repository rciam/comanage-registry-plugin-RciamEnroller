<?php
/**
 * COmanage Registry CO Service Token Setting Index View
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

$e = false;

if($this->action == "configure" && $permissions['configure'])
  $e = true;

// Add breadcrumbs
print $this->element("coCrumb");

$this->Html->addCrumb(_txt('ct.rciam_enroller.1'));

// Add page title
$params = array();
$params['title'] = _txt('ct.rciam_enroller.1');

// Add top links
$params['topLinks'] = array();

print $this->element("pageTitleAndButtons", $params);

print $this->Form->create('RciamEnroller',
    array('url' => array('action' => 'configure', 'co' => $cur_co['Co']['id']),
      'inputDefaults' => array('label' => false, 'div' => false))) . "\n";
print $this->Form->hidden('RciamEnroller.co_id', array('default' => $cur_co['Co']['id'])) . "\n";
// Store the token
$token_key = $this->request->params['_Token']['key'];
// Initiate the variable that we will use to enable or disable save addition of EOFs in the list
$vv_enable_eofs_save = !empty($vv_enable_eofs_save) ? $vv_enable_eofs_save : 'false';
// Disable if you do not have the permissions
if (!$e) {
  $vv_enable_eofs_save = false;
}

print $this->Html->css('/RciamEnroller/css/rciam_enroller');

?>
<script type="text/javascript">
    // Generate flash notifications for messages
    function generateLinkFlash(text, type, timeout) {
        var n = noty({
            text: text,
            type: type,
            dismissQueue: true,
            layout: 'topCenter',
            theme: 'comanage',
            timeout: timeout
        });
    }

    function updateDivDescription(element, msg) {
        divDescr = element.first().find("div:eq(2)");
        text = divDescr.html().split('-')[0].trim();
        text = text + "<span style='color:#ff0000'>' - " + msg + "</span>";
        divDescr.html(text);
    }
    
    function parseFullEOFList($json_obj_eof_list) {
        var $eof_full_list = {};
        $.each($json_obj_eof_list, function (key, value){
            $eof_full_list[key] = value;
        });
        
        return $eof_full_list;
    }
    
    // Remove the row as soon as i press the delete button
    function removeEof(self) {
        var $tr = $(self).closest('tr');
        var $td = $tr.find("td:first");
        eof_id = $td.attr('eof_id');
        eof_name = $td.text();
        var $eof_data = {
            _Token: {}
        };
        $eof_data.id = $tr.attr('id');
        $eof_data._Token.key = '<?php echo $token_key;?>';
        var url_str = '<?php echo $this->Html->url(array(
          'plugin' => Inflector::singularize(Inflector::tableize($this->plugin)),
          'controller' => 'rciam_enroller_eofs',
          'action' => 'delete',
          'co'  => $cur_co['Co']['id'])); ?>' + '/' + $tr.attr('id');
        $.ajax({
            type: "DELETE",
            url: url_str,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.setRequestHeader('X-CSRF-Token', '<?php echo $token_key;?>');
            },
            cache:false,
            data: $eof_data,
            success: function(response) {
                // Add EOF to the option list
                $('#enrollments_list').append($('<option>', {
                    value: eof_id,
                    text : eof_name
                }));
                // Finally remove the row
                $tr.remove();
                // Remove the Empty valued option if available
                $("#enrollments_list option[value='Empty']").remove();
                $('#eof_list_btn').removeAttr("disabled");
                generateLinkFlash("<?php print _txt('rs.link_org_identity_eof.deleted') ?>","success", 2000);
            },
            error: function(response) {
                generateLinkFlash("Delete Failed","error", 2000);
                console.log(response.responseText);
            }
        });
    }

    $(function () {
        $("#btn_save").click(function(e) {
            // 1. I should always have an attribute selected.
            // 2. Currently only mail is allowed
            $attrSelector = $('#attribute');
            $attrVal = $attrSelector.find('option:selected').val().trim();
            if ( $attrVal === '' || $attrVal !== "mail" ) {
               updateDivDescription($attrSelector, "Only mail attribute accepted or Field is empty!");
               $("#coSpinner").remove();
               e.preventDefault();
            }

            // If the email mode is enabled then i need a Logout Endpoint
            if ($("#email_confirmation_mode").find('option:selected').val().trim() === 'E') {
                if ( $("#logout_endpoint .field-info").find('input[type="text"]').val().trim() === "") {
                    updateDivDescription($("#logout_endpoint"), "You must provide a Logout endpoint.");
                    $("#coSpinner").remove();
                    e.preventDefault();
                }
            } else if ($("#email_confirmation_mode").find('option:selected').val().trim() === 'X') {
                if ( $("#auxiliary_authentication .field-info").find('input[type="text"]').val().trim() === "") {
                    updateDivDescription($("#auxiliary_authentication"), "You must provide an Auxiliary Authentication Endpoint.");
                    $("#coSpinner").remove();
                    e.preventDefault();
                }
            }
      });

        // Enable or disable Addition of EOFs in the list
        let btn_status = <?php echo $vv_enable_eofs_save?>;
        if (btn_status) {
            $('#enrollments_list').removeAttr("disabled");
            $('#eof_list_btn').removeAttr("disabled");
            $('#actions_list').attr("disabled", "disabled");
        } else {
            $('#enrollments_list').attr("disabled", "disabled");
            $('#eof_list_btn').attr("disabled", "disabled");
            $('#actions_list').attr("disabled", "disabled");
        }

        // Load the EOFs option list
        let eof_list_remain = <?php echo json_encode($vv_enrollments_list); ?>;
        $.each(eof_list_remain, function(eof_id, eof_name){
            $('#enrollments_list').append($('<option>', {
                value: eof_id,
                text : eof_name
            }));
        });
        let actions_list = <?php echo json_encode(RciamActionsEnum::actions); ?>;
        $.each(actions_list, function(action_id, action_name){
            $('#actions_list').append($('<option>', {
                value: action_id,
                text : action_name
            }));
        });
        // If the list has no data then show an Empty value
        if (eof_list_remain.length == 0) {
            $('#enrollments_list').append('<option value="Empty" disabled selected>Empty</option>');
            $('#eof_list_btn').attr("disabled", "disabled");
        }
        
        // Load the EOF saved list
        let eof_saved_list = <?php echo json_encode($rciam_enrollers['RciamEnrollerEof']); ?>;
        let eof_full_list = parseFullEOFList(<?php echo json_encode($vv_full_enrollments_list); ?>);
        let actions_enum_list = <?php echo json_encode(RciamActionsEnum::actions); ?>;
        $.each(eof_saved_list, function(key, value){
            hl_url = '<?php echo $this->Html->url(array(
              'plugin' => null,
              'controller' => 'co_enrollment_flows',
              'action' => 'edit')); ?>' + '/' + value.co_enrollment_flow_id;
            console.log(actions_enum_list);
            delete_button = '<button type="button" class="deletebutton ui-button ui-corner-all ui-widget" title="Delete" onclick="removeEof(this);">Delete</button>';
            row = "<tr id='" + value.id + "'>" +
                      "<td eof_id='" + value.co_enrollment_flow_id + "'>" +
                          "<a href='" + hl_url + "'>" + eof_full_list[value.co_enrollment_flow_id] + "</a></td>" +
                       "<td action_eof_value='" + value.RciamEnrollerAction.type + "'>" + actions_enum_list[value.RciamEnrollerAction.type] + "</td>" +
                       "<td>" + delete_button + "</td>" +
                   "</tr>";
            $('#enrollment_flows_list_tb > tbody:last-child').append(row);
        });
        
        $('#eof_list_btn').click(function(){
            let $action = $('#actions_list option:selected');
            action_eof_text = $action.text();
            action_eof_value = $action.val();
            let $eof = $('#enrollments_list option:selected');
            eof_text = $eof.text();
            eof_id = $eof.val();
            // The data we will Post to COmanage. We include the token as well.
            let $eof_data = {
                _Token: {},
                RciamEnrollerEof: {},
                RciamEnrollerAction: []
            };
            $eof_data.RciamEnrollerAction.push({"type": action_eof_value});
            $eof_data.RciamEnrollerEof.co_enrollment_flow_id = eof_id;
            $eof_data.RciamEnrollerEof.deleted = 'false';
            $eof_data.RciamEnrollerEof.rciam_enroller_id = <?php echo !empty($rciam_enrollers['RciamEnroller']) ?
                                                                 $rciam_enrollers['RciamEnroller']['id'] : -1;?>;
            $eof_data._Token.key = '<?php echo $token_key;?>';
            // Make the ajax call and add the data into your table
            $.ajax({
                type: "POST",
                url: '<?php echo $this->Html->url(array(
                  'plugin' => Inflector::singularize(Inflector::tableize($this->plugin)),
                  'controller' => 'rciam_enroller_eofs',
                  'action' => 'add',
                  'co'  => $cur_co['Co']['id'])); ?>',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    xhr.setRequestHeader('X-CSRF-Token', '<?php echo $token_key;?>');
                },
                cache:false,
                data: $eof_data,
                success: function(response) {
                    hl_url = '<?php echo $this->Html->url(array(
                      'plugin' => null,
                      'controller' => 'co_enrollment_flows',
                      'action' => 'edit')); ?>' + '/' + eof_id;
                    delete_button = '<button type="button" class="deletebutton ui-button ui-corner-all ui-widget" title="Delete" onclick="removeEof(this);">' +
                                        '<span class="ui-button-icon ui-icon ui-icon-circle-close"></span>' +
                                        '<span class="ui-button-icon-space"> </span>Delete</button>';
                    row = "<tr id='" + response.id+ "'>" +
                              "<td eof_id='" + eof_id+ "'>" +
                              "<a href='" + hl_url + "'>" + eof_text + "</a></td>" +
                              "<td action_eof_value='" + action_eof_value+ "'>" + action_eof_text + "</td>" +
                              "<td>" + delete_button + "</td>" +
                           "</tr>";
                    $('#enrollment_flows_list_tb > tbody:last-child').append(row);
                    // Remove the EOF from the selection list
                    $eof.remove();
                    // If the list has no data then show an Empty value
                    if ($('#enrollments_list option').length == 0) {
                        $('#enrollments_list').append('<option value="Empty" disabled selected>Empty</option>');
                        $('#eof_list_btn').attr("disabled", "disabled");
                    }
                    generateLinkFlash(response.eof_name + " picked.","success", 2000);
                },
                error: function(response) {
                    generateLinkFlash(response.responseJSON.msg,"error", 2000);
                }
            });
        });
    });
</script>


<div class="co-info-topbox">
  <i class="material-icons">info</i>
  <?php print _txt('ct.rciam_enroller.info'); ?>
</div>
<ul id="<?php print $this->action; ?>_rciam_enroller" class="fields form-list">
  <li id="<?php print $this->Rciam->createIdProperty(_txt('pl.rciam_enroller.co_name'));?>">
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.rciam_enroller.co_name'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.rciam_enroller.co_name.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      print $vv_co_list[$cur_co['Co']['id']];
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('status',_txt('fd.status')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
      $attrs = array();
      $attrs['value'] = (!empty($rciam_enrollers['RciamEnroller']['status'])
        ? $rciam_enrollers['RciamEnroller']['status']
        : RciamStatusEnum::Active);
      $attrs['empty'] = false;

      if ($e) {
        print $this->Form->select(
          'status',
          RciamStatusEnum::type,
          $attrs
        );
  
        if ($this->Form->isFieldError('status')) {
          print $this->Form->error('status');
        }
      } else {
        print RciamStatusEnum::type[$rciam_enrollers['RciamEnroller']['status']];
      }
      ?>
    </div>
  </li>
  <li id="<?php print $this->Rciam->createIdProperty(_txt('pl.rciam_enroller.flow'));?>" style="display: flex !important;align-items: center;">
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.rciam_enroller.flow'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.rciam_enroller.flow.desc'); ?></div>
    </div>
    <div class="field-info">
        <div class="field_wrapper">
          <div>
            <table id="enrollment_flows_list_tb" class="eofsTable">
              <thead>
              <th class="enrollment_flow">
                <strong><label class="field-title"><?php print _txt('pl.rciam_enroller.name_lbl'); ?></label></strong>
                <select id="enrollments_list"/>
              </th>
              <th class="action_eof">
                <strong><label class="field-title"><?php print _txt('pl.rciam_enroller.action_lbl'); ?></label></strong>
                <select id="actions_list"/>
              </th>
              <th class="action_btn">
                <input id="eof_list_btn" type="button" value="Add" class="submit-button mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect">
              </th>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
    </div>
  </li>
  <li id="<?php print $this->Rciam->createIdProperty(_txt('pl.rciam_enroller.nocert_msg'));?>" class="field-stack">
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.rciam_enroller.nocert_msg'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.rciam_enroller.nocert_msg.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $intro = empty($rciam_enrollers['RciamEnroller']['nocert_msg']) ? ""
                : filter_var($rciam_enrollers['RciamEnroller']['nocert_msg'],FILTER_SANITIZE_SPECIAL_CHARS);
        print $this->Form->textarea('RciamEnroller.nocert_msg', array('size' => 4000, 'value' => $intro));
      ?>
    </div>
  </li>
  <li id="<?php print $this->Rciam->createIdProperty(_txt('pl.rciam_enroller.return_target'));?>">
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.rciam_enroller.return_target'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.rciam_enroller.return_target.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $value = empty($rciam_enrollers['RciamEnroller']['return']) ? ""
          : filter_var($rciam_enrollers['RciamEnroller']['return'],FILTER_SANITIZE_SPECIAL_CHARS) ;
        print $this->Form->input('RciamEnroller.return', array('size' => 50, 'value' => $value));
      ?>
    </div>
  </li>
  
  
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php
        $options = array(
          'style' => 'float:left;',
          'id'    => 'btn_save',
        );
        $submit_label = _txt('op.save');
        print $this->Form->submit($submit_label, $options);
        print $this->Form->end();
        ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
