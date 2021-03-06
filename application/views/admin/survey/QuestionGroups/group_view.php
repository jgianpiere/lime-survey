<div class="side-body">
	<h3><?php eT('Group summary'); ?></h3>
	<div class="row">
		<div class="col-lg-12 content-right">

			<table id='groupdetails' class="table table-bordered">
			<tr ><td ><strong>
			            <?php eT("Title"); ?>:</strong></td>
			    <td>
			        <?php echo $grow['group_name']; ?> (<?php echo $grow['gid']; ?>)</td>
			</tr>
			<tr>
			    <td><strong>
			        <?php eT("Description:"); ?></strong>
			    </td>
			    <td>
			        <?php if (trim($grow['description'])!='') {
			                templatereplace($grow['description']);
			                echo LimeExpressionManager::GetLastPrettyPrintExpression();
			        } ?>
			    </td>
			</tr>
			<?php if (trim($grow['grelevance'])!='') { ?>
			    <tr>
			        <td><strong>
			            <?php eT("Relevance:"); ?></strong>
			        </td>
			        <td>
			            <?php
			                templatereplace('{' . $grow['grelevance'] . '}');
			                echo LimeExpressionManager::GetLastPrettyPrintExpression();
			            ?>
			        </td>
			    </tr>
			    <?php } ?>
			<?php
			    if (trim($grow['randomization_group'])!='')
			    {?>
			    <tr>
			        <td><?php eT("Randomization group:"); ?></td><td><?php echo $grow['randomization_group'];?></td>
			    </tr>
			    <?php
			    }
			    // TMSW Condition->Relevance:  Use relevance equation or different EM query to show dependencies
			    if (!is_null($condarray))
			    { ?>
			    <tr><td><strong>
			                <?php eT("Questions with conditions to this group"); ?>:</strong></td>
			        <td>
			            <?php foreach ($condarray[$gid] as $depgid => $deprow)
			                {
			                    foreach ($deprow['conditions'] as $depqid => $depcid)
			                    {
			
			                        $listcid=implode("-",$depcid);?>
			                    <a href='<?php echo $this->createUrl("admin/conditions/sa/index/subaction/conditions/surveyid/$surveyid/gid/$depgid/qid/$depqid",array('markcid'=>implode("-",$depcid))); ?>'>[QID: <?php echo $depqid; ?>]</a>
			                    <?php }
			            } ?>
			        </td></tr>
			    <?php } ?>


</table>





			
		</div>
	</div>
</div>