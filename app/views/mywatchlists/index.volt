
<div class="clearfix">
	{{ content() }}

	<h1>My Watchlists</h1>

	<div class="watchlists">
		<ul>
		{% for watchlist in watchlists %}
			<li class="watchlist">
				{% for index, listing in watchlistListings[watchlist.id] %}
				<a href="/mywatchlists/watchlist/{{ watchlist.id }}"><img alt="{{ listing.title }}" src="{{ listing.image_thumb_url }}" /></a>
				{% endfor %}
				<a class="name" href="/mywatchlists/watchlist/{{ watchlist.id }}">{{ watchlist.name|e }}</a>
			</li>
		{% endfor %}
			<li class="add-new">
				<a href="/mywatchlists/watchlist"><img alt="Add new" src="/public/images/add-new.png" /></a>
				<a class="name" href="/mywatchlists/watchlist">Create new watchlist</a>
			</li>
		</ul>
	</div>
</div>
