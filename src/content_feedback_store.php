<?php
/**
* Page module - Send feedback email and store values in DB
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
* @version $Id: content_feedback_store.php 39517 2014-03-04 18:51:22Z weinert $
*/

/**
* Page module - Send feedback email and store values in DB
*
* @package Papaya-Modules
* @subpackage Free-Mail
*/
class content_feedback_store extends content_feedback {

  /**
  * Send email
  *
  * @access public
  * @return string $result XML
  */
  function sendEmail() {
    $content['NAME'] = empty($this->params['mail_name']) ? '' : $this->params['mail_name'];
    $content['FROM'] = empty($this->params['mail_from']) ? '' : $this->params['mail_from'];
    $content['SUBJECT'] = empty($this->params['mail_subject']) ? '' : $this->params['mail_subject'];
    $content['TEXT'] = empty($this->params['mail_message']) ? '' : $this->params['mail_message'];
    $result = parent::sendEmail($content);
    if ($result[0]) {
      $this->storeFeedback($content);
    }
    return $result;
  }

  /**
   * Store feedback
   *
   * @access public
   * @param string $content
   * @return boolean
   */
  function storeFeedback($content) {
    $xmlMessage = sprintf(
      '<entry timestamp="%s">'.LF,
      date('Y-m-j H:i:s', time())
    );
    $xmlMessage .= sprintf(
      '<field name="email">%s</field>'.LF,
      papaya_strings::escapeHTMLChars($content['FROM'])
    );
    $xmlMessage .= sprintf(
      '<field name="name">%s</field>'.LF,
      papaya_strings::escapeHTMLChars($content['NAME'])
    );
    $xmlMessage .= sprintf(
      '<field name="subject">%s</field>'.LF,
      papaya_strings::escapeHTMLChars($content['SUBJECT'])
    );
    $xmlMessage .= sprintf(
      '<field name="message">%s</field>'.LF,
      papaya_strings::escapeHTMLChars($content['TEXT'])
    );
    $xmlMessage .= '</entry>';

    $tableFeedback = PAPAYA_DB_TABLEPREFIX.'_feedback';
    $data = array(
      'feedback_time' => time(),
      'feedback_email' => $content['FROM'],
      'feedback_name' => $content['NAME'],
      'feedback_subject' => $content['SUBJECT'],
      'feedback_message' => $content['TEXT'],
      'feedback_xmlmessage' => $xmlMessage
    );
    $dbObj = new base_db;
    return FALSE !== $dbObj->databaseInsertRecord($tableFeedback, NULL, $data);
  }

}

