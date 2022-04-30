<div class="hidden w-4/12 -mx-8 lg:block">
	<div class="px-8">
		<h1 class="mb-4 text-xl font-bold text-gray-700">Authors</h1>
		<div class="flex flex-col max-w-sm px-6 py-4 mx-auto bg-white rounded-lg shadow-md">
			<ul class="-mx-4">
				<?php foreach ( $view[ 'data' ][ 'authors' ] as $post ): ?>
					<li class="flex items-center mb-3"><img
								src="<?= $post -> author_avatar ?>"
								alt="<?= $post -> author_name ?>"
								class="object-cover w-10 h-10 mx-4 rounded-full">
						<p><a href="?author=<?= $post -> author_name ?>"
							  class="mx-1 font-bold text-gray-700 hover:underline"><?= ucwords ( $post -> author_name ) ?></a>
							<span class="text-sm font-light text-gray-700">Created <?= $post -> posts_count ?> Post<?php if ( $post -> posts_count > 1 ): ?>s<?php endif ?></span>
						</p>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<div class="px-8 mt-10">
		<h1 class="mb-4 text-xl font-bold text-gray-700">Categories</h1>
		<div class="flex flex-col max-w-sm px-4 py-6 mx-auto bg-white rounded-lg shadow-md">
			<ul>
				<?php foreach ( $view[ 'data' ][ 'categories' ] as $category ): ?>
					<li class="mb-3"><a href="?category=<?= $category -> category_slug ?>"
										class="mx-1 font-bold text-gray-700 hover:text-gray-600 hover:underline">
							<?= ucwords ( $category -> category_name ) ?></a> contains <?= $category -> posts_count ?>
																			  post<?php if ( $category -> posts_count > 1 ): ?>s<?php endif; ?>
					</li>
				<?php endforeach; ?>

			</ul>
		</div>
	</div>
	<div class="px-8 mt-10">
		<h1 class="mb-4 text-xl font-bold text-gray-700">Recent Post</h1>
		<div class="flex flex-col max-w-sm px-8 py-6 mx-auto bg-white rounded-lg shadow-md">
			<div class="flex items-center justify-center">
				<a href="?category=quia aspernatur consequatur"
				   class="px-2 py-1 text-sm text-green-100 bg-gray-600 rounded hover:bg-gray-500"><?= $view[ 'data' ][ 'latest' ] -> category_name ?></a>
			</div>
			<div class="mt-4">
				<a href="?action=show&id=62389ca7b5432"
				   class="font-bold text-lg font-medium text-gray-700 hover:underline"><?= $view[ 'data' ][ 'latest' ] -> title ?></a>
			</div>
			<div class="flex items-center justify-between mt-4">
				<div class="flex items-center"><img
							src="https://via.placeholder.com/128x128.png/004466?text=people+myriam"
							alt="avatar"
							class="object-cover w-8 h-8 rounded-full">
					<a href="?author=Myriam Dupont"
					   class="font-bold mx-3 text-sm text-gray-700 hover:underline">Myriam
																					Dupont</a>
				</div>
				<span
						class="text-sm font-light text-gray-600">Mar 21, 2022</span>
			</div>
		</div>
	</div>
</div>