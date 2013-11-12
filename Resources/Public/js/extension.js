function preview(img, selection) {
	$('#x1').val(selection.x1);
	$('#y1').val(selection.y1);
	$('#x2').val(selection.x2);
	$('#y2').val(selection.y2);
	$('#w').val(selection.width);
	$('#h').val(selection.height);

	checkChange();
}

function checkChange() {
	var imgCur = [$('#edit #x1').val(), $('#edit #y1').val(), $('#edit #x2').val(), $('#edit #y2').val()];
	var noCrop = imgOrg.toString() === imgCur.toString();
	if (noCrop == false) {
		$('#controller #close').hide();
		$('#controller #save').show();
	} else {
		$('#controller #close').show();
		$('#controller #save').hide();
	}
}


$(function () {
	$('#controller').on('click', '#save', function () {
		$.ajax({
			dataType: 'json',
			url: 'ajax.php?ajaxID=TkcropthumbsAjaxController::init',
			data: {
				'action': 'save',
				'cropValues': {
					'x1': $('#edit #x1').val(),
					'y1': $('#edit #y1').val(),
					'x2': $('#edit #x2').val(),
					'y2': $('#edit #y2').val()
				},
				'uid': uid
			}
		}).success(function (data) {
				if (data) {
					parent.window.opener.focus();
					parent.close();
				}
			});
	});

	$('#controller').on('click', '#close', function () {
		parent.window.opener.focus();
		parent.close();
	});

	$('#controller').on('click', '#resetSingle', function () {
		$.ajax({
			dataType: 'json',
			url: 'ajax.php?ajaxID=TkcropthumbsAjaxController::init',
			data: {
				'action': 'resetSingle',
				'uid': uid
			}
		}).success(function (data) {
				if (data) {
					location.reload();
				}
			});
	});
});