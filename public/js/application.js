var app = window.app || {};

$(document).ready(function () {
	new app.Toggle();
	app.utils = new app.Utils();
    app.modal = new app.Modal();
    app.flash = new app.Flash();
    app.myWatchlists = new app.Mywatchlists();
    app.loadMore = new app.LoadMore();
});

app.Utils = function() {
};

app.Utils.prototype.executeFunctionByName = function(functionName, argument) {
	var fn = window;
	var functionParts = functionName.split('.');
	for(var i = 0; i < functionParts.length; i++) {
		fn = fn[functionParts[i]];
	}
	if(typeof(fn) == 'function') {
		return fn(argument);
	}
};

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

app.LoadMore = function() {
	this.$loadMoreButton = $('.load-more');
	if(this.$loadMoreButton.length > 0) {
		this.$window = $(window);
		this.$document = $(document);
		this.offset = 0;
		this.itemSelector = this.$loadMoreButton.data('item-selector');
		this.pageSize = this.$loadMoreButton.data('page-size');
		this.$itemWrapper = $(this.$loadMoreButton.data('item-wrapper-selector'));
		this.url = this.$loadMoreButton.data('url');
		this.itemCallback = this.$loadMoreButton.data('item-callback');
		this.infinateScroll = !Modernizr.touch;
		if(this.$itemWrapper.length) {
			if($(this.itemSelector).length < this.pageSize) {
				this.reachedEnd();
			} else {
				this.eventListeners();
			}
		}
	}
};

app.LoadMore.prototype.eventListeners = function() {
	var self = this;
	self.$loadMoreButton.click(function(e) {
		if(!self.hasReachedEnd) {
			self.loadMore();
		}
	});
	if(self.infinateScroll) {
		self.$loadMoreButton.hide();
		self.setHeights();
        self.$window.scroll(function() {
          if (!self.hasReachedEnd && !self.hasError) {
            self.checkBottom();
          }
        }).resize(function() {
            self.setHeights();
        });
    }
};

app.LoadMore.prototype.setHeights = function() {
  this.bottomBuffer = 150;
  this.winHeight = this.$window.height();
  this.docHeight = this.$document.height();
};

app.LoadMore.prototype.reachedEnd = function() {
	this.hasReachedEnd = true;
	this.$loadMoreButton.hide();
};

app.LoadMore.prototype.checkBottom = function() {
  var scrollTop = this.$window.scrollTop();
  if (scrollTop >= (this.docHeight - this.winHeight - this.bottomBuffer) && !this.isLoading && !this.hasReachedEnd) {
    this.loadMore();
  }
};

app.LoadMore.prototype.loadMore = function() {
	var self = this;
	self.offset += this.pageSize;
	var url = self.url+'?offset='+self.offset+'&pageSize='+self.pageSize;
	if(self.itemCallback) {
		url += '&format=json';
	}
	self.isLoading = true;
	self.$itemWrapper.addClass('loading');
	$.ajax({
		url: url,
		dataType: (self.itemCallback ? 'json' : 'html'),
		success: function(data) {
			self.onSuccess(data);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.log(jqXHR, textStatus, errorThrown);
			self.onError(errorThrown);
		},
		complete: function() {
			self.isLoading = false;
			self.$itemWrapper.removeClass('loading');
		}
	});
};

app.LoadMore.prototype.onSuccess = function(data) {
	this.hasError = false;
	if(data && data.length) {
		var html = '';
		if(this.itemCallback) {
			for(var i = 0; i < data.length; i++) {
				html += app.utils.executeFunctionByName(this.itemCallback, data[i]);
			}
		} else {
			html = data;
		}
		this.$itemWrapper.append(html);
		this.setHeights();
	} else {
		this.reachedEnd();
	}
};

app.LoadMore.prototype.onError = function(errorThrown) {
	if(errorThrown == 'Not Found') {
		// Reached end if response is 404
		this.reachedEnd();
	} else {
		this.hasError = true;
		this.offset -= this.pageSize;
		this.$loadMoreButton.show();
	}
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
        this.watchlistEmailIntervalSelector = '.modal .watchlist-email-interval';
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
		this.$saveButton.html('Update<br>watchlist').addClass('show');
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
		var hasUnviewed = false;
		for(var j = 0; j < this.watchlist.watchlists_listings.length; j++) {
			results += this.getListingMarkup(this.watchlist.watchlists_listings[j]);
			if(!this.watchlist.watchlists_listings[j].is_viewed) {
				hasUnviewed = true;
			}
		}
		if(this.watchlist.watchlists_listings.length === 0) {
			results += '<li>No listings where found for this search.</li>';
		}
		this.$searchResultsList.html(results);
		if(hasUnviewed) {
			this.setListingsAsViewed();
		}
    }
};

app.Mywatchlists.prototype.setListingsAsViewed = function() {
	var url = '/mywatchlists/setlistingsasviewed/'+this.watchlist.id;
	$.ajax({
		url: url
	});
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
		if(this.parameters[i].api_name == apiName) {
			return this.parameters[i].id;
		}
	}
	return false;
};

app.Mywatchlists.prototype.getParameterApiName = function(id) {
	for(var i = 0; i < this.parameters.length; i++) {
		if(this.parameters[i].id == id) {
			return this.parameters[i].api_name;
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
			self.addFilter('shipsto', value, 'Ships to ' + value, true);
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
		self.watchlist.name = $(self.watchlistNameSelector).val();
		self.watchlist.email_interval = $(self.watchlistEmailIntervalSelector).val() || null;
		if(false) {
			self.watchlist.name = $(self.watchlistNameSelector).val();
			$('.watchlist-data').val($.stringify(self.watchlist));
			return true;
		}
		e.preventDefault();
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
		creation: listingResponse.creation_tsz,
		is_viewed: 1,
		is_emailed: 0
	};
	return listing;
};

app.Mywatchlists.prototype.getListingMarkup = function(listing) {
	return '<li class="listing '+(listing.is_viewed ? '' : ' listing-new')+'"><a href="'+listing.url+'" title="'+listing.title+'" target="_blank"><img src="'+listing.image_url+'" alt="'+listing.title+'" /></a><div class="listing-text clearfix"><a href="'+listing.url+'" title="'+listing.title+'" target="_blank"><h3 class="listing-title">'+listing.title+'</h3></a><a class="listing-shop" href="'+listing.shop_url+'" title="Checkout '+listing.shop_loginname+(listing.shop_loginname.toLowerCase().substr(listing.shop_loginname.length-1, 1) == 's' ? "'" : "'s")+' shop" target="_blank">'+listing.shop_loginname+'</a><span class="listing-price">'+listing.currency_code+' '+listing.price+'</span></div></li>';
};
