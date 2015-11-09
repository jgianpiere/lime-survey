<?php
/**
 * Right accordion in the edit survey page 
 *
 * @var $data
 */
?>

<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
    
    <!-- General Option -->
    <div class="panel panel-default" id="generaloptionsContainer">
        <div class="panel-heading" role="tab" id="headingOne">
            <h4 class="panel-title">
            <a role="button" data-toggle="collapse" data-parent="#accordion" href="#generaloptions" aria-expanded="true" aria-controls="generaloptions">
            <?php eT("General Option");?>
            </a>
            </h4>
        </div>
        <div id="generaloptions" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
            <div class="panel-body">
                <?php $this->renderPartial('/admin/survey/subview/accordion/_generaloptions_panel', $data); ?>
            </div>
        </div>
    </div>    
    
    <!-- Presentation & navigation  -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingTwo">
            <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#presentationoptions" aria-expanded="false" aria-controls="presentationoptions">
              <?php  eT("Presentation & navigation"); ?>
            </a>                        
            </h4>
        </div>
        <div id="presentationoptions" class="panel-collapse collapse" role="tabpanel" aria-labelledby="presentationoptions">
            <div class="panel-body">
                <?php $this->renderPartial('/admin/survey/subview/accordion/_presentation_panel', $data); ?>    
            </div>
        </div>
    </div>

    <!-- Publication & access control -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingThree">
            <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#publicationoptions" aria-expanded="false" aria-controls="publicationoptions">
              <?php  eT("Publication & access control"); ?>
            </a>                        
            </h4>
        </div>
        <div id="publicationoptions" class="panel-collapse collapse" role="tabpanel" aria-labelledby="publicationoptions">
            <div class="panel-body">
                <?php $this->renderPartial('/admin/survey/subview/accordion/_publication_panel', $data); ?>
            </div>
        </div>
    </div>

    <!-- Notification & data management -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingFour">
            <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#notificationoptions" aria-expanded="false" aria-controls="notificationoptions">
                <?php  eT("Notification & data management"); ?>
            </a>                        
            </h4>
        </div>
        <div id="notificationoptions" class="panel-collapse collapse" role="tabpanel" aria-labelledby="notificationoptions">
            <div class="panel-body">
                <?php $this->renderPartial('/admin/survey/subview/accordion/_notification_panel', $data); ?>
            </div>
        </div>
    </div>

    <!-- Tokens -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingFive">
            <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#tokensoptions" aria-expanded="false" aria-controls="tokensoptions">
                <?php  eT("Tokens"); ?>
            </a>                        
            </h4>
        </div>
        <div id="tokensoptions" class="panel-collapse collapse" role="tabpanel" aria-labelledby="tokensoptions">
            <div class="panel-body">
                <?php $this->renderPartial('/admin/survey/subview/accordion/_tokens_panel', $data); ?>
            </div>
        </div>
    </div>

    <!-- Edition Mode -->    
    <?php if($data['action']=='editsurveysettings'):?>
        
        <!-- Panel integration -->
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="headingSix">
                <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#integrationoptions" aria-expanded="false" aria-controls="resourcesoptions">
                    <?php  eT("Panel integration"); ?>
                </a>                        
                </h4>
            </div>
            <div id="integrationoptions" class="panel-collapse collapse" role="tabpanel" aria-labelledby="integrationoptions">
                <div class="panel-body">
                    <?php $this->renderPartial('/admin/survey/subview/accordion/_integration_panel', $data); ?>
                </div>
            </div>
        </div>                
    
        <!-- Resources -->
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="headingSeven">
                <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#resourcesoptions" aria-expanded="false" aria-controls="resourcesoptions">
                    <?php  eT("Resources"); ?>
                </a>                        
                </h4>
            </div>
            <div id="resourcesoptions" class="panel-collapse collapse" role="tabpanel" aria-labelledby="resourcesoptions">
                <div class="panel-body">
                    <?php $this->renderPartial('/admin/survey/subview/accordion/_resources_panel', $data); ?>
                </div>
            </div>
        </div>                
    <?php endif;?>
</div>