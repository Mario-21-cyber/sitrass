<?php

// ---------------------------------------------------------------------
// ALIAS ROUTE PARA SA CHAT.
// Ang orihinal na URL na "chat/open/{id}" ay hinaharang ng security
// system ng InfinityFree (403 Forbidden bago pa maabot ang PHP code).
// Kaya ginawa natin ang "messages/view/{id}" - ligtas na URL pattern na
// katulad ng mga proven na gumaganang routes (hal. customer/myBookings).
// Pareho lang ang ginagawa nito sa ChatController::open().
// ---------------------------------------------------------------------
class MessagesController extends ChatController {

    public function view($bookingId) {
        $this->open($bookingId);
    }

    // Alias para sa rental chat (katulad ng view() ng booking)
    public function viewRental($rentalId) {
        $this->openRental($rentalId);
    }
}
