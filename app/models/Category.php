<?php

	namespace Models;

	use Models\Model;

	class Category extends Model
	{
		public function get (): array
		{
			$sql = <<< SQL
        SELECT c.slug as category_slug,
            c.id as category_id,
            c.name as category_name,
            COUNT(p.id) as posts_count
        FROM categories c
        JOIN category_post cp on c.id = cp.category_id
        JOIN posts p on cp.post_id = p.id
        GROUP BY c.slug, c.id
        ORDER BY c.slug
SQL;

			/*$statement = $this -> pdo -> prepare ( $sql );
			$statement -> execute (); //[':id' => $id]
			$categories = $statement -> fetchAll ();*/ // fetch() quand un seul résultat

			//[':id' => $id]
			return $this -> pdo -> query ( $sql ) -> fetchAll ();
		}

		public function exists ( string $id ): bool
		{
			$sql = <<< SQL
        SELECT count(id)
        FROM categories
        WHERE id = :id
    SQL;

			$statement = $this -> pdo -> prepare ( $sql );
			$statement -> execute ( [ ':id' => $id ] );

			return (bool)$statement -> fetchColumn ();
		}

		public function get_category_by_slug ( string $slug )
		{
			// tri (cliquer sur le nom d'une catégorie) - récupérer une catégorie

			$sql = <<< SQL
        SELECT c.slug as category_slug,
            c.name as category_name
        FROM categories c
        WHERE c.slug = :slug
SQL;

			$statement = $this -> pdo -> prepare ( $sql );
			$statement -> execute ( [ ':slug' => $slug ] );
			// fetch() quand un seul résultat

			return $statement -> fetch ();
		}
	}