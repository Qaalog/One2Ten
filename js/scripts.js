jQuery(document).ready(function() {

    jQuery("div#no-js").hide();
    jQuery("div.container").show();

    if (!("ontouchstart" in document.documentElement)) {
        document.documentElement.className += " no-touch";
    }

    jQuery('button[name=submit_vote]').on('click touchend', function() {
        var rate = jQuery('input[name=rate]:checked').val();
        if (typeof rate == 'undefined') {
            // disable submit empty rate
            return false;
        }
    });

    jQuery('input[type="file"]').on('change', function() {
        var file = this.files[0];
        var imagefile = file.type;
        var match = ["image/jpeg", "image/png", "image/jpg"];
        if (!((imagefile == match[0]) || (imagefile == match[1]) || (imagefile == match[2]))) {
            return false;
        } else {
            var reader = new FileReader();
            reader.imageWrap = jQuery(this).closest('.upload-wrap').find('img.media-file-preview');
            reader.onload = voteImageIsLoaded;
            reader.readAsDataURL(this.files[0]);
        }
    });
    jQuery('.step-first .btn-custom').on('click', function(){
        var rate = jQuery('input[name="rate"]:checked').val();
        if (rate) {
            jQuery('.step-first').hide();
            jQuery('.step-last').show();
        }   
    });

    jQuery('.switch input').on('change', function(){
        if(this.checked)
            jQuery('.if-notify').show();
        else
            jQuery('.if-notify').hide();
    });

    jQuery('.close-file').on('click touchend', function(){
        jQuery(this).closest('.file-wrap').find('img').hide();
    });

    statsPosition();
});

function voteImageIsLoaded(e) {
    var image = new Image();
    image.src = e.target.result;
    image.onload = function() {
        $imageWrap = e.target.imageWrap;
        //$imageWrap.find('div.no-image').hide();
        var imageElement = $imageWrap;
        imageElement.attr('src', e.target.result).show();
    };
}

function statsPosition() {
  var yourRateWrap = jQuery('.rate-yours'),
      curRateWrap = jQuery('.rate-current'),
      yourRate = parseInt(jQuery('.rate-yours strong').text()),
      currentRate = parseInt(jQuery('.rate-current strong').text());

  jQuery('.number-wrap .number-radio').each(function() {
    var curNumWrap = parseInt(jQuery(this).text());
    if( curNumWrap == currentRate ){
      jQuery(this).addClass('number-current');
      jQuery(this).closest('.number-wrap').append(curRateWrap);
      switch ( curNumWrap ) {
       case 1:
       case 2:
        curRateWrap.addClass('to-left');
        break;

      case 9:
      case 10:
        curRateWrap.addClass('to-right');
        break;  
      }
    }
    if( curNumWrap == yourRate ){
      jQuery(this).addClass('number-yours');
      jQuery(this).closest('.number-wrap').append(yourRateWrap);
      switch ( curNumWrap ) {
       case 1:
       case 2:
        yourRateWrap.addClass('to-left');
        break;

      case 9:
      case 10:
        yourRateWrap.addClass('to-right');
        break;  
      }
    }
  });
}