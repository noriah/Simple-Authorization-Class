<?php
/*
 * Simple PHP Authentication Class
 * Version 2.93
 *
 * Copyright 2012, noriah
 * http://noriah.no-ip.org
 *
 */
 
class Auth{

  //Variable Declaration
	private $keyLength = 24;
	private $saltLength = 8;
	private $loggedIn;
	private $key;
	private $flags;
	private $userInfo;
	
	//Constructor
	public function __construct($key = NULL){
		if($key != NULL){
			$this->key = $key;
		}elseif(isset($_COOKIE['login'])){
			$this->key = $_COOKIE['login'];
		}else{
			$this->key = NULL;
		}
    }
	
	public function setKey($key){
		$this->key = $key;
	}
	
	public function getKey(){
		return $this->key;
	}
	
	public function login($user,$pass,$keeplog){
	
		$query = "SELECT * FROM users WHERE user = '".$user."'";
		$info = $this->mysql_get($query);
		
		if(md5($pass)==$info['pass'] && isset($info['user'])){
		
			$key = $this->createRandomKey($this->keyLength);
			$salt = $this->createRandomKey($this->saltLength);
			$keyHash = $this->key_hash($key,$salt);
			$ip = getRealIpAddr();
			$agent = $_SERVER['HTTP_USER_AGENT'];
			
			$query = "UPDATE `users` SET `userAgent` = '".$agent."', `ip` = '".$ip."', `hash` = '".$keyHash."', `lastLogin` = NOW() WHERE `users`.`id` = '".$info['id']."'";
			$this->mysql_set($query);
			
			$cKey = $this->key_encode($info['id'].'n'.$key.'o'.$salt);
			
			if($keeplog == "on"){
				setcookie("login", $cKey, time()+60*60*24*7, '/', str_ireplace('www.', '', $_SERVER['SERVER_NAME']));
			}else{
				setcookie("login", $cKey, 0, '/', str_ireplace('www.', '', $_SERVER['SERVER_NAME']));}
				
			$this->setKey($cKey);
			$this->loggedIn = true;
			
			return true;
			
		}else{return false;}
	}
	
	public function checkLogin($redirect = false, $address = "/login.php"){
	
		if(isset($this->loggedIn)){return $this->loggedIn;}
		if(is_null($this->key)){
			if($redirect){$this->redirect($address);}else{return false;}
		}else{
			
			$keyData = $this->key_decode();
			$keyHash = $this->key_hash($keyData['key'],$keyData['salt']);
			$ip = $this->ip_get();
			
			$query = "SELECT * FROM users WHERE id = '".$keyData['id']."'";
			$results = $this->mysql_get($query);
			
			if(is_null($results['hash'])){
				if($redirect){$this->redirect($address);}else{return false;}
			}
			
			if($results['hash'] == $keyHash || $results['ip'] == $ip){
				$this->loggedIn = true;
				return true;
			}else{
				if($redirect){$this->redirect($address);}else{return false;}
			}
		}
		return -1;
	}
	
	public function logout(){
		$query = "UPDATE `users` SET `hash` = NULL WHERE `users`.`id` = '".$this->key_decode('id')."'";
		$this->mysql_set($query);
		setcookie("login", "", time()-3600, '/', str_ireplace('www.', '', $_SERVER['SERVER_NAME']));
		header("Location: /");
		//.str_replace("?logout","",$_SESSION['curl1'])
		exit;
	}
	
	public function removeKey(){
		setcookie("login", "", time()-3600, '/', str_ireplace('www.', '', $_SERVER['SERVER_NAME']));
	}
	
	public function userInfo_get($item = NULL)
	{
		if(!isset($this->userInfo)){
			$keyData = $this->key_decode();
			$query = "SELECT * FROM users WHERE id = '".$keyData['id']."'";
			$data = $this->mysql_get($query);
			$data['flags'] = json_decode($data['flags'], true);
			$this->userInfo = $data;
		}
		if(!isset($this->userInfo)){
			return false;
		}
		if($item != NULL){
			return $this->userInfo[$item];
		}else{
			return $this->userInfo;
		}
	}
	
