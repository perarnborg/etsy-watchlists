
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
<div class="hidden settings-form-container">
	<form class="settings-form" action="/mywatchlists/save" method="post">
		<input type="hidden" name="watchlist" class="watchlist-data" />
		<p>
			<label for="settings-watchlist-name">Watchlist name</label>
			<input type="text" placeholder="Enter a name for your watchlist" id="settings-watchlist-name" class="watchlist-name"{% if watchlist is defined %} value="{{ watchlist.name }}"{% endif %} />
		</p>
		<p>
			<label for="settings-watchlist-email-interval">How often do you want to recieve an email?</label>
			<select id="settings-watchlist-email-interval" class="watchlist-email-interval">
				<option value="">Never</option>
				<option value="86400"{% if watchlist is defined %}{% if watchlist.email_interval == 86400 %} selected="selected"{% endif %}{% endif %}>Every day</option>
				<option value="302400"{% if watchlist is defined %}{% if watchlist.email_interval == 302400 %} selected="selected"{% endif %}{% endif %}>Twice a week</option>
				<option value="604800"{% if watchlist is defined %}{% if watchlist.email_interval == 604800 %} selected="selected"{% endif %}{% endif %}>Once a week</option>
				<option value="2592000"{% if watchlist is defined %}{% if watchlist.email_interval == 2592000 %} selected="selected"{% endif %}{% endif %}>Once a month</option>
			</select>
		</p>
		<input type="submit" value="Save Watchlist" />
	</form>
</div>
<script>
	var parameters = [
	{% for index, parameter in parameters %}
	{{ index > 0 ? ',' : '' }}
	{ id: {{ parameter.id }}, name: '{{ parameter.name }}', api_name: '{{ parameter.api_name }}', valueType: '{{ parameter.value_type }}' }
	{% endfor %}
	];
	{% if currentWatchlist is defined %}
	var watchlist = {
		name: '{{currentWatchlist.name}}',
		watchlists_parameters: [
			{% for index, parameter in currentWatchlistParameters %}
			{{ index > 0 ? ',' : '' }}
			{
				parameters_id: {{ parameter.parameters_id }},
				value: '{{ parameter.value }}',
				title: '{{ parameter.title }}'
			}
			{% endfor %}
		],
		watchlists_listings: [
			{% for index, listing in currentWatchlistListings %}
			{{ index > 0 ? ',' : '' }}
			{
				listing_id: {{listing.listing_id}},
				title: '{{ listing.title }}',
				url: '{{ listing.url }}',
				image_thumb_url: '{{ listing.image_thumb_url }}',
				image_url: '{{ listing.image_url }}',
				shop_loginname: '{{ listing.shop_loginname }}',
				shop_url: '{{ listing.shop_url }}',
				currency_code: '{{ listing.currency_code }}',
				price: '{{ listing.price }}',
				creation: '{{ listing.creation }}',
				is_viewed: {{ listing.is_viewed }}
			}
			{% endfor %}
		]
	};
	{% endif %}
</script>
