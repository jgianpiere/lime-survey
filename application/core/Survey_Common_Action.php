<?php

/*
* LimeSurvey
* Copyright (C) 2007-2011 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*
*/

/**
* Survey Common Action
*
* This controller contains common functions for survey related views.
*
* @package        LimeSurvey
* @subpackage    Backend
* @author        Shitiz Garg
*/
class Survey_Common_Action extends CAction
{
    public function __construct($controller=null, $id=null)
    {
        parent::__construct($controller, $id);
        // Make sure viewHelper can be autoloaded
        Yii::import('application.helpers.viewHelper');
    }

    /**
    * Override runWithParams() implementation in CAction to help us parse
    * requests with subactions.
    *
    * @param array $params URL Parameters
    */
    public function runWithParams($params)
    {
        // Default method that would be called if the subaction and run() do not exist
        $sDefault = 'index';

        // Check for a subaction
        if (empty($params['sa']))
        {
            $sSubAction = $sDefault; // default
        }
        else
        {
            $sSubAction = $params['sa'];
        }
        // Check if the class has the method
        $oClass = new ReflectionClass($this);
        if (!$oClass->hasMethod($sSubAction))
        {
            // If it doesn't, revert to default Yii method, that is run() which should reroute us somewhere else
            $sSubAction = 'run';
        }

        // Populate the params. eg. surveyid -> iSurveyId
        $params = $this->_addPseudoParams($params);

        if (!empty($params['iSurveyId']))
        {
            if(!Survey::model()->findByPk($params['iSurveyId']))
            {
                $this->getController()->error('Invalid A survey id');
            }
            elseif (!Permission::model()->hasSurveyPermission($params['iSurveyId'], 'survey', 'read'))
            {
                $this->getController()->error('No permission');
            }
            else
            {
                LimeExpressionManager::SetSurveyId($params['iSurveyId']); // must be called early - it clears internal cache if a new survey is being used
            }
        }

        // Check if the method is public and of the action class, not its parents
        // ReflectionClass gets us the methods of the class and parent class
        // If the above method existence check passed, it might not be neceessary that it is of the action class
        $oMethod  = new ReflectionMethod($this, $sSubAction);

        // Get the action classes from the admin controller as the urls necessarily do not equal the class names. Eg. survey -> surveyaction
        $aActions = Yii::app()->getController()->getActionClasses();

        if(empty($aActions[$this->getId()]) || strtolower($oMethod->getDeclaringClass()->name) != strtolower($aActions[$this->getId()]) || !$oMethod->isPublic())
        {
            // Either action doesn't exist in our whitelist, or the method class doesn't equal the action class or the method isn't public
            // So let us get the last possible default method, ie. index
            $oMethod = new ReflectionMethod($this, $sDefault);
        }

        // We're all good to go, let's execute it
        // runWithParamsInternal would automatically get the parameters of the method and populate them as required with the params
        return parent::runWithParamsInternal($this, $oMethod, $params);
    }

    /**
    * Some functions have different parameters, which are just an alias of the
    * usual parameters we're getting in the url. This function just populates
    * those variables so that we don't end up in an error.
    *
    * This is also used while rendering wrapped template
    * {@link Survey_Common_Action::_renderWrappedTemplate()}
    *
    * @param array $params Parameters to parse and populate
    * @return array Populated parameters
    */
    private function _addPseudoParams($params)
    {
        // Return if params isn't an array
        if (empty($params) || !is_array($params))
        {
            return $params;
        }

        $pseudos = array(
        'id' => 'iId',
        'gid' => 'iGroupId',
        'qid' => 'iQuestionId',
        'sid' => array('iSurveyId', 'iSurveyID'),
        'surveyid' => array('iSurveyId', 'iSurveyID'),
        'srid' => 'iSurveyResponseId',
        'scid' => 'iSavedControlId',
        'uid' => 'iUserId',
        'ugid' => 'iUserGroupId',
        'fieldname' => 'sFieldName',
        'fieldtext' => 'sFieldText',
        'action' => 'sAction',
        'lang' => 'sLanguage',
        'browselang' => 'sBrowseLang',
        'tokenids' => 'aTokenIds',
        'tokenid' => 'iTokenId',
        'subaction' => 'sSubAction',
        );

        // Foreach pseudo, take the key, if it exists,
        // Populate the values (taken as an array) as keys in params
        // with that key's value in the params
        // (only if that place is empty)
        foreach ($pseudos as $key => $pseudo)
        {
            if (!empty($params[$key]))
            {
                $pseudo = (array) $pseudo;

                foreach ($pseudo as $pseud)
                {
                    if (empty($params[$pseud]))
                    {
                        $params[$pseud] = $params[$key];
                    }
                }
            }
        }

        // Fill param with according existing param, replace existing parameters.
        // iGroupId/gid can be found with qid/iQuestionId
        if(isset($params['iQuestionId']))
        {
            $oQuestion=Question::model()->find("qid=:qid",array(":qid"=>$params['iQuestionId']));//Move this in model to use cache
            if($oQuestion)
            {
                $params['iGroupId']=$params['gid']=$oQuestion->gid;
            }
        }
        // iSurveyId/iSurveyID/sid can be found with gid/iGroupId
        if(isset($params['iGroupId']))
        {
            $oGroup=QuestionGroup::model()->find("gid=:gid",array(":gid"=>$params['iGroupId']));//Move this in model to use cache
            if($oGroup)
            {
                $params['iSurveyId']=$params['iSurveyID']=$params['surveyid']=$params['sid']=$oGroup->sid;
            }
        }

        // Finally return the populated array
        return $params;
    }

    /**
    * Action classes require them to have a run method. We reroute it to index
    * if called.
    */
    public function run()
    {
        $this->index();
    }

