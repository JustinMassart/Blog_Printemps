<?php

	use Carbon\Carbon;
	use Cocur\Slugify\Slugify;
	use Models\Author;
	use Models\Category;
	use Models\Post;
	use Ramsey\Uuid\Uuid;
	use JetBrains\PhpStorm\ArrayShape;

	require_once './vendor/autoload.php';

	session_start ();

	/*	try {
			global $pdo;
			$pdo = new PDO( 'mysql:host=127.0.0.1;port=3306;dbname=blog', 'root', '',
				[ PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ] );
		} catch ( PDOException $e ) {
			echo $e -> getMessage ();
			exit;
		}*/

// Front Controller

	const START_PAGE = 1;
	const PER_PAGE = 4;
	const DEFAULT_SORT_ORDER = 'DESC';
	const VIEWS_PATH = __DIR__ . '/views/';
	const PARTIALS_PATH = VIEWS_PATH . 'partials/';
	const VIEWS_EXTENSION = '.php';
	const POSTS_PATH = __DIR__ . '/datas/posts/';

	define ( 'POST_FILES', glob ( POSTS_PATH . '*.json' ) );

// Router
	$action = $_REQUEST[ 'action' ] ?? 'index';

	$callback = match ( $action ) {
		'create' => 'create',
		'show' => 'show',
		'store' => 'store',
		default => 'index',
	};


	/*
	 * TODO: Add store route for comments
	 */

// Controllers
	#[ArrayShape( [ 'name' => "string", 'data' => "array" ] )] function index (): array
	{

		$category_model = new Category();
		$author_model = new Author();
		$post_model = new Post();

		$sort_order = DEFAULT_SORT_ORDER;
		if ( isset( $_GET[ 'order-by' ] ) ) {
			$sort_order = $_GET[ 'order-by' ] === 'oldest' ? 'ASC' : DEFAULT_SORT_ORDER;
		}

		$filter = [];

		$posts = $post_model -> getAll ( $filter, $sort_order );
		$posts_count = count ( $posts );
		define ( 'MAX_PAGE', intdiv ( $posts_count, PER_PAGE ) + ( $posts_count % PER_PAGE ? 1 : 0 ) );

		$p = START_PAGE;
		if ( isset( $_GET[ 'p' ] ) ) {
			if ( (int)$_GET[ 'p' ] >= START_PAGE && (int)$_GET[ 'p' ] <= MAX_PAGE ) {
				$p = (int)$_GET[ 'p' ];
			}
		}

		$categories = $category_model -> get ();
		$authors = $author_model -> get ();
		$latest = $post_model -> latest ();

		return [
			'name' => 'index',
			'data' => [
				'title' => 'La page Index',
				'categories' => $categories,
				'authors' => $authors,
				'posts' => $posts,
				'latest' => $latest,
				'p' => $p,
			],
		];

	}

	#[ArrayShape( [ 'name' => "string", 'data' => "array" ] )] function create (): array
	{

		$category_model = new Category();
		$author_model = new Author();
		$post_model = new Post();

		/*$categories = get_categories ();*/
		$categories = $category_model -> get ();
		/*$authors = get_authors ();*/
		$authors = $author_model -> get ();
		$latest = $post_model -> latest ();
		return [
			'name' => 'create',
			'data' => [
				'title' => 'La page create',
				'categories' => $categories,
				'authors' => $authors,
				'latest' => $latest,
			]
		];
	}

	#[ArrayShape( [ 'name' => "string", 'data' => "array" ] )] function show (): array
	{
		$category_model = new Category();
		$author_model = new Author();
		$post_model = new Post();

		// Est-ce que il y a un slug dans l’url ?
		if ( !isset( $_GET[ 'slug' ] ) ) {
			header ( 'Location: /404.php' );
			exit;
		}
		$slug = $_GET[ 'slug' ];
		$post = $post_model -> getOne ( $slug );
		// Est-ce que ce slug correspond à un post ?
		if ( !$post ) {
			header ( 'Location: /404.php' );
			exit;
		};
		$title = $post -> title . ' - Blog';
		$categories = $category_model -> get ();
		$authors = $author_model -> get ();
		$latest = $post_model -> latest ();

		return [
			'name' => 'single',
			'data' => [
				'title' => $title,
				'categories' => $categories,
				'authors' => $authors,
				'post' => $post,
				'latest' => $latest,
			]
		];
	}

	function store (): void
	{
		if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
			if ( !has_validation_errors () ) {

				$post_model = new Post();
				$slugify = new Slugify();

				// Récupérer les données et créer le fichier
				$post = new stdClass();
				$post -> id = Uuid ::uuid4 ();
				$post -> title = $_POST[ 'post-title' ];
				$post -> excerpt = $_POST[ 'post-excerpt' ];
				$post -> body = $_POST[ 'post-body' ];
				$post -> slug = $slugify -> slugify ( $post -> title );
				$post -> category = '73639a15-f2a8-4ca7-b3ab-13f178b3a4d3';
				$post -> published_at = ( new Datetime() ) -> format ( 'Y-m-d H:i:s' );
				$post -> author_id = 'd61118a8-3bc5-4d10-b40a-12cb428e4701';
				$post -> author_name = 'richie moen';
				$post -> author_avatar = 'https://via.placeholder.com/128x128.png/007744?text=people+richie+moen+nulla';

				/*$post_path = POSTS_PATH . $post -> id . '.json';
				file_put_contents ( $post_path, json_encode ( $post ) );*/

				$post_model -> save ( $post );

				header ( 'Location: /?action=show&id=' . $post -> id );
			} else {
				header ( 'Location: /?action=create' );
			}
			exit;
		}

		// Rediriger vers ?action=show&id=ksjlksjfkls
	}

// Validators
	function has_validation_errors (): bool
	{

		$category_model = new Category();

		$_SESSION[ 'errors' ] = [];
		if ( mb_strlen ( $_POST[ 'post-title' ] ) < 5 || mb_strlen ( $_POST[ 'post-title' ] ) > 100 ) {
			$_SESSION[ 'errors' ][ 'post-title' ] = 'Le titre doit être avoir une taille comprise entre 5 et 100 caractères';
		}
		if ( mb_strlen ( $_POST[ 'post-excerpt' ] ) < 20 || mb_strlen ( $_POST[ 'post-excerpt' ] ) > 200 ) {
			$_SESSION[ 'errors' ][ 'post-excerpt' ] = 'Le résumé doit être avoir une taille comprise entre 20 et 200 caractères';
		}
		if ( mb_strlen ( $_POST[ 'post-body' ] ) < 100 || mb_strlen ( $_POST[ 'post-body' ] ) > 1000 ) {
			$_SESSION[ 'errors' ][ 'post-body' ] = 'Le texte doit être avoir une taille comprise entre 100 et 1000 caractères';
		}
		if ( !$category_model -> exists ( $_POST[ 'post-category' ] ) ) {
			$_SESSION[ 'errors' ][ 'post-category' ] = 'La catégorie doit faire partie des catégories existantes';
		}
		$_SESSION[ 'old' ] = $_POST;
		return (bool)count ( $_SESSION[ 'errors' ] );
	}

	/*function get_paginated_posts(array $posts, int $p): array
	{
		$start = ($p - 1) * PER_PAGE;
		$end = $start + PER_PAGE - 1;
		return array_filter($posts, fn($post, $i) => ($i >= $start && $i <= $end), ARRAY_FILTER_USE_BOTH);
	}*/

// View rendering with its associated data
	$view = call_user_func ( $callback );
	require VIEWS_PATH . $view[ 'name' ] . VIEWS_EXTENSION;