	public function checkFlag($item = "basic", $redirect = false, $message = false){
		if($message){
			require("auth_strings.php");
			$errorVar = $flagS["$item"];
		}
		if(is_null($this->flags)){
			$id = substr($this->key,0,strpos($this->key,"n"));
			$query = "SELECT flags FROM users WHERE id = '".$id."'";
			$flagsJSON = $this->mysql_get($query);
			$flags = json_decode($flagsJSON['flags'],true);
			$this->flags = $flags;
		}
		if(!isset($this->flags)){
			if($redirect){$this->redirect("/error/permission.php", "Could Not Load Permission Flags");}else{return false;}
		}
		if(isset($this->flags["root"])){
			if($this->flags["root"] == true){
				return true;
			}
		}
		if(isset($this->flags["$item"])){
			if($this->flags["$item"] == true){
				return true;
			}else{
				if($redirect){$this->redirect("/error/permission.php", $errorVar);}else{return false;}
			}
		}else{
			if($redirect){$this->redirect("/error/permission.php", $errorVar);}else{return false;}
		}
		if($redirect){$this->redirect("/error/permission.php", "An Error Occurred.");}else{return false;}
	}
	
	public function createRandomKey($amount){
		$keyset  = "abcdefghijklmopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$randkey = "";
		for ($i=0; $i<$amount; $i++){
			$randkey .= substr($keyset, rand(0, strlen($keyset)-1), 1);}
		return $randkey;
	}
	
	private function redirect($address, $message = NULL){
		if(!is_null($message)){
			if(session_id() == '') {
				session_name("session"); session_start();
			}
			$_SESSION['errorVar'] = '<div class="error">'.$message.'</div>';
		}
		header("Location: $address");
		exit();
	}
	
	private function key_hash($key, $salt)
	{
		$nsalt = '$1$'.$salt.'$';
		return crypt($key,$nsalt);
	}
	
	private function key_encode($id, $key, $salt){
		return $id.'n'.$key.'o'.$salt;
	}
	
	private function key_decode($item = NULL)
	{
		$data['id'] = substr($this->key,0,strpos($this->key,"n"));
		$data['key'] = substr($this->key,strpos($this->key,"n")+1,$this->keyLength);
		$data['salt'] = substr($this->key,-$this->saltLength);
		if(!is_null($item)){return $data["$item"];}else{return $data;}
	}
	
	private function ip_get(){
		if(!empty($_SERVER['HTTP_CLIENT_IP'])){$ip=$_SERVER['HTTP_CLIENT_IP'];
		}elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}else{$ip=$_SERVER['REMOTE_ADDR'];}
		return $ip;
	}
	
	private function mysql_get($query)
	{
		require($_SERVER['DOCUMENT_ROOT'].'/common/php/MySQL.php');
		$mscon = new mysqli($dbInfo['server'],$dbInfo['user'],$dbInfo['pass'],$dbs['noriah']);
		if ($mscon->connect_errno) {printf("Connect failed: %s\n", $mscon->connect_error); exit();}
		$infoR = $mscon->query($query);
		$row = $infoR->fetch_array(MYSQLI_ASSOC);
		$mscon->close();
		return $row;
	}
	
	private function mysql_set($query)
	{
		require($_SERVER['DOCUMENT_ROOT'].'/common/php/MySQL.php');
		$mscon = new mysqli($dbInfo['server'],$dbInfo['user'],$dbInfo['pass'],$dbs['noriah']);
		if ($mscon->connect_errno) {printf("Connect failed: %s\n", $mscon->connect_error); exit();}
		$infoR = $mscon->query($query);
		$mscon->close();
	}
}

function checkIPBan($ip = NULL)
{
	if($ip == NULL)
	{
		if(!empty($_SERVER['HTTP_CLIENT_IP'])){$ip=$_SERVER['HTTP_CLIENT_IP'];
		}elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}else{$ip=$_SERVER['REMOTE_ADDR'];}
	}
	require($_SERVER['DOCUMENT_ROOT'].'/common/php/MySQL.php');
	$mscon = new mysqli($dbInfo['server'],$dbInfo['user'],$dbInfo['pass'],$dbs['noriah']);
	if ($mscon->connect_errno) {printf("Connect failed: %s\n", $mscon->connect_error); exit();}
	$infoR = $mscon->query("SELECT * FROM ipBan WHERE ip = '".$ip."'");
	if(empty($infoR)){return false;}
	$row = $infoR->fetch_array(MYSQLI_ASSOC);
	$mscon->close();
	if($row['banned'] == 1){header("Location: /error/ipbanned.html"); exit;}
}
?>
