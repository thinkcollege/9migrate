(function($) {

  /**
   * Individual employment - if gross wages / hours < $10, then show minimum wage
   * stuff.
   */

 Drupal.behaviors.startScan = {
     attach: function (context, settings) {
var dob = Date.parse($(this).text());
  //  $("#edit-field-entered-date-und-0-value-datepicker-popup-0").datepicker({maxDate: '0'});
  // #edit-field-dob-und-0-value-datepicker-popup-0





$('#edit-field-dob-und-0-value-datepicker-popup-0').change(function() {
  var dob = new Date( $(this).val());
  var entered = new Date($('#edit-field-entered-date-und-0-value-datepicker-popup-0').val());
  var date = new Date();
  var sixteenWarn = new Date((date.getMonth()) + '/' + (date.getDate()) + '/' + (date.getFullYear() - 16));

  if (dob > entered){
alert('Program entry date is before birth date. Please correct.');
}



if(dob > sixteenWarn)
{
 alert('You are reporting on an individual less than sixteen years of age.  Is this correct?');
}
  });
  $('#edit-field-entered-date-und-0-value-datepicker-popup-0').change(function() {
    var entered = new Date( $(this).val());
    var dob = new Date($('#edit-field-dob-und-0-value-datepicker-popup-0').val());

      if (dob > entered){
    alert('Program entry date is before birth date. Please correct.');
    }
    });
     }
   }

})(jQuery);