    /**
    * Routes the action into correct subaction
    *
    * @access protected
    * @param string $sa
    * @param array $get_vars
    * @return void
    */
    protected function route($sa, array $get_vars)
    {
        $func_args = array();
        foreach ($get_vars as $k => $var)
            $func_args[$k] = Yii::app()->request->getQuery($var);

        return call_user_func_array(array($this, $sa), $func_args);
    }

    /**
    * Renders template(s) wrapped in header and footer
    *
    * Addition of parameters should be avoided if they can be added to $aData
    *
    * NOTE FROM LOUIS : We want to remove this function, wich doesn't respect MVC pattern.
    * The work it's doing should be handle by layout files, and subviews inside views.
    * Eg : for route "admin/survey/sa/listquestiongroups/surveyid/282267"
    *       the Group controller should use a main layout (with admin menu bar as a widget), then render the list view, in wich the question group bar is called as a subview.
    *
    * So for now, we try to evacuate all the renderWrappedTemplate logic (if statements, etc.) to subfunctions, then it will be easier to remove.
    * Comments starting with //// indicate how it should work in the future
    *
    * @param string $sAction Current action, the folder to fetch views from
    * @param string|array $aViewUrls View url(s)
    * @param array $aData Data to be passed on. Optional.
    */
    protected function _renderWrappedTemplate($sAction = '', $aViewUrls = array(), $aData = array())
    {
        // Gather the data
        $aData = $this->_addPseudoParams($aData); //// the check of the surveyid should be done in the Admin controller it self.
        $aData['sImageURL'] = Yii::app()->getBaseUrl(true).'/images/lime-icons/328637/';

        //// This will be handle by subviews inclusions
        $aViewUrls = (array) $aViewUrls; $sViewPath = '/admin/';
        if (!empty($sAction))
            $sViewPath .= $sAction . '/';



        ob_start(); //// That was used before the MVC pattern, in procedural code. Will not be used anymore.

        $this->_showHeaders($aData); //// THe headers will be called from the layout
        $this->_showadminmenu(); //// The admin menu will be called from the layout, probably as a widget for dynamic content.
        $this->_userGroupBar($aData);

        //// Here will start the rendering from the controller of the main view.
        //// For example, the Group controller will use the main.php layout, and then render the view list_group.php
        //// This view will call as a subview the questiongroupbar, and then _listquestiongroup subview.

        //// This check will be useless when it will be handle directly by each specific controller.
        if (!empty($aData['surveyid']))
        {
            //// TODO : check what is doing exactly this function. (application/helpers/expressions/em_manager_helper.php)
            //// If it's about initialiazing global variables, should be removed and parsed in right controllers.
            //// But it seems that it's just useless.
            LimeExpressionManager::StartProcessingPage(false, Yii::app()->baseUrl,true);  // so can click on syntax highlighting to edit questions

            $oSurvey = $aData['oSurvey'] = Survey::model()->findByPk($aData['surveyid']);

            $this->_titlebar($aData);

            //// Each view will call the correct bar as a subview.
            $this->_surveybar($aData);
            $this->_nquestiongroupbar($aData);
            $this->_questionbar($aData);
            $this->_browsemenubar($aData);
            $this->_tokenbar($aData);
            $this->_organizequestionbar($aData);

            //// TODO : check what it's doing exactly, and why it is here (not after or before)
            //// It seems that it is not used at all. It's about timing
            LimeExpressionManager::FinishProcessingPage();



            //// TODO : Move this div inside each correct view ASAP !
            echo '<div class="container-fluid" id="in_survey_common"><div class="row">';

            $this->_updatenotification();
            $this->_notifications();

            //// Here the main content views.
            $this->_surveysidemenu($aData);
            $this->_listquestiongroups($aData);
            $this->_listquestions($aData);
            $this->_nsurveysummary($aData);

        }
        else
        {
            ///
            $this->_fullpagebar($aData);
            $this->_updatenotification();
            $this->_notifications();
            //// TODO : Move this div inside each correct view ASAP !
            echo '
                    <!-- Full page, started in Survey_Common_Action::render_wrapped_template() -->
                        <div class="container-fluid full-page-wrapper" id="in_survey_common_action">
                            <div class="row">';
        }

        //// Here the rendering of all the subviews process. Will not be use anymore, because each subview will be directly called from her parent view.

        ////  TODO : while refactoring, we must replace the use of $aViewUrls by $aData[.. conditions ..], and then call to function such as $this->_nsurveysummary($aData);

        // Load views
        foreach ($aViewUrls as $sViewKey => $viewUrl)
        {
            if (empty($sViewKey) || !in_array($sViewKey, array('message', 'output')))
            {
                if (is_numeric($sViewKey))
                {
                    Yii::app()->getController()->renderPartial($sViewPath . $viewUrl, $aData);
                }
                elseif (is_array($viewUrl))
                {
                    foreach ($viewUrl as $aSubData)
                    {
                        $aSubData = array_merge($aData, $aSubData);
                        Yii::app()->getController()->renderPartial($sViewPath . $sViewKey, $aSubData);
                    }
                }
            }
            else
            {
                switch ($sViewKey)
                {
                    //// We'll use some Bootstrap alerts, and call them inside each correct view.
                    // Message
                    case 'message' :
                        if (empty($viewUrl['class']))
                        {
                            Yii::app()->getController()->_showMessageBox($viewUrl['title'], $viewUrl['message']);
                        }
                        else
                        {
                            Yii::app()->getController()->_showMessageBox($viewUrl['title'], $viewUrl['message'], $viewUrl['class']);
                        }
                        break;

                        // Output
                    case 'output' :
                        //// TODO : http://goo.gl/ABl5t5

                        echo $viewUrl;

                        if(isset($aViewUrls['afteroutput']))
                            echo $aViewUrls['afteroutput'];

                        break;
                }
            }
        }

        //// TODO : Move this div inside each correct view ASAP !
        echo '</div></div>' ;



        //// THe footer will be called directly from the layout.
        // Footer
        if(!isset($aData['display']['endscripts']) || $aData['display']['endscripts'] !== false)
            Yii::app()->getController()->_loadEndScripts();

        if( !Yii::app()->user->isGuest )
        {
        if(!isset($aData['display']['footer']) || $aData['display']['footer'] !== false)
            Yii::app()->getController()->_getAdminFooter('http://manual.limesurvey.org', gT('LimeSurvey online manual'));
        }

        $out = ob_get_contents();
        ob_clean();
        App()->getClientScript()->render($out);
        echo $out;
    }

