<?php
class tutAtHelp_ControllerPublic_Help extends XFCP_tutAtHelp_ControllerPublic_Help
{
	public function actionTutorials()
	{
		$visitor = XenForo_Visitor::getInstance();
		$act = $this->_input->filterSingle('action', XenForo_Input::STRING);
		$sac = $this->_input->filterSingle('subaction', XenForo_Input::STRING);
		$ssa = $this->_input->filterSingle('subsubaction', XenForo_Input::STRING);
		$tut = $this->_input->filterSingle('tutorial', XenForo_Input::INT);
		$pag = $this->_input->filterSingle('page', XenForo_Input::INT);
		//tutAtHelp_sharedStatic::dropTableDB();
		//tutAtHelp_sharedStatic::createTableDB();
		$usageDrafts          = tutAtHelp_sharedStatic::usageDrafts($visitor['user_id']);
		$usageImageMegs       = tutAtHelp_sharedStatic::usageImageMegs($visitor['user_id']);
		$usagePendingApproval = tutAtHelp_sharedStatic::usagePendingApproval($visitor['user_id']);
		$usageSteps           = tutAtHelp_sharedStatic::usageSteps($visitor['user_id'],$tut);
		
		$xfopt = XenForo_Application::get('options');

		$marginTopTitle       = $xfopt->tutorialfixmargintoptitle;
		
		$maxDrafts            = $xfopt->maxtutorialdrafts;
		$maxImageMegs         = $xfopt->maximagespace;
		$maxPendingApproval   = $xfopt->maxtutorialpending;
		$maxSteps             = $xfopt->maxtutorialsteps;

		$canAddDraft = (($usageDrafts<$maxDrafts)&&($usagePendingApproval<$maxPendingApproval));
		$canAddImage = ($usageImageMegs<$maxImageMegs);
		$canAddStep  = ($usageSteps<$maxSteps);

		$canSend = tutAtHelp_sharedStatic::isEverythingFilled($tut);
		
		$permission = 0;
		if(true)
			$permission = 1; //see
		if($visitor->hasPermission('tutathelppermgroup','tutorialcreate'))
			$permission = 2; //contribute
		if($visitor->hasPermission('tutathelppermgroup','tutorialmoderate'))
			$permission = 3; //moderate
		if($act=='displayimage'){
			$mimeArr = array(IMAGETYPE_GIF => 'image/gif',
							 IMAGETYPE_JPEG=> 'image/jpeg',
							 IMAGETYPE_PNG => 'image/png');
			$mimetranslator = tutAtHelp_sharedStatic::getSupportedImageFormats()['fromsql'];
			$imgid = $this->_input->filterSingle('image', XenForo_Input::INT);
			$notfound = $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,'styles/default/xenforo/icons/missing-image.png');
			if(!$imgid || $imgid<=0) return $notfound;
			$imgarr = tutAtHelp_sharedStatic::getImageData($imgid);
			if(!$imgarr) return $notfound;
			$fname = $imgarr['fname'];
			$imgbytes = $imgarr['imageBytes'];
			$fsize = strlen($imgbytes);
			$mime = $mimetranslator[$imgarr['imageFormat']];
			$filets = $imgarr['filetimestamp'];
			$headers = @getallheaders();
			if(isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == $filets)) {
				header('Last-Modified: '.gmdate('D, d M Y H:i:s', $filets).' GMT', true, 304);
				header('Connection: close');
				die();
			}else{
				header('Last-Modified: '.gmdate('D, d M Y H:i:s', $filets).' GMT', true, 200);
				header('Content-Type: '.$mime);
				header('Content-Disposition: inline; filename="'.$fname.'"');
				header('Content-Length: ' . $fsize);
				header('Connection: close');
				die($imgbytes);
			}
		}
		$validActions=array('edit','moderate','newtut');
		$viewParams = array();
		if(in_array($act,$validActions)){
			if($permission<2){
				return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
			}
			if($permission<3){
				if($act=='moderate')
					return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
			}
			$t=tutAtHelp_sharedStatic::getTutorialInfo($tut);
			if($tut>0 && !is_array($t)){
				return $this->responseError('The requested resource does not exist.');
			}
			if($permission<3){
				if(is_array($t) && !$t['isdraft'])
					return $this->responseError('This tutorial has been submitted.');
			}
			if($permission<3){
				if(is_array($t) && $t['author']!=$visitor['user_id'])
					return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
			}
			if($permission==3){
				if(is_array($t) && $t['approved']>0 && $sac!='unaccepttut')
					return $this->responseError('This tutorial is publicly visible. Put it into moderation queue again before editing it.');
			}
			unset($t);
			$viewParams['title']='';
			if($act=='newtut' && $canAddDraft){
				tutAtHelp_sharedStatic::newTutorial($visitor['user_id']);
				return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
							XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit')));
			}
			else if($act=='edit'){
				if($tut<=0){
					$drafts = tutAtHelp_sharedStatic::getDraftTutorialList($visitor['user_id']);
					$html='';
					$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Drafts</div>';
					$html.='<p>';
					$html.='<div style="text-align: left; clear: right;">';
					$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials').'">
								Back
							</a>';
					$html.='</div>';
					$html.='<div style="text-align: right; clear: left;">';
					if($canAddDraft){
						$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'newtut')).'">
									Add new tutorial
								</a>';
					}
					else{
						$html.='<span>Tutorial creation limit reached.</span>';
					}
					$html.='</div>';
					$html.='</p>';
					foreach($drafts as $draft){
						$html.='<div class="primaryContent">';
						$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('tutorial'=>$draft['tutid'],'page'=>1)).'">';
						$html.='<span title="Preview" style="display: block; float: left; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/xenforo-ui-sprite.png\') no-repeat -144px -16px;"></span>';
						$html.='</a>';
						$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$draft['tutid'])).'">';
						$html.='<span title="Edit" style="display: block; float: left; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/permissions/edit.png\') no-repeat center center;"></span>';
						$html.='Tutorial #'.$draft['tutid'];
						$html.='</a>';
						$html.=': ';
						$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$draft['tutid'],'subaction'=>'renametut')).'">';
						$tutnm = $draft['tutnm'];
						$html.=($tutnm)?htmlspecialchars($tutnm):'<i>unamed</i>';
						$html.='</a>';
						$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$draft['tutid'],'subaction'=>'deletetut')).'">';
						$html.='<span title="Delete" style="display: block; float: right; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/permissions/deny.png\') no-repeat center center;"></span>';
						$html.='</a>';
						$html.='<span style="display: block; float: right; width: 16px; height: 16px;"></span>';
						$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$draft['tutid'],'subaction'=>'submittut')).'">';
						$html.='<span title="Submit" style="display: block; float: right; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/permissions/allow.png\') no-repeat center center;"></span>';
						$html.='</a>';
						$html.='</div>';
					}
					$viewParams['html']=$html;
				}
				else if($sac=='renametut'){
					$t=tutAtHelp_sharedStatic::getTutorialInfo($tut);
					if($ssa=='do'){
						$t['tutnm']=$this->_input->filterSingle('newname', XenForo_Input::STRING);
						tutAtHelp_sharedStatic::updateTutorialInfo($visitor['user_id'],$t);
						return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
									XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit')));
					}
					$html='';
					$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Drafts &gt; Rename tutorial #'.$tut.'</div>';
					$html.='<p>';
					$html.='<div style="text-align: left;">';
					$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit')).'">
								Back
							</a>';
					$html.='</div>';
					$html.='</p>';
					$html.='<form method="post" action="'.XenForo_Link::buildPublicLink('help/tutorials').'">';
					$html.='<input class="textCtrl" maxlength="200" type="text" name="newname" placeholder="untitled" value="'.htmlspecialchars($t['tutnm']).'">';
					$html.='<input type="hidden" name="tutorial" value="'.$t['tutid'].'">';
					$html.='<input type="hidden" name="action" value="edit">';
					$html.='<input type="hidden" name="subaction" value="renametut">';
					$html.='<input type="hidden" name="subsubaction" value="do">';
					$html.='<input type="hidden" name="_xfToken" value="'.$visitor['csrf_token_page'].'">';
					$html.='<input type="hidden" name="type" value="post" />';
					$html.='<input class="button" type="submit" name="submit" value="Rename">';
					$html.='</form>';
					$viewParams['html']=$html;
				}
				else if($sac=='deletetut'){
					if($ssa=='conffirm'){
						tutAtHelp_sharedStatic::deleteTutorial($tut);
						return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
									XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit')));
					}
					$html='';
					$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Drafts &gt; Delete tutorial #'.$tut.' ?</div>';
					$html.='<p>';
					$html.='</p>';
					$html.='<p>';
					$html.='Are you sure you want to delete this tutorial?
					<p>
					<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','subaction'=>'deletetut','tutorial'=>$tut,'subsubaction'=>'conffirm')).'">Yes</a>
					<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit')).'">No</a>
					</p>';
					$html.='</p>';
					$viewParams['html']=$html;
				}
				else if($sac=='submittut'){
					if($ssa=='conffirm'){
						if(!$canSend){
							return $this->responseError('Not all fields are filled.');
						}
						tutAtHelp_sharedStatic::submitTutorial($visitor['user_id'],0,$tut);
						return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
									XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit')));
					}
					$html='';
					$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Drafts &gt; Submit tutorial #'.$tut.' ?</div>';
					$html.='<br />
					<p>Are you sure you want to submit this tutorial for moderation?</p>
					<p>After submitting this tutorial, you won\'t be able to edit it again.<br />
					If your tutorial gets rejected, it will be deleted forever.</p>
					<br />
					<p>
					<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','subaction'=>'submittut','tutorial'=>$tut,'subsubaction'=>'conffirm')).'">Yes</a>
					<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit')).'">No</a>
					</p>';
					$viewParams['html']=$html;
				}
				else{
					if($sac=='newstep' && $canAddStep){
						tutAtHelp_sharedStatic::newStep($visitor['user_id'],$tut);
						return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
									XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut)));
					}
					else if($sac=='delstep'){
						tutAtHelp_sharedStatic::deleteStep($tut,$pag);
						return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
									XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut)));
					}
					else if($sac=='insertimage' && $canAddImage){
						$redir = $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
										XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut)));
						$file=XenForo_Upload::getUploadedFile('upload');
						if(($file==null)||(!($file->isValid()&&$file->isImage()))){
							return $redir;
						}
						$filecont = @file_get_contents($file->getTempFile());
						$filename = $file->getFileName();
						$imageid = tutAtHelp_sharedStatic::putImage($visitor['user_id'],$tut,$filecont,$filename);
						if(!$imageid){
							return $redir;
						}
						tutAtHelp_sharedStatic::assignImageToStep($visitor['user_id'],$tut,$pag,$imageid);
						return $redir;
					}
					else if($sac=='removeimage'){
						$redir = $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
														 XenForo_Link::buildPublicLink('help/tutorials',
																					   '',
																					   array('action'=>'edit',
																							 'tutorial'=>$tut
								)));
						tutAtHelp_sharedStatic::unassignImageFromStepDeleting($visitor['user_id'],$tut,$pag);
						return $redir;
					}
					else if($pag>0){
						$step=tutAtHelp_sharedStatic::getStep($tut,$pag);
						if($step==null){
							return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
										XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut)));
						}
						if($sac=='updstep'){
							$qi=$this->_input->filterSingle('qi', XenForo_Input::STRING);
							$di=$this->_input->filterSingle('di', XenForo_Input::STRING);
							$step['quickInstruction']=$qi;
							$step['detailedInstruction']=$di;
							tutAtHelp_sharedStatic::updateStepInfo($visitor['user_id'],$step);
							return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
										XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut)));
						}
						if($sac=='mvstepup'){
							$pag-=1;
							$sac='mvstepdown';
						}
						if($sac=='mvstepdown'){
							if(!($pag<=0 || tutAtHelp_sharedStatic::getStepCount($tut)<=$pag))
							tutAtHelp_sharedStatic::swapStepPos($visitor['user_id'],$tut,$pag,$pag+1);
							return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
										XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut)));
						}
						$qi=htmlspecialchars($step['quickInstruction']);
						$di=htmlspecialchars($step['detailedInstruction']);
						$html='';
						$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Drafts &gt; Edit tutorial #'.$tut.' &gt; Edit step #'.$pag.'</div>';
						$html.='<p>';
						$html.='<div style="text-align: left;">';
						$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut)).'">
									Back
								</a>';
						$html.='</div>';
						$html.='</p>';
						$html.='<form method="post" action="'.XenForo_Link::buildPublicLink('help/tutorials').'">';
						$html.='<input type="hidden" name="action" value="edit">';
						$html.='<input type="hidden" name="subaction" value="updstep">';
						$html.='<input type="hidden" name="tutorial" value="'.$tut.'">';
						$html.='<input type="hidden" name="page" value="'.$pag.'">';
						$html.='<input type="hidden" name="_xfToken" value="'.$visitor['csrf_token_page'].'">';
						$html.='<input type="hidden" name="type" value="post" />';
						$html.='Quick instruction:';
						$html.='<br />';
						$html.='<input name="qi" class="textCtrl" style="width: 100%;" maxlength="200" value="'.$qi.'">';
						$html.='<br />';
						$html.='<br />';
						$html.='Detailed instruction:';
						$html.='<br />';
						$html.='<textarea name="di" class="textCtrl" style="width: 100%; resize: none;" rows="8" maxlength="1000">';
						$html.=$di;
						$html.='</textarea>';
						$html.='<br />';
						$html.='<br />';
						$html.='<input class="button" type="submit" name="submit" value="Update">';
						$html.='</form>';
						$viewParams['html']=$html;
					}
					else{
						$html='';
						$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Drafts &gt; Edit tutorial #'.$tut.'</div>';
						$html.='<p>';
						$html.='<div style="text-align: left;">';
						$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit')).'">
									Back
								</a>';
						$html.='</div>';
						$html.='<div style="text-align: right;">';
						if($canAddStep){
							$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut,'subaction'=>'newstep')).'">
										Add step
									</a>';
						}
						else{
							$html.='<span>Step creation limit reached.</span>';
						}
						$html.='</div>';
						$html.='<p>';
						$steps=tutAtHelp_sharedStatic::getStepList($tut);
						$laststep=tutAtHelp_sharedStatic::getStepCount($tut);
						foreach($steps as $step){
							$qi=htmlspecialchars($step['quickInstruction']);
							$di=htmlspecialchars($step['detailedInstruction']);
							$html.='<div class="primaryContent">';
							if($step['step']!=1){
								$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut,'page'=>$step['step'],'subaction'=>'mvstepup')).'">';
							}
							$html.='<span title="Move up"
											style="display: block;
											float: left;
											width: 16px;
											height: 16px;
											background: transparent url(\'styles/default/xenforo/icons/redirect.png\') no-repeat center center;
											-webkit-transform: rotate(-90deg);
											-moz-transform: rotate(-90deg);
											-ms-transform: rotate(-90deg);
											-o-transform: rotate(-90deg);
											transform: rotate(-90deg);'.(($step['step']==1)?'
											opacity: 0.4;
											filter: alpha(opacity=40);':'').'"></span>';
							if($step['step']!=1){
								$html.='</a>';
							}
							if($step['step']!=$laststep){
								$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut,'page'=>$step['step'],'subaction'=>'mvstepdown')).'">';
							}
							$html.='<span title="Move down"
											style="display: block;
											float: left;
											width: 16px;
											height: 16px;
											background: transparent url(\'styles/default/xenforo/icons/redirect.png\') no-repeat center center;
											-webkit-transform: rotate(90deg);
											-moz-transform: rotate(90deg);
											-ms-transform: rotate(90deg);
											-o-transform: rotate(90deg);
											transform: rotate(90deg);'.(($step['step']==$laststep)?'
											opacity: 0.4;
											filter: alpha(opacity=40);':'').'"></span>';
							if($step['step']!=$laststep){
								$html.='</a>';
							}
							$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut,'page'=>$step['step'])).'">';
							$html.='<span title="Edit" style="display: block; float: left; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/permissions/edit.png\') no-repeat center center;"></span>';
							$html.='</a>';
							$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut,'page'=>$step['step'])).'">';
							$html.='Step #'.$step['step'];
							$html.='</a>';
							$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut,'page'=>$step['step'],'subaction'=>'delstep')).'">';
							$html.='<span title="Delete" style="display: block; float: right; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/permissions/deny.png\') no-repeat center center;"></span>';
							$html.='</a>';
							$html.='<br />';
							$html.='Quick instruction: '.(($qi||$qi==='0')?$qi:'<i>empty</i>');
							$html.='<br />';
							$html.='<br />';
							$html.='Image: <br />';
							if($step['imageDisp']){
								//some image
								$iid = $step['imageDisp'];
								$imglnk = XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'displayimage','image'=>$iid));
								$html.='<div>';
								$html.='<a class="LbTrigger"
										   data-href="index.php?misc/lightbox"
										   target="_blank"
										   href="'.$imglnk.'">';
								$html.='<img class="LbImage"
											 src="'.$imglnk.'"
											 style="display: block;
													max-width: 100px;
													max-height: 100px;">';
								$html.='</a>';
								$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit','tutorial'=>$tut,'page'=>$step['step'],'subaction'=>'removeimage')).'">Remove image</a>';
								$html.='</div>';
							}else if($canAddImage){
								//no image
								$html.='<form method="post" enctype="multipart/form-data" action="'.XenForo_Link::buildPublicLink('help/tutorials').'">';
								$html.='<input type="hidden" name="action" value="edit">';
								$html.='<input type="hidden" name="subaction" value="insertimage">';
								$html.='<input type="hidden" name="tutorial" value="'.$tut.'">';
								$html.='<input type="hidden" name="page" value="'.$step['step'].'">';
								$html.='<input type="hidden" name="_xfToken" value="'.$visitor['csrf_token_page'].'">';
								$html.='<input type="hidden" name="type" value="post" />';
								$html.='<input type="file" name="upload">';
								$html.='<br />';
								$html.='<input class="button" type="submit" value="Upload Image" name="submit">';
								$html.='</form>';
							}else{
								$html.='<span>Image storage limit exceeded: '.@number_format($usageImageMegs,2).'MB of '.$maxImageMegs.'MB used.</span>';
							}
							$html.='</div>';
						}
						$viewParams['html']=$html;
					}
				}
			}
			
			//////////////////////////////////////////////////////////////////////////////////////////////////
			//////////////////////////////////////////////////////////////////////////////////////////////////
			//////////////////////////////////////////////////////////////////////////////////////////////////
			//////////////////////////////////////////////////////////////////////////////////////////////////
			//////////////////////////////////////////////////////////////////////////////////////////////////
			//////////////////////////////////////////////////////////////////////////////////////////////////
			//////////////////////////////////////////////////////////////////////////////////////////////////
			
			else if($act=='moderate'){
				if($tut<=0){
					$userModel = $this->getModelFromCache('XenForo_Model_User');
					$drafts = tutAtHelp_sharedStatic::getModerationTutorialList();
					$html='';
					$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Tutorial moderation queue</div>';
					$html.='<p>';
					$html.='<div style="text-align: left;">';
					$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials').'">
								Back
							</a>';
					$html.='</div>';
					$html.='</p>';
					foreach($drafts as $draft){
						$html.='<div class="primaryContent">';
						$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('tutorial'=>$draft['tutid'],'page'=>1)).'">';
						$html.='<span title="Preview" style="display: block; float: left; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/xenforo-ui-sprite.png\') no-repeat -144px -16px;"></span>';
						$html.='</a>';
						$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$draft['tutid'])).'">';
						$html.='<span title="Edit" style="display: block; float: left; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/permissions/edit.png\') no-repeat center center;"></span>';
						$html.='Tutorial #'.$draft['tutid'];
						$html.='</a>';
						$html.=': ';
						$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$draft['tutid'],'subaction'=>'renametut')).'">';
						$tutnm = $draft['tutnm'];
						$html.=($tutnm)?htmlspecialchars($tutnm):'<i>unamed</i>';
						$html.='</a>';
						$html.='<br />';
						if($userModel->getUserById($draft['author']))
						$html.='Submitted by: '.XenForo_Template_Helper_Core::helperUserName($userModel->getUserById($draft['author']));
						$html.='<br />';
						if($userModel->getUserById($draft['lasteditor']))
						$html.='Last modification by: '.XenForo_Template_Helper_Core::helperUserName($userModel->getUserById($draft['lasteditor']));
						$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$draft['tutid'],'subaction'=>'deletetut')).'">';
						$html.='<span title="Reject" style="display: block; float: right; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/permissions/deny.png\') no-repeat center center;"></span>';
						$html.='</a>';
						$html.='<span style="display: block; float: right; width: 16px; height: 16px;"></span>';
						$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$draft['tutid'],'subaction'=>'accepttut')).'">';
						$html.='<span title="Accept" style="display: block; float: right; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/permissions/allow.png\') no-repeat center center;"></span>';
						$html.='</a>';
						$html.='</div>';
					}
					$viewParams['html']=$html;
				}
				else if($sac=='renametut'){
					$t=tutAtHelp_sharedStatic::getTutorialInfo($tut);
					if($ssa=='do'){
						$t['tutnm']=$this->_input->filterSingle('newname', XenForo_Input::STRING);
						tutAtHelp_sharedStatic::updateTutorialInfo($visitor['user_id'],$t);
						return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
									XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate')));
					}
					$html='';
					$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Tutorial moderation queue &gt; Rename tutorial #'.$tut.'</div>';
					$html.='<p>';
					$html.='<div style="text-align: left;">';
					$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate')).'">
								Back
							</a>';
					$html.='</div>';
					$html.='<p>';
					$html.='<form method="post" action="'.XenForo_Link::buildPublicLink('help/tutorials').'">';
					$html.='<input class="textCtrl" maxlength="200" type="text" name="newname" placeholder="untitled" value="'.htmlspecialchars($t['tutnm']).'">';
					$html.='<input type="hidden" name="tutorial" value="'.$t['tutid'].'">';
					$html.='<input type="hidden" name="action" value="moderate">';
					$html.='<input type="hidden" name="subaction" value="renametut">';
					$html.='<input type="hidden" name="subsubaction" value="do">';
					$html.='<input type="hidden" name="_xfToken" value="'.$visitor['csrf_token_page'].'">';
					$html.='<input type="hidden" name="type" value="post" />';
					$html.='<input class="button" type="submit" name="submit" value="Rename">';
					$html.='</form>';
					$viewParams['html']=$html;
				}
				else if($sac=='deletetut'){
					if($ssa=='conffirm'){
						tutAtHelp_sharedStatic::deleteTutorial($tut);
						return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
									XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate')));
					}
					$html='';
					$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Tutorial moderation queue &gt; Reject tutorial #'.$tut.' ?</div>';
					$html.='
					<p>
					</p>
					<p>
					Are you sure you want to reject this tutorial?
					</p>
					<p>
					It will be deleted if rejected.
					</p>
					<br />
					<p>
					<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','subaction'=>'deletetut','tutorial'=>$tut,'subsubaction'=>'conffirm')).'">Yes</a>
					<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate')).'">No</a>
					</p>';
					$viewParams['html']=$html;
				}
				else if($sac=='unaccepttut'){
					if($ssa=='conffirm'){
						tutAtHelp_sharedStatic::unpublishTutorial($visitor['user_id'],$tut);
						return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
									XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate')));
					}
					$html='';
					$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Tutorial moderation queue &gt; Unaccept tutorial #'.$tut.' ?</div>';
					$html.='<br />
					<p>Are you sure you want to unaccept this tutorial?</p>
					<p>After unaccepting this tutorial, it will return to moderation queue.</p>
					<br />
					<p>
					<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','subaction'=>'unaccepttut','tutorial'=>$tut,'subsubaction'=>'conffirm')).'">Yes</a>
					<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials').'">No</a>
					</p>';
					$viewParams['html']=$html;
				}
				else if($sac=='accepttut'){
					if($ssa=='conffirm'){
						if(!$canSend){
							return $this->responseError('Not all fields are filled.');
						}
						tutAtHelp_sharedStatic::publishTutorial($visitor['user_id'],$tut);
						return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
									XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate')));
					}
					$html='';
					$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Tutorial moderation queue &gt; Accept tutorial #'.$tut.' ?</div>';
					$html.='<br />
					<p>Are you sure you want to accept this tutorial?</p>
					<p>After accepting this tutorial, it will be publicly visible.</p>
					<br />
					<p>
					<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','subaction'=>'accepttut','tutorial'=>$tut,'subsubaction'=>'conffirm')).'">Yes</a>
					<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate')).'">No</a>
					</p>';
					$viewParams['html']=$html;
				}
				else{
					if($sac=='newstep'){
						tutAtHelp_sharedStatic::newStep($visitor['user_id'],$tut);
						return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
									XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut)));
					}
					else if($sac=='delstep'){
						tutAtHelp_sharedStatic::deleteStep($tut,$pag);
						return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
									XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut)));
					}
					else if($sac=='insertimage'){
						$redir = $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
										XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut)));
						$file=XenForo_Upload::getUploadedFile('upload');
						if(($file==null)||(!($file->isValid()&&$file->isImage()))){
							return $redir;
						}
						$filecont = @file_get_contents($file->getTempFile());
						$filename = $file->getFileName();
						$imageid = tutAtHelp_sharedStatic::putImage($visitor['user_id'],$tut,$filecont,$filename);
						if(!$imageid){
							return $redir;
						}
						tutAtHelp_sharedStatic::assignImageToStep($visitor['user_id'],$tut,$pag,$imageid);
						return $redir;
					}
					else if($sac=='removeimage'){
						$redir = $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
														 XenForo_Link::buildPublicLink('help/tutorials',
																					   '',
																					   array('action'=>'moderate',
																							 'tutorial'=>$tut
								)));
						tutAtHelp_sharedStatic::unassignImageFromStepDeleting($visitor['user_id'],$tut,$pag);
						return $redir;
					}
					else if($pag>0){
						$step=tutAtHelp_sharedStatic::getStep($tut,$pag);
						if($step==null){
							return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
										XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut)));
						}
						if($sac=='updstep'){
							$qi=$this->_input->filterSingle('qi', XenForo_Input::STRING);
							$di=$this->_input->filterSingle('di', XenForo_Input::STRING);
							$step['quickInstruction']=$qi;
							$step['detailedInstruction']=$di;
							tutAtHelp_sharedStatic::updateStepInfo($visitor['user_id'],$step);
							return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
										XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut)));
						}
						if($sac=='mvstepup'){
							$pag-=1;
							$sac='mvstepdown';
						}
						if($sac=='mvstepdown'){
							if(!($pag<=0 || tutAtHelp_sharedStatic::getStepCount($tut)<=$pag))
							tutAtHelp_sharedStatic::swapStepPos($visitor['user_id'],$tut,$pag,$pag+1);
							return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
										XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut)));
						}
						$qi=htmlspecialchars($step['quickInstruction']);
						$di=htmlspecialchars($step['detailedInstruction']);
						$html='';
						$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Tutorial moderation queue &gt; Edit tutorial #'.$tut.' &gt; Edit step #'.$pag.'</div>';
						$html.='<p>';
						$html.='<div style="text-align: left;">';
						$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut)).'">
									Back
								</a>';
						$html.='</div>';
						$html.='</p>';
						$html.='<form method="post" action="'.XenForo_Link::buildPublicLink('help/tutorials').'">';
						$html.='<input type="hidden" name="action" value="moderate">';
						$html.='<input type="hidden" name="subaction" value="updstep">';
						$html.='<input type="hidden" name="tutorial" value="'.$tut.'">';
						$html.='<input type="hidden" name="page" value="'.$pag.'">';
						$html.='<input type="hidden" name="_xfToken" value="'.$visitor['csrf_token_page'].'">';
						$html.='<input type="hidden" name="type" value="post" />';
						$html.='Quick instruction:';
						$html.='<br />';
						$html.='<input name="qi" class="textCtrl" style="width: 100%;" maxlength="200" value="'.$qi.'">';
						$html.='<br />';
						$html.='<br />';
						$html.='Detailed instruction:';
						$html.='<br />';
						$html.='<textarea name="di" class="textCtrl" style="width: 100%; resize: none;" rows="8" maxlength="1000">';
						$html.=$di;
						$html.='</textarea>';
						$html.='<br />';
						$html.='<br />';
						$html.='<input class="button" type="submit" name="submit" value="Update">';
						$html.='</form>';
						$viewParams['html']=$html;
					}
					else{
						$html='';
						$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Tutorial moderation queue &gt; Edit tutorial #'.$tut.'</div>';
						$html.='<p>';
						$html.='<div style="text-align: left;">';
						$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate')).'">
									Back
								</a>';
						$html.='</div>';
						$html.='<div style="text-align: right;">';
						$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut,'subaction'=>'newstep')).'">
									Add step
								</a>';
						$html.='</div>';
						$html.='</p>';
						$steps=tutAtHelp_sharedStatic::getStepList($tut);
						$laststep=tutAtHelp_sharedStatic::getStepCount($tut);
						foreach($steps as $step){
							$qi=htmlspecialchars($step['quickInstruction']);
							$di=htmlspecialchars($step['detailedInstruction']);
							$html.='<div class="primaryContent">';
							if($step['step']!=1){
								$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut,'page'=>$step['step'],'subaction'=>'mvstepup')).'">';
							}
							$html.='<span title="Move up"
											style="display: block;
											float: left;
											width: 16px;
											height: 16px;
											background: transparent url(\'styles/default/xenforo/icons/redirect.png\') no-repeat center center;
											-webkit-transform: rotate(-90deg);
											-moz-transform: rotate(-90deg);
											-ms-transform: rotate(-90deg);
											-o-transform: rotate(-90deg);
											transform: rotate(-90deg);'.(($step['step']==1)?'
											opacity: 0.4;
											filter: alpha(opacity=40);':'').'"></span>';
							if($step['step']!=1){
								$html.='</a>';
							}
							if($step['step']!=$laststep){
								$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut,'page'=>$step['step'],'subaction'=>'mvstepdown')).'">';
							}
							$html.='<span title="Move down"
											style="display: block;
											float: left;
											width: 16px;
											height: 16px;
											background: transparent url(\'styles/default/xenforo/icons/redirect.png\') no-repeat center center;
											-webkit-transform: rotate(90deg);
											-moz-transform: rotate(90deg);
											-ms-transform: rotate(90deg);
											-o-transform: rotate(90deg);
											transform: rotate(90deg);'.(($step['step']==$laststep)?'
											opacity: 0.4;
											filter: alpha(opacity=40);':'').'"></span>';
							if($step['step']!=$laststep){
								$html.='</a>';
							}
							$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut,'page'=>$step['step'])).'">';
							$html.='<span title="Edit" style="display: block; float: left; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/permissions/edit.png\') no-repeat center center;"></span>';
							$html.='</a>';
							$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut,'page'=>$step['step'])).'">';
							$html.='Step #'.$step['step'];
							$html.='</a>';
							$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut,'page'=>$step['step'],'subaction'=>'delstep')).'">';
							$html.='<span title="Delete" style="display: block; float: right; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/permissions/deny.png\') no-repeat center center;"></span>';
							$html.='</a>';
							$html.='<br />';
							$html.='<b>Quick instruction:</b> '.(($qi||$qi==='0')?$qi:'<i>empty</i>');
							$html.='<br />';
							$html.='<br />';
							$html.='Image: <br />';
							if($step['imageDisp']){
								//some image
								$iid = $step['imageDisp'];
								$imglnk = XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'displayimage','image'=>$iid));
								$html.='<div>';
								$html.='<a class="LbTrigger"
										   data-href="index.php?misc/lightbox"
										   target="_blank"
										   href="'.$imglnk.'">';
								$html.='<img class="LbImage"
											 src="'.$imglnk.'"
											 style="display: block;
													max-width: 100px;
													max-height: 100px;">';
								$html.='</a>';
								$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tut,'page'=>$step['step'],'subaction'=>'removeimage')).'">Remove image</a>';
								$html.='</div>';
							}else{
								//no image
								$html.='<form method="post" enctype="multipart/form-data" action="'.XenForo_Link::buildPublicLink('help/tutorials').'">';
								$html.='<input type="hidden" name="action" value="moderate">';
								$html.='<input type="hidden" name="subaction" value="insertimage">';
								$html.='<input type="hidden" name="tutorial" value="'.$tut.'">';
								$html.='<input type="hidden" name="page" value="'.$step['step'].'">';
								$html.='<input type="hidden" name="_xfToken" value="'.$visitor['csrf_token_page'].'">';
								$html.='<input type="hidden" name="type" value="post" />';
								$html.='<input type="file" name="upload">';
								$html.='<br />';
								$html.='<input class="button" type="submit" value="Upload Image" name="submit">';
								$html.='</form>';
							}
							$html.='<br />';
							$html.='<b>Detailed instruction:</b> '.(($di||$di==='0')?$di:'<i>empty</i>');
							
							$html.='</div>';
						}
						$viewParams['html']=$html;
					}
				}
			}
		}
		////////////////////////////////////////
		////////////////////////////////////////
		////////////////////////////////////////
		else if($tut<=0){
			$userModel = $this->getModelFromCache('XenForo_Model_User');
			$tutorials = tutAtHelp_sharedStatic::getPublishedTutorialList();
			$html='';
			$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;">Published tutorials</div>';
			if($permission>=2){
				$html.='<div style="text-align: right;">';
				if($permission>=3){
					$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate')).'">
								Moderate
							</a>';
				}
				$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'edit')).'">
							Drafts
						</a>';
				$html.='</div>';
			}
			$html.='<div>';
			foreach($tutorials as $tutorial){
				$html.='<div class="primaryContent">';
				$html.='<a style="font-size: 1.3em;" href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('tutorial'=>$tutorial['tutid'],'page'=>1)).'">';
				$html.=htmlspecialchars($tutorial['tutnm']);
				$html.='</a>';
				$html.='<br />';
				if($userModel->getUserById($tutorial['author'])){
					$html.='Submitted by: '.XenForo_Template_Helper_Core::helperUserName($userModel->getUserById($tutorial['author']));
					$html.=', '.XenForo_Template_Helper_Core::dateTime($tutorial['approvetimestamp']);
				}
				$html.='<br />';
				if($userModel->getUserById($tutorial['lasteditor'])){
					$html.='Last edited by: '.XenForo_Template_Helper_Core::helperUserName($userModel->getUserById($tutorial['lasteditor']));
					$html.=', '.XenForo_Template_Helper_Core::dateTime($tutorial['publishtimestamp']);
				}
				if($permission>=3){
					$html.='<a href="'.XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'moderate','tutorial'=>$tutorial['tutid'],'subaction'=>'unaccepttut')).'">';
					$html.='<span title="Moderate again" style="display: block; float: right; width: 16px; height: 16px; background: transparent url(\'styles/default/xenforo/permissions/revoke.png\') no-repeat center center;"></span>';
					$html.='</a>';
				}
				$html.='</div>';
			}
			$html.='</div>';
			$viewParams['html']=$html;
			//die(print_r($tutorials,true));
		}
		else if($tut>0){
			$userModel = $this->getModelFromCache('XenForo_Model_User');
			$tutorial = tutAtHelp_sharedStatic::getTutorialInfo($tut);
			if(!is_array($tutorial)) return $this->responseError('The requested resource does not exist.');
			$stepcount = intval(tutAtHelp_sharedStatic::getStepCount($tut));
			if($pag<1) $pag = 1;
			if($pag>$stepcount) $pag = $stepcount;
			$prevp = $pag-1;
			$nextp = $pag+1;
			if($prevp<1) $prevp = 1;
			if($nextp>$stepcount) $nextp = @max($stepcount,1);
			$step = tutAtHelp_sharedStatic::getStep($tut,$pag);
			$html='';
			$html.='<div class="heading h1" style="margin-top: '.$marginTopTitle.'px;'.(($tutorial['approved']>0)?'':' margin-bottom: 0px;').'">'.htmlspecialchars($tutorial['tutnm']).'&nbsp;</div>';
			if($tutorial['approved']>0){
				if($userModel->getUserById($tutorial['author'])){
					$html.='Submitted by: '.XenForo_Template_Helper_Core::helperUserName($userModel->getUserById($tutorial['author']));
					$html.=', '.XenForo_Template_Helper_Core::dateTime($tutorial['approvetimestamp']);
					$html.='<br />';
				}
				if($userModel->getUserById($tutorial['lasteditor'])){
					$html.='Last edited by: '.XenForo_Template_Helper_Core::helperUserName($userModel->getUserById($tutorial['lasteditor']));
					$html.=', '.XenForo_Template_Helper_Core::dateTime($tutorial['publishtimestamp']);
					$html.='<br />';
				}
			}else{
				$html.='<h3 class="subHeading" style="margin-top: 0px;">';
				if($tutorial['isdraft']){
					$html.='This tutorial is a draft.';
				}
				else{
					$html.='This tutorial is in moderation queue.';
				}
				$html.='</h3>';
			}
			$html.='<a class="button" href="'.XenForo_Link::buildPublicLink('help/tutorials').'">Back to tutorial list</a>';
			$html.='<br />';
			$html.='<br />';
			$prevlnk = XenForo_Link::buildPublicLink('help/tutorials','',array('tutorial'=>$tut,'page'=>$prevp));
			$nextlnk = XenForo_Link::buildPublicLink('help/tutorials','',array('tutorial'=>$tut,'page'=>$nextp));
			if($pag<=1){
				$prevlnk = XenForo_Link::buildPublicLink('help/tutorials');
			}
			$html.='<a class="button" href="'.$prevlnk.'">Previous</a>';
			if($pag<$stepcount){
				$html.='<a class="button" href="'.$nextlnk.'">Next</a>';
			}
			$html.='<br />';
			$html.='<p><b>Step '.(string)$pag.' of '.(string)$stepcount.':</b> ';
			$html.=htmlspecialchars($step['quickInstruction']);
			$html.='</p>';
			$iid = $step['imageDisp'];
			if($iid){
				$imglnk = XenForo_Link::buildPublicLink('help/tutorials','',array('action'=>'displayimage','image'=>$iid));
				$html.='<p>';
				$html.='<div style="text-align:center;">';
				$html.='<a class="LbTrigger"
						   data-href="index.php?misc/lightbox"
						   target="_blank"
						   href="'.$imglnk.'">';
				$html.='<img class="LbImage"
							 src="'.$imglnk.'"
							 style="max-width: 100%;
									max-height: 500px;">';
				$html.='</a>';
				$html.='</div>';
				$html.='</p>';
			}
			$html.='<b>Detailed description:</b> ';
			$html.=@str_replace("\n","<br />\n",htmlspecialchars($step['detailedInstruction']));
			$viewParams['html']=$html;
		}
		if($act=='edit' && $sac==''){
			$html='';
			$html.='
		<div class="section" style="max-width: 350px">
			<div class="secondaryContent statsList" >
				<h3><a>Usage quota</a></h3>
				<div class="pairsJustified">
					<dl><dt>Drafts:</dt>
						<dd>'.$usageDrafts.' of '.$maxDrafts.'</dd></dl>
					<dl><dt>Unpublished (drafts + moderation) :</dt>
						<dd>'.$usagePendingApproval.' of '.$maxPendingApproval.'</dd></dl>
					'.(($tut>0)?'<dl><dt>Steps:</dt>
						<dd>'.$usageSteps.' of '.$maxSteps.'</dd></dl>
					':'').'<dl><dt>Images (unpublished) :</dt>
						<dd>'.@number_format($usageImageMegs,2).'MB of '.$maxImageMegs.'MB</dd></dl>
				</div>
			</div>
		</div>';
			$viewParams['html'].=$html;
		}
		return $this->_getWrapper('tutorials',
			$this->responseView('XenForo_ViewPublic_Base', 'help_tutorials',$viewParams)
		);
	}
}
