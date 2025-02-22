MappedRepairEvents.Helper = {

    init : function() {
        this.highlightFormFields();
        this.checkUrlForLoginBoxOpen();
        this.bindFlashMessageCancelButton();
        this.beautifyDropdowns();
        this.initCookieBanner();
        if (MappedRepairEvents.Helper.isIphone()) {
            $('html').addClass('iphone');
        }
        MappedRepairEvents.Helper.initLoginBoxLayoutListener();
        MappedRepairEvents.Helper.setLoginBoxLayout();
        MappedRepairEvents.Detect.initIsMobileListener();
        MappedRepairEvents.Detect.setIsMobile();
    },

    initCopyPermalinkToClipboard : function() {
        var clipboard = new ClipboardJS('.knowledge-permalink', {
            text: function(trigger) {
                return $(trigger).data('url');
            },
        });
        clipboard.on('success', function (e) {
            console.log(e);
            $(e.trigger).text('Permalink kopiert!');
            e.clearSelection();
        });
    },

    // https://github.com/Studio-42/elFinder/issues/2905#issuecomment-487106097
    copyToClipboard: function(string) {

        var temp = document.createElement('textarea');

        temp.value = string;
        temp.selectionStart = 0;
        temp.selectionEnd = temp.value.length;

        var s = temp.style;
        s.position = 'fixed';
        s.left = '-100%';

        document.body.appendChild(temp);
        temp.focus();
        var result = document.execCommand('copy');
        
        temp.blur();
        document.body.removeChild(temp);

        return result;
    },

    bindAddDateButton : function(dateHtml) {

        // remove select2 which is already initialized - it causes problems when the html is copied and pasted
        $('.date-time-wrapper select').select2('destroy');

        var addDateButton = $('a.add-date-button');

        addDateButton.on('click', function() {
            var wrapper = $(this).closest('.date-time-wrapper');
            wrapper.append(dateHtml);
            MappedRepairEvents.Helper.bindRemoveDateButton();
            MappedRepairEvents.Helper.reinitalizeDateWrappers();

            // set default values of new from first row
            var row = $('.date-time-wrapper .row');
            row.last().find('.input.text input').val(
                row.first().find('.input.text input').val()
            );
            row.last().find('.input.time input').each(function(i) {
                var id = '#0-uhrzeitend';
                if (i == 0) {
                    id = '#0-uhrzeitstart';
                }
                $(this).val($(id).val());
            });

        });

    },

    bindRemoveDateButton : function() {
        $('a.remove-date-button').off('click').on('click', function() {
            $(this).closest('.row').remove();
            MappedRepairEvents.Helper.reinitalizeDateWrappers();
        });
    },

    reinitalizeDateWrappers : function() {
        var row = $('.date-time-wrapper .row');
        // date label
        row.find('.input.text label').each(function(i) {
            var newIndex = i;
            var newLabel = newIndex + 1;
            $(this).html('Datum #' + newLabel);
            $(this).attr('for', $(this).attr('for').replace(/\d+/, newIndex));
        });
        // date field
        row.find('.input.text input').each(function(i) {
            var newIndex = i;
            $(this).attr('id', $(this).attr('id').replace(/\d+/, newIndex));
            $(this).attr('name', $(this).attr('name').replace(/\d+/, newIndex));
        });
        // time fields
        row.each(function(i) {
            var newIndex = i;
            $(this).find('.input.time input').each(function() {
                $(this).attr('id', $(this).attr('id').replace(/\d+/, newIndex));
                $(this).attr('name', $(this).attr('name').replace(/\d+/, newIndex));
            });
        });
        row.find('.datepicker-input').datepicker('destroy');
        row.find('.datepicker-input').datepicker();
    },

    /**
     * http://stackoverflow.com/questions/8472/practical-non-image-based-captcha-approaches?lq=1
     */
    updateAntiSpamField: function (prependIdPart, form, id) {

        if ($('#botEwX482' + id).length == 0) {
            var inputField = $('<input />').attr('id', 'botEwX482' + id).attr('name', 'botEwX482').attr('type', 'hidden');
            $(prependIdPart + id).prepend(inputField);
        }
        var a = document.getElementById('botEwX482' + id);
        if (isNaN(a.value) == true) {
            a.value = 0;
        } else {
            a.value = parseInt(a.value) + 1;
        }

        let timeout = setTimeout(function () {
            MappedRepairEvents.Helper.updateAntiSpamField(prependIdPart, form, id);
        }, 1000);

        if (a.value > 0) {
            clearTimeout(timeout);
        }
    },

    initLoginBoxLayoutListener : function() {
        $(window).on('resize orientationchange', function() {
            MappedRepairEvents.Helper.setLoginBoxLayout();
        });
    },

    setLoginBoxLayout : function() {
        var newRight = $('#header').width() - $(window).width() + 20;
        if (newRight < 20) {
            newRight = 20;
        }
        $('#header #login-box').css('right', newRight + 'px');
    },

    initSkillFilterForKnowledges : function() {

        var select = $('select#skills');
        select.on('change', function() {
            var filterValue = $(this).val();
            MappedRepairEvents.Helper.applyFilterForKnowledge(filterValue);
            var myhash = '#filter-' + filterValue;
            if(history.pushState) {
                history.pushState(null, null, myhash);
            } else {
                location.hash = myhash;
            }
        });

        if (window.location.hash) {
            var myhash = window.location.hash.substring(1);
            if (myhash.match(/filter-/)) {
                var filterValue = myhash.replace(/filter-/, '');
                select.val(filterValue).trigger('change');
            }
        }

    },

    applyFilterForKnowledge : function(value) {

        var allButtons = $('.collapse').find('a.skill_icon, a.button');
        allButtons.each(function() {
            $(this).removeClass('selected');
        });
        $('.knowledge-row').hide();

        if (value != '') {
            $('.knowledge-row.' + value).show();
            allButtons.each(function() {
                if ($(this).data('val') == value) {
                    $(this).addClass('selected');
                }
            });
        } else {
            $('.knowledge-row').show();
        }
    },

    bindKnowledgeFilterButtons: function() {
        var allButtons = $('.collapse').find('a.skill_icon, a.button');
        allButtons.on('click', function() {
            var value;
            if ($(this).hasClass('selected')) {
                value = '';
            } else {
                value = $(this).data('val');
            }
            $('select#skills').val(value).trigger('change');
        });
    },

    initSkillFilterForUsers : function() {
        $('select#skills').on('change', function() {
            var url = '/aktive';
            if (parseInt($(this).val()) == $(this).val()) {
                url = '/aktive/' + $(this).val() + '-' + $.slugify($(this).find('option:selected').text());
            } else {
                url = '/aktive/' + $(this).val();
            }
            document.location.href = url;
        });
    },

    /**
     * Returns a function, that, as long as it continues to be invoked, will not
     * be triggered. The function will be called after it stops being called for
     * N milliseconds. If `immediate` is passed, trigger the function on the
     * leading edge, instead of the trailing.
     * https://davidwalsh.name/javascript-debounce-function
     */
    debounce: function(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    },

    isIphone : function() {
        return true; //???
    },

    toLocaleStringSupportsOptions : function() {
        return !!(typeof Intl == 'object' && Intl && typeof Intl.NumberFormat == 'function');
    },

    formatFloatAsString: function(float, digits) {
        if (this.toLocaleStringSupportsOptions()) {
            var floatAsString = float.toLocaleString(
                'de-DE',
                {
                    minimumFractionDigits: digits,
                    maximumFractionDigits: digits
                }
            );
        } else {
            floatAsString = float.toFixed(digits);
        }
        return floatAsString;
    },

    getStringAsFloat: function (string) {
        string = string.replace(/,/, '.');
        return parseFloat(string);
    },

    initBoxToggle : function() {
        $('.box-toggle').on('click', function() {
            var collapseContentSelector = $(this).attr('href');
            if ($(this).hasClass('active')) {
                $(this).removeClass('active');
            } else {
                $(this).addClass('active');
            }
            $(collapseContentSelector).fadeToggle(100, function() {
                if ($(this).css('display') == 'none') {
                    $(this).removeClass('active');
                } else {
                    $(this).addClass('active');
                }
            });
        });

        if (window.location.hash) {
            var myhash = window.location.hash.substring(1);
            if (!myhash.match(/filter-/)) {
                $('.box-toggle' + myhash).trigger('click');
            }
        }

    },

    initCookieBanner : function() {

        $.CookiesMessage({
            messageBg: '#c8d218',
            messageText: 'Diese Seite verwendet Cookies. Durch die Nutzung unserer Seite erklären Sie sich damit einverstanden, dass wir Cookies setzen.',
            messageLinkColor: '#fff',            // Message box links color
            acceptText: 'Verstanden',                      // Accept button text
            infoText: 'Datenschutzbestimmungen einsehen',  // More Info button text
            infoUrl: '/seite/datenschutz',                 // More Info button URL
            closeEnable: false
        });
        $('#band-cookies-ok').addClass('button');
    },

    addNewTagsToSelect2Multidropdown : function(selector, newTags) {

        if (newTags !== null && newTags.length == 0) {
            return;
        }

        const selectElement = $(selector);

        for (var i in newTags) {
            var newOption = new Option(newTags[i], newTags[i], true, true);
            selectElement.append(newOption);
        }
        selectElement.trigger('change');
    },

    beautifyDropdowns : function() {
        $('select').not('.no-select2').select2();
    },

    layoutEditButtons : function() {
        var buttons = $('div.admin.edit button');
        buttons.css('top', ($('#header').height() + 40) + 'px');
        buttons.show();
    },

    initCustomCoordinatesCheckbox : function(checkboxSelector) {
        var wrapper = $('.custom-coordinates-wrapper');
        var checkbox = $(checkboxSelector);
        checkbox.on('click', function() {
            MappedRepairEvents.Helper.toggleCheckboxWrapper($(this), wrapper);
        });
        if (checkbox.is(':checked')) {
            MappedRepairEvents.Helper.toggleCheckboxWrapper(checkbox, wrapper);
        }
    },

    toggleCheckboxWrapper : function(checkbox, wrapper) {
        if (checkbox.is(':checked')) {
            wrapper.show();
        } else {
            wrapper.hide();
        }
    },

    doCurrentlyUpdatedActions : function(isCurrentlyUpdated) {

        if (!isCurrentlyUpdated) return;

        var submitButton = $('div.admin form button[type=submit]');

        //felder, die per html auf readonly gesetzt wurden (url), nicht freigeben!
        var textFields = $('div.admin form input[type=text]').not('input[readonly=readonly]');

        $('#unlockEditPageLink').click(function() {
            textFields.removeAttr('readonly');
            $('div.flash-message-box').animate({ opacity: 'toggle' }, 'slow', function() {
                MappedRepairEvents.Admin.layoutEditButtons();
            });
            submitButton.attr('disabled', false);
            submitButton.removeClass('gray');
            $('#flashMessage a.closer').trigger('click');
        });

        textFields.attr('readonly', 'readonly');
        submitButton.attr('disabled', true);
        submitButton.addClass('gray');
    },

    /**
     * @param date: yyyy-mm-dd
     */
    niceDate : function (date) {
        date = date.split('-');
        return [ date[2], date[1], date[0] ].join('.');
    },

    /**
     * @param time: hh:mm:ss
     */
    niceTime : function(time) {
        time = time.split(':');

        if (time[1] == '00') {
            return [ time[0] ];
        } else {
            return [ time[0], time[1] ].join(':');
        }
    },

    bindSlugToggle : function() {
        $('#show-url-edit-field').on('click', function() {
            $('.url-edit-field').animate({height : 'toggle'}, 300);
        });
    },

    addSpinnerToButton: function (button, iconClass) {
        button.find('i').removeClass(iconClass);
        button.find('i').addClass('fa-circle-notch');
        button.find('i').addClass('fa-spin');
    },

    removeSpinnerFromButton: function (button, iconClass) {
        button.find('i').removeClass('fa-circle-notch');
        button.find('i').removeClass('fa-spin');
        button.find('i').addClass(iconClass);
    },

    bindSaveAndRedirectToUrlButton: function() {
        $('#save-and-redirect-to-url-button').on('click', function() {
            $('input[name="referer"]').val($(this).data('redirect-url'));
            $(this).closest('form').submit();
        });
    },

    bindCancelButtonWithFixedRedirect: function(uid, redirect) {

        uid = uid || 0;

        $('#cancel-button').on('click', function() {

            MappedRepairEvents.Helper.ajaxCall(
                '/admin/' + 'intern/' + 'ajaxCancelAdminEditPage/',
                {
                    uid: uid,
                    referer: redirect
                },
                { onOk : function(data) {
                    document.location.href = data.referer;
                },
                onError : function(data) {
                    alert(data.message);
                }
                }
            );
        });


    },

    bindCancelButton : function(uid) {

        uid = uid || 0;

        $('#cancel-button').on('click', function() {

            if (uid == 0) {
                document.location.href = $('input[name="referer"]').val();
            }

            MappedRepairEvents.Helper.ajaxCall(
                '/admin/' + 'intern/' + 'ajaxCancelAdminEditPage/',
                {
                    uid: uid,
                    referer: $('input[name="referer"]').val()
                },
                { onOk : function(data) {
                    document.location.href = data.referer;
                },
                onError : function(data) {
                    alert(data.message);
                }
                }
            );
        });

    },

    addSpinner: function (button) {
        button.html('<i class="fa fa-spinner fa-spin"><i>');
        this.disableButton(button);
    },

    removeSpinner: function (button, label) {
        button.html(label);
        this.enableButton(button);
    },

    enableButton: function (button) {
        button.attr('disabled', false);
        button.removeClass('disabled');
    },

    disableButton: function (button) {
        button.attr('disabled', 'disabled');
        button.addClass('disabled'); // :enabled selector does not work in chrome, bootstrap adds pointer-events: none;
    },

    initScrollToTopButton: function() {

        $('#scroll-to-top').hide();

        $(window).scroll(function() {
            if ($(this).scrollTop() > 100) {
                $('#scroll-to-top').fadeIn();
            } else {
                $('#scroll-to-top').fadeOut();
            }
        });

        $('#scroll-to-top a').on('click', function() {
            $('body,html').animate({
                scrollTop: 0
            }, 400);
            return false;
        });

    },

    initRegistration : function() {

        // remove double ids because form is rendered twice - all labels are clickable
        var multipleCheckbox = $('.fcph .categories-checkbox-wrapper .checkbox');
        multipleCheckbox.find('label').removeAttr('for');
        multipleCheckbox.find('input').removeAttr('id');

        $('.registration-button').on('click', function() {
            var formOpenedText = '▼ Bitte ausfüllen';
            if ($(this).html() != formOpenedText) {
                $(this).data('originalText', $(this).html());
                $(this).html(formOpenedText);
            } else {
                $(this).html($(this).data('originalText'));
            }
            $(this).closest('.half').find('.fcph').slideToggle('slow');
        });
        if ($('#flashMessage.error').length > 0) {
            var userGroupId = 7;
            if (window.location.href.match(/orga/)) {
                userGroupId = 9;
            }
            $('.fcph-' + userGroupId).closest('.half').find('.registration-button').trigger('click');
        }
    },

    initSlider: function(selector) {
        new Swiper(selector, {
            loop: true,
            autoplay: {
                delay: 6000,
            },
            pagination: {
                el: '.swiper-pagination',
                type: 'bullets',
                clickable: true,
            },
        });
    },

    hideAndResetCalendarEventsBox : function() {
        $('#calEvents').hide();
        $('#selectedDate').attr('data-date', '').html('');
        $('#calDottedLine').hide();
    },

    showEventDetail : function(event, isMobile) {

        var parsedEvent = $.parseJSON(event);
        $('.fc-day[data-date='+parsedEvent[1]+']').trigger('click');

        if (isMobile) {
            var eventRow = $('.calEvent[rel^="'+parsedEvent[0]+'"]');
            if (eventRow.length == 0) {
                return; // avoid error for past events
            }
            eventRow.trigger('click');
            this.beforeBodyAnimateMobile();
            $('html,body').animate({
                scrollTop: eventRow.offset().top - $('#header').height() + eventRow.height()
            }, 400, null, MappedRepairEvents.Helper.afterBodyAnimateMobile
            );
        }
    },

    beforeBodyAnimateMobile : function() {
        $('html,body').css('height', 'inherit');
    },

    /* html and body need height inherit for a working scrollTop,
     * but mobile navigation is not working properly with this setting
     * therefore it is reset on navToggle click
     */
    afterBodyAnimateMobile : function() {
        $('#navToggle').one('click', function() {
            $('html,body').css('height', '100%');
        });
    },

    updateDayEventCount : function(dayRow) {
        var eventCount = $('.calEvent[rel*="' + dayRow.data('date')+'"]').not('.isntInRadius').length;
        var day = dayRow.find('.fc-daygrid-day-events');
        day.find('span.event-count').remove();
        if (eventCount > 0) {
            day.html('<span class="event-count">' + eventCount + '</span>');
            day.find('.event-count').show();
        }
    },

    bindCalEventClickHome : function() {
        $('.calEvent').on('click', function() {
            var rel = $(this).attr('rel').split(' ');
            window.location.href = '/'+rel[1]+'?event='+[rel[0], rel[2]].join(',')+'#datum';
        });
    },

    /**
     * for mobile version
     * implemented as accordion - only one event detail can be shown at a time
     */
    bindCalEventClickWorkshopDetailMobile : function() {
        $('.calEvent').on('click', function() {
            var eventBox2Show = $(this).next('.eventBox');
            eventBox2Show.siblings('.eventBox').hide();
            eventBox2Show.toggle(100);
        });
    },

    getCategoryIcon : function(category) {
        return '<div title="' + category.name + '" class="skill_icon small ' + category.icon + '"></div>';
    },

    getCalEventHtml : function(ev, wuid, showDate, stringEventIsActive, stringEventIsInactive, stringEditEvent, stringDuplicateEvent, stringConfirmDeleteEvent, stringDeleteEvent, stringNoCategories) {

        var calEvent = '<div itemscope itemtype="http://schema.org/Event" class="calEvent ';
        calEvent += ev.status == 1 ? 'active' : 'inactive';
        calEvent += ev.is_online_event == 1 ? ' is-online-event' : '';
        calEvent += '" style="display:none;"';
        calEvent += ' rel="' + [ev.uid, ev.wurl, ev.datumstart_formatted].join(' ');
        calEvent += '" data-date="' + ev.datumstart_formatted;
        calEvent += '">';

        if (ev.hasModifyPermissions) {
            calEvent += '<div class="active-inactive" style="color:'+( ev.status == 1 ? 'green' : 'red' )+';">' + ( ev.status == 1 ? stringEventIsActive : stringEventIsInactive) + '</div>';
        }

        var formattedDate = '';
        if (showDate) {
            formattedDate = MappedRepairEvents.Helper.niceDate(ev.datumstart_formatted);
            formattedDate += ' / ';
        }
        var formattedTime = '';
        if (ev.uhrzeitstart_formatted != '00:00' && ev.uhrzeitend_formatted != '00:00') {
            formattedTime = MappedRepairEvents.Helper.niceTime(ev.uhrzeitstart_formatted)+' - '+MappedRepairEvents.Helper.niceTime(ev.uhrzeitend_formatted)+' Uhr';
        }
        if (ev.is_online_event) {
            calEvent += '<span class="is-online-event">[Online]</span>';
        }
        calEvent += '<span itemprop="name"><b>'+ formattedDate + ev.eventname+'</b></span>: ';
        calEvent += '<span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"><span itemprop="streetAddress">'+ev.strasse+',</span> ';
        calEvent += '<span itemprop="postalCode"> '+ev.zip+'</span> ';
        calEvent += '<span itemprop="addressLocality">'+ev.ort+'</span> von ';
        calEvent += '<meta itemprop="startDate" content="'+ev.datumstart_formatted+'T'+MappedRepairEvents.Helper.niceTime(ev.uhrzeitstart_formatted)+'" /><span>'+MappedRepairEvents.Helper.niceTime(ev.uhrzeitstart_formatted)+'</span> - ';
        calEvent += '<meta itemprop="endDate" content="'+ev.datumstart_formatted+'T'+MappedRepairEvents.Helper.niceTime(ev.uhrzeitend_formatted)+'" /><span>'+MappedRepairEvents.Helper.niceTime(ev.uhrzeitend_formatted)+' Uhr</span>';

        calEvent += '</div>';

        if (wuid) {
            calEvent += '<div class="eventBox" style="display:none;" itemscope itemtype="http://schema.org/Event">';

            if (ev.hasModifyPermissions) {
                calEvent += '<div class="active-inactive" style="color:'+( ev.status == 1 ? 'green' : 'red' )+';">' + ( ev.status == 1 ? stringEventIsActive : stringEventIsInactive) + '</div><br />';
            }

            if (ev.hasModifyPermissions) {
                calEvent += '<div class="deleteEvent">';
                if (ev.isPast == '0') {
                    calEvent += '<a class="editEvent button" href="/termine/edit/'+ev.uid+'">'+stringEditEvent+'</a>';
                }
                calEvent += '<a class="dupEvent button" href="/termine/duplicate/'+ev.uid+'">'+stringDuplicateEvent+'</a>';
                if (ev.isPast == '0') {
                    calEvent += '<a class="delEvent button gray" href="/termine/delete/'+ev.uid+'" onclick="return confirm(\''+stringConfirmDeleteEvent+'\');">'+stringDeleteEvent+'</a>';
                }
                calEvent += '</div>';
                calEvent += '<div class="sc"></div><br />';
            }

            calEvent += ( ev.image ? '<div itemprop="image" class="imgevent"><img src="/files/uploadify/events/thumbs-100/'+ev.image+'"></div>' : '' );
            calEvent += '<div class="eventtitel" itemprop="name">'+ev.eventname+'</div>';
            calEvent += '<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"><span itemprop="streetAddress">'+ev.strasse+'</span>, <span itemprop="postalCode"> '+ev.zip+'</span> <span itemprop="addressLocality">'+ev.ort+'</span>, <span itemprop="addressCountry">'+ev.land+'</span></div>';
            calEvent += '<div>'+ev.veranstaltungsort+'</div>';
            calEvent += '<meta itemprop="startDate" content="'+ev.datumstart_formatted+'T'+MappedRepairEvents.Helper.niceTime(ev.uhrzeitstart_formatted)+'" />';
            calEvent += '<meta itemprop="endDate" content="'+ev.datumstart_formatted+'T'+MappedRepairEvents.Helper.niceTime(ev.uhrzeitend_formatted)+'" />';
            calEvent += '<div>' + formattedDate + formattedTime + '</div>';
            calEvent += '<br /><div itemprop="description">'+ev.eventbeschreibung+'</div>';
            calEvent += '<div class="sc"></div>';

            if (ev.categories && ev.categories.length > 0) {
                for(var i in ev.categories) {
                    calEvent += '<div title="' + ev.categories[i].name + '" class="lstCatNew skill_icon small ' + ev.categories[i].icon + '"></div>';
                }
            } else {
                calEvent += stringNoCategories;
            }

            calEvent += '<div class="sc"></div><br />';
            calEvent += '<div class="mapevent" itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">';
            calEvent += '<meta itemprop="latitude" content="'+ev.lat+'" />';
            calEvent += '<meta itemprop="longitude" content="'+ev.lng+'" />';
            calEvent += '<iframe class="eiwsd" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="//www.openstreetmap.org/export/embed.html?bbox='+ev.lng+'%2C'+ev.lat+'%2C'+ev.lng+'%2C'+ev.lat+'&amp;zoom=14&amp;layers=H&amp;marker='+ev.lat+'%2C'+ev.lng+'" ></iframe>';
            calEvent += '</div>';
            calEvent += '<div class="sc"></div>';

            calEvent += '</div>';
        }

        return calEvent;
    },

    bindDownloadInfoSheetButton : function() {
        $('a.download-info-sheets').on('click', function() {
            var workshopUid = $(this).data('workshop-uid');
            var year = $(this).closest('.workshop-content-wrapper').find('select[name="info-sheets-year"]').val();
            var yearUrlString = '';
            if (year != '') {
                yearUrlString = '/' + year;
            }
            window.open('/laufzettel/download/' + workshopUid + yearUrlString);
        });
    },

    bindDeleteEventButton : function() {

        $('a.delete-event').on('click', function() {
            var message = 'Soll dieser Termin wirklich gelöscht werden?<br />Dies kann nicht rückgängig gemacht werden!';
            var eventUid = $(this).closest('tr').find('td.eventUid').html();
            $.prompt(message, {
                buttons : {
                    Ja : true,
                    Nein : false
                },
                submit : function(v, m, f) {
                    if (m) {
                        var url = '/termine/delete/' + eventUid;
                        document.location.href = url;
                    }
                }
            });
        });

    },

    bindDeleteInfoSheetButton : function() {

        $('a.delete-info_sheet').on('click', function() {
            var message = 'Soll dieser Laufzettel wirklich gelöscht werden?<br />Dies kann nicht rückgängig gemacht werden!';
            var eventUid = $(this).closest('tr').find('td.infoSheetUid').html();
            $.prompt(message, {
                buttons : {
                    Ja : true,
                    Nein : false
                },
                submit : function(v, m, f) {
                    if (m) {
                        var url = '/laufzettel/delete/' + eventUid;
                        document.location.href = url;
                    }
                }
            });
        });

    },

    getFileExtension : function(path) {
        return path.split('.').pop();
    },

    adaptHeightOfWorkshopsBoxLogo : function(container, isMobile) {
        var logos = $(container).find('.row .inner img');
        logos.each(function() {
            $(this).on('load', function() {
                var logoHeight = $(this).height();
                var containerHeight = $(this).closest('.row').height();
                if (logoHeight > containerHeight) {
                    var logoWidth = $(this).width();
                    var newHeight = containerHeight - 10;
                    $(this).height(newHeight);
                    var newWidth = newHeight * logoWidth / logoHeight;
                    $(this).width(newWidth);
                    if (!isMobile) {
                        $(this).css('margin-left', 20 + (70 - newWidth) / 2);
                    } else {
                        $(this).addClass('height-adapted');
                    }
                }
            }).trigger('load');
        });
    },

    initSubNavi : function() {

        var mainNavi = $('#nav > ul > li > a');
        var subNavi = $('#nav > ul ul');

        var preselectedSubNavi = $('#nav > ul ul:visible');

        // center sub navis
        mainNavi.each(function() {

            var centerPositionMainNavi = $(this).position().left + $(this).outerWidth() / 2;
            var subnavi = $(this).closest('li').find('ul');

            if (subnavi.length > 0) {

                subnavi.css('paddingLeft', 0);
                subnavi.css('width', 0);

                var subnaviInnerWidth = 0;
                subnavi.find('li').each(function() {
                    var originalDisplay = $(this).closest('ul').css('display');
                    if (originalDisplay == 'none') {
                        $(this).closest('ul').show();
                    }
                    subnaviInnerWidth += $(this).width();
                    if (originalDisplay == 'none') {
                        $(this).closest('ul').hide();
                    }
                });

                var leftForSubNavi = centerPositionMainNavi - subnaviInnerWidth / 2;
                subnavi.css('left', 0);
                subnavi.css('paddingLeft', leftForSubNavi);

                var subnaviOuterWidth = $('#header').width() - leftForSubNavi;
                subnavi.css('width', subnaviOuterWidth);
            }
        });

        mainNavi.on('mouseover', function(e) {
            subNavi.hide();
            $(this).closest('li').find('ul').show();
        });

        mainNavi.on('mouseout', function(e) {
            $(this).closest('li').not(':hover').find('ul').hide();
            preselectedSubNavi.show();
        });

        subNavi.on('mouseover', function(e) {
            subNavi.hide();
            $(this).show();
        });

        subNavi.on('mouseout', function(e) {
            $(this).hide();
            preselectedSubNavi.show();
        });

    },

    showSubNavi : function(activeElement) {
        // show subnavi if active element is main element
        if (activeElement.parent().find('ul.submenu').length > 0) {
            activeElement.parent().find('ul.submenu').show();
        }

        // show subnavi if active element is subnavi element
        if (activeElement.parent().parent().hasClass('submenu')) {
            var submenu = activeElement.parent().parent();
            submenu.parent().find('> a').addClass('active');
            submenu.show();
        }
    },

    initMobile : function() {

        // elements in header.ctp are cached
        if ($('#header').hasClass('mobile')) {
            $('#nav').addClass('mobile');
        }

        var socialIcons = [];
        $('div.socialicons a').each(function() {
            var item = $('<li />').html($(this));
            var splitTitle = $(this).attr('title').split(' ');
            var socialMediaName = splitTitle[splitTitle.length - 1];
            item.find('a').html(socialMediaName);
            socialIcons.push(item);
        });
        $('#nav #menu').append(socialIcons);


        $('body').append($('#nav'));
        $('#login-box').show();

        var controller = new slidebars();
        controller.init();

        $('#navToggle').on('click', function (event) {
            event.stopPropagation();
            event.preventDefault();
            controller.toggle('main-menu');
        });

        $(controller.events).on('opened', function (event, id) {
            $('[canvas]').on('click', function() {
                controller.close(id);
            });
        });

        // chrome pull refresh fix
        $('body, html').css('overflow-y', 'auto');

    },

    initFindEventsForm : function(form) {
        $(form).find('input[type="text"]').on('keyup', function(e) {
            var button = $(form).find('a');
            button.attr('href', '/termine/?keyword=' + $(this).val());
            if (e.which == 13) {
                window.location.href = button.attr('href');
            }
        });
    },

    appendCssFile : function(file) {
        $('head').append('<link rel="stylesheet" type="text/css" href="/' + file + '.css" />');
    },

    initJqueryTabsWithoutAjax : function() {
        $('.custom-ui-tabs > ul > li').on('mouseover', function(e) {
            $(this).addClass('ui-state-hover');
        });
        $('.custom-ui-tabs > ul > li').on('mouseout', function(e) {
            $(this).removeClass('ui-state-hover');
        });
    },

    initWorkshopDetail : function(workshopUid) {

        this.loadWorkshopForWorkshopDetailCalendar(workshopUid);

        $('#tabs, #tabs2').tabs();

        $(document).on('mouseover', '.skill_icon', function(e){
            if(!$(this).data('skill_icon')){
                $(this).tooltip({
                    content: function() {
                        return $(this).attr('title');
                    }
                }).triggerHandler('mouseover');
            }
        });

    },

    fixChartInHiddenIframe : function () {
        var chartIframeTab = $('.ui-tabs-anchor[href$="#tabs-3"]');
        chartIframeTab.on('click', function() {
            $('#tabs-3').removeClass('force-show');
            var iframe = $('#tabs-3').find('iframe');
            iframe.height(iframe.data('height'));
        });
    },

    loadWorkshopForWorkshopDetailCalendar : function(workshopUid) {
        $.ajax('/workshops/ajaxGetAllWorkshopsForMap?workshopUid=' + workshopUid, {
            'async': false
        }).done(
            function(data) {
                MappedRepairEvents.Helper.workshop = data.workshops;
            }
        );
    },

    initTooltip : function(container) {
        $(container).tooltip({
            content: function() {
                return $(this).attr('title');
            },
            position: { my: 'right top', at: 'right bottom' }
        });
    },

    bindApplyWorkshopButton : function(button) {

        var workshopUid = button.closest('form').find('#0-workshop-uid').val();

        if (workshopUid == 0) {
            alert('Bitte wähle eine Initiative aus.');
            return;
        }

        $('.ajaxLoader').show();

        MappedRepairEvents.Helper.ajaxCall('/workshops/ajaxGetWorkshopDetail/' + workshopUid, {}, {
            onOk : function(data) {
                $('.ajaxLoader').hide();
                $('#0-veranstaltungsort').val(data.workshop.adresszusatz);
                $('#0-strasse').val(data.workshop.street);
                $('#0-zip').val(data.workshop.zip);
                $('#0-ort').val(data.workshop.city);
                $('#0-land').val(data.workshop.country.name_de);
                var useCustomCoordinatesCheckbox = $('#0-use-custom-coordinates');
                if (data.workshop.use_custom_coordinates) {
                    useCustomCoordinatesCheckbox.prop('checked', true);
                } else {
                    useCustomCoordinatesCheckbox.prop('checked', false);
                }
                var wrapper = $('.custom-coordinates-wrapper');
                MappedRepairEvents.Helper.toggleCheckboxWrapper(useCustomCoordinatesCheckbox, wrapper);
                $('#0-lat').val(data.workshop.lat);
                $('#0-lng').val(data.workshop.lng);

                $('.categories-checkbox-wrapper select').prop('checked', false);
                for(var i in data.workshop.categories) {
                    var categoryId = data.workshop.categories[i].id;
                    $('#0-categories-ids-' + categoryId).prop('checked', true);
                }

            },
            onError : function(data) {
                alert(data.message);
            }
        });

    },

    initSelfSubmittingForm: function(form) {
        $(form).find('input,select').prop('disabled', false);
        $(form).on('submit', function() {
            $('#content').css('opacity', .5);
        });
    },

    triggerSubmitAfterTextInputKeyup: function(form) {
        $(form).find('input[type="text"]').on('keyup', MappedRepairEvents.Helper.debounce(
            function() {
                $(form).submit();
            }, 2000)
        );
    },

    triggerSubmitAfterChange: function(form) {
        $(form).find('input[type="text"],select').on('change', function() {
            $(form).submit();
        });
        $(form).find('input[type="checkbox"]').on('click', function() {
            $(form).submit();
        });
    },

    initWorkshopAllForm: function(form) {
        MappedRepairEvents.Helper.initSelfSubmittingForm(form);
        MappedRepairEvents.Helper.triggerSubmitAfterChange(form);
        MappedRepairEvents.Helper.triggerSubmitAfterTextInputKeyup(form);
    },

    /**
     * updates href of category links depending on input field value (keyword)
     * so that keyword is not lost if category button is clicked (instead of submit button)
     */
    initEventAllForm : function(form) {

        MappedRepairEvents.Helper.initSelfSubmittingForm(form);
        MappedRepairEvents.Helper.triggerSubmitAfterChange(form);
        MappedRepairEvents.Helper.triggerSubmitAfterTextInputKeyup(form);

        $(form).find('input#keyword').on('keyup', function(event) {

            var keyword = $(this).val();

            $(form).find('a.lstCat').each(function() {

                var href = $(this).attr('href');
                var params = href.split('/');

                // sometimes 'index' is added in url => not needed, messes up length count
                for(var i in params) {
                    if (params[i] == 'index') {
                        params.splice(i, 1);
                    }
                }

                if (params.length == 3) {
                    // param keyword not yet existing
                    params.splice(2, 0, 'keyword:' + keyword);
                }

                if (params.length == 4) {
                    // param keyword already existing
                    params[2] = 'keyword:' + keyword;
                }

                $(this).attr('href', params.join('/'));

            });

        });

    },

    bindTooltip : function(selector) {
        $(selector).tooltip({
            content: function() {
                return $(this).attr('title');
            }
        });
    },

    bindFlashMessageCancelButton : function() {
        $('#flashMessage a.closer').on('click', function() {
            $(this).parent().animate({height : 'toggle'}, 300);
        });
    },

    bindToggleLinks: function (autoOpen, openFirstElement) {

        $('.toggle-link').on('click', function () {
            MappedRepairEvents.Helper.doToggle($(this), $(this).next().next());
        });

        if (autoOpen) {
            $('.toggle-link').trigger('click');
        }

        if (openFirstElement) {
            $('.toggle-link').first().trigger('click');
        }

    },

    bindShowMoreLink: function(autoOpen) {

        autoOpen = autoOpen || false;

        $('.show-more-link').on('click', function () {
            $('article.preview').hide();
            $(this).hide();
            MappedRepairEvents.Helper.doToggle($(this), $(this).next());
        });

        if (autoOpen) {
            $('.show-more-link').trigger('click');
        }

    },

    bindToggleLinksForSubtables : function() {
        $('.toggle-link-for-subtable').on('click', function () {
            MappedRepairEvents.Helper.doToggle($(this), $(this).closest('tr').next('tr.subtable-container'));
        });
    },

    doToggle : function(element, elementToToggle) {

        var toggleMode = elementToToggle.css('display');

        if (toggleMode == 'none') {
            element.html(element.html().replace(/Mehr/, 'Weniger'));
            element.addClass('collapsed');
        } else {
            element.html(element.html().replace(/Weniger/, 'Mehr'));
            element.removeClass('collapsed');
        }

        elementToToggle.stop(true, true).animate({
            height: 'toggle'
        }, 400);

    },

    isChrome : function() {
        return navigator.userAgent.match(/chrome/i);
    },

    isSafari : function() {
        return navigator.userAgent.match(/safari/i);
    },

    addCssFile : function(cssFile) {
        cssFile += '.css';
        $.get(cssFile, function(cssFile) {
            $('head').append('<style type=\'text/css\'>' + cssFile + '</style>');
        });
    },

    initHome : function() {
        $('#home .box.static a.teaser-text-link').click(function() {
            $('#home .box.static div.teaser-text').animate({height : 'toggle'}, 1000);
            if ($(this).html() == '... weiterlesen') {
                $(this).html('... weniger');
            } else {
                $(this).html('... weiterlesen');
            }
        });
    },

    /**
     * sorts an associative array by a given field
     * http://stackoverflow.com/questions/979256/how-to-sort-an-array-of-javascript-objects
     *
     *  // Sort by price high to low
     *  homes.sort(sortBy('price', true, parseInt));
     *  //Sort by city, case-insensitive, A-Z
     *  homes.sort(sortBy('city', false, function(a){return a.toUpperCase()}));
     */
    sortBy : function(field, reverse, primer) {

        var key = function (x) {return primer ? primer(x[field]) : x[field];};

        return function (a,b) {
            var A = key(a), B = key(b);
            return ((A < B) ? -1 : (A > B) ? +1 : 0) * [-1,1][+!!reverse];
        };

    },

    removeDuplicates : function(inputArray) {
        var i;
        var len = inputArray.length;
        var outputArray = [];
        var temp = {};

        for (i = 0; i < len; i++) {
            temp[inputArray[i]] = 0;
        }
        for (i in temp) {
            outputArray.push(i);
        }
        return outputArray;
    },

    bindNewObjectButton : function(selector, message, url) {
        $(selector).click(function() {
            $.prompt(message, {
                buttons : {
                    Ja : true,
                    Abbrechen : false
                },
                submit : function(v, m, f) {
                    if (v) {
                        MappedRepairEvents.Helper.ajaxCall(url, {}, {
                            onOk : function(data) {
                                document.location.href = data.redirectUrl;
                            },
                            onError : function(data) {
                                alert(data.message);
                            }
                        });

                    }
                }
            });
        });
    },

    getFlashMessageBoxContainer : function() {
        return $('#flashMessage');
    },

    setFlashMessageSuccess : function(message) {
        return this.setFlashMessage('success', message);
    },

    setFlashMessage : function(type, message) {
        this.getFlashMessageBoxContainer().html(message);
        this.getFlashMessageBoxContainer().addClass(type);
        this.getFlashMessageBoxContainer().show('slow');
    },
    hideFlashMessage : function(type) {
        type = type || '';
        $('#flashMessage' + type).hide('slow');
    },
    quickHideFlashMessage : function() {
        this.getFlashMessageBoxContainer().hide();
    },

    bindApplyForCollaborationButtonUser : function() {

        $('#mitarbeits-anfrage-stellen').click(function() {

            var selectedWorkshop = $('#users-workshops-workshop-uid option:selected').text();
            var selectedUser = $('#users-workshops-user-uid option:selected').text();
            var infoText = 'Möchtest du wirklich der Initiative <b>' + selectedWorkshop + '</b> beitreten?';
            if (selectedUser != '') {
                infoText = 'Möchtest du wirklich <b>' + selectedUser+ '</b> der Initiative <b>' + selectedWorkshop + '</b> zuordnen?';
            }
            $.prompt(infoText, {
                buttons : {
                    Ja : true,
                    Abbrechen : false
                },
                submit : function(v, m, f) {
                    if (v) {
                        $('#workshopApply').submit();
                    }
                }
            });
        });
    },

    checkUrlForLoginBoxOpen : function() {
        if (document.location.search.match(/\?login=/)) {
            $('#login-box-form').show();
        }
    },

    bindWorkshopUserActions : function(email) {

        $('a.resign-not-possible').on('click', function() {
            $.prompt('Du bist der/die letzte aktive Organisator*in der Initiative, daher ist das Austreten nicht möglich. <br /><br />Bei Fragen wende dich bitte an ' + email);
        });

        $('a.refuse-not-possible').on('click', function() {
            var name = $(this).closest('tr').find('td:first').html();
            $.prompt('<b>' + name + '</b> ist der/die letzte aktive Organisator*in der Initiative, daher ist das Beenden der Mitarbeit nicht möglich. <br /><br />Bei Fragen wende dich bitte an ' + email);
        });

        $('a.refuse, a.approve, a.resign').on('click', function() {

            var name = $(this).closest('tr').find('td:first').html();
            var userUid = $(this).closest('tr').find('div.userUid').html();
            var workshopUid = $(this).closest('tr').find('div.workshopUid').html();

            var type = $(this).closest('tr').find('.user-type').html();

            switch(type) {
            case 'user' :
                var refuseMessage = 'Möchtest du die Mitarbeit von ' + name + ' wirklich beenden?';
                var approveMessage = 'Möchtest du ' + name + ' wirklich als Mitarbeiter bestätigen?';
                var resignMessage = 'Möchtest du wirklich aus der Initiative <b>' + name + '</b> austreten?';
                break;
            }

            var message, url, buttons;
            if ($(this).hasClass('refuse')) {
                message = refuseMessage;
                url = '/initiativen/' + 'use' + 'r/ref' + 'use/' + type + '/' +  userUid + '/' + workshopUid;

                buttons = {
                    Beenden : true,
                    Abbrechen : false
                };
            }

            if ($(this).hasClass('approve')) {
                message = approveMessage;
                url = '/initiativen/' + 'use' + 'r/appro' + 've/' + type + '/' +  userUid + '/' + workshopUid;

                buttons = {
                    Bestaetigen : true,
                    Abbrechen : false
                };
            }

            if ($(this).hasClass('resign')) {
                message = resignMessage;
                url = '/initiativen/' + 'use' + 'r/res' + 'ign/' + type + '/' +  userUid + '/' + workshopUid;
                buttons = {
                    Austreten : true,
                    Abbrechen : false
                };
            }

            $.prompt(message, {
                buttons : buttons,
                submit : function(v, m, f) {
                    if (m) {
                        document.location.href = url;
                    }
                }
            });

        });
    },

    highlightFormFields : function() {
        var formFieldsToHighlight = $(
            'input[type="text"], input[type="password"], textarea, select').not(
            'input[readonly="readonly"]');
        formFieldsToHighlight.on('focus', function() {
            $(this).css('background-color', '#EFEFEF');
        });
        formFieldsToHighlight.on('blur', function() {
            $(this).css('background-color', 'white');
        });
    },

    doLoginFormActions : function() {

        $('#anmelden-link').on('click', function() {
            $('#login-box-form').animate({height : 'toggle'}, 500);
        });

        $('#UserEmail').on('blur', function() {
            MappedRepairEvents.Helper.resetInputField(this, 'E-Mail-Adresse');
        });

        $('#UserEmail').on('focus', function() {
            MappedRepairEvents.Helper.emptyInputField(this, 'E-Mail-Adresse');
        });

        $('#UserPassword').on('blur', function() {
            MappedRepairEvents.Helper.resetInputField(this, 'Passwort');
        });

        $('#UserPassword').on('focus', function() {
            MappedRepairEvents.Helper.emptyInputField(this, 'Passwort');
        });

    },

    /**
     * usage on form field onblur TODO '+' is not escaped and therefore always
     * replaced -> function does not work if default value has '+' in it!
     *
     * @param string
     *            form field
     * @param string
     *            defaultValue
     */
    resetInputField : function(formField, defaultValue) {
        if (formField.value == '') {
            jQuery(formField).css('color', '#999');
            formField.value = unescape(decodeURI(defaultValue)).replace(/\+/g, ' ');
        }
    },

    /**
     * usage on form field onfocus TODO '+' is not escaped and therefore always
     * replaced -> function does not work if default value has '+' in it!
     *
     * @param string
     *            form field
     * @param string
     *            defaultValue
     */
    emptyInputField : function(formField, defaultValue) {
        jQuery(formField).css('color', '#333');
        if (formField.value.replace(/\+/g, ' ') == unescape(decodeURI(defaultValue)).replace(/\+/g, ' ')) {
            formField.value = '';
        }
    },

    /**
     * German initialisation for the jQuery UI date picker plugin. Written by
     * Milian Wolff (mail@milianw.de).
     */
    initDatepicker : function() {
        jQuery(function($) {
            $.datepicker.regional['de'] = {
                closeText : 'schließen',
                prevText : '&#x3c;zurück',
                nextText : 'Vor&#x3e;',
                currentText : 'heute',
                monthNames : [ 'Januar', 'Februar', 'März', 'April', 'Mai',
                    'Juni', 'Juli', 'August', 'September', 'Oktober',
                    'November', 'Dezember' ],
                monthNamesShort : [ 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun',
                    'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez' ],
                dayNames : [ 'Sonntag', 'Montag', 'Dienstag', 'Mittwoch',
                    'Donnerstag', 'Freitag', 'Samstag' ],
                dayNamesShort : [ 'So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa' ],
                dayNamesMin : [ 'So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa' ],
                weekHeader : 'Wo',
                dateFormat : 'dd.mm.yy',
                firstDay : 1,
                isRTL : false,
                showMonthAfterYear : false,
                yearSuffix : '',
                changeYear : true,
                changeMonth : true,
                duration : 'fast',
                yearRange : '2010:2030'
            };
            $.datepicker.setDefaults($.datepicker.regional['de']);
        });
    },

    ajaxCall : function(url, data, callbacks) {

        var csrfToken = $('meta[name="csrfToken"]').attr('content');
        jQuery.ajaxSetup({
            headers:
            { 'X-CSRF-TOKEN': csrfToken }
        });

        return jQuery.ajax( {
            url : url,
            type : callbacks.method || 'POST',
            contentType : 'application/x-www-form-urlencoded; charset=utf-8',
            data : data,
            dataType : 'json',
            success : function(data, textStatus) {
                try {
                    if (callbacks.onEnd)
                        callbacks.onEnd(data);
                    if (data.status == 0) {
                        callbacks.onOk(data);
                    } else {
                        callbacks.onError(data);
                    }
                } catch (e) {
                    if (console && console.error) {
                        console.error(e);
                    } else {
                        alert(e.toString());
                    }
                }
            },
            error : function(XMLHttpRequest, textStatus, errorThrown) {
                data = {
                    status : 9,
                    msg : 'Fatal Error, see error log',
                    jquery : {
                        XMLHttpRequest : XMLHttpRequest,
                        textStatus : textStatus,
                        errorThrown : errorThrown
                    }
                };
                if (callbacks.onEnd)
                    callbacks.onEnd(data);
                callbacks.onError(data);
                if (window.console && console.error) {
                    console.error(data);
                } else {
                    alert(data.msg + ' ' + textStatus + ' ' + errorThrown);
                }
            }
        });
    }

};
