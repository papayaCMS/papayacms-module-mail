<?php
/**
 * Page module - Comment to page site
 *
 * @copyright 2002-2007 by papaya Software GmbH - All rights reserved.
 * @link http://www.papaya-cms.com/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 *
 * You can redistribute and/or modify this script under the terms of the GNU General Public
 * License (GPL) version 2, provided that the copyright and license notes, including these
 * lines, remain unmodified. papaya is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package Papaya-Modules
 * @subpackage Free-Mail
 * @version $Id: content_feedback_form.php 39732 2014-04-08 15:34:45Z weinert $
 */

/**
 * Page to send comments
 *
 * @package Papaya-Modules
 * @subpackage Free-Mail
 */
class content_feedback_form extends base_content {

  /**
   * cacheable ?
   * @var boolean $cacheable
   */
  var $cacheable = FALSE;

  /**
   * Database object base_db
   * @var base_db $dbObject
   */
  var $dbObject = NULL;

  /**
   * Parameter name
   * @var string $paramName
   */
  var $paramName = 'cff';

  /**
   * feedback form data
   * @var array $feedbackFormData
   */
  var $feedbackFormData = NULL;

  /**
   * confirmation already sent to user
   * @var boolean $confirmationSent
   */
  var $confirmationSent = FALSE;

  /**
   * @var base_dynamic_form $dialogData
   */
  var $dialogData = NULL;

  /**
   * @var email $emailObj
   */
  var $emailObj = NULL;

  /**
   * Tabbed content edit fields. $editGroups is used in favor of $editFields.
   *
   * @var array $editGroups
   */
  var $editGroups = [
    [
      'General',
      'categories-content',
      [
        'title' => ['Page Title', 'isSomeText', FALSE, 'input', 200, '', ''],
        'nl2br' => [
          'Automatic linebreak', 'isNum', FALSE, 'translatedcombo',
          [0 => 'Yes', 1 => 'No'],
          'Apply linebreaks from input to the HTML output.', 1
        ],
        'teaser' => ['Teaser', 'isSomeText', FALSE, 'simplerichtext', 5, '', ''],
        'msg_hello' => ['Text', 'isSomeText', FALSE, 'richtext', 30, '', ''],
        'Page after submission',
        'result_type' => [
          'Output type', 'isNum', TRUE, 'combo',
          [0 => 'HTML', 1 => 'PDF', 2 => 'PDF + HTML'],
          '"HTML" to display the result on this page
         , "PDF" to display the result in a PDF and leaving the Website blank
         , and "PDF + HTML" for both options.', 0
        ],
        'pdf_popup' => [
          'Display mode', 'isAlpha', TRUE, 'combo',
          ['popup' => 'Popup', '_blank' => 'New page', '_self' => 'In current page'],
          '', '_self'
        ],
        'save_popup_text' => [
          'Popup link title', 'isNoHTML', TRUE, 'textarea', 3,
          'Link that opens popup with pdf.', 'PDF generated. Click here to open.'
        ],
        'popup_title' => ['Popup title', 'isNoHTML', FALSE, 'input', 200, '', '']
      ]
    ],
    [
      'Email',
      'items-mail',
      [
        'Form',
        'form_feedback' => ['Form', 'isNum', FALSE, 'function', 'getFormsCombo'],
        'msg_store' => [
          'Feedback mode', 'isNum', FALSE, 'combo',
          [
            0 => 'Send feedback per mail',
            1 => 'Store feedback in database',
            2 => 'Send feedback per mail and store it in database'
          ],
          'Store feedback or send per mail',
          0
        ],
        'msg_attach' => [
          'Send fields as attachment', 'isNum', FALSE,
          'yesno', '', '', 0
        ],
        'msg_sender' => [
          'Sender mail field', 'isSomeText', FALSE,
          'function', 'getFieldsCombo', 'Generated form field for sender\'s email address.'
        ],
        'msg_sender_name' => [
          'Sender name field', 'isSomeText', FALSE,
          'function', 'getFieldsCombo', 'Generated form field for sender\'s name.'
        ],
        'send_button_title' => [
          'Send button title', 'isNoHTML', FALSE,
          'input', 70, '', 'Send'
        ],
        'Email',
        'msg_mailto' => [
          'Recipient', 'isEmail', FALSE, 'input', 200,
          'Feedback recipient and sender of feedback confirmations.', ''
        ],
        'return_path' => ['Return Path', 'isEmail', TRUE, 'input', 200, '', ''],
        'msg_subject' => [
          'Feedback subject', 'isSomeText', FALSE, 'input', 200,
          'Use {%Fieldname%} to fill in the form values supplied by the sender.',
          '[Comment] {%Fieldname%}'
        ],
        'msg_body' => [
          'Feedback message', 'isSomeText', FALSE, 'textarea', 6,
          'Use {%Fieldname%} to fill in the form values supplied by the sender.',
          "Name: {%Fieldname%} Email: {%Fieldname%} Comment: {%Fieldname%}"
        ],
        'Privacy Confirmation',
        'require_privacy_confirmation' => ['Require', 'isNum', FALSE, 'yesno', NULL, '', 0],
        'msg_privacy' => ['Privacy declaration', 'isSomeText', FALSE, 'simplerichtext', 5, '', ''],
        'Honeypot',
        'use_honeypot' => ['Use', 'isNum', FALSE, 'yesno', NULL, '', 0],
        'honeypot_label' => ['Label', 'isSomeText', TRUE, 'input', '', 100, 'To'],
        'honeypot_name' => ['Name', 'isAlphaNum', TRUE, 'input', '', 20, 'to'],
        'Confirmation email',
        'confirm_subject' => [
          'subject', 'isNoHTML', FALSE, 'input',
          200, 'Email subject sent to surfer. Leave empty to send no email to surfer.'
        ],
        'confirm_body' => [
          'Message', 'isNoHTML', FALSE,
          'textarea', 5, 'Email body sent to surfer.',
          'Thank you for your comment. We will get back to you as soon as possible.'
        ],
      ]
    ],
    [
      'Messages',
      'items-dialog',
      [
        'Messages',
        'msg_error' => [
          'Input error', 'isSomeText', FALSE, 'textarea', 3, '',
          'Please check your input.'
        ],
        'msg_error_privacy' => [
          'Unconfirmed privacy declaration', 'isSomeText', FALSE, 'textarea', 3, '',
          'You need to confirm the privacy declaration.'
        ],
        'msg_send' => [
          'Confirmation', 'isSomeText', FALSE, 'simplerichtext', 3, '',
          'Message sent. Thank You.'
        ],
        'msg_notsend' => [
          'Send error', 'isSomeText', FALSE, 'textarea', 3, '',
          'Send error. Please try again.'
        ]
      ]
    ]
  ];


