<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'user.php';
	$status_ui = "";
	$statuslist = "";
	/* OLD ONE 
	if($isOwner == "Yes"){
		$status_ui = '<textarea id="statustext" placeholder="What&#39;s new with you '.$u.'..."></textarea>';
		$status_ui .= '<button id="statusBtn" onclick="postToStatus(\'status_post\',\'a\',\''.$u.'\',\'statustext\')">Post</button>';
	} else if($isFriend == true && $log_username != $u){
		$status_ui = '<textarea id="statustext" placeholder="Hi '.$log_username.', say something to '.$u.'"></textarea>';
		$status_ui .= '<button id="statusBtn" onclick="postToStatus(\'status_post\',\'c\',\''.$u.'\',\'statustext\')">Post</button>';
	}*/

	// NEW ONE
	if($isOwner == "Yes"){
		$status_ui = '<textarea id="statustext" onfocus="showBtnDiv()" placeholder="What&#39;s new with you '.$u.'?"></textarea>';
		$status_ui .= '<div id="uploadDisplay_SP"></div>';
		$status_ui .= '<div id="btns_SP" class="hiddenStuff">';
			$status_ui .= '<button id="statusBtn" onclick="postToStatus(\'status_post\',\'a\',\''.$u.'\',\'statustext\')">Post</button>';
			$status_ui .= '<img src="images/camera.png" id="triggerBtn_SP" class="triggerBtn" onclick="triggerUpload(event, \'fu_SP\')" width="22" height="22" title="Upload A Photo" />';
		$status_ui .= '</div>';
		$status_ui .= '<div id="standardUpload" class="hiddenStuff">';
			$status_ui .= '<form id="image_SP" enctype="multipart/form-data" method="post">';
			$status_ui .= '<input type="file" name="FileUpload" id="fu_SP" onchange="doUpload(\'fu_SP\')"/>';
			$status_ui .= '</form>';
		$status_ui .= '</div>';
	} else if($isFriend == true && $log_username != $u){
		$status_ui = '<textarea id="statustext" onfocus="showBtnDiv()" placeholder="Hi '.$log_username.', say something to '.$u.'"></textarea>';
		$status_ui .= '<div id="uploadDisplay_SP"></div>';
		$status_ui .= '<div id="btns_SP" class="hiddenStuff">';
			$status_ui .= '<button id="statusBtn" onclick="postToStatus(\'status_post\',\'a\',\''.$u.'\',\'statustext\')">Post</button>';
			$status_ui .= '<img src="images/camera.png" id="triggerBtn_SP" class="triggerBtn" onclick="triggerUpload(event, \'fu_SP\')" width="22" height="22" title="Upload A Photo" />';
		$status_ui .= '</div>';
		$status_ui .= '<div id="standardUpload" class="hiddenStuff">';
			$status_ui .= '<form id="image_SP" enctype="multipart/form-data" method="post">';
			$status_ui .= '<input type="file" name="FileUpload" id="fu_SP" onchange="doUpload(\'fu_SP\')"/>';
			$status_ui .= '</form>';
		$status_ui .= '</div>';
	}

	?>
	<?php
		$sql = "SELECT s.*, u.avatar 
		FROM status AS s 
		LEFT JOIN users AS u ON u.username = s.author
		WHERE (s.account_name='$u' AND s.type='a') 
		OR (s.account_name='$u' AND s.type='c') 
		ORDER BY s.postdate";
	?>
	<?php 
	$query = mysqli_query($conn, $sql);
	$statusnumrows = mysqli_num_rows($query);
	while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
		$statusid = $row["id"];
		$account_name = $row["account_name"];
		$author = $row["author"];
		$postdate = $row["postdate"];
		$avatar = $row["avatar"];
		$user_image = '<a href="user.php?u='.$author.'"><img class="round" src="user/'.$author.'/'.$avatar.'" width="50" height="50" border="0" title="'.$author.'"/></a>';
		if($avatar == NULL){
			$user_image = '<a href="user.php?u='.$author.'"><img class="round" src="images/avatardefault.png" width="50" height="50" border="0" title="'.$author.'"/></a>';
		}
		$data = $row["data"];
		$data = nl2br($data);
		$data = str_replace("&amp;","&",$data);
		$data = stripslashes($data);
		$statusDeleteButton = '';
		if($author == $log_username || $account_name == $log_username ){
			$statusDeleteButton = '<span id="sdb_'.$statusid.'"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" onclick="return false;" onmousedown="deleteStatus(\''.$statusid.'\',\'status_'.$statusid.'\');" title="Delete Post And Its Replies">Delete Post</button></span> &nbsp; &nbsp;';
		}
		// Add share button
		$shareButton = "";
		if($log_username != "" && $author != $log_username && $account_name != $log_username){
			$shareButton = '<div id="share_btn"><b id="text_weight"><a href="#" id="link_color" onclick="return false;" onmousedown="shareStatus(\''.$statusid.'\');" title="Share this post">Share</a></b></div>';
		}

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

		// GATHER UP ANY STATUS REPLIES
		$status_replies = "";
		$sql2 = "SELECT s.*, u.avatar 
				FROM status AS s 
				LEFT JOIN users AS u ON u.username = s.author
				WHERE s.osid = '$statusid' 
				AND s.type='b' 
				ORDER BY postdate ASC";

		$query_replies = mysqli_query($conn, $sql2);
		$replynumrows = mysqli_num_rows($query_replies);
	    if($replynumrows > 0){
	        while ($row2 = mysqli_fetch_array($query_replies, MYSQLI_ASSOC)) {
				$statusreplyid = $row2["id"];
				$replyauthor = $row2["author"];
				$replydata = $row2["data"];
				$avatar2 = $row2["avatar"];
				$user_image2 = '<a href="user.php?u='.$author.'"><img id="round_2" src="user/'.$replyauthor.'/'.$avatar2.'" width="50" height="50" border="0" title="'.$replyauthor.'"/></a>';
				if($avatar2 == NULL){
					$user_image2 = '<a href="user.php?u='.$author.'"><img id="round_2" src="images/avatardefault.png" width="50" height="50" border="0" title="'.$replyauthor.'"/></a>';
				}
				$replydata = nl2br($replydata);
				$replypostdate = $row2["postdate"];
				$replydata = str_replace("&amp;","&",$replydata);
				$replydata = stripslashes($replydata);
				$replyDeleteButton = '';
				if($replyauthor == $log_username || $account_name == $log_username ){
					$replyDeleteButton = '<span id="srdb_'.$statusreplyid.'"><button onclick="Confirm.render("Delete Post?","delete_post","post_1")" class="delete_s" href="#" onclick="return false;" onmousedown="deleteReply(\''.$statusreplyid.'\',\'reply_'.$statusreplyid.'\');" title="Delete Comment">Delete Reply</button ></span>';
				}
				$status_replies .= '
				<div id="reply_'.$statusreplyid.'" class="reply_boxes">
					<div>'.$replyDeleteButton.'<p id="float"><b>Reply: </b>'.$replypostdate.'</p>'.$user_image2.'<p id="reply_text">'.$replydata.'</p>
					</div>
				</div>';
	        }
	    }
			$statuslist .= '<div id="status_'.$statusid.'" class="status_boxes"><div>'.$statusDeleteButton.'<p id="status_date"><b>Post: </b>'.$postdate.'</p>'.$user_image.'<p id="status_text">'.$data.'</p>'.$shareButton.''.$likeButton.'
			</div>'.$status_replies.'
			</div>';
		if($isFriend == true || $log_username == $u){
		    $statuslist .= '<textarea id="replytext_'.$statusid.'" class="replytext" onfocus="showBtnDiv_reply('.$statusid.')" placeholder="Write a comment..."></textarea>';
			$statuslist .= '<div id="uploadDisplay_SP_reply_'.$statusid.'"></div>';
			$statuslist .= '<div id="btns_SP_reply_'.$statusid.'" class="hiddenStuff">';
				$statuslist .= '<button id="replyBtn_'.$statusid.'" class="btn_rply" onclick="replyToStatus('.$statusid.',\''.$u.'\',\'replytext_'.$statusid.'\',this)">Reply</button>';
				$statuslist .= '<img src="images/camera.png" id="triggerBtn_SP_reply" class="triggerBtn" onclick="triggerUpload_reply(event, \'fu_SP_reply\')" width="22" height="22" title="Upload A Photo" />';
			$statuslist .= '</div>';
			$statuslist .= '<div id="standardUpload_reply" class="hiddenStuff">';
				$statuslist .= '<form id="image_SP_reply" enctype="multipart/form-data" method="post">';
				$statuslist .= '<input type="file" name="FileUpload" id="fu_SP_reply" onchange="doUpload_reply(\'fu_SP_reply\', '.$statusid.')"/>';
				$statuslist .= '</form>';
			$statuslist .= '</div>';	
			}
		}
