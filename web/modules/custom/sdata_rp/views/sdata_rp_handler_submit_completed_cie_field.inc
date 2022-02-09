<?php

/**
 * @file
 * Definition of sdata_rp_handler_submit_completed_cie_field
 */

/**
 * Provides a custom views field.
 */
class sdata_rp_handler_submit_completed_cie_field extends views_handler_field {
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
    $reporting_period_node = node_load($reporting_period_nid);

    // Entity field query that gets a list of individual data nodes for the given provider
    // for the current reporting period for the provider's state.
    if ($state_individual_data_content_type != 'individual_data_ga') return;
      
    // Georgia CIE
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
        ->fieldCondition('og_group_ref', 'target_id', $provider_nid)
        ->fieldCondition('field_cie_individual_yes_no','value', '1', '=')
            ->propertyCondition('status', 1)
            ->propertyCondition('type', $state_individual_data_content_type)
            ->fieldCondition('field_reporting_period', 'target_id', $reporting_period_nid);
    $result = $query->execute(); 
    

    // Assume the best.
    $valid = TRUE;
    $completed = FALSE;
    $empty = FALSE;
   
    $query2 = new EntityFieldQuery();
    $query2->entityCondition('entity_type', 'node')
    ->addTag('DANGEROUS_ACCESS_CHECK_OPT_OUT')
    ->fieldCondition('og_group_ref', 'target_id', $provider_nid)
    ->fieldCondition('field_cie_individual_yes_no','value', '1', '=')
    ->propertyCondition('status',array(0),'IN')
    ->propertyCondition('type', $state_individual_data_content_type)
    ->fieldCondition('field_reporting_period', 'target_id', $reporting_period_nid);
    $result2 = $query2->execute();
    
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
            $valid = _sdata_rp_validate_md_cie($node);
            break;
          case 'individual_data_ma':
            $valid = _sdata_rp_validate_ma_cie($node);
            break;
          case 'individual_data_ga':
            $valid = _sdata_rp_validate_ga_cie($node);
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
            ->fieldCondition('field_cie_individual','value','1', '=')
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

    $daterange = date_format(date_create($reporting_period_node->field_rp_date_range[LANGUAGE_NONE][0]['value']), "F j") . " to " .  date_format(date_create($reporting_period_node->field_rp_date_range[LANGUAGE_NONE][0]['value2']), "F j, Y");
    $deadlinedate = "Reporting due date: <span class=\"bigRed\">" . date_format(date_create($reporting_period_node->field_rp_deadline_date[LANGUAGE_NONE][0]['value']),"F j Y") . "</span>";

