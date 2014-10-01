<?php
require_once('./lib/Stripe.php');
// Set your secret key: remember to change this to your live secret key in production
// See your keys here https://manage.stripe.com/account
Stripe::setApiKey("sk_test_6z2IkNNVv6ER0o4GV3lDSdd2");

// Get the credit card details submitted by the form
$token = $_POST['stripeToken'];

// Create the charge on Stripe's servers - this will charge the user's card
try {
$charge = Stripe_Charge::create(array(
  "amount" => 1000, // amount in cents, again
  "currency" => "gbp",
  "card" => $token,
  "description" => "payinguser@example.com")
);
} catch(Stripe_CardError $e) {
  // The card has been declined
}
?>