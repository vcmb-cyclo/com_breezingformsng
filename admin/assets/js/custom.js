jQuery(document).ready(function () {

    jQuery('#subhead-container').addClass('position-sticky');
    jQuery('#subhead-container').addClass('sticky-top');

    jQuery('joomla-toolbar-button:first').addClass('ms-auto');

    jQuery('#content .span2').addClass('sidebar').removeClass('span2');
    jQuery('#content .span10').addClass('main-content').removeClass('span10');

    jQuery('.container-main').removeClass('container-fluid');

    jQuery('#submenu').addClass('wraplist');

    jQuery('#toolbar').prepend('<div class="btn-wrapper menu-button"><button onclick="" class="btn btn-small"><i class="fa fa-bars" aria-hidden="true"></i></button></div>');

    /*
    toggle fullscreen
    */

    jQuery('main').prepend('<div class="toggle_fullscreen"> <a href="#" id="toggle_fullscreen"><i class="fa fa-up-down-left-right fa-rotate-45 fa-lg" aria-hidden="true"></i></a></div>');

    jQuery('#bfRecordsTableContainer').parent('main').prepend('<div class="toggle_fullscreen_managerecords"> <a href="#" id="toggle_fullscreen_managerecords"><i class="fa fa-up-down-left-right fa-rotate-45 fa-lg" aria-hidden="true"></i></a></div>');


    jQuery('#bfRecordsTableContainer').prevAll('.toggle_fullscreen').remove();



    jQuery('main').append('<a href="#" class="scrollToTop"><i class="fa fa-angle-up fa-3x" aria-hidden="true"></i></a>');

    jQuery('#bfFormAdvanced fieldset a').addClass('btn-more-options');

    /*
    breezingforms logo
    */

    jQuery('#sidebar .sidebar-nav').prepend('<div class="bfs-logo" align="center"><a href="https://crosstec.org" target="_blank"><img src="../administrator/components/com_breezingforms/assets/images/bf_logo.png" border="0" alt="logo"></a></div>');

    /*
    admin table
    */

    //jQuery('main #adminForm').children(":first").addClass('top-table');


    /*
      checkbox and radio button customize
     */

    jQuery('main input[type="checkbox"]').after('<label><span></span></label>');
    jQuery('main input[type="radio"]').after('<label ><span><span></span></span></label>');


    /*
     new page, section and element
     */

    //jQuery('#newStuffBar button[id="bfNewPageButton"]').replaceWith('<button class="btn btn-warning" id="bfNewPageButton" value="New page" type="submit"><i class="fa fa-plus-square" aria-hidden="true"></i> Page</button>');
    //jQuery('#newStuffBar button[id="bfNewSectionButton"]').replaceWith('<button class="btn btn-warning" id="bfNewSectionButton" value="New section" type="submit"><i class="fa fa-plus-square" aria-hidden="true"></i> Section</button>');
    //jQuery('#newStuffBar button[id="bfNewElementButton"]').replaceWith('<button class="btn btn-warning" id="bfNewElementButton" value="New element" type="submit"><i class="fa fa-plus-square" aria-hidden="true"></i> Element</button>');




    /*
    Toggle sidebar and main-content
  */

    //Save Sidebar state in sessionStorage
    if (window.sessionStorage) {

        jQuery(".sidebar").addClass(sessionStorage.toggle);
        jQuery("main").addClass(sessionStorage.toggle2);
        jQuery("main").addClass(sessionStorage.toggle3);

        jQuery('.menu-button .btn-small').click(function () {

            if (sessionStorage.toggle != "min-menu") {
                jQuery(".sidebar").toggleClass("min-menu", true);
                sessionStorage.toggle = "min-menu";

            } else {
                jQuery(".sidebar").toggleClass("min-menu", false);
                sessionStorage.toggle = "";
            }

            if (sessionStorage.toggle2 != "main-content-toggle") {
                jQuery("main").toggleClass('main-content-toggle', true);
                sessionStorage.toggle2 = "main-content-toggle";
            } else {
                jQuery("main").toggleClass("main-content-toggle", false);
                sessionStorage.toggle2 = "";
            }

            if (sessionStorage.toggle3 != "main-content-toggle-out") {
                jQuery("main").addClass("main-content-toggle-out", true);
                sessionStorage.toggle3 = "main-content-toggle-out";
            } else {
                jQuery("main").addClass("main-content-toggle-out", false);
                sessionStorage.toggle3 = "";
            }
        });
    } else {
        jQuery('.menu-button .btn-small').click(function () {
            jQuery(".sidebar").toggleClass('min-menu');
            jQuery("main").toggleClass('main-content-toggle');
            jQuery("main").addClass('main-content-toggle-out');

        });
    }

    //when Min-Menu is On remove those transitions
    if (sessionStorage.toggle == "min-menu") {
        jQuery("#sidebar").css("transition", "none");
        jQuery("main").css("transition", "none");

        jQuery('.menu-button .btn-small').click(function () {
            jQuery("#sidebar").css("transition", "width 600ms ease 0s");
            jQuery("main").css("transition", "all 600ms ease 0s");
        });
    }



    jQuery('#bfQuickModeRight span .tab-items').prepend('<i class="fa fa-cog" aria-hidden="true"></i> ');

    jQuery('#bfQuickModeRight span .tab-element').prepend('<i class="fa fa-cogs" aria-hidden="true"></i> ');


    jQuery(window).scroll(function () {

        //console.log(jQuery(window).height() - jQuery(this).scrollTop());

        if (jQuery(this).scrollTop() >= 80) {

            jQuery('#subhead').addClass('subhead-scroll');

        } else {

            jQuery('#subhead').removeClass('subhead-scroll');
        }
    });



    //Check to see if the window is top if not then display button
    jQuery(window).scroll(function () {
        if (jQuery(this).scrollTop() > 100) {
            jQuery('.scrollToTop').fadeIn();
        } else {
            jQuery('.scrollToTop').fadeOut();
        }
    });

    //Click event to scroll to top
    jQuery('.scrollToTop').click(function () {
        jQuery('html, body').animate({
            scrollTop: 0
        }, 800);
        return false;
    });

    jQuery('.admin').css('display', 'block');

    jQuery('#SelectOptionDialog').wrap('<div class="classicmode-box"></div>');



});

