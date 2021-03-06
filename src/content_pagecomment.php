<?php
/**
* Page module - Comment to page site
*
* Configured by xml-file
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
* @version $Id: content_pagecomment.php 39730 2014-04-07 21:05:30Z weinert $
*/

/**
* Page to send comments
*
* Configured by xml-file
*
* @package Papaya-Modules
* @subpackage Free-Mail
*/
class content_pagecomment extends base_content {

  /**
  * cacheable ?
  * @var boolean $cacheable
  */
  var $cacheable = FALSE;

  /**
  * Parameter name
  * @var string $paramName
  */
  var $paramName = 'mail';

  /**
  * Content edit fields
  * @var array $editFields
  */
  var $editFields = array(
    'title' => array('Page Title', 'isSomeText', FALSE, 'input', 200, '', ''),
    'Email',
    'msg_mailto' => array('Recipient', 'isEmail', FALSE, 'input', 200, '', ''),
    'return_path' => array('Return Path', 'isEmail', TRUE, 'input', 200, '', ''),
    'msg_subject' => array('Subject template of email', 'isNoHTML', FALSE, 'input', 200, '', ''),
    'msg_body' => array('Body template of email', 'isNoHTML', FALSE, 'textarea', 20, '',
      "{%TITLE%}\n\n{%COMMENT%}\n\n{%FROM%}\n\n{%TEXT%}\n\n{%LINK%}"),
    'Captions',
    'cap_sender' => array('Sender', 'isNoHTML', FALSE, 'input', 200, '', ''),
    'cap_comments' => array('Comments', 'isNoHTML', FALSE, 'input', 200, '', ''),
    'caption_submit' => array(
      'Comment page', 'isNoHTML', FALSE, 'input', 200, '', 'Comment page'
    ),
    'Messages',
    'msg_hello' => array('Text', 'isSomeText', FALSE, 'textarea', 6, '', ''),
    'msg_send' => array('Confirmation', 'isSomeText', FALSE, 'textarea', 6, '',
      'Message sent. Thank You.'),
    'msg_error' => array('Input Error', 'isSomeText', FALSE, 'textarea', 6, '',
      'Please check your input.'),
    'msg_notsend' => array('Send error', 'isSomeText', FALSE, 'textarea', 6, '',
      'Sent Error. Please try again.'),
    'msg_privacy' => array('Privacy', 'isSomeText', FALSE, 'textarea', 10, '', ''),
  );

  /**
  * Get mail data
  *
  * @param integer $topicId
  * @access public
  * @return array
  */
  function getMailData($topicId) {
    $content = NULL;
    if (isset($GLOBALS['PAPAYA_PAGE'])) {
      $className = get_class($this->parentObj);
      /** @var base_topic $linkedTopic */
      $linkedTopic = new $className();
      if ($linkedTopic->topicExists($topicId) &&
          $linkedTopic->loadOutput($topicId, $this->parentObj->getContentLanguageId())) {
        /** @var papaya_page $page */
        $page = $GLOBALS['PAPAYA_PAGE'];
        if ($page->validateAccess($topicId)) {
          if ($str = $linkedTopic->parseContent(FALSE)) {
            $content['xml'] = $str;
            if ($dom = PapayaXmlDocument::createFromXml($str, TRUE)) {
              $content['title'] = $dom->xpath()->evaluate('string(/*/title)');
              $content['text'] = $dom->xpath()->evaluate('string(/*/text)');
            }
          }
        }
      }
    }
    return $content;
  }

