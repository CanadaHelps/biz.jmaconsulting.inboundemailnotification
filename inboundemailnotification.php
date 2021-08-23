<?php

require_once 'inboundemailnotification.civix.php';
// phpcs:disable
use CRM_Inboundemailnotification_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function inboundemailnotification_civicrm_config(&$config) {
  _inboundemailnotification_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function inboundemailnotification_civicrm_xmlMenu(&$files) {
  _inboundemailnotification_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function inboundemailnotification_civicrm_install() {
  _inboundemailnotification_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function inboundemailnotification_civicrm_postInstall() {
  _inboundemailnotification_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function inboundemailnotification_civicrm_uninstall() {
  _inboundemailnotification_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function inboundemailnotification_civicrm_enable() {
  _inboundemailnotification_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function inboundemailnotification_civicrm_disable() {
  _inboundemailnotification_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function inboundemailnotification_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _inboundemailnotification_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function inboundemailnotification_civicrm_managed(&$entities) {
  _inboundemailnotification_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function inboundemailnotification_civicrm_caseTypes(&$caseTypes) {
  _inboundemailnotification_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function inboundemailnotification_civicrm_angularModules(&$angularModules) {
  _inboundemailnotification_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function inboundemailnotification_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _inboundemailnotification_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function inboundemailnotification_civicrm_entityTypes(&$entityTypes) {
  _inboundemailnotification_civix_civicrm_entityTypes($entityTypes);
}

function inboundemailnotification_civicrm_postJob($job, $params, $result) {
  if ($job->name == 'Process Inbound Emails' && empty($result['is_error'])) {
    $emailActivityTypeId
      = (defined('EMAIL_ACTIVITY_TYPE_ID') && EMAIL_ACTIVITY_TYPE_ID)
      ? EMAIL_ACTIVITY_TYPE_ID
      : CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Inbound Email');
      
    $activityStatus = civicrm_api3('MailSettings', 'getvalue', [
      'return' => "activity_status",
      'is_default' => 0,
      'options' => ['limit' => 1],
    ]) ?: 'Scheduled';
    
    $params = [
      'activity_type_id' => $emailActivityTypeId,
      'status_id' => $activityStatus,
    ];
    
    $lastDismissalTime = Civi::settings()->get('last_inbound_email_notification_dismissal');
    if (!empty($lastDismissalTime)) {
      $params['activity_date_time'] = ['>=' => $lastDismissalTime];
    }

    $newRepliesCount = civicrm_api3('Activity', 'getcount', $params);
    
    if ($newRepliesCount > 0) {
      Civi::settings()->set('inbound_email_notification_count', $newRepliesCount);
      // reset last notification time to trigger notification immediatly
      Civi::settings()->set('inbound_email_notification_time', '');
    }
  }
}

function inboundemailnotification_civicrm_pageRun(&$page) {
  if (CRM_Core_Permission::check('CH admin inbound email notification')) {
    $newRepliesCount = Civi::settings()->get('inbound_email_notification_count');
    if ($newRepliesCount > 0) {
      $lastTime = Civi::settings()->get('inbound_email_notification_time');
      // if there is no lastdismissal time set
      if (empty($lastTime) || (strtotime('now') > (strtotime($lastTime) + 3600))) {
        $emailActivityTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Inbound Email');
        $activityStatus = civicrm_api3('MailSettings', 'getvalue', [
          'return' => "activity_status",
          'is_default' => 0,
          'options' => ['limit' => 1],
        ]) ?: 'Scheduled';
        $activityStatusID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', $activityStatus);
        $lastDismissalTime = Civi::settings()->get('last_inbound_email_notification_dismissal');
        Civi::settings()->set('inbound_email_notification_time', date('YmdHis'));
        $statusMessage = ts('New Emails Have Been Received
          <br />
          <br />
          <a href="%1">View</a>&nbsp;&nbsp;&nbsp;&nbsp;
          <a href="%2">Dismiss</a>
        ', [
          1 => CRM_Utils_System::url('civicrm/activity/search', "reset=1&force=1&activity_type_id={$emailActivityTypeId}&activity_status_id={$activityStatusID}&activity_date_time_low={$lastDismissalTime}"),
          2 => CRM_Utils_System::url('civicrm/inbound-email-dismiss'),
        ]);
        $statusTitle = $newRepliesCount == 1 ? ts('1 New Reply') : ts('%1 New Replies', [1 => $newRepliesCount]);
        CRM_Core_Session::setStatus($statusMessage, $statusTitle, 'alert');
      }
    }
  }
}

function inboundemailnotification_civicrm_searchTasks($objectType, &$tasks ) {
  if ($objectType == 'activity') {
    $tasks['archive'] = [
      'title' => ts('Archive'),
      'class' => 'CRM_Activity_Form_Task_Archive',
    ];
  }
}

/**
 * Implements hook_civicrm_thems().
 */
function inboundemailnotification_civicrm_themes(&$themes) {
  _inboundemailnotification_civix_civicrm_themes($themes);
}

function inboundemailnotification_civicrm_permission(&$permissions) {
  $permissions['CH admin inbound email notification'] = [ts('CiviCRM: CH admin inbound email notification')];

  __addPermssionToClientAdminRole();
}

function __addPermssionToClientAdminRole() {
  // ensure that its a drupal site and user module is enabled
  if (CRM_Core_Config::singleton()->userFramework != 'Drupal' || !module_exists('user')) {
    return;
  }
  
  $settings = [
    'client administrator' => [
      'CH admin inbound email notification',
    ],
  ];
  
  foreach (user_roles() as $rid => $name) {
    if (in_array(strtolower($name), array_keys($settings))) {
      foreach ($settings[strtolower($name)] as $permission) {
        $result = db_query("SELECT * FROM {role_permission} where rid = $rid AND permission = '$permission'");
        $found = FALSE;
        foreach ($result as $row) {
          $found = ($row->permission == $permission);
        }
        if (!$found) {
          // delete all permission assigned to the other role
          db_delete('role_permission')
            ->condition('permission', $permission)
            ->execute();

          // assign permission to specified role
          db_merge('role_permission')->key(
            [
              'rid' => $rid,
              'permission' => $permission,
            ]
          )->fields(['module' => 'civicrm'])
          ->execute();

          // Clear the user access cache.
          drupal_static_reset('user_access');
          drupal_static_reset('user_role_permissions');
        }
      }
    }
  }
}

