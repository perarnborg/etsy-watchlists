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
		var $area = self.getArea($trigger);
		if($trigger.data('group') && !$trigger.hasClass('open')) {
			$(self.toggleTriggerSelector+'.open[data-group='+$trigger.data('group')+']').each(function() {
				$openTrigger = $(this);
				$openArea = self.getArea($openTrigger);
				$openTrigger.removeClass('open');
				$openArea.hide();
				$openArea.removeClass('open');
			});
		}
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

app.Toggle.prototype.getArea = function($trigger) {
	return $trigger.data('target') ? $($trigger.data('target')) : $trigger.next(self.toggleAreaSelector);
};

app.Mywatchlists = function() {
    this.$searchForm = $('.search-on-etsy-form');
    if(this.$searchForm.length > 0) {
        this.$keywordsInput = $('.etsy-keywords');
        this.$searchResultsList = $('.search-on-etsy-results ul');
        this.$saveButton = $('.search-on-etsy .button-save');
        this.$categories = $('.etsy-categories');
        this.categorySelector = '.etsy-categories a';
        this.$shipsto = $('.etsy-shipsto');
        this.$filter = $('.etsy-filters ul');
        this.filteredSelector = '.etsy-filters li a';
        this.setup();
		this.eventListeners();
    }
};

app.Mywatchlists.prototype.setup = function() {
    this.parameters = window.parameters;
    this.watchlist = window.watchlist;
    if(typeof(this.watchlist) == 'undefined') {
		this.watchlist = {id: 0, name: '', parameters: []};
    } else {
		this.$saveButton.val('Update<br>watchlist');
    }

};

app.Mywatchlists.prototype.getWatchlistParamater = function(apiName) {
	for(var i = 0; i < this.watchlist.parameters.length; i++) {
		if(this.watchlist.parameters[i].apiName == apiName) {
			return this.watchlist.parameters[i];
		}
	}
	return false;
};

app.Mywatchlists.prototype.getParamaterId = function(apiName) {
	for(var i = 0; i < this.parameters.length; i++) {
		if(this.parameters[i].apiName == apiName) {
			return this.parameters[i].id;
		}
	}
	return false;
};

app.Mywatchlists.prototype.setWatchlistParamater = function(apiName, value) {
	var existingIndex = false;
	for(var i = 0; i < this.watchlist.parameters.length; i++) {
		if(this.watchlist.parameters[i].apiName == apiName) {
			existingIndex = i;
		}
	}
	if(value !== false) {
		if(existingIndex !== false) {
			this.watchlist.parameters[existingIndex].value = value;
		} else {
			this.watchlist.parameters.push({parameterId: this.getParamaterId(apiName), apiName: apiName, value: value});
		}
	} else if(existingIndex !== false) {
		this.watchlist.parameters.splice(existingIndex, 1);
	}
};

app.Mywatchlists.prototype.eventListeners = function() {
	var self = this;
	self.$searchForm.submit(function(e) {
		e.preventDefault();
		if(self.getWatchlistParamater('keywords') === false) {
			return;
		}
		self.$searchResultsList.addClass('pending');
		self.$saveButton.removeClass('show');
		var url = '/mywatchlists/search';
		for(var i = 0; i < self.watchlist.parameters.length; i++) {
			url += (url.indexOf('?') == -1 ? '?' : '&') + self.watchlist.parameters[i].apiName + '=' + self.watchlist.parameters[i].value.split(' ').join(',');
		}
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
	self.$keywordsInput.keyup(function(e) {
		var value = $(this).val();
		self.setWatchlistParamater('keywords', (value.length > 0) ? value : false);
	});
	$(document).on('click', self.categorySelector, function(e) {
		e.preventDefault();
		var title = $(this).attr('title');
		var value = $(this).data('value');
		self.$categories.html();
		self.addFilter('category', value, title);
		self.listCategories(value, title);
	});
	$(self.$shipsto).change(function(){
		var value = $(this).val();
		if(value) {
			self.addFilter('shipsto', value);
		} else {
			self.removeFilter('shipsto');
		}
	});
	$(document).on('click', self.filteredSelector, function(e) {
		e.preventDefault();
		$item = $(this).closest('li');
		if($item.data('api-name') == 'category') {
			self.listCategories();
		}
		if($item.data('api-name') == 'shipsto') {
			self.$shipsto.val('');
		}
		self.removeFilter($item.data('api-name'));
	});
};

app.Mywatchlists.prototype.listCategories = function(topCategoryName, topCategoryTitle) {
	var self = this;
	$.ajax({
		url: '/mywatchlists/categories/'+(topCategoryName ? topCategoryName : ''),
		dataType: 'json',
		success: function(data) {
			var results = '';
			if(topCategoryTitle) {
				results += data.length > 0 ? ('Sub categories in ' + topCategoryTitle + '<br>') : ('No sub categories in ' + topCategoryTitle);
			}
			for(var i = 0; i < data.length; i++) {
				results += '<li><a href="#" data-value="'+data[i].name+'" title="'+data[i].long_name+'">'+data[i].short_name+'</a></li> ';
			}
			self.$categories.html(results);
		}
	});
};

app.Mywatchlists.prototype.addFilter = function(apiName, value, title) {
	this.removeFilter(apiName, true);
	this.setWatchlistParamater(apiName, value);
	if(!title) {
		title = value;
	}
	this.$filter.append('<li data-api-name="'+apiName+'" data-value="'+value+'"><a href="#">x</a>'+title+'</li>');
};

app.Mywatchlists.prototype.removeFilter = function(apiName, leaveInModel) {
	if(!leaveInModel) {
		this.setWatchlistParamater(apiName, false);
	}
	this.$filter.find('[data-api-name='+apiName+']').remove();
};

app.Mywatchlists.prototype.getListingMarkup = function(listing) {
	return '<li><a href="'+listing.url+'" title="'+listing.title+'" target="_blank"><img src="'+listing.MainImage.url_170x135+'" alt="'+listing.title+'" /></a><div class="listing-text clearfix"><a href="'+listing.url+'" title="'+listing.title+'" target="_blank"><h3 class="listing-title">'+listing.title+'</h3></a><a class="listing-shop" href="'+listing.Shop.url+'" title="Checkout '+listing.Shop.login_name+(listing.Shop.login_name.toLowerCase().substr(listing.Shop.login_name.length-1, 1) == 's' ? "'" : "'s")+' shop" target="_blank">'+listing.Shop.login_name+'</a><span class="listing-price">'+listing.currency_code+' '+listing.price+'</span></div></li>';
};
