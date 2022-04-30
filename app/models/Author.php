<?php

	namespace Models;

	use Models\Model;

	class Author extends Model
	{
		public function get (): array
		{
			$sql = <<< SQL
        SELECT a.slug as author_slug,
            a.id as author_id,
            a.name as author_name,
            a.avatar as author_avatar,
            COUNT(p.id) as posts_count
        FROM authors a
        JOIN posts p on a.id = p.author_id
        GROUP BY a.slug, a.id
        ORDER BY a.slug
SQL;

			//[':id' => $id]
			// fetch() quand un seul résultat

			return $this -> pdo -> query ( $sql ) -> fetchAll ();


			/*$authors = [];
			$posts = get_all_posts();

			foreach ($posts as $post) {
				if (!in_array($post->author_name, array_keys($authors))) {
					$authors[$post->author_name]['count'] = 1;
					$authors[$post->author_name]['avatar'] = $post->author_avatar;
				} else {
					$authors[$post->author_name]['count'] += 1;
				}
			}

			return $authors;*/
		}

		public function get_author_by_slug ( string $slug )
		{
			// tri (cliquer sur le nom d'un auteur) - récupérer un auteur

			$sql = <<< SQL
        SELECT a.slug as author_slug,
            a.name as author_name,
            a.avatar as author_avatar
        FROM authors a
        WHERE a.slug = :slug
SQL;

			$statement = $this -> pdo -> prepare ( $sql );
			$statement -> execute ( [ ':slug' => $slug ] );
			// fetch() quand un seul résultat

			return $statement -> fetch ();
		}
	}