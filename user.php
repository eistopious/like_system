<?php
	require_once 'php_includes/check_login_statues.php';
	/*function findDistance($uAlatlon, $uBlatlon){
		$u1 = explode(',', $uAlatlon);
		$lat1 = $u1[0];
		$lon2 = $u1[1];

		$u2 = explode(',', $uBlatlon);
		$lat2 = $u2[0];
		$lon2 = $u2[1];

		$dist = acos(sin(deg2rad($lat1))
				* sin(deg2rad($lat2))
				+ cos(deg2rad($lat1))
				* cos(deg2rad($lat2))
				* cos(deg2rad($lon1 - $lon2)));

		$dist = rad2deg($dist);
		$miles = (float) $dist * 69;
		$km = (float) $miles * 1.61;

		$totalDist = sprintf("%0.2f", $miles).' miles';
		$totalDist .= ' ('.sprintf("%0.2f", $km).' kilometres)';
		
		return $totalDist;
	}*/

	// Initialize any variables that the page might echo
	$u = "";
	$sex = "Male";
	$userlevel = "";
	$country = "";
	$joindate = "";
	$lastsession = "";
	$profile_pic = "";
	$profile_pic_btn = "";
	$avatar_form = "";

	// Make sure the _GET username is set and sanitize it
	if(isset($_GET["u"])){
		$u = preg_replace('#[^a-z0-9]#i', '', $_GET['u']);
	}else{
		header('Location: index.php');
		exit();
	}

	// Select the member from the users table
	$sql = "SELECT * FROM `users` WHERE `username`='$u' AND `activated`='1' LIMIT 1";
	$user_query = mysqli_query($conn, $sql);

	// Now make sure the user exists in the table
	$numrows = mysqli_num_rows($user_query);
	if($numrows < 1){
		echo "<b style='text-align: center;'>That user does not exist or is not yet activated, press back</b>";
		exit();
	}

	// Check to see if the viewer is the account owner
	$isOwner = "No";
	if($u == $log_username && $user_ok == true){
		$isOwner = "Yes";
		$profile_pic_btn = '<a href="#" onclick="return false;" onmousedown="toggleElement(\'avatar_form\')">Change Avatar</a>';
		$avatar_form  = '<form id="avatar_form" enctype="multipart/form-data" method="post" action="php_parsers/photo_system.php">';
		$avatar_form .=   '<h4>Change your avatar</h4>';
		$avatar_form .=   '<input type="file" name="avatar" id="file" class="inputfile" required>';
		$avatar_form .=   '<label for="file">Choose a file</label>';
		$avatar_form .=   '<p><input type="submit" value="Upload"></p>';
		$avatar_form .= '</form>';
	}

	// Fetch the user row from the query above
	while($row = mysqli_fetch_array($user_query, MYSQLI_ASSOC)){
		$profile_id = $row["id"];
		$gender = $row["gender"];
		$country = $row["country"];
		$userlevel = $row["userlevel"];
		$signup = $row["signup"];
		$avatar = $row["avatar"];
		$lastlogin = $row["lastlogin"];
		$joindate = strftime("%b %d, %Y", strtotime($signup));
		$lastsession = strftime("%b %d, %Y", strtotime($lastlogin));
		// Get the latlon as user A
		$uAlatlon = $row["latlon"];
		$bdate = substr($row["bday"], 5, 9);
		$birthday = $row["bday"];
		$birthday_year = substr($row["bday"], 0, 4);
	}
	$is_birthday = "no";
	$today_is = date('m-d');
	if($today_is == $bdate){
		$is_birthday = "yes";
	}
	$leap = date("L");
	if($leap == '0' && $today_is == "02-28" && $bdate == '02-29'){
		$is_birthday = "yes";
	}

	if($gender == "f"){
		$sex = "Female";
	}
	$profile_pic = '<img src="user/'.$u.'/'.$avatar.'" alt="'.$u.'">';

	if($avatar == NULL){
		$profile_pic = '<img src="images/avatardefault.png">';
	}

	$current_year = date("Y");
	$age = $current_year - $birthday_year;

	// Get the latlon as user B
	$uBlatlon = "";
	if(isset($log_username)){
		$result = mysqli_query($conn, "SELECT latlon FROM users WHERE username='$log_username' LIMIT 1");
		while($row = mysqli_fetch_row($result)){
			$uBlatlon = $row[0];
		}
	}

	$totalDist = "";
	if(($uAlatlon != "") && ($uBlatlon != "")){
		$totalDist = findDistance($uAlatlon,$uBlatlon);
	}

	if($userlevel == "a"){
		$userlevel = "Verified";
	}else if($userlevel == "b"){
		$userlevel = "Not Verified";
	}else{
		$userlevel = "Not verified";
	}
