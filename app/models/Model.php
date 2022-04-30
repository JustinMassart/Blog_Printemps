<?php

	namespace Models;

	use PDO;
	use PDOException;

	class Model
	{
		protected ?PDO $pdo = null;

		public function __construct ()
		{
			try {
				$this -> pdo = new PDO( 'mysql:host=127.0.0.1;port=3306;dbname=blog', 'root', '',
					[ PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ] );
			} catch ( PDOException $e ) {
				echo $e -> getMessage ();
				exit;
			}
		}
	}