?>
<script>
	function deleteStatus(e,t){if(1!=confirm("Press OK to confirm deletion of this status and its replies"))return!1;var s=ajaxObj("POST","php_parsers/status_system.php");s.onreadystatechange=function(){1==ajaxReturn(s)&&("delete_ok"==s.responseText?(_(t).style.display="none",_("replytext_"+e).style.display="none",_("replyBtn_"+e).style.display="none"):alert(s.responseText))},s.send("action=delete_status&statusid="+e)}function deleteReply(e,t){if(1!=confirm("Press OK to confirm deletion of this reply"))return!1;var s=ajaxObj("POST","php_parsers/status_system.php");s.onreadystatechange=function(){1==ajaxReturn(s)&&("delete_ok"==s.responseText?_(t).style.display="none":alert(s.responseText))},s.send("action=delete_reply&replyid="+e)}

	var hasImage = "";
	window.onbeforeunload = function(){
		if(hasImage != ""){
		    return "You have not posted your image";
		}
	}

	function showBtnDiv(){
		_("statustext").style.height = "150px";
		_("btns_SP").style.display = "block";
	}

	function showBtnDiv_reply(e){
		_("replytext_"+e).style.height = "130px";
		_("btns_SP_reply_"+e).style.display = "block";
	}

	function doUpload(id){
		var file = _(id).files[0];
		if(file.name == ""){
			return false;		
		}
		if(file.type != "image/jpeg" && file.type != "image/gif" && file.type != "image/png" && file.type != "image/jpg"){
			alert("That file type is not supported.");
			return false;
		}
		_("triggerBtn_SP").style.display = "none";
		_("uploadDisplay_SP").innerHTML = '<img src="images/rolling.gif" width="30" height="30">';
		var formdata = new FormData();
		formdata.append("stPic", file);
		var ajax = new XMLHttpRequest();
		ajax.addEventListener("load", completeHandler, false);
		ajax.addEventListener("error", errorHandler, false);
		ajax.addEventListener("abort", abortHandler, false);
		ajax.open("POST", "php_parsers/photo_system.php");
		ajax.send(formdata);	
	}
	function completeHandler(event){
		var data = event.target.responseText;
		var datArray = data.split("|");
		if(datArray[0] == "upload_complete"){
			hasImage = datArray[1];
			_("uploadDisplay_SP").innerHTML = '<img src="tempUploads/'+datArray[1]+'" class="statusImage" />';
		} else {
			_("uploadDisplay_SP").innerHTML = datArray[0];
			_("triggerBtn_SP").style.display = "block";
		}
	}
	function errorHandler(event){
		_("uploadDisplay_SP").innerHTML = "Upload Failed";
		_("triggerBtn_SP").style.display = "block";
	}
	function abortHandler(event){
		_("uploadDisplay_SP").innerHTML = "Upload Aborted";
		_("triggerBtn_SP").style.display = "block";
	}

	function doUpload_reply(id,e){
		var file = _(id).files[0];
		if(file.name == ""){
			return false;		
		}
		if(file.type != "image/jpeg" && file.type != "image/gif" && file.type != "image/png" && file.type != "image/jpg"){
			alert("That file type is not supported.");
			return false;
		}
		_("triggerBtn_SP_reply").style.display = "none";
		_("uploadDisplay_SP_reply_"+e).innerHTML = '<img src="images/rolling.gif" width="30" height="30">';
		var formdata = new FormData();
		formdata.append("stPic_reply", file);
		var ajax = new XMLHttpRequest();
		ajax.addEventListener("load", function(event){
		    completeHandler_reply.call(this,e);
		}, false );
		ajax.addEventListener("error", errorHandler_reply, false);
		ajax.addEventListener("abort", abortHandler_reply, false);
		ajax.open("POST", "php_parsers/photo_system.php");
		ajax.send(formdata);	
	}

	function completeHandler_reply(event,e){
		var data = event.target.responseText;
		var datArray = data.split("|");
		if(datArray[0] == "upload_complete"){
			hasImage = datArray[1];
			_("uploadDisplay_SP_reply_"+e).innerHTML = '<img src="tempUploads/'+datArray[1]+'" class="statusImage" />';
		} else {
			_("uploadDisplay_SP_reply_"+e).innerHTML = datArray[0];
			_("triggerBtn_SP_reply").style.display = "block";
		}
	}

	function errorHandler_reply(event){
		_("uploadDisplay_SP_reply_").innerHTML = "Upload Failed";
		_("triggerBtn_SP_reply").style.display = "block";
	}

	function abortHandler_reply(event){
		_("uploadDisplay_SP_reply").innerHTML = "Upload Aborted";
		_("triggerBtn_SP_reply").style.display = "block";
	}

	function triggerUpload(e,elem){
		e.preventDefault();
		_(elem).click();	
	}

	function triggerUpload_reply(e,elem){
		e.preventDefault();
		_(elem).click();
	}

	function postToStatus(action,type,user,ta){
		var data = _(ta).value;
		if(data == "" && hasImage == ""){
			alert("Type something first");
			return false;
		}
		// Just Image
		var data2 = "";
		if(data != ""){
			data2 = data.replace(/\n/g,"<br />").replace(/\r/g,"<br />");
		}
		// No text but image
		if (data2 == "" && hasImage != ""){
			data = "||na||";
			data2 = '<img src="permUploads/'+hasImage+'" />';		
		} else if (data2 != "" && hasImage != ""){
			data2 += '<br /><img src="permUploads/'+hasImage+'" />';
		} else {
			hasImage = "na";
		}
		_("statusBtn").disabled = true;
		var ajax = ajaxObj("POST", "php_parsers/status_system.php");
		ajax.onreadystatechange = function() {
			if(ajaxReturn(ajax) == true) {
				var datArray = ajax.responseText.split("|");
				if(datArray[0] == "post_ok"){
					var sid = datArray[1];
					var currentHTML = _("statusarea").innerHTML;
					_("statusarea").innerHTML = '<div id="status_'+sid+'" class="status_boxes"><div><b>Posted by you just now:</b> <span id="sdb_'+sid+'"><button onclick="return false;" class="delete_s" onmousedown="deleteStatus(\''+sid+'\',\'status_'+sid+'\');" title="Delete Status And Its Replies">Delete Status</button></span><br />'+data2+'</div></div><textarea id="replytext_'+sid+'" class="replytext" placeholder="Write a comment..."></textarea><button id="replyBtn_'+sid+'" onclick="replyToStatus('+sid+',\'<?php echo $u; ?>\',\'replytext_'+sid+'\',this)">Reply</button>'+currentHTML;
					_("statusBtn").disabled = false;
					_(ta).value = "";
					_("triggerBtn_SP").style.display = "block";
					_("btns_SP").style.display = "none";
					_("uploadDisplay_SP").innerHTML = "";
					_("statustext").style.height = "40px";
					_("fu_SP").value = "";
					hasImage = "";
				} else {
					alert(ajax.responseText);
				}
			}
		}
		ajax.send("action="+action+"&type="+type+"&user="+user+"&data="+data+"&image="+hasImage);
	}

	function replyToStatus(sid,user,ta,btn){
		var data = _(ta).value;
		if(data == "" && hasImage == ""){
			alert("Type something first");
			return false;
		}

		// Just Image
		var data2 = "";
		if(data != ""){
			data2 = data.replace(/\n/g,"<br />").replace(/\r/g,"<br />");
		}
		// No text but image
		if (data2 == "" && hasImage != ""){
			data = "||na||";
			data2 = '<img src="permUploads/'+hasImage+'" />';		
		} else if (data2 != "" && hasImage != ""){
			data2 += '<br /><img src="permUploads/'+hasImage+'" />';
		} else {
			hasImage = "na";
		}

		_("replyBtn_"+sid).disabled = true;
		var ajax = ajaxObj("POST", "php_parsers/status_system.php");
		ajax.onreadystatechange = function() {
			if(ajaxReturn(ajax) == true) {
				var datArray = ajax.responseText.split("|");
				if(datArray[0] == "reply_ok"){
					var rid = datArray[1];
					data = data.replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\n/g,"<br />").replace(/\r/g,"<br />");
					_("status_"+sid).innerHTML += '<div id="reply_'+rid+'" class="reply_boxes"><div><b>Reply by you just now:</b><span id="srdb_'+rid+'"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\''+rid+'\',\'reply_'+rid+'\');" title="Delete Comment">Delete Reply</button></span><br />'+data2+'</div></div>';
					_("replyBtn_"+sid).disabled = false;
					_(ta).value = "";
					_("triggerBtn_SP_reply").style.display = "block";
					_("btns_SP_reply_"+sid).style.display = "none";
					_("uploadDisplay_SP_reply").innerHTML = "";
					_("replytext_"+sid).style.height = "40px";
					_("fu_SP_reply").value = "";
					hasImage = "";
				} else {
					alert(ajax.responseText);
				}
			}
		}
		ajax.send("action=status_reply&sid="+sid+"&user="+user+"&data="+data+"&image="+hasImage);
	}

	function shareStatus(id){
		var ajax = ajaxObj("POST", "php_parsers/status_system.php");
		ajax.onreadystatechange = function(){
			if(ajaxReturn(ajax) == true){
				if(ajax.responseText == "share_ok"){
					alert("You've shared this post");
				}else{
					alert(ajax.responseText);
				}
			}
		}
		ajax.send("action=share&id="+id);
	}

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

<div id="statusui">
  <?php echo $status_ui; ?>
</div>
<div id="statusarea">
  <?php echo $statuslist; ?>
</div>