    // If any of the individual data nodes fail validation, then the form isn't ready to
    // submit.
    if (in_array('Survey administrator', $user->roles)|| in_array('administrator', $user->roles) || in_array('GA Admin', $user->roles)) 
    {
      $output = "<p><strong>Monthly reporting date range: $daterange</strong></p>
      <p><strong> $deadlinedate</strong></p>";
       if ($valid && !$completed && !$empty) {
       //  $output = l("Data complete - ready to submit", 'sdata_rp/submit_data/' . $provider_nid);
        $output .= "<p><a href=\"/provider/$provider_nid/individuals-cie/$reporting_period_nid\">Follow this link to enter CIE individual data</a><br /><a class=\"bigSub\" href=\"/node/$provider_nid/edit?status=final-cie\">CIE Data complete - ready to submit.</a></p>";
       }


       elseif($empty) {
         $output = "";
       }

       elseif($valid && $completed) {
         $output .= "<p>You have already edited and submitted CIE individual data for this reporting period.</p>";
       }

       else {
         $output .= "<p><a href=\"/provider/$provider_nid/individuals-cie/$reporting_period_nid\">Follow this link to enter CIE individual data</a><br />There are some CIE individuals that are not complete. Please complete them before submitting your data.</p>";
        }
    } 
    else 
    {
      $output = "<p><strong>Monthly CIE reporting date range: $daterange</strong></p>
      <p><strong> $deadlinedate</strong></p>";
      if ($valid && !$completed && !$empty) {
      //  $output = l("Data complete - ready to submit", 'sdata_rp/submit_data/' . $provider_nid);
      $output .= "<h4><a href=\"/provider/$provider_nid/individuals-cie/$reporting_period_nid\">Follow this link to enter CIE individual data</a></h4><p>Navigate to the list of individuals we have on file for your program, agency, or organization. From this page, you will have the ability to add and delete individuals, edit individuals' information and employment data, and view summary tables of all your individuals' data.</p><h4 class=\"redFour\">Submit your data:</h4><p><a class=\"bigSub\" href=\"/node/$provider_nid/edit?status=final-cie\">Data complete - ready to submit.</a>  &nbsp;<strong>Select the link to complete your data entry for this reporting period.  Note:  This is a final submission for this reporting period.  You will not be able to return and edit CIE individuals or data.</strong></p>";
      }


      elseif($empty) {
        $output .= "<h4><a href=\"/provider/$provider_nid/individuals-cie/$reporting_period_nid\">Follow this link to enter CIE individual data</a></h4><p>Navigate to the list of CIE individuals we have on file for your program, agency, or organization. From this page, you will have the ability to add and delete individuals, edit individuals' information and employment data, and view summary tables of all your individuals' data.</p><h4 class=\"redFour\">Submit your data:</h4><p>You have no CIE individuals listed with this provider.</p>";
      }

      elseif($valid && $completed) {
        $output .= "<h4>Follow this link to enter CIE individual data</h4><p>You have already edited and submitted CIE individual data for this reporting period.</p><h4 class=\"redFour\">Submit your data:</h4><p>You have already submitted your data for this reporting period.</p>";
      }

      else {
        $output .= "<h4><a href=\"/provider/$provider_nid/individuals-cie/$reporting_period_nid\">Follow this link to enter CIE individual data</a></h4><p>Navigate to the list of individuals we have on file for your program, agency, or organization. From this page, you will have the ability to add and delete individuals, edit individuals' information and employment data, and view summary tables of all your individuals' data.</p><h4 class=\"redFour\">Submit your data:</h4><p>There are some individuals that are not complete. Please complete them before submitting your data.</p>";
      }
    }
    return $output;
  }
}
function _sdata_rp_validate_ga_cie($node) {
  if ($node->field_ga_ind_data_complete['und'][0]['value'] != 1) return FALSE;
  return TRUE;
}

/* function _sdata_rp_validate_ga_old($node) {
  // Individual competitive job
  if (
        (!$node->field_indv_comp_partic['und'][0]['value'] &&


          (
            !isset($node->field_indv_comp_hrs['und'][0]['value']) ||
            !isset($node->field_indv_comp_gross_wages['und'][0]['value']) || !isset($node->field_indv_comp_paid_time_off['und'][0]['value']) || !isset($node->field_indv_comp_time_in_job['und'][0]['value']) || !isset($node->field_indv_comp_occupation['und'][0]['value']) || !isset($node->field_indv_comp_fund_source['und'][0]['value'])
           
            
            ||


            (
              $node->field_indv_comp_hrs['und'][0]['value'] == 'Other' && !strlen($node->field_indv_comp_other_source['und'][0]['value']) > 0
            )
            ||


            (
              isset($node->field_indv_comp_hrs['und'][0]['value']) && isset($node->field_indv_comp_gross_wages['und'][0]['value']) && ((floatval($node->field_indv_comp_gross_wages['und'][0]['value'])/floatval($node->field_indv_comp_hrs['und'][0]['value'])) < floatval(variable_get('surveynodeform_mdminwage'))) && !strlen($node->field_indv_comp_why_not_min['und'][0]['value']) > 0
            )
          )
        )
        

  ) 
  {
    return FALSE;
  }
  

  //Group integrated
  if (
      ( !$node->field_grp_integ_partic['und'][0]['value'] &&
        (

          !isset($node->field_grp_integ_hrs['und'][0]['value']) ||
          !isset($node->field_grp_integ_gross_wages['und'][0]['value']) ||   !isset($node->field_grp_integ_paid_time_off['und'][0]['value']) || !isset($node->field_grp_integ_occupation['und'][0]['value'])
          
          || !isset($node->field_grp_integ_work_location['und'][0]['value'])



        )
      )
      
  ) 
  {
    return FALSE;
  }
  //Self employent
  if (
        (!$node->field_self_emp_partic['und'][0]['value']) &&
        (
          !isset($node->field_self_emp_hrs['und'][0]['value']) ||
          !isset($node->field_self_emp_gross_income['und'][0]['value']) 
          ||   !isset($node->field_self_emp_gross_expens['und'][0]['value'])
          || !isset($node->field_self_emp_occupation['und'][0]['value'])
        )
      ) 
     
  {
    return FALSE;
  }

  //Facility based/Sheltered work
  if (
      (!$node->field_shl_partic['und'][0]['value']) &&
      (

        !isset($node->field_shl_hrs['und'][0]['value']) ||
        !isset($node->field_shl_gross_wages['und'][0]['value']) 
        || !isset($node->field_shl_paid_time_off['und'][0]['value']) 


      )
  ) 
  {
      return FALSE;
  }

    //Community based non work
  if (
        (!$node->field_com_non_work_partic['und'][0]['value'] && !$node->field_com_non_wrk_hours['und'][0]['value']) 
          ||
          (!$node->field_com_non_work_partic['und'][0]['value'] && ($node->field_com_non_work_vol_partic_2['und'][0]['value'] == '_none' || $node->field_com_non_work_vol_partic_2['und'][0]['value'] == '')
         
         
        )
      ) 
    {
      return FALSE;
    }

      //Facility based non work
    if (!$node->field_fac_non_work_partic['und'][0]['value'] &&
          (
            $node->field_fac_non_work_yn_partic['und'][0]['value']!= 1
            //new field Sept. 20202
          
          )
        )
    {
      return FALSE;
    }

  if (
    $node->field_indv_comp_partic['und'][0]['value'] &&
    $node->field_grp_integ_partic['und'][0]['value'] &&
    $node->field_self_emp_partic['und'][0]['value'] &&
    $node->field_shl_partic['und'][0]['value'] &&
    $node->field_com_non_work_partic['und'][0]['value'] &&
    $node->field_fac_non_work_partic['und'][0]['value'] &&
    (
      (!isset($node->field_indv_data_partic_why['und'][0]['value']) || $node->field_indv_data_partic_why['und'][0]['value'] == '4')
      ||
      (
        $node->field_indv_data_partic_why['und'][0]['value'] == 3 && !isset($node->field_indv_data_partic_other['und'][0]['value'])
        )
    )
  )
  {
    return FALSE;
  }




  return TRUE;
}

/*
 * Custom validation function for individual_data (MD) nodes.
 */
