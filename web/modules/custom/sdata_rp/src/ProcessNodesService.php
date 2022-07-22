<?php
namespace Drupal\surveynodeform;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use \DateTime;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\NodeInterface;

class ProcessNodesService
{
    protected $entityTypeManager;
    public function __construct(EntityTypeManagerInterface $entity_type_manager)
    {
        $this->entityTypeManager = $entity_type_manager;
    }
    protected function sdata_rp_batch_enrol()
    {
      global $user;
      $adminuserid =$user->uid;
      if($adminuserid > 0) {
        $result = db_query("select distinct node.nid from node where node.type = 'provider' and node.nid not in (SELECT  DISTINCT gid from og_membership where etid = $adminuserid AND entity_type = 'user')");
        $uid = $adminuserid;
        $messageids = "";
        foreach ($result as $record) {
         $gid = $record->nid;
         $messageids .= ", " . $record->nid;
         $values['entity'] = user_load($uid);
         $values['entity type'] = 'user';
          $values['state'] = OG_STATE_ACTIVE;
         og_group('node',$gid,$values);
        }
       } else {
        return;
        }
      if($gid != ""){
        drupal_set_message(t("You were added to provider groups with the following id(s): $gid"), 'status');
       } else {
         drupal_set_message(t("You are already a member of all groups."), 'status');
       }




      return drupal_goto("/");

     }
     protected function sdata_rp_ga_batch_enrol()
    {
      global $user;
      $gaadminuserid =$user->uid;
      if($gaadminuserid > 0) {
         $georgiaresult = db_query("select nid from node where title = 'Georgia' and type = 'state'")->fetchObject();
         $georgiaid = $georgiaresult->nid;

           watchdog('sdata_rp', 'Georgia id: ' . $georgiaid);

         $result = db_query("select etid from og_membership where gid = $georgiaid and field_name = 'og_group_provider_state_ref' and og_membership.etid not in (SELECT  DISTINCT gid from og_membership where etid = $gaadminuserid AND entity_type = 'user')");


         $uid = $gaadminuserid;
         $messageids = "";
         foreach ($result as $record) {
           $gid = $record->etid;
           $messageids .= ", " . $record->etid;
           $values['entity'] = user_load($uid);
           $values['entity type'] = 'user';
           $values['state'] = OG_STATE_ACTIVE;
           og_group('node',$gid,$values);
         }

       } else {
        return;
        }
      if($gid != ""){
        drupal_set_message(t("You were added to provider groups with the following id(s): $gid"), 'status');
       } else {
         drupal_set_message(t("You are already a member of all groups."), 'status');
       }




      return drupal_goto("/");

     }

   protected function sdata_rp_node_postinsert($node) {
       global $user;

    if ((in_array('MA user', $user->roles) || in_array('MD user', $user->roles) || in_array('GA user', $user->roles) || in_array('Survey administrator', $user->roles)) && !in_array('administrator', $user->roles) || in_array('GA Admin', $user->roles))
    {

       if ($node->type == 'individual') {
         $provider_id = $node->og_group_ref[LANGUAGE_NONE][0]['target_id'];
         $provider = node_load($provider_id);
         $state_id = $provider->og_group_provider_state_ref[LANGUAGE_NONE][0]['target_id'];
         $state = node_load($state_id);
         $ciecheck = $node->field_cie_individual[LANGUAGE_NONE][0]['value'] == 1 ? true : false;
         $state_individual_data_content_type = $state->field_individual_data_type[LANGUAGE_NONE][0]['value'];
         $reporting_period_nid = $state->field_current_reporting_period[LANGUAGE_NONE][0]['target_id'];
         $toggle_period_nid = $reporting_period_nid;
         $legacy_period_nid = $state->field_legacy_reporting_period[LANGUAGE_NONE][0]['target_id'];
         $legacyarray = array();
         module_load_include('inc', 'sdata_rp', 'views/sdata_rp_handler_submit_completed_field');
         if($legacy_period_nid) $legacyarray = _sdata_rp_switch_period($state_individual_data_content_type, $provider_id, $legacy_period_nid);
         if($legacyarray[0] && !$legacyarray[1] && !$legacyarray[2])  $toggle_period_nid = $legacy_period_nid;
         $toggle_period_node = node_load($legacy_period_nid);

           //if($ciecheck) $state_individual_data_content_type = 'individual_data_ga_cie';
         _sdata_rp_create_new_individual_data_node($node, $state_individual_data_content_type, $toggle_period_node, $provider, false);
       }
    }
   }

