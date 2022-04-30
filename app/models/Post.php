<?php

	namespace Models;

	use Models\Model;
	use stdClass;

	class Post extends Model
	{

		public function __construct ()
		{
			parent ::__construct ();
		}

		public function getOne ( string $slug ): stdClass|bool
		{
			return $this -> get_by_slug ( $slug );
		}

		public function getAll ( array &$filter = [], string $order = DEFAULT_SORT_ORDER, int $page = 1 ): array
		{


			if ( isset( $_GET[ 'category' ] ) ) {
				$filter[ 'type' ] = 'category';
				$filter[ 'value' ] = $_GET[ 'category' ];
				//var_dump($_GET['category']);die();
				$posts = $this -> get_by_category ( $_GET[ 'category' ], DEFAULT_SORT_ORDER, START_PAGE, PER_PAGE );
			} else if ( isset( $_GET[ 'author' ] ) ) {
				$filter[ 'type' ] = 'author_name';
				$filter[ 'value' ] = $_GET[ 'author' ];
				$posts = $this -> get_by_author ( $_GET[ 'author' ], DEFAULT_SORT_ORDER, START_PAGE, PER_PAGE );
			} else {
				$posts = $this -> get_unfiltered ( DEFAULT_SORT_ORDER, START_PAGE, PER_PAGE );
			}

			// ajouter les catégories aux posts
			$this -> add_categories_to_posts ( $posts );
			//var_dump($posts->categories->name);die();

			return $posts;
		}

		public function get_unfiltered ( $order, $start, $per_page )
		{
			// récupérer tous les posts + leur auteur + order by et limit

			$sql = <<< SQL
        SELECT p.id as id,
               p.title as title,
               p.slug as slug,
               p.body as body,
               p.published_at as published_at,
               p.excerpt as excerpt,
               p.thumbnail as thumbnail,
               p.author_id as author_id,
               a.name as author_name,
               a.slug as author_slug,
               a.avatar as author_avatar
        FROM posts p
        JOIN authors a on p.author_id = a.id
        ORDER BY published_at $order
        LIMIT $start, $per_page
SQL;

			return $this -> pdo -> query ( $sql ) -> fetchAll ();
		}

		public function get_by_category ( string $id, $order, $start, $per_page ): bool|array
		{
			// JOIN relation category + where id

			$sql = <<< SQL
        SELECT p.title as title,
               p.id as id,
               p.slug as slug,
               p.body as body,
               p.published_at as published_at,
               p.excerpt as excerpt,
               p.thumbnail as thumbnail,
               p.author_id as author_id,
               a.name as author_name,
               a.slug as author_slug,
               a.avatar as author_avatar
        FROM posts p
        JOIN authors a on p.author_id = a.id
        JOIN category_post cp on p.id = cp.post_id
        WHERE cp.category_id = :id
        ORDER BY published_at $order
        LIMIT $start, $per_page
SQL;

			$statement = $this -> pdo -> prepare ( $sql );
			$statement -> execute ( [ ':id' => $id ] );
			return $statement -> fetchAll ();
		}

		public function get_by_author ( string $id, $order, $start, $per_page ): bool|array
		{
			// where id

			$sql = <<< SQL
        SELECT p.title as title,
               p.id as id,
               p.slug as slug,
               p.body as body,
               p.published_at as published_at,
               p.excerpt as excerpt,
               p.thumbnail as thumbnail,
               p.author_id as author_id,
               a.name as author_name,
               a.slug as author_slug,
               a.avatar as author_avatar
        FROM posts p
        JOIN authors a on p.author_id = a.id
        WHERE a.id = :id
        ORDER BY published_at $order
        LIMIT $start, $per_page
SQL;

			$statement = $this -> pdo -> prepare ( $sql );
			$statement -> execute ( [ ':id' => $id ] );
			return $statement -> fetchAll ();

		}

		public function get_by_slug ( string $slug )
		{
			// modifier en lui ajoutant ses catégories (add_categories_to_post($post)) aussi

			$sql = <<< SQL
        SELECT p.title as title,
               p.id as id,
               p.slug as slug,
               p.body as body,
               p.published_at as published_at,
               p.excerpt as excerpt,
               p.thumbnail as thumbnail,
               p.author_id as author_id,
               a.name as author_name,
               a.slug as author_slug,
               a.avatar as author_avatar
        FROM posts p
        JOIN authors a on p.author_id = a.id
        JOIN category_post cp on p.id = cp.post_id
        WHERE p.slug = :slug
        GROUP BY p.slug
SQL;

			$statement = $this -> pdo -> prepare ( $sql );
			$statement -> execute ( [ ':slug' => $slug ] );
			$post = $statement -> fetch ();

			$this -> add_categories_to_post ( $post );

			return $post;

		}

		public function save ( $post ): bool
		{

			// enregistrer dans la DB un post (posts)
			$sql = <<< SQL
		INSERT INTO posts (id, title, slug, body, published_at, excerpt, thumbnail, author_id, deleted_at)
		VALUES (:post_id,:post_title,:post_slug,:post_body,:post_published_at,:post_excerpt,:post_thumbnail,:post_author_id,null);
SQL;

			return $this -> pdo -> prepare ( $sql ) -> execute ( [
				':post_id' => $post -> id,
				':post_title' => $post -> title, ':post_slug' => $post -> slug,
				':post_body' => $post -> body,
				':post_published_at' => $post -> published_at,
				':post_excerpt' => $post -> excerpt,
				':post_thumbnail' => $post -> author_avatar,
				':post_author_id' => $post -> author_id ] ); //[':id' => $id]

			// enregistrer dans la DB la catégorie (category_post)
		}

		public function latest ()
		{
			$sql = <<< SQL
        SELECT p.id as id,
               p.title as title,
               p.slug as slug,
               p.body as body,
               p.published_at as published_at,
               p.excerpt as excerpt,
               p.thumbnail as thumbnail,
               p.author_id as author_id,
               a.name as author_name,
               a.slug as author_slug,
               a.avatar as author_avatar
        FROM posts p
        JOIN authors a on p.author_id = a.id
        ORDER BY published_at DESC
        LIMIT 1;
SQL;

			$statement = $this -> pdo -> query ( $sql );
			$post = $statement -> fetch ();


			$this -> add_categories_to_post ( $post );

			return $post;
		}

		public function add_categories_to_posts ( $posts ): bool|array
		{ //array
			// ajouter les catégories de chaque post
			foreach ( $posts as $post ) {
				$post -> categories = $this -> add_categories_to_post ( $post );
				//var_dump($post);die();
			}

			//var_dump($posts);die();

			return $posts;

		}

		public function add_categories_to_post ( $post ): bool|array
		{ //obj
			// ajouter les catégories de chaque post

			$categories = $this -> get_categories_by_post ( $post -> id );
			//var_dump($post);die();

			return $categories;


		}

		public function get_categories_by_post ( string $id ): bool|array
		{
			// récupérer les categories lié à un certain post via l'id du post

			$sql = <<< SQL
        	SELECT c.slug as category_slug,
               c.name as category_name,
               c.id as category_id
        	FROM categories c
        		JOIN category_post cp on c.id = cp.category_id
        		JOIN posts p on cp.post_id = p.id
        	WHERE p.id = :id;
SQL;

			$statement = $this -> pdo -> prepare ( $sql );
			$statement -> execute ( [ ':id' => $id ] );
			// fetch() quand un seul résultat

			return $statement -> fetchAll ();

		}

	}