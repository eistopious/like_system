<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'user.php';
	$status_ui = "";
	$statuslist = "";

		// Add like button
		$likeButton = "";
		if($log_username != ""){
			$likeButton = '<a href="#" onclick="return false;" onmousedown="likeStatus('.$statusid.');" title="Like Post">Like</a>';
		}

		// Add count likes
		$statusQuery = $conn->query("
			SELECT 
			status.id,
			COUNT(status_likes.id) AS likes,
			GROUP_CONCAT(users.username SEPARATOR '|') AS liked_by
			FROM status

			LEFT JOIN status_likes
			ON status.id = status_likes.status

			LEFT JOIN users
			ON status_likes.user = users.id

			GROUP BY status.id 
		");

		while($row = $statusQuery->fetch_object()){
			$statuses[] = $row;
		}

		//echo '<pre>'.print_r($statuses,true).'</pre>';

	function likeStatus(id){
		var ajax = ajaxObj("POST", "php_parsers/status_system.php");
		ajax.onreadystatechange = function(){
			if(ajaxReturn(ajax) == true){
				if(ajax.responseText == "like_ok"){
					location.reload();
				}else{
					alert(ajax.responseText);
				}
			}
		}
		ajax.send("action=like&id="+id);
	}
</script><head>
	<link rel="stylesheet" type="text/css" href="style/style.css">
	<script src="js/dialog.js"></script>
</head>
<div id="statusarea">
  <?php 
    foreach ($statuses as $status) {
	 // Yoou can echo your html here for each status object
	 echo $status->likes;    
    }
	
   ?>
</div>