    /**
     * Display the update notification
     */
    function _updatenotification()
    {
        if( !Yii::app()->user->isGuest )
        {
            $updateModel = new UpdateForm();
            $updateNotification = $updateModel->updateNotification;

            if($updateNotification->result)
            {
                return $this->getController()->renderPartial("/admin/update/_update_notification", array('security_update_available'=>$updateNotification->security_update));
            }
        }
    }

    /**
     * Display notifications
     */
    function _notifications()
    {
            $aMessage = App()->session['arrayNotificationMessages'];
            unset(App()->session['arrayNotificationMessages']);
            return $this->getController()->renderPartial("notifications/notifications", array('aMessage'=>$aMessage));
    }

    /**
     * Survey summary
     */
    function _nsurveysummary($aData)
    {
        if (isset($aData['display']['surveysummary']))
        {
            if ((empty($aData['display']['menu_bars']['surveysummary']) || !is_string($aData['display']['menu_bars']['surveysummary'])) && !empty($aData['gid']))
            {
                $aData['display']['menu_bars']['surveysummary'] = 'viewgroup';
            }
            $this->_surveysummary($aData);
        }
    }

    /**
     * Header
     */
    function _showHeaders($aData)
    {
        if(!isset($aData['display']['header']) || $aData['display']['header'] !== false)
        {
            // Send HTTP header
            header("Content-type: text/html; charset=UTF-8"); // needed for correct UTF-8 encoding
            Yii::app()->getController()->_getAdminHeader();
        }
    }


    /**
    * _showadminmenu() function returns html text for the administration button bar
    *
    * @access public
    * @global string $homedir
    * @global string $scriptname
    * @global string $surveyid
    * @global string $setfont
    * @global string $imageurl
    * @param int $surveyid
    * @return string $adminmenu
    */
    public function _showadminmenu()
    {
        // We don't wont the admin menu to be shown in login page
        if( !Yii::app()->user->isGuest )
        {
            // Default password notification
            if (Yii::app()->session['pw_notify'] && Yii::app()->getConfig("debug")<2)
                Yii::app()->session['flashmessage'] = gT("Warning: You are still using the default password ('password'). Please change your password and re-login again.");

            // Count active survey
            $model =  new Survey('search');
            $model->active = 'Y';
            $aData['dataForConfigMenu']['activesurveyscount'] = $aData['activesurveyscount'] = $model->search()->itemCount;

            // Count survey
            $model->active = '';
            $aData['dataForConfigMenu']['surveyscount'] = $model->search()->itemCount;

            // Count user
            $aData['dataForConfigMenu']['userscount'] = count(User::model()->findAll());

            // Count tokens and deactivated surveys
            $tablelist = Yii::app()->db->schema->getTableNames();
            foreach ($tablelist as $table)
            {
                if (strpos($table, Yii::app()->db->tablePrefix . "old_tokens_") !== false)
                {
                    $oldtokenlist[] = $table;
                }
                elseif (strpos($table, Yii::app()->db->tablePrefix . "tokens_") !== false)
                {
                    $tokenlist[] = $table;
                }
                elseif (strpos($table, Yii::app()->db->tablePrefix . "old_survey_") !== false)
                {
                    $oldresultslist[] = $table;
                }
            }

            if (isset($oldresultslist) && is_array($oldresultslist))
            {
                $deactivatedsurveys = count($oldresultslist);
            }
            else
            {
                $deactivatedsurveys = 0;
            }
            if (isset($oldtokenlist) && is_array($oldtokenlist))
            {
                $deactivatedtokens = count($oldtokenlist);
            }
            else
            {
                $deactivatedtokens = 0;
            }
            if (isset($tokenlist) && is_array($tokenlist))
            {
                $activetokens = count($tokenlist);
            }
            else
            {
                $activetokens = 0;
            }

            $aData['dataForConfigMenu']['deactivatedsurveys'] = $deactivatedsurveys;
            $aData['dataForConfigMenu']['activetokens'] = $activetokens;
            $aData['dataForConfigMenu']['deactivatedtokens'] = $deactivatedtokens;

            $aData['sitename'] = Yii::app()->getConfig("sitename");

            $updateModel = new UpdateForm();
            $updateNotification = $updateModel->updateNotification;
            $aData['showupdate'] = $updateNotification->result;

            $this->getController()->renderPartial("/admin/super/adminmenu", $aData);
        }
    }


    function _titlebar($aData)
    {
        if( isset($aData['title_bar']) ) {
            $this->getController()->renderPartial("/admin/super/title_bar", $aData);
        }
    }

    function _tokenbar($aData)
    {
        if( isset($aData['token_bar']) ) {
            $this->getController()->renderPartial("/admin/token/token_bar", $aData);
        }
    }

    /**
     * Render the save/cancel bar for Organize question groups/questions
     *
     * @param array $aData
     *
     * @since 2014-09-30
     * @author Olle Haerstedt <olle.haerstedt@limesurvey.org>
     */
    function _organizequestionbar($aData) {
        if (isset($aData['organizebar'])) {
            $this->getController()->renderPartial("/admin/survey/Question/organizequestionbar_view", $aData);
        }
    }

