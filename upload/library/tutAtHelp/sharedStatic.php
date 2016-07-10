<?php

class tutAtHelp_sharedStatic {
	public static function mysql_escape_mimic_fromPhpDoc($inp)
	{//http://php.net/manual/pt_BR/function.mysql-real-escape-string.php
		return str_replace(array('\\',    "\0",  "\n",  "\r",   "'",   '"', "\x1a"),
						   array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'),
						   $inp);
	}
	
	public static function getSupportedImageFormats(){
		$base=array(0 => IMAGETYPE_GIF,
					1 => IMAGETYPE_JPEG,
					2 => IMAGETYPE_PNG);
		$r=array();
		$r['fromsql']=$base;
		$r['tosql']=array();
		foreach($base as $k=>$v){
			$r['tosql'][$v]=$k;
		}
		return $r;
	}
	
	public static function createTableDB(){
		$dbc=XenForo_Application::get('db');
		$q='CREATE TABLE IF NOT EXISTS kiror_help_tutorial_titles (
		tutid SERIAL,
		tutnm VARCHAR(255),
		author INT,
		lasteditor INT,
		isdraft BOOLEAN,
		approved INT,
		createdtimestamp INT,
		publishtimestamp INT,
		approvetimestamp INT,
		lasteditimestamp INT,
		PRIMARY KEY (tutid)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;';
		$dbc->query($q);
		$q='CREATE TABLE IF NOT EXISTS kiror_help_tutorial_images (
		tutid BIGINT UNSIGNED,
		sender INT,
		imageId SERIAL,
		imageBytes MEDIUMBLOB,
		imageFormat INT,
		fname TEXT,
		filetimestamp INT,
		FOREIGN KEY (tutid) REFERENCES kiror_help_tutorial_titles(tutid),
		PRIMARY KEY (imageId)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;';
		$dbc->query($q);
		$q='CREATE TABLE IF NOT EXISTS kiror_help_tutorial_steps (
		tutid BIGINT UNSIGNED,
		step INT,
		quickInstruction VARCHAR(255),
		imageDisp BIGINT UNSIGNED,
		detailedInstruction TEXT,
		FOREIGN KEY (tutid) REFERENCES kiror_help_tutorial_titles(tutid),
		FOREIGN KEY (imageDisp) REFERENCES kiror_help_tutorial_images(imageId),
		PRIMARY KEY (tutid,step)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;';
		$dbc->query($q);
	}

	public static function dropTableDB(){
		$dbc=XenForo_Application::get('db');
		$q='DROP TABLE IF EXISTS kiror_help_tutorial_steps;';
		$dbc->query($q);
		$q='DROP TABLE IF EXISTS kiror_help_tutorial_images;';
		$dbc->query($q);
		$q='DROP TABLE IF EXISTS kiror_help_tutorial_titles;';
		$dbc->query($q);
	}
	
	public static function getTutorialList(){
		$dbc=XenForo_Application::get('db');
		$q='SELECT * FROM kiror_help_tutorial_titles;';
		return $dbc->fetchAll($q);
	}
	
	public static function getDraftTutorialList($uid){
		$uid = intval($uid);
		$dbc=XenForo_Application::get('db');
		$q='SELECT * FROM kiror_help_tutorial_titles WHERE author='.$uid.' AND (isdraft=TRUE OR isdraft is NULL);';
		return $dbc->fetchAll($q);
	}
	
	public static function getModerationMineTutorialList($uid){
		$uid = intval($uid);
		$dbc=XenForo_Application::get('db');
		$q='SELECT * FROM kiror_help_tutorial_titles WHERE isdraft=FALSE AND approved=0 AND author='.$uid.';';
		return $dbc->fetchAll($q);
	}
	
	public static function getModerationTutorialList(){
		$dbc=XenForo_Application::get('db');
		$q='SELECT * FROM kiror_help_tutorial_titles WHERE isdraft=FALSE AND approved=0;';
		return $dbc->fetchAll($q);
	}
	
