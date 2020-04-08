<?php
// Begin of MAIN part of this ajax.php script
require_once './inc/sql.php';
$func=@$_GET['func'];
// Calls the function requested, see below for the definition of the corresponding functions
echo function_exists($func)?$func():"no corresponding function";
$sql->close();
// End of MAIN part of the ajax.php

/* function exampleFunction() {
	global $sql;
	$response = array();
	$gameid=@$_COOKIE['gameid']?:"";
	$param1=@$_GET['param1']?:"";
	
	$Query = $sql->prepare("");
	$Query->bind_param("s",$param1);
	$Query->execute();
	$Results=$Query->get_result();
	if ($Results->num_rows > 0) {
		$response = $Results->fetch_array(MYSQLI_ASSOC);
		$response['html']="html sentence to log the result";
	} else {
		$response['outcome']="ERROR";
		$response['yards']=0;
		$response['html']="Specific Error Message or SQL error.";
	}
	return (@$_GET['format']=="json")?json_encode($response):$response['html'];
} */

function updateName() {
/*
OK test
TODO : add "security" implementing a passphrase control
*/
	global $sql;
	$response = array();
	$gameid=@$_COOKIE['gameid']?:"";
	$player=@$_GET['player']?:"";
	$namevalue=@$_GET['value']?:"";
	
	$Query=$sql->prepare("UPDATE `fliip_games` SET `".$player."` = ? WHERE `fg_id` = ?");
	$Query->bind_param("si",$namevalue,$gameid);
	$Query->execute();
	if ($Query->affected_rows > 0) {
		$response['html']=$player." name changed to ".$namevalue." successfully. (gameid=".$gameid.")";
	} else {
		$response['html']="Player name not valid, gameid ".$gameid." not found in cookies or SQL error.";
	}
	return (@$_GET['format']=="json")?json_encode($response):$response['html'];
}