  /**
  * Get parsed data
  *
  * @access public
  * @return string xml
  */
  function getParsedData($parseParams = NULL) {
    $this->setDefaultData();
    $this->initializeParams();

    if (isset($_GET['refpage']) && $_GET['refpage'] > 0) {
      $this->params['refpage'] = $_GET['refpage'];
      $refererPage = $this->params['refpage'];
    } elseif (isset($this->params['refpage']) && $this->params['refpage'] > 0) {
      $refererPage = $this->params['refpage'];
    } else {
      $refererPage = 0;
    }

    if (isset($_GET['urlparams']) && $_GET['urlparams'] > 0) {
      $this->params['urlparams'] = $_GET['urlparams'];
      $urlParams = $this->params['urlparams'];
    } elseif (isset($this->params['urlparams'])) {
      $urlParams = $this->params['urlparams'];
    } else {
      $urlParams = '';
    }

    $result = '';

    if (isset($this->data['title'])) {
      $result .= sprintf(
        '<title>%s</title>',
        papaya_strings::escapeHTMLChars($this->data['title'])
      );
    }
    $result .= '<mail>'.LF;

    $content = $this->getMailData($refererPage);
    if (isset($content) && is_array($content)) {
      $result .= $content['xml'];
    }

    if (isset($this->params['submit']) &&  $this->params['submit']) {
      $errors = $this->checkMailDialogInput();
      if (!$errors) {
        $content['LINK'] = $this->getAbsoluteURL($this->getWebLink($refererPage)).
          $this->recodeQueryString($urlParams);
        $content['FROM'] = $this->params['mail_from'];
        $content['COMMENT'] = $this->params['mail_comments'];
        $emailObj = new email();
        if (!empty($this->data['return_path'])) {
          $emailObj->setReturnPath($this->data['return_path'], TRUE);
        }
        if (isset($this->params['mail_from'])) {
          $emailObj->setSender($this->params['mail_from']);
        }
        $emailObj->setSubject($this->data['msg_subject'], $content);
        $emailObj->setBody($this->data['msg_body'], $content);
        if ($emailObj->send($this->data['msg_mailto'])) {
          $result .= sprintf(
            '<message type="normal">%s</message>'.LF,
            $this->getXHTMLString($this->data['msg_send'])
          );
        } else {
          $result .= sprintf(
            '<message type="warning">%s</message>'.LF,
            $this->getXHTMLString($this->data['msg_notsend'])
          );
        }
      } else {
        $result .= '<message type="error">'.LF;
        $result .= $this->getXHTMLString($this->data['msg_error']);
        if (is_array($errors)) {
          $result .= '<ul>'.LF;
          foreach ($errors as $fieldCaption) {
            $result .= sprintf(
              '<li>%s</li>'.LF,
              papaya_strings::escapeHTMLChars($fieldCaption)
            );
          }
          $result .= '</ul>'.LF;
        }
        $result .= '</message>'.LF;
        $result .= $this->getMailDialog($refererPage, $urlParams);
      }
    } else {
      $result .= sprintf(
        '<message type="normal">%s</message>',
        $this->getXHTMLString($this->data['msg_hello'])
      );
      $result .= $this->getMailDialog($refererPage, $urlParams);
    }
    $result .= sprintf(
      '<privacy>%s</privacy>',
      $this->getXHTMLString($this->data['msg_privacy'])
    );
    $result .= '</mail>'.LF;
    return $result;
  }

  /**
  * Get mail dialog
  *
  * @param string $refererPage
  * @param string $urlParams
  * @access public
  * @return string xml
  */
  function getMailDialog($refererPage, $urlParams) {
    $result = sprintf(
      '<form action="%s" method="post">'.LF,
      papaya_strings::escapeHTMLChars($this->baseLink)
    );
    $result .= sprintf(
      '<input type="hidden" name="%s[submit]" value="1" />'.LF,
      papaya_strings::escapeHTMLChars($this->paramName)
    );
    $result .= sprintf(
      '<input type="hidden" name="%s[refpage]" value="%s" />'.LF,
      papaya_strings::escapeHTMLChars($this->paramName),
      (int)$refererPage
    );
    $result .= sprintf(
      '<input type="hidden" name="%s[urlparams]" value="%s" />'.LF,
      papaya_strings::escapeHTMLChars($this->paramName),
      papaya_strings::escapeHTMLChars($urlParams)
    );
    $result .= sprintf(
      '<label for="%s[mail_from]">%s</label>'.LF,
      $this->paramName,
      papaya_strings::escapeHTMLChars($this->data['cap_sender'])
    );
    $result .= sprintf(
      '<input type="text" name="%s[mail_from]" value="%s" class="text" />'.LF,
      papaya_strings::escapeHTMLChars($this->paramName),
      empty($this->params['mail_from'])
        ? '' : papaya_strings::escapeHTMLChars($this->params['mail_from'])
    );
    $result .= sprintf(
      '<label for="%s[mail_comments]">%s</label>'.LF,
      papaya_strings::escapeHTMLChars($this->paramName),
      papaya_strings::escapeHTMLChars($this->data['cap_comments'])
    );
    $result .= sprintf(
      '<textarea name="%s[mail_comments]" class="text">%s</textarea>'.LF,
      papaya_strings::escapeHTMLChars($this->paramName),
      empty($this->params['mail_comments'])
        ? '' : papaya_strings::escapeHTMLChars($this->params['mail_comments'])
    );
    $result .= sprintf(
      '<submitbutton caption="%s"/>',
      papaya_strings::escapeHTMLChars($this->data['caption_submit'])
    );
    $result .= '</form>'.LF;
    return $result;
  }

  /**
  * Check mail dialog input
  *
  * @access public
  * @return array
  */
  function checkMailDialogInput() {
    $result = FALSE;
    if (!empty($this->params['mail_from']) &&
        !PapayaFilterFactory::isEmail($this->params['mail_from'], FALSE)) {
      $result['mail_from'] = $this->data['cap_sender'];
    }
    if (empty($this->params['mail_comments']) ||
        !PapayaFilterFactory::isNotEmpty($this->params['mail_comments'], TRUE)) {
      $result['mail_comments'] = $this->data['cap_comments'];
    }
    return $result;
  }
}

