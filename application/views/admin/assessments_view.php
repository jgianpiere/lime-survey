<?php
/**
 * Assesments view
 * HTML start in controller
 */
?>
<?php echo PrepareEditorScript(false, $this); ?>
<script type="text/javascript">
    <!--
        var strnogroup='<?php eT("There are no groups available.", "js");?>';
    -->
</script>

<h4><?php eT("Assessment rules");?></h4>

<!-- List assesments -->
<table class='table'>
    
    <!-- header -->
    <thead>
        <tr>
            <th>
                <?php eT("ID");?>
            </th>
            <th>
                <?php eT("Actions");?>
            </th>
            <th>
                <?php eT("SID");?>
            </th>

            <?php foreach ($headings as $head):?>
	           <th><?php echo $head; ?></th>
            <?php endforeach; ?>
            
            <th>
                <?php eT("Title");?>
            </th>
            
            <th>
                <?php eT("Message");?>
            </th>
        </tr>
    </thead>
    
    <!-- body -->
    <tbody>
        <?php foreach($assessments as $assess): ?>
            <tr>   
                <!-- ID --> 
                <td>
                    <?php echo $assess['id'];?>
                </td>
                
                <!-- Actions -->
                <td>
                    <?php if (Permission::model()->hasSurveyPermission($surveyid, 'assessments','update')) { ?>
                        <?php 
                            echo CHtml::link(
                                CHtml::image("{$imageurl}edit_16.png",gT("Edit")),
                                array("admin/assessments","sa"=>"index","surveyid"=>$surveyid,"action"=>'assessmentedit','id'=>$assess['id'])
                            );
                        ?>
                    <?php } ?>
                    
                    <?php if (Permission::model()->hasSurveyPermission($surveyid, 'assessments','delete')) { ?>
                         <?php echo CHtml::form(array("admin/assessments/sa/index/surveyid/{$surveyid}"), 'post');?>
                         <input type='image' src='<?php echo $imageurl;?>/token_delete.png' alt='<?php eT("Delete");?>' onclick='return confirm("<?php eT("Are you sure you want to delete this entry?","js");?>")' />
                         <input type='hidden' name='action' value='assessmentdelete' />
                         <input type='hidden' name='id' value='<?php echo $assess['id'];?>' />
                         </form>
                    <?php } ?>
                </td>
                
                <!-- SID -->
                <td>
                    <?php echo $assess['sid'];?>
                </td>
                
                <!-- Total || Question group -->
                <?php if ($assess['scope'] == "T") { ?>
                	<td><?php eT("Total");?></td>
                	<td>-</td>
                <?php } else { ?>
                	<td><?php eT("Question group");?></td>
                	<td><?php echo $groups[$assess['gid']]." (".$assess['gid'].")";?></td>
                <?php } ?>
                
                <!-- minimum -->
                <td><?php echo $assess['minimum'];?></td>
                
                <!-- maximum -->
                <td><?php echo $assess['maximum'];?></td>
                
                <!-- Score of the current group -->
                <td>
                    <?php 
                    $aReplacement=array('PERC'=>gt('Score of the current group'),'TOTAL'=>gt('Total score'));
                    templatereplace($assess['name'],$aReplacement);
                    echo FlattenText(LimeExpressionManager::GetLastPrettyPrintExpression(), true);
                    ?>
                </td>
                
                <!-- message -->
                <td>
                    <?php 
                    templatereplace($assess['message'],$aReplacement);
                    echo FlattenText(LimeExpressionManager::GetLastPrettyPrintExpression(), true);
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Edition -->
<?php if ((Permission::model()->hasSurveyPermission($surveyid, 'assessments','update') && $actionvalue=="assessmentupdate") || (Permission::model()->hasSurveyPermission($surveyid, 'assessments','create')&& $actionvalue=="assessmentadd")): ?>
    <br />
    <?php echo CHtml::form(array("admin/assessments/sa/index/surveyid/{$surveyid}"), 'post', array('class'=>'form30','id'=>'assessmentsform','name'=>'assessmentsform'));?>
        <h4><?php echo $actiontitle;?></h4>
        <ul class="assessmentscope list-unstyled">
            
            <!-- Scope, Total, Group -->
            <li>
                <label><?php eT("Scope");?></label>
                <input type='radio' id='radiototal' name='scope' value='T' 
                <?php if (!isset($editdata) || $editdata['scope'] == "T") {echo "checked='checked' ";} ?>/>
                
                <label for='radiototal'><?php eT("Total");?></label>
                <input type='radio' id='radiogroup' name='scope' value='G' <?php if (isset($editdata) && $editdata['scope'] == "G") {echo " checked='checked' ";} ?>/>
                
                <label for='radiogroup'><?php eT("Group");?></label>
            </li>
            
            <!-- Question group -->  
            <li>
                <label for='gid'><?php eT("Question group");?></label>
                <?php 
                if (isset($groups))
                { ?>
                    <select name='gid' id='gid' class="form-control">
                        <?php
                        foreach ($groups as $groupId => $groupName) {
                            echo '<option value="' . $groupId . '"'.(isset($editdata['gid']) && $editdata['gid']== $groupId ? ' selected' : '').'>' . $groupName . '</option>';
                        }
                        ?>
                    </select>
                    <br/>
            	<?php
            	}
            	else
            		echo eT("No question group found."); 	 
            	?> 
            </li>
        
            <!-- Minimum -->
            <li>
                <label for='minimum'><?php eT("Minimum");?></label>
                <input type='text' id='minimum' name='minimum' class='numbersonly'<?php if (isset($editdata)) {echo " value='{$editdata['minimum']}' ";} ?>/>
            </li>
            
            <!-- Maximum -->
            <li>
                <label for='maximum'><?php eT("Maximum");?></label>
                <input type='text' id='maximum' name='maximum' class='numbersonly'<?php if (isset($editdata)) {echo " value='{$editdata['maximum']}' ";} ?>/>
            </li>
        </ul>
        
        <!-- Languages tabs -->
        <div id="languagetabs">
            <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all  list-unstyled">
                <?php foreach ($assessmentlangs as $assessmentlang)
                {
                    $position=0;
                    echo '<li class="ui-state-default ui-corner-top" style="clear: none;"><a href="#tablang'.$assessmentlang.'">'.getLanguageNameFromCode($assessmentlang, false);
                    if ($assessmentlang==$baselang) {echo ' ('.gT("Base language").')';}
                    echo '</a></li>';
                } ?>
            </ul>
        
            <?php foreach ($assessmentlangs as $assessmentlang)
            {
                $heading=''; $message='';
                if ($action == "assessmentedit")
                {
                	$results = Assessment::model()->findAllByAttributes(array('id' => $editId, 'language' => $assessmentlang));
            	    foreach ($results as $row) 
                    {
            	        $editdata=$row->attributes;
            	    }
            	    $heading=htmlspecialchars($editdata['name'],ENT_QUOTES);
            	    $message=htmlspecialchars($editdata['message']);
                } ?>
                <div id="tablang<?php echo $assessmentlang;?>">
                    <ul class="list-unstyled"><li><label for='name_<?php echo $assessmentlang;?>'><?php eT("Heading");?>:</label>
                        <input type='text' name='name_<?php echo $assessmentlang;?>' id='name_<?php echo $assessmentlang;?>' size='80' value='<?php echo $heading;?>'/></li>
                        <li><label for='assessmentmessage_<?php echo $assessmentlang;?>'><?php eT("Message");?>:</label>
                        <textarea name='assessmentmessage_<?php echo $assessmentlang;?>' id='assessmentmessage_<?php echo $assessmentlang;?>' rows='10' cols='80'><?php echo $message;?></textarea>
                        <?php echo getEditor("assessment-text","assessmentmessage_$assessmentlang", "[".gT("Message:", "js")."]",$surveyid,$gid,null,$action); ?>
                        </li>
                        <li style="text-align:center;">
                            <input type='submit' class="hidden" value='<?php eT("Save");?>'/>
                        </li>
                    </ul>
                </div>
            <?php } ?>
        </div>
        
        <!-- action buttons -->
        <div>
            <?php if ($action == "assessmentedit") echo "&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' value='".gT("Cancel")."' onclick=\"document.assessmentsform.action.value='assessments'\" />\n ";?>
            <input type='hidden' name='sid' value='<?php echo $surveyid;?>' />
            <input type='hidden' name='action' value='<?php echo $actionvalue;?>' />
            <input type='hidden' name='id' value='<?php echo $editId;?>' />
        </div>
    </form>
<?php endif; ?>
</div></div></div><!-- opened in controller -->