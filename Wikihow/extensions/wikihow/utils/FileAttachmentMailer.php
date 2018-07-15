<?php

class FileAttachmentMailer {
	/**
	 * Send the reports as an e-mail attachment.
	 */
	public static function sendAttachment($from, $replyTo, $recipients, $subject, $msg, $filename, $data, $mimeType = 'text/tab-separated-values') {
		$data = [
			// Generate a unique boundary hash for the multipart e-mail body
			'boundaryHash' => md5(date('r', time())),
			'attachment' => chunk_split(base64_encode($data)),
			'today' => date('Y-m-d', strtotime('today')),
			'msg' => $msg,
			'filename' => $filename,
			'mimeType' => $mimeType,
		];

		$options =  ['loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__))];
		$m = new Mustache_Engine($options);
		$body = $m->render('file_attachment_mailer', $data);

		$from = new MailAddress($from);
		$to = self::getMailAddresses($recipients);
		$replyTo = new MailAddress($replyTo);
		$contentType = 'multipart/mixed; boundary="PHP-mixed-' . $data['boundaryHash'] . '"';

		UserMailer::send(
			$to,
			$from,
			$subject,
			$body,
			$replyTo,
			$contentType
		);

		return;
	}

	/*
	 * returns and array of MailAddress objects given a comma separated string of emails
	 */
	public static function getMailAddresses($emails) {
		$emails = explode(",", $emails);
		$addresses = [];
		foreach ($emails as $email) {
			$addresses[] = new MailAddress($email);
		}

		return $addresses;
	}
}