	public static function getPublishedTutorialList(){
		$dbc=XenForo_Application::get('db');
		$q='SELECT * FROM kiror_help_tutorial_titles WHERE isdraft=FALSE AND approved>0;';
		return $dbc->fetchAll($q);
	}
	
	public static function getTutorialInfo($tutid){
		$dbc=XenForo_Application::get('db');
		$q='SELECT * FROM kiror_help_tutorial_titles WHERE tutid='.$tutid.' LIMIT 1;';
		return $dbc->fetchRow($q);
	}
	
	public static function getStep($tutid,$step){
		$tutid = intval($tutid);
		$step = intval($step);
		$dbc=XenForo_Application::get('db');
		$q='SELECT * FROM kiror_help_tutorial_steps WHERE tutid='.$tutid.' AND step='.$step.' LIMIT 1;';
		return $dbc->fetchRow($q);
	}
	
	public static function getStepList($tutid){
		$tutid = intval($tutid);
		$dbc=XenForo_Application::get('db');
		$q='SELECT * FROM kiror_help_tutorial_steps WHERE tutid='.$tutid.' ORDER BY step;';
		return $dbc->fetchAll($q);
	}
	
	public static function getStepCount($tutid){
		$tutid = intval($tutid);
		$dbc=XenForo_Application::get('db');
		$q='SELECT COUNT(step) as res FROM kiror_help_tutorial_steps WHERE tutid='.$tutid.';';
		return $dbc->fetchRow($q)['res'];
	}
	
	public static function getImageData($imgid){
		$imgid = intval($imgid);
		$dbc=XenForo_Application::get('db');
		$q='SELECT * FROM kiror_help_tutorial_images WHERE imageId='.$imgid.' LIMIT 1;';
		return $dbc->fetchRow($q);
	}
	
	public static function getImageMeta($imgid){
		$imgid = intval($imgid);
		$dbc=XenForo_Application::get('db');
		$q='SELECT imageId,imageFormat,filetimestamp,fname,sender FROM kiror_help_tutorial_images WHERE imageId='.$imgid.' LIMIT 1;';
		return $dbc->fetchRow($q);
	}
	
	public static function getImageMetaList($tutid){
		$tutid = intval($tutid);
		$dbc=XenForo_Application::get('db');
		$q='SELECT imageId,imageFormat,filetimestamp,fname,sender FROM kiror_help_tutorial_images WHERE tutid='.$tutid.' LIMIT 1;';
		return $dbc->fetchAll($q);
	}
	
	public static function newTutorial($uidme){
		$uidme = intval($uidme);
		$dbc=XenForo_Application::get('db');
		$q='INSERT INTO kiror_help_tutorial_titles (author,lasteditor,createdtimestamp,approved,isdraft) VALUES
		('.$uidme.','.$uidme.','.time().',0,TRUE);';
		$dbc->query($q);
		$q='SELECT tutid FROM kiror_help_tutorial_titles ORDER BY tutid DESC LIMIT 1;';
		return $dbc->fetchRow($q)['tutid'];
	}
	
