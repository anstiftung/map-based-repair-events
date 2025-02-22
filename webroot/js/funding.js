MappedRepairEvents.Funding = {

    useDisabledRadioButtonsWorkaround: () => {
        document.querySelector('form').addEventListener('submit', function() {
            document.querySelectorAll('input[type="radio"][disabled]').forEach(el => {
                el.removeAttribute('disabled');
            });
        });
    },

    bindSubmitUsageproofButton: (uid) => {
        $('#submit-usageproof-button-' + uid).on('click', function() {
            $.prompt('Möchtest du den Verwendungsnachweis wirklich einreichen?',
                {
                    buttons: {'Ja, jetzt einreichen': true, Abbrechen: false},
                    submit: function(v,m,f) {
                        if(m) {
                            const form = document.getElementById('fundingForm');
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'submit_usageproof';
                            input.value = 1;
                            form.appendChild(input);
                            form.submit();
                        }
                    }
                }
            );
        });
    },

    bindReceiptlistCheckboxPaybackOk: (receiptlistDifference) => {
        $('#fundingForm #fundings-fundingusageproof-payback-ok').on('change', function() {
            const showCheckbox = receiptlistDifference > 0;
            MappedRepairEvents.Funding.onClickHandlerReceiptlistCheckboxPaybackOk($(this), showCheckbox);
        }).trigger('change');
    },

    onClickHandlerReceiptlistCheckboxPaybackOk: (checkbox, showCheckbox) => {
        const checkboxWrapper = checkbox.closest('.input');
        if (showCheckbox) {
            checkboxWrapper.show();
        } else {
            checkboxWrapper.hide();
        }
    },

    showOrHideCheckboxD: (show) => {
        const checkboxWrapper = $('#fundingForm #fundings-fundingusageproof-checkbox-d').closest('.input');
        if (show) {
            checkboxWrapper.show();
        } else {
            checkboxWrapper.hide();
        }
    },

    bindReceiptlistCheckboxA: () => {
        $('#fundingForm #fundings-fundingusageproof-checkbox-a').on('change', function() {
            MappedRepairEvents.Funding.onClickHandlerReceiptlistCheckboxA($(this));
        }).trigger('change');
    },

    onClickHandlerReceiptlistCheckboxA: (checkbox) => {
        const isChecked = checkbox.is(':checked');
        const textarea = checkbox.closest('.inner-wrapper').find('.input.textarea');
        if (isChecked) {
            textarea.show();
        } else {
            textarea.hide();
        }
    },

    bindAddReceiptlistButton: () => {
        $('#add-receiptlist-button').on('click', function() {
            const form = document.getElementById('fundingForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'add_receiptlist';
            input.value = 1;
            form.appendChild(input);
            form.submit();
        });
    },

    bindDeleteReceiptlistCheckboxClickHandler: () => {
        $('#fundingForm .receiptlist-delete-checkbox').on('change', function() {
            MappedRepairEvents.Funding.onClickHandlerDeleteReceiptlistCheckbox();
        });
    },

    onClickHandlerDeleteReceiptlistCheckbox: () => {
        const anyChecked = $('#fundingForm .receiptlist-delete-checkbox').is(':checked');
        const submitButton = $('#fundingForm').find('button[type="submit"]');
        const addButton = $('#fundingForm').find('#add-receiptlist-button');
        addButton.prop('disabled', anyChecked);
        if (anyChecked) {
            submitButton.text('Zwischenspeichern und ausgewählte Beleg(e) löschen');
            submitButton.addClass('red');
        } else {
            submitButton.text('Zwischenspeichern');
            submitButton.removeClass('red');
        }
    },

    initIsMissing: () => {
        $('#fundingForm fieldset:not(.fundinglist) .input.required').find('input, textarea').each(function() {
            if ($(this).val() === '') {
                $(this).closest('.input').addClass('is-missing');
            }
        });
    },

    bindSubmitFundingButton: (uid) => {
        $('#submit-funding-button-' + uid).on('click', function() {
            $.prompt('Möchtest du den Förderantrag wirklich einreichen?',
                {
                    buttons: {'Ja, jetzt einreichen': true, Abbrechen: false},
                    submit: function(v,m,f) {
                        if(m) {
                            const form = document.getElementById('fundingForm');
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'submit_funding';
                            input.value = 1;
                            form.appendChild(input);
                            form.submit();
                        }
                    }
                }
            );
        });
    },

    initTextareaCounter: () => {
        $('#fundingForm textarea').each(function() {
            const maxLength = $(this).attr('maxlength');
            const currentLength = $(this).val().length;
            const counter = $('<span>', {
                class: 'counter',
                style: 'width:75px;',
                text: `${currentLength} / ${maxLength}`
            });

            $(this).after(counter);

            $(this).on('input', function() {
                const updatedLength = $(this).val().length;
                counter.text(`${updatedLength} / ${maxLength}`);
            });
        });
    },

    onClickHandlerVerifiedCheckbox: (fieldName, checked) => {
        const fieldNameInputField = $(`#${fieldName}`);
        const wrapper = fieldNameInputField.closest('.input');
        wrapper.toggleClass('is-pending', !checked);
        wrapper.toggleClass('is-verified', checked);
        fieldNameInputField.prop('readonly', checked);
    },

    onClickHandlerDeleteCheckbox: (uploadType) => {
        const deleteUploadCheckboxes = $('input[name="Fundings[delete_fundinguploads_' + uploadType + '][]"]');
        const deleteUploadCheckboxesChecked = deleteUploadCheckboxes.is(':checked');
        const uploadInput = $('#fundings-files-fundinguploads-' + uploadType.replace('_', '-'));
        uploadInput.prop('disabled', deleteUploadCheckboxesChecked);
        const uploadButton = uploadInput.closest('fieldset').find('.upload-button');
        if (deleteUploadCheckboxesChecked) {
            uploadButton.text('Dateien löschen');
            uploadButton.addClass('red');
        } else {
            uploadButton.text('Dateien hochladen');
            uploadButton.removeClass('red');
        }
    },

    bindDeleteButton: (uid) => {
        $('#funding-delete-button-' + uid).on('click', function() {
            const workshopName = $(this).closest('.workshop-wrapper').find('.heading').text();
            $.prompt('Möchtest du den Förderantrag der Initiative <b>' + workshopName + '</b> wirklich löschen?',
                {
                    buttons: {L\u00f6schen: true, Abbrechen: false},
                    submit: function(v,m,f) {
                        if(m) {
                            document.location.href = '/mein-foerderantrag/delete/' + uid;
                        }
                    }
                }
            );
        });
    },

    updateProgressBar: (verifiedDataCount, totalFieldsCount) => {
        const progressInPercent = verifiedDataCount / totalFieldsCount * 100;
        const progressWrapper = $('#fundingForm .progress-wrapper');
        $('.progress-bar').progressbar({value: progressInPercent});
        if (progressInPercent === 100) {
            progressWrapper.find('p').text('Fortschritt: Alle Felder sind bestätigt, du kannst den Förderantrag jetzt einreichen.');
        } else {
            progressWrapper.find('.verified-count').text(verifiedDataCount);
        }
    },

    initBindDeleteFundinguploads: (uploadType) => {
        $('#fundingForm .input').find('input.is-upload.' + uploadType).each(function() {
            const fieldName = $(this).attr('id');
            const fileuploadIdField = fieldName.replace('filename', 'id');
            const fileuploadId = $(`#${fileuploadIdField}`).val();

            const checkbox = $('<input>', {
                type: 'checkbox',
                value: fileuploadId,
                class: 'is-upload',
                name: 'Fundings[delete_fundinguploads_' + uploadType + '][]',
                checked: false,
                on: {
                    change: (e) => MappedRepairEvents.Funding.onClickHandlerDeleteCheckbox(uploadType, e.target.checked),
                }
            });

            const label = $('<label>', {
                class: 'checkbox delete-upload no-required',
                text: 'löschen?'
            }).append(checkbox);

            label.appendTo($(this).closest('.input'));
        });
    },

    initIsVerified: (isVerifiedData, isAdminView) => {

        const parsedIsVerifiedData = JSON.parse(isVerifiedData);

        $('#fundingForm .input:not(.is-missing)').find('input[type="text"]:not(.no-verify), input[type="checkbox"]:not(.is-upload):not(.no-verify), input[type="email"],  input[type="tel"], textarea:not(.no-verify)').each(function() {

            const fieldName = $(this).attr('id');
            const checked = parsedIsVerifiedData === null ? false : parsedIsVerifiedData.includes(fieldName);

            const checkbox = $('<input>', {
                type: 'checkbox',
                value: fieldName,
                name: 'Fundings[verified_fields][]',
                checked: checked,
                on: {
                    change: (e) => MappedRepairEvents.Funding.onClickHandlerVerifiedCheckbox(fieldName, e.target.checked),
                }
            }).trigger('change');

            const label = $('<label>', {
                class: 'verified checkbox no-required',
                text: 'bestätigt?'
            }).append(checkbox);

            if (!isAdminView) {
                label.appendTo($(this).closest('.input'));
            }
        });
    }
};
