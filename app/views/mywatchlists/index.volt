
<div class="clearfix">
	<div class="search-on-etsy">
		{{ content() }}

		<h1>Search on Etsy to create a new Watchlist</h1>

		<form class="search-on-etsy-form">
			<div>
				<input type="text" class="etsy-keywords" placeholder="Search for items on Etsy" />
				<input class="button" type="submit" value="Search" />
			</div>
			<div class="etsy-category-filter">
				<a class="toggle-trigger" href="#">Filter by category</a>
				<div class="toggle-area">
					<ul class="etsy-categories">
					{% for category in categories %}
						<li><a href="#" data-value="{{ category.name|e }}">{{ category.short_name|e }}</a></li>
					{% endfor %}
					</ul>
					<div class="etsy-filters etsy-category-filters">
						<ul></ul>
					</div>
 				</div>
			</div>
		</form>
		<div class="button-control">
			<button class="button button-save">Save as<br>Watchlist</button>
		</div>

		<div class="etsy-filters etsy-other-filters">
			<ul></ul>
		</div>
		<div class="search-on-etsy-results">
			<ul class="clearfix"></ul>
		</div>

	</div>
	<div class="my-watchlists">
		<h3>Existing Watchlists</h3>
		<ul>
		{% for watchlist in watchlists %}
			<li>{{ watchlist.name|e }}</li>
		{% endfor %}
		</ul>
	</div>
</div>
