<?php

use JetBrains\PhpStorm\ArrayShape;

session_start();

// Front Controller

const START_PAGE = 1;
const PER_PAGE = 4;
const DEFAULT_SORT_ORDER = 1;
const VIEWS_PATH = __DIR__.'/views/';
const PARTIALS_PATH = VIEWS_PATH.'partials/';
const VIEWS_EXTENSION = '.php';
const POSTS_PATH = __DIR__.'/datas/posts/';

define('POST_FILES', glob(POSTS_PATH.'*.json'));

// Router
$action = $_REQUEST['action'] ?? 'index';

$callback = match ($action) {
    'create' => 'create',
    'show' => 'show',
    'store' => 'store',
    default => 'index',
};


// Controllers
#[ArrayShape(['name' => "string", 'data' => "array"])] function index(): array
{
    $sort_order = DEFAULT_SORT_ORDER;
    if (isset($_GET['order-by'])) {
        $sort_order = $_GET['order-by'] === 'oldest' ? -1 : 1;
    }

    $filter = [];
    if (isset($_GET['category'])) {
        $filter['type'] = 'category';
        $filter['value'] = $_GET['category'];
    } elseif (isset($_GET['author'])) {
        $filter['type'] = 'author_name';
        $filter['value'] = $_GET['author'];
    }

    $posts = get_posts($filter, $sort_order);
    $posts_count = count($posts);
    define('MAX_PAGE', intdiv($posts_count, PER_PAGE) + ($posts_count % PER_PAGE ? 1 : 0));

    $p = START_PAGE;
    if (isset($_GET['p'])) {
        if ((int) $_GET['p'] >= START_PAGE && (int) $_GET['p'] <= MAX_PAGE) {
            $p = (int) $_GET['p'];
        }
    }

    $posts = get_paginated_posts($posts, $p);
    $title = 'La page index';
    $categories = get_categories();
    $authors = get_authors();
    ksort($categories);
    ksort($authors);
    return [
        'name' => 'index',
        'data' => compact('title', 'categories', 'authors', 'posts', 'p'),
    ];
}

#[ArrayShape(['name' => "string", 'data' => "array"])] function create(): array
{
    $categories = get_categories();
    $authors = get_authors();
    ksort($categories);
    ksort($authors);
    return [
        'name' => 'create',
        'data' => [
            'title' => 'La page create',
            'categories' => $categories,
            'authors' => $authors,
        ]
    ];
}

#[ArrayShape(['name' => "string", 'data' => "array"])] function show(): array
{
    // Est-ce que il y a un id dans l’url ?
    if (!isset($_GET['id'])) {
        header('Location: /404.php');
        exit;
    }
    $id = $_GET['id'];
    $post = get_post($id);
    // Est-ce que cet id correspond à un post ?
    if (!$post) {
        header('Location: /404.php');
        exit;
    };
    $title = $post->title.' - Blog';
    $categories = get_categories();
    $authors = get_authors();
    ksort($categories);
    ksort($authors);
    return [
        'name' => 'single',
        'data' => compact('title', 'categories', 'authors', 'post'),
    ];
}

function store(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!has_validation_errors()) {
            // Récupérer les données et créer le fichier
            $post = new stdClass();
            $post->id = uniqid();
            $post->title = $_POST['post-title'];
            $post->excerpt = $_POST['post-excerpt'];
            $post->body = $_POST['post-body'];
            $post->category = $_POST['post-category'];
            $post->published_at = (new Datetime())->format('Y-m-d H:i:s');
            $post->author_name = 'jon snow';
            $post->author_avatar = 'https://via.placeholder.com/128x128.png/004466?text=people+jon';

            $post_path = POSTS_PATH.$post->id.'.json';
            file_put_contents($post_path, json_encode($post));

            header('Location: /?action=show&id='.$post->id);
        } else {
            header('Location: /?action=create');
        }
        exit;
    }
    header('Location: index.php');
    exit;
    // Écrire les données du formulaire dans un fichier
    // Rediriger vers ?action=show&id=ksjlksjfkls
}

// Validators
function has_validation_errors(): bool
{
    $_SESSION['errors'] = [];
    if (mb_strlen($_POST['post-title']) < 5 || mb_strlen($_POST['post-title']) > 100) {
        $_SESSION['errors']['post-title'] = 'Le titre doit être avoir une taille comprise entre 5 et 100 caractères';
    }
    if (mb_strlen($_POST['post-excerpt']) < 20 || mb_strlen($_POST['post-excerpt']) > 200) {
        $_SESSION['errors']['post-excerpt'] = 'Le résumé doit être avoir une taille comprise entre 20 et 200 caractères';
    }
    if (mb_strlen($_POST['post-body']) < 100 || mb_strlen($_POST['post-body']) > 1000) {
        $_SESSION['errors']['post-body'] = 'Le texte doit être avoir une taille comprise entre 100 et 1000 caractères';
    }
    $categories = get_categories();
    if (!in_array($_POST['post-category'], array_keys($categories))) {
        $_SESSION['errors']['post-category'] = 'La catégorie doit faire partie des catégories existantes';
    }
    $_SESSION['old'] = $_POST;
    return (bool) count($_SESSION['errors']);
}

// Models
function get_post(string $id): stdClass|bool
{
    $file_path = POSTS_PATH.$id.'.json';
    if (!in_array($file_path, POST_FILES)) {
        return false;
    }
    return json_decode(file_get_contents($file_path));
}

function get_posts(array $filter = [], string $order = DEFAULT_SORT_ORDER): array
{
    $posts = get_all_posts();

    if ($filter !== []) {
        $posts = array_filter($posts, fn($p) => $p->{$filter['type']} === $filter['value']);
    }

    usort($posts, fn($p1, $p2) => $p1->published_at > $p2->published_at ? -1 * $order : 1 * $order);

    return $posts;
}

function get_all_posts(): array
{
    $posts = [];

    foreach (POST_FILES as $file_path) {
        $posts [] = json_decode(file_get_contents($file_path));
    }

    return $posts;
}

function get_categories(): array
{
    $categories = [];
    $posts = get_all_posts();

    foreach ($posts as $post) {
        if (!in_array($post->category, array_keys($categories))) {
            $categories[$post->category] = 1;
        } else {
            $categories[$post->category] += 1;
        }
    }

    return $categories;
}

function get_authors(): array
{
    $authors = [];
    $posts = get_all_posts();

    foreach ($posts as $post) {
        if (!in_array($post->author_name, array_keys($authors))) {
            $authors[$post->author_name]['count'] = 1;
            $authors[$post->author_name]['avatar'] = $post->author_avatar;
        } else {
            $authors[$post->author_name]['count'] += 1;
        }
    }

    return $authors;
}

function get_paginated_posts(array $posts, int $p): array
{
    $start = ($p - 1) * PER_PAGE;
    $end = $start + PER_PAGE - 1;
    return array_filter($posts, fn($post, $i) => ($i >= $start && $i <= $end), ARRAY_FILTER_USE_BOTH);
}

// View rendering with its associated data
$view = call_user_func($callback);
require VIEWS_PATH.$view['name'].VIEWS_EXTENSION;