<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
class Index extends Survey_Common_Action
{

    public function run()
    {
        App()->loadHelper('surveytranslator');
        App()->getClientScript()->registerPackage('panel-clickable');
        App()->getClientScript()->registerPackage('panels-animation');
        $aData['issuperadmin'] = false;
        if (Permission::model()->hasGlobalPermission('superadmin','read'))
        {
            $aData['issuperadmin'] = true;
        }

        // We get the last survey visited by user
        $setting_entry = 'last_survey_'.Yii::app()->user->getId();        
        $lastsurvey = getGlobalSetting($setting_entry);
        if( $lastsurvey != null)
        {
            $aData['showLastSurvey'] = true;
            $iSurveyID = $lastsurvey;
            $surveyinfo = Survey::model()->findByPk($iSurveyID)->surveyinfo;
            $aData['surveyTitle'] = $surveyinfo['surveyls_title']."(".gT("ID").":".$iSurveyID.")";           
            $aData['surveyUrl'] = $this->getController()->createUrl("admin/survey/sa/view/surveyid/{$iSurveyID}");
        }
        else 
        {
            $aData['showLastSurvey'] = false;            
        }
        
        // We get the last question visited by user 
        $setting_entry = 'last_question_'.Yii::app()->user->getId();      
        $lastquestion = getGlobalSetting($setting_entry);
        
        // the question group of this question
        $setting_entry = 'last_question_gid_'.Yii::app()->user->getId();
        $lastquestiongroup = getGlobalSetting($setting_entry);
        
        // the sid of this question : last_question_sid_1
        $setting_entry = 'last_question_sid_'.Yii::app()->user->getId();
        $lastquestionsid = getGlobalSetting($setting_entry); 
        
        if( $lastquestion != null && $lastquestiongroup != null)
        {
            $baselang = Survey::model()->findByPk($iSurveyID)->language;
            $aData['showLastQuestion'] = true;
            $qid = $lastquestion;
            $gid = $lastquestiongroup;
            $sid = $lastquestionsid;
            $qrrow = Question::model()->findByAttributes(array('qid' => $qid, 'gid' => $gid, 'sid' => $sid, 'language' => $baselang));
            
            $aData['last_question_name'] = $qrrow['title']; 
            if($qrrow['question'])
                $aData['last_question_name'] .= ' : '.$qrrow['question'];
             
            $aData['last_question_link'] = $this->getController()->createUrl("admin/questions/sa/view/surveyid/$iSurveyID/gid/$gid/qid/$qid");
        }
        else 
        {
           $aData['showLastQuestion'] = false;
        }
        
        $aData['countSurveyList'] = count(getSurveyList(true));
        
        $this->_renderWrappedTemplate('super', 'welcome', $aData);     
    }

}