  /**
   * Get parsed teaser
   *
   * @access public
   * @return string $result
   */
  function getParsedTeaser($parseParams = NULL) {
    $this->setDefaultData();
    $result = sprintf(
      '<title>%s</title>'.LF,
      papaya_strings::escapeHTMLChars($this->data['title'])
    );
    $result .= sprintf(
      '<text>%s</text>',
      $this->getXHTMLString($this->data['teaser'], !((bool)$this->data['nl2br']))
    );
    return $result;
  }

  /**
   * Get parsed data
   *
   * @access public
   * @return string
   */
  function getParsedData($parseParams = NULL) {
    $this->setDefaultData();

    $this->initializeParams();

    $this->getFeedbackFormData($this->data['form_feedback']);
    $result = '';
    if (isset($this->params['pdf_popup']) && $this->params['pdf_popup']) {
      $result = $this->getJSCaptions();
    }

    if (isset($this->data['title'])) {
      $result .= sprintf(
        '<title>%s</title>',
        papaya_strings::escapeHTMLChars($this->data['title'])
      );
      $result .= sprintf(
        '<text>%s</text>',
        $this->getXHTMLString($this->data['msg_hello'], !((bool)$this->data['nl2br']))
      );
    }
    $result .= '<mail>'.LF;
    if ($this->isFormSubmitted()) {
      $privacyConfirmed = TRUE;
      $dialogChecked = TRUE;
      if (!$this->isPrivacyConfirmed()) {
        $privacyConfirmed = FALSE;
        $result .= sprintf(
          '<message type="error" identifier="error-privacy">%s</message>'.LF,
          $this->getXHTMLString($this->data['msg_error_privacy'])
        );
      }
      if ($this->isHoneypotActivated()) {
        $result .= sprintf(
          '<message type="normal">%s</message>'.LF,
          $this->getXHTMLString($this->data['msg_send'])
        );
      } else {
        if (!$this->dialogData->checkDialogInputs()) {
          $dialogChecked = FALSE;
          $result .= sprintf(
            '<message type="error" identifier="error-field">%s<ul><li>%s</li></ul></message>'.LF,
            $this->getXHTMLString($this->data['msg_error']),
            implode('</li><li>', $this->dialogData->inputErrors)
          );
        }
        if ($privacyConfirmed && $dialogChecked) {
          // Save data in Session to use later in PDF-Popup
          $this->setSessionValue('feedback_params', $this->params);
          switch ($this->data['msg_store']) {
          case 0: // Send email
            $result .= $this->sendEmail();
            break;
          case 1: // Store feedback in database
            $result .= $this->storeFeedback();
            break;
          case 2: // Store feedback in database and send it as email
            $result .= $this->sendEmail();
            $this->storeFeedback();
          }
          if (isset($this->data['result_type']) && isset($this->data['pdf_popup'])) {
            if ($this->data['result_type'] >= 1) { // + PDF from 1
              $linkPopup = $this->getAbsoluteURL($this->getWebLink(NULL, NULL, 'pdf'));
              $result .= sprintf(
                '<pdf-popup>'.LF.
                '<target>%s</target>'.LF.
                '<text>%s</text>'.LF.
                '<link>%s</link>'.LF.
                '</pdf-popup>'.LF,
                papaya_strings::escapeHTMLChars($this->data['pdf_popup']),
                $this->getXHTMLString($this->data['save_popup_text'], TRUE),
                papaya_strings::escapeHTMLChars($linkPopup)
              );
            }
            if ($this->data['result_type'] == 2) { // PDF + HTML
              $result .= $this->dialogData->getDialogXML();
            }
          }
        } else {
          $result .= $this->dialogData->getDialogXML();
        }
      }
    } elseif ($params = $this->getSessionValue('feedback_params')) {
      $this->params = $params;
      $result .= $this->getFeedbackEntryXML();
      $result .= $this->dialogData->getDialogXML();
    } else {
      // Nothing received, return form
      $result .= $this->dialogData->getDialogXML();
    }
    if ($this->data['use_honeypot']) {
      $result .= sprintf(
        '<honeypot label="%s" name="%s[%s]"/>',
        papaya_strings::escapeHTMLChars($this->data['honeypot_label']),
        papaya_strings::escapeHTMLChars($this->paramName),
        papaya_strings::escapeHTMLChars($this->data['honeypot_name'])
      );
    }
    $result .= sprintf(
      '<privacy require-confirmation="%s" name="%s[confirm_privacy]">%s</privacy>',
      $this->data['require_privacy_confirmation'] ? 'yes' : 'no',
      papaya_strings::escapeHTMLChars($this->paramName),
      $this->getXHTMLString($this->data['msg_privacy'])
    );
    $result .= '</mail>'.LF;
    return $result;
  }