    /**
    * Shows admin menu for question
    *
    * @param int Survey id
    * @param int Group id
    * @param int Question id
    * @param string action
    */
    function _questionbar($aData)
    {
        if(isset($aData['questionbar']))
        {
            if (is_object($aData['oSurvey']))
            {

                $iSurveyID = $aData['surveyid'];
                $oSurvey = $aData['oSurvey'];
                $gid = $aData['gid'];
                $qid = $aData['qid'];

                // action
                $action = (!empty($aData['display']['menu_bars']['qid_action'])) ? $aData['display']['menu_bars']['qid_action'] : null;
                $baselang = $oSurvey->language;

                //Show Question Details
                //Count answer-options for this question
                $qrr = Answer::model()->findAllByAttributes(array('qid' => $qid, 'language' => $baselang));
                $aData['qct'] = $qct = count($qrr);

                //Count sub-questions for this question
                $sqrq = Question::model()->findAllByAttributes(array('parent_qid' => $qid, 'language' => $baselang));
                $aData['sqct'] = $sqct = count($sqrq);

                $qrrow = Question::model()->findByAttributes(array('qid' => $qid, 'gid' => $gid, 'sid' => $iSurveyID, 'language' => $baselang));
                if (is_null($qrrow)) return;
                $questionsummary = "<div class='menubar'>\n";

                // Check if other questions in the Survey are dependent upon this question
                $condarray = getQuestDepsForConditions($iSurveyID, "all", "all", $qid, "by-targqid", "outsidegroup");

                $surveyinfo = $oSurvey->attributes;

                $surveyinfo = array_map('flattenText', $surveyinfo);
                $aData['activated'] = $surveyinfo['active'];

                $qrrow = $qrrow->attributes;
                $aData['languagelist'] = $oSurvey->getAllLanguages();
                $aData['qtypes'] = $qtypes = getQuestionTypeList('', 'array');
                $aData['action'] = $action;
                $aData['surveyid'] = $iSurveyID;
                $aData['qid'] = $qid;
                $aData['gid'] = $gid;
                $aData['qrrow'] = $qrrow;
                $aData['baselang'] = $baselang;

                $aAttributesWithValues = Question::model()->getAdvancedSettingsWithValues($qid, $qrrow['type'], $iSurveyID, $baselang);

                $DisplayArray = array();
                foreach ($aAttributesWithValues as $aAttribute)
                {
                    if (($aAttribute['i18n'] == false && isset($aAttribute['value']) && $aAttribute['value'] != $aAttribute['default']) ||
                        ($aAttribute['i18n'] == true && isset($aAttribute['value'][$baselang]) && $aAttribute['value'][$baselang] != $aAttribute['default']))
                    {
                        if ($aAttribute['inputtype'] == 'singleselect')
                        {
                            $aAttribute['value'] = $aAttribute['options'][$aAttribute['value']];
                        }
                        $DisplayArray[] = $aAttribute;
                    }
                }

                $aData['advancedsettings'] = $DisplayArray;
                $aData['condarray'] = $condarray;
                $questionsummary .= $this->getController()->renderPartial('/admin/survey/Question/questionbar_view', $aData, true);
                $finaldata['display'] = $questionsummary;
                $this->getController()->renderPartial('/survey_view', $finaldata);
            }
            else
            {
                Yii::app()->session['flashmessage'] = gT("Invalid survey ID");
                $this->getController()->redirect(array("admin/index"));
            }
        }
    }


    function _nquestiongroupbar($aData)
    {
        if(isset($aData['questiongroupbar']))
        {
            if(!isset($aData['gid']))
            {
                if(isset($_GET['gid']))
                {
                   $aData['gid'] = $_GET['gid'];
                }
            }
            $surveyid = $aData['surveyid'];
            $gid = $aData['gid'];
            $oSurvey = $aData['oSurvey'];
            $baselang =$oSurvey->language;

            $sumresult4 = Question::model()->findAllByAttributes(array('sid' => $surveyid, 'gid' => $gid, 'language' => $baselang));
            $sumcount4 = count($sumresult4);
            $aData['sumcount4'] = $sumcount4;

            $sumresult1 = Survey::model()->with(array(
                'languagesettings' => array('condition' => 'surveyls_language=language'))
                )->findByPk($surveyid); //$sumquery1, 1) ; //Checked //  if surveyid is invalid then die to prevent errors at a later time
            $surveyinfo = $sumresult1->attributes;
            $surveyinfo = array_merge($surveyinfo, $sumresult1->defaultlanguage->attributes);
            $surveyinfo = array_map('flattenText', $surveyinfo);
            //$surveyinfo = array_map('htmlspecialchars', $surveyinfo);
            $aData['activated'] = $activated = $surveyinfo['active'];

            $condarray = getGroupDepsForConditions($surveyid, "all", $gid, "by-targgid");
            $aData['condarray'] = $condarray;

            $aData['languagelist'] = $oSurvey->getAllLanguages();
            $this->getController()->renderPartial("/admin/survey/QuestionGroups/nquestiongroupbar_view", $aData);
        }
    }

    function _fullpagebar($aData)
    {
        if((isset($aData['fullpagebar'])))
        {
            $this->getController()->renderPartial("/admin/super/fullpagebar_view", $aData);
        }
    }

