<?php

/**
 * @file AuthorSubmitStep5Form.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmitStep5Form
 * @ingroup author_form_submit
 *
 * @brief Form for Step 5 of author paper submission.
 */

//$Id$

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep5Form extends AuthorSubmitForm {

	/**
	 * Constructor.
	 */
	function AuthorSubmitStep5Form($paper) {
		parent::AuthorSubmitForm($paper, 5);
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();

		// Get paper file for this paper
		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		$paperFiles =& $paperFileDao->getPaperFilesByPaper($this->paperId);

		$templateMgr->assign_by_ref('files', $paperFiles);
		$templateMgr->assign_by_ref('conference', Request::getConference());

		parent::display();
	}

	/**
	 * Save changes to paper.
	 */
	function execute() {
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');

		$conference = Request::getConference();
		$schedConf = Request::getSchedConf();

		// Update paper
		$paper =& $this->paper;
		$paper->setDateSubmitted(Core::getCurrentDate());
		$paper->setSubmissionProgress(0);
		$paper->stampStatusModified();

		// We've collected the paper now -- bump the review progress
		$paper->setCurrentStage(REVIEW_STAGE_PRESENTATION);

		$paperDao->updatePaper($paper);

		// Designate this as the review version by default.
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($paper->getPaperId());
		AuthorAction::designateReviewVersion($authorSubmission);
		unset($authorSubmission);

		// Update any review assignments so they may access the file
		$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($paper->getPaperId());
		$reviewAssignments =& $reviewAssignmentDao->getReviewAssignmentsByPaperId($paper->getPaperId(), REVIEW_STAGE_PRESENTATION);
		foreach($reviewAssignments as $reviewAssignment) {
			$reviewAssignment->setReviewFileId($authorSubmission->getReviewFileId());
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
		}

		$reviewMode = $authorSubmission->getReviewMode();
		$user =& Request::getUser();

		$trackDirectors = array();
		if ($reviewMode == REVIEW_MODE_BOTH_SIMULTANEOUS || $reviewMode == REVIEW_MODE_PRESENTATIONS_ALONE) {
			// Editors have not yet been assigned; assign them.
			$trackDirectors = $this->assignDirectors($paper);
		}

		$this->confirmSubmission($paper, $user, $schedConf, $conference, $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL?'SUBMISSION_UPLOAD_ACK':'SUBMISSION_ACK', $trackDirectors);

		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($this->paperId, PAPER_LOG_PRESENTATION_SUBMIT, LOG_TYPE_AUTHOR, $user->getUserId(), 'log.author.presentationSubmitted', array('submissionId' => $paper->getPaperId(), 'authorName' => $user->getFullName()));

		return $this->paperId;
	}

}

?>
