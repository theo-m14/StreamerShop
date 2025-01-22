import * as React from 'react';
import {
  EmbeddedCheckoutProvider,
  EmbeddedCheckout
} from '@stripe/react-stripe-js';
import {loadStripe} from '@stripe/stripe-js';
import AdressSelection from './AdressSelection';
// Make sure to call `loadStripe` outside of a component’s render to avoid
// recreating the `Stripe` object on every render.
export default function ({cart}) {

  const [clientSecret, setClientSecret] = React.useState(null);

  const stripePromise = loadStripe('pk_test_51O0mx9Ddah1uLYmzf0SMrTBkSFADmF9sR63AdXlea9ZT8OOfMrzhQIBb62SVTIiTwrnbYbV8QXPJsvtooYRudZb600UHHJ3MFq');
  const options = {clientSecret,onComplete: () => {
    fetch('/createOrder', {
      method: 'POST',
      body: JSON.stringify({session_id: sessionId,cart: cart,total: total,adress: adress}),
    });
  }};
  const [adress, setAdress] = React.useState(null);
  return (
    <>
      <AdressSelection setAdress={setAdress} setClientSecret={setClientSecret} cart={cart} />
      {adress && clientSecret && (
        <div className="container is-max-desktop">
          <EmbeddedCheckoutProvider
            stripe={stripePromise}
            options={options}
        >
          <EmbeddedCheckout className="box mt-6 mb-6" id="embedded-checkout-stripe" />
        </EmbeddedCheckoutProvider>
        </div>
      )}
    </>
  )
}