?>
<?php
	$isFriend = false;
	$ownerBlockViewer = false;
	$viewerBlockOwner = false;
	if($u != $log_username && $user_ok == true){
		$friend_check = "SELECT id FROM friends WHERE user1='$log_username' AND user2='$u' AND accepted='1' OR user1='$u' AND user2='$log_username' AND accepted='1' LIMIT 1";
	if(mysqli_num_rows(mysqli_query($conn, $friend_check)) > 0){
        $isFriend = true;
    }
	$block_check1 = "SELECT id FROM blockedusers WHERE blocker='$u' AND blockee='$log_username' LIMIT 1";
	if(mysqli_num_rows(mysqli_query($conn, $block_check1)) > 0){
        $ownerBlockViewer = true;
    }
	$block_check2 = "SELECT id FROM blockedusers WHERE blocker='$log_username' AND blockee='$u' LIMIT 1";
	if(mysqli_num_rows(mysqli_query($conn, $block_check2)) > 0){
        	$viewerBlockOwner = true;
    	}
	}
?>
<?php 
	$friend_button = '<button style="opacity: 0.6; cursor: not-allowed;">Request As Friend</button>';
	$block_button = '<button style="opacity: 0.6; cursor: not-allowed;">Block User</button>';

	// LOGIC FOR FRIEND BUTTON
	if($isFriend == true){
		$friend_button = '<button onclick="friendToggle(\'unfriend\',\''.$u.'\',\'friendBtn\')">Unfriend </button>';
	} else if($user_ok == true && $u != $log_username && $ownerBlockViewer == false){
		$friend_button = '<button onclick="friendToggle(\'friend\',\''.$u.'\',\'friendBtn\')">Request As Friend </button>';
	}

	// LOGIC FOR BLOCK BUTTON
	if($viewerBlockOwner == true){
		$block_button = '<button onclick="blockToggle(\'unblock\',\''.$u.'\',\'blockBtn\')">Unblock User </button>';
	} else if($user_ok == true && $u != $log_username){
		$block_button = '<button onclick="blockToggle(\'block\',\''.$u.'\',\'blockBtn\')">Block User </button>';
	}
?>
<?php
	$friendsHTML = '';
	$friends_view_all_link = '';
	$sql = "SELECT COUNT(id) FROM friends WHERE user1='$u' AND accepted='1' OR user2='$u' AND accepted='1'";
	$query = mysqli_query($conn, $sql);
	$query_count = mysqli_fetch_row($query);
	$friend_count = $query_count[0];
	if($friend_count < 1){
		$friendsHTML = '<b>'.$u." has no friends yet.</b>";
	} else {
		$max = 14;
		$all_friends = array();
		$sql = "SELECT user1 FROM friends WHERE user2='$u' AND accepted='1' ORDER BY RAND() LIMIT $max";
		$query = mysqli_query($conn, $sql);
		while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
			array_push($all_friends, $row["user1"]);
		}
		$sql = "SELECT user2 FROM friends WHERE user1='$u' AND accepted='1' ORDER BY RAND() LIMIT $max";
		$query = mysqli_query($conn, $sql);
		while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
			array_push($all_friends, $row["user2"]);
		}
		$friendArrayCount = count($all_friends);
		if($friendArrayCount > $max){
			array_splice($all_friends, $max);
		}
		if($friend_count > $max){
			$friends_view_all_link = '<a href="view_friends.php?u='.$u.'">View all</a>';
		}
		$orLogic = '';
		foreach($all_friends as $key => $user){
				$orLogic .= "username='$user' OR ";
		}
		$orLogic = chop($orLogic, "OR ");
		$sql = "SELECT username, avatar FROM users WHERE $orLogic";
		$query = mysqli_query($conn, $sql);
		while($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
			$friend_username = $row["username"];
			$friend_avatar = $row["avatar"];
			if($friend_avatar != ""){
				$friend_pic = 'user/'.$friend_username.'/'.$friend_avatar.'';
			} else {
				$friend_pic = 'images/avatardefault.png';
			}
			$friendsHTML .= '<a href="user.php?u='.$friend_username.'"><img class="friendpics" src="'.$friend_pic.'" alt="'.$friend_username.'" title="'.$friend_username.'"></a>';
		}
	}

	// Create the photos button
	$photos_btn = "<button onclick='window.location = 'photos.php?u=<?php echo $u; ?>View Photos</button>";

	// Create the random photos
	$coverpic = "";
	$photo_title = "";
	$sql = "SELECT filename FROM photos WHERE user='$u' ORDER BY RAND()";
	$query = mysqli_query($conn, $sql);
	if(mysqli_num_rows($query) > 0){
		$row = mysqli_fetch_row($query);
		$filename = $row[0];
		$coverpic = '<img src="user/'.$u.'/'.$filename.'" alt="Photo">';
	}
