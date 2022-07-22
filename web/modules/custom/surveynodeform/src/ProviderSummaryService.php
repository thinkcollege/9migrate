<?php
namespace Drupal\surveynodeform;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use \DateTime;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\NodeInterface;

class ProviderSummaryService
{
    protected $entityTypeManager;
    public function __construct(EntityTypeManagerInterface $entity_type_manager)
    {
        $this->entityTypeManager = $entity_type_manager;
    }


    public function surveynodeform_get_state_id($state) {
      $query = \Drupal::entityQuery('node');
      $result = $query
        ->condition('type', 'state')
        ->condition('title', $state)
        ->execute();

        $nids = array();
      if ($result) {
          $nids = \Drupal\node\Entity\Node::loadMultiple($result);

        return $nids[0];
      }

    }
    public function surveynodeform_prov_summary($provid) {
      $form['#attached']['library'][] = 'surveynodeform/surveynodeform.library';
      $statearray = surveynodeform_provider_statename($provid);
      $stateid = $statearray[1];
      $currepper = surveynodeform_get_curr_rep_per($stateid);
      $georgiaid = $this->surveynodeform_get_state_id('Georgia');
      $massid = $this->surveynodeform_get_state_id('Massachusetts');
      $mdid = $this->surveynodeform_get_state_id('Maryland');
      $query1 = \Drupal::entityQuery('node');
        $query1->condition('bundle', "individual")
            ->condition('status', NodeInterface::PUBLISHED)
            ->condition('og_group_ref', 'target_id', $provid);
              $results1 = $query1->execute();
              $personkeys = \Drupal\node\Entity\Node::loadMultiple($results1);
              $provnode = \Drupal\node\Entity\Node::load($provid);
              $providername = $provnode->title;
              $reportingnode = \Drupal\node\Entity\Node::load($currepper);
              $reportingperiod = date("F/j/Y", strtotime($reportingnode->field_rp_date_range['und'][0]['value'])) . " to " . date("F/j/Y", strtotime($reportingnode->field_rp_date_range['und'][0]['value2']));

      // Adding a new state: add state id
      switch($stateid) {
        case $massid:
          $datanode = 'individual_data_ma';
        break;
        case $mdid:
          $datanode = 'individual_data';
        break;

        case $georgiaid:
          $datanode = 'individual_data_ga';
        break;
        }

      $query = \Drupal::entityQuery('node');
      $query->condition('bundle', "$datanode")
        //  ->propertyCondition('status', NODE_PUBLISHED)
          ->condition('og_group_ref', 'target_id', $provid)
          ->condition('field_reporting_period', 'target_id', $currepper)
          ->condition('field_individual', 'target_id', $personkeys, 'IN');
            $results = $query->execute();
            $firstarray = \Drupal\node\Entity\Node::loadMultiple($results);
            $personids = array();
      // new state: change stateid
      switch($stateid) {
        case $massid:
          return $this->surveynodeform_ma_prov_sum_results($statearray,$stateid,$currepper,$providername,$reportingperiod,$firstarray,$provid);
        case $mdid:
          return $this->surveynodeform_md_prov_sum_results($statearray,$stateid,$currepper,$providername,$reportingperiod,$firstarray,$provid);

        case $georgiaid:
          return $this->surveynodeform_ga_prov_sum_results($statearray,$stateid,$currepper,$providername,$reportingperiod,$firstarray,$provid);
        }
    }
    public function surveynodeform_ga_prov_sum_results($statearray,$stateid,$currepper,$providername,$reportingperiod,$firstarray,$provid) {

      foreach($firstarray as $firstnode) {
          $personode = \Drupal\node\Entity\Node::load($firstnode);
          $personids []= $personode->field_individual['und'][0]['target_id'];
        }
      //$datanode = array_intersect($personkeys,$personids);
      $namearray = array('indcomp','grpinteg','selfemp','facbased','combased','facbasednonwork');
      $headerarray = array(' ','Total Served
      (unduplicated count)','Individual competitive job','Group integrated job','Self employment','Facility based job', 'Community based non work', 'Facility based non work');
      $captions = array("Number participating by Activity","Hours of Participation by Activity","Wages for selected two-week span during the reporting period");
      foreach($namearray as $key => $value) {
        ${$value . 'count'} = 0;
          ${$value . 'pct'} = 0;
          ${$value . 'check'} = false;
        //new state: change key based on number of categories
        if ($key < 5){
            ${$value . 'hrs'} = 0;
          ${$value . 'hrspct'} = 0;
          ${$value . 'meanhrs'} = 0;
          }



      }

      for ($i = 0 ; $i < 4 ; $i ++) {
          ${$namearray[$i] . 'wage'} = 0;
          ${$namearray[$i] . 'meanwage'} = 0;
          if ($i != 3) {
            ${$namearray[$i] . 'ptocount'} = 0;
            ${$namearray[$i] . 'ptopct'} = 0;

          }

          if ($i != 0 && $i != 3) {
            ${$namearray[$i] . 'setasidecount'} = 0;
            ${$namearray[$i] . 'setasidepct'} = 0;
          }

      }


      $hoursarray = array();
      $wagesarray = array();
      $ttlcount = 0;
      $workttlcount = 0;
      foreach($firstarray as $eachnode) {
        $thisnode = \Drupal\node\Entity\Node::load($eachnode);
        $indcompptocount = 0;
        $indcontptocount = 0;
        $grpintegptocount = 0;
        $facbasedptocount = 0;
        $indcontsetasidecount = 0;
        $grpintegsetasidecount = 0;
        $facbasedsetasidecount = 0;
        $indcomphrs = 0;
        $indconthrs = 0;
        $grpinteghrs = 0;
        $selfemphrs = 0;
        $facbasedhrs = 0;
        $combasedhrs = 0;
        $indcompwage = 0;
        $indcontwage = 0;
        $grpintegwage = 0;
        $selfempwage = 0;
        $facbasedwage = 0;
        $indcompcount = 0;
        $grpintegcount = 0;
        $selfempcount = 0;
        $facbasedcount = 0;
        $combasedcount = 0;

      //Total counts
      $indcompcheck = !$thisnode->field_indv_comp_hrs || $thisnode->field_indv_comp_hrs['und'][0]['value'] == '' ? false:true;
      if($indcompcheck) $indcompcount ++;
      $grpintegcheck = !$thisnode->field_grp_integ_hrs || $thisnode->field_grp_integ_hrs['und'][0]['value'] == '' ? false:true;
      if($grpintegcheck) $grpintegcount ++;
      $selfempcheck = !$thisnode->field_self_emp_hrs || $thisnode->field_self_emp_hrs['und'][0]['value'] == '' ? false:true;
      if($selfempcheck) $selfempcount ++;
      $facbasedcheck = !$thisnode->field_shl_hrs || $thisnode->field_shl_hrs['und'][0]['value'] == '' ? false:true;
      if($facbasedcheck) $facbasedcount ++;
      $combasedcheck = !$thisnode->field_com_non_wrk_hours || $thisnode->field_com_non_wrk_hours['und'][0]['value'] == '' ? false:true;
      if($combasedcheck) $combasedcount ++;
      $facbasednonworkcheck = $thisnode->field_fac_non_work_partic && $thisnode->field_fac_non_work_partic['und'][0]['value'] == 1 ? false : ($thisnode->field_fac_non_work_yn_partic && $thisnode->field_fac_non_work_yn_partic['und'][0]['value'] == 1 ? true:false) ;
      if($facbasednonworkcheck) $facbasednonworkcount ++;
      if($indcompcheck || $indcontcheck || $grpintegcheck || $selfempcheck || $combasedcheck || $facbasedcheck) $workttlcount++;
      if($indcompcheck || $indcontcheck || $grpintegcheck || $selfempcheck || $combasedcheck || $facbasedcheck || $facbasednonworkcheck) $ttlcount++;
      //PTO counts
      $indcompptocount += $thisnode->field_indv_comp_paid_time_off && $thisnode->field_indv_comp_paid_time_off['und'][0]['value'] == 'yes' ? 1 :0;
      $grpintegptocount += $thisnode->field_grp_integ_paid_time_off &&  $thisnode->field_grp_integ_paid_time_off['und'][0]['value'] == 'yes' ? 1 :0;
      $facbasedptocount += $thisnode->field_shl_paid_time_off && $thisnode->field_shl_paid_time_off['und'][0]['value'] == 'yes' ? 1 :0;


      //Hour counts
      $indcomphrs+= $indcompcount != 0 && $thisnode->field_indv_comp_hrs ? $thisnode->field_indv_comp_hrs['und'][0]['value'] : 0;
      $grpinteghrs+= $grpintegcount != 0 && $thisnode->field_grp_integ_hrs  ? $thisnode->field_grp_integ_hrs['und'][0]['value'] : 0;
      $selfemphrs+= $selfempcount != 0 &&  $thisnode->field_self_emp_hrs? $thisnode->field_self_emp_hrs['und'][0]['value'] : 0;
      $facbasedhrs+= $facbasedcount != 0 && $thisnode->field_shl_hrs ? $thisnode->field_shl_hrs['und'][0]['value'] : 0;
      $combasedhrs+= $combasedcount != 0 && $thisnode->field_com_non_wrk_hours ? $thisnode->field_com_non_wrk_hours['und'][0]['value'] : 0;


      //Wages
      $indcompwage +=  $indcompcount !=0 && $thisnode->field_indv_comp_gross_wages ? $thisnode->field_indv_comp_gross_wages['und'][0]['value'] : 0;
      $grpintegwage += $grpintegcount != 0 && $thisnode->field_grp_integ_gross_wages ? $thisnode->field_grp_integ_gross_wages['und'][0]['value'] : 0;
      $selfempwage += $selfempcount != 0 && $thisnode->field_self_emp_gross_income ? $thisnode->field_self_emp_gross_income['und'][0]['value'] : 0;
      $facbasedwage += $facbasedcount != 0 && $thisnode->field_shl_gross_wages ? $thisnode->field_shl_gross_wages['und'][0]['value'] : 0;

      }

      //Occupation
      $indcompoccupation = $thisnode->field_indv_comp_occupation && $thisnode->field_indv_comp_occupation['und'][0]['value'] ? $thisnode->field_indv_comp_occupation['und'][0]['value'] : '' ;
      $indcomppct =  $indcompcount != 0 ? ($indcompcount/$ttlcount) * 100 : 0;
      $indcomppct = $indcomppct != 0 ? number_format((float)$indcomppct, 1, '.', '') . "%" : " -- ";
      $indcontpct =  $indcontcount != 0 ? ($indcontcount/$ttlcount) * 100 : 0;
      $grpintegpct =  $grpintegcount != 0 ? ($grpintegcount/$ttlcount) * 100 : 0;
      $grpintegpct = $grpintegpct != 0 ? number_format((float)$grpintegpct, 1, '.', '') . "%" : " -- ";
      $selfemppct =  $selfempcount != 0 ? ($selfempcount/$ttlcount) * 100 : 0;
      $selfemppct = $selfemppct != 0 ? number_format((float)$selfemppct, 1, '.', '') . "%" : " -- ";
      $facbasedpct =  $facbasedcount != 0 ? ($facbasedcount/$ttlcount) * 100 : 0;
      $facbasedpct = $facbasedpct != 0 ? number_format((float)$facbasedpct, 1, '.', '') . "%" : " -- ";
      $combasedpct =  $combasedcount != 0 ? ($combasedcount/$ttlcount) * 100 : 0;
      $combasedpct = $combasedpct != 0 ? number_format((float)$combasedpct, 1, '.', '') . "%" : " -- ";
      $facbasednonworkpct = $facbasednonworkcount != 0 ? ($facbasednonworkcount/$ttlcount) * 100 : 0;
      $facbasednonworkpct = $facbasednonworkpct != 0 ? number_format((float)$facbasednonworkpct, 1, '.', '') . "%" : " -- ";
      $arraycount = array(array("Number participating in activity",$ttlcount,$indcompcount,$grpintegcount,$selfempcount,$facbasedcount,$combasedcount,$facbasednonworkcount),
      array("Percent participating in activity",$ttlcount,$indcomppct,$grpintegpct,$selfemppct,$facbasedpct,$combasedpct,$facbasednonworkpct));
      $ttlhour = $indcomphrs + $grpinteghrs + $selfemphrs + $facbasedhrs + $combasedhrs;
      $ttlwage = $indcompwage + $grpintegwage + $selfempwage + $facbasedwage;
      //Percentages
      $indcomphrspct = $indcomphrs > 0 ? ($indcomphrs/$ttlhour) *100 : 0;
      $indcomphrspct = $indcomphrspct > 0 ? number_format((float)$indcomphrspct, 1, '.', '') . "%" : " -- ";
      $grpinteghrspct = $grpinteghrs > 0 ? ($grpinteghrs/$ttlhour) *100 : 0;
      $grpinteghrspct = $grpinteghrspct > 0 ? number_format((float)$grpinteghrspct, 1, '.', '') . "%" : " -- ";
      $selfemphrspct = $selfemphrs > 0 ? ($selfemphrs/$ttlhour) *100 : 0;
      $selfemphrspct = $selfemphrspct > 0 ? number_format((float)$selfemphrspct, 1, '.', '') . "%" : " -- ";
      $facbasedhrspct = $facbasedhrs > 0 ? ($facbasedhrs/$ttlhour) *100 : 0;
      $facbasedhrspct = $facbasedhrspct > 0 ? number_format((float)$facbasedhrspct, 1, '.', '') . "%" : " -- ";
      $combasedhrspct = $combasedhrs > 0 ? ($combasedhrs/$ttlhour) *100 : 0;
      $combasedhrspct = $combasedhrspct > 0 ? number_format((float)$combasedhrspct, 1, '.', '') . "%" : " -- ";
      $indcompmeanhrs = $indcomphrs > 0 && $indcompcount > 0 ? floatval($indcomphrs)/floatval($indcompcount): 0;
      $indcompmeanhrs = $indcompmeanhrs > 0 ? number_format((float)$indcompmeanhrs, 1, '.', '') : " -- ";
      $grpintegmeanhrs = $grpinteghrs > 0 && $grpintegcount > 0 ? $grpinteghrs/$grpintegcount: 0;
      $grpintegmeanhrs = $grpintegmeanhrs > 0 ? number_format((float)$grpintegmeanhrs, 1, '.', '') : " -- ";
      $selfempmeanhrs = $selfemphrs > 0 && $selfempcount > 0 ? $selfemphrs/$selfempcount: 0;
      $selfempmeanhrs = $selfempmeanhrs > 0 ? number_format((float)$selfempmeanhrs, 1, '.', '') : " -- ";
      $facbasedmeanhrs = $facbasedhrs > 0 && $facbasedcount > 0 ? $facbasedhrs/$facbasedcount: 0;
      $facbasedmeanhrs = $facbasedmeanhrs > 0 ? number_format((float)$facbasedmeanhrs, 1, '.', '') : " -- ";
      $combasedmeanhrs = $combasedhrs > 0 && $combasedcount > 0 ? $combasedhrs/$combasedcount: 0;
      $combasedmeanhrs = $combasedmeanhrs > 0 ? number_format((float)$combasedmeanhrs, 1, '.', '') : " -- ";
      $hoursarray = array(array("Mean hours per person participating in activity in two-week period*",$ttlcount,$indcompmeanhrs,$grpintegmeanhrs,$selfempmeanhrs . "*",$facbasedmeanhrs,$combasedmeanhrs),array("Percent of total hours in activity for two-week period",$ttlcount,$indcomphrspct,$grpinteghrspct,$selfemphrspct,$facbasedhrspct,$combasedhrspct));
      $indcompmeanwage = $indcompwage > 0 && $indcompcount > 0 ? $indcompwage/$indcompcount: 0;
      $indcompmeanwage = $indcompmeanwage > 0 ? "$" . number_format((float)$indcompmeanwage, 2, '.', '') : " -- ";
      $grpintegmeanwage = $grpintegwage > 0 && $grpintegcount > 0 ? $grpintegwage/$grpintegcount: 0;
      $grpintegmeanwage = $grpintegmeanwage > 0 ? "$" . number_format((float)$grpintegmeanwage, 2, '.', '') : " -- ";
      $selfempmeanwage = $selfempwage > 0 && $selfempcount > 0 ? $selfempwage/$selfempcount: 0;
      $selfempmeanwage = $selfempmeanwage > 0 ? "$" . number_format((float)$selfempmeanwage, 2, '.', '') : " -- ";
      $facbasedmeanwage = $facbasedwage > 0 && $facbasedcount > 0 ? $facbasedwage/$facbasedcount: 0;
      $facbasedmeanwage = $facbasedmeanwage > 0 ? "$" . number_format((float)$facbasedmeanwage, 2, '.', '') : " -- ";
      $indcompptopct = $indcompptocount > 0 && $indcompcount > 0 ? ($indcompptocount/$indcompcount) * 100 : 0;
      $indcompptopct = number_format((float)$indcompptopct, 1, '.', '') . "%";
      $grpintegptopct = $grpintegptocount > 0 && $grpintegcount > 0 ? ($grpintegptocount/$grpintegcount) * 100 : 0;
      $grpintegptopct = number_format((float)$grpintegptopct, 1, '.', '') . "%";
      $facbasedptopct = $facbasedptocount > 0 && $facbasedcount > 0 ? ($facbasedptocount/$facbasedcount) * 100 : 0;
      $facbasedptopct = number_format((float)$facbasedptopct, 1, '.', '') . "%";
      $wagesarray = array(array("Mean two-week wage*",$ttlcount,$indcompmeanwage,$grpintegmeanwage,$selfempmeanwage . "*",$facbasedmeanwage),array("Percent earning paid time off"," -- ",$indcompptopct,$grpintegptopct," -- ",$facbasedptopct)
      );
      //new state: change below based on number of categories
      $sixarray = array_slice($headerarray, 0, 7);
      $fivearray = array_slice($headerarray,0,6);
      $data = array(
            '#type' => 'markup',
            '#prefix' => "<div class=\"clearfix\"><a class=\"viewButton btn\" href=\"/\">Back to main page</a><a class=\"viewButton btn addInd\" href=\"/provider/$provid/individuals\" data-toggle=\"tooltip\" title=\"\" data-original-title=\"View a list of your individuals\">View list of individuals</a><a class=\"btn btn-danger\" href=\"/user/logout\">Log out</a></div>
            <h3>Data for $providername for  a two-week period between the dates $reportingperiod</h3>",
            '#markup' =>  $this->surveynodeform_table($captions[0],$arraycount,$headerarray) . $this->surveynodeform_table($captions[1],$hoursarray,$sixarray). $this->surveynodeform_table($captions[2],$wagesarray,$fivearray),
            '#suffix' => '<p style="padding-bottom: 20px">* Self-employment earnings are calculated for last three months. [hours are reported for the standard 2 week period]</p>',
      );
      return $data;


    }

    public function surveynodeform_md_prov_sum_results($statearray,$stateid,$currepper,$providername,$reportingperiod,$firstarray,$provid) {

      foreach($firstarray as $firstnode) {
          $personode = \Drupal\node\Entity\Node::load($firstnode);
          $personids []= $personode->field_individual['und'][0]['target_id'];
        }
      $datanode = array_intersect($personkeys,$personids);
      $namearray = array('indcomp','indcont','grpinteg','selfemp','facbased','combased','facbasednonwork');
      $headerarray = array(' ','Total Served
      (unduplicated count)','Individual competitive job','Individual
      contracted job','Group integrated job','Self employment','Facility based job', 'Community based non work', 'Facility based non work');
      $captions = array("Number participating by Activity","Hours of Participation by Activity","Wages for selected two-week span during the reporting period");
      foreach($namearray as $key => $value) {
        ${$value . 'count'} = 0;
          ${$value . 'pct'} = 0;
          ${$value . 'check'} = false;

        if ($key < 6){
            ${$value . 'hrs'} = 0;
          ${$value . 'hrspct'} = 0;
          ${$value . 'meanhrs'} = 0;
          }



      }
      for ($i = 0 ; $i < 5 ; $i ++) {
          ${$namearray[$i] . 'wage'} = 0;
          ${$namearray[$i] . 'meanwage'} = 0;
          if ($i != 3) {
            ${$namearray[$i] . 'ptocount'} = 0;
            ${$namearray[$i] . 'ptopct'} = 0;
          }
          if ($i != 0 && $i != 3) {
            ${$namearray[$i] . 'setasidecount'} = 0;
            ${$namearray[$i] . 'setasidepct'} = 0;
          }

      }


      $hoursarray = array();
      $wagesarray = array();
      $ttlcount = 0;
      $workttlcount = 0;
      foreach($firstarray as $eachnode) {
        $thisnode = \Drupal\node\Entity\Node::load($eachnode);
      $indcompptocount = 0;
      $indcontptocount = 0;
      $grpintegptocount = 0;
      $facbasedptocount = 0;
      $indcontsetasidecount = 0;
      $grpintegsetasidecount = 0;
      $facbasedsetasidecount = 0;
      $indcomphrs = 0;
      $indconthrs = 0;
      $grpinteghrs = 0;
      $selfemphrs = 0;
      $facbasedhrs = 0;
      $combasedhrs = 0;
      $indcompwage = 0;
      $indcontwage = 0;
      $grpintegwage = 0;
      $selfempwage = 0;
      $facbasedwage = 0;
      $facbasedcount = 0;
      $indcompcount = 0;
      $indcontcount = 0;
      $grpintegcount = 0;
      $selfempcount = 0;
      $facbasedcount = 0;
      $combasedcount = 0;
      $facbasednonworkcount = 0;
      //Total counts
      $indcompcheck = !$thisnode->field_indv_comp_hrs || $thisnode->field_indv_comp_hrs['und'][0]['value'] == '' ? false:true;
      if($indcompcheck) $indcompcount ++;
      $indcontcheck = !$thisnode->field_indv_cont_hrs || $thisnode->field_indv_cont_hrs['und'][0]['value'] == '' ? false:true;
      if($indcontcheck) $indcontcount++;
      $grpintegcheck = !$thisnode->field_grp_integ_hrs || $thisnode->field_grp_integ_hrs['und'][0]['value'] == '' ? false:true;
      if($grpintegcheck) $grpintegcount ++;
      $selfempcheck = !$thisnode->field_self_emp_hrs || $thisnode->field_self_emp_hrs['und'][0]['value'] == '' ? false:true;
      if($selfempcheck) $selfempcount ++;
      $facbasedcheck = !$thisnode->field_shl_hrs || $thisnode->field_shl_hrs['und'][0]['value'] == '' ? false:true;
      if($facbasedcheck) $facbasedcount ++;
      $combasedcheck = !$thisnode->field_com_non_wrk_hours || $thisnode->field_com_non_wrk_hours['und'][0]['value'] == '' ? false:true;
      if($combasedcheck) $combasedcount ++;
      $facbasednonworkcheck = $thisnode->field_fac_non_work_partic && $thisnode->field_fac_non_work_partic['und'][0]['value'] == 1 ? false : ($thisnode->field_fac_non_work_yn_partic && $thisnode->field_fac_non_work_yn_partic['und'][0]['value'] == 1 ? true:false) ;
      if($facbasednonworkcheck) $facbasednonworkcount ++;
      if($indcompcheck || $indcontcheck || $grpintegcheck || $selfempcheck || $combasedcheck || $facbasedcheck) $workttlcount++;
      if($indcompcheck || $indcontcheck || $grpintegcheck || $selfempcheck || $combasedcheck || $facbasedcheck || $facbasednonworkcheck) $ttlcount++;
      //PTO counts
      $indcompptocount += $thisnode->field_indv_comp_paid_time_off && $thisnode->field_indv_comp_paid_time_off['und'][0]['value'] == 'yes' ? 1 :0;
      $indcontptocount += $thisnode->field_indv_cont_paid_time_off && $thisnode->field_indv_cont_paid_time_off['und'][0]['value'] == 'yes' ? 1 :0;
      $grpintegptocount += $thisnode->field_grp_integ_paid_time_off &&  $thisnode->field_grp_integ_paid_time_off['und'][0]['value'] == 'yes' ? 1 :0;
      $facbasedptocount += $thisnode->field_shl_paid_time_off && $thisnode->field_shl_paid_time_off['und'][0]['value'] == 'yes' ? 1 :0;

      $indcontsetasidecount += $thisnode->field_indv_contr_set_aside && $thisnode->field_indv_contr_set_aside['und'][0]['value'] == 'yes' ? 1 :0;
      $grpintegsetasidecount += $thisnode->field_grp_integr_set_aside && $thisnode->field_grp_integr_set_aside['und'][0]['value'] == 'yes' ? 1 :0;
      $facbasedsetasidecount += $thisnode->field_shlt_set_aside && $thisnode->field_shlt_set_aside['und'][0]['value'] == 'yes' ? 1 :0;


      //Hour counts
      $indcomphrs+= $indcompcount != 0 && $thisnode->field_indv_comp_hrs ? $thisnode->field_indv_comp_hrs['und'][0]['value'] : 0;
      $indconthrs+= $indcontcount != 0 && $thisnode->field_indv_cont_hrs ? $thisnode->field_indv_cont_hrs['und'][0]['value'] : 0;
      $grpinteghrs+= $grpintegcount != 0 && $thisnode->field_grp_integ_hrs  ? $thisnode->field_grp_integ_hrs['und'][0]['value'] : 0;
      $selfemphrs+= $selfempcount != 0 &&  $thisnode->field_self_emp_hrs? $thisnode->field_self_emp_hrs['und'][0]['value'] : 0;
      $facbasedhrs+= $facbasedcount != 0 && $thisnode->field_shl_hrs ? $thisnode->field_shl_hrs['und'][0]['value'] : 0;
      $combasedhrs+= $combasedcount != 0 && $thisnode->field_com_non_wrk_hours ? $thisnode->field_com_non_wrk_hours['und'][0]['value'] : 0;


      //Wages
      $indcompwage +=  $indcompcount !=0 && $thisnode->field_indv_comp_gross_wages ? $thisnode->field_indv_comp_gross_wages['und'][0]['value'] : 0;
      $indcontwage +=  $indcontcount !=0 && $thisnode->field_indv_cont_gross_wages ? $thisnode->field_indv_cont_gross_wages['und'][0]['value'] : 0;
      $grpintegwage += $grpintegcount != 0 && $thisnode->field_grp_integ_gross_wages ? $thisnode->field_grp_integ_gross_wages['und'][0]['value'] : 0;
      $selfempwage += $selfempcount != 0 && $thisnode->field_self_emp_gross_income ? $thisnode->field_self_emp_gross_income['und'][0]['value'] : 0;
      $facbasedwage += $facbasedcount != 0 && $thisnode->field_shl_gross_wages ? $thisnode->field_shl_gross_wages['und'][0]['value'] : 0;

      }

      $indcomppct =  $indcompcount != 0 ? ($indcompcount/$ttlcount) * 100 : 0;
      $indcomppct = $indcomppct != 0 ? number_format((float)$indcomppct, 1, '.', '') . "%" : " -- ";
      $indcontpct =  $indcontcount != 0 ? ($indcontcount/$ttlcount) * 100 : 0;
      $indcontpct = $indcontpct != 0 ? number_format((float)$indcontpct, 1, '.', '') . "%" : " -- ";
      $grpintegpct =  $grpintegcount != 0 ? ($grpintegcount/$ttlcount) * 100 : 0;
      $grpintegpct = $grpintegpct != 0 ? number_format((float)$grpintegpct, 1, '.', '') . "%" : " -- ";
      $selfemppct =  $selfempcount != 0 ? ($selfempcount/$ttlcount) * 100 : 0;
      $selfemppct = $selfemppct != 0 ? number_format((float)$selfemppct, 1, '.', '') . "%" : " -- ";
      $facbasedpct =  $facbasedcount != 0 ? ($facbasedcount/$ttlcount) * 100 : 0;
      $facbasedpct = $facbasedpct != 0 ? number_format((float)$facbasedpct, 1, '.', '') . "%" : " -- ";
      $combasedpct =  $combasedcount != 0 ? ($combasedcount/$ttlcount) * 100 : 0;
      $combasedpct = $combasedpct != 0 ? number_format((float)$combasedpct, 1, '.', '') . "%" : " -- ";
      $facbasednonworkpct = $facbasednonworkcount != 0 ? ($facbasednonworkcount/$ttlcount) * 100 : 0;
      $facbasednonworkpct = $facbasednonworkpct != 0 ? number_format((float)$facbasednonworkpct, 1, '.', '') . "%" : " -- ";
      $arraycount = array(array("Number participating in activity",$ttlcount,$indcompcount,$indcontcount,$grpintegcount,$selfempcount,$facbasedcount,$combasedcount,$facbasednonworkcount),
      array("Percent participating in activity",$ttlcount,$indcomppct,$indcontpct,$grpintegpct,$selfemppct,$facbasedpct,$combasedpct,$facbasednonworkpct));
      $ttlhour = $indcomphrs + $indconthrs + $grpinteghrs + $selfemphrs + $facbasedhrs + $combasedhrs;
      $ttlwage = $indcompwage + $indcontwage + $grpintegwage + $selfempwage + $facbasedwage;
      //Percentages
      $indcomphrspct = $indcomphrs > 0 ? ($indcomphrs/$ttlhour) *100 : 0;
      $indcomphrspct = $indcomphrspct > 0 ? number_format((float)$indcomphrspct, 1, '.', '') . "%" : " -- ";
      $indconthrspct = $indconthrs > 0 ? ($indconthrs/$ttlhour) *100 : 0;
      $indconthrspct = $indconthrspct > 0 ? number_format((float)$indconthrspct, 1, '.', '') . "%" : " -- ";
      $grpinteghrspct = $grpinteghrs > 0 ? ($grpinteghrs/$ttlhour) *100 : 0;
      $grpinteghrspct = $grpinteghrspct > 0 ? number_format((float)$grpinteghrspct, 1, '.', '') . "%" : " -- ";
      $selfemphrspct = $selfemphrs > 0 ? ($selfemphrs/$ttlhour) *100 : 0;
      $selfemphrspct = $selfemphrspct > 0 ? number_format((float)$selfemphrspct, 1, '.', '') . "%" : " -- ";
      $facbasedhrspct = $facbasedhrs > 0 ? ($facbasedhrs/$ttlhour) *100 : 0;
      $facbasedhrspct = $facbasedhrspct > 0 ? number_format((float)$facbasedhrspct, 1, '.', '') . "%" : " -- ";
      $combasedhrspct = $combasedhrs > 0 ? ($combasedhrs/$ttlhour) *100 : 0;
      $combasedhrspct = $combasedhrspct > 0 ? number_format((float)$combasedhrspct, 1, '.', '') . "%" : " -- ";
      $indcompmeanhrs = $indcomphrs > 0 && $indcompcount > 0 ? $indcomphrs/$indcompcount: 0;
      $indcompmeanhrs = $indcompmeanhrs > 0 ? number_format((float)$indcompmeanhrs, 1, '.', '') : " -- ";
      $indcontmeanhrs = $indconthrs > 0 && $indcontcount > 0 ? $indconthrs/$indcontcount: 0;
      $indcontmeanhrs = $indcontmeanhrs > 0 ? number_format((float)$indcontmeanhrs, 1, '.', '') : " -- ";
      $grpintegmeanhrs = $grpinteghrs > 0 && $grpintegcount > 0 ? $grpinteghrs/$grpintegcount: 0;
      $grpintegmeanhrs = $grpintegmeanhrs > 0 ? number_format((float)$grpintegmeanhrs, 1, '.', '') : " -- ";
      $selfempmeanhrs = $selfemphrs > 0 && $selfempcount > 0 ? $selfemphrs/$selfempcount: 0;
      $selfempmeanhrs = $selfempmeanhrs > 0 ? number_format((float)$selfempmeanhrs, 1, '.', '') : " -- ";
      $facbasedmeanhrs = $facbasedhrs > 0 && $facbasedcount > 0 ? $facbasedhrs/$facbasedcount: 0;
      $facbasedmeanhrs = $facbasedmeanhrs > 0 ? number_format((float)$facbasedmeanhrs, 1, '.', '') : " -- ";
      $combasedmeanhrs = $combasedhrs > 0 && $combasedcount > 0 ? $combasedhrs/$combasedcount: 0;
      $combasedmeanhrs = $combasedmeanhrs > 0 ? number_format((float)$combasedmeanhrs, 1, '.', '') : " -- ";
      $hoursarray = array(array("Mean hours per person participating in activity in two-week period*",$ttlcount,$indcompmeanhrs,$indcontmeanhrs,$grpintegmeanhrs,$selfempmeanhrs . "*",$facbasedmeanhrs,$combasedmeanhrs),array("Percent of total hours in activity for two-week period",$ttlcount,$indcomphrspct,$indconthrspct,$grpinteghrspct,$selfemphrspct,$facbasedhrspct,$combasedhrspct));
      $indcompmeanwage = $indcompwage > 0 && $indcompcount > 0 ? $indcompwage/$indcompcount: 0;
      $indcompmeanwage = $indcompmeanwage > 0 ? "$" . number_format((float)$indcompmeanwage, 2, '.', '') : " -- ";
      $indcontmeanwage = $indcontwage > 0 && $indcontcount > 0 ? $indcontwage/$indcontcount: 0;
      $indcontmeanwage = $indcontmeanwage > 0 ? "$" . number_format((float)$indcontmeanwage, 2, '.', '') : " -- ";
      $grpintegmeanwage = $grpintegwage > 0 && $grpintegcount > 0 ? $grpintegwage/$grpintegcount: 0;
      $grpintegmeanwage = $grpintegmeanwage > 0 ? "$" . number_format((float)$grpintegmeanwage, 2, '.', '') : " -- ";
      $selfempmeanwage = $selfempwage > 0 && $selfempcount > 0 ? $selfempwage/$selfempcount: 0;
      $selfempmeanwage = $selfempmeanwage > 0 ? "$" . number_format((float)$selfempmeanwage, 2, '.', '') : " -- ";
      $facbasedmeanwage = $facbasedwage > 0 && $facbasedcount > 0 ? $facbasedwage/$facbasedcount: 0;
      $facbasedmeanwage = $facbasedmeanwage > 0 ? "$" . number_format((float)$facbasedmeanwage, 2, '.', '') : " -- ";
      $indcompptopct = $indcompptocount > 0 && $indcompcount > 0 ? ($indcompptocount/$indcompcount) * 100 : 0;
      $indcompptopct = number_format((float)$indcompptopct, 1, '.', '') . "%";
      $indcontptopct = $indcontptocount > 0 && $indcontcount > 0 ? ($indcontptocount/$indcontcount) * 100 : 0;
      $indcontptopct = number_format((float)$indcontptopct, 1, '.', '') . "%";
      $grpintegptopct = $grpintegptocount > 0 && $grpintegcount > 0 ? ($grpintegptocount/$grpintegcount) * 100 : 0;
      $grpintegptopct = number_format((float)$grpintegptopct, 1, '.', '') . "%";
      $facbasedptopct = $facbasedptocount > 0 && $facbasedcount > 0 ? ($facbasedptocount/$facbasedcount) * 100 : 0;
      $facbasedptopct = number_format((float)$facbasedptopct, 1, '.', '') . "%";
      $indcontsetasidepct = $indcontsetasidecount > 0 && $indcontcount > 0 ? ($indcontsetasidecount/$indcontcount) * 100 : 0;
      $indcontsetasidepct = number_format((float)$indcontsetasidepct, 1, '.', '') . "%";
      $grpintegsetasidepct = $grpintegsetasidecount > 0 && $grpintegcount > 0 ? ($grpintegsetasidecount/$grpintegcount) * 100 : 0;
      $grpintegsetasidepct = number_format((float)$grpintegsetasidepct, 1, '.', '') . "%";
      $facbasedsetasidepct = $facbasedsetasidecount > 0 && $facbasedcount ? ($facbasedsetasidecount/$facbasedcount) * 100 : 0;
      $facbasedsetasidepct = number_format((float)$facbasedsetasidepct, 1, '.', '') . "%";
      $wagesarray = array(array("Mean two-week wage*",$ttlcount,$indcompmeanwage,$indcontmeanwage,$grpintegmeanwage,$selfempmeanwage . "*",$facbasedmeanwage),array("Percent earning paid time off"," -- ",$indcompptopct,$indcontptopct,$grpintegptopct," -- ",$facbasedptopct),array("Percent on set-aside contract"," "," -- ",$indconthrspct,$grpinteghrspct," -- ",$facbasedhrspct)
      );
      $sixarray = array_slice($headerarray, 0, 8);
      $fivearray = array_slice($headerarray,0,7);
      $data = array(
            '#type' => 'markup',
            '#prefix' => "<div class=\"clearfix\"><a class=\"viewButton btn\" href=\"/\">Back to main page</a><a class=\"viewButton btn addInd\" href=\"/provider/$provid/individuals\" data-toggle=\"tooltip\" title=\"\" data-original-title=\"View a list of your individuals\">View list of individuals</a><a class=\"btn btn-danger\" href=\"/user/logout\">Log out</a></div>
            <h3>Data for $providername for  a two-week period between the dates $reportingperiod</h3>",
            '#markup' =>  $this->surveynodeform_table($captions[0],$arraycount,$headerarray) . $this->surveynodeform_table($captions[1],$hoursarray,$sixarray). $this->surveynodeform_table($captions[2],$wagesarray,$fivearray),
            '#suffix' => '<p style="padding-bottom: 20px">* Self-employment earinings and hours are calculated for last three months.</p>',
      );
      return $data;


    }

    public function surveynodeform_ma_prov_sum_results($statearray,$stateid,$currepper,$providername,$reportingperiod,$firstarray,$provid) {

              foreach($firstarray as $firstnode) {
                 $personode = \Drupal\node\Entity\Node::load($firstnode);
                  $personids []= $personode->field_individual['und'][0]['target_id'];
                }
              //$datanode = array_intersect($personkeys,$personids);
              $namearray = array('indcomp','grpinteg','selfemp','jobsearch','wraparound');
              $headerarray = array(' ','Total Served
              (unduplicated count)','Individual employment','Group supported job','Self employment','Job search and exploration', 'Wrap-around services');
              $captions = array("Number participating by Activity","Hours of Participation by Activity","Wages for selected two-week span during the reporting period");
              foreach($namearray as $key => $value) {
                ${$value . 'count'} = 0;
                 ${$value . 'pct'} = 0;

                if ($key < 3){
                   ${$value . 'hrs'} = 0;
                  ${$value . 'hrspct'} = 0;
                 ${$value . 'meanhrs'} = 0;
                 ${$value . 'check'} = false;
                 }



              }
              for ($i = 0 ; $i < 3 ; $i ++) {
                 ${$namearray[$i] . 'wage'} = 0;
                 ${$namearray[$i] . 'meanwage'} = 0;
                 if ($i != 3) {
                   ${$namearray[$i] . 'ptocount'} = 0;
                   ${$namearray[$i] . 'ptopct'} = 0;
                 }
                 if ($i != 0 && $i != 3) {
                   ${$namearray[$i] . 'setasidecount'} = 0;
                   ${$namearray[$i] . 'setasidepct'} = 0;
                 }

              }


              $hoursarray = array();
              $wagesarray = array();
              $ttlcount = 0;
              $workcount = 0;

              foreach($firstarray as $eachnode) {
                $indcomphrs = 0;
                $indcompptocount = 0;
                $selfemphrs = 0;
                $grpinteghrs =0;
                $indcompwage = 0;
                $grpintegwage = 0;
                $selfempwage = 0;
                $indcompptocount = 0;
                $indcontptocount = 0;
                $grpintegptocount = 0;
                $facbasedptocount = 0;
                $indcontsetasidecount = 0;
                $grpintegsetasidecount = 0;
                $facbasedsetasidecount = 0;
                $facbasedcount = 0;
                $indcompcount = 0;
                $indcontcount = 0;
                $grpintegcount = 0;
                $selfempcount = 0;
                $facbasedcount = 0;
                $combasedcount = 0;
                $facbasednonworkcount = 0;
                $thisnode = \Drupal\node\Entity\Node::load($eachnode);
                $indcompcheck = !$thisnode->field_indv_comp_hrs || $thisnode->field_indv_comp_hrs['und'][0]['value'] == '' ? false:true;
                if($indcompcheck) $indcompcount ++;
                $grpintegcheck = !$thisnode->field_grp_integ_hrs || $thisnode->field_grp_integ_hrs['und'][0]['value'] == '' ? false:true;
                if($grpintegcheck) $grpintegcount ++;
                $selfempcheck = !$thisnode->field_self_emp_hrs || $thisnode->field_self_emp_hrs['und'][0]['value'] == '' ? false:true;
                if($selfempcheck) $selfempcount ++;
                $jobsearchcheck = $thisnode->field_job_search_partic && $thisnode->field_job_search_partic['und'][0]['value'] == 1 ? false : (!$thisnode->field_job_search_job_dev_y_n['und'][0]['value'] == 'yes' && !$thisnode->field_job_search_discov_plan_y_n['und'][0]['value'] == 'yes' ? false: true) ;
                if($jobsearchcheck) $jobsearchcount ++;
                $wraparoundcheck = $thisnode->field_day_program_partic && $thisnode->field_day_program_partic['und'][0]['value'] == 1 ? false : (!$thisnode->field_day_program_commun_y_n['und'][0]['value'] == 'yes' && !$thisnode->field_day_program_dayhab_y_n['und'][0]['value'] == 'yes' && !$thisnode->field_day_program_other_y_n['und'][0]['value'] == 'yes' ? false:true) ;
                if($wraparoundcheck) $wraparoundcount ++;
                if($indcompcheck || $groupintegcheck || $selfempcheck) $workcount++;
                if($indcompcheck ||$grpintegcheck || $selfempcheck || $jobsearchcheck || $wraparoundcheck) $ttlcount++;
                //PTO counts
                $indcompptocount += $thisnode->field_indv_paid_time_y_n && $thisnode->field_indv_paid_time_y_n['und'][0]['value'] == 'yes' ? 1 :0;
                $selfemphrs+= $selfempcount != 0 &&  $thisnode->field_self_emp_hrs? $thisnode->field_self_emp_hrs['und'][0]['value'] : 0;
                $indcomphrs+= $indcompcount != 0 && $thisnode->field_indv_comp_hrs ? $thisnode->field_indv_comp_hrs['und'][0]['value'] : 0;
                $grpinteghrs+= $grpintegcount != 0 && $thisnode->field_grp_integ_hrs  ? $thisnode->field_grp_integ_hrs['und'][0]['value'] : 0;
                $selfemphrs+= $selfempcount != 0 &&  $thisnode->field_self_emp_hrs? $thisnode->field_self_emp_hrs['und'][0]['value'] : 0;

                //Wages
                $indcompwage +=  $indcompcount !=0 && $thisnode->field_indv_comp_gross_wages ? $thisnode->field_indv_comp_gross_wages['und'][0]['value'] : 0;
                $grpintegwage += $grpintegcount != 0 && $thisnode->field_grp_integ_gross_wages ? $thisnode->field_grp_integ_gross_wages['und'][0]['value'] : 0;
                $selfempwage += $selfempcount != 0 && $thisnode->field_self_emp_gross_income ? $thisnode->field_self_emp_gross_income['und'][0]['value'] : 0;
                // print "$eachnode<br /> $indcompcount<br />$grpintegcount<br />$selfempcount<br />$jobsearchcount<br />$wraparoundcount<br /><strong>$ttlcount</strong>&nbsp;<br />";


              }
              $ttlhour = $indcomphrs + $grpinteghrs + $selfemphrs;
              $ttlwage = $indcompwage + $grpintegwage + $selfempmeanwage;
            $indcomppct =  $indcompcount != 0 ? ($indcompcount/$ttlcount) * 100 : 0;
            $indcomppct = $indcomppct != 0 ? number_format((float)$indcomppct, 1, '.', '') . "%" : " -- ";
                $grpintegpct =  $grpintegcount != 0 ? ($grpintegcount/$ttlcount) * 100 : 0;
                $grpintegpct = $grpintegpct != 0 ? number_format((float)$grpintegpct, 1, '.', '') . "%" : " -- ";
                $selfemppct =  $selfempcount != 0 ? ($selfempcount/$ttlcount) * 100 : 0;
                $selfemppct = $selfemppct != 0 ? number_format((float)$selfemppct, 1, '.', '') . "%" : " -- ";
                $jobsearchpct =  $jobsearchcount != 0 ? ($jobsearchcount/$ttlcount) * 100 : 0;
                $jobsearchpct = $jobsearchpct != 0 ? number_format((float)$jobsearchpct, 1, '.', '') . "%" : " -- ";
                $wraparoundpct =  $wraparoundcount != 0 ? ($wraparoundcount/$ttlcount) * 100 : 0;
                $wraparoundpct = $wraparoundpct != 0 ? number_format((float)$wraparoundpct, 1, '.', '') . "%" : " -- ";


                //Percentages
                $indcomphrspct = $indcomphrs > 0 ? ($indcomphrs/$ttlhour) *100 : 0;
                $indcomphrspct = $indcomphrspct > 0 ? number_format((float)$indcomphrspct, 1, '.', '') . "%" : " -- ";
                $grpinteghrspct = $grpinteghrs > 0 ? ($grpinteghrs/$ttlhour) *100 : 0;
                $grpinteghrspct = $grpinteghrspct > 0 ? number_format((float)$grpinteghrspct, 1, '.', '') . "%" : " -- ";
                $selfemphrspct = $selfemphrs > 0 ? ($selfemphrs/$ttlhour) *100 : 0;
                $selfemphrspct = $selfemphrspct > 0 ? number_format((float)$selfemphrspct, 1, '.', '') . "%" : " -- ";
                $indcompmeanhrs = $indcomphrs > 0 && $indcompcount > 0 ? $indcomphrs/$indcompcount: 0;
                $indcompmeanhrs = $indcompmeanhrs > 0 ? number_format((float)$indcompmeanhrs, 1, '.', '') : " -- ";
                $grpintegmeanhrs = $grpinteghrs > 0 && $grpintegcount > 0 ? $grpinteghrs/$grpintegcount: 0;
                $grpintegmeanhrs = $grpintegmeanhrs > 0 ? number_format((float)$grpintegmeanhrs, 1, '.', '') : " -- ";
                $selfempmeanhrs = $selfemphrs > 0 && $selfempcount > 0 ? $selfemphrs/$selfempcount: 0;
                $selfempmeanhrs = $selfempmeanhrs > 0 ? number_format((float)$selfempmeanhrs, 1, '.', '') : " -- ";
                $indcompmeanwage = $indcompwage > 0 && $indcompcount > 0 ? $indcompwage/$indcompcount: 0;
                $indcompmeanwage = $indcompmeanwage > 0 ? "$" . number_format((float)$indcompmeanwage, 2, '.', '') : " -- ";
                $grpintegmeanwage = $grpintegwage > 0 && $grpintegcount > 0 ? $grpintegwage/$grpintegcount: 0;
                $grpintegmeanwage = $grpintegmeanwage > 0 ? "$" . number_format((float)$grpintegmeanwage, 2, '.', '') : " -- ";
                $selfempmeanwage = $selfempwage > 0 && $selfempcount > 0 ? $selfempwage/$selfempcount: 0;
                $selfempmeanwage = $selfempmeanwage > 0 ? "$" . number_format((float)$selfempmeanwage, 2, '.', '') : " -- ";
                $indcompptopct = $indcompptocount > 0 && $indcompcount > 0 ? ($indcompptocount/$indcompcount) * 100 : 0;
                $indcompptopct = number_format((float)$indcompptopct, 1, '.', '') . "%";
              $wagesarray = array(array("Mean four-week wage*",$workcount,$indcompmeanwage,$grpintegmeanwage,$selfempmeanwage . "*"),array("Percent earning paid time off",$indcompcount,$indcompptopct," -- ","--")
            );

            $hoursarray = array(array("Mean hours per person participating in activity in four-week period*",$workcount,$indcompmeanhrs,$grpintegmeanhrs,$selfempmeanhrs . "*"),array("Percent of total hours in activity for four-week period",$workcount,$indcomphrspct,$grpinteghrspct,$selfemphrspct));
              $arraycount = array(array("Number participating in activity",$ttlcount,$indcompcount,$grpintegcount,$selfempcount,$jobsearchcount,$wraparoundcount),
              array("Percent participating in activity",$ttlcount,$indcomppct,$grpintegpct,$selfemppct,$jobsearchpct,$wraparoundpct));
     $sixarray = array_slice($headerarray, 0, 8);
     $fivearray = array_slice($headerarray,0,5);
     $data = array(
          '#type' => 'markup',
          '#prefix' => "<div class=\"clearfix\"><a class=\"viewButton btn\" href=\"/\">Back to main page</a><a class=\"viewButton btn addInd\" href=\"/provider/$provid/individuals\" data-toggle=\"tooltip\" title=\"\" data-original-title=\"View a list of your individuals\">View list of individuals</a><a class=\"btn btn-danger\" href=\"/user/logout\">Log out</a></div>
          <h3>Data for $providername for the four week period $reportingperiod</h3>",
          '#markup' =>  $this->surveynodeform_table($captions[0],$arraycount,$headerarray) . $this->surveynodeform_table($captions[1],$hoursarray,$fivearray). $this->surveynodeform_table($captions[2],$wagesarray,$fivearray),
          '#suffix' => '<p style="padding-bottom: 20px">* Self-employment earinings and hours are calculated for last three months.</p>',
      );
      return $data;


    }


    public function surveynodeform_table($caption = NULL,$rows,$header) {
      $tablecontent = "<table class=\"summTable\">";
      $tablecontent .= $caption ? "<caption>$caption</caption>" : "";
      $tablecontent .= "<tr>";
      foreach($header as $headcell) {
      $tablecontent .= "<th>$headcell</th>";
      }

      $tablecontent .= "</tr>";
      $rowno = 0;
      foreach($rows as $row) {
        $cellno = 0;
        $tablecontent .="<tr class=\"row$rowno\">";
        foreach($row as $cell) {


           $tablecontent .= "<td class=\"cell$cellno\">$cell</td>";
           $cellno ++;
         }
       $tablecontent .= "</tr>";
       $rowno++;
      }
      $tablecontent .= "</table>";


      /*
      $header = $header;
      $data = array(
                $rows,
      );
      $output = theme('table',
               array(
                 'header' => $header,
                      'rows' => $data )); */
      return $tablecontent;
    }

    public function surveynodeform_preprocess_node(&$vars){
      global $nid;
      $current_path = \Drupal::service('path.current')->getPath();
      $path_args = explode('/', $current_path);
        if ($path_args[0] == 'node' && is_numeric($path_args[1]))   $nid = $path_args[1];



    }


}
