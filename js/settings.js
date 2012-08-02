$(document).ready(function(){
	var setPreference = function(obj, key, val, cb) {
		$.post(OC.filePath('journal', 'ajax', 'setpreference.php'), {'key':key, 'value':val}, function(jsondata) {
			if(cb) {
				cb(jsondata.status);
			}
			var success = {'background-color':'green', 'color': 'white'};
			var failure = {'background-color':'red', 'color': 'white'};
			if(jsondata.status == 'success') {
				$('#settings_status').css(success).html(t('journal', 'Saved')).fadeIn().fadeOut(2000);
				return true;
			} else {
				$('#settings_status').css(failure).html(t('journal', 'Error saving: ')+jsondata.data.message).fadeIn().fadeOut(2000);
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
				OC.Journal.Journals.update();
				$('#journal_single_calendar').prop('disabled', false);
			}
		});
	});

	$('#journal_single_calendar').on('change', function(event){
		setPreference(this, 'single_calendar', Number(this.checked), function(result) {
			if(result == 'success') {
				OC.Journal.Journals.update();
			}
		});
	});
});
