/**
 * Skein Configurator Frontend
 * Handles color selection, slot management, and gradient preview
 */
(function ($) {
    'use strict';

    // ============================================
    // Configuration & Constants
    // ============================================
    const CONFIG = {
        MAX_COLORS: 5,
        INITIAL_FILLED_SLOTS: 3,
        INIT_DELAY: 500,
        GRADIENT_RADIUS_STOPS: {
            1: [0, 97],
            2: [0, 40, 97],
            3: [0, 31, 72, 97],
            4: [0, 22, 66, 80, 97],
            5: [0, 17, 52, 71, 89, 97]
        },
        DEFAULT_COLOR: '#cccccc',
        BORDER_OPACITY: 0.3
    };

    const SELECTORS = {
        lengthSelect: '#skeinLengthSelect',
        selectedLength: '#skeinSelectedLength',
        selectedColors: '#skeinSelectedColors',
        colorSlots: '#skeinColorSlots',
        colorSlot: '.skein-color-slot',
        colorSwatch: '.skein-color-swatch',
        clearIcon: '.slot-clear-icon',
        slotName: '.slot-name',
        gradient: '#skeinGradient',
        modalColorOption: '.swal-color-option'
    };

    // ============================================
    // Utility Functions
    // ============================================
    const Utils = {
        /**
         * Get localized string with fallback
         */
        getString(key, fallback) {
            return skeinConfig?.strings?.[key] || fallback;
        },

        /**
         * Shuffle array using Fisher-Yates algorithm
         */
        shuffleArray(array) {
            const shuffled = [...array];
            for (let i = shuffled.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }
            return shuffled;
        },

        /**
         * Create SVG element with attributes
         */
        createSvgElement(tag, attributes) {
            const element = document.createElementNS('http://www.w3.org/2000/svg', tag);
            Object.entries(attributes).forEach(([key, value]) => {
                element.setAttribute(key, value);
            });
            return element;
        }
    };

    // ============================================
    // Slot Manager
    // ============================================
    const SlotManager = {
        currentSlot: null,

        /**
         * Clear a slot to empty state
         */
        clearSlot($slot) {
            $slot
                .addClass('empty')
                .removeClass('filled')
                .css('background-color', '')
                .removeData('color-name')
                .removeData('color-code')
                .find(SELECTORS.slotName)
                .text(Utils.getString('empty', 'Empty'));
        },

        /**
         * Fill a slot with color
         */
        fillSlot($slot, colorName, colorCode) {
            $slot
                .removeClass('empty')
                .addClass('filled')
                .css('background-color', colorCode)
                .data('color-name', colorName)
                .data('color-code', colorCode)
                .find(SELECTORS.slotName)
                .text(colorName);
        },

        /**
         * Get all filled slots' colors
         */
        getFilledColors() {
            const colors = [];
            $(`${SELECTORS.colorSlot}:not(.empty)`).each(function () {
                colors.push({
                    name: $(this).data('color-name'),
                    code: $(this).data('color-code')
                });
            });
            return colors;
        },

        /**
         * Get available colors from swatches
         */
        getAvailableColors() {
            const colors = [];
            $(SELECTORS.colorSwatch).each(function () {
                colors.push({
                    name: $(this).data('color-name'),
                    code: $(this).data('color-code')
                });
            });
            return colors;
        },

        /**
         * Update hidden input with selected colors
         */
        updateHiddenInput() {
            const colors = this.getFilledColors();
            $(SELECTORS.selectedColors).val(JSON.stringify(colors));
        }
    };

    // ============================================
    // Gradient Manager
    // ============================================
    const GradientManager = {
        /**
         * Update the SVG radial gradient overlay
         */
        update() {
            const gradientElement = document.querySelector(SELECTORS.gradient);
            if (!gradientElement) return;

            const colors = this.getGradientColors();
            this.clearStops(gradientElement);
            this.createStops(gradientElement, colors);
        },

        /**
         * Get colors for gradient (max 5)
         */
        getGradientColors() {
            const colors = [];
            $(`${SELECTORS.colorSlot}:not(.empty)`).each(function () {
                const colorCode = $(this).data('color-code');
                if (colorCode && colors.length < CONFIG.MAX_COLORS) {
                    colors.push(colorCode);
                }
            });
            return colors.length > 0 ? colors : [CONFIG.DEFAULT_COLOR];
        },

        /**
         * Clear all gradient stops
         */
        clearStops(element) {
            while (element.firstChild) {
                element.removeChild(element.firstChild);
            }
        },

        /**
         * Create gradient stops
         */
        createStops(element, colors) {
            const stops = CONFIG.GRADIENT_RADIUS_STOPS[colors.length];

            colors.forEach((color, index) => {
                const stop = Utils.createSvgElement('stop', {
                    offset: `${stops[index + 1]}%`,
                    'stop-color': color,
                    'stop-opacity': '1'
                });
                element.appendChild(stop);
            });

            // Add transparent border ring
            const lastColor = colors[colors.length - 1];
            const borderStop = Utils.createSvgElement('stop', {
                offset: '100%',
                'stop-color': lastColor,
                'stop-opacity': String(CONFIG.BORDER_OPACITY)
            });
            element.appendChild(borderStop);
        }
    };

    // ============================================
    // Modal Manager
    // ============================================
    const ModalManager = {
        /**
         * Build color grid HTML for modal
         */
        buildColorGridHtml($currentSlot) {
            const currentColorName = $currentSlot.data('color-name') || '';
            const isEmptySlot = $currentSlot.hasClass('empty');
            const emptySlotText = Utils.getString('emptySlot', 'Empty Slot');

            let html = '<div class="swal-color-grid">';

            // Empty slot option
            const emptySelected = isEmptySlot ? ' selected' : '';
            html += `
                <button class="swal-color-option empty-slot-option${emptySelected}" 
                        data-color-name="Empty" data-color-code="" title="${emptySlotText}">
                    <svg class="empty-slot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor" width="24" height="24"><path d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>
                    <span>${emptySlotText}</span>
                </button>`;

            // Color options
            $(SELECTORS.colorSwatch).each(function () {
                const colorName = $(this).data('color-name');
                const colorCode = $(this).data('color-code');
                const selectedClass = (!isEmptySlot && colorName === currentColorName) ? ' selected' : '';

                html += `
                    <button class="swal-color-option${selectedClass}" 
                            data-color-name="${colorName}" 
                            data-color-code="${colorCode}" 
                            style="background-color: ${colorCode};" 
                            title="${colorName}">
                        <span>${colorName}</span>
                    </button>`;
            });

            html += '</div>';
            return html;
        },

        /**
         * Handle color selection in modal
         */
        handleColorSelection(colorName, colorCode) {
            const $slot = SlotManager.currentSlot;

            if (colorName === 'Empty') {
                SlotManager.clearSlot($slot);
            } else {
                SlotManager.fillSlot($slot, colorName, colorCode);
            }

            SlotManager.updateHiddenInput();
            GradientManager.update();
        },

        /**
         * Update visual selection state in modal
         */
        updateModalSelection(selectedButton) {
            document.querySelectorAll(SELECTORS.modalColorOption).forEach(btn => {
                btn.classList.remove('selected');
            });
            selectedButton.classList.add('selected');
        },

        /**
         * Prompt user for next empty slot
         */
        promptNextSlot() {
            const $emptySlots = $(`${SELECTORS.colorSlot}.empty`);

            if ($emptySlots.length === 0) {
                Swal.close();
                return;
            }

            Swal.fire({
                title: Utils.getString('colorSelected', 'Color Selected'),
                text: Utils.getString('selectAnotherColor', 'Do you want to select a color for another empty slot?'),
                showCancelButton: true,
                confirmButtonText: Utils.getString('yes', 'Yes'),
                cancelButtonText: Utils.getString('no', 'No')
            }).then((result) => {
                if (result.isConfirmed) {
                    this.open($emptySlots.first());
                }
            });
        },

        /**
         * Open color selection modal
         */
        open($slot) {
            SlotManager.currentSlot = $slot;

            Swal.fire({
                title: Utils.getString('selectColor', 'Select Color'),
                html: this.buildColorGridHtml($slot),
                showCancelButton: true,
                showConfirmButton: false,
                cancelButtonText: Utils.getString('close', 'Close'),
                width: '800px',
                customClass: {
                    popup: 'skein-swal-popup',
                    htmlContainer: 'skein-swal-html',
                    cancelButton: 'skein-swal-cancel'
                },
                didOpen: () => this.bindModalEvents()
            });
        },

        /**
         * Bind click events to modal color options
         */
        bindModalEvents() {
            const self = this;

            document.querySelectorAll(SELECTORS.modalColorOption).forEach(btn => {
                btn.addEventListener('click', function () {
                    const colorName = this.getAttribute('data-color-name');
                    const colorCode = this.getAttribute('data-color-code');

                    self.handleColorSelection(colorName, colorCode);
                    self.updateModalSelection(this);

                    if (colorName === 'Empty') {
                        Swal.close();
                    } else {
                        self.promptNextSlot();
                    }
                });
            });
        }
    };

    // ============================================
    // Event Handlers
    // ============================================
    const EventHandlers = {
        /**
         * Initialize all event handlers
         */
        init() {
            this.bindLengthSelect();
            this.bindSlotClick();
            this.bindClearClick();
            this.initSortable();
        },

        /**
         * Handle length selection change
         */
        bindLengthSelect() {
            $(SELECTORS.lengthSelect).on('change', function () {
                $(SELECTORS.selectedLength).val($(this).val());
            });

            // Initialize on load
            const initialLength = $(SELECTORS.lengthSelect).val();
            if (initialLength) {
                $(SELECTORS.selectedLength).val(initialLength);
            }
        },

        /**
         * Handle slot clicks
         */
        bindSlotClick() {
            $(document).on('click', SELECTORS.colorSlot, function (e) {
                if ($(e.target).closest(SELECTORS.clearIcon).length > 0) return;

                e.preventDefault();
                ModalManager.open($(this));
            });
        },

        /**
         * Handle clear icon clicks
         */
        bindClearClick() {
            $(document).on('click', SELECTORS.clearIcon, function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $slot = $(this).closest(SELECTORS.colorSlot);
                SlotManager.clearSlot($slot);
                SlotManager.updateHiddenInput();
                GradientManager.update();
            });
        },

        /**
         * Initialize SortableJS
         */
        initSortable() {
            const element = document.querySelector(SELECTORS.colorSlots);
            if (!element || typeof Sortable === 'undefined') return;

            new Sortable(element, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                handle: SELECTORS.colorSlot,
                forceFallback: false,
                onEnd() {
                    SlotManager.updateHiddenInput();
                    GradientManager.update();
                }
            });
        }
    };

    // ============================================
    // Initialization
    // ============================================
    const App = {
        /**
         * Preset random colors on initial load
         */
        presetRandomColors() {
            const availableColors = SlotManager.getAvailableColors();
            if (availableColors.length === 0) return;

            const shuffledColors = Utils.shuffleArray(availableColors);

            $(SELECTORS.colorSlot).each(function (index) {
                if (index < CONFIG.INITIAL_FILLED_SLOTS && index < shuffledColors.length) {
                    SlotManager.fillSlot($(this), shuffledColors[index].name, shuffledColors[index].code);
                }
            });

            SlotManager.updateHiddenInput();
        },

        /**
         * Initialize the application
         */
        init() {
            EventHandlers.init();

            // Wait for SVG to be ready
            setTimeout(() => {
                this.presetRandomColors();
                GradientManager.update();
            }, CONFIG.INIT_DELAY);
        }
    };

    // Start application when DOM is ready
    $(document).ready(() => App.init());

})(jQuery);
