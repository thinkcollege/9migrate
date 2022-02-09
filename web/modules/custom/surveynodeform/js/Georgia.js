(function($) {

    /**
     * Individual employment - if gross wages / hours < $10, then show minimum wage
     * stuff. testing 2
     */

  Drupal.behaviors.remState = {
       attach: function (context, settings)
       {
          $('.table-responsive a[data-toggle="tooltip"]').tooltip({
           trigger : 'hover'
          })

          $(document).ajaxComplete(function(){
          $("#field-area-office-add-more-wrapper select option[value='33']").attr('disabled','disabled');
          $("#field-area-office-add-more-wrapper select option[value='13']").attr('disabled','disabled');
          $("#field-area-office-add-more-wrapper select option[value='13']").each(function() {
                $(this).remove();
          });
          $("#field-area-office-add-more-wrapper select option[value='33']").each(function() {
                $(this).remove();
            });
          });



        }

  }
    Drupal.behaviors.startScan =
  {
    attach: function (context, settings)
    {

      $('ul.vertical-tabs-list li a').addClass('inComplete');
        $( document ).one('ready',scanFieldsets);
        $(document).one('ready',surveynodeformcheckAllActions);
        $('th').text('Order').hide();
        $( document ).one('ready', function() {
          $('.nextSec a').click(function(){
          $('html, body').animate({scrollTop:0}, 'slow');

          });
        });
        if (!$('#edit-group_indv_comp p.nextSec').length)$('#edit-group_indv_comp').append('<p class="nextSec"><a class="openTab" href="#edit-group_grp_integ">>> Next: Group integrated job >></a></p>');
        if (!$('#edit-group_grp_integ p.nextSec').length) $('#edit-group_grp_integ').append('<p class="nextSec"><a class="openTab" href="#edit-group_self_emp">>> Next: Self employment >></a></p>');
         if (!$('#edit-group_self_emp p.nextSec').length) $('#edit-group_self_emp').append('<p class="nextSec"><a class="openTab" href="#edit-group_shl">>> Next: Facility based/sheltered work >></a></p>');
       if (!$('#edit-group_shl p.nextSec').length) $('#edit-group_shl').append('<p class="nextSec"><a class="openTab" href="#edit-group_com_non_work">>> Next: Community based non work >></a></p>');
       /* if (!$('#editInstruc').length)  $('ul.nav.nav-tabs.vertical-tabs-list').append('<p id="editInstruc">Please fill in all categories for the individual’s data to appear as complete.<br />For categories the individual participated in, please enter additional information, such as hours and wages, where asked.<br />For categories the individual did not participate in, please check in the appropriate box (Did not participate in this activity) to confirm this information. </p>'); */

         if (!$('#edit-group_com_non_work p.nextSec').length) $('#edit-group_com_non_work').append('<p class="nextSec"><a class="openTab" href="#edit-group_fac_non_work">>> Next: Facility based non work >></a></p>');

      $('fieldset div.form-type-textarea .form-textarea').each(function(i, elem) {
       if($(elem).val() && !$(elem).closest('fieldset').hasClass('showQues')) { $(elem).closest('fieldset').addClass('showQues'); }
      });
      if($('.deleteGroup').is(":visible")) { if(!$('#edit-actions').hasClass('adjustDown'))$('#edit-actions').addClass('adjustDown'); else $('#edit-actions').removeClass('adjustDown');}

      if(!$('.main-container #saveWarn').length ) $('.main-container #edit-submit').after('<p id="saveWarn">Be sure to Save before exiting this page or you will lose your work.</p><a id="logOutbut" href="/user/logout">Log out</a>');

      $('.currentYear').each(function(i, elem) {
        curyearText = $('#currentYear').text();
        if($('#currentYear').text().length > 0) {$(elem).text(curyearText); }
        else { $(elem).text(''); }
      });
      $('h1.page-header em').hide();

      $('a.saveLeave').bind("click tap", saveAndLeave);

      $('fieldset').each(function(i, elem) {
          var fieldID = "#" + $(elem).prop('id');
          var hours1 = $(fieldID + ' .checkHours').prop('id');
          var wages1 =  $(fieldID + ' .checkWages').prop('id');
          var hours = "#" + hours1;
          var wages = "#" + wages1;

          surveynodeformInitialGaMinWage(fieldID,hours,wages);

        });
       /* $('fieldset.active').each(function(i, elem) {

          var fieldID = "#" + $(elem).prop('id');
          var hours1 = $(fieldID + ' .checkHours').prop('id');
          var wages1 =  $(fieldID + ' .checkWages').prop('id');
          var hours = "#" + hours1;
          var wages = "#" + wages1;
          var hourly_rate = $(wages).val() / $(hours).val();
          var show_rate = parseFloat(hourly_rate).toFixed(2);
          var hasValues = false;
          if (($(wages).val() != '') && ($(hours).val() != '')) {
            hasValues = true;
          }

          if(hasValues) {
            if ($(fieldID + ' .indCalcHourly').length) $(fieldID + ' .indCalcHourly').remove();
            $(fieldID + ' .checkWages').after('<div class="indCalcHourly"><label class="control-label">Calculated hourly wage:</label><div> $' + show_rate +  '</div></div>');


          }
        }); */

    }

  }

  Drupal.behaviors.clearHidden = {
    attach: function (context, settings) {
      $(".form-type-checkbox input").change(function() {
        if($(this).is(':checked')) {var fieldID =$(this).closest('fieldset').prop('id');
       if($('#' + fieldID).hasClass('showQues'))  $('#' + fieldID).removeClass('showQues');
        $('#' + fieldID + ' input').each(function(i, el) {

              $(el).val("");

        });

        $('#' + fieldID + ' select').each(function(i, el) {

              $(el).val("_none");

        });
        $('#' + fieldID + ' .form-textarea').each(function(i, el) {

              $(el).val("");

        });

       }

      });




    }
  }

  Drupal.behaviors.clearDidNotPart = {
    attach: function (context, settings) {
        var noPar = false;
        $(".form-type-checkbox input").change(function() {
          $(".form-type-checkbox input").each(function () { noPar = $(this).prop('checked' ) ? true : false;
          });
          if(noPar) {
            $('#edit-field-indv-data-partic-why input.form-radio').each(function () { $(this).prop('checked', false);});
            $('input#edit-field-indv-data-partic-why-und-4').prop('checked', true);
          $('#edit-field-indv-data-partic-other-und-0-value').val("");
          }
        });

    }
  }


  Drupal.behaviors.scanVertTab = {
    attach: function (context, settings) {
      $('.nav-tabs > li').bind("mouseenter touchstart",scanFieldsets);
      $( document ).one('ready',scanFieldsets);
    }
  }
  Drupal.behaviors.scanOpenTab = {
    attach: function (context, settings) {
     // $('a.openTab').bind("select",scanFieldsets);
      $( document ).one('ready',scanFieldsets);
    }
  }

  Drupal.behaviors.checkSaved = {
    attach: function (context, settings) {
      var unsaved = false;

      $(":input").change(function(){
          unsaved = true;
      });
      $("select").change(function(){
          unsaved = true;
      });
      $('#edit-submit').on("click focus",function(){
          unsaved = false;
      });
      $(window).on('beforeunload', function(){
      if(unsaved) {
          return "You have unsaved changes on this page. Do you want to leave this page and discard your changes or stay on this page?";
        }
      });
    }
  }




  Drupal.behaviors.alternateTab = {
    attach: function (context, settings) {
      $(document).on('click', 'a.openTab', function (event) {


        setTimeout(function(){
          linkToTab();
        }, 1500);


      });
    }
  }

  Drupal.behaviors.surveynodeformChMoreBut = {
    attach: function (context, settings) {
        changeMoreButton();
    }
  };

  Drupal.behaviors.surveynodeformEnforceNumeric = {
    attach: function (context, settings) {

      $('.field-type-number-float input').keydown(isNumber);


    }

  };

  Drupal.behaviors.surveynodeformTextCheck = {
    attach: function (context, settings) {
     $('.form-type-textfield input').keydown(checkText);
    }
  };

  Drupal.behaviors.surveynodeformremoveIntWarn = {
    attach: function (context, settings) {

       $('.field-type-number-float input').bind("blur",removeNumWarn);

    }

  } ;

  Drupal.behaviors.surveynodeformMakeActive = {
    attach: function (context, settings) {

       $('.vertical-tabs-panes > fieldset').bind("mouseenter touchstart",makeActiveTab);


    }

  } ;

  Drupal.behaviors.surveynodeformNotReq = {

    attach: function (context, settings) {
      $('#edit-field-indv-start-date-und-0-value-datepicker-popup-0').addClass('notReq');
      $('#edit-field-indv-date-last-worked-und-0-value-datepicker-popup-0').addClass('notReq');
    }
  };
  Drupal.behaviors.surveynodeformIsReq = {

    attach: function (context, settings) {

      if(!$('#edit-field-total-integ-y-n').hasClass('fieldReq')) { $('#edit-field-total-integ-y-n').addClass('fieldReq'); }
      // if(!$('#edit-field-integ-emp-svc-y-n-und').hasClass('fieldReq')) { $('#edit-field-integ-emp-svc-y-n-und').addClass('fieldReq'); }
      if(!$('#edit-field-fac-y-n').hasClass('fieldReq')) { $('#edit-field-fac-y-n').addClass('fieldReq'); }
      if(!$('#edit-field-comm-does-state-offer').hasClass('fieldReq')) { $('#edit-field-comm-does-state-offer').addClass('fieldReq'); }
      // if(!$('#edit-field-fac-bas-y-n-und').hasClass('fieldReq')) { $('#edit-field-fac-bas-y-n-und').addClass('fieldReq'); }
      if(!$('#edit-field-oth-emp-day-y-n').hasClass('fieldReq')) { $('#edit-field-oth-emp-day-y-n').addClass('fieldReq'); }
    }
  };

  Drupal.behaviors.surveynodeformCheckEmptyFields = {
    attach: function (context, settings) {


       $('ul.vertical-tabs-list li a').bind("click",emptyFieldWarn);



    }

  };



  Drupal.behaviors.surveynodeformStripCommas  = {
    attach: function (context, settings) {
      $('#individual-data-ga-node-form').submit(completionTasks);
    }
  }

  Drupal.behaviors.surveynodeformGaIndComJob = {
    attach: function (context, settings) {
        surveynodeformGaMinWage('#edit-group_indv_comp','#edit-field-indv-comp-hrs-und-0-value', '#edit-field-indv-comp-gross-wages-und-0-value');
       surveynodeformGaHrRange('#edit-field-indv-comp-hrs-und-0-value');
       surveynodeformGaWageRange('#edit-field-indv-comp-gross-wages-und-0-value');
    }
  };


  Drupal.behaviors.surveynodeformGaGrpIntegJob = {
    attach: function (context, settings) {
      surveynodeformGaMinWage('#edit-group_grp_integ','#edit-field-grp-integ-hrs-und-0-value', '#edit-field-grp-integ-gross-wages-und-0-value');
         surveynodeformGaHrRange('#edit-field-grp-integ-hrs-und-0-value');
           surveynodeformGaWageRange('#edit-field-grp-integ-gross-wages-und-0-value');
    }
  };
  Drupal.behaviors.surveynodeformGaSelfEmp = {
    attach: function (context, settings) {

      surveynodeformGaHrRange('#edit-field-self-emp-hrs-und-0-value');
      surveynodeformGaSelfEarningsRange('#edit-field-self-emp-gross-income-und-0-value');
      surveynodeformGaSelfExpenseRange('#edit-field-self-emp-gross-expens-und-0-value');
    }
  };
  Drupal.behaviors.surveynodeformGaFacBsedJob = {
    attach: function (context, settings) {
      surveynodeformGaMinWage('#edit-group_shl','#edit-field-shl-hrs-und-0-value', '#edit-field-shl-gross-wages-und-0-value');
      surveynodeformGaHrRange('#edit-field-shl-hrs-und-0-value');
        surveynodeformGaWageRange('#edit-field-shl-gross-wages-und-0-value');
    }
  };
  Drupal.behaviors.surveynodeformGaComNonWk = {
    attach: function (context, settings) {

      surveynodeformGaHrRange('#edit-field-com-non-wrk-hours-und-0-value','hours');

    }
  };
  Drupal.behaviors.surveynodeformNoParticAll = {
    attach: function (context, settings) {

     $('.form-type-checkbox input').bind('click focus', surveynodeformcheckAllActions);
    }
  };

     /**
      * Group integrated job - if gross wages / hours < $8.25, then show minimum wage
      * stuff.
      */
  function surveynodeformGaHrRange(rangeVal) {
    $(rangeVal).change(function() {
      var hasValues = false;
      var checkVal = parseFloat($(rangeVal).val().replace(/,/g, ''));
      if ($(rangeVal).val() && $(rangeVal).val() != '') {
        hasValues = true; }


      if (checkVal > Drupal.settings.Surveyconfig.gahrhigh) { alert('The hours value you entered looks too high. Is this the correct number?');}
      else if (checkVal == 0) {
        alert('If the individual had no hours check "Did not participate in this activity" above.');
        $(rangeVal).val('');

      }
      else if (checkVal < Drupal.settings.Surveyconfig.gahrlow) {
        alert('The hours value you entered looks too low. Is this the correct number?');
      }


    });
  }
  function surveynodeformGaWageRange(rangeVal) {
    $(rangeVal).change(function() {
      var hasValues = false;
      var checkVal = parseFloat($(rangeVal).val().replace(/,/g, ''));
      if ($(rangeVal).val() && $(rangeVal).val() != '') {
        hasValues = true; }


        if (checkVal > Drupal.settings.Surveyconfig.gawagehigh) { alert('The wage value you entered looks too high. Is this the correct number?');}
        else if (checkVal < Drupal.settings.Surveyconfig.gawagelow) { alert('The wage value you entered looks too low. Is this the correct number?');}

    });
  }

  function surveynodeformGaMinWage(fieldID,hours, wages) {
    $(hours + ', ' + wages).change(function() {

        var hourly_rate = $(wages).val() / $(hours).val();
        var show_rate = parseFloat(hourly_rate).toFixed(2);
        var hasValues = false;
        if (($(wages).val() != '') && ($(hours).val() != '')) {
          hasValues = true;
        }
        if ((hourly_rate < Drupal.settings.Surveyconfig.gamin) && (hasValues)) {
          if(!$(wages).closest('fieldset').hasClass('showQues')) $(wages).closest('fieldset').addClass('showQues');
          //  $(wages).val('');
         // alert('Are you sure about the wages and hours you entered? Hours/wages are less than the Georgia minimum wage of  $' + Drupal.settings.Surveyconfig.gamin + '/hr.');
        }
        else {
          if($(wages).closest('fieldset').hasClass('showQues')) $(wages).closest('fieldset').removeClass('showQues');

        }

        if(hasValues) {
          if ($(fieldID + ' .indCalcHourly').length) $(fieldID + ' .indCalcHourly').remove();
          $(fieldID + ' .checkWages').after('<div class="indCalcHourly"><label class="control-label">Calculated hourly wage:</label><div> $' + show_rate +  '</div></div>');


        }
    });


  }

  function surveynodeformGaSelfEarningsRange(rangeVal) {
    $(rangeVal).change(function() {
        var hasValues = false;
        var checkVal = parseFloat($(rangeVal).val().replace(/,/g, ''));
        if ($(rangeVal).val() && $(rangeVal).val() != '') {
          hasValues = true; }


          if (checkVal > Drupal.settings.Surveyconfig.gaselfearnhigh) { alert('The self-employment earnings value you entered looks too high. Is this the correct number?');}
          else if (checkVal < Drupal.settings.Surveyconfig.gawagelow) { alert('The self-employment earnings value you entered looks too low. Is this the correct number?');}

    });
  }

  function surveynodeformGaSelfExpenseRange(rangeVal) {
    $(rangeVal).change(function() {
       var hasValues = false;
       var checkVal = parseFloat($(rangeVal).val().replace(/,/g, ''));
       if ($(rangeVal).val() && $(rangeVal).val() != '') {
         hasValues = true; }


         if (checkVal > Drupal.settings.Surveyconfig.gaselfexpensehigh) { alert('The self-employment expenses value you entered looks too high. Is this the correct number?');}
         else if (checkVal < Drupal.settings.Surveyconfig.gawagelow) { alert('The self-employment expenses value you entered looks too low. Is this the correct number?');}

    });
  }

  function surveynodeformInitialGaMinWage(fieldID,hours, wages) {


    var hourly_rate = $(wages).val() / $(hours).val();
    var show_rate = parseFloat(hourly_rate).toFixed(2);
        var hasValues = false;


    var hasValues = false;
    if (($(wages).val() != '') && ($(hours).val() != '')) {
      hasValues = true;
    }
    if ((hourly_rate < Drupal.settings.Surveyconfig.gamin) && (hasValues)) {
      if(!$(wages).closest('fieldset').hasClass('showQues')) $(wages).closest('fieldset').addClass('showQues');
    //  $(wages).val('');

    }
    else {
      if($(wages).closest('fieldset').hasClass('showQues')) $(wages).closest('fieldset').removeClass('showQues');

    }
    if(hasValues) {
      if ($(fieldID + ' .indCalcHourly').length) $(fieldID + ' .indCalcHourly').remove();
      $(fieldID + ' .checkWages').after('<div class="indCalcHourly"><label class="control-label">Calculated hourly wage:</label><div> $' + show_rate +  '</div></div>');


    }


  }

  function surveynodeformcheckAllActions () {
     if ($('input[id="edit-field-indv-comp-partic-und"]').is(':checked') && $('input[id="edit-field-grp-integ-partic-und"]').is(':checked') && $('input[id="edit-field-self-emp-partic-und"]').is(':checked') && $('input[id="edit-field-shl-partic-und"]').is(':checked') && $('input[id="edit-field-com-non-work-partic-und"]').is(':checked') && $('input[id="edit-field-fac-non-work-partic-und"]').is(':checked') ) { if(!$('#reasonnopartic').hasClass('activated')) { $('#reasonnopartic').addClass('activated');} } else { if($('#reasonnopartic').hasClass('activated')) { $('#reasonnopartic').removeClass('activated');}}
  }

  function completionTasks() {
    //remove commas
    $('.field-type-number-float input').each(function(i, el) {
      if($(el).val() != "" ) {
          $(el).val($(el).val().replace(/,/g, ''));
      }
    });
    //check that all tabs are complete and set hidden field field_ga_ind_data_complete complete/imcomplete
    var formComplete = false;


      $('.vertical-tab-button > a').each(function(i, el) {
        if ($(this).hasClass('tabFilled')) {
        formComplete = true;

        } else if($(this).hasClass('inComplete')) {
          formComplete = false;
          return false;
        }
      });
    if($('#reasonnopartic').hasClass('activated') && $('input#edit-field-indv-data-partic-why-und-4').prop('checked')) formComplete = false;
    if($('#reasonnopartic').hasClass('activated') && $('input#edit-field-indv-data-partic-why-und-3').prop('checked') && $('#edit-field-indv-data-partic-other-und-0-value').val() == "" ) formComplete = false;



       if (formComplete) $('#edit-field-ga-ind-data-complete input').val('1');
       else $('#edit-field-ga-ind-data-complete input').val('0');


  }

  function saveAndLeave(event) {
    // Remember the link href
    var href = this.href;

    // Don't follow the link
    event.preventDefault();
      $('#edit-submit').click();

  }

  function changeMoreButton() {
     // $('#field-contact-other-staff-add-more-wrapper input').val('Add a staff member');
     $('button.field-add-more-submit').val('Add a staff member');
     $('button.field-add-more-submit').text('Add a staff member');
  }

  function isNumber(evt) {

    if (evt.which != 9 && evt.which != 188 && evt.which != 37 && evt.which != 39 && evt.which != 190 && evt.which != 17 && evt.which != 86 && evt.which != 91 && evt.which != 67 && evt.which != 110)
    {
        var theEvent = evt || window.event;
        var key = theEvent.keyCode || theEvent.which;
        key = String.fromCharCode(key);
        if (key.length == 0) return;
        var regex = /^[0-9\b]+$/;

        if (!regex.test(key)) {
          if(!(theEvent.keyCode >= 96 && theEvent.keyCode <= 105) && !(theEvent.keyCode >= 48 && theEvent.keyCode <= 57)) {
            if (!$(this).prev('p').hasClass('reqNumwarn')) {
               $(this).before('<p class="reqNumwarn">Numbers, decimals, and commas only in this field.</p>')
              }
            theEvent.returnValue = false;
            if (theEvent.preventDefault) theEvent.preventDefault();

          }
        } else { if($(this).hasClass('redLine'))  $(this).removeClass('redLine'); }
    }
  }



  function checkText(evt) {
    var theEvent = evt || window.event;
    var key = theEvent.keyCode || theEvent.which;
    key = String.fromCharCode(key);
    if (key.length == 0) return;
            var theEvent = evt || window.event;
            var key = theEvent.keyCode || theEvent.which;
            key = String.fromCharCode(key);

        if($(this).hasClass('redLine'))  $(this).removeClass('redLine');


  }


  function removeNumWarn() {
     if ($(this).prev('p').hasClass('reqNumwarn')) {
     var warnP = $(this).prev('p');
       setTimeout(function(){
         $(warnP).remove();
       }, 500);
     }
     return;

  }

  function popNumSpans(spanclass,numvar) {
     $('.' + spanclass).each(function(i, elem) { if(numvar) {$(elem).html(numvar); } else { $(elem).html(''); } });
  }

  function linkToTab(evt) {
    linkURL = $(location).attr('href');
    hashLink = window.location.hash;

      // alert(linkURL);
    $('.vertical-tabs-panes fieldset').each(function(i, el) {

      if($(el).hasClass('active')) { $(el).removeClass('active');
         $(el).find('> div').removeClass('in');
      }
    });
    $('.vertical-tabs-list li a').each(function(i, elm) {
       $(elm).attr( 'aria-expanded', 'false').parent('li').removeClass('active selected');
      });


    $('.vertical-tabs-panes fieldset' + hashLink).addClass('active').find('> div').addClass('in');
    $('a[href="' + hashLink + '"]').attr( 'aria-expanded', 'true').parent('li').addClass('active selected');



  }

  function commifyNum(rawnum){
    var x = rawnum;
    rawnum = x.toString().replace(/,/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return rawnum;
  }

  function processNumVars(idstring, typeint) {

      returnvar = $('#' + idstring).val() ? (typeint == false ? commifyNum(parseInt($('#'+ idstring).val().replace(/,/g, ''),10)) : parseInt($('#'+ idstring).val().replace(/,/g, ''),10)) : '';
      return returnvar;
  }



  function scanFieldsets () {

     //  alert(Drupal.settings.surveynodeform);

    var origID = '';
    $('.vertical-tabs-panes > fieldset').each(function(i, el) {

      if ($(el).hasClass('active') ) {origID = $(el).attr('id');}

      else {  $(el).addClass('active'); }
      makeActiveTab.call($(el));
      $(el).removeClass('active');
      $(el).removeClass('activeTwo');
    });
    $('.vertical-tabs-list li a').each(function(i, elem) {


       $(elem).removeClass('activeTwo');

    });

    $('#' + origID).addClass('active');
  }

  function makeActiveTab() {
    if ($(this).hasClass('active') && !$(this).hasClass('activeTwo'))
    {
      $(this).addClass('activeTwo')
    }
    var fieldID = $(this).attr('id');
    var currentTab = $('a[href="#' + fieldID + '"]');
    if (!currentTab.hasClass('activeTwo')) currentTab.addClass('activeTwo');
    var countempty = 0;
    if(!$('#edit-field-save-and-return').hasClass('fieldReq')) $('#edit-field-save-and-return').addClass('fieldReq');



    $('div.field-widget-options-buttons:visible').each(function(i, el) {

        if(!$(el).hasClass('fieldReq') && !$(el).parents('.checkGroup').length > 0) { $(el).addClass('fieldReq');}

    });


    $('div.checkGroup:visible').each(function(i, el) {
      if (!$('input:checkbox:checked',this).length > 0)  {
        if(!$(this).hasClass('redLine')) $(this).addClass('redLine'); } else { if ($(this).hasClass('redLine')) { $(this).removeClass('redLine'); }
      }

    });
    $('div.vertical-tabs-panes > fieldset.active div > input').each(function(i, elem) {
          if( $(elem).is(":visible") && !$(elem).parent().hasClass('visDiv')) {
              $(elem).parent().addClass('visDiv');
          } else { if ( !$(elem).is(":visible") && $(elem).parent().hasClass('visDiv')) {
              $(elem).parent().removeClass('visDiv');}

          }


    });

    $('div.vertical-tabs-panes > fieldset.active div > textarea').each(function(i, elem) {
        if( $(elem).is(":visible") && !$(elem).parent().hasClass('visDiv')) {
            $(elem).parent().addClass('visDiv');
        }
        else { if ( !$(elem).is(":visible") && $(elem).parent().hasClass('visDiv')) {
              $(elem).parent().removeClass('visDiv');
            }

        }


      });

    $('div.vertical-tabs-panes > fieldset.active div > select').each(function(i, elem) {
        if( $(elem).is(":visible") && !$(elem).parent().hasClass('visDiv')) {
        $(elem).parent().addClass('visDiv');
          } else { if ( !$(elem).is(":visible") && $(elem).parent().hasClass('visDiv')) {
          $(elem).parent().removeClass('visDiv');}

        }


    });

    $('div.vertical-tabs-panes > fieldset.activeTwo .fieldReq').each(function(i, elem) {
        groupId = $(elem).attr('id');
        if (!$('#' + groupId + ' input').is(":checked")) { countempty += 1;
            if (!$('#' + groupId).hasClass('redLine')){ $('#' + groupId).addClass('redLine'); }

        } else
        { if ($('#' + groupId).hasClass('redLine')){ $('#' + groupId).removeClass('redLine'); }
        }



    });



    $('div.vertical-tabs-panes > fieldset.activeTwo .visDiv > input').each(function(i, elem) {
        if(  !$(elem).val() && !$(elem).hasClass('notReq')) { countempty += 1;

        if (!$(elem).hasClass('redLine')){ $(elem).addClass('redLine');}
          } else { if ($(elem).hasClass('redLine')){ $(elem).removeClass('redLine');} }





    });
    $('div.vertical-tabs-panes > fieldset.activeTwo .visDiv > textarea').each(function(i, elem) {
        if(  !$(elem).val() && !$(elem).hasClass('notReq')) { countempty += 1;

        if (!$(elem).hasClass('redLine')){ $(elem).addClass('redLine');}
        }
        else
        { if ($(elem).hasClass('redLine')){ $(elem).removeClass('redLine');}
        }

    });
    $('div.vertical-tabs-panes > fieldset.activeTwo .visDiv > select').each(function(i, elem) {
        if(  !$(elem).val() && !$(elem).hasClass('notReq')) {
              countempty += 1;

            if (!$(elem).hasClass('redLine')){ $(elem).addClass('redLine');}
        }
        else if ($(elem).prop('id') == 'edit-field-fac-non-work-yn-partic-und' && $(elem).val() == '-1')
        {
          countempty += 1;

          if (!$(elem).hasClass('redLine')){ $(elem).addClass('redLine');}
        }

         else if ($(elem).prop('id') == 'edit-field-com-non-work-vol-partic-und' && $(elem).val() == '-1')
         {  countempty += 1;

              if (!$(elem).hasClass('redLine')){ $(elem).addClass('redLine');}
          }
        else { if ($(elem).hasClass('redLine')){ $(elem).removeClass('redLine');} }
    });

    $('div.vertical-tabs-panes > fieldset.activeTwo .visDiv > select').each(function(i, elem) {
        if(  (!$(elem).val() || $(elem).val() == '_none')  && !$(elem).hasClass('notReq')) { countempty += 1;

        if (!$(elem).hasClass('redLine')){ $(elem).addClass('redLine');}
          } else { if ($(elem).hasClass('redLine')){ $(elem).removeClass('redLine');} }





    });
    $('div.vertical-tabs-panes > fieldset.activeTwo .visDiv > textarea').each(function(i, elem) {
        if(  (!$(elem).val())  && !$(elem).hasClass('notReq')) { countempty += 1;

        if (!$(elem).hasClass('redLine')){ $(elem).addClass('redLine');}
          } else { if ($(elem).hasClass('redLine')){ $(elem).removeClass('redLine');} }





    });

    if (countempty < 1) {
       if (!$(currentTab).hasClass('tabFilled')) {$(currentTab).addClass('tabFilled');}
       if ($(currentTab).hasClass('inComplete')) {$(currentTab).removeClass('inComplete');}
      }
      else { if ($(currentTab).hasClass('tabFilled')) {$(currentTab).removeClass('tabFilled');} if (!$(currentTab).hasClass('inComplete')) {$(currentTab).addClass('inComplete');}
      }
    $('.field-type-number-float input').each(function(i, el) {
          if($(el).val().length ) {
            commafield = $(el).val().replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");

           $(el).val(commafield);

          }
    });

  }

  function emptyFieldWarn() {
    var warnText = "";

    var centerPopup = $('div.vertical-tabs-panes');

    if (!$(this).hasClass('activeTwo')) {
       var lastlink = $('.vertical-tabs-list li a.activeTwo').attr("href");
       var heading =$('.vertical-tabs-list li a.activeTwo').text();
       var queryString =  $(location).attr('pathname');

    $('div.vertical-tabs-panes > fieldset.activeTwo .fieldReq').each(function(i, elem) {
        groupId = $(elem).attr('id');
        var radioLabel = $('label[for="' + groupId + '"]').text().length ? $('label[for="' + groupId + '"]').text() : $('label[for="' + groupId + '-und"]').text();


        if (!$('#' + groupId + ' input').is(":checked")) { warnText += "<li>" + radioLabel + "</li>";
       // $('#' + groupId).addClass('redLine');

       }



    });

    $('div.vertical-tabs-panes > fieldset.activeTwo .visDiv > input').each(function(i, elem) {
        if(  !$(elem).val() && !$(elem).hasClass('notReq')) {
        var label = "<li>" + $('label[for="'+ $(elem).attr('id')+'"]').text() + "</li>";
        // $(elem).addClass('redLine');
          warnText += label;}

    });


    var tabfilled = false;
    $('#idd_popup #popupText').empty() ;
    if (warnText != "") {

          $('#idd_popup #popupText').append("<p>The following fields are required in the section <strong>" + heading + "</span></strong></p><ul>" + warnText + "</ul>");


    } else
    {
        tabfilled = true;    if(tabfilled) { if (!$(this).hasClass('tabFilled'))$('.vertical-tabs-list li a.activeTwo').addClass('tabFilled'); if ($(this).hasClass('inComplete'))$('.vertical-tabs-list li a.activeTwo').removeClass('inComplete'); }}
        $('.vertical-tabs-list li a').each(function(i, el) {

            $(el).removeClass('activeTwo');


        });


        $('.vertical-tabs-panes > fieldset').each(function(i, el) {

            $(el).removeClass('activeTwo');


        });



    }

  }

})(jQuery);
