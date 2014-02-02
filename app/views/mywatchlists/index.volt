
<div class="clearfix">
	<div class="search-on-etsy">
		{{ content() }}

		<h1>Search on Etsy to create a new Watchlist</h1>

		<form class="search-on-etsy-form">
			<div>
				<input type="text" class="etsy-keywords" placeholder="Search for items on Etsy" />
				<input class="button" type="submit" value="Show results" />
			</div>
			<div class="etsy-category-filter">
				<a class="toggle-trigger" data-target=".toggle-area-categories" data-group="toggle-filters" href="#">Filter by category</a>
				<a class="toggle-trigger" data-target=".toggle-area-shipping" data-group="toggle-filters" href="#">Filter by shipping</a>
				<div class="toggle-area toggle-area-categories">
					<ul class="etsy-categories">
					{% for category in categories %}
						<li><a href="#" data-value="{{ category.name|e }}" title="{{ category.long_name|e }}">{{ category.short_name|e }}</a></li>
					{% endfor %}
					</ul>
 				</div>
				<div class="toggle-area toggle-area-shipping">
					<select class="etsy-shipsto">
					<option value="">Choose country</option>
					{% for country in countries %}
					<option value="{{ country.name }}" data-code="{{ country.iso_country_code }}">{{ country.name }}</option>
					{% endfor %}
					</select>
 				</div>
			</div>
		</form>
		<div class="button-control">
			<button class="button button-save">Create<br>Watchlist</button>
		</div>

		<div class="etsy-filters">
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
<script>
	var parameters = [
	{% for index, parameter in parameters %}
	{{ index > 0 ? ',' : '' }}
	{ id: {{ parameter.id }}, name: '{{ parameter.name }}', apiName: '{{ parameter.apiName }}', valueType: '{{ parameter.valueType }}' }
	{% endfor %}
	];
</script>
