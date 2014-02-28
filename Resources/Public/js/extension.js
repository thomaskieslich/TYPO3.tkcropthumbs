$(function () {

	var cropbox = $('img#cropbox').imgAreaSelect({
		x1: imgX1,
		y1: imgY1,
		x2: imgX2,
		y2: imgY2,
		imageWidth: imgWidth,
		imageHeight: imgHeight,
		aspectRatio: imgAr,
		handles: true,
		keys: true,
		fadeSpeed: 200,
		onInit: preview,
		onSelectChange: preview,
		persistent: true,
		instance: true
	});

	$('#edit').on('keyup', '#x1, #y1, #x2, #y2', function () {
		cropbox.setSelection(
			$('#edit #x1').val(),
			$('#edit #y1').val(),
			$('#edit #x2').val(),
			$('#edit #y2').val(),
			true
		);
		cropbox.update();
		preview('', cropbox.getSelection());
	});


	$('#edit').on('change', '#aspectRatio', function () {
		cropbox.setOptions({
			aspectRatio: $('#aspectRatio').val(),
			movable: false
		});

		if ($('#aspectRatio').val()) {
			$('#edit #x1, #edit #y1, #edit #x2, #edit #y2').attr('readonly', 'readonly');
		} else {
			$('#edit #x1, #edit #y1, #edit #x2, #edit #y2').removeAttr('readonly');
		}

		$('.imgareaselect-selection').css('background', '#900');
	});

	$('#controller').on('click', '#reset', function () {
		cropbox.setSelection(selectionOrg['x1'], selectionOrg['y1'], selectionOrg['x2'], selectionOrg['y2']);
		cropbox.update();
		checkChange();
	});

	$('#controller').on('click', '#save', function () {
		$.ajax({
			dataType: 'json',
			url: ajaxUrl,
			data: {
				'action': 'save',
				'cropValues': JSON.stringify(cropbox.getSelection()),
				'uid': imgUid
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


	$('#controller').on('click', '#delete', function () {
		$.ajax({
			dataType: 'json',
			url: ajaxUrl,
			data: {
				'action': 'delete',
				'uid': imgUid
			}
		}).success(function (data) {
			if (data) {
				parent.window.opener.focus();
				parent.close();
			}
		});
	});

	function preview(img, selection) {
		$('#x1').val(selection.x1);
		$('#y1').val(selection.y1);
		$('#x2').val(selection.x2);
		$('#y2').val(selection.y2);
		$('#w').val(selection.width);
		$('#h').val(selection.height);

		$('.imgareaselect-selection').css('background', 'none');
		cropbox.setOptions({
			movable: true
		});
		cropbox.update();

		checkChange();
	}

	var selectionOrg = cropbox.getSelection();

	function checkChange() {
		var selectionCur = cropbox.getSelection();
		var noCrop = JSON.stringify(selectionOrg) === JSON.stringify(selectionCur);
		if (noCrop == false) {
			$('#controller #close').hide();
			$('#controller #save').show();
		} else {
			$('#controller #close').show();
			$('#controller #save').hide();
		}

		if ($('#edit #aspectRatio').val() == 0) {
			$('#edit #x1, #edit #y1, #edit #x2, #edit #y2').removeAttr('readonly');
		}
	}

});