	public static function putImage($uidme,$tutid,$imagebytes,$fname){
		$uidme = intval($uidme);
		$tutid = intval($tutid);
		$imagebytesescaped = self::mysql_escape_mimic_fromPhpDoc($imagebytes);
		$fname = self::mysql_escape_mimic_fromPhpDoc($fname);
		$dbc=XenForo_Application::get('db');
		$sfarr=self::getSupportedImageFormats();
		$fformat=@getimagesizefromstring($imagebytes);
		if(!$fformat) return 0;
		$codeformat = $fformat[2];
		if(!$codeformat) return 0;
		if(!array_key_exists($codeformat,$sfarr['tosql'])) return 0;
		$toSql = $sfarr['tosql'][$codeformat];
		$q='INSERT INTO kiror_help_tutorial_images (tutid,imageBytes,imageFormat,fname,sender,filetimestamp) VALUES
		('.$tutid.',\''.$imagebytesescaped.'\','.$toSql.',\''.$fname.'\','.$uidme.','.time().');';
		$dbc->query($q);
		$q='SELECT imageId FROM kiror_help_tutorial_images WHERE tutid='.$tutid.' ORDER BY imageId DESC LIMIT 1;';
		return $dbc->fetchRow($q)['imageId'];
	}
	
	public static function newStep($uidme,$tutid){
		$uidme = intval($uidme);
		$tutid = intval($tutid);
		$dbc=XenForo_Application::get('db');
		$stp=intval(self::getStepCount($tutid))+1;
		$q='INSERT INTO kiror_help_tutorial_steps (tutid,step,imageDisp,quickInstruction,detailedInstruction) VALUES
		('.$tutid.','.$stp.',NULL,\'\',\'\');';
		$dbc->query($q);
		self::updateTutorialModifiedTime($uidme,$tutid);
	}
	
	public static function updateTutorialInfo($uidme,$arr){
		$uidme = intval($uidme);
		$dbc=XenForo_Application::get('db');
		if(!array_key_exists('tutid',$arr)) return false;
		$tutid = $arr['tutid'];
		$nfo = self::getTutorialInfo($tutid);
		foreach($arr as $k=>$v){
			$nfo[$k]=$v;
		}
		if(strlen($nfo['tutnm'])>255) return false;
		$q='UPDATE kiror_help_tutorial_titles
			SET
				tutnm=\''.self::mysql_escape_mimic_fromPhpDoc($nfo['tutnm']).'\',
				author='.$nfo['author'].',
				lasteditor='.$nfo['lasteditor'].',
				isdraft='.(($nfo['isdraft'])?'TRUE':'FALSE').',
				approved='.$nfo['approved'].',
				publishtimestamp='.(($nfo['publishtimestamp'])?$nfo['publishtimestamp']:0).',
				approvetimestamp='.(($nfo['approvetimestamp'])?$nfo['approvetimestamp']:0).',
				lasteditimestamp='.time().'
			WHERE tutid='.$tutid.';';
		$dbc->query($q);
		return true;
	}
		
	public static function updateTutorialModifiedTime($uidme,$tutid){
		$uidme = intval($uidme);
		$tutid = intval($tutid);
		$dbc=XenForo_Application::get('db');
		$q='UPDATE kiror_help_tutorial_titles
			SET
				lasteditor='.$uidme.',
				lasteditimestamp='.time().'
			WHERE tutid='.$tutid.';';
		$dbc->query($q);
		return true;
	}
	
	public static function updateStepInfo($uidme,$arr){
		$uidme = intval($uidme);
		$dbc=XenForo_Application::get('db');
		if(!array_key_exists('tutid',$arr)) return false;
		if(!array_key_exists('step',$arr)) return false;
		$tutid = intval($arr['tutid']);
		$step = intval($arr['step']);
		$nfo = self::getStep($tutid,$step);
		if(!$nfo) return false;
		foreach($arr as $k=>$v){
			$nfo[$k]=$v;
		}
		$q='UPDATE kiror_help_tutorial_steps
			SET
				quickInstruction=\''.self::mysql_escape_mimic_fromPhpDoc($nfo['quickInstruction']).'\',
				detailedInstruction=\''.self::mysql_escape_mimic_fromPhpDoc($nfo['detailedInstruction']).'\',
				imageDisp='.(($nfo['imageDisp'])?intval($nfo['imageDisp']):'NULL').'
			WHERE tutid='.$tutid.' AND step='.$step.';';
		$dbc->query($q);
		self::updateTutorialModifiedTime($uidme,$tutid);
		return true;
	}
	
	public static function swapStepPos($uidme,$tutid,$stp1,$stp2){
		$uidme = intval($uidme);
		$tutid = intval($tutid);
		$stp1 = intval($stp1);
		$stp2 = intval($stp2);
		$dbc=XenForo_Application::get('db');
		$tmp=-1;
		$q='UPDATE kiror_help_tutorial_steps
			SET step='.$tmp.'
			WHERE tutid='.$tutid.' AND step='.$stp2.';';
		$dbc->query($q);
		$q='UPDATE kiror_help_tutorial_steps
			SET step='.$stp2.'
			WHERE tutid='.$tutid.' AND step='.$stp1.';';
		$dbc->query($q);
		$q='UPDATE kiror_help_tutorial_steps
			SET step='.$stp1.'
			WHERE tutid='.$tutid.' AND step='.$tmp.';';
		$dbc->query($q);
		self::updateTutorialModifiedTime($uidme,$tutid);
		return true;
	}
		
	public static function assignImageToStep($uidme,$tutid,$step,$imageid){
		$uidme = intval($uidme);
		$tutid = intval($tutid);
		$step = intval($step);
		$imageid = intval($imageid);
		$dbc=XenForo_Application::get('db');
		$q='UPDATE kiror_help_tutorial_steps
			SET imageDisp='.$imageid.'
			WHERE tutid='.$tutid.' AND step='.$step.';';
		$dbc->query($q);
		self::updateTutorialModifiedTime($uidme,$tutid);
		return true;
	}
	
	public static function unassignImageFromStep($uidme,$tutid,$step){
		$uidme = intval($uidme);
		$tutid = intval($tutid);
		$step = intval($step);
		$dbc=XenForo_Application::get('db');
		$q='UPDATE kiror_help_tutorial_steps
			SET imageDisp=NULL
			WHERE tutid='.$tutid.' AND step='.$step.';';
		$dbc->query($q);
		self::updateTutorialModifiedTime($uidme,$tutid);
		return true;
	}
	
	public static function unassignImageFromStepDeleting($uidme,$tutid,$step){
		$uidme = intval($uidme);
		$tutid = intval($tutid);
		$step = intval($step);
		$dbc=XenForo_Application::get('db');
		$q='SELECT imageDisp FROM kiror_help_tutorial_steps
			WHERE tutid='.$tutid.' AND step='.$step.';';
		$imageID = $dbc->fetchRow($q)['imageDisp'];
		$imageID = intval($imageID);
		if(!$imageID) return false;
		$q='UPDATE kiror_help_tutorial_steps
			SET imageDisp=NULL
			WHERE imageDisp='.$imageID.';';
		$dbc->query($q);
		$q='DELETE FROM kiror_help_tutorial_images
			WHERE imageId='.$imageID.';';
		$dbc->query($q);
		self::updateTutorialModifiedTime($uidme,$tutid);
		return true;
	}
	
	public static function submitTutorial($uidme,$isapproved,$tutid){
		$uidme = intval($uidme);
		$tutid = intval($tutid);
		$isapproved = ($isapproved!=null&&$isapproved!=false);
		$dbc=XenForo_Application::get('db');
		$nfo = self::getTutorialInfo($tutid);
		$nfo['isdraft']=false;
		$nfo['approved']=(($isapproved)?$uidme:0);
		$nfo['approvetimestamp']=time();
		return self::updateTutorialInfo($uidme,$nfo);
	}
	
	public static function deleteImage($imageid){
		$imageid = intval($imageid);
		$dbc=XenForo_Application::get('db');
		$q='UPDATE kiror_help_tutorial_steps
			SET imageDisp=NULL
			WHERE imageDisp='.$imageid.';';
		$dbc->query($q);
		$q='DELETE FROM kiror_help_tutorial_images WHERE imageId='.$imageid.';';
		$dbc->query($q);
	}
	
	public static function deleteStep($tutid,$step){
		$tutid = intval($tutid);
		$step = intval($step);
		$img = 0;
		$stp = self::getStep($tutid,$step);
		if($stp && is_array($stp) && array_key_exists('imageDisp',$stp)){
			$img=intval($stp['imageDisp']);
		}
		if($img){
			self::deleteImage($img);
		}
		$dbc=XenForo_Application::get('db');
		$q='DELETE FROM kiror_help_tutorial_steps
			WHERE tutid='.$tutid.' AND step='.$step.';';
		$dbc->query($q);
		$q='UPDATE kiror_help_tutorial_steps
			SET step=step-1
			WHERE tutid='.$tutid.' AND step>'.$step.';';
		$dbc->query($q);
	}
	
	public static function deleteTutorial($tutid){
		$tutid = intval($tutid);
		$dbc=XenForo_Application::get('db');
		$q='DELETE FROM kiror_help_tutorial_steps
			WHERE tutid='.$tutid.';';
		$dbc->query($q);
		$q='DELETE FROM kiror_help_tutorial_images
			WHERE tutid='.$tutid.';';
		$dbc->query($q);
		$q='DELETE FROM kiror_help_tutorial_titles
			WHERE tutid='.$tutid.';';
		$dbc->query($q);
	}
	
	public static function publishTutorial($uidme,$tutid){
		$uidme = intval($uidme);
		$tutid = intval($tutid);
		$dbc=XenForo_Application::get('db');
		$nfo = self::getTutorialInfo($tutid);
		$nfo['isdraft']=false;
		$nfo['approved']=$uidme;
		$nfo['publishtimestamp']=time();
		return self::updateTutorialInfo($uidme,$nfo);
	}
	
	public static function unpublishTutorial($uidme,$tutid){
		$uidme = intval($uidme);
		$tutid = intval($tutid);
		$dbc=XenForo_Application::get('db');
		$nfo = self::getTutorialInfo($tutid);
		$nfo['isdraft']=false;
		$nfo['approved']=0;
		$nfo['publishtimestamp']=0;
		return self::updateTutorialInfo($uidme,$nfo);
	}
	
	public static function usageDrafts($uidme){
		return count(self::getDraftTutorialList($uidme));
	}
	
	public static function usageSteps($uidme,$tutid){
		return self::getStepCount($tutid);
	}
	
	public static function usagePendingApproval($uidme){
		$inModQueue = count(self::getModerationMineTutorialList($uidme));
		$inDraftLst = count(self::getDraftTutorialList($uidme));
		return $inModQueue+$inDraftLst;
	}
	
	public static function usageImageMegs($uidme){
		$uidme = intval($uidme);
		$dbc=XenForo_Application::get('db');
		$q='SELECT SUM(OCTET_LENGTH(imageBytes)) as res
			FROM `kiror_help_tutorial_titles`
			INNER JOIN `kiror_help_tutorial_images`
			ON (`kiror_help_tutorial_titles`.`tutid`=`kiror_help_tutorial_images`.`tutid`)
			WHERE author='.$uidme.' AND (isdraft=TRUE OR isdraft is NULL);';
		$bytes=$dbc->fetchRow($q)['res'];
		return $bytes/@pow(2,20);
		
	}
	
	public static function isEverythingFilled($tut){
		$tut = intval($tut);
		$dbc=XenForo_Application::get('db');
		$q='SELECT tutnm
			FROM `kiror_help_tutorial_titles`
			WHERE tutid='.$tut.'
			LIMIT 1;';
		$nm=$dbc->fetchRow($q)['tutnm'];
		if(strlen($nm)<=0) return false;
		$steps = self::getStepList($tut);
		if(count($steps)<=0) return false;
		foreach($steps as $step){
			if(strlen($step['quickInstruction'])<=0) return false;
			if(strlen($step['detailedInstruction'])<=0) return false;
		}
		return true;
	}
	
}
