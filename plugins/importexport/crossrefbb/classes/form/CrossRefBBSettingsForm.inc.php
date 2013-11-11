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
		// The username is used in HTTP basic authentication and according to RFC2617 it therefore may not contain a colon.
		$this->addCheck(new FormValidatorRegExp($this, 'username', FORM_VALIDATOR_OPTIONAL_VALUE, 'plugins.importexport.datacite.settings.form.usernameRequired', '/^[^:]+$/'));
	}


	//
	// Implement template methods from DOIExportSettingsForm
	//
	/**
	 * @see DOIExportSettingsForm::getFormFields()
	 */
	function getFormFields() {
		return array(
			'username' => 'string',
			'password' => 'string'
		);
	}
}

?>
