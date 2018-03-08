<?php

class Sql {

	//Atributos
	private $conn;

	//Metodos de conexao
	public function connect() {
		try{
			$this->conn = new PDO("mysql:host=localhost;dbname=hcode_shop", "root", "");
			$this->conn->exec("set names utf8");

		}catch(PDOException $e) {
			echo "<br> Erro ao se conectar com o banco de dados: " . $e->getMessage();
		}
	}

	//Metodos de desconexao
	public function disconnect() {
		if($this->conn !== NULL)
			$this->conn = NULL;
		else
			echo "<br> Erro ao fechar a conexão com o banco de dados";
	}

	//Metodo para obter a conexao
	public function getConnection() {
		if($this->conn === NULL) 
			$this->connect();

		return $this->conn;
	}

	//Metodos para realizar pesquisa
	public function query($rawQuery, $params = array()) {

		$stmt = $this->conn->prepare($rawQuery);

		$this->setParams($stmt, $params);

		$stmt->execute();

		return $stmt;
	}

	public function select($rawQuery, $params = array()):array {

		$stmt = $this->query($rawQuery, $params);


		return $stmt->fetchALL(PDO::FETCH_ASSOC);
	}


	//Metodo auxiliar para pesquisa
	private function setParams($statment, $parameters = array()) {

		foreach ($parameters as $key => $value) {

			$this->setParam($statment, $key, $value);
		}
	}

	//Metodo auxiliar para pesquisa
	private function setParam($statment, $key, $value) {

		$statment->bindParam($key, $value);
	}


}//Fim da classe SQL


?>