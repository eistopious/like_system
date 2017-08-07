<?php
include_once("../php_includes/check_login_statues.php");
if($user_ok != true || $log_username == "") {
	exit();
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
?>
