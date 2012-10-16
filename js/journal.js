String.prototype.unEscape = function(){
	str = this;
	return str.replace(/\\"/g, '"');
};
String.prototype.stripTags = function(){
	tags = this;
	stripped = tags.replace(/<(.|\n)*?>/g, '');
	return stripped;
};
String.prototype.zeroPad = function(digits) {
	n = this.toString();
	while (n.length < digits) {
		n = '0' + n;
	}
	return n;
}

	/**
	 * Opens a popup with the setting for an app.
	 * @param args.selector String. A jquery selector that resolves to a single DOM element where the bubble help will be positioned..
	 * @param args.content String. The text or HTML to fill in the bubble help.
	 * @param cb function. A function that if provided will be called when the help is closed.
	 */
	OC.popupHelp = function(args, cb) {
		if(typeof args === 'undefined' || typeof args.selector != 'string') {
			throw { name: 'InvalidParameter', message: 'The parameter \'selector\' is missing or not a string.' };
		}
		if(typeof args === 'undefined' || typeof args.content != 'string') {
			throw { name: 'InvalidParameter', message: 'The parameter \'content\' is missing or not a string.' };
		}
		var obj = $(args.selector);
		if(obj.length !== 1) {
			throw {
				name: 'InvalidParameter',
				message: 'The parameter \'selector\' must only resolve to one DOM element. '
					+ 'It resolved to ' + obj.length
			};
		}
		var arrowclass = 'topleft'; //settings.hasClass('topright') ? 'up' : 'left';
		var pos = obj.offset();
		var width = $('body').width();
		var height = $('body').height();
		//console.log('w/h:', width, height)
		var popup = $('<div class="popup hidden"></div>');
		$('body').prepend(popup);
		//popup.addClass(settings.hasClass('topright') ? 'topright' : 'bottomleft');
		popup.html(args.content).show();
		var posx = parseInt(pos.left + (obj.outerWidth()/2));
		var posy = parseInt(pos.top + obj.outerHeight());
		//console.log('posx/width:', popup.innerWidth()+posx, width)
		if(posx + popup.outerWidth() > width) {
			//console.log('-' + parseInt(popup.innerWidth() + (obj.outerWidth()/2)))
			posx = posx - popup.innerWidth();
			arrowclass = 'topright';
		}
		if(posy + popup.outerHeight() > height) {
			posy = posy - popup.outerHeight();
		}
		//console.log('popup:', popup.outerWidth(), popup.outerHeight());
		//console.log('x/y:', posx, posy)
		popup.prepend('<span class="arrow '+arrowclass+'"></span><a class="close svg"></a>');
		popup.css({'left':posx, 'top':posy});
		popup.find('.close').bind('click', function() {
				popup.remove();
				if(typeof cb == 'function') {
					cb();
				}
			});
		popup.show();
	}


OC.Journal = {
	categories:undefined,
	init:function() {
		self = this;
		this.setEnabled(false);
		// Fetch journal entries. If it's a direct link 'id' will be loaded.
		OC.Journal.Journals.update(id);
		$.getJSON(OC.filePath('journal', 'ajax', 'categories/list.php'), function(jsondata) {
			if(jsondata.status == 'success') {
				OC.Journal.categories = jsondata.data.categories;
			} else {
				OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
			}
		});
	},
	/**
	 * Arguments:
	 * message: The text message to show. The only mandatory parameter.
	 * timeout: The timeout in seconds before the notification disappears. Default 10.
	 * timeouthandler: A function to run on timeout.
	 * clickhandler: A function to run on click. If a timeouthandler is given it will be cancelled.
	 * data: An object that will be passed as argument to the timeouthandler and clickhandler functions.
	 */
	notify:function(params) {
		self = this;
		if(!self.notifier) {
			self.notifier = $('#notification');
		}
		self.notifier.text(params.message);
		self.notifier.fadeIn();
		self.notifier.on('click', function() { $(this).fadeOut();});
		var timer = setTimeout(function() {
			self.notifier.fadeOut();
			if(params.timeouthandler && $.isFunction(params.timeouthandler)) {
				params.timeouthandler(self.notifier.data(dataid));
				self.notifier.off('click');
				self.notifier.removeData(dataid);
			}
		}, params.timeout && $.isNumeric(params.timeout) ? parseInt(params.timeout)*1000 : 10000);
		var dataid = timer.toString();
		if(params.data) {
			self.notifier.data(dataid, params.data);
		}
		if(params.clickhandler && $.isFunction(params.clickhandler)) {
			self.notifier.on('click', function() {
				clearTimeout(timer);
				self.notifier.off('click');
				params.clickhandler(self.notifier.data(dataid));
				self.notifier.removeData(dataid);
			});
		}
	},
	categoriesChanged:function(newcategories) { // Categories added/deleted.
		this.categories = $.map(newcategories, function(v) {return v;});
		$('#categories').multiple_autocomplete('option', 'source', this.categories);
	},
	propertyContainerFor:function(obj) {
		if($(obj).hasClass('propertycontainer')) {
			return $(obj);
		}
		return $(obj).parents('.propertycontainer').first();
	},
	required:function(event){ // eventhandler for required elements
			// FIXME: This doesn't seem to work.
			console.log('blur on required');
			var obj = $(event.target);
			$(obj).addClass('required');
			if($(this).val().trim() == '') {
				$('<strong>This field is required!</strong>').appendTo($(obj));
				return;
			} else {
				$(obj).removeClass('required');
				$(obj).off('blur', OC.Journal.required);
			}
	},
	setEnabled:function(state) {
		if(typeof state == 'undefined') { state = true; }
		console.log('OC.Journal.setEnabled: ' + state);
		if(state) {
			$('#description').rte('setEnabled', true);
			if($('#description').rte('mode') == 'html') {
				$('#editortoolbar li').show();
			}
			$('#togglemode').show();
			$('#summary').addClass('editable');
			$('.property,#also_time').each(function () {
				$(this).prop('disabled', false);
			});
			if(!OC.Journal.singlecalendar) {
				$('#calendar').prop('disabled', false);
			}
		} else {
			$('#description').rte('setEnabled', false);
			$('#editortoolbar .richtext, #togglemode').hide();
			$('#summary').removeClass('editable');
			$('.property,#also_time').each(function () {
				$(this).prop('disabled', true);
			});
		}
	},
	toggleMode:function() {
		console.log('togglemode');
		$('#description').rte('toggleMode');
		$('#editortoolbar li.richtext').toggle();
	},
	Entry:{
		id:'',
		data:undefined,
		add:function() {
			// TODO: wrap a DIV around the summary field with a suggestion(?) to fill out this field first. See OC.Journal.required
			// Remember to reenable all controls.
			$('#entry,#metadata').show();
			$('#firstrun').hide();
			$('#description').rte({classes: ['property','content']});
			OC.Journal.setEnabled(false);
			$('#summary').prop('disabled', false);
			$('#summary').addClass('editable');
			$('#leftcontent lidata-id="'+this.id+'"').removeClass('active');
			this.id = 'new';
			this.data = undefined;
			$('.property').each(function () {
				switch($(this).get(0).nodeName) {
					case 'DIV':
						$(this).html('');
						break;
					case 'INPUT':
					case 'TEXTAREA':
						$(this).val('');
						break;
					default:
						console.log('OC.Journal.Entry.add. Forgot: ' + $(this).get(0).nodeName);
						break;
				}
			});
			//$('#description').rte('setEnabled', false);
			$('#editortoolbar li.richtext').hide();
			$('#editable').attr('checked', true);
			$('#actions').hide();
		},
		createEntry:function(data) {
			var sharedindicator = data.owner == OC.currentUser ? ''
				: '<img class="shared svg" src="'+OC.imagePath('core', 'actions/shared')+'" title="'+t('journal', 'Shared by ')+data.owner+'" />'
			var date = new Date(parseInt(data.dtstart)*1000);
			var timestring = (data.only_date?'':' ' + date.toLocaleTimeString());
			return $('<li data-id="'+data.id+'"><a href="'+OC.linkTo('journal', 'index.php')+'&id='+data.id+'">'+data.summary.unEscape()+'</a>'
				+ sharedindicator + '<br /><em>'+date.toDateString()+timestring+'<em></li>').data('entry', data);
		},
		loadEntry:function(id, data) {
			console.log('loadEntry: ' + id + ': ' + data.summary);
			this.permissions = parseInt(data.permissions);
			this.readonly = !(this.permissions & OC.PERMISSION_UPDATE);
			console.log('permissions:', this.permissions);
			console.log('readonly:', this.readonly);
			$('#editable').prop('disabled', this.readonly)
				.next('label').text(this.readonly ? t('journal', 'Read-only') : t('journal', 'Edit'))
				.attr('title', this.readonly ? t('journal', 'This entry is read-only') : t('journal', 'Set this journal entry in edit mode'));
			$('#actions').show().find('a.share').attr('data-item', id).attr('data-possible-permissions', data.permissions).attr('style', null);

			this.id = id;
			this.cid = data.calendarid;
			this.data = data;
			$('#entry').data('id', id);
			$('#calendar').val(data.calendarid);
			$('#summary').val(data.summary.unEscape());
			$('#organizer').val(data.organizer.value.split(':')[1]);

			var format = data.description.format;
			console.log('format: '+format);
			$('#description').rte(format, data.description.value.unEscape());
			$('#description').rte('mode', format);
			(format=='html'&&$('#editable').is(':checked')
				? $('#editortoolbar li.richtext').show()
				: $('#editortoolbar li.richtext').hide());

			$('#location').val(data.location);

			$('#categories').val(data.categories.join(','));
			$('#categories').multiple_autocomplete('option', 'source', OC.Journal.categories);

			var date = new Date(parseInt(data.dtstart)*1000);
			if(Modernizr.inputtypes.date) {
				$('#dtstartdate').val($.datepicker.formatDate('yy-mm-dd', date)); //
			} else {
				$('#dtstartdate').datepicker('setDate', date);
			}
			if(data.only_date) {
				$('#dtstarttime').val('').hide();
				$('#also_time').attr('checked', false);
				//$('#also_time').get(0).checked = false;
			} else {
				// timepicker('setTime', ...) triggers a 'change' event, so you have to jump through hoops here ;-)
				$('#dtstarttime').val(date.getHours().toString().zeroPad(2)+':'+date.getMinutes().toString().zeroPad(2));
				$('#dtstarttime').show();
				$('#also_time').attr('checked', true);
				//$('#also_time').get(0).checked = true;
			}
			console.log('dtstart: '+date);

			OC.Share.loadIcons('journal');
		},
		saveproperty:function(obj) {
			if(!this.id || this.id == 'new') { // we are adding an entry and want a response back from the server.
				this.id = 'new';
				this.cid = $('#calendar').val();
				console.log('OC.Journal.Entry.saveproperty: We need to add a new one.');
				//return;
			}
			var container = OC.Journal.propertyContainerFor(obj);
			var params = {'id':this.id, cid:this.cid};
			params['type'] = container.data('type');
			params['parameters'] = {};
			switch(params['type']) {
				case 'ORGANIZER':
				case 'LOCATION':
				case 'CATEGORIES':
					params['value'] = $(obj).val();
					break;
				case 'SUMMARY':
					if(this.id == 'new' && $(obj).val().trim() == '') {
						$(obj).focus();
						$(obj).addClass('required');
						$(obj).on('blur', OC.Journal.required);
						return;
					}
					params['value'] = $(obj).val();
					break;
				case 'DESCRIPTION':
					// Check if we get the description from the textarea or the contenteditable.
					var format = ($(obj).get(0).nodeName == 'DIV' ? 'html' : 'text'); // FIXME: should check rte instead.
					var value = $('#description').rte(format); // calls either the 'text' or 'html' method of the rte.
					//var value = ($(obj).get(0).nodeName == 'DIV' ? $(obj).html() : $(obj).text());
					console.log('nodeName: ' + $(obj).get(0).nodeName);
					params['value'] = value;
					params['parameters']['FORMAT'] = format.toUpperCase();
					break;
				case 'DTSTART':
					// FIXME: I need a RegEx wizard to validate the format of these.
					var date = $('#dtstartdate').val() || $.datepicker.formatDate('yy-mm-dd', new Date());
					var time = $('#dtstarttime').val() || '00:00';
					/* Why doesn't this work?
					 * var datetime = new Date(
						parseInt(date.substr(0, 4), 10), // Year
						parseInt(date.substr(5, 2), 10), // Month
						parseInt(date.substr(8, 2), 10), // Date
						parseInt(time.substr(0, 2), 10), // Hours
						parseInt(time.substr(3, 2), 10), 0, 0); // Minutes and seconds*/
					var datetime = $.datepicker.parseDate('yy-mm-dd', date);
					if($('#also_time').is(':checked')) {
						datetime.setHours(parseInt(time.substr(0, 2), 10));
						datetime.setMinutes(parseInt(time.substr(3, 2), 10));
					}
					console.log('saveproperty, DTSTART', date, time, datetime);
					params['value'] = datetime.getTime()/1000;
					break;
				default:
					$.extend(1, $(obj).serializeArray(), params);
					break;
			}
			self = this;
			$.post(OC.filePath('journal', 'ajax', 'saveproperty.php'), params, function(jsondata) {
				if(jsondata.status == 'success') {
					if(self.id == 'new') {
						self.loadEntry(jsondata.data.id, jsondata.data);
						OC.Journal.setEnabled(true);
					} else {
						$('#leftcontent li[data-id="'+self.id+'"]').remove();
					}
					var item = self.createEntry(jsondata.data);
					$('#leftcontent').append(item).find('img.shared').tipsy();
					OC.Journal.Journals.doSort();
					OC.Journal.Journals.scrollTo(self.id);
					console.log('successful save');
				} else if(jsondata.status == 'error') {
					OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
				} else {
					console.log('saveproperty: Unknown return value');
				}
			});
		},
		moveToCalendar:function(calendarid) {
			self = this;
			$.post(OC.filePath('journal', 'ajax', 'movetocalendar.php'), {'id':this.id, 'calendarid':calendarid}, function(jsondata) {
				if(jsondata.status == 'success') {
					console.log('successful move');
				} else if(jsondata.status == 'error') {
					OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
				} else {
					console.log('saveproperty: Unknown return value');
				}
			});
		},
		doExport:function() {
			document.location.href = OC.linkTo('journal', 'export.php') + '?id=' + this.id;
		},
		doDelete:function() {
			// TODO: Do something when there are no more entries.
			if(this.id == 'new') { return; }
			$('#delete').tipsy('hide');
			self = this;
			OC.dialogs.confirm(t('contacts', 'Are you sure you want to delete this entry?'), t('journal', 'Warning'), function(answer) {
				if(answer == true) {
					$.post(OC.filePath('journal', 'ajax', 'delete.php'), {'id': self.id}, function(jsondata) {
						if(jsondata.status == 'success') {
							var curlistitem = $('#leftcontent li[data-id="'+self.id+'"]');
							var newlistitem = curlistitem.prev('li');
							if(!$(newlistitem).is('li')) {
								newlistitem = curlistitem.next('li');
							}
							curlistitem.remove();
							if(!$(newlistitem).is('li')) {
								OC.Journal.Journals.update();
								//alert('No more entries. Do something!!!');
							}
							console.log('newlistitem: ' + newlistitem.toString());
							if(newlistitem.length > 0) {
								$(newlistitem).addClass('active');
								var data = newlistitem.data('entry');
								self.loadEntry(data.id, data);
							}
							console.log('successful delete');
						} else {
							OC.dialogs.alert(jsondata.data.message.text, t('contacts', 'Error'));
						}
					});
				}
			});
		},
	},
	Journals:{
		sortmethod:'dtasc',
		filterDateRange:function() {
			if(!$('#daterangefrom').val() || ! $('#daterangeto').val())
			var start, end;
			if(Modernizr.inputtypes.date) {
				var dateparts = $('#daterangefrom').val().split('-');
				if(dateparts.length < 3) {
					return;
				}
				start = new Date(dateparts[0], dateparts[1], dateparts[2]);
			} else {
				start = $('#daterangefrom').datepicker('getDate');
			}
			if(start == null) {
				return;
			}
			if(Modernizr.inputtypes.date) {
				var dateparts = $('#daterangeto').val().split('-');
				if(dateparts.length < 3) {
					return;
				}
				end = new Date(dateparts[0], dateparts[1], dateparts[2]);
			} else {
				end = $('#daterangeto').datepicker('getDate');
			}
			if(end == null) {
				return;
			}
			console.log('filterDateRange', start, end);
			$('#leftcontent li').each(function () {
				var data = $(this).data('entry');
				var dtstart = new Date(parseInt(data.dtstart)*1000);
				//console.log('dtstart', dtstart);
				if(dtstart >= start && dtstart <= end) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		},
		doSort:function(method) {
			if(method) {
				this.sortmethod = method;
			} else {
				 method = this.sortmethod;
			}
			// Thanks to http://www.java2s.com/Tutorial/JavaScript/0220__Array/Usinganalphabeticalsortmethodonstrings.html
			// and http://stackoverflow.com/questions/4258974/sort-list-based-on-data-attribute-using-jquery-metadata-plugin#4259074
			// and http://stackoverflow.com/questions/8882418/jquery-sorting-lib-that-supports-multilanguage-sorting
			compareDateTimeAsc = function(a, b){
				return (parseInt(a.dtstart) > parseInt(b.dtstart)?-1:1);
			}
			compareDateTimeDesc = function(a, b){
				return (parseInt(b.dtstart) > parseInt(a.dtstart)?-1:1);
			}
			compareSummaryAsc = function(a, b){
				return b.summary.toLowerCase().localeCompare(a.summary.toLowerCase());
			}
			compareSummaryDesc = function(a, b){
				return a.summary.toLowerCase().localeCompare(b.summary.toLowerCase());
			}
			var func;
			switch(method) {
				case 'dtasc':
					func = compareDateTimeAsc;
					break;
				case 'dtdesc':
					func = compareDateTimeDesc;
					break;
				case 'sumasc':
					func = compareSummaryAsc;
					break;
				case 'sumdesc':
					func = compareSummaryDesc;
					break;
				default:
					var func = compareDateTimeDesc;
					break;
			}

			var arr = []
			// loop through each list item and get the metadata
			$('#leftcontent').children('li:visible').each(function () {
				var meta = $(this).data('entry');
				meta.elem = $(this);
				arr.push(meta);
			});
			arr.sort(func);

			//Foreach item append it to the container. The first i arr will then end up in the top
			$.each(arr, function(index, item){
				item.elem.appendTo(item.elem.parent());
			});
		},
		update:function(id) {
			this.owners = [];
			console.log('update: ' + id);
			self = this;
			$('#leftcontent').addClass('loading');
			$.getJSON(OC.filePath('journal', 'ajax', 'entries.php'), function(jsondata) {
				if(jsondata.status == 'success') {
					OC.Journal.singlecalendar = Boolean(jsondata.data.singlecalendar);
					if(OC.Journal.singlecalendar) {
						$('#calendar').val(jsondata.data.cid).prop('disabled', true);
					}
					var entries = $('#leftcontent').empty();
					if(jsondata.data.entries.length > 0) {
						$(jsondata.data.entries).each(function(i, entry) {
							entries.append(OC.Journal.Entry.createEntry(entry)).find('img.shared').tipsy();
							if(OC.Journal.Journals.owners.indexOf(entry.owner) === -1) {
								OC.Journal.Journals.owners.push(entry.owner);
							}
						});
						console.log('owners', OC.Journal.Journals.owners.length);
						OC.Journal.Journals.doSort('dtasc');
						var firstitem;
						if(id) {
							firstitem = $('#leftcontent li[data-id="'+id+'"]');
						} else {
							firstitem = $('#leftcontent li').first();
							if(firstitem.length == 0) {
								return;
							}
							id = firstitem.data('entry').id;
						}
						firstitem.addClass('active');
						OC.Journal.Journals.scrollTo(id);
						OC.Journal.Entry.loadEntry(firstitem.data('id'), firstitem.data('entry'));
						$('#entry,#metadata').show();
						$('#firstrun').hide();
					} else {
						$('#description').rte('destroy');
						$('#entry,#metadata').hide();
						$('#firstrun').show();
					}
				} else {
					OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
				}
			});
			$('#leftcontent').removeClass('loading');
		},
		scrollTo:function(id){
			var item = $('#leftcontent li[data-id="'+id+'"]');
			if(item) {
				try {
					$('#leftcontent').animate({scrollTop: $('#leftcontent li[data-id="'+id+'"]').offset().top-70}, 'slow','swing');
				} catch(e) {}
			}
		}
	}
};

$(document).ready(function(){
	OCCategories.changed = OC.Journal.categoriesChanged;
	OCCategories.app = 'journal';

	// Initialize controls.
	$('#categories').multiple_autocomplete({source: OC.Journal.categories});
	//$('#categories').multiple_autocomplete('option', 'source', categories);
	if(!Modernizr.inputtypes.date) {
		$('#dtstartdate').datepicker({dateFormat: 'dd-mm-yy'});
	}
	if(!Modernizr.inputtypes.time) {
		$('#dtstarttime').timepicker({timeFormat: 'hh:mm', showPeriodLabels:false});
	}
	$('#description').rte({classes: ['property','content']});
	$('.tip').tipsy();

	OC.Journal.init();

	$('#controls').on('click', '.cog', function(event) {
		OC.appSettings({appid:'journal', loadJS:true, cache:false});
	});

	// Show the input with a direct link the journal entry, binds an event to close
	// it on blur and removes the binding again afterwards.
	$('#showlink').on('click', function(event) {
		console.log('showlink');
		$('#link').toggle('slow').val(totalurl+'&id='+OC.Journal.Entry.id).focus().
			on('blur',function(event) {$(this).hide()}).off('blur', $(this));
		return false;
	});

	$('#rightcontent').on('change', '.property', function(event) {
		OC.Journal.Entry.saveproperty(this);
	});

	$('#controls').on('click', '#add', function(event) {
		OC.Journal.Entry.add();
	});

	$('#controls').on('change', '#daterangesort', function(event) {
		var drfrom = $('#daterangefrom');
		var drto = $('#daterangeto');
		if($(this).is(':checked')) {
			drfrom.prop('disabled', false);
			if(!Modernizr.inputtypes.date) {
				drfrom.datepicker({
					dateFormat: 'dd-mm-yy',
					changeMonth: true,
					changeYear: true
				});
			}
			drto.prop('disabled', false);
			if(!Modernizr.inputtypes.date) {
				drto.datepicker({
					dateFormat: 'dd-mm-yy',
					changeMonth: true,
					changeYear: true
				});
			}
			OC.Journal.Journals.filterDateRange();
		} else {
			drfrom.prop('disabled', true);
			drto.prop('disabled', true);
			if(!Modernizr.inputtypes.date) {
				drfrom.datepicker('destroy');
				drto.datepicker('destroy');
			}
			$('#leftcontent li').show();
		}
	});

	$('#controls').on('change input', '#daterangefrom,#daterangeto', function(event) {
		console.log('daterange change')
		OC.Journal.Journals.filterDateRange();
	});

	$('#metadata').on('change', '#calendar', function(event) {
		OC.Journal.Entry.moveToCalendar($(event.target).val());
	});

	$('#metadata').on('change', '#also_time', function(event) {
		$('#dtstarttime').toggle().trigger('change');
	});

	$('#metadata').on('click', '#export', function(event) {
		OC.Journal.Entry.doExport();
	});

	$('#metadata').on('click', '#editcategories', function(event) {
		$(this).tipsy('hide');
		OCCategories.edit();
	});

	$('#metadata').on('click', '#delete', function(event) {
		console.log('delete clicked');
		OC.Journal.Entry.doDelete();
	});

	$('#controls').on('change', '#entrysort', function(event) {
		OC.Journal.Journals.doSort($(this).val());
	});

	// Proxy click.
	$('#leftcontent').on('keydown', '#leftcontent', function(event) {
		if(event.which == 13) {
			$('#leftcontent').click(event);
		}
	});
	// Journal entry clicked
	$(document).on('click', '#leftcontent', function(event) {
		var $tgt = $(event.target);
		var item = $tgt.is('li')?$($tgt):($tgt).parents('li').first();
		if(item.length == 0) {
			return true;
		}
		var id = item.data('id');
		item.addClass('active');
		var oldid = $('#entry').data('id');
		console.log('oldid: ' + oldid);
		if(oldid != 0){
			$('#leftcontent li[data-id="'+oldid+'"]').removeClass('active');
		}
		OC.Journal.Entry.loadEntry(id, item.data('entry'));
		return false;
	});
	// Editor command.
	$('.rte-toolbar button').on('click', function(event) {
		console.log('cmd: ' + $(this).data('cmd'));
		$('#description').rte('formatText', $(this).data('cmd'));
		event.preventDefault();
		return false;
	});
	// Toggle text/html editing mode.
	$('#togglemode').on('click', function(event) {
		OC.Journal.toggleMode(true);
		return false;
	});
	$('#editable').on('change', function(event) {
		OC.Journal.setEnabled($(this).get(0).checked);
	});

});
