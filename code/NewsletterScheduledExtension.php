<?php
/**
 * Allows the admin to specify a date and time after which the newsletter is
 * automatically sent out.
 *
 * @package silverstripe-newsletter-scheduled
 */
class NewsletterScheduledExtension extends DataObjectDecorator {

	public function extraStatics() {
		return array(
			'db' => array(
				'IsScheduled'   => 'Boolean',
				'ScheduledTime' => 'SS_Datetime'
			),
			'has_one' => array(
				'SendJob' => 'QueuedJobDescriptor'
			)
		);
	}

	public function updateCMSFields(FieldSet $fields) {
		if ($this->owner->Status != 'Draft') {
			return;
		}

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.js');
		Requirements::javascript('newsletter-scheduled/javascript/NewsletterScheduledAdmin.js');

		$fields->addFieldsToTab('Root.Scheduling', array(
			new CheckboxField('IsScheduled', 'Send the newsletter at a scheduled time?'),
			$datetime = new DatetimeField('ScheduledTime', 'Time to send newsletter after')
		));

		$datetime->getDateField()->setConfig('showcalendar', true);
		$datetime->getTimeField()->setConfig('showdropdown', true);
	}

	public function onBeforeWrite() {
		if ($this->owner->IsScheduled && $this->owner->ScheduledTime) {
			if ($this->owner->SendJobID) {
				$this->owner->SendJob()->StartAfter = $this->owner->ScheduledTime;
				$this->owner->SendJob()->write();
			} else {
				$job = new SendNewsletterJob($this->owner);
				$id  = singleton('QueuedJobService')->queueJob($job, $this->owner->ScheduledTime);

				$this->owner->SendJobID = $id;
			}
		} else {
			if ($this->owner->SendJobID) {
				$this->owner->SendJob()->delete();
				$this->owner->SendJobID = null;
			}
		}
	}

}