function dicePlay () {
/* OK
TODO : add other type of outcome, now only handles "Return" */
	global $sql;
	$response = array();
	$gamebefore = array();
	$gameafter = array();
	$gameid = @$_COOKIE['gameid']?:"";
	$diceplaytype=@$_GET['type']?:"";
	$response['diceroll']=(mt_rand(1,6)+mt_rand(1,6)+mt_rand(1,6));
	
	$Query = $sql->prepare("SELECT `fdco_outcome` as 'outcome', `fdco_yards` as 'yards' FROM `fliip_diceplaysoutcomes` WHERE `fdco_type`=? AND `fdco_dice`=?");
	$Query->bind_param("si",$diceplaytype,$response['diceroll']);
	$Query->execute();
	$Results=$Query->get_result();
	if ($Results->num_rows > 0) {
		foreach ($Results->fetch_array(MYSQLI_ASSOC) as $col => $value) {
			$response[$col]=$value;
		}
		$response['html']="3 dices rolled ".$response['diceroll']." for ".$diceplaytype." outcomes ".$response['outcome']." of ".$response['yards'];

		$QueryGameParams=$sql->prepare("SELECT `fg_bluedrive` as 'bluedrive', `fg_reddrive` as 'reddrive', `fg_bluescore` as 'bluescore', "
						."`fg_redscore` as 'redscore', `fg_direction` as 'direction', `fg_scrimmage` as 'los', `fg_down` as 'down', `fg_1downline` as 'firstdownline' "
						."FROM `fliip_games` WHERE `fg_id` = ?");
		$QueryGameParams->bind_param("i", $gameid);
		$QueryGameParams->execute();
		$ResultGameParams=$QueryGameParams->get_result();
		if ($ResultGameParams->num_rows > 0) {
			$gamebefore = $ResultGameParams->fetch_array(MYSQLI_ASSOC);
			$gameafter = $gamebefore;
			// calculate new los = Line of Scrimmage
			if ($response['outcome']=="Return") {
				$gameafter['direction'] = -1*$gamebefore['direction']; // change sign via *-1
				$gameafter['direction']==1 ? $gameafter['bluedrive'] += 1 : $gameafter['reddrive'] += 1;
				
				if (strpos($diceplaytype,"KickOff") !== false){ // Kick Off type of DicePlay
					// New LoS is the yardage outcome but the outcome is expressed from the player own starting EndZone
					$gameafter['los'] = (50-$response['yards'])*$gamebefore['direction'];
				} else { // Punt type of DicePlay
					// New Los is calculated from current LoS + yardage of the punt
					$gameafter['los'] = $gamebefore['los']+$response['yards']*$gamebefore['direction'];
				}
				
				// set next play to 1st down and calculate new 1st down line
				$gameafter['down']=1;
				$gameafter['firstdownline']=$gameafter['direction']==1?min($gameafter['los']+10,50):max($gameafter['los']-10,-50);
			} else {
				// TODO : add other type of outcome, now only handles "Return"

			}
			
		}
		$response['gameafter']=$gameafter;
		$response['html'].= " New LoS ".$gameafter['los']." .";
		$response['status']="OK";
	} else {
		$response['status']="OK";
		$response['outcome']="ERROR";
		$response['yards']=0;
		$response['html']="Incorrect DicePlay ".$diceplaytype." or SQL error.";
	}
	
	// Log the play with the context "before" in which is was played
	$QueryPlayLogRecord = $sql->prepare("INSERT INTO `fliip_playlog` (`fpl_game`, `fpl_direction`, `fpl_scrimmage`, `fpl_down`, `fpl_1downline`, "
										."`fpl_play`, `fpl_dice`, `fpl_outcome`, `fpl_yards`, `fpl_details`) "
										."VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
	$QueryPlayLogRecord->bind_param("iiiiisisis",$gameid,$gamebefore['direction'],$gamebefore['los'],$gamebefore['down'],$gamebefore['firstdownline'],
										$diceplaytype,$response['diceroll'],$response['outcome'],$response['yards'],$response['html']);
	$QueryPlayLogRecord->execute();
	if ($QueryPlayLogRecord->affected_rows <= 0) {
		$response['status'].="_ERROR_LOG";
	}
	
	// Update the game params to prepare for next play
	$QueryGameUpdate = $sql->prepare("UPDATE `fliip_games` SET `fg_bluedrive` = ?, `fg_reddrive` = ?, `fg_bluescore` = ?, `fg_redscore` = ?, "
									."`fg_direction` = ?, `fg_scrimmage` = ?, `fg_down` = ?, `fg_1downline` = ?, `fg_offense` = NULL, `fg_defense` = NULL, "
									."`fg_lastplay` = ? WHERE `fg_id` = ?");
	$QueryGameUpdate->bind_param("iiiiiiiisi",$gameafter['bluedrive'],$gameafter['reddrive'],$gameafter['bluescore'],$gameafter['redscore'],
									$gameafter['direction'],$gameafter['los'],$gameafter['down'],$gameafter['firstdownline'],
									$response['html'],$gameid);
	$QueryGameUpdate->execute();
	if ($QueryGameUpdate->affected_rows <= 0) {
		$response['status'].="_ERROR_GAMESQL";
	}
	
	return (@$_GET['format']=="json")?json_encode($response):$response['html'];
}

function chooseCard () {
/* test OK
TODO : add a control to only accept change of cards from the correct player, ie if direction of play is 1 : then only blue can set offense
*/
	global $sql;
	$response = array();
	$gameid = @$_COOKIE['gameid']?:"";
	$card = @$_GET['card']?:"";
	$offdef = @$_GET['offdef']?:"";
	
	$Query = $sql->prepare("UPDATE `fliip_games` SET `fg_".$offdef."` = ? WHERE `fg_id` = ?".
							/* control to only set Offense card if Defense is not yet set, and as a contrary only set Defense if Offense is already set */
							" AND ".($offdef=="offense"?"`fg_defense` is NULL":"`fg_offense` is not NULL"));
	$Query->bind_param("si",$card,$gameid);
	$Query->execute();
	if ($Query->affected_rows > 0) {
		$response['status']="OK";
		$response['html']="Card ".$card." recorded successfully for ".$offdef.". (gameid=".$gameid.")";
	} else {
		$response['status']="ERROR";
		$response['html']="Card ".$card." not valid, gameid ".$gameid." (empty if no cookie) not found, side ".$offdef." error or try to change offense after defense choice or SQL error.";
	}
	return (@$_GET['format']=="json")?json_encode($response):$response['html'];
}

function getOffensePersonnel () {
/* OK */
	global $sql;
	$response = array();
	$gameid = @$_COOKIE['gameid']?:"";
	
	$Query = $sql->prepare("SELECT `fc_personnel` FROM `fliip_games` LEFT JOIN `fliip_cards` ON `fc_id` = `fg_offense` WHERE `fg_id` = ?");
	$Query->bind_param("i", $gameid);
	$Query->execute();
	$Result=$Query->get_result();		
	if ($Result->num_rows > 0) {
		$card = $Result->fetch_array(MYSQLI_ASSOC);
		if ($card['fc_personnel']!="") {
			$response['personnel']=$card['fc_personnel'];
			$response['status']="OK";
		} else {
			$response['personnel']="NotDefined";
			$response['status']="UNDEF";
		}
		$response['html']="Offense personnel is ".$response['personnel'];
	} else {
		$response['status']="ERROR";
		$response['personnel']="";
		$response['html']="Offense card error or gameid ".$gameid." (empty if no cookie) not found or SQL error.";
	}
	return (@$_GET['format']=="json")?json_encode($response):$response['html'];
}

function calculateCardOutcome () {
/* OK */
	global $sql;
	$response = array();
	$gamebefore = array();
	$gameafter = array();
	$gameid = @$_COOKIE['gameid']?:"";
	
	$QueryOutcome=$sql->prepare("SELECT `fcpo_outcome` as 'outcome', `fcpo_yards` as 'yards', `fcpo_offense` as 'offense', `fcpo_defense` as 'defense' "
								."FROM `fliip_cardsplaysoutcomes`,`fliip_games`"
								."WHERE `fcpo_offense` = `fg_offense` AND `fcpo_defense` = `fg_defense` AND `fg_id` = ?");
	$QueryOutcome->bind_param("i", $gameid);
	$QueryOutcome->execute();
	$ResultOutcome=$QueryOutcome->get_result();		
	if ($ResultOutcome->num_rows > 0) {
		$response = $ResultOutcome->fetch_array(MYSQLI_ASSOC);
		$response['status']="OK";
	} else {
		// TODO : test if fg_offense and fg_defense are well set otherwise return error
		// the combination of cards is not yet defined in the table, return random 
		$response['outcome']="YardsGainedRandom";
		$response['yards']=mt_rand(1,6);
		$response['status']="RANDOM";
	}
	$response['html']="Cards outcome ".$response['yards']." ".$response['outcome'];
	
	$QueryGameParams=$sql->prepare("SELECT `fg_bluedrive` as 'bluedrive', `fg_reddrive` as 'reddrive', `fg_bluescore` as 'bluescore', "
						."`fg_redscore` as 'redscore', `fg_direction` as 'direction', `fg_scrimmage` as 'los', `fg_down` as 'down', `fg_1downline` as 'firstdownline' "
						."FROM `fliip_games` WHERE `fg_id` = ?");
	$QueryGameParams->bind_param("i", $gameid);
	$QueryGameParams->execute();
	$ResultGameParams=$QueryGameParams->get_result();
	if ($ResultGameParams->num_rows > 0) {
		$gamebefore = $ResultGameParams->fetch_array(MYSQLI_ASSOC);
		$gameafter = $gamebefore;
		// calculate new los = Line of Scrimmage
		$gameafter['los'] = $gamebefore['los']+($response['yards']*$gamebefore['direction']);
		
		if ($response['outcome']=="TouchDown" or ($gameafter['los']*$gamebefore['direction'])>=50) {
			//TOUCHDOWN !!! directly or just because offense has passed EndZone line (more than 50)
			$gamebefore['direction']==1 ? $gameafter['bluescore'] += 7 : $gameafter['redscore'] += 7;
			$gameafter['down']=0; // to symbolise the kickoff that has to happen
			$gameafter['firstdownline']=0;
			$gameafter['los']=0;
			$response['status'].="_TD";
			$response['html'].=" - TouchDown !!!";
		} elseif ( $response['outcome']=="Interception" or $response['outcome']=="Fumble"
				or ($gamebefore['down']==4 and ($gameafter['los']-$gamebefore['firstdownline'])*$gamebefore['direction'] < 0 ) ) {
			// Possession is LOST and change direction
			$gameafter['direction'] = -1*$gamebefore['direction']; // change sign via *-1
			$gameafter['direction']==1 ? $gameafter['bluedrive'] += 1 : $gameafter['reddrive'] += 1;
			// back to 1st down and new 1st down line
			$gameafter['down']=1;
			$gameafter['firstdownline']=$gameafter['direction']==1?min($gameafter['los']+10,50):max($gameafter['los']-10,-50);
			$response['status'].="_LOST";
			$response['html'].=" - Lost ball, changed direction.";
			$response['html'].= " New LoS ".$gameafter['los']." .";
		} else {
			// Offense keeps the ball for next play
			// newlos $gameafter['los'] is already calculated, just has to evaluate if gained 1st down
			if (($gameafter['los']-$gamebefore['firstdownline'])*$gamebefore['direction'] >= 0) {
				// back to 1st down
				$gameafter['down']=1;
				$gameafter['firstdownline']=$gameafter['direction']==1?min($gameafter['los']+10,50):max($gameafter['los']-10,-50);
				$response['html'].=" - 1st down gained !";
			} else {
				$gameafter['down']+=1;
				$response['html'].=" - Next ".downtext($gameafter['down'])." down to play.";
			}
			$response['html'].= " New LoS ".$gameafter['los']." .";
		}
	} else {
		$response['status'].="_ERROR_GAMEPARAMS";
		$response['html'].=" - No data for ".$gameid." (empty if no cookie) found or SQL error.";
	}
	// add new game values in the response to be able to update the page without another request
	$response['gameafter']=$gameafter;
	
	// Log the play with the context "before" in which is was played
	$QueryPlayLogRecord = $sql->prepare("INSERT INTO `fliip_playlog` (`fpl_game`, `fpl_direction`, `fpl_scrimmage`, `fpl_down`, `fpl_1downline`, "
										."`fpl_play`, `fpl_offense`, `fpl_defense`, `fpl_outcome`, `fpl_yards`, `fpl_details`) "
										."VALUES (?, ?, ?, ?, ?, 'Cards', ?, ?, ?, ?, ?)");
	$QueryPlayLogRecord->bind_param("iiiiisssis",$gameid,$gamebefore['direction'],$gamebefore['los'],$gamebefore['down'],$gamebefore['firstdownline'],
										$response['offense'],$response['defense'],$response['outcome'],$response['yards'],$response['html']);
	$QueryPlayLogRecord->execute();
	if ($QueryPlayLogRecord->affected_rows <= 0) {
		$response['status'].="_ERROR_LOG";
	}
	
	// Update the game params to prepare for next play
	$QueryGameUpdate = $sql->prepare("UPDATE `fliip_games` SET `fg_bluedrive` = ?, `fg_reddrive` = ?, `fg_bluescore` = ?, `fg_redscore` = ?, "
									."`fg_direction` = ?, `fg_scrimmage` = ?, `fg_down` = ?, `fg_1downline` = ?, `fg_offense` = NULL, `fg_defense` = NULL, "
									."`fg_lastplay` = ? WHERE `fg_id` = ?");
	$QueryGameUpdate->bind_param("iiiiiiiisi",$gameafter['bluedrive'],$gameafter['reddrive'],$gameafter['bluescore'],$gameafter['redscore'],
									$gameafter['direction'],$gameafter['los'],$gameafter['down'],$gameafter['firstdownline'],
									$response['html'],$gameid);
	$QueryGameUpdate->execute();
	if ($QueryGameUpdate->affected_rows <= 0) {
		$response['status'].="_ERROR_GAMESQL";
	}
	return (@$_GET['format']=="json")?json_encode($response):$response['html'];
}

?>
