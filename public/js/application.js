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
	$(document).on('click', self.toggleTriggerSelector, function(e) {
		e.preventDefault();
		var $trigger = $(this);
		var $area = $trigger.next(self.toggleAreaSelector);
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
        this.$categories = $('.etsy-categories');
        this.categorySelector = '.etsy-categories a';
        this.$categoryFilter = $('.etsy-category-filters ul');
        this.$filter = $('.etsy-other-filters ul');
        this.filteredSelector = '.etsy-filter li a';
		this.eventListeners();
    }
};

app.Mywatchlists.prototype.eventListeners = function() {
	var self = this;
	self.$searchForm.submit(function(e) {
		e.preventDefault();
		if(self.$keywordsInput.val().length === 0) {
			return;
		}
		self.$searchResultsList.addClass('pending');
		self.$saveButton.removeClass('show');
		var url = '/mywatchlists/search?keywords='+self.$keywordsInput.val().split(' ').join(',');
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
			}
		});
	});
	$(document).on('click', self.categorySelector, function(e) {
		e.preventDefault();
		var title = $(this).html();
		var value = $(this).data('value');
		self.$categories.html();
		self.addFilter('category', value, title);
		$.ajax({
			url: '/mywatchlists/categories/'+value,
			dataType: 'json',
			success: function(data) {
				var results = '';
				for(var i = 0; i < data.length; i++) {
					console.log(data[i]);
					results += '<li><a href="#" data-value="'+data[i].name+'">'+data[i].short_name+'</a></li> ';
				}
				self.$categories.html(results);
			}
		});
	});
};

app.Mywatchlists.prototype.addFilter = function(apiName, value, title) {
	var $filter = this.$filter;
	if(apiName == 'category') {
		$filter = this.$categoryFilter;
	}
	$filter.find('[data-api-name='+apiName+']').remove();
	if(!title) {
		title = value;
	}
	$filter.append('<li data-api-name="'+apiName+'" data-value="'+value+'"><a href="#">x</a>'+title+'</li>');
};

app.Mywatchlists.prototype.getListingMarkup = function(listing) {
	return '<li><a href="'+listing.url+'" title="'+listing.title+'" target="_blank"><img src="'+listing.MainImage.url_170x135+'" alt="'+listing.title+'" /></a><div class="listing-text clearfix"><a href="'+listing.url+'" title="'+listing.title+'" target="_blank"><h3 class="listing-title">'+listing.title+'</h3></a><a class="listing-shop" href="'+listing.Shop.url+'" title="Checkout '+listing.Shop.login_name+(listing.Shop.login_name.toLowerCase().substr(listing.Shop.login_name.length-1, 1) == 's' ? "'" : "'s")+' shop" target="_blank">'+listing.Shop.login_name+'</a><span class="listing-price">'+listing.currency_code+' '+listing.price+'</span></div></li>';
};
