(function($) {
  $.previewThese = function(files) {
    if ($('form').hasClass('saveit')) {
      $('.saveit').remove();
    }
    var fileCollection = new Array();
    var files = files;
    console.log(files);
    $.each(files, function(i, file) {

      var reader = new FileReader();
      reader.readAsDataURL(file);

      reader.onload = function(e) {
        console.log(e.target.width);
        fileCollection.push(file);
        var template = 
          '<form class="saveit" id="img_upload">' +
            '<img src="' + e.target.result + '">' +
            '<input id="remove" type="button" name="Remove" value="Ta bort">' +
          '</form>';

        $('#tmpfiles').append(template);
      }
    });
    return fileCollection;
  }
}(jQuery));

(function($) {
  $.arrayOfThese = function(files) {
    if (files.target.files.length > 10) {
      msg('errormsg', 'Only 10 files allowed');
      $(this).val('');
    } else {
      files = files.target.files;
      return files
    }
  }
}(jQuery));

$(document).ready(function() {

  var file = null;
  var formdata = null;
  var fileCollection = new Array();
  
  $('#dropzone').bind('dragover drop', function(e) {
    e.stopPropagation(); 
    e.preventDefault();
    $('#dropzone').removeClass('dropzone').addClass('dragover');
    if (e.type === 'drop') {
      if (e.originalEvent.dataTransfer.files.length < 11) {
        console.log(e.originalEvent.dataTransfer.files);
        fileCollection = $.previewThese(e.originalEvent.dataTransfer.files);
      } else {
        msg('errormsg', 'Only 10 files allowed');
      }
      $('#dropzone').removeClass('dragover').addClass('dropzone');
    }
  });

  $('#filestoupload').change(function(e) {
    var fileOfThese = $.arrayOfThese(e);
    fileCollection = $.previewThese(fileOfThese);
  });

  $(document).on('click', '#remove', function(e) {
    e.preventDefault();
    var thisObject = $(this).parent('form');
    var index = thisObject.index();
    fileCollection.splice(index, 1);
    thisObject.remove();
  });
  $(document).on('click', '#saveall', function() {
    console.log(fileCollection);
    if (fileCollection.length > 0) {
      formdata = new FormData(fileCollection);
      $.each(fileCollection, function(i, file) {
        formdata.append(i, file);
      });

      $.ajax({
        type: 'POST',
        url: 'request/uploadimgs.php',
        data: formdata,
        cache: false,
        contentType: false,
        processData: false,
        success: function(res){
          var response = JSON.parse(res);
          msg(response.typeofmsg, response.message);
        },
        error: function(res) {
          var response = JSON.parse(res);
          msg(response.typeofmsg, response.message);
        }
      });
    } else {
      msg('errormsg', 'Ingen bild vald');
    }
  });

});