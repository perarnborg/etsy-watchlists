var app = window.app || {};

$(document).ready(function () {
	new app.Toggle();
    app.modal = new app.Modal();
    app.flash = new app.Flash();
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

app.Modal = function() {
	this.modalClassName = 'modal';
	this.modalSelector = '.'+this.modalClassName;
	this.modalContentClassName = 'modal-content';
	this.modalContentSelector = '.'+this.modalContentClassName;
	this.modalCloseClassName = 'modal-close';
	this.modalCloseSelector = '.'+this.modalCloseClassName;
	this.modalIndex = 0;
	this.eventListeners();
};

app.Modal.prototype.eventListeners = function() {
	var self = this;
	$(document).on('click', self.modalCloseSelector, function(e){
		e.preventDefault();
		var $modal = $(this).closest(self.modalSelector);
		self.closeModal($modal);
	});
};

app.Modal.prototype.openModal = function($content) {
	var content = $content.html();
	var modalId = this.modalClassName+'-'+this.modalIndex;
	$('body').append('<div class="'+this.modalClassName+'" id="'+modalId+'"><div class="'+this.modalContentClassName+'"><a href="#" class="'+this.modalCloseClassName+' hidden-text">Close</a>'+content+'</div></div>');
	var $modal = $('#'+modalId);
	$modal.fadeIn(200);
	this.modalIndex++;
};

app.Modal.prototype.closeModal = function($modal) {
	$modal.fadeOut(200);
	setTimeout(function(){$modal.remove();}, 200);
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
        this.$settingsFormContainer = $('.settings-form-container');
        this.settingsFormSelector = '.modal .settings-form';
        this.watchlistNameSelector = '.modal .watchlist-name';
        this.setup();
		this.eventListeners();
    }
};

app.Flash = function() {
};

app.Flash.prototype.error = function(message) {
	alert(message);
};

app.Mywatchlists.prototype.setup = function() {
    this.parameters = window.parameters;
    this.watchlist = window.watchlist;
    if(typeof(this.watchlist) == 'undefined') {
		this.watchlist = {id: 0, name: '', watchlists_parameters: [], watchlists_listings: []};
    } else {
		this.$saveButton.val('Update<br>watchlist');
		$('h1').html(this.watchlist.name);
		for(var i = 0; i < this.watchlist.watchlists_parameters.length; i++) {
			var watchlistParameter = this.watchlist.watchlists_parameters[i];
			var apiName = this.getParameterApiName(watchlistParameter.parameters_id);
			if(apiName == 'keywords') {
				this.$keywordsInput.val(watchlistParameter.value);
			} else {
				this.addFilter(apiName, watchlistParameter.value, watchlistParameter.title, false);
				if(apiName == 'category') {
					this.listCategories(watchlistParameter.value, watchlistParameter.title);
				}
			}
		}
		var results = '';
		for(var j = 0; j < this.watchlist.watchlists_listings.length; j++) {
			results += this.getListingMarkup(this.watchlist.watchlists_listings[j]);
		}
		if(this.watchlist.watchlists_listings.length === 0) {
			results += '<li>No listings where found for this search.</li>';
		}
		this.$searchResultsList.html(results);
    }

};

app.Mywatchlists.prototype.getWatchlistParameter = function(apiName) {
	var id = this.getParameterId(apiName);
	for(var i = 0; i < this.watchlist.watchlists_parameters.length; i++) {
		if(this.watchlist.watchlists_parameters[i].parameters_id == id) {
			return this.watchlist.watchlists_parameters[i];
		}
	}
	return false;
};

app.Mywatchlists.prototype.getParameterId = function(apiName) {
	for(var i = 0; i < this.parameters.length; i++) {
		if(this.parameters[i].apiName == apiName) {
			return this.parameters[i].id;
		}
	}
	return false;
};

app.Mywatchlists.prototype.getParameterApiName = function(id) {
	for(var i = 0; i < this.parameters.length; i++) {
		if(this.parameters[i].id == id) {
			return this.parameters[i].apiName;
		}
	}
	return false;
};

app.Mywatchlists.prototype.setWatchlistParameter = function(apiName, value, title) {
	var existingIndex = false;
	var parameterId = this.getParameterId(apiName);
	for(var i = 0; i < this.watchlist.watchlists_parameters.length; i++) {
		if(this.watchlist.watchlists_parameters[i].parameters_id == parameterId) {
			existingIndex = i;
		}
	}
	if(value !== false) {
		if(existingIndex !== false) {
			this.watchlist.watchlists_parameters[existingIndex].value = value;
			this.watchlist.watchlists_parameters[existingIndex].title = title;
		} else {
			this.watchlist.watchlists_parameters.push({parameters_id: parameterId, value: value, title: title});
		}
	} else if(existingIndex !== false) {
		this.watchlist.watchlists_parameters.splice(existingIndex, 1);
	}
};

app.Mywatchlists.prototype.eventListeners = function() {
	var self = this;
	self.$searchForm.submit(function(e) {
		e.preventDefault();
		if(self.getWatchlistParameter('keywords') === false) {
			return;
		}
		self.$searchResultsList.addClass('pending');
		self.$saveButton.removeClass('show');
		var url = '/mywatchlists/search';
		for(var i = 0; i < self.watchlist.watchlists_parameters.length; i++) {
			url += (url.indexOf('?') == -1 ? '?' : '&') + self.getParameterApiName(self.watchlist.watchlists_parameters[i].parameters_id) + '=' + self.watchlist.watchlists_parameters[i].value.split(' ').join(',');
		}
		$.ajax({
			url: url,
			dataType: 'json',
			success: function(data) {
				var results = '';
				if(!self.watchlist.id) {
					self.watchlist.watchlists_listings = [];
				}
				for(var i = 0; i < data.length; i++) {
					var listing = self.getListingFromResponse(data[i]);
					results += self.getListingMarkup(listing);
					if(!self.watchlist.id) {
						self.watchlist.watchlists_listings.push(listing);
					}
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
		self.setWatchlistParameter('keywords', (value.length > 0) ? value : false);
	});
	$(document).on('click', self.categorySelector, function(e) {
		e.preventDefault();
		var title = $(this).attr('title');
		var value = $(this).data('value');
		self.$categories.html();
		self.addFilter('category', value, title, true);
		self.listCategories(value, title);
	});
	$(self.$shipsto).change(function(){
		var value = $(this).val();
		if(value) {
			self.addFilter('shipsto', value, false, true);
		} else {
			self.removeFilter('shipsto', true);
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
		self.removeFilter($item.data('api-name'), true);
	});
	self.$saveButton.click(function(e) {
		e.preventDefault();
		app.modal.openModal(self.$settingsFormContainer);
	});
	$(document).on('submit', self.settingsFormSelector, function(e) {
		if(false) {
			self.watchlist.name = $(self.watchlistNameSelector).val();
			$('.watchlist-data').val($.stringify(self.watchlist));
			return true;
		}
		e.preventDefault();
		self.watchlist.name = $(self.watchlistNameSelector).val();
		if(self.watchlist.name.length > 0) {
			self.saveWatchlist();
		}
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

app.Mywatchlists.prototype.addFilter = function(apiName, value, title, performSearch) {
	this.removeFilter(apiName, false);
	this.setWatchlistParameter(apiName, value, title);
	if(!title) {
		title = value;
	}
	this.$filter.append('<li data-api-name="'+apiName+'" data-value="'+value+'"><a href="#">x</a>'+title+'</li>');
	if(performSearch) {
		this.$searchForm.submit();
	}
};

app.Mywatchlists.prototype.removeFilter = function(apiName, performSearch) {
	this.$filter.find('[data-api-name='+apiName+']').remove();
	if(performSearch) {
		this.setWatchlistParameter(apiName, false);
		this.$searchForm.submit();
	}
};

app.Mywatchlists.prototype.saveWatchlist = function() {
	var self = this;
	var watchlist = $.stringify(self.watchlist);
	var url = '/mywatchlists/save';
	self.$saveButton.addClass('loading');
	console.log(url);
	$.ajax({
		type: "POST",
		url: url,
		data: {watchlist: watchlist},
		success: function(data) {
			document.location = '/mywatchlists';
		},
		error: function() {
			app.flash.error('Could not save this watchlist right now');
		},
		complete: function() {
			self.$saveButton.removeClass('loading');
		}
	});
};

app.Mywatchlists.prototype.getListingFromResponse = function(listingResponse) {
	var listing = {
		listing_id: listingResponse.listing_id,
		title: listingResponse.title,
		url: listingResponse.url,
		image_thumb_url: listingResponse.MainImage.url_75x75,
		image_url: listingResponse.MainImage.url_170x135,
		shop_loginname: listingResponse.Shop.login_name,
		shop_url: listingResponse.Shop.url,
		currency_code: listingResponse.currency_code,
		price: listingResponse.price,
		creation: listingResponse.creation_tsz
	};
	return listing;
};

app.Mywatchlists.prototype.getListingMarkup = function(listing) {
	return '<li><a href="'+listing.url+'" title="'+listing.title+'" target="_blank"><img src="'+listing.image_url+'" alt="'+listing.title+'" /></a><div class="listing-text clearfix"><a href="'+listing.url+'" title="'+listing.title+'" target="_blank"><h3 class="listing-title">'+listing.title+'</h3></a><a class="listing-shop" href="'+listing.shop_url+'" title="Checkout '+listing.shop_loginname+(listing.shop_loginname.toLowerCase().substr(listing.shop_loginname.length-1, 1) == 's' ? "'" : "'s")+' shop" target="_blank">'+listing.shop_loginname+'</a><span class="listing-price">'+listing.currency_code+' '+listing.price+'</span></div></li>';
};
