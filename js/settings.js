$(document).ready(function(){
	var setPreference = function(obj, key, val, cb) {
		$.post(OC.filePath('journal', 'ajax', 'setpreference.php'), {'key':key, 'value':val}, function(jsondata) {
			if(cb) {
				cb(jsondata.status);
			}
			var success = {'padding': '0.5em', 'background-color':'green', 'color': 'white', 'font-weight': 'bold', 'float': 'left'};
			var failure = {'padding': '0.5em', 'background-color':'red', 'color': 'white', 'font-weight': 'bold', 'float': 'left'};
			if(jsondata.status == 'success') {
				$('#journal_status').css(success).html(t('journal', 'Saved')).fadeIn().fadeOut(5000);
				return true;
			} else {
				$('#journal_status').css(failure).html(t('journal', 'Error saving: ')+jsondata.data.message).fadeIn().fadeOut(5000);
				return false;
			}
		});
	}

	if($('#journal_calendar option:selected').val() == '') {
		$('#journal_single_calendar').prop('disabled', true);
	}

	$('#journal_calendar').on('change', function(event){
		setPreference(this, 'default_calendar', $('#journal_calendar option:selected').val(), function(result) {
			if(result == 'success') {
				$('#journal_single_calendar').prop('disabled', false);
			}
		});
	});
	
	$('#journal_single_calendar').on('change', function(event){
		setPreference(this, 'single_calendar', this.checked);
	});
});
