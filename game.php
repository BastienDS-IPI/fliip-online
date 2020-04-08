<?php
require_once './inc/sql.php';

$gameidURL=@$_GET['gameid']?:0;//@ not to print notice warning and ?: ternary operator to default value
$gameid=@$_COOKIE['gameid']?:$gameidURL;

$getgame=$sql->prepare("SELECT * FROM fliip_Games WHERE fg_id= ?");
$getgame->bind_param("i", $gameid);
$getgame->execute();
$gameparams=$getgame->get_result();
if ($gameparams->num_rows == 0 /* no game exists with this number, then create a new one */) {
	// Shuffle the direction of play
	// +1 = Positive (Left to Right= bLue on Red)
	// -1 = Negative (Right to Left = Red on bLue)
	$direction=0;
	while ($direction==0) { $direction=mt_rand(-1,1); }
	
	// create a new Game
	$sql->query("INSERT INTO `fliip_games` (`fg_bluedrive`, `fg_reddrive`, `fg_bluescore`, `fg_redscore`, `fg_direction`, `fg_scrimmage`) VALUES ('0', '0', '0', '0', '".$direction."', '0')");
	$gameid=$sql->insert_id;
	
	// re-execute the request for game params
	$getgame->execute();
	$gameparams=$getgame->get_result();
}
foreach ($gameparams->fetch_array(MYSQLI_ASSOC) as $col => $value) {
	$$col=$value;
}

setcookie("gameid",$gameid, 0, "/", "", false, false);

require_once './inc/header.php';

// TODO : update boxscore when gameafter values are returned from calculateOutcome and Diceplay

?>
<table id="boxScore" class="header">
	<thead>
		<td><a href="./game.php?gameid=<?= $fg_id ?>">Game <?= $fg_id ?></a></td>
		<td>Player Names</td>
		<td>Score</td>
		<td>Drives</td>
		<td>Possession & LoS</td>
	</thead>
	<tbody>
		<tr>
			<td><label for="fg_blueplayer">Blue Player</label></td>
			<td><input type="text" name="fg_blueplayer" size=20 maxlength=50 value="<?= $fg_blueplayer ?>" onchange="updateName(this);"></input></td>
			<td><?= $fg_bluescore ?></td>
			<td><?= $fg_bluedrive ?></td>
			<td <?= $fg_direction==1?"":"hidden" ?>><?= "Blue ". downtext($fg_down)." on ".$fg_scrimmage ?></td>
		</tr>
		<tr>
			<td><label for="fg_redplayer">Red Player</label></td>
			<td><input type="text" name="fg_redplayer" size=20 maxlength=50 value="<?= $fg_redplayer ?>" onchange="updateName(this);"></input></td>
			<td><?= $fg_redscore ?></td>
			<td><?= $fg_reddrive ?></td>
			<td <?= $fg_direction==-1?"":"hidden" ?>><?= "Red ".downtext($fg_down)." on ".$fg_scrimmage ?></td>
		</tr>		
	</tbody>
</table>
<div id="actions">
	<button type="button" id="KickOff_Normal" onclick="diceplay(this);">KickOff_Normal</button>
	<button type="button" id="Punt_Normal" onclick="diceplay(this);">Punt_Normal</button>
	<br/>
	<select name="offenseCards" id="offenseCards">
<?php
$listOffenseCards=$sql->query("SELECT `fc_id`,`fc_personnel`,`fc_name` FROM `fliip_cards` WHERE `fc_offdef`='Offense' ORDER BY `fc_personnel`,`fc_name`");
while ($offcard = $listOffenseCards->fetch_array(MYSQLI_ASSOC)) {
	echo "<option value='".$offcard['fc_id']."'>".$offcard['fc_personnel']." - ".$offcard['fc_name']."</option>"; 
} ?>
	</select>
	<button type="button" id="chooseOffense" onclick="chooseCard('offense');">ChooseOffense</button>
	<button type="button" id="getOffensePersonnel" onclick="getOffensePersonnel();">getOffensePersonnel</button>
	<select name="defenseCards" id="defenseCards">