    /**
    * Shows admin menu for surveys
    * @param int Survey id
    */
    function _surveybar($aData)
    {
        if((isset($aData['surveybar'])))
        {
            $iSurveyID = $aData['surveyid'];
            $oSurvey = $aData['oSurvey'];
            $gid = isset($aData['gid'])?$aData['gid']:null;
            $surveyinfo = ( isset($aData['surveyinfo']) )?$aData['surveyinfo']:$oSurvey->surveyinfo;
            $baselang = $surveyinfo['language'];

            $activated = ($surveyinfo['active'] == 'Y');
            App()->getClientScript()->registerPackage('jquery-cookie');
            App()->getClientScript()->registerScriptFile(Yii::app()->getConfig('adminscripts') . 'surveytoolbar.js');

            //Parse data to send to view
            $aData['surveyinfo'] = $surveyinfo;

            // ACTIVATE SURVEY BUTTON
            $aData['activated'] = $activated;

            $condition = array('sid' => $iSurveyID, 'parent_qid' => 0, 'language' => $baselang);

            //$sumquery3 =  "SELECT * FROM ".db_table_name('questions')." WHERE sid={$iSurveyID} AND parent_qid=0 AND language='".$baselang."'"; //Getting a count of questions for this survey
            $sumresult3 = Question::model()->findAllByAttributes($condition); //Checked
            $sumcount3 = count($sumresult3);

            $aData['canactivate'] = $sumcount3 > 0 && Permission::model()->hasSurveyPermission($iSurveyID, 'surveyactivation', 'update');
            $aData['candeactivate'] = Permission::model()->hasSurveyPermission($iSurveyID, 'surveyactivation', 'update');
            $aData['expired'] = $surveyinfo['expires'] != '' && ($surveyinfo['expires'] < dateShift(date("Y-m-d H:i:s"), "Y-m-d H:i", Yii::app()->getConfig('timeadjust')));
            $aData['notstarted'] = ($surveyinfo['startdate'] != '') && ($surveyinfo['startdate'] > dateShift(date("Y-m-d H:i:s"), "Y-m-d H:i", Yii::app()->getConfig('timeadjust')));

            // Start of suckerfish menu
            // TEST BUTTON
            if (!$activated)
            {
                $aData['icontext'] = gT("Test this survey");
            }
            else
            {
                $aData['icontext'] = gT("Execute this survey");
            }

            $aData['baselang'] = $oSurvey->language;
            $aData['additionallanguages'] = $oSurvey->getAdditionalLanguages();
            $aData['languagelist'] =  $oSurvey->getAllLanguages();
            $aData['onelanguage']=(count($aData['languagelist'])==1);


            $aData['hasadditionallanguages'] = (count($aData['additionallanguages']) > 0);

            // EDIT SURVEY TEXT ELEMENTS BUTTON
            $aData['surveylocale'] = Permission::model()->hasSurveyPermission($iSurveyID, 'surveylocale', 'read');
            // EDIT SURVEY SETTINGS BUTTON
            $aData['surveysettings'] = Permission::model()->hasSurveyPermission($iSurveyID, 'surveysettings', 'read');
            // Survey permission item
            $aData['surveysecurity'] = Permission::model()->hasSurveyPermission($iSurveyID, 'surveysecurity', 'read');
            // CHANGE QUESTION GROUP ORDER BUTTON
            $aData['surveycontent'] = Permission::model()->hasSurveyPermission($iSurveyID, 'surveycontent', 'read');
            $aData['groupsum'] = (getGroupSum($iSurveyID, $surveyinfo['language']) > 1);
            // SET SURVEY QUOTAS BUTTON
            $aData['quotas'] = Permission::model()->hasSurveyPermission($iSurveyID, 'quotas', 'read');
            // Assessment menu item
            $aData['assessments'] = Permission::model()->hasSurveyPermission($iSurveyID, 'assessments', 'read');
            // EDIT SURVEY TEXT ELEMENTS BUTTON
            // End if survey properties
            // Tools menu item
            // Delete survey item
            $aData['surveydelete'] = Permission::model()->hasSurveyPermission($iSurveyID, 'survey', 'delete');
            // Translate survey item
            $aData['surveytranslate'] = Permission::model()->hasSurveyPermission($iSurveyID, 'translations', 'read');
            // RESET SURVEY LOGIC BUTTON
            //$sumquery6 = "SELECT count(*) FROM ".db_table_name('conditions')." as c, ".db_table_name('questions')."
            // as q WHERE c.qid = q.qid AND q.sid=$iSurveyID"; //Getting a count of conditions for this survey
            // TMSW Condition->Relevance:  How is conditionscount used?  Should Relevance do the same?

            $iConditionCount = Condition::model()->with(Array('questions'=>array('condition'=>'sid ='.$iSurveyID)))->count();

            $aData['surveycontent'] = Permission::model()->hasSurveyPermission($iSurveyID, 'surveycontent', 'update');
            $aData['conditionscount'] = ($iConditionCount > 0);
            // Eport menu item
            $aData['surveyexport'] = Permission::model()->hasSurveyPermission($iSurveyID, 'surveycontent', 'export');
            // PRINTABLE VERSION OF SURVEY BUTTON
            // SHOW PRINTABLE AND SCANNABLE VERSION OF SURVEY BUTTON
            //browse responses menu item
            $aData['respstatsread'] = Permission::model()->hasSurveyPermission($iSurveyID, 'responses', 'read')
                || Permission::model()->hasSurveyPermission($iSurveyID, 'statistics', 'read')
                || Permission::model()->hasSurveyPermission($iSurveyID, 'responses', 'export');
            // Data entry screen menu item
            $aData['responsescreate'] = Permission::model()->hasSurveyPermission($iSurveyID, 'responses', 'create');
            $aData['responsesread'] = Permission::model()->hasSurveyPermission($iSurveyID, 'responses', 'read');
            // TOKEN MANAGEMENT BUTTON
            $bTokenExists = tableExists('{{tokens_' . $iSurveyID . '}}');
            if(!$bTokenExists) {
                $aData['tokenmanagement'] = Permission::model()->hasSurveyPermission($iSurveyID, 'surveysettings', 'update')
                    || Permission::model()->hasSurveyPermission($iSurveyID, 'tokens', 'create');
            }
            else {
                $aData['tokenmanagement'] = Permission::model()->hasSurveyPermission($iSurveyID, 'surveysettings', 'update')
                    || Permission::model()->hasSurveyPermission($iSurveyID, 'tokens', 'create')
                    || Permission::model()->hasSurveyPermission($iSurveyID, 'tokens', 'read')
                    || Permission::model()->hasSurveyPermission($iSurveyID, 'tokens', 'export')
                    || Permission::model()->hasSurveyPermission($iSurveyID, 'tokens', 'import'); // and export / import ?
            }

            $aData['gid'] = $gid; // = $this->input->post('gid');

            if (Permission::model()->hasSurveyPermission($iSurveyID, 'surveycontent', 'read'))
            {
                $aData['permission'] = true;
            }
            else
            {
                $aData['gid'] = $gid = null;
                $qid = null;
                $aData['permission'] = false;
            }

            if (getGroupListLang($gid, $baselang, $iSurveyID))
            {
                $aData['groups'] = getGroupListLang($gid, $baselang, $iSurveyID);
            }
            else
            {
                $aData['groups'] = "<option>" . gT("None") . "</option>";
            }

            $aData['GidPrev'] = $GidPrev = getGidPrevious($iSurveyID, $gid);

            $aData['GidNext'] = $GidNext = getGidNext($iSurveyID, $gid);
            $aData['iIconSize'] = Yii::app()->getConfig('adminthemeiconsize');
            if($aData['gid']==null)
                $this->getController()->renderPartial("/admin/survey/surveybar_view", $aData);
        }
    }


