<?php
require_once './inc/sql.php';
require_once './inc/header.php';
echo "<div id=\"testargs\">\n";
foreach ($_GET as $arg => $value) {
	echo "	arg {$arg} has value {$value}<br/>\n";
}
echo "</div>
	<div id=\"content-wrapper\">
		<section id=\"content\">";



$req = $sql->prepare("SELECT * FROM test WHERE name LIKE ? and nb > ?");
$req->bind_param("si", $nameSearch, $nbMin);
$nameSearch="%";
$nbMin=10;
$req->execute();
		 
$result = $req->get_result();
		while ($row = $result->fetch_array()){
			echo "{$row['name']} {$row['nb']} {$row['value']}<br>\n";
		}

echo "	</section>
</div>";

require_once './inc/footer.php';
?>
