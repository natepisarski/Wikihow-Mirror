<?php

class QADBResult {
	var $success;
	var $aqid;
	var $msg;
	var $aq;

	/**
	 * @return mixed
	 */
	public function getAqid() {
		return $this->aqid;
	}

	/**
	 * @return mixed
	 */
	public function getSqid() {
		return $this->sqid;
	}


	/**
	 * @return bool
	 */
	public function getSuccess() {
		return $this->success;
	}

	/**
	 * @return string
	 */
	public function getMsg() {
		return $this->msg;
	}

	public function __construct($success, $msg = '', $aqid = null){
		$this->success = $success;
		$this->msg = $msg;
		$this->aqid = $aqid;
	}

	/**
	 * @return ArticleQuestion
	 */
	public function getAq() {
		return $this->aq;
	}

	/**
	 * @param ArticleQuestion $aq
	 */
	public function setAq($aq) {
		$this->aq = $aq;
	}
}