    /**
     * Show side menu for survey view
     * @param array $aData all the needed data
     */
    function _surveysidemenu($aData)
    {
        // Side menu scripts
        App()->getClientScript()->registerPackage('sidemenu');

        $iSurveyID = $aData['surveyid'];
        // TODO : create subfunctions
        $sumresult1 = Survey::model()->with(array(
            'languagesettings' => array('condition'=>'surveyls_language=language'))
        )->find('sid = :surveyid', array(':surveyid' => $aData['surveyid'])); //$sumquery1, 1) ; //Checked

        if (Permission::model()->hasSurveyPermission($iSurveyID, 'surveycontent', 'read'))
        {
            $aData['permission'] = true;
        }
        else
        {
            $aData['gid'] = $gid = null;
            $qid = null;
            $aData['permission'] = false;
        }

        if (!is_null($sumresult1))
        {
            $surveyinfo = $sumresult1->attributes;
            $surveyinfo = array_merge($surveyinfo, $sumresult1->defaultlanguage->attributes);
            $surveyinfo = array_map('flattenText', $surveyinfo);
            $aData['activated'] = ($surveyinfo['active'] == 'Y');

            // Tokens
            $bTokenExists = tableExists('{{tokens_' . $iSurveyID . '}}');
            if (!$bTokenExists) {
                $aData['tokenmanagement'] = Permission::model()->hasSurveyPermission($iSurveyID, 'surveysettings', 'update')
                    || Permission::model()->hasSurveyPermission($iSurveyID, 'tokens', 'create');
            }
            else {
                $aData['tokenmanagement'] = Permission::model()->hasSurveyPermission($iSurveyID, 'surveysettings', 'update')
                    || Permission::model()->hasSurveyPermission($iSurveyID, 'tokens', 'create')
                    || Permission::model()->hasSurveyPermission($iSurveyID, 'tokens', 'read')
                    || Permission::model()->hasSurveyPermission($iSurveyID, 'tokens', 'export')
                    || Permission::model()->hasSurveyPermission($iSurveyID, 'tokens', 'import'); // and export / import ?
            }

            // Question explorer
            $aGroups = QuestionGroup::model()->findAllByAttributes(array('sid' => $iSurveyID, "language" => $sumresult1->defaultlanguage->surveyls_language));
            if(count($aGroups))
            {
                foreach($aGroups as $group)
                {
                    $group->aQuestions = Question::model()->findAllByAttributes(array("sid"=>$iSurveyID, "gid"=>$group['gid'],"language"=>$sumresult1->defaultlanguage->surveyls_language));
                }
            }
            $aData['aGroups'] = $aGroups;
            $aData['surveycontent'] = Permission::model()->hasSurveyPermission($aData['surveyid'], 'surveycontent', 'read');
            $this->getController()->renderPartial("/admin/super/sidemenu", $aData);
        }
        else
        {
            Yii::app()->session['flashmessage'] = gT("Invalid survey ID");
            $this->getController()->redirect(array("admin/index"));
        }
    }

    /**
     * listquestion groups
     */
    private function _listquestiongroups($aData)
    {
        if ( isset($aData['display']['menu_bars']['listquestiongroups']) )
        {
            $this->getController()->renderPartial("/admin/survey/QuestionGroups/listquestiongroups", $aData);
        }
    }

    private function _listquestions($aData)
    {
        if ( isset($aData['display']['menu_bars']['listquestions']) )
        {
            $iSurveyID = $aData['surveyid'];
            $oSurvey = $aData['oSurvey'];
            $baselang = $oSurvey->language;

            // The DataProvider will be build from the Question model, search method
            $model = new Question('search');

            // Global filter
            if (isset($_GET['Question']))
                $model->attributes = $_GET['Question'];

            // Filter group
            if (isset($_GET['group_name']))
                $model->group_name = $_GET['group_name'];

            // Set number of page
            if (isset($_GET['pageSize']))
                Yii::app()->user->setState('pageSize',(int)$_GET['pageSize']);

            // We filter the current survey id
            $model['sid'] = $iSurveyID;
            $model['language'] = $baselang;

            $aData['model']=$model;

            $this->getController()->renderPartial("/admin/survey/Question/listquestions", $aData);
        }
    }

