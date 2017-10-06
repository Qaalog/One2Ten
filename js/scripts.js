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
        var notifyManager = jQuery('.switch input#notify_manager').is(':checked');
        if (notifyManager && jQuery(this).hasClass('inactive')) {
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
    jQuery('.step-first .btn-custom.add-rate-comment').on('click', function(){
        var rate = jQuery('input[name="rate"]:checked').val();
        if (rate) {
            jQuery('.step-first').hide();
            jQuery('.step-last').show();
        }   
    });
    jQuery('.step-first .btn-custom.rate-ready').on('click', function(){
        var rate = jQuery('input[name="rate"]:checked').val();
        if (rate) {
            jQuery(this).closest('form').find('button[name=submit_vote]').trigger('click');
        }
    });

    jQuery('.switch input').on('change', function(){
        if (this.checked)
            jQuery('.if-notify').show();
        else
            jQuery('.if-notify').hide();
        jQuery('.if-notify .input-wrap input').first().trigger('change');
    });

    jQuery('.close-file').on('click touchend', function(){
        jQuery(this).closest('.file-wrap').find('img').hide();
    });

    jQuery('input[name="rate"]').on('change', function(){
        var rate = parseInt(jQuery(this).val());
        updateQuestionDiv(rate);
    });

    jQuery('.if-notify .input-wrap input').on('change', function() {
        var notifyManager = jQuery('.switch input#notify_manager').is(':checked');
        var $button = jQuery('button[name=submit_vote]');
        if (notifyManager) {
            var name = jQuery('.if-notify .input-wrap input[name=user_name]').val().trim();
            var info = jQuery('.if-notify .input-wrap input[name=user_info]').val().trim();
            if (name > '' && info> '') {
                $button.removeClass('inactive');
            } else {
                $button.addClass('inactive');
            }
        } else {
            $button.removeClass('inactive');
        }
    });

    statsPosition();
    updateQuestionDiv(0);

    jQuery('.step-tags .tag-wrap').wrapMatch(2, 'step-tags-inner');
});

(function($){
  $.fn.wrapMatch = function(count, className) {
    var length = this.length;
    for(var i = 0; i < length ; i+=count) {
      this.slice(i, i+count).wrapAll('<div '+((typeof className == 'string')?'class="'+className+'"':'')+'/>');
    }
    return this;
  };
})(jQuery);

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

function updateQuestionDiv(rate) {
    var $div = jQuery('.step-tags');
    var $tags = $div.find('.tag-wrap');
    var positiveRate = parseInt( $div.find('h3.positive').data('rate') );
    var negativeRate = parseInt( $div.find('h3.negative').data('rate') );
    if (rate && rate>0 && $tags.length && (positiveRate || negativeRate)) {
        $div.find('h3').hide();
        if (positiveRate && rate >= positiveRate) {
            $div.find('h3.positive').show();
            $div.show();
        } else if (negativeRate && rate <= negativeRate) {
            $div.find('h3.negative').show();
            $div.show();
        } else {
            $div.hide();
            $tags.val('');
        }
    } else {
        $div.hide();
    }
    if (rate && rate>0) {
        jQuery('.btn-block .btn-custom').removeClass('inactive');
    }
}