   /**
    * Implements hook_permission().
    */
   protected function sdata_rp_permission() {
     return array(
       'sdata submit individual data' => array(
         'title' => t('Submit individual data'),
         'description' => t('Submit completed individual data for a reporting period.'),
       ),
     );
   }

   /**
    * Implements hook_views_api
    */
   protected function sdata_rp_views_api() {
     return array(
       'api' => 3,
       'path' => drupal_get_path('module', 'sdata_rp'),
     );
   }

   /**
    * The batch callback.
    */
   public function _sdata_rp_batch_create_individual_data_nodes($reporting_period_node_id) {
     $reporting_period_node = node_load($reporting_period_node_id);
     $cieperiod = $reporting_period_node->field_georgia_cie[LANGUAGE_NONE][0]['value'];

     if ($reporting_period_node->type != 'reporting_period') {
       return;
     }

     // Get the state node id. This assumes each reporting period has only one state.
     $state_nid = $reporting_period_node->og_group_state_ref[LANGUAGE_NONE][0]['target_id'];

     // Load the state node to get its individual data content type machine name.
     $state_node = node_load($state_nid);
     $state_individual_data_content_type = $state_node->field_individual_data_type[LANGUAGE_NONE][0]['value'];

     // Get list of providers.
     $query = new EntityFieldQuery();
     $query->entityCondition('entity_type', 'node')
       ->entityCondition('bundle', 'provider')
       ->propertyCondition('status', NODE_PUBLISHED);
     $result = $query->execute();
     // Load the providers into an array.
     if (isset($result['node'])) {
       $provider_nids = array_keys($result['node']);
       $providers = entity_load('node', $provider_nids);
     }

     // Filter out providers who don't operate in the state given in the term.
     $state_providers = array();
     foreach ($providers as $key => $provider) {
       $provider_state_nid = $provider->og_group_provider_state_ref[LANGUAGE_NONE][0]['target_id'];
       if ($provider_state_nid != $state_nid) {
         continue;
       }
       $state_providers[] = $provider;
     }

     $batch = array(
       'operations' => array(),
       'finished' => '_sdata_rp_batch_create_individual_data_nodes_finished',
       'title' => t('Create individual data nodes for @state, @reporting_period', array('@state' => $state_node->title, '@reporting_period' => $reporting_period_node->title)),
       'init_message' => t('Creating individual data nodes...'),
       'progress_message' => t('Created @current out of @total.'),
       'error_message' => t('There has been an error.')
     );

     // Find all individual nodes that belong to each provider.
     foreach ($state_providers as $provider) {

         $query = new EntityFieldQuery();
         if($cieperiod == '1') {
           $query->entityCondition('entity_type', 'node')
             ->entityCondition('bundle', 'individual')
             ->fieldCondition('og_group_ref', 'target_id', $provider->nid)
             ->fieldCondition('field_cie_individual', 'value', '1', '=')
             ->propertyCondition('status', NODE_PUBLISHED);

         } else {
           $query->entityCondition('entity_type', 'node')
             ->entityCondition('bundle', 'individual')
             ->fieldCondition('og_group_ref', 'target_id', $provider->nid)
             ->propertyCondition('status', NODE_PUBLISHED);
         }
         $result = $query->execute();



       // Load the individuals into an array.
       if (isset($result['node'])) {
         // Loop around each individual to create individual data nodes for the
         // given reporting period.
         foreach($result['node'] as $individual) {
           $individual_node = node_load($individual->nid);
           $batch['operations'][] = array('_sdata_rp_batch_create_individual_data_node_process', array($individual_node, $state_individual_data_content_type, $reporting_period_node, $provider));
         }
       }
     }
     batch_set($batch);
     batch_process('admin/states'); // The path to redirect to when done.
   }

