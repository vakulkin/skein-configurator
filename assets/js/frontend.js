jQuery(document).ready(function ($) {
    var currentSlot = null;

    // Handle length selection change
    $('#skeinLengthSelect').on('change', function () {
        var selectedValue = $(this).val();
        $('#skeinSelectedLength').val(selectedValue);
    });

    // Initialize length on page load
    var initialLength = $('#skeinLengthSelect').val();
    if (initialLength) {
        $('#skeinSelectedLength').val(initialLength);
    }

    // Function to open color selection modal
    function openColorModal(slot) {
        currentSlot = slot;

        // Build color options HTML
        var colorsHtml = '<div class="swal-color-grid">';
        // Add Empty Slot option first
        colorsHtml += '<button class="swal-color-option empty-slot-option" data-color-name="Empty" data-color-code="" title="Empty Slot">' +
            '<span>Empty Slot</span>' +
            '</button>';
        $('.skein-color-swatch').each(function () {
            var colorName = $(this).data('color-name');
            var colorCode = $(this).data('color-code');
            colorsHtml += '<button class="swal-color-option" data-color-name="' + colorName + '" data-color-code="' + colorCode + '" style="background-color: ' + colorCode + ';" title="' + colorName + '">' +
                '<span>' + colorName + '</span>' +
                '</button>';
        });
        colorsHtml += '</div>';

        // Show SweetAlert2 modal
        Swal.fire({
            title: 'Select Color',
            html: colorsHtml,
            showCancelButton: true,
            showConfirmButton: false,
            cancelButtonText: 'Close',
            width: '600px',
            customClass: {
                popup: 'skein-swal-popup',
                htmlContainer: 'skein-swal-html',
                cancelButton: 'skein-swal-cancel'
            },
            didOpen: () => {
                // Handle color selection
                document.querySelectorAll('.swal-color-option').forEach(btn => {
                    btn.addEventListener('click', function () {
                        var colorName = this.getAttribute('data-color-name');
                        var colorCode = this.getAttribute('data-color-code');

                        if (colorName === 'Empty') {
                            // Empty slot
                            currentSlot
                                .addClass('empty')
                                .css('background-color', '')
                                .removeData('color-name')
                                .removeData('color-code')
                                .find('.slot-name')
                                .text('Empty');
                        } else {
                            // Select color
                            currentSlot
                                .removeClass('empty')
                                .css('background-color', colorCode)
                                .data('color-name', colorName)
                                .data('color-code', colorCode)
                                .find('.slot-name')
                                .text(colorName);
                        }

                        updateSelectedColors();
                        updateGradientOverlay();

                        if (colorName !== 'Empty') {
                            // Check if there are empty slots
                            var emptySlots = $('.skein-color-slot.empty');
                            if (emptySlots.length > 0) {
                                Swal.fire({
                                    title: 'Color Selected',
                                    text: 'Do you want to select a color for another empty slot?',
                                    showCancelButton: true,
                                    confirmButtonText: 'Yes',
                                    cancelButtonText: 'No'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Open modal for next empty slot
                                        var nextSlot = emptySlots.first();
                                        openColorModal(nextSlot);
                                    } else {
                                        Swal.close();
                                    }
                                });
                            } else {
                                Swal.close();
                            }
                        } else {
                            Swal.close();
                        }
                    });
                });
            }
        });
    }

    // Handle slot clicks - open SweetAlert2 modal
    $(document).on('click', '.skein-color-slot', function (e) {
        e.preventDefault();
        openColorModal($(this));
    });

    // Initialize SortableJS for color slots
    var colorSlotsElement = document.getElementById('skeinColorSlots');
    if (colorSlotsElement && typeof Sortable !== 'undefined') {
        new Sortable(colorSlotsElement, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            handle: '.skein-color-slot',
            forceFallback: false,
            onEnd: function (evt) {
                updateSelectedColors();
                updateGradientOverlay();
            }
        });
    }

    // Update hidden input with selected colors
    function updateSelectedColors() {
        var colors = [];
        $('.skein-color-slot:not(.empty)').each(function () {
            colors.push({
                name: $(this).data('color-name'),
                code: $(this).data('color-code')
            });
        });
        $('#skeinSelectedColors').val(JSON.stringify(colors));
    }

    // Update the radial gradient overlay - supports up to 5 colors
    function updateGradientOverlay() {
        // Get SVG gradient element
        var gradientElement = document.getElementById('skeinGradient');
        if (!gradientElement) {
            console.log('No gradient element found');
            return;
        }

        // Get selected colors from slots (up to 5)
        var selectedColors = [];
        $('.skein-color-slot:not(.empty)').each(function () {
            var colorCode = $(this).data('color-code');
            if (colorCode && selectedColors.length < 5) {
                selectedColors.push(colorCode);
            }
        });

        // Use default gray if no colors selected
        if (selectedColors.length === 0) {
            selectedColors = ['#cccccc'];
        }

        // Clear existing stops
        while (gradientElement.firstChild) {
            gradientElement.removeChild(gradientElement.firstChild);
        }

        // Manual radius definitions for each color count (1-5 colors)
        // Each array defines the boundary points for concentric circles
        // Last stop at 95% to leave space for transparent border ring
        var numColors = selectedColors.length;
        var radiusStops = {
            1: [0, 97],                       // 1 color: full circle to 95%
            2: [0, 40, 97],                   // 2 colors: adjusted proportionally
            3: [0, 31, 72, 97],               // 3 colors: adjusted proportionally
            4: [0, 22, 61, 82, 97],           // 4 colors: adjusted proportionally
            5: [0, 17, 52, 71, 89, 97]        // 5 colors: adjusted proportionally
        };

        var stops = radiusStops[numColors];

        // Create smooth gradient with blended colors
        selectedColors.forEach(function (color, index) {
            var radius = stops[index + 1];

            var stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
            stop.setAttribute('offset', radius + '%');
            stop.setAttribute('stop-color', color);
            stop.setAttribute('stop-opacity', '1');
            gradientElement.appendChild(stop);
        });

        // Add transparent ring as last ring for semi-transparent border effect
        if (selectedColors.length > 0) {
            var lastColor = selectedColors[selectedColors.length - 1];
            var transparentStop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
            transparentStop.setAttribute('offset', '100%');
            transparentStop.setAttribute('stop-color', lastColor);
            transparentStop.setAttribute('stop-opacity', '0.3');
            gradientElement.appendChild(transparentStop);
        }

        console.log('Gradient updated with ' + numColors + ' colors:', selectedColors);
    }

    // Preset random non-repeated colors on page load
    function presetRandomColors() {
        var availableColors = [];
        $('.skein-color-swatch').each(function () {
            availableColors.push({
                name: $(this).data('color-name'),
                code: $(this).data('color-code')
            });
        });

        if (availableColors.length === 0) {
            return;
        }

        // Shuffle colors array
        var shuffledColors = availableColors.slice();
        for (var i = shuffledColors.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var temp = shuffledColors[i];
            shuffledColors[i] = shuffledColors[j];
            shuffledColors[j] = temp;
        }

        // Assign random colors to slots (only first 3)
        $('.skein-color-slot').each(function (index) {
            if (index < 3 && index < shuffledColors.length) {
                var color = shuffledColors[index];
                $(this)
                    .removeClass('empty')
                    .css('background-color', color.code)
                    .data('color-name', color.name)
                    .data('color-code', color.code)
                    .find('.slot-name')
                    .text(color.name);
            }
        });

        updateSelectedColors();
    }

    // Initialize on page load (wait for SVG to be ready)
    setTimeout(function () {
        presetRandomColors();
        updateGradientOverlay();
    }, 500);
});
