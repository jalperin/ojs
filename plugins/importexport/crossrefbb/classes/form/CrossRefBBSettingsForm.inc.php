<?php

/**
 * @file plugins/importexport/crossrefbb/classes/form/CrossRefBBSettingsForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossRefBBSettingsForm
 * @ingroup plugins_importexport_crossrefbb_classes_form
 *
 * @brief Form for journal managers to setup the CrossRefBB plug-in.
 */


if (!class_exists('DOIExportSettingsForm')) { // Bug #7848
	import('plugins.importexport.crossrefbb.classes.form.DOIExportSettingsForm');
}

class CrossRefBBSettingsForm extends DOIExportSettingsForm {

	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin CrossRefBBExportPlugin
	 * @param $journalId integer
	 */
	function CrossRefBBSettingsForm(&$plugin, $journalId) {
		// Configure the object.
		parent::DOIExportSettingsForm($plugin, $journalId);

		// Add form validation checks.
		$this->addCheck(new FormValidator($this, 'depositorName', 'required', 'plugins.importexport.crossrefbb.settings.form.depositorNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'depositorEmail', 'required', 'plugins.importexport.crossrefbb.settings.form.depositorEmailRequired'));
	}


	//
	// Implement template methods from DOIExportSettingsForm
	//
	/**
	 * @see DOIExportSettingsForm::getFormFields()
	 */
	function getFormFields() {
		return array(
			'depositorName' => 'string',
			'depositorEmail' => 'string',
			'username' => 'string',
			'password' => 'string'
			);
	}
}

?>