?>
<?php
	$job = "";
	$schools = "";
	$about = "";
	$works = "";
	$profession = "";
	$city = "";
	$state = "";
	// Gather more informations about user
	$sql = "SELECT * FROM edit WHERE username='$u'";
	$query = mysqli_query($conn, $sql);
	$numrows = mysqli_num_rows($query);
	if($numrows > 0){
		while($row = mysqli_fetch_array($query, MYSQLI_ASSOC)){
			$job = $row["job"];
			$schools = $row["schools"];
			$about = $row["about"];
			$profession = $row["profession"];
			$state = $row["state"];
			$city = $row["city"];
		}
		if($profession == "w"){
			$works = "Working";
		}else if($profession == "r"){
			$works = "Retired";
		}else if($profession == "u"){
			$works = "Unemployed";
		}else if($profession == "o"){
			$works = "Other";
		}else{
			$works = "Student";
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>User Profile - <?php echo $u; ?></title>
	<meta charset="utf-8">
	<link rel="icon" type="image/x-icon" href="images/logo.png">
	<link rel="stylesheet" type="text/css" href="style/style.css">
	<script src="js/main.js"></script>
	<script src="js/ajax.js"></script>
	<script type="text/javascript">
		function friendToggle(e,n,t){_(t).innerHTML='<img src="images/rolling.gif" width="30" height="30">';var o=ajaxObj("POST","php_parsers/friend_system.php");o.onreadystatechange=function(){1==ajaxReturn(o)&&("friend_request_sent"==o.responseText?_(t).innerHTML="OK Friend Request Sent":"unfriend_ok"==o.responseText?_(t).innerHTML="<button onclick=\"friendToggle('friend','<?php echo $u; ?>','friendBtn')\">Request As Friend</button>":(alert(o.responseText),_(t).innerHTML="Try again later"))},o.send("type="+e+"&user="+n)}function blockToggle(e,n,t){(t=document.getElementById(t)).innerHTML='<img src="images/rolling.gif" width="30" height="30">';var o=ajaxObj("POST","php_parsers/block_system.php");o.onreadystatechange=function(){1==ajaxReturn(o)&&("blocked_ok"==o.responseText?t.innerHTML="<button onclick=\"blockToggle('unblock','<?php echo $u; ?>','blockBtn')\">Unblock User</button>":"unblocked_ok"==o.responseText?t.innerHTML="<button onclick=\"blockToggle('block','<?php echo $u; ?>','blockBtn')\">Block User</button>":(alert(o.responseText),t.innerHTML="Try again later!"))},o.send("type="+e+"&blockee="+n)}

		function openUserEdit(){
			if(_("editprofileform").style.display === "none"){
				_("editprofileform").style.display = "block";
				_("userEditBtn").style.backgroundColor = "#ad5038";
			}else{
				_("editprofileform").style.display = "none";
				_("userEditBtn").style.backgroundColor = "#763626";
			}
		}

		function statusMax(field, maxlimit) {
			if (field.value.length > maxlimit){
				alert(maxlimit+" maximum character limit reached");
				field.value = field.value.substring(0, maxlimit);
			}
		}

		function emptyElement(x){
			_(x).innerHTML = "";
		}

		function editChanges(){
			var status = _("status");
			var job = _("job").value;
			var schools = _("schools").value;
			var ta = _("ta").value;
			var pro = _("profession").value;
			var city = _("city").value;
			var state = _("state").value;

			if(job == "" && schools == "" && ta == "" && pro == "" && city == "" && state == ""){
				status.innerHTML = "Please fill in at least 1 field";
			}else{
				_("editbtn").style.display = "none";
				status.innerHTML = '<img src="images/rolling.gif" width="30" height="30">';
				var ajax = ajaxObj("POST", "php_parsers/edit_parser.php");
				ajax.onreadystatechange = function(){
					if(ajaxReturn(ajax) == true){
						if(ajax.responseText != "edit_success"){
							status.innerHTML = ajax.responseText;
							_("editbtn").style.display = "block";
						}else{
							_("editprofileform").innerHTML = "<p class='success_green'>Your changes has been saved successfully</p>";
						}
					}
				}
				ajax.send("job="+job+"&schools="+schools+"&ta="+ta+"&pro="+pro+"&city="+city+"&state="+state);
			}
		}
	</script>
</head>
<body>
	<?php include_once("template_pageTop.php"); ?>
	<div id="pageMiddle_2">
	  <div id="profile_pic_box"><?php echo $profile_pic_btn; ?><?php echo $avatar_form; ?><?php echo $profile_pic; ?></div>
	   <div id="photo_showcase" onclick="window.location = 'photos.php?u=<?php echo $u; ?>';" title="View <?php echo $u; ?>&#39;s photo galleries">
    	<?php echo $coverpic; ?>
    	</div>
	  <h2><?php echo $u; ?></h2>
	  <p>Is the viewer the page owner logged in and verified? <b><?php echo $isOwner; ?></b></p>
	  <p><b>Gender: </b><?php echo $sex; ?></p>
	  <p><b>Country: </b><?php echo $country; ?><?php require_once 'template_country_flags.php'; ?></p>
	  <?php if($state != ""){ ?>
      	<p><b>State/Province: </b><?php echo $state; ?></p>
	  <?php } ?>
	  <?php if($city != ""){ ?>
      	<p><b>City/Town: </b><?php echo $city; ?></p>
	  <?php } ?>
	  <p><b>User Security: </b> <?php echo $userlevel; ?></p>
      <p><b>Sign Up Date: </b> <?php echo $joindate; ?></p>
      <p><b>Last Log in: </b> <?php echo $lastsession; ?></p>
      <p><b>Birthday: </b> <?php echo $birthday; ?></p>
      <p><b>Age: </b><?php echo $age; ?></p>
      <?php if($job != ""){ ?>
      	<p><b>Job: </b><?php echo $job; ?></p>
	  <?php } ?>
	  <?php if($schools != ""){ ?>
      	<p><b>School: </b><?php echo $schools; ?></p>
	  <?php } ?>
	  <?php if($about != ""){ ?>
      	<p><b>About me: </b><?php echo $about; ?></p>
	  <?php } ?>
	  <?php if($profession != ""){ ?>
      	<p><b>Profession: </b><?php echo $works; ?></p>
	  <?php } ?>
	  <!--<p><b>This user lives </b><?php echo $totalDist; ?> from you</p>-->
	  <?php if($log_username == $u && $user_ok == true){ ?>
	  	<button onclick="openUserEdit()" id="userEditBtn">Edit Profile</button>
	  <?php } ?>

	  <form name="editprofileform" id="editprofileform" onsubmit="return false;">
	  	<div class="editformtitle">Job:</div>
	  	<input id="job" type="text" placeholder="Engineer at IBM" onfocus="emptyElement('status')">
	  	<div class="editformtitle">Schools:</div>
	  	<input id="schools" type="text" placeholder="University of Florida" onfocus="emptyElement('status')">
	  	<div class="editformtitle">State/Province:</div>
	  	<input id="state" type="text" placeholder="California" onfocus="emptyElement('status')">
	  	<div class="editformtitle">City/Town:</div>
	  	<input id="city" type="text" placeholder="Los Angeles" onfocus="emptyElement('status')">
	  	<div class="editformtitle">About me:</div>
	  	<textarea id="ta" onkeyup="statusMax(this,750)" placeholder="I like watching TV, swimming ..." onfocus="emptyElement('status')"></textarea>
	  	<div id="editformradio">What do you do?</div>
	  	<select id="profession" onfocus="emptyElement('status')">
	  		<option value=""></option>
	  		<option value="s">Student</option>
	  		<option value="w">Working</option>
	  		<option value="r">Retired</option>
	  		<option value="u">Unemployed</option>
	  		<option value="o">Other</option>
	  	</select><br /><br />
	  	<button id="editbtn" onclick="editChanges()">Submit</button>
	  	<div id="status"></div>
	  </form>
	  <hr>
  		<p>Add <?php echo $u; ?> as a friend: <span id="friendBtn"><?php echo $friend_button; ?></span><?php echo '<b> '.$u." has ".$friend_count." friends</b>"; ?> <?php echo $friends_view_all_link; ?></p>
  		<p>Block <?php echo $u; ?>: <span id="blockBtn"><?php echo $block_button; ?></span></p>
  		<?php if($u == $log_username && $user_ok == true){ ?>
  			<p><a href="more_friends.php">Find More Friends</a></p>
  		<?php } ?>
  	  <hr>
  	  <p>Photo Gallery: <button onclick="window.location = 'photos.php?u=<?php echo $u; ?>'">View Photos</button></p>
  	  <p>You can check your own and your friends photos, all in one place, in the photo gallery.</p>
  	  <hr>
  	  <?php if($is_birthday == "yes" && $u == $log_username && $user_ok == true){ 
  	  		echo '<img src="images/bd.gif" id="hb_img">';
  	  	}
  	  ?>
  	  <?php if($u == $log_username && $user_ok == true){ ?>
  	  <div id="groupModule"></div>
  	  <?php } ?>
  	  <h3><?php echo $u; ?>'s friends (<?php echo $friend_count; ?>)</h3>
  	  <p><?php echo $friendsHTML; ?></p>
  	  <?php require_once 'template_pm.php'; ?>
  	  <hr>
  	  <?php require_once 'template_status.php'; ?>
	</div>
	<!--<?php require_once 'template_pageBottom.php'; ?>-->
</body>
</html>