  /**
   * Returns String with captions uses in JS-Dialogs.
   * They're already escaped for JS-Variables
   * @param string $namespace
   * @return string XML data
   */
  function getJSCaptions($namespace = '') {
    if (!(isset($this->data['popup_title']) && isset($this->data['save_popup_text']))) {
      return '';
    }
    $result = sprintf(
      '<jscaptions namespace="%s">',
      (empty($namespace))
        ? papaya_strings::escapeHTMLChars($this->paramName)
        : papaya_strings::escapeHTMLChars($namespace)
    );
    $result .= sprintf(
      '<caption><name>popupTitle</name><value>$val</value></caption>'.LF,
      papaya_strings::escapeHTMLChars($this->data['popup_title'])
    );
    $result .= sprintf(
      '<caption><name>popupText</name><value>$val</value></caption>'.LF,
      papaya_strings::escapeHTMLChars($this->data['save_popup_text'])
    );
    return $result;
  }

  /**
   * Get database connection object
   * @return base_db
   */
  function &getDatabaseObject() {
    if (!(isset($this->dbObject) && is_object($this->dbObject))) {
      $this->dbObject = new base_db();
    }
    return $this->dbObject;
  }

  /**
   * get combobox for the form choice
   *
   * @access public
   * @param string $name is the name of the field
   * @param array $field is the definition array
   * @param mixed $data is the selected value
   * @return string $result XML
   */
  function getFormsCombo($name, $field, $data) {
    $result = '';
    $dbObject = $this->getDatabaseObject();
    $sql = 'SELECT feedback_form_id, feedback_form_title
              FROM %s
             ORDER BY feedback_form_title ASC';
    $params = PAPAYA_DB_TABLEPREFIX.'_feedback_forms';
    $forms = [];
    if ($res = $dbObject->databaseQueryFmt($sql, $params)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $forms[] = $row;
      }
    }
    if (isset($forms) && is_array($forms) && count($forms) > 0) {
      $result .= sprintf(
        '<select name="%s[%s]" class="dialogSelect dialogScale" fid="%s[%s]">',
        papaya_strings::escapeHTMLChars($this->paramName),
        papaya_strings::escapeHTMLChars($name),
        papaya_strings::escapeHTMLChars($this->paramName),
        papaya_strings::escapeHTMLChars($name)
      );
      foreach ($forms as $form) {
        if ($data == $form['feedback_form_id']) {
          $selected = 'selected="selected"';
        } else {
          $selected = '';
        }
        $result .= sprintf(
          '<option value="%s" %s>%s</option>',
          papaya_strings::escapeHTMLChars($form['feedback_form_id']),
          $selected,
          papaya_strings::escapeHTMLChars(trim($form['feedback_form_title']))
        );
      }
      $result .= '</select>';
    } else {
      $this->addMsg(MSG_ERROR, $this->_gt('No forms defined.'));
    }
    return $result;
  }

  /**
   * get combobox for the field choice
   *
   * @access public
   * @param string $name is the name of the field
   * @param array $field is the definition array
   * @param mixed $data is the selected value
   * @return string $result XML
   */
  function getFieldsCombo($name, $field, $data) {
    $result = '';
    if (isset($this->data['form_feedback'])) {
      $dbObject = $this->getDatabaseObject();
      $structure = '';
      if ($name == 'msg_sender_name') {
        $selected = '';
        if (!isset($data) || $data == 0) {
          $selected = ' selected="selected"';
        }
        $result .= sprintf('<option value="0"%s></option>', $selected);
      }
      $sql = 'SELECT feedback_form_structure
                FROM %s
               WHERE feedback_form_id = %d';
      $params = [
        PAPAYA_DB_TABLEPREFIX.'_feedback_forms',
        $this->data['form_feedback']
      ];
      if ($res = $dbObject->databaseQueryFmt($sql, $params)) {
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $structure = $row;
        }
      }
      $result .= sprintf(
        '<select name="%s[%s]" class="dialogSelect dialogScale" fid="%s[%s]">',
        papaya_strings::escapeHTMLChars($this->paramName),
        papaya_strings::escapeHTMLChars($name),
        papaya_strings::escapeHTMLChars($this->paramName),
        papaya_strings::escapeHTMLChars($name)
      );
      $result .= sprintf(
        '<option value="">%s</option>',
        papaya_strings::escapeHTMLChars(new PapayaUiStringTranslated('None'))
      );
      if (!empty($structure['feedback_form_structure'])) {
        $dom = new PapayaXmlDocument();
        $dom->loadXml($structure['feedback_form_structure']);
        foreach ($dom->xpath()->evaluate('//field/@name') as $option) {
          $option = trim($option->value);
          if ($option != '') {
            if ($data == $option) {
              $selected = ' selected="selected"';
            } else {
              $selected = '';
            }
            $result .= sprintf(
              '<option value="%s"%s>%s</option>',
              papaya_strings::escapeHTMLChars($option),
              $selected,
              papaya_strings::escapeHTMLChars($option)
            );
          }
        }
      }
      $result .= '</select>';
    } else {
      $result .= sprintf(
        '<input type="text" name="%s[%s]" value="%s" class="dialogInput dialogScale" fid="%s" />',
        papaya_strings::escapeHTMLChars($this->paramName),
        papaya_strings::escapeHTMLChars($name),
        papaya_strings::escapeHTMLChars($data),
        papaya_strings::escapeHTMLChars($name)
      );
    }
    return $result;
  }

  /**
   * get feedback form data
   *
   * @param int $feedbackId
   */
  function getFeedbackFormData($feedbackId) {
    $dbObject = $this->getDatabaseObject();
    $sql = "SELECT feedback_form_structure
              FROM %s
             WHERE feedback_form_id = %d";
    $params = [PAPAYA_DB_TABLEPREFIX.'_feedback_forms', $feedbackId];
    if ($res = $dbObject->databaseQueryFmt($sql, $params)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $this->feedbackFormData = $row;
      }
    }
    $this->dialogData = new base_dynamic_form(
      $this->feedbackFormData['feedback_form_structure'],
      NULL,
      [$this->paramName => ['send' => 1]]
    );
    // Fallback, if send_button_title is not set yet.
    if (empty($this->data['send_button_title'])) {
      $this->dialogData->buttonTitle = 'Send';
    } else {
      $this->dialogData->buttonTitle = $this->data['send_button_title'];
    }
    $this->dialogData->baseLink = $this->baseLink;
    $this->dialogData->paramName = $this->paramName;
    $this->dialogData->loadParams();
  }

  /**
   * Send email
   *
   * @return string $result XML
   */
  private function sendEmail() {
    $nl = "\r\n";
    $result = '';
    $content = [];
    $attachStr = '';
    if ($inputData = $this->dialogData->getDialogInputs()) {
      foreach ($inputData as $field => $value) {
        if ($field !== 'send') {
          if (is_array($value)) {
            $comboboxValues = '';
            foreach ($value as $entry) {
              $comboboxValues .= $entry.$nl;
            }
            $content[$this->dialogData->fields[$field]['name']] = $comboboxValues;
            $attachStr .= $this->dialogData->fields[$field]['name'].$nl.$comboboxValues.$nl.$nl;
          } else {
            $content[$this->dialogData->fields[$field]['name']] = $value;
            $attachStr .= $this->dialogData->fields[$field]['name'].$nl.$value.$nl.$nl;
          }
        }
      }
    }
    $this->emailObj = new email();
    if (!empty($this->data['return_path'])) {
      $this->emailObj->setReturnPath($this->data['return_path'], TRUE);
    }
    list($senderEmail, $senderName) = $this->getFeedbackSender();
    if (!empty($senderEmail)) {
      $this->emailObj->setSender($senderEmail, $senderName);
    }
    $this->emailObj->addAddress($this->data['msg_mailto']);
    $this->emailObj->setSubject($this->data['msg_subject'], $content);
    $this->emailObj->setBody($this->data['msg_body'], $content);
    if (isset($this->data['msg_attach']) && $this->data['msg_attach']) {
      $this->emailObj->addAttachmentData('feedback.txt', $attachStr);
    }
    if ($this->emailObj->send()) {
      $result .= sprintf(
        '<message type="normal">%s</message>'.LF,
        $this->getXHTMLString($this->data['msg_send'])
      );
      $this->sendConfirmation($content);
    } else {
      $result .= sprintf(
        '<message type="warning">%s</message>'.LF,
        $this->getXHTMLString($this->data['msg_notsend'])
      );
    }
    return $result;
  }

  /**
   * Store feedback in database
   *
   * @access public
   * @return string $result XML
   */
  function storeFeedback() {
    $dbObject = $this->getDatabaseObject();
    $result = '';
    $xmlMessage = $this->getFeedbackEntryXML();
    list($sender, $senderName) = $this->getFeedbackSender();
    $content = [];
    if ($inputData = $this->dialogData->getDialogInputs()) {
      foreach ($inputData as $field => $value) {
        if ($field !== 'send') {
          if (is_array($value)) {
            $theValue = '';
            foreach ($value as $entry) {
              $theValue .= $entry.LF;
            }
            $content[$this->dialogData->fields[$field]['name']] = $theValue;
          } else {
            $content[$this->dialogData->fields[$field]['name']] = $value;
          }
        }
      }
    }
    $template = new base_simpletemplate();
    $subject = $template->parse($this->data['msg_subject'], $content);
    $body = $template->parse($this->data['msg_body'], $content);
    $data = [
      'feedback_time' => time(),
      'feedback_email' => papaya_strings::escapeHTMLChars(trim($sender)),
      'feedback_name' => papaya_strings::escapeHTMLChars(trim($senderName)),
      'feedback_form' => $this->data['form_feedback'],
      'feedback_subject' => papaya_strings::escapeHTMLChars(trim($subject)),
      'feedback_message' => papaya_strings::escapeHTMLChars($body),
      'feedback_xmlmessage' => $xmlMessage
    ];
    if (FALSE !== $dbObject->databaseInsertRecord(PAPAYA_DB_TABLEPREFIX.'_feedback', NULL, $data)) {
      $result .= sprintf(
        '<message type="normal">%s</message>'.LF,
        $this->getXHTMLString($this->data['msg_send'])
      );
      $this->sendConfirmation($data);
    } else {
      $result .= sprintf(
        '<message type="warning">%s</message>'.LF,
        $this->getXHTMLString($this->data['msg_notsend'])
      );
    }
    return $result;
  }

  /**
   * Generates a string object that contains the XML with the current user's
   * submitted form feedback data.
   *
   * @return string XML for PDF transformation and database storage.
   */
  function getFeedbackEntryXML() {
    $xmlMessage = sprintf(
      '<entry timestamp="%s">'.LF,
      date('Y-m-j H:i:s', time())
    );
    foreach ($this->params as $field => $value) {
      if ($field != 'send') {
        if (is_array($value)) {
          $xmlMessage .= sprintf(
            '<fieldset name="%s">'.LF,
            papaya_strings::escapeHTMLChars($field)
          );
          foreach ($value as $entry) {
            $xmlMessage .= sprintf(
              '<field>%s</field>'.LF,
              papaya_strings::escapeHTMLChars($entry)
            );
          }
          $xmlMessage .= sprintf('</fieldset>');
        } else {
          $xmlMessage .= sprintf(
            '<field name="%s">%s</field>'.LF,
            papaya_strings::escapeHTMLChars($field),
            papaya_strings::escapeHTMLChars($value)
          );
        }
      }
    }
    $xmlMessage .= '</entry>';
    return $xmlMessage;
  }

  /**
   * Send an automatic confirmation mail to the user --
   * but only, if it has not been sent yet
   *
   * @access public
   */
  function sendConfirmation($values) {
    if (!$this->confirmationSent) {
      list($senderEmail, $senderName) = $this->getFeedbackSender();
      if (!(empty($senderEmail) || empty($this->data['confirm_subject']))) {
        $emailConfirmObj = new email();
        $emailConfirmObj->setSender($this->data['msg_mailto']);
        $emailConfirmObj->addAddress($senderEmail, $senderName);
        $emailConfirmObj->setSubject($this->data['confirm_subject'], $values);
        $emailConfirmObj->setBody($this->data['confirm_body'], $values);
        $emailConfirmObj->send();
      }
      $this->confirmationSent = TRUE;
    }
  }

  private function getFeedbackSender() {
    $senderEmailField = $this->data['msg_sender'];
    $senderNameField = $this->data['msg_sender_name'];
    $result = ['', ''];
    if (!empty($senderEmailField)) {
      $result[0] = PapayaFilterFactory::isEmail($this->params[$senderEmailField])
        ? $this->params[$senderEmailField] : '';
    }
    if (!empty($senderNameField)) {
      $result[0] = PapayaFilterFactory::isNotEmpty($this->params[$senderNameField])
        ? $this->params[$senderNameField] : '';
    }
  }

  public function isFormSubmitted() {
    return isset($this->params['send']) && $this->params['send'];
  }

  public function isPrivacyConfirmed() {
    return (
      (!$this->data['require_privacy_confirmation']) ||
      (isset($this->params['confirm_privacy']) && $this->params['confirm_privacy'])
    );
  }

  public function isHoneypotActivated() {
    $name = $this->data['honeypot_name'];
    if ($this->data['use_honeypot'] && $name) {
      return (
        !isset($this->params[$name]) ||
        $this->params[$name] !== ''
      );
    }
    return FALSE;
  }
}