<?php
$listDefenseCards=$sql->query("SELECT `fc_id`,`fc_personnel`,`fc_name` FROM `fliip_cards` WHERE `fc_offdef`='Defense' ORDER BY `fc_personnel`,`fc_name`");
while ($defcard = $listDefenseCards->fetch_array(MYSQLI_ASSOC)) {
	echo "<option value='".$defcard['fc_id']."'>".$defcard['fc_personnel']." - ".$defcard['fc_name']."</option>"; 
} ?>
	</select>
	<button type="button" id="chooseDefense" onclick="chooseCard('defense');">ChooseDefense</button>
	<br/>
	<button type="button" id="calculateCardOutcome" onclick="calculateCardOutcome();">calculateCardOutcome</button>
	<br/>
</div>
<textarea id="log" cols=120 rows=3 readonly="readonly" >
<?php 	// TODO : load all fpl_details from fliip_playlog table for this game as lines to understand the history of the game ?>
</textarea>
<script>
function updateName (player) {
	$.getJSON("./ajax.php?func=updateName&format=json&player="+player.name+"&value="+player.value, function (data, textStatus, jqXHR) {
		$("#log").prepend("\n").prepend(data.html)
	})
}

function diceplay (type) {
	$.getJSON("./ajax.php?func=dicePlay&format=json&type="+type.id, function (data, textStatus, jqXHR) {
		$("#log").prepend("\n").prepend(data.html)
	})
}

function chooseCard (offdef) {
	$.getJSON("./ajax.php?func=chooseCard&format=json&offdef="+offdef+"&card="+$("#"+offdef+"Cards").val(), function (data, textStatus, jqXHR) {
		$("#log").prepend("\n").prepend(data.html)
	})
}
function getOffensePersonnel () {
	$.getJSON("./ajax.php?func=getOffensePersonnel&format=json", function (data, textStatus, jqXHR) {
		$("#log").prepend("\n").prepend(data.html);
	})
}
function calculateCardOutcome () {
	$.getJSON("./ajax.php?func=calculateCardOutcome&format=json", function (data, textStatus, jqXHR) {
		$("#log").prepend("\n").prepend(data.html)
	})
}

function empty () {
	$.get("./ajax.php", function (data, textStatus, jqXHR) {
		$("#log").prepend("\n").prepend(jqXHR.responseText)
	})
}
</script>
<!--
		<div id="select-card">
			<div id="row1"> 
				<img id="card_l_11" src="../img/SPRI-KM-COM16062417110_0012.jpg" />
				<img id="card_l_12" src="../img/SPRI-KM-COM16062417110_0012.jpg" />
				<img id="card_l_13" src="../img/SPRI-KM-COM16062417110_0012.jpg" />
				<img id="card_l_14" src="../img/SPRI-KM-COM16062417110_0012.jpg" />
			</div>
			<div id="row2"> 
				<img id="card_l_21" src="../img/SPRI-KM-COM16062417110_0012.jpg" />
				<img id="card_l_22" src="../img/SPRI-KM-COM16062417110_0012.jpg" />
				<img id="card_l_23" src="../img/SPRI-KM-COM16062417110_0012.jpg" />
				<img id="card_l_24" src="../img/SPRI-KM-COM16062417110_0012.jpg" />
			</div> 
			<div id="row2"> 
				<img id="card_l_31" src="../img/SPRI-KM-COM16062417110_0012.jpg" />
				<img id="card_l_32" src="../img/SPRI-KM-COM16062417110_0012.jpg" />
				<img id="card_l_33" src="../img/SPRI-KM-COM16062417110_0012.jpg" />
				<img id="card_l_34" src="../img/SPRI-KM-COM16062417110_0012.jpg" />
			</div>
		</div>
		
-->

<?php	require_once './inc/footer.php'; ?>