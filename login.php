<table style="margin: 0 auto;">
	<?php 
	if(isset($_POST['login'])){
		$user = $_POST['user'];
		$pass = $_POST['pass'];
		$keeplog = $_POST['keeplog'];
		
		$loginResponse = $auth->login($user,$pass,$keeplog);
		
		if($loginResponse){
			header("Location: ".$_SESSION['curl1']);
			exit;
		}else{
			$loginMessage = '<div class="error">LOGIN FAILED!</div>';
		}
	}
	if(isset($loginMessage)):?>
	<tr>
		<td colspan="2"><? echo $loginMessage; ?></td>
	</tr>
	<?php endif; ?>
	<form name="loginform" action="" method="POST">
		<tr>
			<td>Username:</td>
			<td><input type="text" name="user"/></td>
		</tr>
		<tr>
			<td>Password:</td>
			<td><input type="password" name="pass"/></td>
		</tr>
		<tr>
			<td>Keep me Logged In: </td>
			<td><input type="checkbox" checked="checked" name="keeplog"/></td>
		</tr>
		<tr>
			<td colspan="2" class="text-right"><button type="Submit" name="login">Login</button></td>
		</tr>
	</form>
</table>
