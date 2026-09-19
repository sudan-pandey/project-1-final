/**
 * ClubManager Events Dashboard JavaScript
 * Handles accessible modal controls, focus trapping, ESC key listener, and backdrop clicks.
 */

document.addEventListener('DOMContentLoaded', function () {
    // DOM Elements
    const modalOverlay = document.getElementById('eventModalOverlay');
    const modalContent = modalOverlay ? modalOverlay.querySelector('.modal-content') : null;
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalCancelBtn = document.getElementById('modalCancelBtn');
    const openModalBtns = document.querySelectorAll('.btn-open-modal');
    const createEventForm = document.getElementById('createEventForm');
    const eventTitleInput = document.getElementById('eventTitle');

    let previousActiveElement = null;

    /**
     * Open Modal Handler
     * @param {Event} event
     */
    function openModal(event) {
        if (event) {
            event.preventDefault();
        }
        previousActiveElement = document.activeElement;

        if (modalOverlay) {
            modalOverlay.classList.add('active');
            modalOverlay.setAttribute('aria-hidden', 'false');

            // Lock body scroll when modal is open
            document.body.style.overflow = 'hidden';

            // Focus on first input in modal after CSS transition
            setTimeout(function () {
                if (eventTitleInput) {
                    eventTitleInput.focus();
                } else if (modalContent) {
                    modalContent.focus();
                }
            }, 50);
        }
    }

    /**
     * Close Modal Handler
     */
    function closeModal() {
        if (modalOverlay && modalOverlay.classList.contains('active')) {
            modalOverlay.classList.remove('active');
            modalOverlay.setAttribute('aria-hidden', 'true');

            // Restore body scroll
            document.body.style.overflow = '';

            // Restore focus to element that opened the modal
            if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
                previousActiveElement.focus();
            }
        }
    }

    // Attach open modal listeners to all trigger buttons
    openModalBtns.forEach(function (btn) {
        btn.addEventListener('click', openModal);
    });

    // Close modal via close (×) button and Cancel button
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }
    if (modalCancelBtn) {
        modalCancelBtn.addEventListener('click', closeModal);
    }

    // Close modal when clicking outside on the backdrop overlay
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function (e) {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });
    }

    // Keyboard Navigation: Close on ESC key and trap focus within modal
    document.addEventListener('keydown', function (e) {
        if (!modalOverlay || !modalOverlay.classList.contains('active')) {
            return;
        }

        // Close on ESC
        if (e.key === 'Escape' || e.key === 'Esc') {
            closeModal();
            return;
        }

        // Focus Trap Inside Modal
        if (e.key === 'Tab') {
            const focusableElements = modalOverlay.querySelectorAll(
                'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );

            if (focusableElements.length === 0) return;

            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.shiftKey) { // Shift + Tab
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else { // Tab
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        }
    });

    // Form submission handling
    if (createEventForm) {
        createEventForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const title = document.getElementById('eventTitle') ? document.getElementById('eventTitle').value : '';
            const dateTime = document.getElementById('eventDateTime') ? document.getElementById('eventDateTime').value : '';
            const location = document.getElementById('eventLocation') ? document.getElementById('eventLocation').value : '';
            const description = document.getElementById('eventDescription') ? document.getElementById('eventDescription').value : '';

            if (!title || !dateTime || !description) {
                alert('Please fill in all required fields.');
                return;
            }

            // Simple demo handling - close modal and alert
            closeModal();
            createEventForm.reset();
            alert('Event "' + title + '" created successfully!');
        });
    }
});