   /**
    * The batch processor.
    */
   protected function _sdata_rp_batch_create_individual_data_node_process($individual_node, $state_individual_data_content_type, $reporting_period_node, $provider, &$context) {
     $context['message'] = "Creating individual data node for " . $individual_node->title . "...";
     _sdata_rp_create_new_individual_data_node($individual_node, $state_individual_data_content_type, $reporting_period_node, $provider, true);
   }

   /**
    * The batch finish handler.
    */
   protected function _sdata_rp_batch_create_individual_data_nodes_finished($success, $results, $operations) {
     if ($success) {
       drupal_set_message('Individual data nodes created!');
     }
     else {
       $error_operation = reset($operations);
       $message = t('An error occurred while processing %error_operation with arguments: @arguments', array(
         '%error_operation' => $error_operation[0],
         '@arguments' => print_r($error_operation[1], TRUE)
       ));
       drupal_set_message($message, 'error');
     }
   }

   protected function _sdata_rp_create_new_individual_data_node($individual, $individual_data_content_type, $reporting_period_node, $provider, $batch) {
     global $user;
     //watchdog('hook_post_action_test', "The inserted node {$individual->type} id is {$individual->nid} Reporting period node id is {$reporting_period_node->nid}  State content type is $individual_data_content_type Provider id is {$provider->nid} from " . __FUNCTION__);

     $provider_id = $provider->nid;
     $state_id = $provider->og_group_provider_state_ref[LANGUAGE_NONE][0]['target_id'];
     $state = node_load($state_id);
     $ciecheck = $individual->field_cie_individual[LANGUAGE_NONE][0]['value'] == 1 ? true : false;
     $state_individual_data_content_type = $state->field_individual_data_type[LANGUAGE_NONE][0]['value'];
     $reporting_period_nid = $reporting_period_node->nid;
     $legacy_period_nid = $state->field_legacy_reporting_period[LANGUAGE_NONE][0]['target_id'] ? $state->field_legacy_reporting_period[LANGUAGE_NONE][0]['target_id'] : null;
     $legacyarray = array();
     //module_load_include('inc', 'sdata_rp', 'views/sdata_rp_handler_submit_completed_field');
     if($legacy_period_nid && !$ciecheck && $batch == false ) {
       $legacyarray = _sdata_rp_switch_period($state_individual_data_content_type, $provider_id, $legacy_period_nid);
       $legacyarrayprint = print_r($legacyarray,true);
       watchdog('hook_post_action_test', "Legacy array: " . $legacyarrayprint . " " . __FUNCTION__);
       // if legacy period not completed
       if(!$legacyarray[1])  {
         $reporting_period_nid = $legacy_period_nid;
         $reporting_period_node = node_load($legacy_period_nid);
       }
     }

   //If there is a legacy period open and the individual is not CIE then the new individual data node gets assigned to the legacy period, because the current period is not open for the user. If the individual is CIE they should get added to the current reporting period because that can't be legacy.
     // Wrap up the node in a nice package.
     $reporting_period_wrapper = entity_metadata_wrapper('node', $reporting_period_node);

     // Check to see if an individual data node already exists for this
     // individual/provider/reporting-period combination. If so, don't create another.
     $query = new EntityFieldQuery();
     $query->entityCondition('entity_type', 'node')
       ->entityCondition('bundle', $individual_data_content_type)
       ->fieldCondition('field_individual', 'target_id', $individual->nid)
       ->fieldCondition('og_group_ref', 'target_id', $provider->nid)
       ->fieldCondition('field_reporting_period', 'target_id', $reporting_period_nid);
     $result = $query->execute();
     if (isset($result['node'])) {
       // Individual data node found, bail.
       return false;
     }

     $node = new stdClass();
     $node->title = $individual->title . ' data for ' . preg_replace('~\([^()]*\)~', '', $reporting_period_wrapper->title->value());
     $node->type = $individual_data_content_type;
     node_object_prepare($node); // Sets some defaults. Invokes hook_prepare() and hook_node_prepare().
     $node->language = LANGUAGE_NONE; // Or e.g. 'en' if locale is enabled
     $node->uid = $user->uid;
     $node->status = 1; //(1 or 0): published or not
     $node->promote = 0; //(1 or 0): promoted to front page
     $node->comment = 0; // 0 = comments disabled, 1 = read only, 2 = read/write

     // Set reporting period.
     $node->field_reporting_period[$node->language][]['target_id'] = $reporting_period_wrapper->getIdentifier();

     // Set individual.
     $node->field_individual[$node->language][]['target_id'] = $individual->nid;

     // If Georgia, set CIE status
     if($individual_data_content_type == 'individual_data_ga') $node->field_cie_individual_yes_no[$node->language][]['value'] = $individual->field_cie_individual[LANGUAGE_NONE][0]['value'] ? $individual->field_cie_individual[LANGUAGE_NONE][0]['value'] : 0;

     // Save node.
     $node = node_submit($node);
     node_save($node);

     // Set provider.
     $values = array(
       'entity_type' => 'node',
       'entity' => $node,
       'field_name' => 'og_group_ref'
     );
     $result = og_group('node', $provider->nid, $values);

     // Set the proper node access.
     node_access_acquire_grants($node);

     return true;
   }

