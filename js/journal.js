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
			var date = new Date(parseInt(data.dtstart)*1000);
			var timestring = (data.only_date?'':' ' + date.toLocaleTimeString());
			return $('<li data-id="'+data.id+'"><a href="'+OC.linkTo('journal', 'index.php')+'&id='+data.id+'">'+data.summary.unEscape()+'</a><br /><em>'+date.toDateString()+timestring+'<em></li>').data('entry', data);
		},
		loadEntry:function(id, data) {
			$('#actions').show();
			//$(document).off('change', '.property');
			console.log('loadEntry: ' + id + ': ' + data.summary);
			this.id = id;
			this.cid = data.calendarid;
			this.data = data;
			$('#entry').data('id', id);
			console.log('summary: ' + data.summary.unEscape());
			$('#calendar').val(data.calendarid);
			$('#summary').val(data.summary.unEscape());
			$('#organizer').val(data.organizer.value.split(':')[1]);
			var format = data.description.format;
			console.log('format: '+format);
			$('#description').rte(format, data.description.value.unEscape());
			$('#description').rte('mode', format);
			//$('#description').expandingTextarea('resize');
			(format=='html'&&$('#editable').get(0).checked?$('#editortoolbar li.richtext').show():$('#editortoolbar li.richtext').hide());
			$('#location').val(data.location);
			$('#categories').val(data.categories.join(','));
			$('#categories').multiple_autocomplete('option', 'source', OC.Journal.categories);
			console.log('Trying to parse: '+data.dtstart);
			var date = new Date(parseInt(data.dtstart)*1000);
			//$('#dtstartdate').val(date.getDate()+'-'+date.getMonth()+'-'+date.getFullYear()); //
			$('#dtstartdate').datepicker('setDate', date);
			if(data.only_date) {
				$('#dtstarttime').hide();
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
					var date = $('#dtstartdate').val() || $.datepicker.formatDate('dd-mm-yy', new Date());
					var time = $('#dtstarttime').val() || '00:00';
					var datetime = new Date(parseInt(date.substring(6, 10), 10), parseInt(date.substring(3, 5), 10)-1, parseInt(date.substring(0, 2), 10) , parseInt(time.substring(0, 2), 10), parseInt(time.substring(3, 5), 10), 0, 0);
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
					$('#leftcontent').append(item);
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
		sortmethod:undefined,
		filterDateRange:function() {
			var start = $('#daterangefrom').datepicker('getDate');
			console.log('start', start);
			if(start == null) {
				return;
			}
			var end = $('#daterangeto').datepicker('getDate');
			console.log('end', end);
			if(end == null) {
				return;
			}
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
			$('#leftcontent li:not(:hidden)').each(function () {
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
							entries.append(OC.Journal.Entry.createEntry(entry));
						});
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
	$('#dtstartdate').datepicker({dateFormat: 'dd-mm-yy'});
	$('#dtstarttime').timepicker({timeFormat: 'hh:mm', showPeriodLabels:false});
	$('#description').rte({classes: ['property','content']});
	$('.tip').tipsy();

	OC.Journal.init();

	$('#controls').on('click', '.settings', function(event) {
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
			drfrom.prop('disabled', false).datepicker({dateFormat: 'dd-mm-yy'});
			drto.prop('disabled', false).datepicker({dateFormat: 'dd-mm-yy'});
		} else {
			drfrom.prop('disabled', true).datepicker('destroy');
			drto.prop('disabled', true).datepicker('destroy');
		}
		OC.Journal.Journals.filterDateRange();
	});

	$('#controls').on('change', '#daterangefrom,#daterangeto', function(event) {
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
