<?php
/**
 * This class provides the functionality to archive activities by changing status to Completed
 */
class CRM_Activity_Form_Task_Archive extends CRM_Activity_Form_Task {

  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific Activity?
   *
   * @var bool
   */
  protected $_single = FALSE;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addDefaultButtons(ts('Archive Activities'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $archived = 0;
    foreach ($this->_activityHolderIds as $activityId['id']) {
      civicrm_api3('Activity', 'create', [
        'id' => $activityId['id'],
        'activity_status_id' => 'Completed',
      ]);
      $archived++;
    }

    if ($archived) {
      $msg = ts('%count activity archived.', ['plural' => '%count activities archived.', 'count' => $archived]);
      CRM_Core_Session::setStatus($msg, ts('Archived'), 'success');
    }

    $emailActivityTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Inbound Email');
    $activityStatus = civicrm_api3('MailSettings', 'getvalue', [
      'return' => "activity_status",
      'is_default' => 0,
      'options' => ['limit' => 1],
    ]) ?: 'Scheduled';
    $activityStatusID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', $activityStatus);
    $lastDismissalTime = Civi::settings()->get('last_inbound_email_notification_dismissal');

    // update the 'New Replies' count
    $params = [
      'activity_type_id' => $emailActivityTypeId,
      'status_id' => $activityStatus,
    ];
    if (!empty($lastDismissalTime)) {
      $params['activity_date_time'] = ['>=' => $lastDismissalTime];
    }
    $newRepliesCount = civicrm_api3('Activity', 'getcount', $params);
    Civi::settings()->set('inbound_email_notification_count', $newRepliesCount);
    // reset last notification time to trigger notification immediatly
    $lastTime = Civi::settings()->set('inbound_email_notification_time', '');

    $url = CRM_Utils_System::url('civicrm/activity/search', "reset=1&force=1&activity_type_id={$emailActivityTypeId}&activity_status_id={$activityStatusID}&activity_date_time_low={$lastDismissalTime}");
    CRM_Utils_System::redirect($url);
  }

}
