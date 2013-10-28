<?php
$hd = mysqli_connect("localhost","itndata","itndata","itndata") or die("Error " . mysqli_error($hd));

if ($argv[1] == "flush")
{
	mysqli_query($hd,"truncate table tmp_noms");
	mysqli_query($hd,"truncate table tmp_vote");
	
}
if ($argv[1] == "countrymap")
{
	mysqli_query($hd,"update tmp_noms t1 inner join countries t2 on t1.country = t2.country set t1.where_micro = t2.subregion");
	mysqli_query($hd,"update tmp_noms t1 inner join countries t2 on t1.where_micro = t2.subregion set t1.where_macro = t2.region");
	$sql = "select * from tmp_noms where where_macro = '' or where_macro is null";
	$res = mysqli_query($hd,$sql);
	while ($row = mysqli_fetch_assoc($res))
	{
		print_r($row);
	}
}

if ($argv[1] == "delete")
{
	mysqli_query($hd,"delete from tmp_noms where country = 'delete'");

}

if ($argv[1] == 'backup')
{
	$stamp = date("YmdHis");
	$exec = "mysqldump -u itndata -pitndata itndata > backup/itndata.$stamp.sql";
	echo "$exec\n";
	exec($exec);
}

if ($argv[1] == 'pump')
{
	$stamp = date("YmdHis");
	$exec = "mysqldump -t -c -u itndata -pitndata itndata tmp_noms tmp_vote";
	$output = Array();
	exec($exec, $output);
	$output = implode("\n",$output);
	$output = "USE itndata;\n" . $output;
	$output = str_replace("`tmp_vote`","`vote`",$output);
	$output = str_replace("`tmp_noms`","`noms`",$output);
	file_put_contents("backup/pump.sql",$output);
	exec("mysql -u itndata -pitndata < backup/pump.sql");
}
/*
delete from noms where datafile = "data/201201.txt"
delete from vote where hash not in (select nhash from noms);
*/