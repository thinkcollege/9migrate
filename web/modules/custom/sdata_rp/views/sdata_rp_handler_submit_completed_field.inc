<?php

/**
 * @file
 * Definition of sdata_rp_handler_submit_completed_
 */

/**
 * Provides a custom views field.
 */
class sdata_rp_handler_submit_completed_field extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
  }

  function query() {
    // do nothing -- to override the parent query.
  }

  function render($data) {
    global $user;


    // Get the provider, state, and reporting period node ids so we can build an entity
    // field query to get the list of individual data nodes that need to be checked
    // for completeness.
    $provider_nid = $data->_field_data['node_og_membership_nid']['entity']->nid;
    $provider = node_load($provider_nid);
    if ($provider->type != 'provider') {
      return;
    }
    $state_nid = $provider->og_group_provider_state_ref[LANGUAGE_NONE][0]['target_id'];
    $state = node_load($state_nid);
    if ($state->type != 'state') {
      return;
    }
    $state_individual_data_content_type = $state->field_individual_data_type[LANGUAGE_NONE][0]['value'];
    $reporting_period_nid = $state->field_current_reporting_period[LANGUAGE_NONE][0]['target_id'];
    //$legacy_period_nid = field_get_items('node', $state, 'field_legacy_reporting_period');
    $legacy_period_nid = $state->field_legacy_reporting_period[LANGUAGE_NONE][0]['target_id'] ? $state->field_legacy_reporting_period[LANGUAGE_NONE][0]['target_id'] : null;
    $toggle_period_nid = $reporting_period_nid;
    $reporting_period_node = node_load($reporting_period_nid);
    if($legacy_period_nid)  $legacy_period_node = node_load($legacy_period_nid); 
    
    $periodarray = array();
    if(isset($legacy_period_nid)) $legacyarray = _sdata_rp_switch_period($state_individual_data_content_type, $provider_nid, $legacy_period_nid);
    if($legacy_period_nid && !$legacyarray[1] && !$legacyarray[2]) { 
        $valid = $legacyarray[0];
        $completed = $legacyarray[1];
        $empty = $legacyarray[2];
        $toggle_period_nid = $legacy_period_nid;
        $printarray = print_r($legacyarray,true);
        $toggle_period_nid = $legacy_period_nid;

    }
    else 
    {   
        $periodarray = _sdata_rp_switch_period($state_individual_data_content_type, $provider_nid, $reporting_period_nid);     
        $valid = $periodarray[0];
        $completed = $periodarray[1];
        $empty = $periodarray[2];
        $toggle_period_nid = $reporting_period_nid;
    }
    $togglenode = $toggle_period_nid == $reporting_period_nid ? $reporting_period_node : ($legacy_period_nid ? $legacy_period_node : $reporting_period_node);
    $daterange = date_format(date_create($togglenode->field_rp_date_range[LANGUAGE_NONE][0]['value']), "F j") . " to " .  date_format(date_create($togglenode->field_rp_date_range[LANGUAGE_NONE][0]['value2']), "F j, Y");
    // if this is a CIE reporting period we are done here.
    if ($togglenode->field_georgia_cie[LANGUAGE_NONE][0]['value'] == '1' && $state_individual_data_content_type == 'individual_data_ga') return;
    $legacyarray = array();
    $deadlinedate = "Reporting due date: <span class=\"bigRed\">" . date_format(date_create($togglenode->field_rp_deadline_date[LANGUAGE_NONE][0]['value']),"F j Y") . "</span>";


    

    // If any of the individual data nodes fail validation, then the form isn't ready to
    // submit.
    if (in_array('Survey administrator', $user->roles)|| in_array('administrator', $user->roles) || in_array('GA Admin', $user->roles)) 
    {  $output = "<p><strong>Semi-annual reporting date range: $daterange</strong></p>
      <p><strong> $deadlinedate</strong></p>";
       if ($valid && !$completed && !$empty) {
       //  $output = l("Data complete - ready to submit", 'sdata_rp/submit_data/' . $provider_nid);
        $output .= "<p><a href=\"/provider/$provider_nid/individuals/$toggle_period_nid\">Follow this link to enter non-CIE individual data</a><br /><a class=\"bigSub\" href=\"/node/$provider_nid/edit?status=final\">Data complete - ready to submit.</a> </p>";
       }


       elseif($empty) {
         $output .= "<p><a href=\"/provider/$provider_nid/individuals/$toggle_period_nid\">Follow this link to enter individual data</a><br />You have no individuals listed with this provider.</p>";
       }

       elseif($valid && $completed) {
         $output .= "<p>You have already edited and submitted individual data for this reporting period.</p>";
       }

       else {
         $output .= "<a href=\"/provider/$provider_nid/individuals/$toggle_period_nid\">Follow this link to enter individual data</a><br />There are some individuals that are not complete. Please complete them before submitting your data.</p>";
        }
    } 
    else 
    {  $output = "<p><strong>Semi-annual reporting date range: $daterange</strong></p>
      <p><strong> $deadlinedate</strong></p>";
      if ($valid && !$completed && !$empty) {
      //  $output = l("Data complete - ready to submit", 'sdata_rp/submit_data/' . $provider_nid);
      $output .= "<h4><a href=\"/provider/$provider_nid/individuals/$toggle_period_nid\">Follow this link to enter non-CIE individual data</a></h4><p>Navigate to the list of individuals we have on file for your program, agency, or organization. From this page, you will have the ability to add and delete individuals, edit individuals' information and employment data, and view summary tables of all your individuals' data.</p><h4 class=\"redFour\">Submit your data:</h4><p><a class=\"bigSub\" href=\"/node/$provider_nid/edit?status=final\">Data complete - ready to submit.</a>  &nbsp;<strong>Select the link to complete your data entry for this reporting period.  Note:  This is a final submission for this reporting period.  You will not be able to return and edit individuals or data.</strong></p>";
      }


      elseif($empty) {
        $output .= "<h4><a href=\"/provider/$provider_nid/individuals/$toggle_period_nid\">Follow this link to enter non-CIE individual data</a></h4><p>Navigate to the list of individuals we have on file for your program, agency, or organization. From this page, you will have the ability to add and delete individuals, edit individuals' information and employment data, and view summary tables of all your individuals' data.</p><h4>Submit your data:</h4><p>You have no individuals listed with this provider.</p>";
      }

      elseif($valid && $completed) {
        $output .= "<h4>Follow this link to enter non-CIE individual data</h4><p>You have already edited and submitted individual data for this reporting period.</p><h4>Submit your data:</h4><p>You have already submitted your data for this reporting period.</p>";
      }

      else {
        $output .= "<h4><a href=\"/provider/$provider_nid/individuals/$toggle_period_nid\">Follow this link to enter non-CIE individual data</a></h4><p>Navigate to the list of individuals we have on file for your program, agency, or organization. From this page, you will have the ability to add and delete individuals, edit individuals' information and employment data, and view summary tables of all your individuals' data.</p><h4>Submit your data:</h4><p>There are some individuals that are not complete. Please complete them before submitting your data.</p>";
      }
    }
   
    return $output;
  }
}
function _sdata_rp_validate_md($node) {
  if ($node->field_ga_ind_data_complete['und'][0]['value'] != 1) return FALSE;
  return TRUE;
}
function _sdata_rp_validate_ga($node) {
  if ($node->field_ga_ind_data_complete['und'][0]['value'] != 1) return FALSE;
  return TRUE;
}
function _sdata_rp_validate_ma($node) {
  if ($node->field_ga_ind_data_complete['und'][0]['value'] != 1) return FALSE;
  return TRUE;
}
function _sdata_rp_switch_period($state_individual_data_content_type, $provider_nid, $reporting_period_nid) {
  // Entity field query that gets a list of individual data nodes for the given provider
  // for the current reporting period for the provider's state.
  if ($state_individual_data_content_type == 'individual_data_ga') { 
    $query = new EntityFieldQuery();
    // Georgia, check for CIE
    $query->entityCondition('entity_type', 'node')
      ->fieldCondition('og_group_ref', 'target_id', $provider_nid)
      ->fieldCondition('field_cie_individual_yes_no','value', '1', '!=')
      ->propertyCondition('status', 1)
      ->propertyCondition('type', $state_individual_data_content_type)
      ->fieldCondition('field_reporting_period', 'target_id', $reporting_period_nid);
    $result = $query->execute();
  } else
  { 
    $query = new EntityFieldQuery();

    $query->entityCondition('entity_type', 'node')
      ->fieldCondition('og_group_ref', 'target_id', $provider_nid)
      ->propertyCondition('status', 1)
      ->propertyCondition('type', $state_individual_data_content_type)
      ->fieldCondition('field_reporting_period', 'target_id', $reporting_period_nid);
    $result = $query->execute();
  }

  // Assume the best.
  $valid = TRUE;
  $completed = FALSE;
  $empty = FALSE;
  if ($state_individual_data_content_type == 'individual_data_ga') {
    $query2 = new EntityFieldQuery();
    $query2->entityCondition('entity_type', 'node')
    ->addTag('DANGEROUS_ACCESS_CHECK_OPT_OUT')
    ->fieldCondition('og_group_ref', 'target_id', $provider_nid)
    ->fieldCondition('field_cie_individual_yes_no','value', '1', '!=')
    ->propertyCondition('status',array(0),'IN')
    ->propertyCondition('type', $state_individual_data_content_type)
    ->fieldCondition('field_reporting_period', 'target_id', $reporting_period_nid);
    $result2 = $query2->execute();
  }
  else
  {
    $query2 = new EntityFieldQuery();
    $query2->entityCondition('entity_type', 'node')
    ->addTag('DANGEROUS_ACCESS_CHECK_OPT_OUT')
    ->fieldCondition('og_group_ref', 'target_id', $provider_nid)
    ->propertyCondition('status',array(0),'IN')
    ->propertyCondition('type', $state_individual_data_content_type)
    ->fieldCondition('field_reporting_period', 'target_id', $reporting_period_nid);
    $result2 = $query2->execute();
  }
  
  // print_r($result2);
  if(count($result) < 1  && count($result2) < 1) $empty = TRUE;
  if(count($result2) >= 1) $completed = TRUE;
  //print "Result 1 "; print_r($result); print "Result 2 "; print_r($result2);
  // If any individual data nodes are returned, loop around each one and validate.
  if (isset($result['node'])) {
    foreach ($result['node'] as $key => $value) {
      $node = node_load($key);
      // Validate node is complete here. Code will probably have to be unique for each
      // Individual data node type (individual_data, individual_data_ma, etc...)
      switch ($state_individual_data_content_type) {
        case 'individual_data':
          $valid = _sdata_rp_validate_md($node);
          break;
        case 'individual_data_ma':
          $valid = _sdata_rp_validate_ma($node);
          break;
        case 'individual_data_ga':
          $valid = _sdata_rp_validate_ga($node);
          break;
      }

      //  if (!$valid) break;
      if (!$valid) {
        $indnode =  $node->field_individual[LANGUAGE_NONE][0]['target_id'];

        $provnode =  $node->og_group_ref[LANGUAGE_NONE][0]['target_id'];
        if ($state_individual_data_content_type == 'individual_data_ga') {
          $query3 = new EntityFieldQuery();
          $query3->entityCondition('entity_type', 'node')
          ->fieldCondition('og_group_ref', 'target_id', $provnode)
          ->fieldCondition('field_cie_individual','value','1', '!=')
          ->propertyCondition('nid', $indnode)
          ->propertyCondition('status', 1)
          ->propertyCondition('type', 'individual');

          $result3 = $query3->execute();

        } else 
        {

          $query3 = new EntityFieldQuery();
          $query3->entityCondition('entity_type', 'node')
          ->fieldCondition('og_group_ref', 'target_id', $provnode)
          ->propertyCondition('nid', $indnode)
          ->propertyCondition('status', 1)
          ->propertyCondition('type', 'individual');

          $result3 = $query3->execute();
        }
        if (empty($result3)) {$valid = TRUE; }


        else {   break; }
      }
    


    }
  }
  return array($valid,$completed,$empty);
}