    /**
    * Show survey summary
    * @param int Survey id
    * @param string Action to be performed
    */
    function _surveysummary($aData)
    {
        $iSurveyID = $aData['surveyid'];

        $aSurveyInfo=getSurveyInfo($iSurveyID);
        $oSurvey = $aData['oSurvey'];
        $baselang = $aSurveyInfo['language'];
        $activated = $aSurveyInfo['active'];

        $condition = array('sid' => $iSurveyID, 'parent_qid' => 0, 'language' => $baselang);

        $sumresult3 = Question::model()->findAllByAttributes($condition); //Checked
        $sumcount3 = count($sumresult3);
        $condition = array('sid' => $iSurveyID, 'language' => $baselang);

        $sumresult2 = QuestionGroup::model()->findAllByAttributes($condition); //Checked
        $sumcount2 = count($sumresult2);

        //SURVEY SUMMARY
        $aAdditionalLanguages = $oSurvey->additionalLanguages;
        $surveysummary2 = "";
        if ($aSurveyInfo['anonymized'] != "N")
        {
            $surveysummary2 .= gT("Responses to this survey are anonymized.") . "<br />";
        }
        else
        {
            $surveysummary2 .= gT("Responses to this survey are NOT anonymized.") . "<br />";
        }
        if ($aSurveyInfo['format'] == "S")
        {
            $surveysummary2 .= gT("It is presented question by question.") . "<br />";
        }
        elseif ($aSurveyInfo['format'] == "G")
        {
            $surveysummary2 .= gT("It is presented group by group.") . "<br />";
        }
        else
        {
            $surveysummary2 .= gT("It is presented on one single page.") . "<br />";
        }
        if ($aSurveyInfo['questionindex'] > 0)
        {
            if ($aSurveyInfo['format'] == 'A')
            {
                $surveysummary2 .= gT("No question index will be shown with this format.") . "<br />";
            }
            elseif ($aSurveyInfo['questionindex'] == 1)
            {
                $surveysummary2 .= gT("A question index will be shown; participants will be able to jump between viewed questions.") . "<br />";
            }
            elseif ($aSurveyInfo['questionindex'] == 2)
            {
                $surveysummary2 .= gT("A full question index will be shown; participants will be able to jump between relevant questions.") . "<br />";
            }
        }
        if ($aSurveyInfo['datestamp'] == "Y")
        {
            $surveysummary2 .= gT("Responses will be date stamped.") . "<br />";
        }
        if ($aSurveyInfo['ipaddr'] == "Y")
        {
            $surveysummary2 .= gT("IP Addresses will be logged") . "<br />";
        }
        if ($aSurveyInfo['refurl'] == "Y")
        {
            $surveysummary2 .= gT("Referrer URL will be saved.") . "<br />";
        }
        if ($aSurveyInfo['usecookie'] == "Y")
        {
            $surveysummary2 .= gT("It uses cookies for access control.") . "<br />";
        }
        if ($aSurveyInfo['allowregister'] == "Y")
        {
            $surveysummary2 .= gT("If tokens are used, the public may register for this survey") . "<br />";
        }
        if ($aSurveyInfo['allowsave'] == "Y" && $aSurveyInfo['tokenanswerspersistence'] == 'N')
        {
            $surveysummary2 .= gT("Participants can save partially finished surveys") . "<br />\n";
        }
        if ($aSurveyInfo['emailnotificationto'] != '')
        {
            $surveysummary2 .= gT("Basic email notification is sent to:") .' '. htmlspecialchars($aSurveyInfo['emailnotificationto'])."<br />\n";
        }
        if ($aSurveyInfo['emailresponseto'] != '')
        {
            $surveysummary2 .= gT("Detailed email notification with response data is sent to:") .' '. htmlspecialchars($aSurveyInfo['emailresponseto'])."<br />\n";
        }

        $dateformatdetails = getDateFormatData(Yii::app()->session['dateformat']);
        if (trim($aSurveyInfo['startdate']) != '')
        {
            Yii::import('application.libraries.Date_Time_Converter');
            $datetimeobj = new Date_Time_Converter($aSurveyInfo['startdate'], 'Y-m-d H:i:s');
            $aData['startdate'] = $datetimeobj->convert($dateformatdetails['phpdate'] . ' H:i');
        }
        else
        {
            $aData['startdate'] = "-";
        }

        if (trim($aSurveyInfo['expires']) != '')
        {
            //$constructoritems = array($surveyinfo['expires'] , "Y-m-d H:i:s");
            Yii::import('application.libraries.Date_Time_Converter');
            $datetimeobj = new Date_Time_Converter($aSurveyInfo['expires'], 'Y-m-d H:i:s');
            //$datetimeobj = new Date_Time_Converter($surveyinfo['expires'] , "Y-m-d H:i:s");
            $aData['expdate'] = $datetimeobj->convert($dateformatdetails['phpdate'] . ' H:i');
        }
        else
        {
            $aData['expdate'] = "-";
        }

        if (!$aSurveyInfo['language'])
        {
            $aData['language'] = getLanguageNameFromCode($currentadminlang, false);
        }
        else
        {
            $aData['language'] = getLanguageNameFromCode($aSurveyInfo['language'], false);
        }

        // get the rowspan of the Additionnal languages row
        // is at least 1 even if no additionnal language is present
        $additionnalLanguagesCount = count($aAdditionalLanguages);
        $first = true;
         if ($aSurveyInfo['surveyls_urldescription'] == "")
        {
            $aSurveyInfo['surveyls_urldescription'] = htmlspecialchars($aSurveyInfo['surveyls_url']);
        }

        if ($aSurveyInfo['surveyls_url'] != "")
        {
            $aData['endurl'] = " <a target='_blank' href=\"" . htmlspecialchars($aSurveyInfo['surveyls_url']) . "\" title=\"" . htmlspecialchars($aSurveyInfo['surveyls_url']) . "\">".flattenText($aSurveyInfo['surveyls_urldescription'])."</a>";
        }
        else
        {
            $aData['endurl'] = "-";
        }

        $aData['sumcount3'] = $sumcount3;
        $aData['sumcount2'] = $sumcount2;

        if ($activated == "N")
        {
            $aData['activatedlang'] = gT("No");
        }
        else
        {
            $aData['activatedlang'] = gT("Yes");
        }

        $aData['activated'] = $activated;
        if ($activated == "Y")
        {
            $aData['surveydb'] = Yii::app()->db->tablePrefix . "survey_" . $iSurveyID;
        }

        $aData['warnings'] = "";
        if ($activated == "N" && $sumcount3 == 0)
        {
            $aData['warnings'] = gT("Survey cannot be activated yet.") . "<br />\n";
            if ($sumcount2 == 0 && Permission::model()->hasSurveyPermission($iSurveyID, 'surveycontent', 'create'))
            {
                $aData['warnings'] .= "<span class='statusentryhighlight'>[" . gT("You need to add question groups") . "]</span><br />";
            }
            if ($sumcount3 == 0 && Permission::model()->hasSurveyPermission($iSurveyID, 'surveycontent', 'create'))
            {
                $aData['warnings'] .= "<span class='statusentryhighlight'>[" . gT("You need to add questions") . "]</span><br />";
            }
        }
        $aData['hints'] = $surveysummary2;

        //return (array('column'=>array($columns_used,$hard_limit) , 'size' => array($length, $size_limit) ));
        //        $aData['tableusage'] = getDBTableUsage($iSurveyID);
        // ToDo: Table usage is calculated on every menu display which is too slow with bug surveys.
        // Needs to be moved to a database field and only updated if there are question/subquestions added/removed (it's currently also not functional due to the port)
        //

        $aData['tableusage'] = false;
        $aData['aAdditionalLanguages'] = $aAdditionalLanguages;
        $aData['surveyinfo'] = $aSurveyInfo;

        $aData['groups_count'] = $sumcount2;
        $this->getController()->renderPartial("/admin/survey/surveySummary_view", $aData);
    }