   /*
    * Custom function for submitting individual data.
    */
   protected function _sdata_rp_submit_data($provider_nid) {
     $provider = node_load($provider_nid);
     if ($provider->type != 'provider') {
       return;
     }
     $provider_name = $provider->title;

     // Get the state node id. This assumes each provider has only one state.
     $state_nid = $provider->og_group_provider_state_ref[LANGUAGE_NONE][0]['target_id'];
     // Load the state node to get its notification email addresses.
     $state = node_load($state_nid);
     $state_individual_data_email_addresses = $state->field_notification_emails['und'][0]['value'];

     $reporting_period_nid = $state->field_current_reporting_period[LANGUAGE_NONE][0]['target_id'];
     $reporting_period_node = node_load($reporting_period_nid);
     //distinguish Georgia CIE nodes from regular nodes
     $cieperiod = $reporting_period_node->field_georgia_cie[LANGUAGE_NONE][0]['value'];
     $state_individual_data_content_type = $state->field_individual_data_type[LANGUAGE_NONE][0]['value'];
     $toggle_period_nid = $reporting_period_nid;
     $legacy_period_nid = $state->field_legacy_reporting_period[LANGUAGE_NONE][0]['target_id'];
     $legacyarray = array();
     module_load_include('inc', 'sdata_rp', 'views/sdata_rp_handler_submit_completed_field');
     if($legacy_period_nid) $legacyarray = _sdata_rp_switch_period($state_individual_data_content_type, $provider_nid, $legacy_period_nid);
     if($legacyarray[0] && !$legacyarray[1] && !$legacyarray[2])  $toggle_period_nid = $legacy_period_nid;



     // Unpublish individal data nodes so that they can't be edited by providers.



     // Entity field query that gets a list of individual data nodes for the given provider
     // for the current reporting period for the provider's state.
     $query = new EntityFieldQuery();
     if($state_individual_data_content_type == 'individual_data_ga') {
       $query->entityCondition('entity_type', 'node')
       ->fieldCondition('og_group_ref', 'target_id', $provider_nid)
       ->fieldCondition('field_cie_individual_yes_no', 'value', '1', '!=')
       ->propertyCondition('status', 1)
       ->propertyCondition('type', $state_individual_data_content_type)
       ->fieldCondition('field_reporting_period', 'target_id', $toggle_period_nid);
     } else {
       $query->entityCondition('entity_type', 'node')
         ->fieldCondition('og_group_ref', 'target_id', $provider_nid)
         ->propertyCondition('status', 1)
         ->propertyCondition('type', $state_individual_data_content_type)
         ->fieldCondition('field_reporting_period', 'target_id', $toggle_period_nid);
     }
     $result = $query->execute();

     // If any individual data nodes are returned, loop around each one and validate.
     if (isset($result['node'])) {
       foreach ($result['node'] as $key => $value) {
         $node = node_load($key);
         // Unpublish nodes so that providers can't access them any longer.
         $node->status = 0;
         node_save($node);
       }
     }

     // Send notification emails.
     //_sdata_rp_send_notification_emails($state_individual_data_email_addresses, $provider_name);


     return drupal_goto("/");
   }
   protected function _sdata_rp_submit_data_cie($provider_nid) {
     $provider = node_load($provider_nid);
     if ($provider->type != 'provider') {
       return;
     }
     $provider_name = $provider->title;

     // Get the state node id. This assumes each provider has only one state.
     $state_nid = $provider->og_group_provider_state_ref[LANGUAGE_NONE][0]['target_id'];
     // Load the state node to get its notification email addresses.
     $state = node_load($state_nid);
     $state_individual_data_email_addresses = $state->field_notification_emails['und'][0]['value'];

     $reporting_period_nid = $state->field_current_reporting_period[LANGUAGE_NONE][0]['target_id'];
     $reporting_period_node = node_load($reporting_period_nid);
     //distinguish Georgia CIE nodes from regular nodes
     $cieperiod = $reporting_period_node->field_georgia_cie[LANGUAGE_NONE][0]['value'];
     $state_individual_data_content_type = $state->field_individual_data_type[LANGUAGE_NONE][0]['value'];
     if($state_individual_data_content_type != 'individual_data_ga') return;

     // Unpublish individal data nodes so that they can't be edited by providers.



     // Entity field query that gets a list of individual data nodes for the given provider
     // for the current reporting period for the provider's state.
     $query = new EntityFieldQuery();

       $query->entityCondition('entity_type', 'node')
       ->fieldCondition('og_group_ref', 'target_id', $provider_nid)
       ->fieldCondition('field_cie_individual_yes_no', 'value', '1', '=')
       ->propertyCondition('status', 1)
       ->propertyCondition('type', $state_individual_data_content_type)
       ->fieldCondition('field_reporting_period', 'target_id', $reporting_period_nid);

     $result = $query->execute();

     // If any individual data nodes are returned, loop around each one and validate.
     if (isset($result['node'])) {
       foreach ($result['node'] as $key => $value) {
         $node = node_load($key);
         // Unpublish nodes so that providers can't access them any longer.
         $node->status = 0;
         node_save($node);
       }
     }

     // Send notification emails.
     _sdata_rp_send_notification_emails($state_individual_data_email_addresses, $provider_name);


     return drupal_goto("/");
   }

   /*
    * Custom function to send notification emails when data is submitted.
    */
   protected function _sdata_rp_send_notification_emails($email_addresses, $provider) {
     $params['provider'] = $provider;
     foreach(explode(',', $email_addresses) as $address) {
       drupal_mail('sdata_rp', 'notification', $address, language_default(), $params);
     }
   }

   /**
    * Implements hook_mail().
    */
   protected function sdata_rp_mail($key, &$message, $params) {
     switch($key) {
       case 'notification':
         $message['subject'] = t("sdata - A provider (@provider_name) has submitted data.)", array('provider_name' => $params['provider']));
         $message['body'][] = t("The provider '@provider_name' has submitted quarterly data to the sdata site.", array('provider_name' => $params['provider']));
         break;
     }
   }

}
