<?php
Class Database{
	public $conn;

	public function __construct(){
		$this->conn=new mysqli('localhost','u388370834_mindaid','MindAid123*','u388370834_mindaid');
	}
}
?>