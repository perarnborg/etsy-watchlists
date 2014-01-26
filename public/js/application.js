var app = window.app || {};

$(document).ready(function () {
	new app.Toggle();
    app.mywatchlists = new app.Mywatchlists();
});

app.Toggle = function() {
	this.toggleTriggerSelector = '.toggle-trigger';
	this.toggleAreaSelector = '.toggle-area';
	this.eventListeners();
};

app.Toggle.prototype.eventListeners = function() {
	var self = this;
	console.log($(self.toggleTriggerSelector));
	$(document).on('click', self.toggleTriggerSelector, function(e) {
		e.preventDefault();
		var $trigger = $(this);
		var $area = $trigger.next(self.toggleAreaSelector);
		console.log($trigger.next());
		if($area.length > 0) {
			if($trigger.hasClass('open')) {
				$trigger.removeClass('open');
				$area.slideUp(100);
				$area.removeClass('open');
			} else {
				$trigger.addClass('open');
				$area.slideDown(100);
				setTimeout(function(){
					$area.addClass('open');
				}, 100);
			}
		}
	});
};

app.Mywatchlists = function() {
    this.$searchForm = $('.search-on-etsy-form');
    if(this.$searchForm.length > 0) {
        this.$keywordsInput = $('.etsy-keywords');
        this.$searchResultsList = $('.search-on-etsy-results ul');
        this.$saveButton = $('.search-on-etsy .button-save');
		this.eventListeners();
    }
};

app.Mywatchlists.prototype.eventListeners = function() {
	var self = this;
	console.log(self);
	self.$searchForm.submit(function(e) {
		e.preventDefault();
		if(self.$keywordsInput.val().length === 0) {
			return;
		}
		self.$searchResultsList.addClass('pending');
		self.$saveButton.removeClass('show');
		var url = '/mywatchlists/search?keywords='+self.$keywordsInput.val();
		$.ajax({
			url: url,
			dataType: 'json',
			success: function(data) {
				var results = '';
				for(var i = 0; i < data.length; i++) {
					results += self.getListingMarkup(data[i]);
				}
				if(data.length === 0) {
					results += '<li>No listings where found for this search.</li>';
				}
				self.$searchResultsList.html(results);
				self.$saveButton.addClass('show');
			},
			complete: function(){
				self.$searchResultsList.removeClass('pending');
			},
			error: function(a,b,c){
				console.log(a,b,c);
			}
		});
	});
};

app.Mywatchlists.prototype.getListingMarkup = function(listing) {
	console.log(listing);
	return '<li><a href="'+listing.url+'" title="'+listing.title+'" target="_blank"><img src="'+listing.MainImage.url_170x135+'" alt="'+listing.title+'" /></a><div class="listing-text clearfix"><a href="'+listing.url+'" title="'+listing.title+'" target="_blank"><h3 class="listing-title">'+listing.title+'</h3></a><a class="listing-shop" href="'+listing.Shop.url+'" title="Checkout '+listing.Shop.login_name+(listing.Shop.login_name.toLowerCase().substr(listing.Shop.login_name.length-1, 1) == 's' ? "'" : "'s")+' shop" target="_blank">'+listing.Shop.login_name+'</a><span class="listing-price">'+listing.currency_code+' '+listing.price+'</span></div></li>';
};