jQuery(document).ajaxComplete(function () {
    jQuery('.jtable-main-container input[type="checkbox"]').after('<label ><span><span></span></span></label>');
    jQuery('#bfAvailableFieldsWrapper input[type="checkbox"]').after('<label ><span><span></span></span></label>');
});


jQuery(window).on("scroll", function () {
    if (jQuery(this).scrollTop() > 10) {
        jQuery("#sidebar").addClass("sidebar-position-scroll");
    } else {
        jQuery("#sidebar").removeClass("sidebar-position-scroll");
    }
});


/****************
toggle fullscreen
****************/


jQuery(document).ready(function () {

    jQuery('#toggle_fullscreen_managerecords').click(function () {

        if (
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement

        ) {

            if (document.exitFullscreen) {
                document.exitFullscreen();
                jQuery('main .fullscreen-btn').remove();
                jQuery('main').removeAttr('style');


            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
                jQuery('main .fullscreen-btn').remove();

                jQuery('main').removeAttr('style');
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
                jQuery('main .fullscreen-btn').remove();

                jQuery('main').removeAttr('style');
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
                jQuery('main .fullscreen-btn').remove();

                jQuery('main').removeAttr('style');
            }
        } else {
            element = jQuery('main').get(0);

            if (element.requestFullscreen) {
                element.requestFullscreen();
                jQuery('main').attr('style', 'margin-left: 0 !important; overflow-y:scroll');
                jQuery('main').prepend('<button onclick="Joomla.submitbutton(&#39;exportPdf&#39;)" class="fullscreen-btn"><span class="icon-download"></span>PDF</button>');
                jQuery('main').prepend('<button onclick="Joomla.submitbutton(&#39;exportCsv&#39;)" class="fullscreen-btn"><span class="icon-download"></span>CSV</button>');
                jQuery('main').prepend('<button onclick="Joomla.submitbutton(&#39;exportXml&#39;)" class="fullscreen-btn"><span class="icon-download"></span>XML</button>');
            } else if (element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
                jQuery('main').attr('style', 'margin-left: 0 !important; overflow-y:scroll');
                jQuery('main').prepend('<button onclick="Joomla.submitbutton(&#39;exportPdf&#39;)" class="fullscreen-btn"><span class="icon-download"></span>PDF</button>');
                jQuery('main').prepend('<button onclick="Joomla.submitbutton(&#39;exportCsv&#39;)" class="fullscreen-btn"><span class="icon-download"></span>CSV</button>');
                jQuery('main').prepend('<button onclick="Joomla.submitbutton(&#39;exportXml&#39;)" class="fullscreen-btn"><span class="icon-download"></span>XML</button>');

            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
                jQuery('main').attr('style', 'margin-left: 0 !important; overflow-y:scroll');
                jQuery('main').prepend('<button onclick="Joomla.submitbutton(&#39;exportPdf&#39;)" class="fullscreen-btn"><span class="icon-download"></span>PDF</button>');
                jQuery('main').prepend('<button onclick="Joomla.submitbutton(&#39;exportCsv&#39;)" class="fullscreen-btn"><span class="icon-download"></span>CSV</button>');
                jQuery('main').prepend('<button onclick="Joomla.submitbutton(&#39;exportXml&#39;)" class="fullscreen-btn"><span class="icon-download"></span>XML</button>');


            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
                jQuery('main').attr('style', 'margin-left: 0 !important; overflow-y:scroll');
                jQuery('main').prepend('<button onclick="Joomla.submitbutton(&#39;exportPdf&#39;)" class="fullscreen-btn"><span class="icon-download"></span>PDF</button>');
                jQuery('main').prepend('<button onclick="Joomla.submitbutton(&#39;exportCsv&#39;)" class="fullscreen-btn"><span class="icon-download"></span>CSV</button>');
                jQuery('main').prepend('<button onclick="Joomla.submitbutton(&#39;exportXml&#39;)" class="fullscreen-btn"><span class="icon-download"></span>XML</button>');



            }
        }
    });


    jQuery('#toggle_fullscreen').click(function () {

        if (
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement

        ) {

            if (document.exitFullscreen) {
                document.exitFullscreen();
                jQuery('main').removeAttr('style');


            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
                jQuery('main').removeAttr('style');
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
                jQuery('main').removeAttr('style');
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
                jQuery('main').removeAttr('style');
            }
        } else {
            element = jQuery('main').get(0);

            if (element.requestFullscreen) {
                element.requestFullscreen();
                jQuery('main').attr('style', 'margin-left: 0 !important; overflow-y:scroll; width:100% !important;');


            } else if (element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
                jQuery('main').attr('style', 'margin-left: 0 !important; overflow-y:scroll; width:100% !important;');

            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
                jQuery('main').attr('style', 'margin-left: 0 !important; overflow-y:scroll; width:100% !important;');



            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
                jQuery('main').attr('style', 'margin-left: 40px !important; overflow-y:scroll; width:100% !important;');


            }
        }
    });


    jQuery(document).bind('webkitfullscreenchange mozfullscreenchange fullscreenchange MSFullscreenChange', function (e) {
        var state = document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement;
        var event = state ? 'FullscreenOn' : 'FullscreenOff';


        if (event === 'FullscreenOff') {
            jQuery('main .fullscreen-btn').remove();
            jQuery('main').removeAttr('style');

        }
    });



});