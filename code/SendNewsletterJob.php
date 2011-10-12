<?php
/**
 * A job which starts a newsletter email process to send a newsletter.
 *
 * @package silverstripe-newsletter-scheduled
 */
class SendNewsletterJob extends AbstractQueuedJob {

	public function __construct(Newsletter $newsletter = null) {
		if ($newsletter) {
			$this->NewsletterID = $newsletter->ID;
			$this->currentStep  = 0;
			$this->totalSteps   = 1;
		}
	}

	public function getNewsletter() {
		return DataObject::get_by_id('Newsletter', $this->NewsletterID);
	}

	public function getTitle() {
		if ($newsletter = $this->getNewsletter()) {
			return "Send Newsletter \"{$newsletter->Subject}\"";
		} else {
			return 'Send Newsletter';
		}
	}

	public function getJobType() {
		return QueuedJob::IMMEDIATE;
	}

	public function process() {
		$newsletter = $this->getNewsletter();
		$type       = $newsletter->getNewsletterType();
		$from       = $type && $type->FromEmail ? $type->FromEmail : Email::getAdminEmail();

		if ($newsletter->Status == 'Draft') {
			$process = new NewsletterEmailProcess(
				$newsletter->Subject,
				$from,
				$newsletter,
				$type,
				base64_encode($newsletter->ID . '_' . date('d-m-Y H:i:s')),
				$type->Group()->Members()
			);
			$process->start();
		}

		$this->currentStep = 1;
		$this->isComplete = true;
	}

}
