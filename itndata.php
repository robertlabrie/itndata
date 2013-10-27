<?php
//testing();
function testing()
{
$comment = "*'''Oppose'''. Even if this was a retirement from all forms of cricket, I would still oppose due to fear of setting a precedent we later regret.I can't see this being posted, but I'll explain my reasoning for the benefit of those who aren't sure whether or not to nominate other retirements in future. Don Bradman would be setting the bar too high, but the problem with going much lower is that there are too many other sportsmen of a similar age who could make a similarly strong case for posting. Sticking to cricket, Ponting was an extremely successful captain despite his Ashes record, but should we also have posted all of Graeme Smith, Steve Waugh, Michael Vaughan and Andrew Strauss? Batting wise he's in the class of Lara, Tendulkar, Dravid and Kallis, but should we post all of their retirements? Most of the second list were captains, and most of the first list were fantastic batsmen. And that's not even considering people from other sports with a comparable or level of interest from the English-speaking world.I would definitely support Alex Ferguson &ndash; almost certainly the best manager or head coach in ''any'' sport in the last generation and a half, and to my knowledge he has never appeared on the Main Page. The only other person I would probably support would be Roger Federer, due to the general consensus on his status within tennis &ndash; but even then I'd have a second thought due to his prior prominence on ITN. I might have considered Michael Schumacher in 2006, Tiger Woods had he called it a day a few years ago, and there may be comparable figures to the above in sports that I'm less interested in. For what it's worth I would vote against Tendulkar, on the basis that we posted the two things that set him apart from Ponting.Sorry for the long-winded post, but I hope that helps others understand where I come from on sporting retirements. â[[User talk:WaitingForConnection|WFC]]â '''[[User:WaitingForConnection/FL wishlist|FL wishlist]]''' 08:25, 1 December 2012 (UTC)";
$comment = " [Posted in Recent death ticker] [[Bal Thackeray]]: Nominated for blurb level";
$comment = " [posteded in] I love it";
$comment = "*'''Strong Support + rewording''' : Very surprising event. Put the emphasis on '''\"revolt against corruption\"''', not against the CCP. Thus most of the wiki NPOV deb";
foreach (Array('support','oppose','comment','question','pull','post') as $vote)
{
	//if (preg_match("/'''(.*\w)" . $vote . "(.*?)'''/i",$comment)) { echo "$vote\n"; break;}
	if (preg_match("/'''([^']*?)" . $vote . "([^']*?)'''/i",$comment)) { echo "$vote\n";}
	//if (preg_match("/'''(\w*?| *?)" . $vote . "(\w*?| *?)'''/i",$comment)) { echo "$vote\n";}
	//if (preg_match("/\[(\w*?| *?)" . $vote . "/i",$comment)) { echo "$vote\n";}
	//if (preg_match("/\[(\w*?| *?)" . $vote . "/i",$comment)) { echo "$vote\n";}
}
die();
}

$data = file_get_contents($argv[1]);

$vote_types = Array('support','oppose','comment','question','pull','post','neutral','ready');
$result_types = Array('post','pull','close','withdrawn');
$hd = mysqli_connect("localhost","itndata","itndata","itndata") or die("Error " . mysqli_error($hd));
$dbprefix = "tmp_";

//strip out HTML comments (heavily used in template)
//$data = preg_replace("/<!--(.*?)-->/","",$data);

//strip out striken comments
$data = preg_replace("/<strike>(.*?)<\/strike>/","",$data);

//strip out hats and habs
/*
$data = preg_replace("/\{\{hat(.*?)\}\}/s","",$data);
$data = preg_replace("/\{\{hab(.*?)\}\}/s","",$data);
*/

//split into sections
$noms = explode("\n===",$data);

//the first entry is garbage
array_shift($noms);
$dbstructure = Array();