    /**
    * Browse Menu Bar
    */
    function _browsemenubar($aData)
    {
        if (!empty($aData['display']['menu_bars']['browse']) && !empty($aData['surveyid']))
        {
            //BROWSE MENU BAR

            $iSurveyID=$aData['surveyid'];
            $oSurvey = $aData['oSurvey'];
            $aData['title'] = $aData['display']['menu_bars']['browse'];
            $aData['thissurvey'] = getSurveyInfo($iSurveyID);
            $aData['surveyid'] = $iSurveyID;

            $tmp_survlangs = $oSurvey->additionalLanguages;
            $baselang = $oSurvey->language;
            $tmp_survlangs[] = $baselang;
            rsort($tmp_survlangs);
            $aData['tmp_survlangs'] = $tmp_survlangs;

            $this->getController()->renderPartial("/admin/responses/browsemenubar_view", $aData);
        }
    }
    /**
    * Load menu bar of user group controller.
    * @param int $ugid
    * @return void
    */
    function _userGroupBar($aData)
    {
        $ugid = (isset($aData['ugid'])) ? $aData['ugid'] : 0 ;
        if (!empty($aData['display']['menu_bars']['user_group']))
        {
            $data = $aData;
            Yii::app()->loadHelper('database');

            if (!empty($ugid)) {
                $sQuery = "SELECT gp.* FROM {{user_groups}} AS gp, {{user_in_groups}} AS gu WHERE gp.ugid=gu.ugid AND gp.ugid = {$ugid}";
                if (!Permission::model()->hasGlobalPermission('superadmin','read'))
                {
                    $sQuery .=" AND gu.uid = ".Yii::app()->session['loginID'];
                }

                $grpresult = Yii::app()->db->createCommand($sQuery)->queryRow();  //Checked

                if ($grpresult) {
                    $grpresultcount=1;
                    $grow = array_map('htmlspecialchars', $grpresult);
                }
                else
                {
                    $grpresultcount=0;
                    $grow = false;
                }

                $data['grow'] = $grow;
                $data['grpresultcount'] = $grpresultcount;

            }

            $data['ugid'] = $ugid;
            $data['imageurl'] = Yii::app()->getConfig("adminimageurl"); // Don't came from rendertemplate ?
            $this->getController()->renderPartial('/admin/usergroup/usergroupbar_view', $data);
        }
    }

    protected function _filterImportedResources($extractdir, $destdir)
    {
        $aErrorFilesInfo = array();
        $aImportedFilesInfo = array();

        if (!is_dir($extractdir))
            return array(array(), array());

        if (!is_dir($destdir))
            mkdir($destdir);

        $dh = opendir($extractdir);

        while ($direntry = readdir($dh))
        {
            if ($direntry != "." && $direntry != "..")
            {
                if (is_file($extractdir . "/" . $direntry))
                {
                    // is  a file
                    $extfile = substr(strrchr($direntry, '.'), 1);
                    if (!(stripos(',' . Yii::app()->getConfig('allowedresourcesuploads') . ',', ',' . $extfile . ',') === false))
                    {
                        // Extension allowed
                        if (!copy($extractdir . "/" . $direntry, $destdir . "/" . $direntry))
                        {
                            $aErrorFilesInfo[] = Array(
                            "filename" => $direntry,
                            "status" => gT("Copy failed")
                            );
                        }
                        else
                        {
                            $aImportedFilesInfo[] = Array(
                            "filename" => $direntry,
                            "status" => gT("OK")
                            );
                        }
                    }
                    else
                    {
                        // Extension forbidden
                        $aErrorFilesInfo[] = Array(
                        "filename" => $direntry,
                        "status" => gT("Forbidden Extension")
                        );
                    }
                    unlink($extractdir . "/" . $direntry);
                }
            }
        }

        return array($aImportedFilesInfo, $aErrorFilesInfo);
    }

    /**
    * Creates a temporary directory
    *
    * @access protected
    * @param string $dir
    * @param string $prefix
    * @param int $mode
    * @return string
    */
    protected function _tempdir($dir, $prefix='', $mode=0700)
    {
        if (substr($dir, -1) != DIRECTORY_SEPARATOR)
            $dir .= DIRECTORY_SEPARATOR;

        do
        {
            $path = $dir . $prefix . mt_rand(0, 9999999);
        }
        while (!mkdir($path, $mode));

        return $path;
    }

}