function _sdata_rp_validate_md_cie($node) {
  if ($node->field_ga_ind_data_complete['und'][0]['value'] != 1) return FALSE;
  return TRUE;
}
function _sdata_rp_validate_md_old_cie($node) {
  // Individual competitive job
  if (
        (!$node->field_indv_comp_partic['und'][0]['value'] &&


          (
            !isset($node->field_indv_comp_hrs['und'][0]['value']) ||
            !isset($node->field_indv_comp_gross_wages['und'][0]['value']) || !isset($node->field_indv_comp_paid_time_off['und'][0]['value'])
            //new field Sept. 20202
            || !isset($node->field_indv_comp_supports['und'][0]['value'])
            ||


            (isset($node->field_indv_comp_hrs['und'][0]['value']) && isset($node->field_indv_comp_gross_wages['und'][0]['value']) && ((floatval($node->field_indv_comp_gross_wages['und'][0]['value'])/floatval($node->field_indv_comp_hrs['und'][0]['value'])) < floatval(variable_get('surveynodeform_mdminwage'))) && !strlen($node->field_indv_comp_why_not_min['und'][0]['value']) > 0)

          )
        )
        ||
        (
          $node->field_indv_comp_partic['und'][0]['value'] 
          && !$node->field_indv_comp_covid['und'][0]['value']
        )

    ) 
    {
      return FALSE;
    }
    //Individual contracted
  if (
        (!$node->field_indv_cont_partic['und'][0]['value'] &&
          (

            !isset($node->field_indv_cont_hrs['und'][0]['value']) ||
            !isset($node->field_indv_cont_gross_wages['und'][0]['value']) ||   !isset($node->field_indv_cont_paid_time_off['und'][0]['value']) || !isset($node->field_indv_contr_set_aside['und'][0]['value'])
            //new field Sept. 20202
            || !isset($node->field_indv_cont_supports['und'][0]['value'])
            ||


            (isset($node->field_indv_cont_hrs['und'][0]['value']) && isset($node->field_indv_cont_gross_wages['und'][0]['value']) && ((floatval($node->field_indv_cont_gross_wages['und'][0]['value'])/floatval($node->field_indv_cont_hrs['und'][0]['value'])) < floatval(variable_get('surveynodeform_mdminwage'))) && !strlen($node->field_indv_cont_why_no_min['und'][0]['value']) > 0)


          )
        )
        ||
        (
          $node->field_indv_cont_partic['und'][0]['value'] 
          && !$node->field_indv_cont_covid['und'][0]['value']
        )
      ) 
      {
        return FALSE;
      }
  //Group integrated
  if (
        (!$node->field_grp_integ_partic['und'][0]['value'] &&
          (

            !isset($node->field_grp_integ_hrs['und'][0]['value']) ||
            !isset($node->field_grp_integ_gross_wages['und'][0]['value']) ||   !isset($node->field_grp_integ_paid_time_off['und'][0]['value']) || !isset($node->field_grp_integr_set_aside['und'][0]['value'])
            //new field Sept. 20202
            || !isset($node->field_grp_integ_supports['und'][0]['value'])



          )
        )
        ||
        (
          $node->field_grp_integ_partic['und'][0]['value'] 
          && !$node->field_grp_integ_covid['und'][0]['value']
        )
      ) 
      {
        return FALSE;
      }
  //Self employent
  if (
        (!$node->field_self_emp_partic['und'][0]['value']) &&
        (!isset($node->field_self_emp_hrs['und'][0]['value']) ||
         !isset($node->field_self_emp_gross_income['und'][0]['value']) ||   !isset($node->field_self_emp_gross_expens['und'][0]['value'])
          //new field Sept. 20202
          || !isset($node->field_self_emp_supports['und'][0]['value'])
        )
      ) 
 {
    return FALSE;
  }

  //Facility based/Sheltered work
  if (
        (!$node->field_shl_partic['und'][0]['value']) &&
        (

          !isset($node->field_shl_hrs['und'][0]['value']) ||
          !isset($node->field_shl_gross_wages['und'][0]['value']) ||   !isset($node->field_shl_paid_time_off['und'][0]['value']) || !isset($node->field_shlt_set_aside['und'][0]['value'])
          //new field Sept. 20202
          || !isset($node->field_shl_supports['und'][0]['value'])


        )
      ) 
      {
        return FALSE;
      }

    //Community based non work
  if (
        (!$node->field_com_non_work_partic['und'][0]['value'] &&
          $node->field_com_non_work_vol_partic['und'][0]['value']!= '1')
        ||
        (!$node->field_com_non_work_partic['und'][0]['value'] &&
        //new field Sept. 20202
         !isset($node->field_com_non_wrk_supports['und'][0]['value']))
        ||
        (
          !$node->field_com_non_work_partic['und'][0]['value'] && $node->field_com_non_work_vol_partic['und'][0]['value'] == '1' && !$node->field_com_non_wrk_hours['und'][0]['value']) ||
          (!$node->field_com_non_work_partic['und'][0]['value'] && $node->field_com_non_work_vol_partic['und'][0]['value'] == '1' && ($node->field_com_non_work_vol_partic_2['und'][0]['value'] == '_none' || $node->field_com_non_work_vol_partic_2['und'][0]['value'] == '')
        )
      ) 
      {
        return FALSE;
      }

      //Facility based non work
  if (
      !$node->field_fac_non_work_partic['und'][0]['value'] &&
      ($node->field_fac_non_work_yn_partic['und'][0]['value']!= 1 ||
        //new field Sept. 20202
        !isset($node->field_fac_non_work_supports['und'][0]['value'])
      )
    )  
    {
    return FALSE;
    }

    if (
      ($node->field_indv_comp_partic['und'][0]['value'] && $node->field_indv_comp_covid['und'][0]['value'] == 'no') &&
      ($node->field_indv_cont_partic['und'][0]['value'] && $node->field_indv_cont_covid['und'][0]['value'] == 'no') &&
      ($node->field_grp_integ_partic['und'][0]['value'] && $node->field_grp_integ_covid['und'][0]['value'] == 'no') &&
      $node->field_self_emp_partic['und'][0]['value'] &&
      $node->field_shl_partic['und'][0]['value'] &&
      $node->field_com_non_work_partic['und'][0]['value'] &&
      $node->field_fac_non_work_partic['und'][0]['value'] &&
      (
        (!isset($node->field_indv_data_partic_why['und'][0]['value']) || $node->field_indv_data_partic_why['und'][0]['value'] == '4')
        ||
        (
          $node->field_indv_data_partic_why['und'][0]['value'] == 3 && !isset($node->field_indv_data_partic_other['und'][0]['value'])
        )
      )
  )
 {
    return FALSE;
  }




  /* $fields = field_info_instances('node', $node->type);
  if (isset($node->field_indv_comp_hrs['und'][0]['value'])) {
    if (($node->field_indv_comp_hrs['und'][0]['value'] < $fields['field_indv_comp_hrs']['settings']['min']) ||
        ($node->field_indv_comp_hrs['und'][0]['value'] > $fields['field_indv_comp_hrs']['settings']['max'])) {
      return FALSE;
    }
  }

  if (isset($node->field_indv_comp_gross_wages['und'][0]['value'])) {
    if (($node->field_indv_comp_gross_wages['und'][0]['value'] < $fields['field_indv_comp_gross_wages']['settings']['min']) ||
        ($node->field_indv_comp_gross_wages['und'][0]['value'] > $fields['field_indv_comp_gross_wages']['settings']['max'])) {
      return FALSE;
    }
  } */

  // Individual contracted job
  // TODO: Add validation for this (and the other) section(s).

  // Group integrated job

  // Self employment

  // Facility based / sheltered work

  // Community based non work

  // Facility based non work


  return TRUE;
}