//loop through each nom
$loadorder = 0;
foreach ($noms as $nom)
{
	//echo "$nom\n";
	$item = Array();
	$item['nhash'] = md5($nom);
	$item['loadorder'] = ++$loadorder;	//access likes to sort on the PK (in this case some anonymous hash) so lets invent something
	//initialize some vars
	$item['result'] = "noconsensus";
	$item['itnr'] = 'no';
	$item['recent_deaths'] = 'no';
	$item['time_elapsed'] = '0';
	$item['fatalities'] = '0';
	$item['datafile'] = $argv[1];
	//$nom = preg_replace("/[\x00-\x1F\x80-\xFF]/s", '', $nom);
	$nom = trim($nom);
	
	$nom = strip_tags($nom);	//dump worthless HTML
	$item['title'] = trim(str_replace("=","",strstr($nom,"\n",true)));
	$item['ltitle'] = strtolower($item['title']);
	foreach ($result_types as $rtype)
	{
		if (preg_match("/\[(\w*?| *?)" . $rtype . "/i",$item['title'])) { $item['result'] = $rtype; }
	}

	//echo "$nom\n";
	//echo "\n----------------------------------\n";

	//planned to use RegEx for this but it was being a pain so F-regex
	$template = $nom;
	$template = substr($template,stripos($template,"{{ITN candidate")+15);
	$template = substr($template,0,strpos($template,"}}"));
	$template = trim($template);
	
	$template = str_replace("| updated1","| updated",$template);
	$template = str_replace("| updater1","| updater",$template);
	//echo "$template\n";
	
	//now process the template
	$template = explode("\n",$template);
	foreach ($template as $t)
	{
		$t = trim($t);
		if (substr($t,0,1) != "|") { continue; }
		$t = trim($t,"| ");
		$t = explode("=",$t,2);
		if (isset($t[1])) { $item[str_replace(" ","_",strtolower(trim($t[0])))] = trim(strtolower($t[1])); }
	}
	//rename sign to nsign
	$item['nsign'] = $item['sign'];
	unset($item['sign']);
	
	//the comments section is everything not inside some braces
	$comments = preg_replace("/\{\{(.+?)\}\}/s","",$nom);
	$comments = trim($comments);
	$comments = strstr($comments,"\n");
	$comments = trim($comments);
	$comments = explode("\n",$comments);
	$item['comments'] = count($comments);	
	//enumerate the comments looking for votes
	$item['time_stop'] = 0;
	$item['time_last'] = 0;
	
	$item['votes'] = Array();
	foreach ($vote_types as $v)
	{
		$item['vote_' . $v] = 0;
	}
	$item['vote_total'] = 0;
	foreach ($comments as $comment)
	{
		//got to some other section marker, time to bail
		if (preg_match("/^==?[_a-zA-Z. ]/",$comment)) { break; }
		
		//work out the details of the vote
		$vote = mwVote($comment);
		$vote['vhash'] = md5($comment);
		//echo "$comment\n"; print_r($vote);
		
		//track the time stop/last through the process
		if ($vote['stamp'] > $item['time_last']) { $item['time_last'] = $vote['stamp']; }
		
		//stack the votes onto an array
		if ((isset($vote['vote'])) && (isset($vote['name'])))
		{
			$vote['hash'] = $item['nhash'];
			array_push($item['votes'],$vote);
			
			//set the stop time at post time, though maybe there was more discussion
			if ($vote['vote'] == 'post') { $item['time_stop'] = $vote['stamp']; }
			
			//load the vote into the DB
			$dbfields = "";
			$dbvalues = "";
			foreach (array_keys($vote) as $key)
			{
				$dbfields .= $key . ",";
				$dbvalues .= "'" . mysqli_real_escape_string($hd,$vote[$key]) . "',";
			}
			$dbfields = trim($dbfields,",");
			$dbvalues = trim($dbvalues,",");
			$sql = "insert into " . $dbprefix . "vote ($dbfields) values ($dbvalues);";
			print_r($vote);
			mysqli_query($hd,$sql) or die("vote: failed to execute $sql\n" . mysqli_error($hd) . "\n" . var_export($vote,true));

			if (in_array($vote['vote'],Array('support','oppose'))) { $item['vote_total']++; }
			$item['vote_' . $vote['vote']]++;
		}
	}
	
	//if never posted, then it stopped at the last comment
	if ($item['time_stop'] == 0) { $item['time_stop'] = $item['time_last']; }
	$item['time_elapsed'] = $item['time_stop'] - $item['time_start'];
	
	//if the nom actually signed the template, we can use it to track the time the nom
	//was started
	if (isset($item['sign']))
	{
		//echo $item['sign'] . "\n";
		$sign = mwSig($item['sign']);
		$item['time_started'] = $sign['stamp'];
	}
	unset($item['votes']);
	echo $item['title'] . "\n";
	//print_r($item);
	//echo $item['ltitle'] . "\n";
	//die();
	
	$dbfields = "";
	$dbvalues = "";
	unset($item['votes']);
	foreach (array_keys($item) as $key)
	{
		$dbfields .= $key . ",";
		$dbvalues .= "'" . mysqli_real_escape_string($hd,$item[$key]) . "',";
	}
	$dbfields = trim($dbfields,",");
	$dbvalues = trim($dbvalues,",");
	$sql = "insert into " . $dbprefix . "noms ($dbfields) values ($dbvalues);";
	//echo "$sql\n";
	mysqli_query($hd,$sql) or die("nom: failed to execute $sql\n" . mysqli_error($hd) . "\n" . var_export($item,true));
	$blah = array_keys($item);
	foreach ($blah as $k) { $dbstructure[$k] = true; }
}

