(function($){

    $(document).ready(function(){

	function init(){

        if($('.colorPicker_circle').length > 0) {
            $('.colorPicker_circle').ColorPicker({
                onBeforeShow: function () {
                    $this = $(this);
                    var color = $(this).css('background-color');
                    $(this).ColorPickerSetColor(color);
                },
                onShow: function (colpkr) {
                    $(colpkr).fadeIn(500);
                    return false;
                },
                onHide: function (colpkr) {
                    $(colpkr).fadeOut(500);
                    return false;
                },
                onChange: function (hsb, hex, rgb) {
                    var color = "#" + hex;
                    $($this).text(color);
                    $('.colorPicker_cir.impColor').css('background-color', color);
                    $($this).css('background-color', color);
                }
            });
        }

        $('#_badgeos_achievement_type_description').attr('maxlength',101);
        $('#_badgeos_achievement_type_description').css({'max-height':'35px','max-width':'800px'});
        $('#_badgeos_achievement_type_description').keypress(function(){
            if($(this).val().length > 100){
                alert('Description field allows only 100 characters limit.');
            }
        });


	}

	$(".color_picker_save_btn").bind("click",function(){

         var completed = $("#completedCircle").css("background-color");
         var colorcode = $("#completedCircle").text();
         var pending = $("#pendingCircle").css("background-color");
         var skipped = $("#skippedCircle").css("background-color");

         if(completed != '' && pending!='' && skipped != ''){

             $.ajax( {
                 url : ajaxurl,
                 data : {
                     'action' : 'interactive_progress_map_color_codes',
                     'completed' : completed,
                     'colorcode' : colorcode
                     /*'pending' : pending,
                     'skipped' : skipped*/
                 },
                 dataType : 'json',
                 async : false,
                 success : function( response ) {
                     if(response.data.message){
                         $('.progressMap_title').prepend(response.data.message);

                         setTimeout(function(){
                             $('.badgeos-interactive-progress-map').fadeOut(1000);
                         },2000);
                     }
                 },
                 error : function() {

                     alert( 'There was an issue requesting membership, please contact your site administrator' );

                 }
             } );

         }
	});

    init();

    });

})(jQuery);