/*
 * Custom validation function for individual_data_ma nodes.
 */
  function _sdata_rp_validate__ga_cie($node) {
    if ($node->field_ga_ind_data_complete['und'][0]['value'] != 1) return FALSE;
    return TRUE;
  }
 function _sdata_rp_validate_ga_old_cie($node) {
  // Individual competitive job
  if (
      (!$node->field_indv_comp_partic['und'][0]['value'] &&


      (
        !isset($node->field_indv_comp_hrs['und'][0]['value']) ||
      !isset($node->field_indv_comp_gross_wages['und'][0]['value']) || (!isset($node->field_indv_paid_time_y_n['und'][0]['value']) || $node->field_indv_paid_time_y_n['und'][0]['value'] == '_none') || (!isset($node->field_indv_comp_health_ins_y_n['und'][0]['value']) || $node->field_indv_comp_health_ins_y_n['und'][0]['value'] == '_none') || (!isset($node->field_indv_comp_new_job_y_n['und'][0]['value']) || $node->field_indv_comp_new_job_y_n['und'][0]['value'] == '_none') || ($node->field_indv_comp_new_job_y_n['und'][0]['value'] == 'yes' && !isset($node->field_indv_emp_hire_date['und'][0]['value'])
      )

 ||


  (isset($node->field_indv_comp_hrs['und'][0]['value']) && isset($node->field_indv_comp_gross_wages['und'][0]['value']) && ((floatval($node->field_indv_comp_gross_wages['und'][0]['value'])/floatval($node->field_indv_comp_hrs['und'][0]['value'])) < floatval(variable_get('surveynodeform_maminwage'))) && (!isset($node->field_indv_emp_less['und'][0]['value']) || $node->field_indv_emp_less['und'][0]['value'] == '2'))
  ||
    (isset($node->field_indv_comp_hrs['und'][0]['value']) && isset($node->field_indv_comp_gross_wages['und'][0]['value']) && ((floatval($node->field_indv_comp_gross_wages['und'][0]['value'])/floatval($node->field_indv_comp_hrs['und'][0]['value'])) < floatval(variable_get('surveynodeform_maminwage'))) && $node->field_indv_emp_less['und'][0]['value'] == '1' && !isset($node->field_indv_emp_less_other['und'][0]['value'])))

      )
      ||
      (
        !isset($node->field_indv_comp10_of_12_y_n['und'][0]['value']) || $node->field_indv_comp10_of_12_y_n['und'][0]['value'] == '_none'
        )

     ) {
    return FALSE;
  }
 //Group integrated
  if ((!$node->field_grp_integ_partic['und'][0]['value']) &&
      (

        !isset($node->field_grp_integ_hrs['und'][0]['value']) ||
      !isset($node->field_grp_integ_gross_wages['und'][0]['value']) ||  (!isset($node->field_grp_location_of_work['und'][0]['value']) || $node->field_grp_location_of_work['und'][0]['value'] ==  '_none' )  || (!isset($node->field_grp_num_of_workers['und'][0]['value']) || $node->field_grp_num_of_workers['und'][0]['value'] ==  '_none' )



      ) ||
      (isset($node->field_grp_integ_hrs['und'][0]['value']) && isset($node->field_grp_integ_gross_wages['und'][0]['value']) && ((floatval($node->field_grp_integ_gross_wages['und'][0]['value'])/floatval($node->field_grp_integ_hrs['und'][0]['value'])) < floatval(variable_get('surveynodeform_maminwage'))) && (!isset($node->field_grp_sup_less['und'][0]['value']) || $node->field_grp_sup_less['und'][0]['value'] == '2'))
      ||
        (isset($node->field_grp_integ_hrs['und'][0]['value']) && isset($node->field_indv_comp_gross_wages['und'][0]['value']) && ((floatval($node->field_indv_comp_gross_wages['und'][0]['value'])/floatval($node->field_grp_integ_hrs['und'][0]['value'])) < floatval(variable_get('surveynodeform_maminwage'))) && $node->field_grp_sup_less['und'][0]['value'] == '1' && !isset($node->field_grp_sup_less_other['und'][0]['value']))
      ||
      (
        !isset($node->field_grp_10_of_12_y_n['und'][0]['value']) || $node->field_grp_10_of_12_y_n['und'][0]['value'] == '_none'
        )
     ) {
    return FALSE;
  }
 //Self employent
  if ((!$node->field_self_emp_partic['und'][0]['value']) &&
      (!isset($node->field_self_emp_hrs['und'][0]['value']) ||
      !isset($node->field_self_emp_gross_income['und'][0]['value']) ||   !isset($node->field_self_emp_gross_expens['und'][0]['value']))
     ) {
    return FALSE;
  }
//Self employent
  if ((!$node->field_self_emp_partic['und'][0]['value']) &&
      (!isset($node->field_self_emp_hrs['und'][0]['value']) ||
      !isset($node->field_self_emp_gross_income['und'][0]['value']) ||   !isset($node->field_self_emp_gross_expens['und'][0]['value']))
     ) {
    return FALSE;
  }

    // Job search and exploration activities
    if (
      !$node->field_job_search_partic['und'][0]['value'] &&
      (

       (
         (!isset($node->field_job_search_discov_plan_y_n['und'][0]['value']) || $node->field_job_search_discov_plan_y_n['und'][0]['value'] == '_none'
         ) ||
         (
           !isset($node->field_job_search_job_dev_y_n['und'][0]['value']) || $node->field_job_search_job_dev_y_n['und'][0]['value'] == '_none'
           )
         ) ||

         (
          $node->field_job_search_discov_plan_y_n['und'][0]['value'] == 'no' &&  $node->field_job_search_job_dev_y_n['und'][0]['value'] == 'no'
          )


     )
    ) {
   return FALSE;
 }

// Wrap around services
if (
  !$node->field_day_program_partic['und'][0]['value'] &&
    (

      (
        (!isset($node->field_day_program_commun_y_n['und'][0]['value']) || $node->field_day_program_commun_y_n['und'][0]['value'] == '_none') ||  (!isset($node->field_day_program_dayhab_y_n['und'][0]['value']) || $node->field_day_program_dayhab_y_n['und'][0]['value'] == '_none') ||
      (!isset($node->field_day_program_other_y_n['und'][0]['value']) || $node->field_day_program_other_y_n['und'][0]['value'] == '_none') ||
      ($node->field_day_program_other_y_n['und'][0]['value'] == 'yes' && !isset($node->field_day_program_other_explain['und'][0]['value'])) ||
      (
       $node->field_day_program_commun_y_n['und'][0]['value'] == 'no' && $node->field_day_program_dayhab_y_n['und'][0]['value'] == 'no' && $node->field_day_program_other_y_n['und'][0]['value'] == 'no'
      )

      )


    )
   ) {
  return FALSE;
  }
  if ($node->field_indv_comp_partic['und'][0]['value'] &&
  $node->field_grp_integ_partic['und'][0]['value'] &&
  $node->field_self_emp_partic['und'][0]['value'] &&
  $node->field_job_search_partic['und'][0]['value'] &&
  $node->field_day_program_partic['und'][0]['value'] &&
  (
    (!isset($node->field_indv_data_partic_why['und'][0]['value']) || $node->field_indv_data_partic_why['und'][0]['value'] == '4')
   ||
    (
      $node->field_indv_data_partic_why['und'][0]['value'] == 3 && !isset($node->field_indv_data_partic_other['und'][0]['value'])
      )
  )
  )
  {
  return FALSE;
  }









  return TRUE;
}