foreach (array_keys($dbstructure) as $dbf) { echo "`$dbf` TEXT NOT NULL,\n"; }
function mwVote($comment)
{
	//echo "\n-----------------------------------------------------------------\n$comment\n---------------------------------------------------\n";
	global $vote_types;
	$out = Array();
	$out['comment'] = $comment;
	$user = mwSig($comment);
	foreach ($vote_types as $vote)
	{
		//if (preg_match("/(\w*?| *?)'''(\w*?| *?)" . $vote . "(\w*?| *?)'''/i",$comment)) { $user['vote'] = $vote; }
		if (preg_match("/'''([^']*?)" . $vote . "([^']*?)'''/i",$comment)) { $user['vote'] = $vote; break; }
	}
	return $user;
}
/**
 *The MW signature has the username and the date in some format or other. Extracting it is key
 *to tracking processing times, etc
 *@param string A user signature string
 *@return mixed An array containing the useful parts
 */
function mwSig($sign)
{
	//echo "$sign\n";
	//return blank array if there is nothing to process
	if (!$sign) { return Array(); }
	$out = Array();
	$out['sign'] = $sign;
	
	
	//Apparently some people think it's funny to mess with the format of the MW date string (thanks IgnorantArmies)
	$sign = preg_replace("/Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday/","",$sign);
	$sign = str_replace("  "," ",$sign);
	
	//the date is stored as a string at a fixed number of spaces from the end
	$arr = explode(" ",$sign);
	$i = count($arr) -1;	//we don't care about the UTC bit
	
	//extract the pieces of the date
	if (count($arr) > 4)
	{
		$out['date'] = $arr[$i-4] . " " . $arr[$i-3] . " " . $arr[$i-2] . " " . $arr[$i-1];
			//store in unix format
			$out['stamp'] = strtotime($out['date']);
	}
	
	
	/*
	The users signatures are a dogs breakfast but almost always have either "User: " or "User talk:". Worse
	still, even though a space is mapped to _ when rendered, MW stores the space of the username in mark-up
	when expanding --~~~~. Some regex here does the trick, returning an array of probable matches
	*/
	$user = preg_split("/\[|\||\]|\)|\#|\//",$sign);
	$user = preg_grep("/(User\:)|(talk\:)/",$user);
	foreach ($user as $u)
	{
		
		$out['name'] = trim(strstr($u,":"),":");
	}
	return $out;
}