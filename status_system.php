<?php
include_once("../php_includes/check_login_statues.php");
if($user_ok != true || $log_username == "") {
	exit();
}
?>
<?php
if (isset($_POST['action']) && $_POST['action'] == "status_post"){
	// Make sure post data and image is not empty
	if(strlen($_POST['data']) < 1 && $_POST['image'] == "na"){
		mysqli_close($conn);
	    echo "data_empty";
	    exit();
	}
	$image = preg_replace('#[^a-z0-9.]#i', '', $_POST['image']);
	// Move the image(s) to the permanent folder
	if($image != "na"){
		$kaboom = explode(".", $image);
		$fileExt = end($kaboom);
		rename("../tempUploads/$image", "../permUploads/$image");
		require_once '../php_includes/image_resize.php';
		$target_file = "../permUploads/$image";
		$resized_file = "../permUploads/$image";
		$wmax = 600;
		$hmax = 700;
		list($width, $height) = getimagesize($target_file);
		if($width > $wmax || $height > $hmax){
			img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
		}
	}
	// Make sure type is either a or c
	// if($_POST['type'] != "a" || $_POST['type'] != "c"){
	if($_POST['type'] != ("a" || "c")){
		mysqli_close($conn);
	    echo "type_unknown";
	    exit();
	}

	// Clean all of the $_POST vars that will interact with the database
	$type = preg_replace('#[^a-z]#', '', $_POST['type']);
	$account_name = preg_replace('#[^a-z0-9]#i', '', $_POST['user']);
	$data = htmlentities($_POST['data']);
	// We just have an image
	if($data == "||na||" && $image != "na"){
		$data = '<img src="permUploads/'.$image.'" />';
	// We have an image and text
	}else if($data != "||na||" && $image != "na"){
		$data = $data.'<br /><img src="permUploads/'.$image.'" />';
	}
	
	$data = mysqli_real_escape_string($conn, $data);
	// Make sure account name exists (the profile being posted on)
	$sql = "SELECT COUNT(id) FROM users WHERE username='$account_name' AND activated='1' LIMIT 1";
	$query = mysqli_query($conn, $sql);
	$row = mysqli_fetch_row($query);
	if($row[0] < 1){
		mysqli_close($conn);
		echo "$account_no_exist";
		exit();
	}
	// Insert the status post into the database now
	$sql = "INSERT INTO status(account_name, author, type, data, postdate) 
			VALUES('$account_name','$log_username','$type','$data',now())";
	$query = mysqli_query($conn, $sql);
	$id = mysqli_insert_id($conn);
	mysqli_query($conn, "UPDATE status SET osid='$id' WHERE id='$id' LIMIT 1");
	// Count posts of type "a" for the person posting and evaluate the count
	$sql = "SELECT COUNT(id) FROM status WHERE author='$log_username' AND type='a'";
    $query = mysqli_query($conn, $sql); 
	$row = mysqli_fetch_row($query);
	// Insert notifications to all friends of the post author
	$friends = array();
	$query = mysqli_query($conn, "SELECT user1 FROM friends WHERE user2='$log_username' AND accepted='1'");
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) { array_push($friends, $row["user1"]); }
	$query = mysqli_query($conn, "SELECT user2 FROM friends WHERE user1='$log_username' AND accepted='1'");
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) { array_push($friends, $row["user2"]); }
	for($i = 0; $i < count($friends); $i++){
		$friend = $friends[$i];
		$app = "Status Post";
		$note = $log_username.' posted on: <br /><a href="user.php?u='.$account_name.'#status_'.$id.'">'.$account_name.'&#39;s Profile</a>';
		mysqli_query($conn, "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES('$friend','$log_username','$app','$note',now())");			
	}
	mysqli_close($conn);
	echo "post_ok|$id";
	exit();
}
?><?php 
//action=status_reply&osid="+osid+"&user="+user+"&data="+data
if (isset($_POST['action']) && $_POST['action'] == "status_reply"){
	// Make sure data is not empty
	if(strlen($_POST['data']) < 1 && $_POST['image'] == "na"){
		mysqli_close($conn);
	    echo "data_empty";
	    exit();
	}

	$image = preg_replace('#[^a-z0-9.]#i', '', $_POST['image']);
	// Move the image(s) to the permanent folder
	if($image != "na"){
		$kaboom = explode(".", $image);
		$fileExt = end($kaboom);
		rename("../tempUploads/$image", "../permUploads/$image");
		require_once '../php_includes/image_resize.php';
		$target_file = "../permUploads/$image";
		$resized_file = "../permUploads/$image";
		$wmax = 600;
		$hmax = 700;
		list($width, $height) = getimagesize($target_file);
		if($width > $wmax || $height > $hmax){
			img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
		}
	}

	// Clean the posted variables
	$osid = preg_replace('#[^0-9]#', '', $_POST['sid']);
	$account_name = preg_replace('#[^a-z0-9]#i', '', $_POST['user']);
	$data = htmlentities($_POST['data']);
	// We just have an image
	if($data == "||na||" && $image != "na"){
		$data = '<img src="permUploads/'.$image.'" />';
	// We have an image and text
	}else if($data != "||na||" && $image != "na"){
		$data = $data.'<br /><img src="permUploads/'.$image.'" />';
	}
	
	$data = mysqli_real_escape_string($conn, $data);
	// Make sure account name exists (the profile being posted on)
	$sql = "SELECT COUNT(id) FROM users WHERE username='$account_name' AND activated='1' LIMIT 1";
	$query = mysqli_query($conn, $sql);
	$row = mysqli_fetch_row($query);
	if($row[0] < 1){
		mysqli_close($conn);
		echo "$account_no_exist";
		exit();
	}
	// Insert the status reply post into the database now
	$sql = "INSERT INTO status(osid, account_name, author, type, data, postdate)
	        VALUES('$osid','$account_name','$log_username','b','$data',now())";
	$query = mysqli_query($conn, $sql);
	$id = mysqli_insert_id($conn);
	// Insert notifications for everybody in the conversation except this author
	$sql = "SELECT author FROM status WHERE osid='$osid' AND author!='$log_username' GROUP BY author";
	$query = mysqli_query($conn, $sql);
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
		$participant = $row["author"];
		$app = "Status Reply";
		$note = $log_username.' commented here:<br /><a href="user.php?u='.$account_name.'#status_'.$osid.'">Click here to view the conversation</a>';
		mysqli_query($conn, "INSERT INTO notifications(username, initiator, app, note, date_time) 
		             VALUES('$participant','$log_username','$app','$note',now())");
	}
	mysqli_close($conn);
	echo "reply_ok|$id";
	exit();
}
?><?php 
if (isset($_POST['action']) && $_POST['action'] == "delete_status"){
	if(!isset($_POST['statusid']) || $_POST['statusid'] == ""){
		mysqli_close($conn);
		echo "status id is missing";
		exit();
	}
	$statusid = preg_replace('#[^0-9]#', '', $_POST['statusid']);
	// Check to make sure this logged in user actually owns that comment
	$query = mysqli_query($conn, "SELECT account_name, author, data FROM status WHERE id='$statusid' LIMIT 1");
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
		$account_name = $row["account_name"]; 
		$author = $row["author"];
		$data = $row["data"];
	}
    if ($author == $log_username || $account_name == $log_username) {
    	// Check for images
    	if(preg_match('/<img.+src=[\'"](?P<src>.+)[\'"].*>/i', $data, $has_image)){
			$source = '../'.$has_image['src'];
			if (file_exists($source)) {
        		unlink($source);
    		}
		}
		mysqli_query($conn, "DELETE FROM status WHERE osid='$statusid'");
		mysqli_close($conn);
	    echo "delete_ok";
		exit();
	}
}
?><?php 
if (isset($_POST['action']) && $_POST['action'] == "delete_reply"){
	if(!isset($_POST['replyid']) || $_POST['replyid'] == ""){
		mysqli_close($conn);
		exit();
	}
	$replyid = preg_replace('#[^0-9]#', '', $_POST['replyid']);
	// Check to make sure the person deleting this reply is either the account owner or the person who wrote it
	$query = mysqli_query($conn, "SELECT osid, account_name, author FROM status WHERE id='$replyid' LIMIT 1");
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
		$osid = $row["osid"];
		$account_name = $row["account_name"];
		$author = $row["author"];
	}
    if ($author == $log_username || $account_name == $log_username) {
		mysqli_query($conn, "DELETE FROM status WHERE id='$replyid'");
		mysqli_close($conn);
	    echo "delete_ok";
		exit();
	}
}
?>
<?php
	if(isset($_POST['action']) && $_POST['action'] == "like"){
		if(!isset($_POST['id'])){
			mysql_close($conn);
			echo "fail";
			exit();
		}
		$id = preg_replace('#[^0-9]#', '', $_POST['id']);
		if($id == ""){
			mysqli_close($conn);
			echo "fail";
			exit();
		}
		$sql = "SELECT author, data FROM status WHERE id='$id' LIMIT 1";
		$query = mysqli_query($conn, $sql);
		$numrows = mysqli_num_rows($query);
		if($numrows < 1){
			mysqli_close($conn);
			echo "fail";
			exit();
		}
		$user_id = "";
		$row = mysqli_fetch_row($query);
		$sql = "SELECT * FROM users WHERE username='$log_username' LIMIT 1";
		$query = mysqli_query($conn, $sql);
		while($row = mysqli_fetch_array($query, MYSQLI_ASSOC)){
			$user_id = $row["id"];
		}
		$sql = "
				INSERT INTO status_likes (user,status)
					SELECT {$user_id}, {$id}
					FROM status
					WHERE EXISTS(
						SELECT id
						FROM status
						WHERE id = {$id})
					AND NOT EXISTS(
						SELECT id
						FROM status_likes
						WHERE user = {$user_id}
						AND status = {$id})
					LIMIT 1
				";
		$query = mysqli_query($conn, $sql);
		mysqli_close($conn);
		exit();
	}
?>
<?php
	if(isset($_POST['action']) && $_POST['action'] == "share"){
		if(!isset($_POST['id'])){
			mysql_close($conn);
			echo "fail";
			exit();
		}
		$id = preg_replace('#[^0-9]#', '', $_POST['id']);
		if($id == ""){
			mysqli_close($conn);
			echo "fail";
			exit();
		}
		$sql = "SELECT author, data FROM status WHERE id='$id' LIMIT 1";
		$query = mysqli_query($conn, $sql);
		$numrows = mysqli_num_rows($query);
		if($numrows < 1){
			mysqli_close($conn);
			echo "fail";
			exit();
		}
		$row = mysqli_fetch_row($query);
		// $row[0] = $row["author"];
		// $row[1] = $row["data"];
		$data = '<br /><br /><b id="share_b">Shared via <a href="user.php?u='.$row[0].'">'.$row[0].'</a></b><br /><hr><br />';
		$data .= '<div id="share_data">'.$row[1].'</div>';
		$sql = "INSERT INTO status(account_name, author, type, data, postdate)
				VALUES('$log_username', '$log_username', 'a', '$data', NOW())";
		$query = mysqli_query($conn, $sql);
		$id = mysqli_insert_id($conn);
 		mysqli_query($conn, "UPDATE status SET osid='$id' WHERE id='$id' LIMIT 1");
		mysqli_close($conn);
		echo "share_ok";
		exit();
	}
?>